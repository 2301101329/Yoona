<?php
session_start();
require 'includes/db.php';

if (isset($_POST['product_id']) && isset($_SESSION['user_id'])) {
    $productId = $_POST['product_id'];
    $userId = $_SESSION['user_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1; // Get quantity from POST, default to 1 if not set

    try {
        // First, check if the product exists
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = :product_id");
        $stmt->execute([':product_id' => $productId]);
        $product = $stmt->fetch();

        if ($product) {
            // Now check if product already exists in the user's cart
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id");
            $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
            $cartItem = $stmt->fetch();

            if ($cartItem) {
                // Product already in cart, update quantity by adding
                $newQuantity = $cartItem['quantity'] + $quantity;
                $stmt = $pdo->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id");
                $stmt->execute([':quantity' => $newQuantity, ':id' => $cartItem['id']]);
            } else {
                // Product not yet in cart, insert with selected quantity
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
                $stmt->execute([':user_id' => $userId, ':product_id' => $productId, ':quantity' => $quantity]);
            }

            echo "success"; // Simple success message
        } else {
            echo "Error: Product does not exist!";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Error: Product ID or User ID not set!";
}
?>
