<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($method === 'GET') {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if ($product) {
            echo json_encode($product);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Not found"]);
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM products");
        echo json_encode($stmt->fetchAll());
    }
} elseif ($method === 'POST') {
    checkAuth();
    $data = json_decode(file_get_contents("php://input"), true);
    $stock = intval($data['stock']);
    $availability = $stock <= 0 ? 'Out of Stock' : ($stock <= 4 ? 'Low Stock' : 'In Stock');
    $stmt = $pdo->prepare("INSERT INTO products (name, type, style, color, price, description, availability, image, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['name'], $data['type'], $data['style'], $data['color'], $data['price'],
        $data['description'], $availability, $data['image'], $stock
    ]);
    echo json_encode(["id" => $pdo->lastInsertId()]);
} elseif ($method === 'PUT') {
    checkAuth();
    if (!$id) {
        preg_match('/products\.php\/(\d+)/', $_SERVER['REQUEST_URI'], $matches);
        if(isset($matches[1])) $id = intval($matches[1]);
    }
    $data = json_decode(file_get_contents("php://input"), true);
    $stock = intval($data['stock']);
    $availability = $stock <= 0 ? 'Out of Stock' : ($stock <= 4 ? 'Low Stock' : 'In Stock');
    $stmt = $pdo->prepare("UPDATE products SET name=?, type=?, style=?, color=?, price=?, description=?, availability=?, image=?, stock=? WHERE id=?");
    $stmt->execute([
        $data['name'], $data['type'], $data['style'], $data['color'], $data['price'],
        $data['description'], $availability, $data['image'], $stock, $id
    ]);
    echo json_encode(["message" => "Product updated"]);
} elseif ($method === 'DELETE') {
    checkAuth();
    if (!$id) {
        preg_match('/products\.php\/(\d+)/', $_SERVER['REQUEST_URI'], $matches);
        if(isset($matches[1])) $id = intval($matches[1]);
    }
    $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
    $stmt->execute([$id]);
    echo json_encode(["message" => "Product deleted"]);
}
?>
