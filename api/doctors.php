<?php
require_once 'db.php';

ensureArchiveColumns($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // The public booking page only sees active doctors; admins see everyone and split by isActive.
    $stmt = $pdo->query(isAdmin() ? "SELECT * FROM doctors" : "SELECT * FROM doctors WHERE isActive = 1");
    echo json_encode($stmt->fetchAll());
} elseif ($method === 'POST') {
    checkAuth();
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("INSERT INTO doctors (name, role, image) VALUES (?, ?, ?)");
    $stmt->execute([$data['name'], $data['role'], $data['image']]);
    echo json_encode(["id" => $pdo->lastInsertId()]);
} elseif ($method === 'PUT') {
    checkAuth();
    preg_match('#^/(\d+)(?:/(archive|restore))?#', requestPath(), $matches);
    if (!isset($matches[1])) fail(400, "Missing doctor id");
    $id = intval($matches[1]);
    if (!empty($matches[2])) {
        setActive($pdo, 'doctors', $id, $matches[2], 'Doctor');
    } else {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("UPDATE doctors SET name = ?, role = ?, image = ? WHERE id = ?");
        $stmt->execute([$data['name'], $data['role'], $data['image'], $id]);
        echo json_encode(["message" => "Doctor updated"]);
    }
} elseif ($method === 'DELETE') {
    checkAuth();
    fail(405, "Doctors are set inactive rather than deleted. Use PUT /{id}/archive.");
}
?>
