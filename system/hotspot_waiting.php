<?php
/**
 * PHP Mikrotik Billing - Hotspot Payment Waiting Page
 * Shows waiting screen while processing M-Pesa payment
 */

// Security check - prevent direct access
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden', TRUE, 403);
    header('location: ../');
    die();
}

// Include the main initialization
require_once 'init.php';

// Check if we have transaction data
if (!isset($_SESSION['hotspot_transaction'])) {
    header('Location: hotspot_login.php');
    exit;
}

$transaction = $_SESSION['hotspot_transaction'];
$plan_name = $transaction['plan_name'];
$amount = $transaction['amount'];
$phone = $transaction['phone'];

// Get hotspot settings for styling
$hotspot_settings = [
    'hotspot_enabled' => $config['hotspot_enabled'] ?? 'yes',
    'hotspot_title' => $config['hotspot_title'] ?? 'Welcome to Our Network',
    'hotspot_subtitle' => $config['hotspot_subtitle'] ?? 'Please login to access the internet',
    'hotspot_primary_color' => $config['hotspot_primary_color'] ?? '#007bff',
    'hotspot_secondary_color' => $config['hotspot_secondary_color'] ?? '#6c757d',
    'hotspot_background_color' => $config['hotspot_background_color'] ?? '#f8f9fa',
    'hotspot_text_color' => $config['hotspot_text_color'] ?? '#212529',
    'hotspot_button_color' => $config['hotspot_button_color'] ?? '#007bff',
    'hotspot_button_text_color' => $config['hotspot_button_text_color'] ?? '#ffffff',
    'hotspot_font_family' => $config['hotspot_font_family'] ?? 'Arial, sans-serif',
    'hotspot_template' => $config['hotspot_template'] ?? 'default',
    'hotspot_show_logo' => $config['hotspot_show_logo'] ?? 'yes',
    'hotspot_show_terms' => $config['hotspot_show_terms'] ?? 'yes',
    'hotspot_terms_text' => $config['hotspot_terms_text'] ?? 'By connecting to this network, you agree to our terms of service.',
    'hotspot_redirect_url' => $config['hotspot_redirect_url'] ?? '',
    'hotspot_custom_css' => $config['hotspot_custom_css'] ?? '',
    'hotspot_custom_js' => $config['hotspot_custom_js'] ?? ''
];

// Generate CSS
$custom_css = "
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: {$hotspot_settings['hotspot_font_family']};
    background: linear-gradient(135deg, {$hotspot_settings['hotspot_background_color']} 0%, #e1e5e9 100%);
    color: {$hotspot_settings['hotspot_text_color']};
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.waiting-container {
    background: rgba(255, 255, 255, 0.98);
    border-radius: 12px;
    padding: 40px 35px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    max-width: 500px;
    width: 100%;
    border: 1px solid rgba(255, 255, 255, 0.2);
    text-align: center;
}

.waiting-header {
    margin-bottom: 30px;
}

.waiting-title {
    color: {$hotspot_settings['hotspot_primary_color']};
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 8px;
}

.waiting-subtitle {
    color: {$hotspot_settings['hotspot_secondary_color']};
    font-size: 16px;
    line-height: 1.5;
}

.payment-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #e1e5e9;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}

.detail-label {
    color: {$hotspot_settings['hotspot_secondary_color']};
    font-weight: 500;
}

.detail-value {
    color: {$hotspot_settings['hotspot_text_color']};
    font-weight: 600;
}

.spinner-container {
    margin: 30px 0;
}

.spinner {
    width: 60px;
    height: 60px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid {$hotspot_settings['hotspot_primary_color']};
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.waiting-text {
    color: {$hotspot_settings['hotspot_secondary_color']};
    font-size: 16px;
    margin-bottom: 10px;
}

.waiting-instructions {
    background: #e8f5e8;
    color: #2e7d32;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #c8e6c9;
    font-size: 14px;
    line-height: 1.5;
}

.retry-btn {
    margin-top: 20px;
    padding: 12px 24px;
    background: {$hotspot_settings['hotspot_button_color']};
    color: {$hotspot_settings['hotspot_button_text_color']};
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.retry-btn:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

@media (max-width: 480px) {
    .waiting-container {
        margin: 10px;
        padding: 30px 25px;
    }

    .waiting-title {
        font-size: 20px;
    }
}
{$hotspot_settings['hotspot_custom_css']}
</style>
";

// Generate the waiting page HTML
$html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Processing Payment - {$hotspot_settings['hotspot_title']}</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    {$custom_css}
</head>
<body>
    <div class='waiting-container'>
        <div class='waiting-header'>
            <h1 class='waiting-title'><i class='fas fa-mobile-alt'></i> Processing Payment</h1>
            <p class='waiting-subtitle'>Please complete the payment on your phone</p>
        </div>

        <div class='payment-details'>
            <div class='detail-row'>
                <span class='detail-label'>Plan:</span>
                <span class='detail-value'>{$plan_name}</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Amount:</span>
                <span class='detail-value'>{$amount} {$config['currency_code']}</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Phone:</span>
                <span class='detail-value'>{$phone}</span>
            </div>
        </div>

        <div class='spinner-container'>
            <div class='spinner'></div>
            <div class='waiting-text'>Waiting for payment confirmation...</div>
        </div>

        <div class='waiting-instructions'>
            <strong>Instructions:</strong><br>
            1. Check your phone for the M-Pesa prompt<br>
            2. Enter your M-Pesa PIN to complete payment<br>
            3. Wait for confirmation (usually takes 10-30 seconds)
        </div>

        <a href='hotspot_login.php' class='retry-btn'>
            <i class='fas fa-arrow-left'></i> Try Different Plan
        </a>
    </div>

    <script>
// Auto-refresh to check payment status
let checkCount = 0;
const maxChecks = 60; // Check for 5 minutes (60 * 5 seconds)

function checkPaymentStatus() {
    checkCount++;

    fetch('hotspot_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            checkout_id: '" . ($_SESSION['hotspot_checkout_id'] ?? '') . "'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Payment successful - redirect to success page
            window.location.href = 'hotspot_success.php';
        } else if (data.status === 'failed') {
            // Payment failed - redirect back to login
            window.location.href = 'hotspot_login.php?error=payment_failed';
        } else if (checkCount >= maxChecks) {
            // Timeout - redirect back to login
            window.location.href = 'hotspot_login.php?error=payment_timeout';
        }
    })
    .catch(error => {
        console.log('Status check failed:', error);
    });
}

// Check payment status every 5 seconds
setInterval(checkPaymentStatus, 5000);

// Initial check
setTimeout(checkPaymentStatus, 2000);

// Custom JavaScript from settings
{$hotspot_settings['hotspot_custom_js']}
    </script>
</body>
</html>";

// Output the HTML
header('Content-Type: text/html; charset=utf-8');
echo $html;
?>