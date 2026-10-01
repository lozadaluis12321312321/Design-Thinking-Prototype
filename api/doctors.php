<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM doctors");
    echo json_encode($stmt->fetchAll());
} elseif ($method === 'POST') {
    checkAuth();
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("INSERT INTO doctors (name, role, image) VALUES (?, ?, ?)");
    $stmt->execute([$data['name'], $data['role'], $data['image']]);
    echo json_encode(["id" => $pdo->lastInsertId()]);
} elseif ($method === 'PUT') {
    checkAuth();
    preg_match('/doctors\.php\/(\d+)/', $_SERVER['REQUEST_URI'], $matches);
    if(isset($matches[1])) {
        $id = intval($matches[1]);
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("UPDATE doctors SET name = ?, role = ?, image = ? WHERE id = ?");
        $stmt->execute([$data['name'], $data['role'], $data['image'], $id]);
        echo json_encode(["message" => "Doctor updated"]);
    }
} elseif ($method === 'DELETE') {
    checkAuth();
    preg_match('/doctors\.php\/(\d+)/', $_SERVER['REQUEST_URI'], $matches);
    if(isset($matches[1])) {
        $id = intval($matches[1]);
        $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["message" => "Doctor deleted"]);
    }
}
?>
