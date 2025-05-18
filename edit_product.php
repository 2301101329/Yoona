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

// Check if ID is passed
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_inventory.php");
    exit;
}

$id = intval($_GET['id']);

// Fetch product by ID
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// If product not found
if (!$product) {
    echo "Product not found.";
    exit;
}

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_price = floatval($_POST['price']);
    $new_quantity = intval($_POST['quantity']);
    $new_description = trim($_POST['description']);

    $update = $pdo->prepare("UPDATE products SET price = ?, quantity = ?, description = ? WHERE id = ?");
    $update->execute([$new_price, $new_quantity, $new_description, $id]);

    header("Location: admin_inventory.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
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
        .card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            padding: 32px 32px 24px 32px;
            max-width: 420px;
            margin: 32px auto 0 auto;
        }
        .card h2 {
            margin-bottom: 18px;
            color: var(--primary);
            font-size: 1.4rem;
            font-weight: 700;
            text-align: center;
        }
        .card label {
            display: block;
            margin-top: 15px;
            font-weight: 500;
            color: #333;
        }
        .card input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .card textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            min-height: 100px;
            resize: vertical;
            font-family: 'Poppins', sans-serif;
        }
        .card button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            width: 100%;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .card button:hover {
            background: #2980b9;
        }
        .return-btn {
            display: block;
            text-decoration: none;
            background-color: var(--accent);
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 16px;
            text-align: center;
            margin: 20px auto 0 auto;
            width: 200px;
            font-weight: 600;
            transition: background-color 0.3s, color 0.3s;
        }
        .return-btn:hover {
            background-color: var(--primary);
            color: var(--accent);
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
            .content {
                padding: 12px 10px 40px 10px;
            }
            .card {
                padding: 24px 10px 18px 10px;
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
            <li><a href="admin_inventory.php" class="active"><img src="images/inventory.png" alt=""> <span>Inventory</span></a></li>
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
            <span style="font-size:1.5rem;font-weight:700;color:var(--primary);margin-right:32px;">Edit Product</span>
            <div class="admin">
                <div class="admin-avatar"><span>👤</span></div>
                <span><?php echo htmlspecialchars($admin_fullname); ?></span>
            </div>
        </div>
        <div class="content">
            <div class="card">
                <h2>Edit Product</h2>
                <form method="POST">
                    <label>Price (₱)</label>
                    <input type="number" name="price" value="<?= htmlspecialchars($product['price']); ?>" step="0.01" required>

                    <label>Stocks</label>
                    <input type="number" name="quantity" value="<?= htmlspecialchars($product['quantity']); ?>" required>

                    <label>Description</label>
                    <textarea name="description" required><?= htmlspecialchars($product['description']); ?></textarea>

                    <button type="submit">Update Product</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
