<?php
// db.php
$host = 'localhost';
$dbname = 'yoona_website';
$username = 'root';
$password = '';

try {
    // Create a PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // Enable error handling
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}
?>
