<?php

/**
 *  AI Assistant Controller
 */

$action = $routes['1'] ?? '';

header('Content-Type: application/json');

if (!$ai_enabled) {
	showResult(false, 'AI assistant is disabled');
}

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
		// Content-Type: application/json is added by Http::postJsonData
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

	// Increased timeouts for AI API calls (60 seconds total timeout)
	$connect_timeout = 15000; // 15 seconds connect timeout
	$wait_timeout = 60000;   // 60 seconds total timeout

	if ($ai_provider === 'ollama') {
		$url = $base . '/api/chat';
		$payload = [
			'model' => $ai_model ?: 'llama3.1',
			'messages' => $messages,
			'temperature' => $ai_temperature,
		];
		$res = Http::postJsonData($url, $payload, $headers, null, $connect_timeout, $wait_timeout);
		return $res;
	}

    // OpenAI/OpenRouter/Groq compatible
    $url = $base . '/chat/completions';

    // Set defaults and validate parameters
    $model = $ai_model ?: ($ai_provider === 'groq' ? 'llama3-8b-8192' : 'gpt-3.5-turbo');
    $max_tokens = (int)$ai_max_tokens;
    if ($max_tokens <= 0 || $max_tokens > 32768) $max_tokens = 2048; // Reasonable default
    $temperature = (float)$ai_temperature;
    if ($temperature < 0 || $temperature > 2) $temperature = 0.7; // Clamp to valid range

    // Sanitize messages to ensure UTF-8
    $sanitized_messages = [];
    foreach ($messages as $msg) {
        $sanitized_messages[] = [
            'role' => $msg['role'],
            'content' => mb_convert_encoding($msg['content'], 'UTF-8', 'UTF-8')
        ];
    }

    $payload = [
        'model' => $model,
        'max_tokens' => $max_tokens,
        'temperature' => $temperature,
        'messages' => $sanitized_messages,
    ];

    // Debug: Log the request payload
    error_log("AI Request Debug - URL: $url");
    error_log("AI Request Debug - Payload: " . json_encode($payload));
    error_log("AI Request Debug - Headers: " . json_encode($headers));

	// Retry logic with exponential backoff
	$max_retries = 3;
	$retry_delay = 1000000; // 1 second in microseconds

	for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
		$res = Http::postJsonData($url, $payload, $headers, null, $connect_timeout, $wait_timeout);

		// Debug: Log the raw response
		error_log("AI Response Debug (Attempt $attempt) - Raw: " . $res);

		// Check if response is valid JSON (not an error message)
		if (!is_string($res) || !preg_match('/^(Invalid JSON response|HTTP Error)/', $res)) {
			// Valid response or non-error string, return it
			return $res;
		}

		// If this is the last attempt, don't retry
		if ($attempt < $max_retries) {
			error_log("AI API call failed (attempt $attempt), retrying in " . ($retry_delay / 1000000) . " seconds...");
			usleep($retry_delay);
			$retry_delay *= 2; // Exponential backoff
		}
	}

	// All retries failed, return the last response
	return $res;
}

function ai_relaxed_json_decode($raw)
{
    if (is_array($raw)) return $raw;
    if (!is_string($raw) || $raw === '') return null;

    // Check if this looks like an error message from Http class
    if (strpos($raw, 'Invalid JSON response') !== false || strpos($raw, 'HTTP Error') !== false || (strpos($raw, 'Error') !== false && !preg_match('/^\s*\{/', $raw))) {
        return ['http_error' => $raw];
    }

    $out = @json_decode($raw, true);
    if (is_array($out)) return $out;

    // Try to extract and decode partial JSON for better error handling
    $start = strpos($raw, '{');
    $end = strrpos($raw, '}');
    if ($start !== false && $end !== false && $end >= $start) {
        $json = substr($raw, $start, $end - $start + 1);
        $out2 = @json_decode($json, true);
        if (is_array($out2)) return $out2;
    }

    // If still no valid JSON, but response starts with {, it might be truncated
    if ($start === 0 && json_last_error() === JSON_ERROR_SYNTAX) {
        // Return a special marker for truncated response
        return ['truncated_response' => substr($raw, 0, 500)];
    }

    return null;
}

switch ($action) {
	case 'chat':
		if (!$ai_enabled) {
			showResult(false, 'AI assistant is disabled by configuration');
		}
		if ($ai_provider !== 'ollama' && empty($ai_api_key)) {
			showResult(false, 'AI API key is not configured');
		}
		$role = Admin::getID() ? 'admin' : (User::getID() ? 'customer' : 'guest');
		$prompt = trim(_post('message', ''));
		if (empty($prompt)) {
			showResult(false, 'message is required');
		}
		$context = '';
		if ($ai_rag_enabled) {
			$context = ai_read_local_context(2000); // Reduced from 4000 to prevent oversized requests
		}
		$company_name = $config['CompanyName'] ?? 'PHPNuxBill';
		$system = "You are an AI assistant for {$company_name}, a comprehensive Mikrotik billing and hotspot management system. Be helpful, concise, and accurate.

Key capabilities:
- Hotspot & PPPOE management
- Voucher generation and activation
- Customer account management
- Payment processing and gateways
- Multi-router Mikrotik support
- SMS, Email, WhatsApp, Telegram notifications
- Auto-renewal with balance system
- Multi-language support
- Plugin system for extensions

If guidance requires steps, number them clearly. Only answer about {$company_name} features and functionality. If unsure about specific details, ask for clarification.

Current user role: " . $role . "
System context:
" . substr($context, 0, 1500); // Reduced from 3000 to prevent token limit issues
		$messages = [
			['role' => 'system', 'content' => $system],
			['role' => 'user', 'content' => $prompt],
		];
        $raw = ai_chat_call($messages);
        $out = ai_relaxed_json_decode($raw);
  $answer = '';

  // Handle HTTP errors from Http class
  if (is_array($out) && isset($out['http_error'])) {
  	$error_msg = $out['http_error'];

  	// Try to extract and parse JSON from HTTP error messages
  	if (preg_match('/HTTP Error \d+: (.+)$/', $error_msg, $matches)) {
  		$json_part = trim($matches[1]);
  		$parsed_error = @json_decode($json_part, true);
  		if (is_array($parsed_error) && isset($parsed_error['error'])) {
  			$api_error = $parsed_error['error'];
  			$message = $api_error['message'] ?? 'Unknown API error';
  			$type = $api_error['type'] ?? 'unknown_error';
  			$code = $api_error['code'] ?? 'unknown';
  			showResult(false, 'AI API Error: ' . $message . ' (Type: ' . $type . ', Code: ' . $code . ')');
  		} else {
  			showResult(false, 'AI API Error: ' . $error_msg);
  		}
  	} else {
  		showResult(false, 'AI API Error: ' . $error_msg);
  	}
  }

  // Handle truncated responses
  if (is_array($out) && isset($out['truncated_response'])) {
  	showResult(false, 'AI API Error: Response appears to be truncated or incomplete. This may be due to network issues, rate limiting, or server timeout. Raw response preview: ' . substr($out['truncated_response'], 0, 200) . '...');
  }

  // Handle JSON parsing errors
  if ($raw === null || $raw === '') {
   showResult(false, 'AI API Error: No response received from API service');
  } elseif (json_last_error() !== JSON_ERROR_NONE) {
   $json_error = json_last_error_msg();
   $raw_preview = substr($raw, 0, 200);

   // Log detailed error for debugging
   error_log("AI JSON Parse Error: $json_error");
   error_log("AI Raw Response: $raw");
   error_log("AI Request: " . json_encode($messages));

   // Provide specific error messages for common issues
   if (strpos($json_error, 'unexpected end of JSON') !== false) {
    showResult(false, 'AI API Error: Incomplete response from API service. This may be due to network issues or API rate limiting. Raw response preview: ' . $raw_preview . '... (JSON Error: ' . $json_error . ')');
   } else {
    showResult(false, 'AI API Error: Invalid JSON response from API service. Error: ' . $json_error . '. Raw response preview: ' . $raw_preview . '...');
   }
  }

		// Handle API errors
		if (is_array($out) && isset($out['error'])) {
			$error_msg = $out['error']['message'] ?? 'Unknown API error';
			$error_type = $out['error']['type'] ?? 'unknown_error';
			$error_code = $out['error']['code'] ?? 'unknown';

			// Provide helpful guidance for common errors
			if ($error_msg === 'Invalid API Key') {
				showResult(false, 'AI API Error: Invalid API Key. Please check: 1) Your API key is correct, 2) You copied the full key from https://console.groq.com/keys, 3) No extra spaces or characters. Current key starts with: ' . substr($ai_api_key, 0, 10) . '... (Type: ' . $error_type . ', Code: ' . $error_code . ')');
			} else {
				showResult(false, 'AI API Error: ' . $error_msg . ' (Type: ' . $error_type . ', Code: ' . $error_code . ')');
			}
		}

		if (is_array($out)) {
			// OpenAI/OpenRouter/Groq structure
			if (!empty($out['choices'][0]['message']['content'])) {
				$answer = $out['choices'][0]['message']['content'];
			} elseif (!empty($out['message'])) {
				$answer = is_array($out['message']) && isset($out['message']['content']) ? $out['message']['content'] : $out['message'];
			} elseif (!empty($out['choices'][0]['text'])) {
				$answer = $out['choices'][0]['text'];
			}
		}
		if (empty($answer)) {
			$answer = is_string($raw) ? $raw : 'No response from AI service';
		}
		showResult(true, '', ['answer' => $answer]);
	case 'test':
		// Test endpoint for RAG functionality
		$context = ai_read_local_context(2000);
		$test_result = [
			'ai_enabled' => $ai_enabled,
			'ai_provider' => $ai_provider,
			'ai_rag_enabled' => $ai_rag_enabled,
			'context_length' => strlen($context),
			'context_preview' => substr($context, 0, 500) . '...',
			'system_info' => ai_get_system_context()
		];
		showResult(true, 'AI Assistant RAG Test Results', $test_result);
	case 'status':
		// Simple status check without authentication
		showResult(true, 'AI Assistant Status', [
			'ai_enabled' => $ai_enabled,
			'ai_provider' => $ai_provider,
			'ai_rag_enabled' => $ai_rag_enabled,
			'ai_model' => $ai_model ?? 'Not configured',
			'ai_api_key' => !empty($ai_api_key) ? 'Set' : 'Not configured',
			'status' => $ai_enabled ? 'Active' : 'Disabled'
		]);
	case 'demo':
		// Demo chat without authentication for testing
		$demo_message = _post('message', 'Hello, can you tell me about PHPNuxBill features?');
		$context = ai_read_local_context(1000); // Reduced
		$company_name = $config['CompanyName'] ?? 'PHPNuxBill';
		$system = "You are an AI assistant for {$company_name}, a comprehensive Mikrotik billing and hotspot management system. Be helpful, concise, and accurate.

Key capabilities:
- Hotspot & PPPOE management
- Voucher generation and activation
- Customer account management
- Payment processing and gateways
- Multi-router Mikrotik support
- SMS, Email, WhatsApp, Telegram notifications
- Auto-renewal with balance system
- Multi-language support
- Plugin system for extensions

If guidance requires steps, number them clearly. Only answer about {$company_name} features and functionality.

Current user role: demo
System context:
" . substr($context, 0, 1000); // Reduced

		$messages = [
			['role' => 'system', 'content' => $system],
			['role' => 'user', 'content' => $demo_message],
		];

        $raw = ai_chat_call($messages);
        $out = ai_relaxed_json_decode($raw);
  $answer = '';

  // Handle HTTP errors from Http class
  if (is_array($out) && isset($out['http_error'])) {
  	$error_msg = $out['http_error'];

  	// Try to extract and parse JSON from HTTP error messages
  	if (preg_match('/HTTP Error \d+: (.+)$/', $error_msg, $matches)) {
  		$json_part = trim($matches[1]);
  		$parsed_error = @json_decode($json_part, true);
  		if (is_array($parsed_error) && isset($parsed_error['error'])) {
  			$api_error = $parsed_error['error'];
  			$message = $api_error['message'] ?? 'Unknown API error';
  			$answer = 'AI API Error: ' . $message . ' (Type: ' . ($api_error['type'] ?? 'unknown_error') . ', Code: ' . ($api_error['code'] ?? 'unknown') . ')';
  		} else {
  			$answer = 'AI API Error: ' . $error_msg;
  		}
  	} else {
  		$answer = 'AI API Error: ' . $error_msg;
  	}
  }

  // Handle truncated responses
  if (is_array($out) && isset($out['truncated_response'])) {
  	$answer = 'AI API Error: Response appears to be truncated or incomplete. This may be due to network issues, rate limiting, or server timeout. Raw response preview: ' . substr($out['truncated_response'], 0, 200) . '...';
  }

  // Handle JSON parsing errors
  elseif ($raw === null || $raw === '') {
   $answer = 'No response received from AI service';
  } elseif (json_last_error() !== JSON_ERROR_NONE) {
   $json_error = json_last_error_msg();
   $raw_preview = substr($raw, 0, 200);

   // Log detailed error for debugging
   error_log("AI Demo JSON Parse Error: $json_error");
   error_log("AI Demo Raw Response: $raw");

   $answer = 'Invalid JSON response from AI service. Error: ' . $json_error . '. Raw response preview: ' . $raw_preview . '...';
  } elseif (is_array($out)) {
			if (!empty($out['choices'][0]['message']['content'])) {
				$answer = $out['choices'][0]['message']['content'];
			} elseif (!empty($out['message'])) {
				$answer = is_array($out['message']) && isset($out['message']['content']) ? $out['message']['content'] : $out['message'];
			}
		}

		if (empty($answer)) {
			$answer = is_string($raw) ? $raw : 'No response from AI service';
		}

		showResult(true, 'AI Demo Response', [
			'question' => $demo_message,
			'answer' => $answer,
			'ai_provider' => $ai_provider,
			'ai_model' => $ai_model
		]);
	case 'webtest':
		// Web-accessible test page (no authentication required for demo)
		$context = ai_read_local_context(1000);
		$system_info = ai_get_system_context();

		$test_data = [
			'ai_configuration' => [
				'ai_enabled' => $ai_enabled,
				'ai_provider' => $ai_provider,
				'ai_model' => $ai_model ?? 'Not set',
				'ai_rag_enabled' => $ai_rag_enabled,
				'ai_api_key' => !empty($ai_api_key) ? 'Configured' : 'Not configured'
			],
			'context_info' => [
				'context_length' => strlen($context),
				'context_sources' => ['README.md', 'docs/index.html', 'pages_template/index.html'],
				'context_preview' => substr($context, 0, 300) . '...'
			],
			'system_info' => $system_info
		];

		showResult(true, 'AI Assistant Web Test', $test_data);
	case 'info':
		// Simple info page for testing
		$info = [
			'ai_enabled' => $ai_enabled,
			'ai_provider' => $ai_provider,
			'ai_model' => $ai_model ?? 'Not configured',
			'ai_rag_enabled' => $ai_rag_enabled,
			'ai_api_key_status' => !empty($ai_api_key) ? 'Set' : 'Not set',
			'controller_exists' => file_exists(__FILE__),
			'php_version' => PHP_VERSION,
			'current_time' => date('Y-m-d H:i:s'),
			'ai_features' => [
				'RAG enabled: ' . ($ai_rag_enabled ? 'Yes' : 'No'),
				'Multi-provider support: Yes',
				'Role-based responses: Yes',
				'Context-aware: Yes',
				'Error handling: Yes'
			]
		];
		showResult(true, 'AI Assistant Information', $info);
	default:
		showResult(false, 'AI endpoint not found. Available endpoints: status, test, demo, chat, webtest, info');
}
