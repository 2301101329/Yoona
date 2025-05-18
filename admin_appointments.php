<?php
session_start();
require 'includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Access denied.";
    exit;
}

// Fetch admin full name for topbar
$stmt = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_fullname = $stmt->fetchColumn();

// Handle Accept/Decline/Mark as Done
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'];
    $action = $_POST['action'];

    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
    $stmt->execute([$id]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $appt['user_id'] ?? null;

    if ($user_id) {
        if ($action === 'accept') {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'Accepted', decline_reason = NULL WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'decline') {
            $reason = $_POST['reason'] ?? 'No reason provided';
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'Declined', decline_reason = ? WHERE id = ?");
            $stmt->execute([$reason, $id]);
        } elseif ($action === 'done') {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'Done' WHERE id = ?");
            $stmt->execute([$id]);
        }
    }
}

// Fetch all appointments to display
$stmt = $pdo->query("SELECT * FROM appointments ORDER BY date, time");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Appointments</title>
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
        /* Appointments Table Styles */
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
        .accept-btn {
            background: #FFD166;
            color: #18315B;
        }
        .accept-btn:hover {
            background: #18315B;
            color: #FFD166;
        }
        .decline-btn {
            background: #FFE5E5;
            color: #D7263D;
        }
        .decline-btn:hover {
            background: #D7263D;
            color: #fff;
        }
        .done-btn {
            background: #E6F0FF;
            color: #18315B;
        }
        .done-btn:hover {
            background: #18315B;
            color: #FFD166;
        }
        .status {
            font-weight: bold;
        }
        .accepted {
            color: #10b981;
        }
        .declined {
            color: #ef4444;
        }
        .pending {
            color: #f59e0b;
        }
        .cancelled {
            color: #6b7280;
        }
        .reason {
            color: #6b7280;
            font-style: italic;
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
            <li><a href="admin_appointments.php" class="active"><img src="images/appointments.png" alt=""> <span>Appointments</span></a></li>
            <li><a href="admin_users.php"><img src="images/users.png" alt=""> <span>Users</span></a></li>
            <li><a href="admin_settings.php"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
        </ul>
        <div class="logout">
            <a href="login.php">Logout</a>
        </div>
    </div>
    <div class="main-content">
        <div class="topbar">
            <span style="font-size:1.5rem;font-weight:700;color:var(--primary);margin-right:32px;">Admin Appointments</span>
            <div class="admin">
                <div class="admin-avatar"><span>👤</span></div>
                <span><?php echo htmlspecialchars($admin_fullname); ?></span>
            </div>
        </div>
        <div class="content">
            <table>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($appointments as $appt): ?>
                <tr>
                    <td><?= htmlspecialchars($appt['fullname']) ?></td>
                    <td><?= htmlspecialchars($appt['email']) ?></td>
                    <td><?= htmlspecialchars($appt['date']) ?></td>
                    <td><?= htmlspecialchars($appt['time']) ?></td>
                    <td><?= htmlspecialchars($appt['service']) ?></td>
                    <td class="status <?= strtolower($appt['status']) ?>">
                        <?= htmlspecialchars($appt['status']) ?>
                        <?php if ($appt['status'] === 'Declined' && $appt['decline_reason']): ?>
                            <div class="reason">Reason: <?= htmlspecialchars($appt['decline_reason']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($appt['status'] === 'Pending'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $appt['id'] ?>">
                                <input type="hidden" name="action" value="accept">
                                <button type="submit" class="btn accept-btn">Accept</button>
                            </form>
                            <button class="btn decline-btn" onclick="declineAppointment(<?= $appt['id'] ?>)">Decline</button>
                        <?php elseif ($appt['status'] === 'Accepted'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $appt['id'] ?>">
                                <input type="hidden" name="action" value="done">
                                <button type="submit" class="btn done-btn">Mark as Done</button>
                            </form>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <form id="declineForm" method="post" style="display:none;">
                <input type="hidden" name="id" id="declineId">
                <input type="hidden" name="action" value="decline">
                <input type="hidden" name="reason" id="declineReason">
            </form>
            <script>
            function declineAppointment(id) {
                Swal.fire({
                    title: 'Decline Appointment',
                    input: 'text',
                    inputLabel: 'Reason for declining',
                    inputPlaceholder: 'Enter reason here...',
                    showCancelButton: true,
                    confirmButtonText: 'Submit',
                    inputValidator: (value) => {
                        if (!value) return 'Please enter a reason';
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('declineId').value = id;
                        document.getElementById('declineReason').value = result.value;
                        document.getElementById('declineForm').submit();
                    }
                });
            }
            </script>
        </div>
    </div>
</div>
</body>
</html>
