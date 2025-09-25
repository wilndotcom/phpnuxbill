<?php

/**
 * M-Pesa STK Push Payment Gateway for PHP Mikrotik Billing
 *
 * This module integrates M-Pesa STK Push payments
 *
 * @author Kilo Code
 * @version 1.0.0
 */

function mpesastk_validate_config()
{
    global $config;
    if (empty($config['mpesastk_shortcode']) || empty($config['mpesastk_passkey']) || empty($config['mpesastk_consumer_key']) || empty($config['mpesastk_consumer_secret'])) {
        sendTelegram("M-Pesa STK Push payment gateway not configured");
        r2(U . 'order/package', 'w', "Admin has not yet setup M-Pesa STK Push payment gateway, please tell admin");
    }
}

function mpesastk_show_config()
{
    global $ui;
    _log('M-Pesa STK show config called', 'Admin', 0);
    $ui->assign('_title', 'M-Pesa STK Push - Payment Gateway');
    $ui->display('mpesastk.tpl');
}

function mpesastk_save_config()
{
    global $admin, $_L;
    $mpesastk_shortcode = _post('mpesastk_shortcode');
    $mpesastk_passkey = _post('mpesastk_passkey');
    $mpesastk_consumer_key = _post('mpesastk_consumer_key');
    $mpesastk_consumer_secret = _post('mpesastk_consumer_secret');
    $mpesastk_environment = _post('mpesastk_environment');

    // Save configurations
    $configs = [
        'mpesastk_shortcode' => $mpesastk_shortcode,
        'mpesastk_passkey' => $mpesastk_passkey,
        'mpesastk_consumer_key' => $mpesastk_consumer_key,
        'mpesastk_consumer_secret' => $mpesastk_consumer_secret,
        'mpesastk_environment' => $mpesastk_environment
    ];

    foreach ($configs as $setting => $value) {
        $d = ORM::for_table('tbl_appconfig')->where('setting', $setting)->find_one();
        if ($d) {
            $d->value = $value;
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = $setting;
            $d->value = $value;
            $d->save();
        }
    }

    _log('[' . $admin['username'] . ']: M-Pesa STK Push ' . Lang::T('Settings_Saved_Successfully'), 'Admin', $admin['id']);
    r2(U . 'paymentgateway/mpesastk', 's', Lang::T('Settings_Saved_Successfully'));
}

function mpesastk_create_transaction($trx, $user)
{
    global $config;

    // Get access token
    $access_token = mpesastk_get_access_token();
    if (!$access_token) {
        r2(U . 'order/package', 'e', "Failed to get M-Pesa access token.");
    }

    // Prepare STK push request
    $timestamp = date('YmdHis');
    $password = base64_encode($config['mpesastk_shortcode'] . $config['mpesastk_passkey'] . $timestamp);

    $phone = $user['phonenumber'] ?? '';
    if (empty($phone)) {
        r2(U . 'order/package', 'e', "Phone number is required for M-Pesa payment.");
    }

    // Format phone number (remove + and ensure it starts with 254)
    $phone = preg_replace('/^\+/', '', $phone);
    if (strpos($phone, '254') !== 0) {
        $phone = '254' . substr($phone, -9);
    }

    $callback_url = U . 'callback/mpesastk';

    $stk_request = [
        'BusinessShortCode' => $config['mpesastk_shortcode'],
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => intval($trx['price']),
        'PartyA' => $phone,
        'PartyB' => $config['mpesastk_shortcode'],
        'PhoneNumber' => $phone,
        'CallBackURL' => $callback_url,
        'AccountReference' => 'TRX' . $trx['id'],
        'TransactionDesc' => 'Payment for ' . $trx['plan_name']
    ];

    $result = mpesastk_stk_push($stk_request, $access_token);

    // Handle common failure modes with clearer messages
    if (!$result || !isset($result['ResponseCode']) || $result['ResponseCode'] != '0') {
        $responseDescription = $result['ResponseDescription'] ?? '';
        $customerMessage = $result['CustomerMessage'] ?? '';
        $errorMessage = $result['errorMessage'] ?? ($result['error'] ?? '');
        $errorCode = $result['errorCode'] ?? '';
        $requestId = $result['requestId'] ?? '';

        $humanMessage = 'Failed to initiate M-Pesa payment.';
        if (!empty($customerMessage)) {
            $humanMessage = $customerMessage;
        } else if (!empty($responseDescription)) {
            $humanMessage .= ' ' . $responseDescription;
        } else if (!empty($errorMessage)) {
            $humanMessage .= ' ' . $errorMessage;
        }

        // Log full payload for admins
        sendTelegram("M-Pesa STK Push FAILED:\n" . json_encode($result, JSON_PRETTY_PRINT));

        // Show more details in Dev mode to help setup
        global $_app_stage;
        if ($_app_stage != 'Live') {
            $debugBits = [];
            if ($errorCode) { $debugBits[] = "code=$errorCode"; }
            if ($requestId) { $debugBits[] = "req=$requestId"; }
            if (!empty($result['CheckoutRequestID'])) { $debugBits[] = 'crid=' . $result['CheckoutRequestID']; }
            $humanMessage .= (!empty($debugBits) ? ' [' . implode('; ', $debugBits) . ']' : '');
        }

        r2(U . 'order/package', 'e', $humanMessage);
    }

    // Save transaction details
    $d = ORM::for_table('tbl_payment_gateway')
        ->where('username', $user['username'])
        ->where('status', 1)
        ->find_one();
    $d->gateway_trx_id = $result['CheckoutRequestID'];
    $d->pg_url_payment = ''; // STK push doesn't have a URL
    $d->pg_request = json_encode($result);
    $d->expired_date = date('Y-m-d H:i:s', strtotime("+ 5 MINUTE"));
    $d->save();

    r2(U . "order/view/" . $trx['id'], 's', "M-Pesa STK Push sent to your phone. Please complete the payment.");
}

function mpesastk_payment_notification()
{
    // Handle callback from M-Pesa
    $callback_data = json_decode(file_get_contents('php://input'), true);

    if (!$callback_data) {
        http_response_code(400);
        die('Invalid callback data');
    }

    // Log callback
    _log('M-Pesa STK Push Callback: ' . json_encode($callback_data), 'Payment', 0);

    if (isset($callback_data['Body']['stkCallback'])) {
        $stk_callback = $callback_data['Body']['stkCallback'];

        $checkout_request_id = $stk_callback['CheckoutRequestID'];
        $result_code = $stk_callback['ResultCode'];
        $result_desc = $stk_callback['ResultDesc'];

        if ($result_code == 0) {
            // Payment successful
            $callback_metadata = $stk_callback['CallbackMetadata']['Item'];

            $amount = 0;
            $mpesa_receipt_number = '';
            $transaction_date = '';
            $phone_number = '';

            foreach ($callback_metadata as $item) {
                switch ($item['Name']) {
                    case 'Amount':
                        $amount = $item['Value'];
                        break;
                    case 'MpesaReceiptNumber':
                        $mpesa_receipt_number = $item['Value'];
                        break;
                    case 'TransactionDate':
                        $transaction_date = $item['Value'];
                        break;
                    case 'PhoneNumber':
                        $phone_number = $item['Value'];
                        break;
                }
            }

            // Find transaction by CheckoutRequestID
            $trx = ORM::for_table('tbl_payment_gateway')
                ->where('gateway_trx_id', $checkout_request_id)
                ->find_one();

            if ($trx) {
                $user = ORM::for_table('tbl_customers')->find_one($trx['username']);

                if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'], 'M-Pesa STK Push')) {
                    _log('Failed to activate package for M-Pesa STK Push payment: ' . $checkout_request_id, 'Payment', 0);
                }

                $trx->pg_paid_response = json_encode($callback_data);
                $trx->payment_method = 'MPESA';
                $trx->payment_channel = 'mpesastk';
                $trx->paid_date = date('Y-m-d H:i:s');
                $trx->status = 2;
                $trx->save();

                _log('M-Pesa STK Push payment successful: ' . $mpesa_receipt_number, 'Payment', $user['id']);
            }
        } else {
            // Payment failed
            _log('M-Pesa STK Push payment failed: ' . $result_desc . ' (' . $checkout_request_id . ')', 'Payment', 0);
        }
    }

    // Respond to M-Pesa
    header('Content-Type: application/json');
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    exit();
}

function mpesastk_get_status($trx, $user)
{
    // For M-Pesa, status is updated via callback, but we can check if expired
    if (strtotime($trx['expired_date']) < time() && $trx['status'] == 1) {
        $trx->status = 3;
        $trx->save();
        r2(U . "order/view/" . $trx['id'], 'd', "Transaction expired.");
    } else if ($trx['status'] == 2) {
        r2(U . "order/view/" . $trx['id'], 's', "Transaction has been paid.");
    } else {
        r2(U . "order/view/" . $trx['id'], 'w', "Transaction is pending payment.");
    }
}

function mpesastk_get_access_token()
{
    global $config;

    $url = mpesastk_get_base_url() . '/oauth/v1/generate?grant_type=client_credentials';

    $credentials = base64_encode($config['mpesastk_consumer_key'] . ':' . $config['mpesastk_consumer_secret']);

    $headers = [
        'Authorization: Basic ' . $credentials
    ];

    $result = json_decode(Http::getData($url, $headers), true);

    if (!is_array($result) || empty($result['access_token'])) {
        sendTelegram('M-Pesa STK get token failed: ' . json_encode($result));
        return false;
    }
    return $result['access_token'];
}

function mpesastk_stk_push($data, $access_token)
{
    $url = mpesastk_get_base_url() . '/mpesa/stkpush/v1/processrequest';

    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ];

    return json_decode(Http::postJsonData($url, $data, $headers), true);
}

function mpesastk_get_base_url()
{
    global $config;
    if (($config['mpesastk_environment'] ?? 'sandbox') == 'live') {
        return 'https://api.safaricom.co.ke';
    } else {
        return 'https://sandbox.safaricom.co.ke';
    }
}