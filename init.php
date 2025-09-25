<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden', TRUE, 403);
    header('location: ../');
    die();
}
$root_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
if (!isset($isApi)) {
    $isApi = false;
}
// on some server, it getting error because of slash is backwards
function _autoloader($class)
{
    global $root_path;
    if (strpos($class, '_') !== false) {
        $class = str_replace('_', DIRECTORY_SEPARATOR, $class);
        if (file_exists($root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include $root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists($root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include $root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    } else {
        if (file_exists($root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include $root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists($root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include $root_path . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    }
}
spl_autoload_register('_autoloader');

if (!file_exists($root_path . 'config.php')) {
    $root_path .= '..' . DIRECTORY_SEPARATOR;
    if (!file_exists($root_path . 'config.php')) {
        r2('./install');
    }
}

if (!file_exists($root_path .  File::pathFixer('system/orm.php'))) {
    echo $root_path . "orm.php file not found";
    die();
}

$DEVICE_PATH = $root_path . File::pathFixer('system/devices');
$UPLOAD_PATH = $root_path . File::pathFixer('system/uploads');
$CACHE_PATH = $root_path . File::pathFixer('system/cache');
$PAGES_PATH = $root_path . File::pathFixer('pages');
$PLUGIN_PATH = $root_path . File::pathFixer('system/plugin');
$WIDGET_PATH = $root_path . File::pathFixer('system/widgets');
$PAYMENTGATEWAY_PATH = $root_path . File::pathFixer('system/paymentgateway');
$UI_PATH = 'ui';

if (!file_exists($UPLOAD_PATH . File::pathFixer('/notifications.default.json'))) {
    echo $UPLOAD_PATH . File::pathFixer("/notifications.default.json file not found");
    die();
}

require_once $root_path . 'config.php';
require_once $root_path . File::pathFixer('system/orm.php');
require_once $root_path . File::pathFixer('system/autoload/PEAR2/Autoload.php');
include $root_path . File::pathFixer('system/autoload/Hookers.php');

if ($db_password != null && ($db_pass == null || empty($db_pass))) {
    // compability for old version
    $db_pass = $db_password;
}
if ($db_pass != null) {
    // compability for old version
    $db_password = $db_pass;
}
ORM::configure("mysql:host=$db_host;dbname=$db_name");
ORM::configure('username', $db_user);
ORM::configure('password', $db_pass);
ORM::configure('return_result_sets', true);
if ($_app_stage != 'Live') {
    ORM::configure('logging', true);
}
if ($isApi) {
    define('U', APP_URL . '/system/api.php?r=');
} else {
    define('U', APP_URL . '/?_route=');
}

// notification message
if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . "notifications.json")) {
    $_notifmsg = json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.json'), true);
}
$_notifmsg_default = json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.default.json'), true);

//register all plugin
foreach (glob(File::pathFixer($PLUGIN_PATH . DIRECTORY_SEPARATOR . '*.php')) as $filename) {
    try {
        include $filename;
    } catch (Throwable $e) {
        //ignore plugin error
    } catch (Exception $e) {
        //ignore plugin error
    }
}

$result = ORM::for_table('tbl_appconfig')->find_many();
foreach ($result as $value) {
    $config[$value['setting']] = $value['value'];
}

if(empty($config['dashboard_Admin'])){
    $config['dashboard_Admin'] = "12.7,5.12";
}

if(empty($config['dashboard_Agent'])){
    $config['dashboard_Agent'] = "12.7,5.12";
}

if(empty($config['dashboard_Sales'])){
    $config['dashboard_Sales'] = "12.7,5.12";
}

if(empty($config['dashboard_Customer'])){
    $config['dashboard_Customer'] = "6,6";
}


$_c =  $config;
// Merge AI config from config.php into $_c so templates can access them
if (isset($ai_enabled)) { $_c['ai_enabled'] = $ai_enabled ? 1 : 0; }
if (isset($ai_provider)) { $_c['ai_provider'] = $ai_provider; }
if (isset($ai_api_key)) { $_c['ai_api_key'] = !empty($ai_api_key) ? 'set' : ''; }
if (isset($ai_base_url)) { $_c['ai_base_url'] = $ai_base_url; }
if (isset($ai_model)) { $_c['ai_model'] = $ai_model; }
if (isset($ai_max_tokens)) { $_c['ai_max_tokens'] = (int)$ai_max_tokens; }
if (isset($ai_temperature)) { $_c['ai_temperature'] = (float)$ai_temperature; }
if (isset($ai_rag_enabled)) { $_c['ai_rag_enabled'] = $ai_rag_enabled ? 1 : 0; }
if (empty($http_proxy) && !empty($config['http_proxy'])) {
    $http_proxy = $config['http_proxy'];
    if (empty($http_proxyauth) && !empty($config['http_proxyauth'])) {
        $http_proxyauth = $config['http_proxyauth'];
    }
}
date_default_timezone_set($config['timezone']);

if ((!empty($radius_user) && $config['radius_enable']) || _post('radius_enable')) {
    if (!empty($radius_password)) {
        // compability for old version
        $radius_pass = $radius_password;
    }
    ORM::configure("mysql:host=$radius_host;dbname=$radius_name", null, 'radius');
    ORM::configure('username', $radius_user, 'radius');
    ORM::configure('password', $radius_pass, 'radius');
    ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'), 'radius');
    ORM::configure('return_result_sets', true, 'radius');
}


// Check if the user has selected a language
if (!empty($_SESSION['user_language'])) {
    $config['language'] = $_SESSION['user_language'];
} else if (!empty($_COOKIE['user_language'])) {
    $config['language'] = $_COOKIE['user_language'];
} else if (User::getID() > 0) {
    $lang = User::getAttribute("Language");
    if (!empty($lang)) {
        $config['language'] = $lang;
    }
}

if (empty($config['language'])) {
    $config['language'] = 'english';
}
$lan_file = $root_path . File::pathFixer('system/lan/' . $config['language'] . '.json');
if (file_exists($lan_file)) {
    $_L = json_decode(file_get_contents($lan_file), true);
} else {
    $_L['author'] = 'Auto Generated by PHPNuxBill Script';
    file_put_contents($lan_file, json_encode($_L));
}

function safedata($value)
{
    $value = trim($value);
    return $value;
}

function _post($param, $defvalue = '')
{
    if (!isset($_POST[$param])) {
        return $defvalue;
    } else {
        return safedata($_POST[$param]);
    }
}

function _get($param, $defvalue = '')
{
    if (!isset($_GET[$param])) {
        return $defvalue;
    } else {
        return safedata($_GET[$param]);
    }
}

function _req($param, $defvalue = '')
{
    if (!isset($_REQUEST[$param])) {
        return $defvalue;
    } else {
        return safedata($_REQUEST[$param]);
    }
}


function _auth($login = true)
{
    if (User::getID()) {
        return true;
    } else {
        if ($login) {
            r2(getUrl('login'));
        } else {
            return false;
        }
    }
}

function _admin($login = true)
{
    if (Admin::getID()) {
        return true;
    } else {
        if ($login) {
            r2(getUrl('login'));
        } else {
            return false;
        }
    }
}


function _log($description, $type = '', $userid = '0')
{
    $d = ORM::for_table('tbl_logs')->create();
    $d->date = date('Y-m-d H:i:s');
    $d->type = $type;
    $d->description = $description;
    $d->userid = $userid;
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']))   //to check ip is pass from cloudflare tunnel
    {
        $d->ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
        $d->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP']))   //to check ip from share internet
    {
        $d->ip = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER["REMOTE_ADDR"])) {
        $d->ip = $_SERVER["REMOTE_ADDR"];
    } else if (php_sapi_name() == 'cli') {
        $d->ip = 'CLI';
    } else {
        $d->ip = 'Unknown';
    }
    $d->save();
}

function Lang($key)
{
    return Lang::T($key);
}

function alphanumeric($str, $tambahan = "")
{
    return Text::alphanumeric($str, $tambahan);
}

function showResult($success, $message = '', $result = [], $meta = [])
{
    header("Content-Type: Application/json");
    $json = json_encode(['success' => $success, 'message' => $message, 'result' => $result, 'meta' => $meta]);
    echo $json;
    die();
}

/**
 * make url canonical or standar
 */
function getUrl($url)
{
    return Text::url($url);
}

function generateUniqueNumericVouchers($totalVouchers, $length = 8)
{
    // Define characters allowed in the voucher code
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $vouchers = array();

    // Attempt to generate unique voucher codes
    for ($j = 0; $j < $totalVouchers; $j++) {
        do {
            $voucherCode = '';
            // Generate the voucher code
            for ($i = 0; $i < $length; $i++) {
                $voucherCode .= $characters[rand(0, $charactersLength - 1)];
            }
            // Check if the generated voucher code already exists in the array
            $isUnique = !in_array($voucherCode, $vouchers);
        } while (!$isUnique);

        $vouchers[] = $voucherCode;
    }

    return $vouchers;
}

function sendTelegram($txt)
{
    Message::sendTelegram($txt);
}

function sendSMS($phone, $txt)
{
    Message::sendSMS($phone, $txt);
}

function sendWhatsapp($phone, $txt)
{
    Message::sendWhatsapp($phone, $txt);
}

function r2($to, $ntype = 'e', $msg = '')
{
    global $isApi;
    if ($isApi) {
        showResult(
            ($ntype == 's') ? true : false,
            $msg
        );
    }
    if ($msg == '') {
        header("location: $to");
        exit;
    }
    $_SESSION['ntype'] = $ntype;
    $_SESSION['notify'] = $msg;
    header("location: $to");
    exit;
}

function _alert($text, $type = 'success', $url = "home", $time = 3)
{
    global $ui, $isApi;
    if ($isApi) {
        showResult(
            ($type == 'success') ? true : false,
            $text
        );
    }
    if (!isset($ui)) return;
    if (strlen($url) > 4) {
        if (substr($url, 0, 4) != "http") {
            $url = getUrl($url);
        }
    } else {
        $url = getUrl($url);
    }
    $ui->assign('text', $text);
    $ui->assign('type', $type);
    $ui->assign('time', $time);
    $ui->assign('url', $url);
    $ui->display('admin/alert.tpl');
    die();
}


if (!isset($api_secret)) {
    $api_secret = $db_pass;
}

function displayMaintenanceMessage(): void
{
    global $config, $ui;
    $date = $config['maintenance_date'];
    if ($date) {
        $ui->assign('date', $date);
    }
    http_response_code(503);
    $ui->assign('companyName', $config['CompanyName']);
    $ui->display('admin/maintenance.tpl');
    die();
}

function isTableExist($table)
{
    try {
        $record = ORM::forTable($table)->find_one();
        return $record !== false;
    } catch (Exception $e) {
        return false;
    }
}
