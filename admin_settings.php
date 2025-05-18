<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
require_once 'includes/db.php';

// Fetch admin info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_fullname = $admin['fullname'];

$success = $error = '';

// Change password
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    if (!password_verify($current, $admin['password'])) {
        $error = 'Current password is incorrect.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        // Password complexity validation
        $password_errors = [];
        if (strlen($new) < 8) {
            $password_errors[] = "at least 8 characters";
        }
        if (!preg_match('/[A-Z]/', $new)) {
            $password_errors[] = "one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $new)) {
            $password_errors[] = "one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $new)) {
            $password_errors[] = "one number";
        }
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $new)) {
            $password_errors[] = "one special character (!@#$%^&*()-_=+{};:,<.>)";
        }
        if (!empty($password_errors)) {
            $error = 'New password must contain: <br>- ' . implode('<br>- ', $password_errors);
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $admin['id']]);
            $success = 'Password changed successfully!';
        }
    }
}

// Change email
if (isset($_POST['change_email'])) {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$email, $admin['id']]);
        $success = 'Email updated successfully!';
        $admin['email'] = $email;
    }
}

// Change name
if (isset($_POST['change_name'])) {
    $fullname = trim($_POST['fullname']);
    if (strlen($fullname) < 2) {
        $error = 'Name must be at least 2 characters.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt->execute([$fullname, $admin['id']]);
        $success = 'Name updated successfully!';
        $admin['fullname'] = $fullname;
        $admin_fullname = $fullname;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Settings</title>
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
        .settings-container {
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 4px 18px rgba(24,49,91,0.10);
            padding: 40px 32px 32px 32px;
            margin-bottom: 40px;
        }
        h2 { color: #18315B; font-size: 1.3rem; margin-bottom: 18px; }
        form { display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px; }
        label { font-weight: 500; color: #18315B; margin-bottom: 4px; }
        input[type="text"], input[type="email"], input[type="password"] {
            padding: 12px 16px;
            border-radius: 10px;
            border: 1.5px solid #B3C7E6;
            font-size: 1rem;
            background: #F3F6FB;
            transition: border 0.2s;
        }
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus {
            border: 1.5px solid #FFD166;
        }
        button {
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
        button:hover {
            background: #18315B;
            color: #FFD166;
        }
        .success {
            background: #E6F0FF;
            color: #18315B;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 10px;
            text-align: center;
            font-size: 1.05rem;
        }
        .error {
            background: #FFE5E5;
            color: #D7263D;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 10px;
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
            <li><a href="admin_users.php"><img src="images/users.png" alt=""> <span>Users</span></a></li>
            <li><a href="admin_settings.php" class="active"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
        </ul>
        <div class="logout">
            <a href="login.php">Logout</a>
        </div>
    </div>
    <div class="main-content">
        <div class="topbar">
            <span style="font-size:1.5rem;font-weight:700;color:var(--primary);margin-right:32px;">Admin Settings</span>
            <div class="admin">
                <div class="admin-avatar"><span>👤</span></div>
                <span><?php echo htmlspecialchars($admin_fullname); ?></span>
            </div>
        </div>
        <div class="content">
            <div class="settings-container">
                <?php if ($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
                <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
                <form method="post">
                    <h2>Change Name</h2>
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($admin['fullname']); ?>" required>
                    <button type="submit" name="change_name">Update Name</button>
                </form>
                <form method="post">
                    <h2>Change Email</h2>
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    <button type="submit" name="change_email">Update Email</button>
                </form>
                <form method="post">
                    <h2>Change Password</h2>
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                    <button type="submit" name="change_password">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html> 