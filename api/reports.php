<?php
require_once 'db.php';

checkAuth();

$productsCount = $pdo->query("SELECT COUNT(*) as count FROM products")->fetch()['count'];
ensureArchiveColumns($pdo);
$appointmentsCount = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE isActive = 1")->fetch()['count'];
$pendingApps = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE status='Pending' AND isActive = 1")->fetch()['count'];
$ordersCount = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE isActive = 1")->fetch()['count'];
$pendingOrders = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status='Pending' AND isActive = 1")->fetch()['count'];
$sales = $pdo->query("SELECT SUM(totalAmount) as total FROM orders WHERE status='Completed' AND isActive = 1")->fetch()['total'];

echo json_encode([
    "totalProducts" => $productsCount,
    "totalAppointments" => $appointmentsCount,
    "pendingAppointments" => $pendingApps,
    "totalOrders" => $ordersCount,
    "pendingOrders" => $pendingOrders,
    "totalSales" => $sales ? $sales : 0
]);
?>
