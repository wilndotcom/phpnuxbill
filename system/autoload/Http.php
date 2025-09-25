<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 *  using proxy, add this variable in config.php
 *  $http_proxy  = '127.0.0.1:3128';
 *  if proxy using authentication, use this parameter
 *  $http_proxyauth = 'user:password';
 **/

class Http
{
    private static function logDebug($message, $data = null)
    {
        // Only log if debug mode is enabled
        if (defined('HTTP_DEBUG') && constant('HTTP_DEBUG')) {
            $log_message = "HTTP Debug: $message";
            if ($data !== null) {
                $log_message .= " | Data: " . (is_string($data) ? $data : json_encode($data));
            }
            error_log($log_message);
        }
    }

    public static function getData($url, $headers = [], $connect_timeout = 15000, $wait_timeout = 30000)
    {
        global $http_proxy, $http_proxyauth, $admin;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $wait_timeout);
        if (is_array($headers) && count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($http_proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $http_proxy);
            if (!empty($http_proxyauth)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $http_proxyauth);
            }
        }
        $server_output = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);
        if ($admin && $error_msg) {
            Message::sendTelegram(
                "Http::getData Error:\n" .
                    _get('_route') . "\n" .
                    "\n$url" .
                    "\n$error_msg"
            );
            return $error_msg;
        }
        return (!empty($server_output)) ? $server_output : $error_msg;
    }

    public static function postJsonData($url, $array_post, $headers = [], $basic = null, $connect_timeout = 15000, $wait_timeout = 30000)
    {
        global $http_proxy, $http_proxyauth, $admin;

        self::logDebug("Starting POST request", [
            'url' => $url,
            'timeout' => $connect_timeout . '/' . $wait_timeout,
            'proxy' => $http_proxy ?: 'none'
        ]);

        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        // Disable "Expect: 100-continue" which can cause issues with some proxies/providers
        $headers[] = 'Expect:';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $wait_timeout);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, false);
        if (!empty($http_proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $http_proxy);
            if (!empty($http_proxyauth)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $http_proxyauth);
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($array_post));
        if (is_array($headers) && count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        // Force HTTP/1.1 for compatibility
        if (defined('CURL_HTTP_VERSION_1_1')) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        }
        if (!empty($basic)) {
            curl_setopt($ch, CURLOPT_USERPWD, $basic);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Get HTTP status code and include headers in response
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        self::logDebug("Request completed", [
            'http_code' => $http_code,
            'response_length' => strlen($response)
        ]);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            self::logDebug("CURL error occurred", $error_msg);
            if ($admin) {
                Message::sendTelegram(
                    "Http::postJsonData Error:\n" .
                        _get('_route') . "\n" .
                        "\n$url" .
                        "\nHTTP Code: $http_code" .
                        "\n$error_msg"
                );
            }
            return $error_msg;
        }

        // Split response into headers and body BEFORE closing handle
        $header = ($header_size !== false && $header_size > 0) ? substr($response, 0, $header_size) : '';
        $body = ($header_size !== false && $header_size > 0) ? substr($response, $header_size) : $response;
        curl_close($ch);

        self::logDebug("Response parsed", [
            'header_size' => $header_size,
            'body_size' => strlen($body),
            'http_code' => $http_code
        ]);

        // Check for HTTP error status codes
        if ($http_code >= 400) {
            $error_msg = "HTTP Error $http_code: " . $body;
            self::logDebug("HTTP error response", [
                'http_code' => $http_code,
                'error_preview' => substr($body, 0, 100)
            ]);
            if ($admin) {
                Message::sendTelegram(
                    "Http::postJsonData HTTP Error:\n" .
                        _get('_route') . "\n" .
                        "\n$url" .
                        "\nHTTP Code: $http_code" .
                        "\nResponse: " . substr($body, 0, 500)
                );
            }
            return $error_msg;
        }

        // Validate JSON response
        if (!empty($body)) {
            $json_test = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $json_error = json_last_error_msg();
                $error_msg = "Invalid JSON response: $json_error. Raw response: " . substr($body, 0, 500);
                self::logDebug("JSON validation failed", [
                    'json_error' => $json_error,
                    'body_preview' => substr($body, 0, 100)
                ]);
                if ($admin) {
                    Message::sendTelegram(
                        "Http::postJsonData JSON Error:\n" .
                            _get('_route') . "\n" .
                            "\n$url" .
                            "\nHTTP Code: $http_code" .
                            "\nJSON Error: $json_error" .
                            "\nResponse: " . substr($body, 0, 500)
                    );
                }
                return $error_msg;
            }
        }

        self::logDebug("Request successful", [
            'body_size' => strlen($body),
            'json_valid' => !empty($body) ? 'true' : 'false'
        ]);

        return (!empty($body)) ? $body : '';
    }


    public static function postData($url, $array_post, $headers = [], $basic = null, $connect_timeout = 15000, $wait_timeout = 30000)
    {
        global $http_proxy, $http_proxyauth, $admin;
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $wait_timeout);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, false);
        if (!empty($http_proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $http_proxy);
            if (!empty($http_proxyauth)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $http_proxyauth);
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array_post));
        if (is_array($headers) && count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if (!empty($basic)) {
            curl_setopt($ch, CURLOPT_USERPWD, $basic);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);
        if ($admin && $error_msg) {
            Message::sendTelegram(
                "Http::postData Error:\n" .
                    _get('_route') . "\n" .
                    "\n$url" .
                    "\n$error_msg"
            );
            return $error_msg;
        }
        return (!empty($server_output)) ? $server_output : $error_msg;
    }
}
