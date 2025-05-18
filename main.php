<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?message=" . urlencode("Please log in first") . "&type=error");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$isAdmin = $_SESSION['is_admin'] ?? false;

$displayName = "Welcome, " . htmlspecialchars($fullname);
if ($isAdmin) {
    $displayName .= " (Admin)";
}

$stmt = $pdo->query("SELECT name, price, img FROM products LIMIT 4");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Yoona Dashboard</title>
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
        /* Sidebar */
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
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            background: var(--main-bg);
            min-height: 100vh;
        }
        /* Topbar */
        .topbar {
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0 32px;
            height: 80px;
            border-bottom-left-radius: 18px;
            border-bottom-right-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .topbar .welcome {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-right: 40px;
        }
        .topbar .user {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto;
        }
        .topbar .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .topbar .user span {
            font-weight: 600;
            color: var(--primary);
        }
        /* Content Area */
        .content {
            padding: 32px 32px 0 32px;
        }
        .best-sellers {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        .product-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 15px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            background-color: #eef2ff;
        }
        .product-card img {
            width: 100%;
            height: 140px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .product-card h3 {
            margin: 0;
            font-size: 16px;
            color: #1f2937;
            text-align: center;
        }
        .product-card .rating {
            font-size: 1.1rem;
            color: var(--primary);
            font-weight: 600;
        }
        @media (max-width: 1100px) {
            .best-sellers {
                grid-template-columns: 1fr;
            }
            .dashboard {
                flex-direction: column;
            }
            .main-content {
                margin-left: 0;
            }
        }
        @media (max-width: 900px) {
            .sidebar {
                width: 70px;
                padding: 16px 0;
            }
            .sidebar .logo span, .sidebar .menu li span, .sidebar .logout {
                display: none;
            }
            .main-content {
                margin-left: 70px;
            }
            .topbar {
                padding: 0 10px;
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
            <li><a href="main.php" class="active"><img src="images/dashboard.png" alt=""> <span>Home</span></a></li>
            <li><a href="products.php"><img src="images/shop.png" alt=""> <span>Shop</span></a></li>
            <li><a href="schedule.php"><img src="images/services.png" alt=""> <span>Services</span></a></li>
            <li><a href="view_order_history.php"><img src="images/view-order.png" alt=""> <span>View Orders</span></a></li>
            <li><a href="view_appointments.php"><img src="images/appointments.png" alt=""> <span>View Appointments</span></a></li>
            <li><a href="settings.php"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
        </ul>
        <div class="logout">
            <a href="login.php">Logout</a>
        </div>
    </div>
    <div class="main-content">
        <div class="topbar">
            <span style="font-size:2rem;font-weight:700;color:var(--primary);margin-right:40px;">Dashboard</span>
            <div class="user">
                <div class="user-avatar"><span>👤</span></div>
                <span><?= $displayName ?></span>
            </div>
        </div>
        <div class="content">
            <h2 style="text-align:center;color:var(--primary);margin-bottom:25px;">Best Sellers</h2>
            <div class="best-sellers">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="images/<?= htmlspecialchars($product['img']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" />
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <div class="rating">₱<?= number_format($product['price'], 2) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script>
fetch('unread_count.php')
  .then(res => res.json())
  .then(data => {
    if (data.count > 0) {
      // You can add a notification badge to the sidebar or topbar if needed
    }
  });
</script>
</body>
</html>
