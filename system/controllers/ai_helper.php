<?php

/**
 *  AI Helper Functions
 *  Shared functions for AI functionality
 */

function ai_read_local_context($limit = 4000)
{
    $parts = [];
    $files = [
        'readme' => 'README.md',
        'docs' => 'docs/index.html',
        'pages' => 'pages_template/index.html',
    ];

    // Add system-specific context
    $system_context = ai_get_system_context();
    $parts[] = $system_context;

    foreach ($files as $key => $path) {
        if (file_exists($path)) {
            $txt = @file_get_contents($path);
            if ($txt !== false && !empty($txt)) {
                $txt = strip_tags($txt);
                $txt = preg_replace('/\s+/', ' ', $txt);
                $parts[] = substr($txt, 0, (int)$limit);
            }
        }
    }
    return implode("\n---\n", $parts);
}

function ai_get_system_context()
{
    global $config;
    $context = "PHPNuxBill System Information:\n";
    $context .= "- Company: " . ($config['CompanyName'] ?? 'PHPNuxBill') . "\n";
    $context .= "- Version: " . ($config['version'] ?? 'Unknown') . "\n";
    $context .= "- Theme: " . ($config['theme'] ?? 'default') . "\n";
    $context .= "- Language: " . ($config['language'] ?? 'english') . "\n";
    $context .= "- Timezone: " . ($config['timezone'] ?? 'UTC') . "\n";
    $context .= "- Currency: " . ($config['currency_code'] ?? 'USD') . "\n";

    // Add feature flags
    $context .= "\nEnabled Features:\n";
    $context .= "- Radius: " . ($config['radius_enable'] ?? 'No') . "\n";
    $context .= "- Maintenance Mode: " . ($config['maintenance_mode'] ?? 'No') . "\n";
    $context .= "- SMS: " . (!empty($config['sms_url']) ? 'Yes' : 'No') . "\n";
    $context .= "- Email: " . (!empty($config['smtp_host']) ? 'Yes' : 'No') . "\n";
    $context .= "- WhatsApp: " . (!empty($config['wa_url']) ? 'Yes' : 'No') . "\n";
    $context .= "- Telegram: " . (!empty($config['telegram_bot']) ? 'Yes' : 'No') . "\n";

    return $context;
}

function ai_provider_headers()
{
	global $ai_provider, $ai_api_key;
	$headers = [];
	if ($ai_provider === 'openai') {
		$headers[] = 'Authorization: Bearer ' . $ai_api_key;
	} elseif ($ai_provider === 'openrouter') {
		$headers[] = 'Authorization: Bearer ' . $ai_api_key;
		$headers[] = 'HTTP-Referer: ' . APP_URL;
		$headers[] = 'X-Title: PHPNuxBill Assistant';
	} elseif ($ai_provider === 'groq') {
		$headers[] = 'Authorization: Bearer ' . $ai_api_key;
		// Note: Content-Type: application/json is added by Http::postJsonData
	}
	return $headers;
}

function ai_provider_endpoint()
{
    global $ai_provider, $ai_base_url;
    if (!empty($ai_base_url)) return rtrim($ai_base_url, '/');
    if ($ai_provider === 'openai') return 'https://api.openai.com/v1';
    if ($ai_provider === 'openrouter') return 'https://openrouter.ai/api/v1';
    if ($ai_provider === 'groq') return 'https://api.groq.com/openai/v1';
    if ($ai_provider === 'ollama') return 'http://127.0.0.1:11434';
    return 'https://openrouter.ai/api/v1';
}

function ai_chat_call($messages)
{
    global $ai_provider, $ai_model, $ai_max_tokens, $ai_temperature;
    $base = ai_provider_endpoint();
    $headers = ai_provider_headers();

    if ($ai_provider === 'ollama') {
        $url = $base . '/api/chat';
        $payload = [
            'model' => $ai_model ?: 'llama3.1',
            'messages' => $messages,
            'temperature' => $ai_temperature,
        ];
        $res = Http::postJsonData($url, $payload, $headers);
        return $res;
    }

    // OpenAI/OpenRouter/Groq compatible
    $url = $base . '/chat/completions';
    $payload = [
        'model' => $ai_model,
        'max_tokens' => (int)$ai_max_tokens,
        'temperature' => (float)$ai_temperature,
        'messages' => $messages,
    ];

    // Debug: Log the request payload
    error_log("AI Request Debug - URL: $url");
    error_log("AI Request Debug - Payload: " . json_encode($payload));
    error_log("AI Request Debug - Headers: " . json_encode($headers));

    $res = Http::postJsonData($url, $payload, $headers);

    // Debug: Log the raw response
    error_log("AI Response Debug - Raw: " . $res);

    return $res;
}