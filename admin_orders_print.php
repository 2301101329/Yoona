<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'includes/db.php';

// Fetch all orders
$stmt = $pdo->query("SELECT orders.*, users.fullname, products.name AS product_name
                     FROM orders
                     JOIN users ON orders.user_id = users.id
                     JOIN products ON orders.product_id = products.id
                     ORDER BY orders.created_at DESC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
$html = '<h2 style="text-align:center;color:#18315B;">Orders Report</h2>';
$html .= '<table style="width:100%;border-collapse:collapse;font-family:Poppins,sans-serif;">';
$html .= '<thead><tr style="background:#18315B;color:#FFD166;">';
$html .= '<th style="padding:10px 6px;">User</th>';
$html .= '<th style="padding:10px 6px;">Product</th>';
$html .= '<th style="padding:10px 6px;">Product ID</th>';
$html .= '<th style="padding:10px 6px;">Quantity</th>';
$html .= '<th style="padding:10px 6px;">Date</th>';
$html .= '<th style="padding:10px 6px;">Status</th>';
$html .= '</tr></thead><tbody>';
foreach ($orders as $order) {
    $html .= '<tr style="border-bottom:1px solid #eee;">';
    $html .= '<td style="padding:8px 6px;">' . htmlspecialchars($order['fullname']) . '</td>';
    $html .= '<td style="padding:8px 6px;">' . htmlspecialchars($order['product_name']) . '</td>';
    $html .= '<td style="padding:8px 6px;">' . $order['product_id'] . '</td>';
    $html .= '<td style="padding:8px 6px;">' . $order['quantity'] . '</td>';
    $html .= '<td style="padding:8px 6px;">' . date('M d, Y H:i', strtotime($order['created_at'])) . '</td>';
    $html .= '<td style="padding:8px 6px;">' . htmlspecialchars($order['status']) . '</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table>';

$mpdf->WriteHTML($html);
$mpdf->Output('orders_report.pdf', \Mpdf\Output\Destination::INLINE); 