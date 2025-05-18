<?php
session_start();
require 'includes/db.php';

$error = '';
$success = '';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$fullname = $_SESSION['fullname'];
$email = $_SESSION['email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $service = $_POST['service'];

    if (empty($appointment_date) || empty($appointment_time) || empty($service)) {
        $error = 'Please select a date, time, and service.';
    } else {
        $today = date('Y-m-d');
        if ($appointment_date < $today) {
            $error = 'You cannot book an appointment in the past.';
        } else {
            try {
                $check_query = "SELECT * FROM appointments WHERE date = :appointment_date AND time = :appointment_time";
                $stmt = $pdo->prepare($check_query);
                $stmt->execute([
                    ':appointment_date' => $appointment_date,
                    ':appointment_time' => $appointment_time
                ]);

                if ($stmt->rowCount() > 0) {
                    $error = 'Selected slot is already booked. Please choose another.';
                } else {
                    $insert_query = "INSERT INTO appointments (fullname, email, user_id, date, time, service)
                                     VALUES (:fullname, :email, :user_id, :date, :time, :service)";
                    $stmt = $pdo->prepare($insert_query);
                    $stmt->execute([
                        ':fullname' => $fullname,
                        ':email' => $email,
                        ':user_id' => $_SESSION['user_id'],
                        ':date' => $appointment_date,
                        ':time' => $appointment_time,
                        ':service' => $service
                    ]);

                    $_SESSION['receipt_date'] = $appointment_date;
                    $_SESSION['receipt_time'] = $appointment_time;
                    $_SESSION['receipt_service'] = $service;

                    header('Location: receipt.php');
                    exit();
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule Appointment</title>
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
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
            width: 100%;
        }
        .topbar .schedule-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }
        .form-row {
            display: flex;
            gap: 16px;
        }
        .form-row > div {
            flex: 1;
        }
        .form-card {
            background: var(--card-bg);
            border-radius: 18px;
            box-shadow: var(--card-shadow);
            padding: 32px 24px 24px 24px;
            max-width: 400px;
            width: 100%;
            margin: 30px auto 30px auto;
        }
        .form-card h2 {
            text-align: center;
            margin-bottom: 24px;
            color: var(--primary);
            font-size: 2rem;
        }
        label {
            display: block;
            margin-top: 18px;
            font-weight: 600;
            color: #374151;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 7px;
            border-radius: 8px;
            border: 1.5px solid #e5e7eb;
            font-size: 1rem;
            background: #f8fafc;
            margin-bottom: 2px;
        }
        input:focus, select:focus {
            outline: 2px solid var(--accent);
            border-color: var(--accent);
        }
        .info {
            margin-top: 10px;
            font-size: 0.95em;
            color: #6b7280;
        }
        .btn-primary {
            margin-top: 28px;
            width: 100%;
            padding: 12px;
            background: var(--accent);
            color: var(--primary);
            border: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: var(--primary);
            color: #fff;
        }
        .error {
            background: var(--danger);
            color: var(--danger-text);
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 8px;
            text-align: center;
            border: 1.5px solid var(--danger-border);
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 8px;
            text-align: center;
            border: 1.5px solid #34d399;
        }
        @media (max-width: 700px) {
            .main-content {
                margin-left: 0;
                padding: 0 8px;
            }
            .form-row { flex-direction: column; gap: 0; }
            .form-card { padding: 18px 4px 18px 4px; }
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
            <li><a href="schedule.php" class="active"><img src="images/services.png" alt=""> <span>Services</span></a></li>
            <li><a href="view_order_history.php"><img src="images/view-order.png" alt=""> <span>View Orders</span></a></li>
            <li><a href="view_appointments.php"><img src="images/appointments.png" alt=""> <span>View Appointments</span></a></li>
            <li><a href="settings.php"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
        </ul>
        <div class="logout">
            <a href="login.php">Logout</a>
        </div>
    </div>
    <div class="main-content">
        <div class="topbar">
            <div class="schedule-title">Book an Appointment</div>
        </div>
        <div class="form-card">
            <h2>Appointment Form</h2>
            <?php if ($error): ?>
                <script>Swal.fire('Error', <?= json_encode($error) ?>, 'error');</script>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off" id="appointmentForm">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($fullname) ?>" readonly>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" readonly>
                <div class="form-row">
                    <div>
                        <label for="appointment_date">Date</label>
                        <input type="date" id="appointment_date" name="appointment_date" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div>
                        <label for="appointment_time">Time</label>
                        <select id="appointment_time" name="appointment_time" required>
                            <option value="">Select a time</option>
                            <option value="09:00">09:00 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="13:00">01:00 PM</option>
                            <option value="14:00">02:00 PM</option>
                            <option value="15:00">03:00 PM</option>
                            <option value="16:00">04:00 PM</option>
                        </select>
                    </div>
                </div>
                <label for="service">Service</label>
                <select id="service" name="service" required>
                    <option value="">Select a service</option>
                    <option value="Repair">Laptop Repair</option>
                    <option value="Repair">Vendo Repair</option>
                    <option value="Maintenance-up">Reformat Maintenance</option>
                </select>
                <button type="submit" class="btn-primary">Book Appointment</button>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('appointmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const date = document.getElementById('appointment_date').value;
    const time = document.getElementById('appointment_time').value;
    if (!date || !time) {
        this.submit();
        return;
    }
    fetch('check_appointment_slot.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `date=${encodeURIComponent(date)}&time=${encodeURIComponent(time)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.taken) {
            Swal.fire('Slot Taken', 'The selected time slot is already booked. Please choose another.', 'error');
        } else {
            this.submit();
        }
    });
});
</script>
</body>
</html>
