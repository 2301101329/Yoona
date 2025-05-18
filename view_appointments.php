<?php
session_start();
require 'includes/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Cancel Appointment
if (isset($_POST['cancel_id'])) {
    $cancel_id = $_POST['cancel_id'];

    // Check if the appointment belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND user_id = ?");
    $stmt->execute([$cancel_id, $_SESSION['user_id']]);
    $appt = $stmt->fetch();

    if ($appt) {
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ?");
        $stmt->execute([$cancel_id]);
    }
}

// Fetch user's appointments
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY date, time");
$stmt->execute([$_SESSION['user_id']]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Appointments</title>
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
        .topbar .appt-title {
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
        .topbar .icon-link:hover {
            color: var(--primary);
            background: var(--accent);
        }
        .appointments-list {
            padding: 0 40px 40px 40px;
            margin-top: 30px;
        }
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
        .status {
            font-weight: bold;
            border-radius: 8px;
            padding: 0;
            min-width: 90px;
            text-align: left;
            font-size: 1rem;
            vertical-align: middle;
            line-height: 1.2;
            margin: 0 auto;
            background: none;
            border: none;
        }
        .status-badge {
            font-weight: bold;
            font-size: 1rem;
            margin-bottom: 2px;
            display: inline-block;
            background: none !important;
            border: none !important;
            padding: 0;
            min-width: 0;
        }
        .status-badge.declined {
            color: #D7263D;
        }
        .status-badge.done {
            color: #222;
        }
        .status-badge.cancelled {
            color: #6b7280;
        }
        .status-badge.pending {
            color: #FFD166;
        }
        .status-badge.accepted {
            color: var(--primary);
        }
        .reason {
            font-style: italic;
            color: #6b7280;
            margin-top: 2px;
            font-size: 0.95em;
            display: block;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: background 0.2s;
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
            .appointments-list {
                padding: 0 8px 40px 8px;
            }
            th, td {
                padding: 10px 6px;
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
            <li><a href="products.php"><img src="images/shop.png" alt=""> <span>Shop</span></a></li>
            <li><a href="schedule.php"><img src="images/services.png" alt=""> <span>Services</span></a></li>
            <li><a href="view_order_history.php"><img src="images/view-order.png" alt=""> <span>View Orders</span></a></li>
            <li><a href="view_appointments.php" class="active"><img src="images/appointments.png" alt=""> <span>View Appointments</span></a></li>
            <li><a href="settings.php"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
        </ul>
        <div class="logout">
            <a href="login.php">Logout</a>
        </div>
    </div>
    <div class="main-content">
        <div class="topbar">
            <div class="appt-title">My Appointments</div>
        </div>
        <div class="appointments-list">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($appointments as $appt): ?>
                <tr>
                    <td><?= htmlspecialchars($appt['date']) ?></td>
                    <td><?= htmlspecialchars($appt['time']) ?></td>
                    <td><?= htmlspecialchars($appt['service']) ?></td>
                    <td class="status">
                        <span class="status-badge <?= strtolower($appt['status']) ?>">
                            <?= htmlspecialchars($appt['status']) ?>
                        </span>
                        <?php if ($appt['status'] === 'Declined' && $appt['decline_reason']): ?>
                            <span class="reason">Reason: <?= htmlspecialchars($appt['decline_reason']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($appt['status'] === 'Pending'): ?>
                            <button class="btn cancel-btn" onclick="confirmCancel(<?= $appt['id'] ?>)">Cancel</button>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <!-- Hidden Form -->
            <form id="cancelForm" method="post" style="display: none;">
                <input type="hidden" name="cancel_id" id="cancel_id">
            </form>
        </div>
    </div>
</div>
<script>
function confirmCancel(id) {
    Swal.fire({
        title: 'Cancel Appointment?',
        text: "Are you sure you want to cancel this appointment?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, cancel it'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('cancel_id').value = id;
            document.getElementById('cancelForm').submit();
        }
    });
}
</script>
</body>
</html>

