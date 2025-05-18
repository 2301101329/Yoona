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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_users.php');
    exit();
}
$id = intval($_GET['id']);

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header('Location: admin_users.php');
    exit();
}

// Handle update
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("UPDATE users SET fullname = ?, username = ?, email = ? WHERE id = ?");
    $stmt->execute([$fullname, $username, $email, $id]);
    $success = true;
    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
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
            padding: 32px 32px 0 32px;
            max-width: 600px;
            margin: 0 auto;
        }
        .edit-user-form {
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 4px 18px rgba(24,49,91,0.10);
            padding: 40px 32px 32px 32px;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }
        .edit-user-form label {
            font-weight: 500;
            color: #18315B;
            margin-bottom: 4px;
        }
        .edit-user-form input[type="text"], .edit-user-form input[type="email"] {
            padding: 12px 16px;
            border-radius: 10px;
            border: 1.5px solid #B3C7E6;
            font-size: 1rem;
            background: #F3F6FB;
            transition: border 0.2s;
        }
        .edit-user-form input[type="text"]:focus, .edit-user-form input[type="email"]:focus {
            border: 1.5px solid #FFD166;
        }
        .edit-user-form button {
            background: #FFD166;
            color: #18315B;
            border: none;
            border-radius: 8px;
            padding: 12px 0;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(24,49,91,0.07);
            margin-top: 10px;
        }
        .edit-user-form button:hover {
            background: #18315B;
            color: #FFD166;
        }
        .success {
            background: #E6F0FF;
            color: #18315B;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 18px;
            text-align: center;
            font-size: 1.05rem;
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
            <li><a href="admin_orders.php"><img src="images/orders.png" alt=""> <span>Orders</span></a></li>
            <li><a href="admin_appointments.php"><img src="images/appointments.png" alt=""> <span>Appointments</span></a></li>
            <li><a href="admin_users.php" class="active"><img src="images/users.png" alt=""> <span>Users</span></a></li>
            <li><a href="admin_settings.php"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
        </ul>
        <div class="logout">
            <a href="login.php">Logout</a>
        </div>
    </div>
    <div class="main-content">
        <div class="topbar">
            <span style="font-size:1.5rem;font-weight:700;color:var(--primary);margin-right:32px;">Edit User</span>
            <div class="admin">
                <div class="admin-avatar"><span>👤</span></div>
                <span><?php echo htmlspecialchars($admin_fullname); ?></span>
            </div>
        </div>
        <div class="content">
            <?php if ($success): ?>
                <div class="success">User updated successfully!</div>
            <?php endif; ?>
            <form method="post" class="edit-user-form">
                <label>Full Name</label>
                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>
</div>
</body>
</html> 