<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'fallback_models' => array_values(array_filter(array_map(
            static fn (string $model): string => trim($model),
            explode(',', (string) env('GEMINI_FALLBACK_MODELS', 'gemini-1.5-flash,gemini-1.5-pro'))
        ))),
        'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 90),
        'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 15),
        'execution_time_limit' => (int) env('GEMINI_EXECUTION_TIME_LIMIT', 300),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'endpoint' => env('OPENAI_ENDPOINT', 'https://api.openai.com/v1/responses'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 90),
        'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 15),
        'execution_time_limit' => (int) env('OPENAI_EXECUTION_TIME_LIMIT', 300),
    ],

    'local_ocr' => [
        'enabled' => (bool) env('LOCAL_OCR_ENABLED', false),
        'strict' => (bool) env('LOCAL_OCR_STRICT', false),
        'endpoint' => env('LOCAL_OCR_ENDPOINT', 'http://127.0.0.1:8011'),
        'timeout' => (int) env('LOCAL_OCR_TIMEOUT', 120),
        'connect_timeout' => (int) env('LOCAL_OCR_CONNECT_TIMEOUT', 5),
    ],

    'chrome_devtools_mcp' => [
        'command' => env('CHROME_DEVTOOLS_MCP_COMMAND', 'npx'),
        'package' => env('CHROME_DEVTOOLS_MCP_PACKAGE', 'chrome-devtools-mcp@latest'),
        'arguments' => array_values(array_filter(array_map(
            static fn (string $argument): string => trim($argument),
            explode(',', (string) env('CHROME_DEVTOOLS_MCP_ARGUMENTS', '--headless,--isolated,--no-usage-statistics'))
        ), static fn (string $argument): bool => $argument !== '')),
        'protocol_version' => env('CHROME_DEVTOOLS_MCP_PROTOCOL_VERSION', '2025-06-18'),
        'startup_timeout' => (int) env('CHROME_DEVTOOLS_MCP_STARTUP_TIMEOUT', 45),
        'request_timeout' => (int) env('CHROME_DEVTOOLS_MCP_REQUEST_TIMEOUT', 30),
        'client_name' => env('CHROME_DEVTOOLS_MCP_CLIENT_NAME', 'gacov-inventarios'),
        'client_version' => env('CHROME_DEVTOOLS_MCP_CLIENT_VERSION', '1.0.0'),
        'knowledge_path' => env('CHROME_DEVTOOLS_MCP_KNOWLEDGE_PATH', 'ai/chrome-devtools-mcp'),
        'repository' => [
            'url' => env('CHROME_DEVTOOLS_MCP_REPOSITORY_URL', 'https://github.com/ChromeDevTools/chrome-devtools-mcp'),
            'branch' => env('CHROME_DEVTOOLS_MCP_REPOSITORY_BRANCH', 'main'),
            'timeout' => (int) env('CHROME_DEVTOOLS_MCP_REPOSITORY_TIMEOUT', 20),
            'connect_timeout' => (int) env('CHROME_DEVTOOLS_MCP_REPOSITORY_CONNECT_TIMEOUT', 10),
        ],
    ],

];
