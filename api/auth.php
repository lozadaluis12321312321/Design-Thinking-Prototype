<?php
require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    echo json_encode(["token" => "php_admin_token_123", "message" => "Logged in successfully"]);
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid credentials"]);
}
?>
