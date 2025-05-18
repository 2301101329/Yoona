<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$alert = '';

// Fetch latest user info
$stmt = $pdo->prepare('SELECT fullname, email, password FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Update Profile
if (isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    if ($fullname && $email) {
        $stmt = $pdo->prepare('UPDATE users SET fullname = ?, email = ? WHERE id = ?');
        $stmt->execute([$fullname, $email, $user_id]);
        $_SESSION['fullname'] = $fullname;
        $_SESSION['email'] = $email;
        $alert = "Swal.fire('Success', 'Profile updated successfully!', 'success');";
        $user['fullname'] = $fullname;
        $user['email'] = $email;
    } else {
        $alert = "Swal.fire('Error', 'Full name and email are required.', 'error');";
    }
}

// Change Password
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    if (!password_verify($current, $user['password'])) {
        $alert = "Swal.fire('Error', 'Current password is incorrect.', 'error');";
    } elseif ($new !== $confirm) {
        $alert = "Swal.fire('Error', 'New passwords do not match.', 'error');";
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
            $alert = "Swal.fire('Error', 'New password must contain: <br>- " . implode("<br>- ", $password_errors) . "', 'error');";
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$hashed, $user_id]);
            $alert = "Swal.fire('Success', 'Password changed successfully!', 'success');";
        }
    }
}

// Delete Account
if (isset($_POST['delete_account'])) {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    session_destroy();
    echo "<script>Swal.fire('Deleted!', 'Your account has been deleted.', 'success').then(() => { window.location='login.php'; });</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Yoona - Settings</title>
  <link rel="icon" href="images/yoona.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    }
    * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body { background: var(--main-bg); margin: 0; }
    .dashboard { display: flex; min-height: 100vh; }
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
      padding: 0;
      margin: 0;
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
    .main-content { flex: 1; margin-left: 260px; background: var(--main-bg); min-height: 100vh; padding: 0 0 40px 0; }
    .topbar { background: var(--accent); display: flex; align-items: center; justify-content: space-between; padding: 0 32px; height: 80px; border-bottom-left-radius: 18px; border-bottom-right-radius: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 30px; }
    .topbar .shop-title { font-size: 2rem; font-weight: 700; color: var(--primary); }
    .settings-container { max-width: 480px; margin: 40px auto 0 auto; background: var(--card-bg); border-radius: 18px; box-shadow: var(--card-shadow); padding: 32px 32px 24px 32px; }
    .settings-title { font-size: 1.6rem; font-weight: 700; color: var(--primary); margin-bottom: 18px; text-align: center; }
    .settings-form label { font-weight: 500; color: #374151; margin-bottom: 6px; display: block; }
    .settings-form input[type="text"], .settings-form input[type="email"], .settings-form input[type="password"] { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #d1d5db; margin-bottom: 16px; font-size: 1rem; background: #f8fafc; }
    .settings-form button { background: var(--primary); color: #fff; border: none; border-radius: 8px; padding: 12px 0; width: 100%; font-size: 1.1rem; font-weight: 600; cursor: pointer; margin-bottom: 10px; transition: background 0.2s; }
    .settings-form button:hover { background: var(--accent); color: var(--primary); }
    .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
    .danger-zone { text-align: center; margin-top: 18px; }
    .delete-btn { background: var(--danger); color: var(--danger-text); border: 1px solid var(--danger-border); border-radius: 8px; padding: 12px 0; width: 100%; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background 0.2s, color 0.2s; }
    .delete-btn:hover { background: var(--danger-text); color: #fff; }
    @media (max-width: 700px) {
      .main-content { margin-left: 0; padding: 0; }
      .sidebar { width: 70px; padding: 16px 0; }
      .sidebar .logo span, .sidebar .menu li span, .sidebar .logout { display: none; }
      .settings-container { padding: 18px 8px; }
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
            <li><a href="view_order_history.php"><img src="images/view-order.png" alt=""> <span>View Orders</span></a></li>
            <li><a href="view_appointments.php"><img src="images/appointments.png" alt=""> <span>View Appointments</span></a></li>
            <li><a href="settings.php" class="active"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
    </ul>
    <div class="logout">
      <a href="login.php">Logout</a>
    </div>
  </div>
  <div class="main-content">
    <div class="topbar">
      <div class="shop-title">Settings</div>
      <div></div>
    </div>
    <div class="settings-container">
      <div class="settings-title">Account Settings</div>
      <form class="settings-form" method="post" action="#">
        <label for="fullname">Full Name</label>
        <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        <button type="submit" name="update_profile">Update Profile</button>
      </form>
      <hr class="divider">
      <form class="settings-form" method="post" action="#">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" required>
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" required>
        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <button type="submit" name="change_password">Change Password</button>
      </form>
      <hr class="divider">
      <div class="danger-zone">
        <form method="post" style="margin:0;">
        </form>
      </div>
    </div>
  </div>
</div>
<script>
<?php if ($alert) { echo $alert; } ?>
// SweetAlert2 for delete confirmation
const deleteBtn = document.getElementById('deleteAccountBtn');
if (deleteBtn) {
  deleteBtn.onclick = function(e) {
    e.preventDefault();
    Swal.fire({
      title: 'Are you sure?',
      text: 'This action cannot be undone. Your account will be permanently deleted.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        deleteBtn.closest('form').submit();
      }
    });
  };
}
</script>
</body>
</html> 