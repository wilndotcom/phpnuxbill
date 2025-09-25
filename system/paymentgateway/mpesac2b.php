<?php

/**
 * M-Pesa C2B Payment Gateway for PHP Mikrotik Billing
 *
 * This module integrates M-Pesa Customer to Business (C2B) payments
 *
 * @author Kilo Code
 * @version 1.0.0
 */

function mpesac2b_validate_config()
{
    global $config;
    if (empty($config['mpesa_c2b_shortcode']) || empty($config['mpesa_c2b_consumer_key']) || empty($config['mpesa_c2b_consumer_secret'])) {
        sendTelegram("M-Pesa C2B payment gateway not configured");
        r2(U . 'order/package', 'w', "Admin has not yet setup M-Pesa C2B payment gateway, please tell admin");
    }
}

function mpesac2b_show_config()
{
    global $ui;
    $ui->assign('_title', 'M-Pesa C2B - Payment Gateway');
    $ui->display('mpesac2b.tpl');
}

function mpesac2b_save_config()
{
    global $admin, $_L;
    $mpesa_c2b_shortcode = _post('mpesa_c2b_shortcode');
    $mpesa_c2b_consumer_key = _post('mpesa_c2b_consumer_key');
    $mpesa_c2b_consumer_secret = _post('mpesa_c2b_consumer_secret');
    $mpesa_c2b_environment = _post('mpesa_c2b_environment');

    // Save configurations
    $configs = [
        'mpesa_c2b_shortcode' => $mpesa_c2b_shortcode,
        'mpesa_c2b_consumer_key' => $mpesa_c2b_consumer_key,
        'mpesa_c2b_consumer_secret' => $mpesa_c2b_consumer_secret,
        'mpesa_c2b_environment' => $mpesa_c2b_environment
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

    _log('[' . $admin['username'] . ']: M-Pesa C2B ' . Lang::T('Settings_Saved_Successfully'), 'Admin', $admin['id']);
    r2(U . 'paymentgateway/mpesac2b', 's', Lang::T('Settings_Saved_Successfully'));
}

function mpesac2b_create_transaction($trx, $user)
{
    global $config;

    // For C2B, we don't initiate payment. Customer pays manually.
    // Save transaction details
    $d = ORM::for_table('tbl_payment_gateway')
        ->where('username', $user['username'])
        ->where('status', 1)
        ->find_one();
    $d->gateway_trx_id = 'TRX' . $trx['id']; // Use as BillRefNumber
    $d->pg_url_payment = '';
    $d->pg_request = json_encode(['type' => 'c2b', 'account_reference' => 'TRX' . $trx['id']]);
    $d->expired_date = date('Y-m-d H:i:s', strtotime("+ 1 HOUR")); // Longer expiry for manual payment
    $d->save();

    r2(U . "order/view/" . $trx['id'], 's', "Please pay KES " . $trx['price'] . " to PayBill " . $config['mpesa_c2b_shortcode'] . " Account " . 'TRX' . $trx['id'] . " via M-Pesa.");
}

function mpesac2b_payment_notification()
{
    // Handle callback from M-Pesa C2B
    $callback_data = json_decode(file_get_contents('php://input'), true);

    if (!$callback_data) {
        http_response_code(400);
        die('Invalid callback data');
    }

    // Log callback
    _log('M-Pesa C2B Callback: ' . json_encode($callback_data), 'Payment', 0);

    // C2B callback structure
    if (isset($callback_data['TransID']) && isset($callback_data['BillRefNumber'])) {
        $trans_id = $callback_data['TransID'];
        $bill_ref_number = $callback_data['BillRefNumber'];
        $trans_amount = $callback_data['TransAmount'];
        $msisdn = $callback_data['MSISDN'];
        $trans_time = $callback_data['TransTime'];

        // Find transaction by BillRefNumber
        $trx = ORM::for_table('tbl_payment_gateway')
            ->where('gateway_trx_id', $bill_ref_number)
            ->where('status', 1)
            ->find_one();

        if ($trx) {
            $user = ORM::for_table('tbl_customers')->find_one($trx['username']);

            if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'], 'M-Pesa C2B')) {
                _log('Failed to activate package for M-Pesa C2B payment: ' . $trans_id, 'Payment', 0);
            }

            $trx->pg_paid_response = json_encode($callback_data);
            $trx->payment_method = 'MPESA';
            $trx->payment_channel = 'mpesac2b';
            $trx->paid_date = date('Y-m-d H:i:s');
            $trx->status = 2;
            $trx->save();

            _log('M-Pesa C2B payment successful: ' . $trans_id, 'Payment', $user['id']);
        } else {
            _log('M-Pesa C2B transaction not found: ' . $bill_ref_number, 'Payment', 0);
        }
    }

    // Respond to M-Pesa
    header('Content-Type: application/json');
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    exit();
}

function mpesac2b_get_status($trx, $user)
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

function mpesac2b_get_access_token()
{
    global $config;

    $url = mpesac2b_get_base_url() . '/oauth/v1/generate?grant_type=client_credentials';

    $credentials = base64_encode($config['mpesa_c2b_consumer_key'] . ':' . $config['mpesa_c2b_consumer_secret']);

    $headers = [
        'Authorization: Basic ' . $credentials
    ];

    $result = json_decode(Http::getData($url, $headers), true);

    return $result['access_token'] ?? false;
}

function mpesac2b_stk_push($data, $access_token)
{
    $url = mpesac2b_get_base_url() . '/mpesa/stkpush/v1/processrequest';

    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ];

    return json_decode(Http::postJsonData($url, $data, $headers), true);
}

function mpesac2b_get_base_url()
{
    global $config;
    if (($config['mpesa_c2b_environment'] ?? 'sandbox') == 'live') {
        return 'https://api.safaricom.co.ke';
    } else {
        return 'https://sandbox.safaricom.co.ke';
    }
}