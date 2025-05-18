<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
require_once 'includes/db.php';

// Fetch admin full name for topbar
$stmt = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_fullname = $stmt->fetchColumn();

// Total Products
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
// Orders
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
// Total Stock (sum of quantity)
$total_stock = $pdo->query("SELECT SUM(quantity) FROM products")->fetchColumn();
// Out of Stock
$out_of_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity = 0")->fetchColumn();
// No. of Users (role = user)
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
// Inventory Values
$total_units = $pdo->query("SELECT SUM(quantity) FROM products")->fetchColumn();
$sold_units = $pdo->query("SELECT SUM(quantity) FROM orders")->fetchColumn();
// Number of Appointments
$total_appointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
// Last 3 Orders (join users)
$last_orders = $pdo->query("SELECT o.*, u.fullname FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
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
        .topbar .search {
            flex: 1;
            display: flex;
            justify-content: center;
        }
        .topbar .search input {
            width: 260px;
            padding: 10px 16px;
            border-radius: 20px;
            border: none;
            outline: none;
            font-size: 1rem;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .topbar .admin {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto;
        }
        .topbar .admin-avatar {
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
        .topbar .admin span {
            font-weight: 600;
            color: var(--primary);
        }
        /* Content Area */
        .content {
            padding: 32px 32px 0 32px;
        }
        .cards-row {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
        }
        .card {
            background: var(--card-bg);
            border-radius: 18px;
            box-shadow: var(--card-shadow);
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 0;
            transition: transform 0.18s cubic-bezier(.4,2,.6,1), box-shadow 0.18s cubic-bezier(.4,2,.6,1);
        }
        .card:hover {
            transform: scale(1.04) translateY(-4px);
            box-shadow: 0 8px 24px rgba(24,49,91,0.13);
        }
        .card.red {
            background: var(--danger);
            border: 2px solid var(--danger-border);
            color: var(--danger-text);
        }
        .card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--primary);
        }
        .card.red .card-title {
            color: var(--danger-text);
        }
        .card .card-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .card .card-desc {
            font-size: 1rem;
            color: #888;
        }
        .card .card-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary);
        }
        .card.red .card-icon {
            color: var(--danger-text);
        }
        /* Overview, Users, Inventory, Sales */
        .overview-row {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
        }
        .overview-row .card {
            background: #E6F0FF;
            border: 1.5px solid #B3C7E6;
            color: var(--primary);
            align-items: center;
            text-align: center;
        }
        .overview-row .card.red {
            background: var(--danger);
            border: 2px solid var(--danger-border);
            color: var(--danger-text);
        }
        /* Last Orders Table */
        .orders-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
            margin-top: 12px;
        }
        .orders-table th {
            text-align: left;
            color: var(--primary);
            font-size: 1rem;
            font-weight: 600;
            padding-bottom: 8px;
        }
        .orders-table td {
            background: #fff;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 1rem;
            color: #222;
        }
        .orders-table .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        .status-completed {
            background: var(--accent);
            color: #fff;
            border-radius: 8px;
            padding: 4px 12px;
            font-size: 0.95rem;
            font-weight: 600;
        }
        .status-pending {
            background: #E6F0FF;
            color: var(--primary);
            border-radius: 8px;
            padding: 4px 12px;
            font-size: 0.95rem;
            font-weight: 600;
        }
        /* Monthly Sales */
        .sales-bar {
            background: var(--primary);
            height: 14px;
            border-radius: 8px;
            margin-right: 10px;
            display: inline-block;
        }
        .sales-label {
            font-size: 1rem;
            color: var(--primary);
            font-weight: 500;
        }
        @media (max-width: 1100px) {
            .cards-row, .overview-row {
                flex-direction: column;
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
            <li><a href="#" class="active"><img src="images/dashboard.png" alt=""> <span>Dashboard</span></a></li>
            <li><a href="admin_inventory.php"><img src="images/inventory.png" alt=""> <span>Inventory</span></a></li>
            <li><a href="admin_orders.php"><img src="images/orders.png" alt=""> <span>Orders</span></a></li>
            <li><a href="admin_appointments.php"><img src="images/appointments.png" alt=""> <span>Appointments</span></a></li>
            <li><a href="admin_users.php"><img src="images/users.png" alt=""> <span>Users</span></a></li>
            <li><a href="admin_settings.php"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
        </ul>
        <div class="logout">
            <a href="login.php">Logout</a>
        </div>
    </div>
    <div class="main-content">
        <div class="topbar">
            <span style="font-size:1.5rem;font-weight:700;color:var(--primary);margin-right:32px;">Dashboard</span>
            <div class="admin">
                <div class="admin-avatar"><span>👤</span></div>
                <span><?php echo htmlspecialchars($admin_fullname); ?></span>
            </div>
        </div>
        <div class="content">
            <div class="cards-row overview-row">
                <div class="card">
                    <div class="card-icon">📦</div>
                    <div class="card-value"><?php echo $total_products; ?></div>
                    <div class="card-title">Total Products</div>
                </div>
                <div class="card">
                    <div class="card-icon">🛍️</div>
                    <div class="card-value"><?php echo $total_orders; ?></div>
                    <div class="card-title">Orders</div>
                </div>
                <div class="card">
                    <div class="card-icon">📦</div>
                    <div class="card-value"><?php echo $total_stock ? $total_stock : 0; ?></div>
                    <div class="card-title">Total Stock</div>
                </div>
                <div class="card red">
                    <div class="card-icon">⬇️</div>
                    <div class="card-value"><?php echo $out_of_stock; ?></div>
                    <div class="card-title">Out of Stock</div>
                </div>
            </div>
            <div class="cards-row">
                <div class="card" style="flex:1.2;">
                    <div class="card-title">No. of Users</div>
                    <div style="display:flex;align-items:center;gap:16px;">
                        <div style="background:#E6F0FF;padding:16px 20px;border-radius:12px;display:flex;align-items:center;">
                            <span style="font-size:2rem;margin-right:10px;">👥</span>
                            <div>
                                <div style="font-size:1.5rem;font-weight:700;"><?php echo $total_users; ?></div>
                                <div style="font-size:1rem;color:#888;">Total Customers</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card" style="flex:1.2;">
                    <div class="card-title">Inventory Values</div>
                    <div style="display:flex;align-items:center;gap:24px;">
                        <div style="background:#E6F0FF;padding:16px 20px;border-radius:12px;display:flex;flex-direction:column;align-items:center;">
                            <div style="font-size:2rem;font-weight:700;"><?php echo $total_units ? round(($total_units/($total_units+$sold_units))*100) : 0; ?>%</div>
                            <div style="font-size:1rem;color:#888;">Total Units</div>
                        </div>
                        <div style="background:#E6F0FF;padding:16px 20px;border-radius:12px;display:flex;flex-direction:column;align-items:center;">
                            <div style="font-size:2rem;font-weight:700;"><?php echo $sold_units ? round(($sold_units/($total_units+$sold_units))*100) : 0; ?>%</div>
                            <div style="font-size:1rem;color:#888;">Sold Units</div>
                        </div>
                    </div>
                </div>
                <div class="card" style="flex:1.5;">
                    <div class="card-title">Number of Appointments</div>
                    <div style="margin-top:10px;display:flex;align-items:center;justify-content:center;">
                        <span style="font-size:2.5rem;font-weight:700;color:var(--primary);margin-right:10px;">📅</span>
                        <span style="font-size:2rem;font-weight:700;"> <?php echo $total_appointments; ?> </span>
                    </div>
                </div>
            </div>
            <div class="cards-row">
                <div class="card" style="flex:2;">
                    <div class="card-title">Last Orders</div>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($last_orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['fullname'] ?? 'Unknown'); ?></td>
                                <td>₱<?php echo htmlspecialchars($order['total_price']); ?></td>
                                <td>
                                    <?php if (strtolower($order['status']) === 'completed' || strtolower($order['status']) === 'done'): ?>
                                        <span class="status-completed">Completed</span>
                                    <?php else: ?>
                                        <span class="status-pending"><?php echo htmlspecialchars($order['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars(date('m/d/y', strtotime($order['created_at']))); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
