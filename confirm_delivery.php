<?php
require 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];

    // Update order status to "Order Received"
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Order Received' WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);

    // Redirect to the delivery page after confirming receipt
    header("Location: delivery.php");
    exit();
}
?>
