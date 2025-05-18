<?php
session_start();
require 'includes/db.php'; // Use PDO connection from here

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$productId = $_POST['product_id'] ?? null;
$userId = $_SESSION['user_id'];

// Delete the product from the cart
$sql = "DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
$stmt->execute();

// Check if the item was successfully deleted
if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
}
?>
