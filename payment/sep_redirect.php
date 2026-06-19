<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
if (empty($order_id)) {
    die("شناسه نامعتبر");
}

$Payment_report = select("Payment_report", "*", "id_order", $order_id, "select");
if (!$Payment_report || $Payment_report['Payment_Method'] !== 'sep') {
    die("سفارش یافت نشد");
}

$redirect_url = $Payment_report['dec_not_confirmed'];
if (empty($redirect_url) || strpos($redirect_url, 'http') !== 0) {
    die("لینک پرداخت یافت نشد");
}

header("Location: " . $redirect_url);
exit;
