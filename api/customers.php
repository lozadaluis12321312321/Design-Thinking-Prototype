<?php
require_once 'db.php';

checkAuth();

$stmt = $pdo->query("
    SELECT DISTINCT customerName as name, email, contactNumber 
    FROM (
        SELECT customerName, email, contactNumber FROM orders
        UNION
        SELECT customerName, email, contactNumber FROM appointments
    ) as cust
    WHERE customerName IS NOT NULL AND customerName != ''
");
echo json_encode($stmt->fetchAll());
?>
