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

// Handle delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$del_id]);
    header('Location: admin_users.php');
    exit();
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE fullname LIKE ? OR username LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Fetch users
if ($where) {
    $where .= ($where ? ' AND ' : 'WHERE ') . "role = 'user'";
} else {
    $where = "WHERE role = 'user'";
}
$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Users</title>
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
            padding: 12px 32px 0 32px;
        }
        /* Enhanced Search Bar Styles */
        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }
        .search-bar input[type="text"] {
            padding: 8px 16px;
            border-radius: 20px;
            border: 1.5px solid #B3C7E6;
            font-size: 1rem;
            outline: none;
            background: #fff;
            box-shadow: 0 1px 4px rgba(24,49,91,0.07);
            transition: border 0.2s, box-shadow 0.2s;
            width: 200px;
        }
        .search-bar input[type="text"]:focus {
            border: 1.5px solid #FFD166;
            box-shadow: 0 2px 8px rgba(255,209,102,0.10);
        }
        .search-bar .btn {
            padding: 8px 20px;
            font-size: 1rem;
            border-radius: 20px;
            font-weight: 600;
            box-shadow: 0 1px 4px rgba(24,49,91,0.07);
        }
        /* Remove underline from all buttons */
        .btn, button, .btn:visited, .btn:active, .btn:focus, a.btn {
            text-decoration: none !important;
        }
        /* Users Table Styles */
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
        .btn {
            padding: 8px 18px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            margin: 2px;
        }
        .edit-btn {
            background: #FFD166;
            color: #18315B;
        }
        .edit-btn:hover {
            background: #18315B;
            color: #FFD166;
        }
        .delete-btn {
            background: #FFE5E5;
            color: #D7263D;
        }
        .delete-btn:hover {
            background: #D7263D;
            color: #fff;
        }
        .reset-btn {
            background: #E6F0FF;
            color: #18315B;
        }
        .reset-btn:hover {
            background: #18315B;
            color: #FFD166;
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
            <span style="font-size:1.5rem;font-weight:700;color:var(--primary);margin-right:32px;">Admin Users</span>
            <div class="admin">
                <div class="admin-avatar"><span>👤</span></div>
                <span><?php echo htmlspecialchars($admin_fullname); ?></span>
            </div>
        </div>
        <div class="content">
            <form method="get" class="search-bar">
                <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn">Search</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td class="actions">
                            <a href="admin_edit_user.php?id=<?php echo $user['id']; ?>" class="btn edit-btn">Edit</a>
                            <a href="admin_reset_password.php?id=<?php echo $user['id']; ?>" class="btn reset-btn">Reset Password</a>
                            <button class="btn delete-btn" onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['fullname'])); ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <form id="deleteUserForm" method="get" style="display:none;">
                <input type="hidden" name="delete" id="deleteUserId">
            </form>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
            function confirmDeleteUser(userId, userName) {
                Swal.fire({
                    title: 'Delete User',
                    text: `Are you sure you want to delete user "${userName}"? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('deleteUserId').value = userId;
                        document.getElementById('deleteUserForm').submit();
                    }
                });
            }
            </script>
        </div>
    </div>
</div>
</body>
</html> 