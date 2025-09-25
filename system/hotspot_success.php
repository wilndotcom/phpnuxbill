<?php
/**
 * PHP Mikrotik Billing - Hotspot Payment Success Page
 * Shows success message when payment is completed and internet is activated
 */

// Security check - prevent direct access
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden', TRUE, 403);
    header('location: ../');
    die();
}

// Include the main initialization
require_once 'init.php';

// Check if we have success data (this should be set by the status checker)
if (!isset($_SESSION['hotspot_success'])) {
    header('Location: hotspot_login.php');
    exit;
}

$success_data = $_SESSION['hotspot_success'];
unset($_SESSION['hotspot_success']); // Clear it

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

.success-container {
    background: rgba(255, 255, 255, 0.98);
    border-radius: 12px;
    padding: 40px 35px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    max-width: 500px;
    width: 100%;
    border: 1px solid rgba(255, 255, 255, 0.2);
    text-align: center;
}

.success-header {
    margin-bottom: 30px;
}

.success-icon {
    font-size: 64px;
    color: #28a745;
    margin-bottom: 20px;
}

.success-title {
    color: {$hotspot_settings['hotspot_primary_color']};
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 8px;
}

.success-subtitle {
    color: {$hotspot_settings['hotspot_secondary_color']};
    font-size: 16px;
    line-height: 1.5;
}

.success-details {
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

.success-message {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #c3e6cb;
    font-size: 14px;
    line-height: 1.5;
    margin: 20px 0;
}

.connect-btn {
    margin-top: 20px;
    padding: 14px 28px;
    background: {$hotspot_settings['hotspot_button_color']};
    color: {$hotspot_settings['hotspot_button_text_color']};
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,123,255,0.3);
}

.connect-btn:hover {
    background: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,123,255,0.4);
}

.footer-note {
    margin-top: 20px;
    font-size: 12px;
    color: {$hotspot_settings['hotspot_secondary_color']};
    line-height: 1.4;
}

@media (max-width: 480px) {
    .success-container {
        margin: 10px;
        padding: 30px 25px;
    }

    .success-title {
        font-size: 24px;
    }

    .success-icon {
        font-size: 48px;
    }
}
{$hotspot_settings['hotspot_custom_css']}
</style>
";

// Generate the success page HTML
$plan_name = $success_data['plan_name'] ?? 'Internet Plan';
$amount = $success_data['amount'] ?? '0';
$phone = $success_data['phone'] ?? '';

$html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Payment Successful - {$hotspot_settings['hotspot_title']}</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    {$custom_css}
</head>
<body>
    <div class='success-container'>
        <div class='success-header'>
            <div class='success-icon'>
                <i class='fas fa-check-circle'></i>
            </div>
            <h1 class='success-title'>Payment Successful!</h1>
            <p class='success-subtitle'>Your internet access has been activated</p>
        </div>

        <div class='success-details'>
            <div class='detail-row'>
                <span class='detail-label'>Plan:</span>
                <span class='detail-value'>{$plan_name}</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Amount Paid:</span>
                <span class='detail-value'>{$amount} {$config['currency_code']}</span>
            </div>
            <div class='detail-row'>
                <span class='detail-label'>Phone:</span>
                <span class='detail-value'>{$phone}</span>
            </div>
        </div>

        <div class='success-message'>
            <strong>🎉 Welcome to our network!</strong><br>
            Your internet access is now active. You can start browsing immediately.
        </div>

        <button onclick='connectToInternet()' class='connect-btn'>
            <i class='fas fa-wifi'></i> Start Browsing
        </button>

        <div class='footer-note'>
            <strong>Note:</strong> If you experience any connection issues, please contact our support team.<br>
            <em>Powered by PHPNuxBill Hotspot System</em>
        </div>
    </div>

    <script>
function connectToInternet() {
    // Try to redirect to a common website to test connection
    window.location.href = 'https://www.google.com';
}

// Auto-redirect after 10 seconds
setTimeout(function() {
    connectToInternet();
}, 10000);

// Custom JavaScript from settings
{$hotspot_settings['hotspot_custom_js']}
    </script>
</body>
</html>";

// Output the HTML
header('Content-Type: text/html; charset=utf-8');
echo $html;
?>