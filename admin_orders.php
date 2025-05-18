<?php
session_start();
require 'includes/db.php';

// Check if admin is logged in (you may modify this depending on your auth logic)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Access denied.";
    exit;
}

// Fetch admin full name for topbar
$stmt = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_fullname = $stmt->fetchColumn();

// Handle order status update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['action'])) {
    $order_id = (int)$_POST['order_id'];
    $action = $_POST['action'];

    $new_status = '';
    if ($action === 'accept') {
        $new_status = 'To be Shipped';
    } elseif ($action === 'ship') {
        $new_status = 'Already Shipped';
    } elseif ($action === 'decline') {
        $new_status = 'Declined';
    }

    if ($new_status !== '') {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        echo json_encode(['success' => true]);
        exit;
    }
}

// Fetch all orders
$stmt = $pdo->query("SELECT orders.*, users.fullname, products.name AS product_name, products.img 
                     FROM orders
                     JOIN users ON orders.user_id = users.id
                     JOIN products ON orders.product_id = products.id
                     ORDER BY orders.created_at DESC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Orders</title>
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
            padding: 12px 32px 40px 32px;
        }
        /* Orders Table Styles */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(24,49,91,0.07);
            border-radius: 16px;
            overflow: hidden;
            margin-top: 30px;
        }
        th, td {
            padding: 15px 12px;
            text-align: left;
        }
        th {
            background: #18315B;
            color: #FFD166;
            font-weight: 600;
            font-size: 1rem;
            border-top: 1px solid #18315B;
        }
        td {
            background: #fff;
            border-radius: 8px;
            font-size: 1rem;
            color: #222;
            box-shadow: 0 1px 4px rgba(24,49,91,0.03);
        }
        img {
            width: 60px;
            border-radius: 5px;
        }
        button {
            padding: 8px 18px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            margin: 2px;
        }
        .accept {
            background: #FFD166;
            color: #18315B;
        }
        .accept:hover {
            background: #18315B;
            color: #FFD166;
        }
        .ship {
            background: #E6F0FF;
            color: #18315B;
        }
        .ship:hover {
            background: #18315B;
            color: #FFD166;
        }
        .decline {
            background: #FFE5E5;
            color: #D7263D;
        }
        .decline:hover {
            background: #D7263D;
            color: #fff;
        }
        .disabled {
            background-color: #9ca3af;
            color: #fff;
            cursor: default;
        }
        @media (max-width: 1100px) {
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
            <li><a href="admin_main.php"><img src="images/dashboard.png" alt=""> <span>Dashboard</span></a></li>
            <li><a href="admin_inventory.php"><img src="images/inventory.png" alt=""> <span>Inventory</span></a></li>
            <li><a href="admin_orders.php" class="active"><img src="images/orders.png" alt=""> <span>Orders</span></a></li>
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
            <span style="font-size:1.5rem;font-weight:700;color:var(--primary);margin-right:32px;">Admin Orders</span>
            <div class="admin">
                <div class="admin-avatar"><span>👤</span></div>
                <span><?php echo htmlspecialchars($admin_fullname); ?></span>
            </div>
        </div>
        <div class="content">
            <table>
                <tr>
                    <th>User</th>
                    <th>Product</th>
                    <th>Product ID</th>
                    <th>Quantity</th>
                    <th>Total Price</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['fullname']) ?></td>
                    <td><?= htmlspecialchars($order['product_name']) ?></td>
                    <td><?= $order['product_id'] ?></td>
                    <td><?= $order['quantity'] ?></td>
                    <td>₱<?= number_format($order['total_price'], 2) ?></td>
                    <td><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></td>
                    <td><?= htmlspecialchars($order['status']) ?></td>
                    <td>
                        <?php if ($order['status'] === 'Pending'): ?>
                            <button class="accept" onclick="updateOrderStatus(<?= $order['id'] ?>, 'accept')">Accept</button>
                            <button class="decline" onclick="confirmDecline(<?= $order['id'] ?>)">Decline</button>
                        <?php elseif ($order['status'] === 'To be Shipped'): ?>
                            <button class="ship" onclick="updateOrderStatus(<?= $order['id'] ?>, 'ship')">Ship Now</button>
                        <?php elseif ($order['status'] === 'Already Shipped' || $order['status'] === 'Declined' || $order['status'] === 'Order Received'): ?>
                            <button class="disabled" disabled><?= $order['status'] ?></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <div style="text-align:center; margin-top:32px;">
                <a href="admin_orders_print.php" target="_blank" class="btn print-btn" style="background:#18315B;color:#FFD166;padding:10px 28px;border-radius:24px;font-weight:600;font-size:1.1rem;text-decoration:none;box-shadow:0 2px 8px rgba(24,49,91,0.07);transition:background 0.2s, color 0.2s;display:inline-block;">🖨️ Print Orders</a>
            </div>

            <!-- Confirmation Modal for Decline -->
            <div id="declineModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
                background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
                <div style="background:white; padding:30px; border-radius:10px; text-align:center;">
                    <p>Are you sure you want to decline this order?</p>
                    <button onclick="performDecline()" style="background:#ef4444; color:white; padding:8px 16px; border:none; border-radius:6px;">Yes, Decline</button>
                    <button onclick="closeModal()" style="margin-left:10px; padding:8px 16px;">Cancel</button>
                </div>
            </div>

            <script>
                let orderToDecline = 0;

                function updateOrderStatus(orderId, action) {
                    fetch('admin_orders.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'order_id=' + orderId + '&action=' + action
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Failed to update order.');
                        }
                    });
                }

                function confirmDecline(orderId) {
                    orderToDecline = orderId;
                    document.getElementById('declineModal').style.display = 'flex';
                }

                function performDecline() {
                    updateOrderStatus(orderToDecline, 'decline');
                    closeModal();
                }

                function closeModal() {
                    document.getElementById('declineModal').style.display = 'none';
                }
            </script>
        </div>
    </div>
</div>
</body>
</html>
