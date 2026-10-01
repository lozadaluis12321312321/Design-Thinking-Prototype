<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    checkAuth();
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
    $orders = $stmt->fetchAll();
    
    foreach ($orders as &$order) {
        $stmt2 = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.productId = p.id WHERE oi.orderId = ?");
        $stmt2->execute([$order['id']]);
        $order['items'] = $stmt2->fetchAll();
    }
    echo json_encode($orders);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $date = date('Y-m-d H:i:s');
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO orders (customerName, contactNumber, email, totalAmount, date, paymentMethod) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['customerName'], $data['contactNumber'], $data['email'], $data['totalAmount'], $date, $data['paymentMethod']]);
        $orderId = $pdo->lastInsertId();
        
        $stmt2 = $pdo->prepare("INSERT INTO order_items (orderId, productId, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt3 = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
        $stmt4 = $pdo->prepare("UPDATE products SET availability = CASE WHEN stock <= 0 THEN 'Out of Stock' WHEN stock <= 4 THEN 'Low Stock' ELSE 'In Stock' END WHERE id = ?");
        
        foreach ($data['items'] as $item) {
            $stmt2->execute([$orderId, $item['productId'], $item['quantity'], $item['price']]);
            $stmt3->execute([$item['quantity'], $item['productId']]);
            $stmt4->execute([$item['productId']]);
        }
        $pdo->commit();
        echo json_encode(["id" => $orderId, "message" => "Order placed successfully"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
} elseif ($method === 'PUT') {
    checkAuth();
    preg_match('/orders\.php\/(\d+)\/status/', $_SERVER['REQUEST_URI'], $matches);
    if(isset($matches[1])) {
        $id = intval($matches[1]);
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$data['status'], $id]);
        echo json_encode(["message" => "Status updated"]);
    }
}
?>
