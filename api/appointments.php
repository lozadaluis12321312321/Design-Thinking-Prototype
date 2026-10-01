<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    checkAuth();
    $stmt = $pdo->query("SELECT a.*, d.name as doctorName FROM appointments a LEFT JOIN doctors d ON a.doctorId = d.id ORDER BY a.id DESC");
    echo json_encode($stmt->fetchAll());
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Check for existing appointment
    $checkStmt = $pdo->prepare("SELECT id FROM appointments WHERE doctorId = ? AND date = ? AND time = ? AND status != 'Cancelled'");
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
    preg_match('/appointments\.php\/(\d+)\/status/', $_SERVER['REQUEST_URI'], $matches);
    if(isset($matches[1])) {
        $id = intval($matches[1]);
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$data['status'], $id]);
        echo json_encode(["message" => "Status updated"]);
    }
}
