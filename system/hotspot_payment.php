<?php
/**
 * PHP Mikrotik Billing - Hotspot Payment Processor
 * Handles M-Pesa STK Push payments for hotspot access
 */

// Security check - prevent direct access
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden', TRUE, 403);
    header('location: ../');
    die();
}

// Include the main initialization
require_once 'init.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: hotspot_login.php');
    exit;
}

// Get form data
$plan_id = $_POST['plan_id'] ?? '';
$phone = $_POST['phone'] ?? '';
$mac = $_POST['mac'] ?? '';
$ip = $_POST['ip'] ?? '';
$router = $_POST['router'] ?? '';

// Validate required fields
if (empty($plan_id) || empty($phone) || empty($mac) || empty($ip)) {
    $_SESSION['error'] = 'Missing required information. Please try again.';
    header('Location: hotspot_login.php');
    exit;
}

// Get plan details
$plan = ORM::for_table('tbl_plans')->find_one($plan_id);
if (!$plan || $plan['type'] !== 'Hotspot' || $plan['enabled'] !== '1') {
    $_SESSION['error'] = 'Invalid plan selected.';
    header('Location: hotspot_login.php');
    exit;
}

// Clean phone number (remove spaces, ensure it starts with country code)
$phone = preg_replace('/\s+/', '', $phone);
if (!preg_match('/^\+?254[0-9]{9}$/', $phone)) {
    // If it doesn't start with +254, add it
    if (preg_match('/^0[0-9]{9}$/', $phone)) {
        $phone = '+254' . substr($phone, 1);
    } elseif (preg_match('/^[0-9]{9}$/', $phone)) {
        $phone = '+254' . $phone;
    } elseif (preg_match('/^[0-9]{12}$/', $phone)) {
        $phone = '+' . $phone;
    } else {
        $_SESSION['error'] = 'Invalid phone number format. Please use format: 0712345678 or +254712345678';
        header('Location: hotspot_login.php');
        exit;
    }
}

// Create a temporary customer record for this transaction
$temp_customer = [
    'username' => 'hotspot_' . time() . '_' . rand(1000, 9999),
    'fullname' => 'Hotspot User',
    'email' => 'hotspot@example.com',
    'phone' => $phone,
    'password' => md5(time() . rand()),
    'pppoe_password' => md5(time() . rand()),
    'service_type' => 'Hotspot',
    'hotspot_plan' => $plan['name_plan']
];

// Create payment record in database
$payment = ORM::for_table('tbl_payment_gateway')->create();
$payment->username = $temp_customer['username'];
$payment->gateway = 'mpesa';
$payment->plan_id = $plan_id;
$payment->plan_name = $plan['name_plan'];
$payment->routers_id = $router;
$payment->routers = 'Hotspot Router';
$payment->price = $plan['price'];
$payment->payment_method = 'M-Pesa STK Push';
$payment->payment_channel = 'Hotspot Payment';
$payment->created_date = date('Y-m-d H:i:s');
$payment->status = 1; // 1 = pending
$payment->save();

// Store transaction details in session for callback processing
$_SESSION['hotspot_transaction'] = [
    'plan_id' => $plan_id,
    'plan_name' => $plan['name_plan'],
    'amount' => $plan['price'],
    'phone' => $phone,
    'mac' => $mac,
    'ip' => $ip,
    'router' => $router,
    'customer' => $temp_customer,
    'payment_id' => $payment->id,
    'timestamp' => time()
];

// Check if M-Pesa STK gateway is configured
$mpesa_config = $config['mpesa_consumer_key'] ?? '';
$mpesa_secret = $config['mpesa_consumer_secret'] ?? '';
$mpesa_shortcode = $config['mpesa_shortcode'] ?? '';
$mpesa_passkey = $config['mpesa_passkey'] ?? '';

if (empty($mpesa_config) || empty($mpesa_secret) || empty($mpesa_shortcode)) {
    $_SESSION['error'] = 'Payment gateway not configured. Please contact administrator.';
    header('Location: hotspot_login.php');
    exit;
}

// Initialize M-Pesa STK Push
try {
    // Generate timestamp and password
    $timestamp = date('YmdHis');
    $password = base64_encode($mpesa_shortcode . $mpesa_passkey . $timestamp);

    // Prepare STK push data
    $stk_data = [
        'BusinessShortCode' => $mpesa_shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => intval($plan['price']),
        'PartyA' => str_replace('+', '', $phone),
        'PartyB' => $mpesa_shortcode,
        'PhoneNumber' => str_replace('+', '', $phone),
        'CallBackURL' => APP_URL . '/system/hotspot_callback.php',
        'AccountReference' => 'Hotspot-' . $plan_id,
        'TransactionDesc' => 'Hotspot Internet Access - ' . $plan['name_plan']
    ];

    // Get access token
    $token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    if ($config['mpesa_mode'] ?? 'sandbox' === 'live') {
        $token_url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    }

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($mpesa_config . ':' . $mpesa_secret)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $token_response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($token_response, true);
    if (!isset($token_data['access_token'])) {
        throw new Exception('Failed to get M-Pesa access token');
    }

    // Initiate STK Push
    $stk_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    if ($config['mpesa_mode'] ?? 'sandbox' === 'live') {
        $stk_url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    }

    $ch = curl_init($stk_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token_data['access_token'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stk_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $stk_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $stk_result = json_decode($stk_response, true);

    if ($http_code === 200 && isset($stk_result['ResponseCode']) && $stk_result['ResponseCode'] === '0') {
        // STK Push initiated successfully
        $_SESSION['hotspot_checkout_id'] = $stk_result['CheckoutRequestID'];
        $_SESSION['hotspot_transaction']['checkout_id'] = $stk_result['CheckoutRequestID'];

        // Redirect to waiting page
        header('Location: ' . APP_URL . '/system/hotspot_waiting.php');
        exit;
    } else {
        $error_message = $stk_result['CustomerMessage'] ?? 'Payment initiation failed. Please try again.';
        $_SESSION['error'] = $error_message;
        header('Location: hotspot_login.php');
        exit;
    }

} catch (Exception $e) {
    $_SESSION['error'] = 'Payment processing error: ' . $e->getMessage();
    header('Location: hotspot_login.php');
    exit;
}
?>