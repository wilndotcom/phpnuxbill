<?php
/**
 * PHP Mikrotik Billing - Hotspot Payment Status Checker
 * Checks M-Pesa payment status via AJAX
 */

// Security check - prevent direct access
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden', TRUE, 403);
    header('location: ../');
    die();
}

// Include the main initialization
require_once 'init.php';

// Set JSON response header
header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$checkout_id = $input['checkout_id'] ?? '';

if (empty($checkout_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing checkout ID']);
    exit;
}

// Check if we have transaction data
if (!isset($_SESSION['hotspot_transaction'])) {
    echo json_encode(['status' => 'error', 'message' => 'No active transaction']);
    exit;
}

$transaction = $_SESSION['hotspot_transaction'];

// Check database for payment confirmation
$payment = ORM::for_table('tbl_payment_gateway')
    ->where('id', $transaction['payment_id'])
    ->where('status', '2') // 2 = success in PHPNuxBill
    ->find_one();

if ($payment) {
    // Payment successful - activate hotspot access
    try {
        // Get router details
        $router = ORM::for_table('tbl_routers')->find_one($transaction['router']);
        if ($router) {
            $client = Mikrotik::getClient($router['ip_address'], $router['username'], $router['password']);

            if ($client) {
                // Create customer account
                $customer_data = $transaction['customer'];
                $customer_data['router_id'] = $transaction['router'];

                // Add customer to database
                $customer = ORM::for_table('tbl_customers')->create();
                $customer->username = $customer_data['username'];
                $customer->password = $customer_data['password'];
                $customer->fullname = $customer_data['fullname'];
                $customer->email = $customer_data['email'];
                $customer->phone = $customer_data['phone'];
                $customer->pppoe_password = $customer_data['pppoe_password'];
                $customer->service_type = $customer_data['service_type'];
                $customer->hotspot_plan = $customer_data['hotspot_plan'];
                $customer->router_id = $customer_data['router_id'];
                $customer->save();

                // Get plan details
                $plan = ORM::for_table('tbl_plans')->find_one($transaction['plan_id']);

                // Add hotspot user to Mikrotik
                Mikrotik::addHotspotUser($client, $plan, $customer_data);

                // Log successful activation
                Log::put('Hotspot Activation', 'Hotspot access activated for ' . $customer_data['phone'], $customer->id, $customer_data['username']);

                // Set success data for the success page
                $_SESSION['hotspot_success'] = [
                    'plan_name' => $transaction['plan_name'],
                    'amount' => $transaction['amount'],
                    'phone' => $transaction['phone']
                ];

                // Clear transaction data
                unset($_SESSION['hotspot_transaction']);
                unset($_SESSION['hotspot_checkout_id']);

                echo json_encode(['status' => 'success']);
                exit;
            }
        }
    } catch (Exception $e) {
        Log::put('Hotspot Error', 'Failed to activate hotspot access: ' . $e->getMessage());
    }
}

// Check if payment failed
$failed_payment = ORM::for_table('tbl_payment_gateway')
    ->where('id', $transaction['payment_id'])
    ->where('status', '3') // 3 = failed in PHPNuxBill
    ->find_one();

if ($failed_payment) {
    // Clear session data
    unset($_SESSION['hotspot_transaction']);
    unset($_SESSION['hotspot_checkout_id']);

    echo json_encode(['status' => 'failed']);
    exit;
}

// Still processing
echo json_encode(['status' => 'processing']);
?>