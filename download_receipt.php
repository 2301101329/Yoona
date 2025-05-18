<?php
require 'vendor/autoload.php';

use Mpdf\Mpdf;

$fullname = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$service = $_POST['service'] ?? '';
$receiptId = strtoupper(substr(md5($fullname.$date.$time), 0, 8));

$html = ' 
<html><head><style>
body { font-family: Poppins, Arial, sans-serif; }
.receipt-container { max-width: 420px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(24,49,91,0.13); padding: 0 0 32px 0; overflow: hidden; }
.receipt-header { background: #18315B; color: #FFD166; padding: 32px 24px 18px 24px; text-align: center; }
.receipt-header img { width: 60px; height: 60px; margin-bottom: 10px; }
.receipt-title { font-size: 2rem; font-weight: 700; margin-bottom: 6px; }
.receipt-id { font-size: 1rem; color: #FFD166; margin-bottom: 0; }
.receipt-body { padding: 28px 32px 0 32px; }
.receipt-row { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 1.08rem; }
.receipt-label { color: #18315B; font-weight: 600; }
.receipt-value { color: #374151; }
.receipt-footer { margin-top: 32px; text-align: center; color: #6c757d; font-size: 1rem; }
</style></head><body>
<div class="receipt-container">
    <div class="receipt-header">
        <img src="images/yoona.png" alt="Yoona Logo">
        <div class="receipt-title">Appointment Receipt</div>
        <div class="receipt-id">#'.$receiptId.'</div>
    </div>
    <div class="receipt-body">
        <div class="receipt-row"><span class="receipt-label">Full Name:</span> <span class="receipt-value">'.htmlspecialchars($fullname).'</span></div>
        <div class="receipt-row"><span class="receipt-label">Email:</span> <span class="receipt-value">'.htmlspecialchars($email).'</span></div>
        <div class="receipt-row"><span class="receipt-label">Appointment Date:</span> <span class="receipt-value">'.htmlspecialchars($date).'</span></div>
        <div class="receipt-row"><span class="receipt-label">Appointment Time:</span> <span class="receipt-value">'.htmlspecialchars($time).'</span></div>
        <div class="receipt-row"><span class="receipt-label">Service:</span> <span class="receipt-value">'.htmlspecialchars($service).'</span></div>
        <div class="receipt-footer">
            Thank you for trusting and choosing us!<br>
            Please wait for the confirmation of your appointment.
        </div>
    </div>
</div>
</body></html>';

$mpdf = new Mpdf();
$mpdf->WriteHTML($html);
$mpdf->Output('Appointment_Receipt.pdf', \Mpdf\Output\Destination::DOWNLOAD);
exit; 