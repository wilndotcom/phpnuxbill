<?php
/**
 * PHP Mikrotik Billing - Hotspot M-Pesa Callback Handler
 * Handles M-Pesa payment confirmations for hotspot payments
 */

// Security check - prevent direct access
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden', TRUE, 403);
    header('location: ../');
    die();
}

// Include the main initialization
require_once 'init.php';

// Get callback data
$callback_data = json_decode(file_get_contents('php://input'), true);

// Log callback for debugging
Log::put('M-Pesa Callback', 'Hotspot payment callback received: ' . json_encode($callback_data));

// Check if callback is valid
if (!$callback_data) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback data']);
    exit;
}

// Extract payment details
$stk_callback = $callback_data['Body']['stkCallback'] ?? null;
if (!$stk_callback) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid STK callback']);
    exit;
}

$checkout_request_id = $stk_callback['CheckoutRequestID'];
$result_code = $stk_callback['ResultCode'];
$result_desc = $stk_callback['ResultDesc'];

// Find the payment record
$payment = ORM::for_table('tbl_payment_gateway')
    ->where('gateway_trx_id', $checkout_request_id)
    ->find_one();

if (!$payment) {
    Log::put('M-Pesa Error', 'Payment record not found for checkout ID: ' . $checkout_request_id);
    http_response_code(404);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Payment record not found']);
    exit;
}

try {
    if ($result_code == 0) {
        // Payment successful
        $callback_metadata = $stk_callback['CallbackMetadata']['Item'] ?? [];

        $amount = 0;
        $mpesa_receipt = '';
        $transaction_date = '';
        $phone = '';

        foreach ($callback_metadata as $item) {
            switch ($item['Name']) {
                case 'Amount':
                    $amount = $item['Value'];
                    break;
                case 'MpesaReceiptNumber':
                    $mpesa_receipt = $item['Value'];
                    break;
                case 'TransactionDate':
                    $transaction_date = $item['Value'];
                    break;
                case 'PhoneNumber':
                    $phone = $item['Value'];
                    break;
            }
        }

        // Update payment record
        $payment->status = 2; // Success
        $payment->paid_date = date('Y-m-d H:i:s');
        $payment->gateway_trx_id = $mpesa_receipt;
        $payment->note = 'M-Pesa payment successful - Receipt: ' . $mpesa_receipt;
        $payment->save();

        // Log successful payment
        Log::put('M-Pesa Success', 'Hotspot payment successful: ' . $mpesa_receipt . ' - Amount: ' . $amount, $payment->customer_id);

        // Send success response
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Payment processed successfully']);

    } else {
        // Payment failed
        $payment->status = 3; // Failed
        $payment->note = 'M-Pesa payment failed: ' . $result_desc;
        $payment->save();

        // Log failed payment
        Log::put('M-Pesa Failed', 'Hotspot payment failed: ' . $result_desc . ' - Checkout ID: ' . $checkout_request_id);

        // Send error response
        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Payment failed: ' . $result_desc]);
    }

} catch (Exception $e) {
    Log::put('M-Pesa Error', 'Error processing hotspot payment callback: ' . $e->getMessage());

    // Update payment status to failed
    $payment->status = 3;
    $payment->note = 'Processing error: ' . $e->getMessage();
    $payment->save();

    http_response_code(500);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Internal processing error']);
}

exit;
?>