<?php
// فایل درگاه بانکی واسطه (سپ)
// این فایل به صورت ماژولار طراحی شده تا کمترین تغییر در فایل‌های دیگر ربات ایجاد شود.

function initSepDatabase($pdo) {
    // بررسی وجود تنظیمات درگاه در دیتابیس
    $stmt = $pdo->prepare("SELECT * FROM PaySetting WHERE NamePay = 'sepstatus'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $settings = [
            ['sepstatus', 'offsep'],
            ['domain_sep', 'thunderfile.ir'], // دامنه سایت واسطه
            ['chashbacksep', '0'],
            ['minbalancesep', '20000'],
            ['maxbalancesep', '1000000'],
            ['helpsep', '2']
        ];
        
        foreach ($settings as $setting) {
            $__q = $pdo->prepare("INSERT INTO PaySetting (NamePay, ValuePay) VALUES (?, ?)");
            $__q->bindValue(1, $setting[0], PDO::PARAM_STR);
            $__q->bindValue(2, $setting[1], PDO::PARAM_STR);
            $__q->execute();
        }
    }
}

function createPaySep($price, $order_id) {
    global $domainhosts;
    
    $domain_sep = select("PaySetting", "ValuePay", "NamePay", "domain_sep", "select")['ValuePay'];
    if(empty($domain_sep)) {
        $domain_sep = "thunderfile.ir";
    }

    $callback_base = "https://" . $domainhosts . "/payment/sep_callback.php";
    
    // تبدیل قیمت به ریال (با فرض اینکه قیمت‌های ربات تومان است)
    $amount_rial = intval($price) * 10;
    
    $token = bin2hex(random_bytes(16));

    $data = [
        'amount' => $amount_rial,
        'token' => $token,
        'domain' => $domainhosts, // دامنه ربات برای بررسی در لیست مجاز سایت واسطه
        'order_id' => $order_id,
        'callback_base' => $callback_base,
        'customer_name' => '',
        'cell_number' => ''
    ];

    $proxy_url = "https://" . $domain_sep . "/wp-json/exella/v1/payment-proxy/";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $proxy_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data), // سایت واسطه WP_REST_Request را دریافت میکند
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
?>
