<?php
/**
 * PHP Mikrotik Billing - Hotspot Login Page Generator
 * This script generates the hotspot login page based on settings configured in the Hotspot Settings plugin
 */

// Security check - prevent direct access
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden', TRUE, 403);
    header('location: ../');
    die();
}

// Include the main initialization
require_once 'init.php';

// Helper function to adjust color brightness
function adjustBrightness($hex, $steps) {
    // Normalize into a six character long hex string
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }

    // Split into three parts: R, G and B
    $color_parts = str_split($hex, 2);
    $return = '#';

    foreach ($color_parts as $color) {
        $color = hexdec($color); // Convert to decimal
        $color = max(0, min(255, $color + $steps)); // Adjust color
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
    }

    return $return;
}

// Get hotspot settings from database
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

// Get uploaded files
$UPLOAD_URL_PATH = str_replace($_SERVER['DOCUMENT_ROOT'], '', $UPLOAD_PATH);
$logo_url = '';
$wallpaper_url = '';

if (!empty($config['hotspot_logo']) && file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . $config['hotspot_logo'])) {
    $logo_url = APP_URL . '/' . $UPLOAD_URL_PATH . '/' . $config['hotspot_logo'];
}

if (!empty($config['hotspot_wallpaper']) && file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . $config['hotspot_wallpaper'])) {
    $wallpaper_url = APP_URL . '/' . $UPLOAD_URL_PATH . '/' . $config['hotspot_wallpaper'];
}

// Get template style
$template = $hotspot_settings['hotspot_template'] ?? 'pricing';

// Template-specific CSS variations
$template_css = '';
switch ($template) {
    case 'modern':
        $template_css = '
        .login-container {
            background: linear-gradient(145deg, rgba(255,255,255,0.95), rgba(255,255,255,0.85));
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }
        .title { font-weight: 300; letter-spacing: 1px; }
        .login-btn { border-radius: 25px; font-weight: 300; }
        ';
        break;
    case 'minimal':
        $template_css = '
        .login-container {
            background: rgba(255,255,255,0.98);
            border-radius: 0;
            box-shadow: none;
            border: 1px solid #e1e5e9;
        }
        .header-section { margin-bottom: 20px; }
        .title { font-size: 22px; font-weight: 400; }
        .login-btn { border-radius: 0; }
        ';
        break;
    case 'corporate':
        $template_css = '
        .login-container {
            background: #ffffff;
            border: 2px solid #007bff;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,123,255,0.2);
        }
        .title { color: #007bff; font-weight: 600; }
        .login-btn { background: linear-gradient(45deg, #007bff, #0056b3); }
        .company-info { font-weight: 600; color: #495057; }
        ';
        break;
    case 'pricing':
        $template_css = '
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: ' . (isset($hotspot_settings['hotspot_primary_color']) ? $hotspot_settings['hotspot_primary_color'] : '#7b1e3a') . ';
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 5;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }
        .already-paid {
            font-size: 14px;
            opacity: 0.95;
        }
        body { padding-top: 70px; }
        .login-container { box-shadow: none; background: transparent; max-width: 1100px; }
        .header-section { margin-bottom: 10px; }
        .title { font-size: 34px; text-align: center; }
        .plans-grid { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .plan-card {
            background: linear-gradient(180deg, #ffffff, #ffe6e6);
            border: 1px solid #f1d0d0;
            border-radius: 16px;
            overflow: hidden;
        }
        .plan-pill {
            display: inline-block;
            background: #b71c3a;
            color: #fff;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px;
            margin-bottom: 14px;
            text-transform: uppercase;
        }
        .plan-price { font-size: 36px; font-weight: 800; color: #b71c3a; }
        .plan-price .currency { font-size: 16px; font-weight: 700; text-transform: lowercase; margin-right: 6px; }
        .plan-desc { color: #6c757d; font-size: 14px; margin-top: 8px; }
        .plan-footer { background: #d33; padding: 16px; text-align: center; }
        .plan-select-btn, .plan-submit-btn { background: #d33; color: #fff; border-radius: 8px; border: 0; }
        .plan-select-btn:hover, .plan-submit-btn:hover { background: #b10; }
        .plan-payment-form { display: none; background: #fff; padding: 16px; border-top: 1px solid #f1d0d0; }
        ';
        break;
    default: // default
        $template_css = '';
}

// Generate CSS based on settings - Professional RADIUS-style design
$custom_css = "
<style>
* {
margin: 0;
padding: 0;
box-sizing: border-box;
}

body {
font-family: {$hotspot_settings['hotspot_font_family']};
background: linear-gradient(135deg, {$hotspot_settings['hotspot_background_color']} 0%, " . adjustBrightness($hotspot_settings['hotspot_background_color'], -20) . " 100%);
color: {$hotspot_settings['hotspot_text_color']};
min-height: 100vh;
display: flex;
align-items: center;
justify-content: center;
padding: 20px;
}

" . (!empty($wallpaper_url) ? "
body::before {
content: '';
position: fixed;
top: 0;
left: 0;
width: 100%;
height: 100%;
background-image: url('{$wallpaper_url}');
background-size: cover;
background-position: center;
background-repeat: no-repeat;
opacity: 0.15;
z-index: -1;
}
" : "") . "

.login-container {
background: rgba(255, 255, 255, 0.98);
border-radius: 12px;
padding: 40px 35px;
box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
max-width: 420px;
width: 100%;
border: 1px solid rgba(255, 255, 255, 0.2);
backdrop-filter: blur(20px);
}

.header-section {
text-align: center;
margin-bottom: 35px;
}

.logo {
max-width: 120px;
margin-bottom: 20px;
filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.title {
color: {$hotspot_settings['hotspot_primary_color']};
font-size: 24px;
font-weight: 600;
margin-bottom: 8px;
letter-spacing: -0.5px;
}

.subtitle {
color: {$hotspot_settings['hotspot_secondary_color']};
font-size: 14px;
line-height: 1.5;
margin-bottom: 0;
}

.form-section {
margin-bottom: 25px;
}

.input-group {
position: relative;
margin-bottom: 20px;
}

.input-group input {
width: 100%;
padding: 16px 20px;
border: 2px solid #e1e5e9;
border-radius: 8px;
font-size: 16px;
background: #ffffff;
transition: all 0.3s ease;
outline: none;
}

.input-group input:focus {
border-color: {$hotspot_settings['hotspot_primary_color']};
box-shadow: 0 0 0 3px " . adjustBrightness($hotspot_settings['hotspot_primary_color'], 40) . "33;
}

.input-group label {
position: absolute;
top: 16px;
left: 20px;
color: {$hotspot_settings['hotspot_secondary_color']};
font-size: 14px;
pointer-events: none;
transition: all 0.3s ease;
background: transparent;
padding: 0 4px;
}

.input-group input:focus + label,
.input-group input:not(:placeholder-shown) + label {
top: -8px;
font-size: 12px;
color: {$hotspot_settings['hotspot_primary_color']};
background: #ffffff;
}

.input-icon {
position: absolute;
right: 16px;
top: 50%;
transform: translateY(-50%);
color: {$hotspot_settings['hotspot_secondary_color']};
font-size: 18px;
}

.login-btn {
width: 100%;
padding: 16px;
background: {$hotspot_settings['hotspot_button_color']};
color: {$hotspot_settings['hotspot_button_text_color']};
border: none;
border-radius: 8px;
font-size: 16px;
font-weight: 600;
cursor: pointer;
transition: all 0.3s ease;
text-transform: uppercase;
letter-spacing: 0.5px;
box-shadow: 0 4px 15px " . adjustBrightness($hotspot_settings['hotspot_button_color'], -30) . "40;
}

.login-btn:hover {
background: " . adjustBrightness($hotspot_settings['hotspot_button_color'], -10) . ";
transform: translateY(-2px);
box-shadow: 0 6px 20px " . adjustBrightness($hotspot_settings['hotspot_button_color'], -30) . "60;
}

.login-btn:active {
transform: translateY(0);
}

.footer-section {
text-align: center;
padding-top: 20px;
border-top: 1px solid #e1e5e9;
}

.terms {
font-size: 12px;
color: {$hotspot_settings['hotspot_secondary_color']};
line-height: 1.5;
margin: 0;
}

.terms a {
color: {$hotspot_settings['hotspot_primary_color']};
text-decoration: none;
}

.terms a:hover {
text-decoration: underline;
}

.status-message {
padding: 12px 16px;
border-radius: 6px;
margin-bottom: 20px;
font-size: 14px;
font-weight: 500;
}

.error-message {
background: #fef2f2;
color: #dc2626;
border: 1px solid #fecaca;
}

.success-message {
background: #f0fdf4;
color: #16a34a;
border: 1px solid #bbf7d0;
}

.info-message {
background: #eff6ff;
color: #2563eb;
border: 1px solid #bfdbfe;
}

.company-info {
margin-top: 20px;
padding-top: 15px;
border-top: 1px solid #e1e5e9;
font-size: 11px;
color: {$hotspot_settings['hotspot_secondary_color']};
}

@media (max-width: 480px) {
.login-container {
    margin: 10px;
    padding: 30px 25px;
}

.title {
    font-size: 20px;
}

.subtitle {
    font-size: 13px;
}

.input-group input {
    padding: 14px 18px;
    font-size: 16px;
}

.login-btn {
    padding: 14px;
    font-size: 15px;
}
}

@media (max-width: 360px) {
.login-container {
    padding: 25px 20px;
}

.title {
    font-size: 18px;
}
}
/* Plan Selection Styles */
.plans-section {
    margin-bottom: 25px;
}

.plans-title {
    color: {$hotspot_settings['hotspot_primary_color']};
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 8px;
    text-align: center;
}

.plans-subtitle {
    color: {$hotspot_settings['hotspot_secondary_color']};
    font-size: 14px;
    text-align: center;
    margin-bottom: 25px;
    line-height: 1.4;
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.plan-card {
    background: #ffffff;
    border: 2px solid #e1e5e9;
    border-radius: 16px;
    padding: 25px;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    position: relative;
    overflow: hidden;
}

.plan-card:hover {
    border-color: {$hotspot_settings['hotspot_primary_color']};
    transform: translateY(-8px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.2);
}

.plan-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: linear-gradient(45deg, {$hotspot_settings['hotspot_primary_color']}, " . adjustBrightness($hotspot_settings['hotspot_primary_color'], -20) . ");
    color: white;
    padding: 5px 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-radius: 0 12px 0 20px;
    display: none; /* Hide by default, can be shown for specific plans */
}

.plan-header {
    margin-bottom: 20px;
}

.plan-icon {
    font-size: 32px;
    color: {$hotspot_settings['hotspot_primary_color']};
    margin-bottom: 10px;
}

.plan-name {
    color: {$hotspot_settings['hotspot_primary_color']};
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
}

.plan-price {
    color: {$hotspot_settings['hotspot_secondary_color']};
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.currency {
    font-size: 16px;
    font-weight: 500;
}

.plan-features {
    margin-bottom: 25px;
}

.plan-feature {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: {$hotspot_settings['hotspot_secondary_color']};
    font-size: 13px;
    margin-bottom: 8px;
    padding: 4px 0;
}

.plan-feature i {
    color: {$hotspot_settings['hotspot_primary_color']};
    width: 16px;
}

.plan-select-btn {
    display: inline-block;
    width: 100%;
    padding: 12px 20px;
    background: {$hotspot_settings['hotspot_button_color']};
    color: {$hotspot_settings['hotspot_button_text_color']};
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px " . adjustBrightness($hotspot_settings['hotspot_button_color'], -30) . "40;
}

.plan-select-btn:hover {
    background: " . adjustBrightness($hotspot_settings['hotspot_button_color'], -10) . ";
    transform: translateY(-2px);
    box-shadow: 0 6px 20px " . adjustBrightness($hotspot_settings['hotspot_button_color'], -30) . "60;
    color: {$hotspot_settings['hotspot_button_text_color']};
}

/* Phone Input and Payment Form Styles */
.phone-input-group {
    position: relative;
    margin-bottom: 20px;
}

.phone-input {
    width: 100%;
    padding: 14px 45px 14px 16px;
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    font-size: 16px;
    background: #ffffff;
    transition: all 0.3s ease;
    outline: none;
    box-sizing: border-box;
    font-weight: 500;
}

.phone-input:focus {
    border-color: {$hotspot_settings['hotspot_primary_color']};
    box-shadow: 0 0 0 3px " . adjustBrightness($hotspot_settings['hotspot_primary_color'], 40) . "40;
    transform: translateY(-2px);
}

.phone-input::placeholder {
    color: {$hotspot_settings['hotspot_secondary_color']};
    opacity: 0.7;
}

.phone-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: {$hotspot_settings['hotspot_primary_color']};
    font-size: 18px;
}

.plan-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.plan-submit-btn,
.plan-cancel-btn {
    flex: 1;
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    overflow: hidden;
}

.plan-submit-btn {
    background: linear-gradient(135deg, {$hotspot_settings['hotspot_button_color']}, " . adjustBrightness($hotspot_settings['hotspot_button_color'], -15) . ");
    color: {$hotspot_settings['hotspot_button_text_color']};
    box-shadow: 0 4px 15px " . adjustBrightness($hotspot_settings['hotspot_button_color'], -30) . "50;
}

.plan-submit-btn:hover {
    background: linear-gradient(135deg, " . adjustBrightness($hotspot_settings['hotspot_button_color'], -10) . ", " . adjustBrightness($hotspot_settings['hotspot_button_color'], -25) . ");
    transform: translateY(-3px);
    box-shadow: 0 8px 25px " . adjustBrightness($hotspot_settings['hotspot_button_color'], -30) . "70;
}

.plan-submit-btn:active {
    transform: translateY(-1px);
}

.plan-cancel-btn {
    background: #f8f9fa;
    color: {$hotspot_settings['hotspot_secondary_color']};
    border: 2px solid #e1e5e9;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.plan-cancel-btn:hover {
    background: #e9ecef;
    border-color: {$hotspot_settings['hotspot_secondary_color']};
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.plan-payment-form {
    margin-top: 15px;
}

.no-plans {
    text-align: center;
    color: {$hotspot_settings['hotspot_secondary_color']};
    font-size: 16px;
    padding: 40px 20px;
    background: rgba(255,255,255,0.9);
    border-radius: 8px;
    border: 2px solid #e1e5e9;
}

{$template_css}
{$hotspot_settings['hotspot_custom_css']}
</style>
";

// Generate the complete HTML page - Professional RADIUS-style design
    $html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$hotspot_settings['hotspot_title']}</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    {$custom_css}
</head>
<body>
    " . ($template === 'pricing' ? "<div class='topbar'>
        <div class='brand'>" . (!empty($logo_url) && $hotspot_settings['hotspot_show_logo'] == 'yes' ? "<img src='{$logo_url}' alt='Logo' style='height:28px'>" : "") . "<span>{$hotspot_settings['hotspot_title']}</span></div>
        <div class='already-paid'>Already Paid? <a href='\$(link-login-only)' style='color:#fff;text-decoration:underline'>Click Here.</a></div>
    </div>" : "") . "
    <div class='login-container'>
        <div class='header-section'>
            " . (!empty($logo_url) && $hotspot_settings['hotspot_show_logo'] == 'yes' ? "<img src='{$logo_url}' alt='Logo' class='logo'>" : "") . "
            <h1 class='title'>{$hotspot_settings['hotspot_title']}</h1>
            <p class='subtitle'>{$hotspot_settings['hotspot_subtitle']}</p>
        </div>

        <!-- Display any error messages -->
        {if isset(\$error) && !empty(\$error)}
        <div class='status-message error-message'>
            <i class='fas fa-exclamation-triangle'></i> {\$error}
        </div>
        {/if}

        <!-- Display any success messages -->
        {if isset(\$success) && !empty(\$success)}
        <div class='status-message success-message'>
            <i class='fas fa-check-circle'></i> {\$success}
        </div>
        {/if}

        <div class='plans-section'>
            <h2 class='plans-title'><i class='fas fa-wifi'></i> Select Your Internet Plan</h2>
            <p class='plans-subtitle'>Choose a plan, enter your M-Pesa phone number, and get connected instantly</p>
            <div class='plans-grid'>
";
    // Get available hotspot plans
    $plans = ORM::for_table('tbl_plans')->where('type', 'Hotspot')->where('enabled', '1')->order_by_asc('price')->find_many();

    if (count($plans) > 0) {
        foreach ($plans as $plan) {
            $plan_name = htmlspecialchars($plan['name_plan']);
            $plan_price = $template === 'pricing' ? number_format($plan['price'], 0) : number_format($plan['price'], 2);
            $plan_validity = $plan['validity'];
            $plan_validity_unit = $plan['validity_unit'];

            // Convert validity to readable format
            $validity_text = $plan_validity . ' ' . ($plan_validity_unit == 'Hrs' ? 'Hour' . ($plan_validity > 1 ? 's' : '') : $plan_validity_unit);

            $html .= "
                <div class='plan-card'>
                    <div class='plan-badge'>Most Popular</div>
                    <div class='plan-header'>
                        " . ($template === 'pricing' ? "<div class='plan-pill'>{$plan_name}</div>" : "<div class='plan-icon'><i class='fas fa-wifi'></i></div><h3 class='plan-name'>{$plan_name}</h3>") . "
                        <div class='plan-price'>" . ("<span class='currency'>Ksh</span> {$plan_price}") . "</div>
                        " . ($template === 'pricing' ? "<div class='plan-desc'>{$validity_text}</div>" : "") . "
                    </div>
                    " . ($template === 'pricing' ? "" : "<div class='plan-features'>
                        <div class='plan-feature'>
                            <i class='fas fa-clock'></i>
                            <span>{$validity_text}</span>
                        </div>
                        <div class='plan-feature'>
                            <i class='fas fa-tachometer-alt'></i>
                            <span>" . ($plan['rate_limit'] ?? 'Unlimited Speed') . "</span>
                        </div>
                        <div class='plan-feature'>
                            <i class='fas fa-shield-alt'></i>
                            <span>Secure Connection</span>
                        </div>
                    </div>") . "
                    <div class='plan-footer'>
                        <a href='javascript:void(0)' class='plan-select-btn'>Click Here To Connect</a>
                    </div>
                    <form class='plan-payment-form' method='post' action='" . APP_URL . "/system/hotspot_payment.php'>
                        <input type='hidden' name='plan_id' value='{$plan['id']}'>
                        <input type='hidden' name='mac' value='\$(mac-esc)'>
                        <input type='hidden' name='ip' value='\$(ip)'>
                        <input type='hidden' name='router' value='1'>
                        <div class='phone-input-group'>
                            <input type='tel' name='phone' placeholder='0712345678' required pattern='[0-9]{10,12}' class='phone-input'>
                            <i class='fas fa-mobile-alt phone-icon'></i>
                        </div>
                        <div class='plan-buttons'>
                            <button type='submit' class='plan-submit-btn'>
                                <i class='fas fa-mobile-alt'></i> Pay with M-Pesa
                            </button>
                            <button type='button' class='plan-cancel-btn' onclick='cancelPlan(this)'>
                                <i class='fas fa-arrow-left'></i> Change Plan
                            </button>
                        </div>
                    </form>
                </div>";
        }
    } else {
        $html .= "<div class='no-plans'><i class='fas fa-exclamation-triangle'></i> No hotspot plans available at the moment.</div>";
    }

    $html .= "
            </div>
        </div>

        <div class='footer-section'>
            " . ($hotspot_settings['hotspot_show_terms'] == 'yes' ? "<p class='terms'>{$hotspot_settings['hotspot_terms_text']}</p>" : "") . "

            <div class='company-info'>
                <strong>Internet Service Provider</strong><br>
                Powered by PHPNuxBill Hotspot System
            </div>
        </div>
    </div>

    <script>
// Mikrotik hotspot variables will be replaced by Mikrotik
var link_login = '\$(link-login-only)';
var link_orig = '\$(link-orig)';
var error = '\$(error)';
var success = '\$(error)';

// Handle plan payment forms
document.querySelectorAll('.plan-payment-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var submitBtn = this.querySelector('.plan-submit-btn');
        var originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Processing...';
        submitBtn.disabled = true;

        // Re-enable after 10 seconds as fallback
        setTimeout(function() {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 10000);
    });
});

// Reveal phone input when clicking select button
document.querySelectorAll('.plan-card .plan-select-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var card = this.closest('.plan-card');
        var form = card.querySelector('.plan-payment-form');
        if(form){
            form.style.display = 'block';
            var input = form.querySelector('.phone-input');
            if(input){ input.focus(); }
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});

// Cancel plan function
function cancelPlan(button) {
    var form = button.closest('.plan-payment-form');
    var phoneInput = form.querySelector('.phone-input');
    phoneInput.value = '';
    phoneInput.focus();
}

// Custom JavaScript from settings
{$hotspot_settings['hotspot_custom_js']}
    </script>
</body>
</html>";

// Output the HTML
header('Content-Type: text/html; charset=utf-8');
echo $html;
?>