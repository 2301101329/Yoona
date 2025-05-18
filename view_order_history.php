<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to view your order history.";
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT orders.*, products.name AS product_name, products.img 
                       FROM orders
                       JOIN products ON orders.product_id = products.id
                       WHERE orders.user_id = ? AND orders.status IN ('Order Received', 'Declined', 'Cancelled')
                       ORDER BY orders.created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Order History</title>
    <link rel="icon" href="images/yoona.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #18315B;
            --sidebar-text: #fff;
            --sidebar-active: #FFD166;
            --main-bg: #F3E9D2;
            --card-bg: #fff;
            --card-shadow: 0 2px 8px rgba(0,0,0,0.07);
            --primary: #18315B;
            --accent: #FFD166;
            --danger: #FFE5E5;
            --danger-border: #FFBABA;
            --danger-text: #D7263D;
            --blue-light: #E6F0FF;
            --blue-border: #B3C7E6;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background: var(--main-bg);
        }
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 32px 0 16px 0;
            position: fixed;
            height: 100vh;
            z-index: 10;
        }
        .sidebar .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 32px;
        }
        .sidebar .logo img {
            width: 60px;
            height: 60px;
            margin-bottom: 10px;
        }
        .sidebar .logo span {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .sidebar .menu {
            list-style: none;
            width: 100%;
            flex: 1;
        }
        .sidebar .menu li {
            margin-bottom: 8px;
        }
        .sidebar .menu li a {
            color: var(--sidebar-text);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 32px;
            border-radius: 8px;
            font-size: 1rem;
            transition: background 0.2s;
        }
        .sidebar .menu li a.active, .sidebar .menu li a:hover {
            background: rgba(255, 209, 102, 0.15);
            color: var(--accent);
        }
        .sidebar .menu li img {
            width: 22px;
            height: 22px;
            margin-right: 16px;
        }
        .sidebar .logout {
            margin-top: auto;
            padding: 16px 0 0 0;
            width: 100%;
            text-align: center;
        }
        .sidebar .logout a {
            color: #fff;
            text-decoration: none;
            font-size: 1rem;
            opacity: 0.8;
            transition: color 0.2s, opacity 0.2s;
        }
        .sidebar .logout a:hover {
            color: var(--accent);
            opacity: 1;
        }
        .main-content {
            flex: 1;
            margin-left: 260px;
            background: var(--main-bg);
            min-height: 100vh;
            padding: 0 0 40px 0;
        }
        .topbar {
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            height: 80px;
            border-bottom-left-radius: 18px;
            border-bottom-right-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            margin-bottom: 30px;
        }
        .topbar .history-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }
        .topbar-icons {
            display: flex;
            gap: 18px;
        }
        .topbar .icon-link {
            color: var(--sidebar-active);
            font-size: 2rem;
            text-decoration: none;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .topbar .icon-link:hover {
            color: var(--primary);
            background: var(--accent);
        }
        .orders-list {
            padding: 0 40px 40px 40px;
            margin-top: 30px;
        }
        .order-box {
            background: var(--card-bg);
            border-radius: 18px;
            box-shadow: var(--card-shadow);
            padding: 24px 18px 18px 18px;
            margin-bottom: 24px;
            display: flex;
            gap: 24px;
            align-items: center;
        }
        .order-img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 12px;
            background-color: #f9fafb;
            border: 1.5px solid #eee;
        }
        .order-info {
            flex-grow: 1;
        }
        .order-info p {
            margin: 4px 0;
            color: #374151;
        }
        .status {
            font-weight: bold;
            padding: 4px 12px;
            border-radius: 8px;
            display: inline-block;
        }
        .Declined {
            background: var(--danger);
            color: var(--danger-text);
            border: 1.5px solid var(--danger-border);
        }
        .Order_Received {
            background: var(--accent);
            color: var(--primary);
        }
        .Cancelled {
            background: #e5e7eb;
            color: #6b7280;
        }
        @media (max-width: 1100px) {
            .main-content {
                margin-left: 0;
            }
            .sidebar {
                width: 70px;
                padding: 16px 0;
            }
            .sidebar .logo span, .sidebar .menu li span, .sidebar .logout {
                display: none;
            }
        }
        @media (max-width: 700px) {
            .orders-list {
                padding: 0 8px 40px 8px;
            }
        }
    </style>
</head>
<body>
<div class="dashboard">
    <div class="sidebar">
        <div class="logo">
            <img src="images/yoona.png" alt="logo">
            <span>Yoona</span>
        </div>
        <ul class="menu">
                 <li><a href="main.php"><img src="images/dashboard.png" alt=""> <span>Home</span></a></li>
            <li><a href="products.php"><img src="images/shop.png" alt=""> <span>Shop</span></a></li>
            <li><a href="schedule.php"><img src="images/services.png" alt=""> <span>Services</span></a></li>
            <li><a href="view_order_history.php" class="active"><img src="images/view-order.png" alt=""> <span>View Orders</span></a></li>
            <li><a href="view_appointments.php"><img src="images/appointments.png" alt=""> <span>View Appointments</span></a></li>
            <li><a href="settings.php"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
        </ul>
        <div class="logout">
            <a href="login.php">Logout</a>
        </div>
    </div>
    <div class="main-content">
        <div class="topbar">
            <div class="history-title">Order History</div>
            <div class="topbar-icons">
                <a href="cart.php" class="icon-link" title="Cart">🛒</a>
                <a href="delivery.php" class="icon-link" title="Delivery">🚚</a>
            </div>
        </div>
        <div class="orders-list">
            <?php if (empty($orders)): ?>
                <p style="text-align: center; color: #6b7280; font-size: 1.2rem;">You have no past orders yet.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <?php
                        $imgPath = 'images/default.png';
                        if (!empty($order['img']) && is_string($order['img'])) {
                            if (strpos($order['img'], 'uploads/') === 0 || strpos($order['img'], 'images/') === 0) {
                                $imgPath = $order['img'];
                            } else {
                                $imgPath = 'images/' . $order['img'];
                            }
                        }
                    ?>
                    <div class="order-box">
                        <img class="order-img" src="<?= htmlspecialchars($imgPath) ?>" alt="Product Image">
                        <div class="order-info">
                            <p><strong>Product:</strong> <?= htmlspecialchars($order['product_name']) ?></p>
                            <p><strong>Quantity:</strong> <?= $order['quantity'] ?></p>
                            <p><strong>Total:</strong> ₱<?= number_format($order['total_price'], 2) ?></p>
                            <p><strong>Date Ordered:</strong> <?= date('M d, Y H:i', strtotime($order['created_at'])) ?></p>
                            <span class="status <?= str_replace(' ', '_', $order['status']) ?>"><strong>Status:</strong> <?= $order['status'] ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
