<?php
require 'includes/db.php';

$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';

$taken = false;
if ($date && $time) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE date = ? AND time = ?");
    $stmt->execute([$date, $time]);
    $taken = $stmt->fetchColumn() > 0;
}
header('Content-Type: application/json');
echo json_encode(['taken' => $taken]); 