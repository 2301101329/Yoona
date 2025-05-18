<?php
session_start();
require 'includes/db.php'; // Use PDO connection from here

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

//$data = json_decode(file_get_contents('php://input'), true);

//$productId = $data['productId'];
//$action = $data['action'];
$productId = $_POST['product_id'] ?? null;
$action = $_POST['action'] ?? null;
$userId = $_SESSION['user_id'];

// Check action (increase or decrease quantity)
if ($action === 'increase') {
    $sql = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = :user_id AND product_id = :product_id";
} elseif ($action === 'decrease') {
    $sql = "UPDATE cart SET quantity = quantity - 1 WHERE user_id = :user_id AND product_id = :product_id AND quantity > 1";
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Prepare and execute the query
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
$stmt->execute();

// Check if the update was successful
if ($stmt->rowCount() > 0) {
    // Get the updated quantity from the database
    $sql = "SELECT quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'newQuantity' => $row['quantity']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update quantity']);
}
?>
