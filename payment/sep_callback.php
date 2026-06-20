<?php
ini_set('error_log', 'error_log');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jdf.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../Marzban.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../keyboard.php';
require_once __DIR__ . '/../panels.php';

$ManagePanel = new ManagePanel();

$status = isset($_GET['status']) ? $_GET['status'] : '';
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
$ref_id = isset($_GET['ref_id']) ? $_GET['ref_id'] : '';
$message = isset($_GET['message']) ? $_GET['message'] : '';

if (empty($order_id)) {
    die("سفارش نامعتبر است");
}

$Payment_report = select("Payment_report", "*", "id_order", $order_id, "select");
if (!$Payment_report) {
    die("سفارش نامعتبر است");
}

$price = $Payment_report['price']; // Toman
$setting = select("setting", "*");
$textbotlang = languagechange();

$payment_status = "";
$dec_payment_status = "";

if ($status == 'success') {
    $payment_status = "پرداخت با موفقیت انجام شد";
    $dec_payment_status = "با تشکر از خرید شما";

    if ($Payment_report['payment_Status'] != "paid") {
        DirectPayment($order_id, "../images.jpg");

        $pricecashback = select("PaySetting", "ValuePay", "NamePay", "chashbacksep", "select")['ValuePay'];
        $Balance_id = select("user", "*", "id", $Payment_report['id_user'], "select");
        
        if ($pricecashback != "0" && $pricecashback != null) {
            $result = ($Payment_report['price'] * $pricecashback) / 100;
            $Balance_confrim = intval($Balance_id['Balance']) + $result;
            update("user", "Balance", $Balance_confrim, "id", $Balance_id['id']); 
            
            if (isset($textbotlang['paymentGateway']['giftReport'])) {
                $text_report = sprintf($textbotlang['paymentGateway']['giftReport'], $result);
                sendmessage($Balance_id['id'], $text_report, null, 'HTML');
            }
        }

        update("Payment_report", "payment_Status", "paid", "id_order", $order_id);
        
        $paymentreports = select("topicid", "idreport", "report", "paymentreport", "select")['idreport'];
        $price_formatted = number_format($price);
        
        $text_report = "💳 تراکنش موفق درگاه بانکی\n\n" .
                       "👤 شناسه کاربر: <code>{$Payment_report['id_user']}</code>\n" .
                       "👤 نام کاربری: @{$Balance_id['username']}\n" .
                       "💰 مبلغ: $price_formatted تومان\n" .
                       "🔖 شماره سفارش: $order_id\n" .
                       "🔢 شماره پیگیری: $ref_id";
        
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $paymentreports,
                'text' => $text_report,
                'parse_mode' => "HTML"
            ]);
        }
    }
} else {
    $payment_status = "پرداخت ناموفق بود";
    $dec_payment_status = urldecode($message);
}
?>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نتیجه تراکنش</title>
    <style>
    @font-face {
        font-family: 'vazir';
        src: url('/Vazir.eot');
        src: local('☺'), url('../fonts/Vazir.woff') format('woff'), url('../fonts/Vazir.ttf') format('truetype');
    }
    body {
        font-family: vazir, tahoma, sans-serif;
        background-color: #f2f2f2;
        margin: 0;
        padding: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        direction: rtl;
    }
    .confirmation-box {
        background-color: #ffffff;
        border-radius: 8px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 40px;
        text-align: center;
    }
    h1 {
        color: <?php echo ($status == 'success') ? '#4CAF50' : '#F44336'; ?>;
        margin-bottom: 20px;
        font-size: 22px;
    }
    p {
        color: #666666;
        margin-bottom: 10px;
        font-size: 15px;
    }
    .ref-number {
        font-weight: bold;
        color: #333;
        background: #f9f9f9;
        padding: 5px 10px;
        border-radius: 4px;
        display: inline-block;
        margin-top: 10px;
    }
    </style>
</head>
<body>
    <div class="confirmation-box">
        <h1><?php echo $payment_status; ?></h1>
        <p>شماره سفارش: <span class="ref-number"><?php echo htmlspecialchars($order_id); ?></span></p>
        <p>مبلغ: <span><?php echo number_format($price); ?></span> تومان</p>
        <?php if ($status == 'success' && !empty($ref_id)) : ?>
        <p>شماره پیگیری: <span class="ref-number"><?php echo htmlspecialchars($ref_id); ?></span></p>
        <?php endif; ?>
        <p><?php echo htmlspecialchars($dec_payment_status); ?></p>
        <br>
        <p style="font-size: 13px; color: #888;">لطفاً به ربات بازگردید.</p>
    </div>
</body>
</html>
