<?php
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['receipt_date'], $_SESSION['receipt_time'], $_SESSION['receipt_service'])) {
    header('Location: schedule.php');
    exit();
}

$fullname = $_SESSION['fullname'];
$email = $_SESSION['email'];
$date = $_SESSION['receipt_date'];
$time = $_SESSION['receipt_time'];
$service = $_SESSION['receipt_service'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Receipt</title>
    <link rel="icon" href="images/yoona.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #e9ecef;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }
        .receipt-container {
            max-width: 420px;
            margin: 50px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(24,49,91,0.13);
            padding: 0 0 32px 0;
            overflow: hidden;
        }
        .receipt-header {
            background: #18315B;
            color: #FFD166;
            padding: 32px 24px 18px 24px;
            text-align: center;
        }
        .receipt-header img {
            width: 60px;
            height: 60px;
            margin-bottom: 10px;
        }
        .receipt-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .receipt-id {
            font-size: 1rem;
            color: #FFD166;
            margin-bottom: 0;
        }
        .receipt-body {
            padding: 28px 32px 0 32px;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 1.08rem;
        }
        .receipt-label {
            color: #18315B;
            font-weight: 600;
        }
        .receipt-value {
            color: #374151;
        }
        .receipt-footer {
            margin-top: 32px;
            text-align: center;
            color: #6c757d;
            font-size: 1rem;
        }
        .receipt-actions {
            text-align: center;
            margin-top: 24px;
        }
        .btn-download {
            background: #FFD166;
            color: #18315B;
            border: none;
            border-radius: 8px;
            padding: 12px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin: 0 8px;
            transition: background 0.2s;
        }
        .btn-download:hover {
            background: #18315B;
            color: #FFD166;
        }
        @media print {
            .btn-download, .receipt-actions, .back-main { display: none !important; }
            body { background: #fff; }
            .receipt-container { box-shadow: none; margin: 0; }
        }
        .back-main {
            display: inline-block;
            margin-top: 18px;
            padding: 10px 24px;
            background: #18315B;
            color: #FFD166;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .back-main:hover {
            background: #FFD166;
            color: #18315B;
        }
    </style>
</head>
<body>
<div class="receipt-container">
    <div class="receipt-header">
        <img src="images/yoona.png" alt="Yoona Logo">
        <div class="receipt-title">Appointment Receipt</div>
        <div class="receipt-id">#<?= strtoupper(substr(md5($fullname.$date.$time), 0, 8)) ?></div>
    </div>
    <div class="receipt-body">
        <div class="receipt-row"><span class="receipt-label">Full Name:</span> <span class="receipt-value"><?= htmlspecialchars($fullname) ?></span></div>
        <div class="receipt-row"><span class="receipt-label">Email:</span> <span class="receipt-value"><?= htmlspecialchars($email) ?></span></div>
        <div class="receipt-row"><span class="receipt-label">Appointment Date:</span> <span class="receipt-value"><?= htmlspecialchars($date) ?></span></div>
        <div class="receipt-row"><span class="receipt-label">Appointment Time:</span> <span class="receipt-value"><?= htmlspecialchars($time) ?></span></div>
        <div class="receipt-row"><span class="receipt-label">Service:</span> <span class="receipt-value"><?= htmlspecialchars($service) ?></span></div>
        <div class="receipt-footer">
            Thank you for trusting and choosing us!<br>
            Please wait for the confirmation of your appointment.
        </div>
        <div class="receipt-actions">
            <button class="btn-download" onclick="document.getElementById('pdfForm').submit(); return false;">Download</button>
        </div>
        <form id="pdfForm" method="post" action="download_receipt.php" style="display:none;">
            <input type="hidden" name="fullname" value="<?= htmlspecialchars($fullname) ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
            <input type="hidden" name="time" value="<?= htmlspecialchars($time) ?>">
            <input type="hidden" name="service" value="<?= htmlspecialchars($service) ?>">
        </form>
        <div style="text-align:center;">
            <a href="main.php" class="back-main">Back to Main</a>
        </div>
    </div>
</div>
</body>
</html>
