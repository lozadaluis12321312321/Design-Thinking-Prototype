<?php
/**
 * Customer accounts.
 *   POST customer_auth.php?action=register  { fullName, email, password, contactNumber? }
 *   POST customer_auth.php?action=login     { email, password }
 *   GET  customer_auth.php?action=me        (Bearer cust_... token)
 *   POST customer_auth.php?action=logout    (Bearer cust_... token)
 * Register and login answer { token, customer }. Tokens last 30 days.
 */
require_once 'db.php';

ensurePatientTables($pdo);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

function publicCustomer($c) {
    return ['id' => (int)$c['id'], 'fullName' => $c['fullName'], 'email' => $c['email'], 'contactNumber' => $c['contactNumber'], 'createdAt' => $c['createdAt']];
}

function issueToken($pdo, $customerId) {
    $token = 'cust_' . bin2hex(random_bytes(24));
    $pdo->prepare("INSERT INTO customer_tokens (customerId, tokenHash, expiresAt) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))")
        ->execute([$customerId, hash('sha256', $token)]);
    // Opportunistic cleanup so the table does not grow forever.
    $pdo->exec("DELETE FROM customer_tokens WHERE expiresAt < NOW()");
    return $token;
}

if ($method === 'POST' && $action === 'register') {
    $data = jsonBody();
    $fullName = trim($data['fullName'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    if ($fullName === '' || mb_strlen($fullName) > 255) fail(400, "Please enter your full name.", ['field' => 'fullName']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail(400, "Please enter a valid email address.", ['field' => 'email']);
    if (strlen($password) < 8) fail(400, "Your password must be at least 8 characters.", ['field' => 'password']);
    $contact = cleanPhone($data['contactNumber'] ?? '', 'contactNumber');

    $exists = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $exists->execute([$email]);
    if ($exists->fetch()) fail(409, "An account with that email already exists. Try signing in.", ['field' => 'email']);

    $now = date('Y-m-d H:i:s');
    $pdo->prepare("INSERT INTO customers (fullName, email, password, contactNumber, createdAt) VALUES (?, ?, ?, ?, ?)")
        ->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $contact, $now]);
    $id = (int)$pdo->lastInsertId();
    // Every customer gets an (empty) patient record they can fill in from "My record".
    $pdo->prepare("INSERT INTO patient_records (customerId, createdAt, updatedAt) VALUES (?, ?, ?)")->execute([$id, $now, $now]);

    http_response_code(201);
    echo json_encode(['token' => issueToken($pdo, $id), 'customer' => publicCustomer(['id' => $id, 'fullName' => $fullName, 'email' => $email, 'contactNumber' => $contact, 'createdAt' => $now])]);

} elseif ($method === 'POST' && $action === 'login') {
    $data = jsonBody();
    $email = strtolower(trim($data['email'] ?? ''));
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $customer = $stmt->fetch();
    if (!$customer || !password_verify($data['password'] ?? '', $customer['password'])) fail(400, "That email or password is not correct.");
    if (isset($customer['isActive']) && !$customer['isActive']) fail(403, "This account has been deactivated by the clinic. Please contact them for help.");
    echo json_encode(['token' => issueToken($pdo, $customer['id']), 'customer' => publicCustomer($customer)]);

} elseif ($method === 'GET' && $action === 'me') {
    echo json_encode(publicCustomer(requireCustomer($pdo)));

} elseif ($method === 'POST' && $action === 'logout') {
    $pdo->prepare("DELETE FROM customer_tokens WHERE tokenHash = ?")->execute([hash('sha256', bearerToken())]);
    echo json_encode(['message' => 'Signed out']);

} else {
    fail(404, "Unknown action");
}
