<?php

/**
 *  Help Controller
 *  Provides help functionality integrated with AI assistant
 */

$action = $routes['1'] ?? '';

header('Content-Type: application/json');

if (!$ai_enabled) {
    showResult(false, 'AI assistant is disabled');
}

// Include AI helper functions
require_once 'ai_helper.php';

function help_get_help_topics()
{
    return [
        'general' => [
            'title' => 'General Help',
            'description' => 'General information about PHPNuxBill features and usage'
        ],
        'hotspot' => [
            'title' => 'Hotspot Management',
            'description' => 'Help with hotspot configuration, user management, and voucher creation'
        ],
        'billing' => [
            'title' => 'Billing & Payments',
            'description' => 'Information about payment processing, invoices, and customer billing'
        ],
        'routers' => [
            'title' => 'Router Management',
            'description' => 'Help with Mikrotik router configuration and management'
        ],
        'reports' => [
            'title' => 'Reports & Analytics',
            'description' => 'Understanding reports, analytics, and system insights'
        ],
        'settings' => [
            'title' => 'System Settings',
            'description' => 'Configuration options and system administration'
        ]
    ];
}

function help_get_quick_answers()
{
    return [
        'how_to_create_voucher' => [
            'question' => 'How do I create a voucher?',
            'answer' => 'Go to Plans > Vouchers, select a plan, set voucher details, and click Create.'
        ],
        'how_to_add_customer' => [
            'question' => 'How do I add a new customer?',
            'answer' => 'Navigate to Customers > Add, fill in customer details, and save.'
        ],
        'how_to_setup_payment' => [
            'question' => 'How do I set up payment gateways?',
            'answer' => 'Go to Settings > Payment Gateway, install and configure your preferred payment method.'
        ],
        'how_to_configure_router' => [
            'question' => 'How do I configure a router?',
            'answer' => 'Access Routers > Add, enter router details, test connection, and save configuration.'
        ]
    ];
}

switch ($action) {
    case 'status':
        // Show AI configuration status for help system
        $status_info = [
            'ai_enabled' => $ai_enabled,
            'ai_provider' => $ai_provider,
            'ai_api_key_configured' => !empty($ai_api_key),
            'ai_api_key_status' => !empty($ai_api_key) ? 'Configured' : 'Not configured',
            'can_use_ai_search' => $ai_enabled && ($ai_provider === 'ollama' || !empty($ai_api_key)),
            'help_endpoints_available' => [
                'topics' => 'Available help topics',
                'quick' => 'Quick answers',
                'search' => 'AI-powered search (requires AI configuration)',
                'guide/{topic}' => 'Step-by-step guides',
                'faq' => 'Frequently asked questions',
                'status' => 'Current AI configuration status'
            ]
        ];
        showResult(true, 'Help System Status', $status_info);

    case 'topics':
        // Return available help topics
        $topics = help_get_help_topics();
        showResult(true, 'Available Help Topics', $topics);

    case 'quick':
        // Return quick answers to common questions
        $quick_answers = help_get_quick_answers();
        showResult(true, 'Quick Help Answers', $quick_answers);

    case 'search':
        // Search help using AI
        if (!$ai_enabled) {
            showResult(false, 'AI assistant is disabled');
        }
        if ($ai_provider !== 'ollama' && empty($ai_api_key)) {
            showResult(false, 'AI API key is not configured. Current configuration: Provider=' . $ai_provider . ', API Key=' . (!empty($ai_api_key) ? 'Set' : 'Empty') . '. Get a free API key from: https://console.groq.com/keys');
        }

        // Check if API key looks like a placeholder
        if ($ai_api_key === 'YOUR_GROQ_API_KEY_HERE' || $ai_api_key === 'YOUR_API_KEY_HERE' || strlen($ai_api_key) < 20) {
            showResult(false, 'AI API key appears to be a placeholder. Please replace it with your actual API key from https://console.groq.com/keys. Your current key: ' . substr($ai_api_key, 0, 20) . '... Get your free API key here: https://console.groq.com/keys');
        }

        $query = trim(_post('query', ''));

        // If no query in POST data, try to get it from JSON input
        if (empty($query)) {
            $json_input = file_get_contents('php://input');
            if (!empty($json_input)) {
                $json_data = json_decode($json_input, true);
                if (is_array($json_data) && isset($json_data['query'])) {
                    $query = trim($json_data['query']);
                }
            }
        }

        if (empty($query)) {
            showResult(false, 'Search query is required. Send as POST parameter "query" or as JSON {"query": "your question"}');
        }

        $role = Admin::getID() ? 'admin' : (User::getID() ? 'customer' : 'guest');
        $context = ai_read_local_context(3000);

        $system = "You are a helpful assistant for PHPNuxBill, a comprehensive Mikrotik billing and hotspot management system. Provide clear, concise help and guidance.

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

Current user role: " . $role . "
System context:
" . substr($context, 0, 2000);

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "Help me with: " . $query]
        ];

        $raw = ai_chat_call($messages);
        $out = @json_decode($raw, true);
        $answer = '';

        if (is_array($out)) {
            if (!empty($out['choices'][0]['message']['content'])) {
                $answer = $out['choices'][0]['message']['content'];
            } elseif (!empty($out['message'])) {
                $answer = is_array($out['message']) && isset($out['message']['content']) ? $out['message']['content'] : $out['message'];
            }
        }

        if (empty($answer)) {
            $answer = is_string($raw) ? $raw : 'No response from AI service';
        }

        showResult(true, 'Help Search Results', ['query' => $query, 'answer' => $answer]);

    case 'guide':
        // Return step-by-step guides
        $topic = $routes['2'] ?? '';
        $guides = [
            'voucher_creation' => [
                'title' => 'Creating Vouchers',
                'steps' => [
                    '1. Go to Plans > Vouchers',
                    '2. Select an existing plan or create a new one',
                    '3. Set voucher prefix, length, and quantity',
                    '4. Configure validity period and data limits',
                    '5. Click "Generate Vouchers"',
                    '6. Download or print the generated vouchers'
                ]
            ],
            'customer_setup' => [
                'title' => 'Setting Up Customers',
                'steps' => [
                    '1. Navigate to Customers > Add',
                    '2. Enter customer personal information',
                    '3. Set up username and password',
                    '4. Configure service details if needed',
                    '5. Save the customer profile'
                ]
            ],
            'router_config' => [
                'title' => 'Configuring Routers',
                'steps' => [
                    '1. Access Routers > Add Router',
                    '2. Enter router name and description',
                    '3. Provide IP address and login credentials',
                    '4. Test the connection',
                    '5. Configure hotspot settings',
                    '6. Save and enable the router'
                ]
            ]
        ];

        if (isset($guides[$topic])) {
            showResult(true, 'Step-by-Step Guide', $guides[$topic]);
        } else {
            showResult(false, 'Guide not found. Available guides: ' . implode(', ', array_keys($guides)));
        }

    case 'faq':
        // Frequently asked questions
        $faqs = [
            [
                'question' => 'How do I reset a customer password?',
                'answer' => 'Go to Customers > List, find the customer, click Edit, and update the password field.'
            ],
            [
                'question' => 'How do I check system usage?',
                'answer' => 'Access Dashboard or Reports > System Usage to view bandwidth and resource utilization.'
            ],
            [
                'question' => 'How do I configure SMS notifications?',
                'answer' => 'Go to Settings > Notifications, configure SMS settings with your provider details.'
            ],
            [
                'question' => 'How do I backup the system?',
                'answer' => 'Use the export feature in Settings > Export to backup customers, plans, and other data.'
            ]
        ];

        showResult(true, 'Frequently Asked Questions', $faqs);

    default:
        // Return general help information
        $help_info = [
            'available_endpoints' => [
                'status' => 'Check AI configuration and system status',
                'topics' => 'List available help topics',
                'quick' => 'Get quick answers to common questions',
                'search' => 'Search help using AI (POST with query parameter)',
                'guide/{topic}' => 'Get step-by-step guides',
                'faq' => 'View frequently asked questions'
            ],
            'ai_integration' => $ai_enabled ? 'AI assistant is enabled for enhanced help' : 'AI assistant is disabled',
            'system_info' => [
                'version' => $config['version'] ?? 'Unknown',
                'company' => $config['CompanyName'] ?? 'PHPNuxBill'
            ]
        ];

        showResult(true, 'PHPNuxBill Help System', $help_info);
}