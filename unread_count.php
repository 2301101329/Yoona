<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];

$result = $db->query("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = $user_id AND is_read = 0");
$row = $result->fetch_assoc();

echo json_encode(['count' => $row['cnt']]);
