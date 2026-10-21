<?php
/**
 * Patient records (one per customer account).
 *
 * Customer (Bearer cust_... token):
 *   GET  patient_records.php                 own record + profile
 *   PUT  patient_records.php                 update own record (and fullName / contactNumber)
 *
 * Admin (Bearer admin token):
 *   GET    patient_records.php?q=search       all records, newest update first
 *   GET    patient_records.php/{id}           one record
 *   PUT    patient_records.php/{id}           update any field incl. clinicalNotes
 *   PUT    patient_records.php/{id}/archive   set the customer inactive (record kept, sign-in blocked)
 *   PUT    patient_records.php/{id}/restore   re-activate the customer
 * Records are never deleted; archived ones stay visible to admins under the Archived tab.
 */
require_once 'db.php';

ensurePatientTables($pdo);
$method = $_SERVER['REQUEST_METHOD'];

const SEXES = ['Female', 'Male', 'Other', 'Prefer not to say'];
const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'];
const CORRECTION = ['None', 'Glasses', 'Contact lenses', 'Both'];

// column => [max length, allowed values or null]
const RECORD_FIELDS = [
    'dateOfBirth' => ['date', null], 'sex' => [20, SEXES], 'address' => [500, null], 'bloodType' => [5, BLOOD_TYPES],
    'allergies' => [2000, null], 'medications' => [2000, null], 'conditions' => [2000, null], 'eyeConditions' => [2000, null],
    'wearsCorrection' => [30, CORRECTION], 'lastEyeExam' => ['date', null], 'prescription' => [2000, null],
    'emergencyContactName' => [255, null], 'emergencyContactPhone' => [100, null], 'notes' => [5000, null],
];
const ADMIN_ONLY_FIELDS = ['clinicalNotes' => [10000, null]];

const SELECT_RECORD = "SELECT r.*, c.fullName, c.email, c.contactNumber, c.createdAt AS accountCreatedAt, c.isActive, c.archivedAt
                       FROM patient_records r JOIN customers c ON c.id = r.customerId";

function validDate($s) {
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s && $s <= date('Y-m-d');
}

// Returns [column => value] for the fields present in $data, or exits with 400.
function cleanRecord($data, $allowed) {
    $out = [];
    foreach ($allowed as $field => [$limit, $options]) {
        if (!array_key_exists($field, $data)) continue;
        $v = $data[$field];
        $v = is_string($v) ? trim($v) : $v;
        if ($v === '' || $v === null) { $out[$field] = null; continue; }
        if (!is_string($v)) fail(400, "Invalid value for $field.", ['field' => $field]);
        if ($field === 'emergencyContactPhone') { $out[$field] = cleanPhone($v, $field); continue; }
        if ($limit === 'date') {
            if (!validDate($v)) fail(400, "Please enter a valid date (not in the future) for $field.", ['field' => $field]);
        } elseif (mb_strlen($v) > $limit) {
            fail(400, "$field is too long (max $limit characters).", ['field' => $field]);
        }
        if ($options && !in_array($v, $options, true)) fail(400, "Invalid choice for $field.", ['field' => $field]);
        $out[$field] = $v;
    }
    return $out;
}

function cleanProfile($data) {
    $out = [];
    if (array_key_exists('fullName', $data)) {
        $name = trim((string)$data['fullName']);
        if ($name === '' || mb_strlen($name) > 255) fail(400, "Please enter a full name.", ['field' => 'fullName']);
        $out['fullName'] = $name;
    }
    if (array_key_exists('contactNumber', $data)) $out['contactNumber'] = cleanPhone($data['contactNumber'], 'contactNumber');
    return $out;
}

function shape($row, $includeClinical) {
    if (!$row) return null;
    unset($row['password']);
    $row['id'] = (int)$row['id'];
    $row['customerId'] = (int)$row['customerId'];
    if (!$includeClinical) unset($row['clinicalNotes']);
    return $row;
}

function fetchRecord($pdo, $where, $params) {
    $stmt = $pdo->prepare(SELECT_RECORD . " WHERE $where");
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

// Apply record + profile updates inside one transaction and return the fresh row.
function applyUpdate($pdo, $recordId, $customerId, $record, $profile) {
    if (!$record && !$profile) fail(400, "Nothing to update.");
    $pdo->beginTransaction();
    if ($record) {
        $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($record)));
        $pdo->prepare("UPDATE patient_records SET $sets, updatedAt = ? WHERE id = ?")->execute([...array_values($record), date('Y-m-d H:i:s'), $recordId]);
    } else {
        $pdo->prepare("UPDATE patient_records SET updatedAt = ? WHERE id = ?")->execute([date('Y-m-d H:i:s'), $recordId]);
    }
    if ($profile) {
        $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($profile)));
        $pdo->prepare("UPDATE customers SET $sets WHERE id = ?")->execute([...array_values($profile), $customerId]);
    }
    $pdo->commit();
}

// Make sure a customer has a record row (accounts created before this feature, or if the insert failed).
function ownRecord($pdo, $customer) {
    $row = fetchRecord($pdo, "r.customerId = ?", [$customer['id']]);
    if ($row) return $row;
    $now = date('Y-m-d H:i:s');
    $pdo->prepare("INSERT INTO patient_records (customerId, createdAt, updatedAt) VALUES (?, ?, ?)")->execute([$customer['id'], $now, $now]);
    return fetchRecord($pdo, "r.customerId = ?", [$customer['id']]);
}

preg_match('#^/(\d+)(?:/(archive|restore))?#', requestPath(), $m);
$pathId = isset($m[1]) ? intval($m[1]) : null;
$pathAction = $m[2] ?? '';

if (isAdmin()) {
    if ($method === 'GET' && $pathId === null) {
        $q = trim($_GET['q'] ?? '');
        $params = [];
        $where = '';
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where = "WHERE c.fullName LIKE ? OR c.email LIKE ? OR c.contactNumber LIKE ?";
            $params = [$like, $like, $like];
        }
        $stmt = $pdo->prepare(SELECT_RECORD . " $where ORDER BY r.updatedAt DESC, r.id DESC");
        $stmt->execute($params);
        echo json_encode(array_map(fn($r) => shape($r, true), $stmt->fetchAll()));

    } elseif ($method === 'GET') {
        if ($pathAction !== '') fail(404, "Record not found");
        $row = fetchRecord($pdo, "r.id = ?", [$pathId]);
        if (!$row) fail(404, "Record not found");
        echo json_encode(shape($row, true));

    } elseif ($method === 'PUT' && $pathId !== null && $pathAction !== '') {
        $row = fetchRecord($pdo, "r.id = ?", [$pathId]);
        if (!$row) fail(404, "Record not found");
        // Archiving keeps the record but deactivates the customer account (sign-in blocked).
        $active = $pathAction === 'restore' ? 1 : 0;
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE customers SET isActive = ?, archivedAt = ? WHERE id = ?")->execute([$active, $active ? null : date('Y-m-d H:i:s'), $row['customerId']]);
        $pdo->prepare("UPDATE patient_records SET updatedAt = ? WHERE id = ?")->execute([date('Y-m-d H:i:s'), $pathId]);
        if (!$active) $pdo->prepare("DELETE FROM customer_tokens WHERE customerId = ?")->execute([$row['customerId']]);
        $pdo->commit();
        echo json_encode(["message" => "Patient " . ($active ? "restored" : "archived"), "isActive" => $active]);

    } elseif ($method === 'PUT' && $pathId !== null) {
        $row = fetchRecord($pdo, "r.id = ?", [$pathId]);
        if (!$row) fail(404, "Record not found");
        $data = jsonBody();
        applyUpdate($pdo, $pathId, $row['customerId'], cleanRecord($data, RECORD_FIELDS + ADMIN_ONLY_FIELDS), cleanProfile($data));
        echo json_encode(shape(fetchRecord($pdo, "r.id = ?", [$pathId]), true));

    } elseif ($method === 'DELETE') {
        fail(405, "Patient records are archived rather than deleted. Use PUT /{id}/archive.");

    } else {
        fail(405, "Method not allowed");
    }
    exit();
}

// ---- customer ----
$customer = requireCustomer($pdo);

if ($method === 'GET') {
    echo json_encode(shape(ownRecord($pdo, $customer), false));

} elseif ($method === 'PUT') {
    $row = ownRecord($pdo, $customer);
    $data = jsonBody();
    applyUpdate($pdo, $row['id'], $customer['id'], cleanRecord($data, RECORD_FIELDS), cleanProfile($data));
    echo json_encode(shape(ownRecord($pdo, $customer), false));

} else {
    fail(405, "Method not allowed");
}
