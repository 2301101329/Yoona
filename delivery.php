<?php
require 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $cancel_order_id = $_POST['cancel_order_id'];
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
    $stmt->execute([$cancel_order_id, $user_id]);
    header("Location: delivery.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT o.*, p.name AS product_name, p.img, p.price 
                           FROM orders o 
                           JOIN products p ON o.product_id = p.id 
                           WHERE o.user_id = ? AND o.status NOT IN ('Order Received', 'Declined', 'Cancelled')");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Delivery Cart - Yoona</title>
    <link rel="icon" href="images/yoona.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .topbar .delivery-title {
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
        .topbar .icon-link.active {
            background: var(--accent);
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        .topbar .icon-link:hover {
            color: var(--primary);
            background: var(--accent);
        }
        .container {
            padding: 0 40px 40px 40px;
            margin-top: 30px;
        }
        .order-card {
            background: var(--card-bg);
            border-radius: 18px;
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
            padding: 24px 18px 18px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-left {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .order-card img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            border-radius: 12px;
            background-color: #f3f4f6;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            padding: 8px;
        }
        .order-details {
            flex: 1;
        }
        .order-details h3 {
            margin: 0 0 8px 0;
            font-size: 1.2rem;
            color: var(--primary);
            font-weight: 600;
        }
        .order-details p {
            margin: 4px 0;
            color: #4b5563;
        }
        .button-group {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }
        .confirm-btn, .cancel-btn {
            background-color: var(--accent);
            color: var(--primary);
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 150px;
            font-weight: 600;
        }
        .confirm-btn:hover {
            background-color: var(--primary);
            color: #fff;
        }
        .cancel-btn {
            background-color: var(--danger);
            color: var(--danger-text);
            border: 1.5px solid var(--danger-border);
        }
        .cancel-btn:hover {
            background-color: var(--danger-text);
            color: #fff;
        }
        .status-label {
            font-weight: bold;
            color: #2563eb;
        }
        .empty {
            text-align: center;
            padding: 60px;
            color: #9ca3af;
            font-size: 18px;
        }
        .price {
            font-weight: bold;
            color: #111827;
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
            .container {
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
            <li><a href="products.php"><img src="images/inventory.png" alt=""> <span>Shop</span></a></li>
            <li><a href="schedule.php"><img src="images/appointments.png" alt=""> <span>Services</span></a></li>
            <li><a href="view_order_history.php"><img src="images/users.png" alt=""> <span>View Orders</span></a></li>
            <li><a href="view_appointments.php"><img src="images/users.png" alt=""> <span>View Appointments</span></a></li>
            <li><a href="settings.php"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
        </ul>
        <div class="logout">
            <a href="login.php">Logout</a>
        </div>
    </div>
    <div class="main-content">
        <div class="topbar">
            <div class="delivery-title">Your Deliveries</div>
            <div class="topbar-icons">
                <a href="cart.php" class="icon-link" title="Cart">🛒</a>
                <a href="delivery.php" class="icon-link active" title="Delivery">🚚</a>
            </div>
        </div>
        <div class="container">
            <?php if ($orders): ?>
                <?php foreach ($orders as $order): 
                    $imgPath = 'images/default.png';
                    if (!empty($order['img'])) {
                        if (strpos($order['img'], 'uploads/') === 0 || strpos($order['img'], 'images/') === 0) {
                            $imgPath = $order['img'];
                        } else {
                            $imgPath = 'images/' . $order['img'];
                        }
                    }
                    $totalPrice = $order['price'] * $order['quantity'];
                ?>
                    <div class="order-card">
                        <div class="order-left">
                            <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($order['product_name']) ?>">
                            <div class="order-details">
                                <h3><?= htmlspecialchars($order['product_name']) ?></h3>
                                <p>Quantity: <?= (int)$order['quantity'] ?></p>
                                <p class="price">Total: ₱<?= number_format($totalPrice, 2) ?></p>
                                <p>Status: <span class="status-label"><?= htmlspecialchars($order['status']) ?></span></p>
                            </div>
                        </div>
                        <div class="button-group">
                            <?php if ($order['status'] === 'Already Shipped'): ?>
                                <form method="POST" action="confirm_delivery.php" class="confirm-form">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="button" class="confirm-btn" onclick="confirmReceived(this.form)">Confirm Received</button>
                                </form>
                            <?php elseif ($order['status'] === 'Pending'): ?>
                                <form method="POST" class="cancel-form">
                                    <input type="hidden" name="cancel_order_id" value="<?= $order['id'] ?>">
                                    <button type="button" class="cancel-btn" onclick="confirmCancel(this.form)">Cancel Order</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">No current deliveries. All your orders are completed or declined.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
function confirmReceived(form) {
    Swal.fire({
        title: 'Confirm Delivery',
        text: 'Have you received your order?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, I have',
        cancelButtonText: 'Not yet'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}
function confirmCancel(form) {
    Swal.fire({
        title: 'Cancel Order',
        text: 'Are you sure you want to cancel this order?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel it',
        cancelButtonText: 'No, keep it'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}
</script>
</body>
</html>
