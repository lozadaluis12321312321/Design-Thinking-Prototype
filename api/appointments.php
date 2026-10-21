<?php
require_once 'db.php';

ensureArchiveColumns($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'unavailable_times') {
        $doctorId = $_GET['doctorId'];
        $date = $_GET['date'];
        $stmt = $pdo->prepare("SELECT time FROM appointments WHERE doctorId = ? AND date = ? AND isActive = 1 AND status NOT IN ('Cancelled', 'Completed')");
        $stmt->execute([$doctorId, $date]);
        $times = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode($times);
        exit;
    }
    
    checkAuth();
    // Admin gets every appointment, active and archived; the UI splits them.
    $stmt = $pdo->query("SELECT a.*, d.name as doctorName FROM appointments a LEFT JOIN doctors d ON a.doctorId = d.id ORDER BY a.id DESC");
    echo json_encode($stmt->fetchAll());
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $data['contactNumber'] = cleanPhone($data['contactNumber'] ?? '', 'contactNumber', true);

    $doc = $pdo->prepare("SELECT id FROM doctors WHERE id = ? AND isActive = 1");
    $doc->execute([$data['doctorId']]);
    if (!$doc->fetch()) fail(400, "That doctor is not currently accepting appointments.", ['field' => 'doctorId']);
    
    // Check for existing appointment
    $checkStmt = $pdo->prepare("SELECT id FROM appointments WHERE doctorId = ? AND date = ? AND time = ? AND isActive = 1 AND status NOT IN ('Cancelled', 'Completed')");
    $checkStmt->execute([$data['doctorId'], $data['date'], $data['time']]);
    if ($checkStmt->rowCount() > 0) {
        http_response_code(409); // Conflict
        header('Content-Type: application/json');
        echo json_encode(["error" => "The doctor is already booked for this date and time."]);
        exit;
    }

    $createdDate = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO appointments (customerName, contactNumber, email, doctorId, type, date, time, notes, status, createdDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
    $stmt->execute([
        $data['customerName'], $data['contactNumber'], $data['email'], $data['doctorId'], 
        $data['type'], $data['date'], $data['time'], $data['notes'], $createdDate
    ]);
    echo json_encode(["id" => $pdo->lastInsertId(), "message" => "Appointment booked"]);
} elseif ($method === 'PUT') {
    checkAuth();
    preg_match('#^/(\d+)/(status|archive|restore)#', requestPath(), $matches);
    if (!isset($matches[1])) fail(400, "Missing appointment id");
    $id = intval($matches[1]);
    if ($matches[2] === 'status') {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$data['status'], $id]);
        echo json_encode(["message" => "Status updated"]);
    } else {
        setActive($pdo, 'appointments', $id, $matches[2], 'Appointment');
    }
} elseif ($method === 'DELETE') {
    checkAuth();
    fail(405, "Appointments are archived rather than deleted. Use PUT /{id}/archive.");
}
