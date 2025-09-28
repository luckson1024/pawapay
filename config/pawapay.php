<?php

// Import helper functions
use function Myzuwa\PawaPay\Support\env;
use function Myzuwa\PawaPay\Support\storage_path;

// Ensure bootstrap is loaded
require_once __DIR__ . '/bootstrap.php';

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the API connection details including environment, credentials,
    | and base URLs for different environments.
    |
    */
    'api' => [
        'environment' => env('PAWAPAY_ENVIRONMENT', 'sandbox'),
        'token' => env('PAWAPAY_API_TOKEN', 'eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJ0dCI6IkFBVCIsInN1YiI6IjEwMDc5IiwibWF2IjoiMSIsImV4cCI6MjA3MzcyMjE5NywiaWF0IjoxNzU4MTg5Mzk3LCJwbSI6IkRBRixQQUYiLCJqdGkiOiIxOWVlMTVjZS0zNDcyLTQ4NDItODZhYi0yMTEzOTY0NzA2MDkifQ.5V41KcLlRau7rox91WulAbPce9cAITjIUTE05oWSxo6SsXIoi3C5EN_4eC8X2KqkrS32-HdmPxxA8luzEY2bgw'),
        'base_url' => [
            'sandbox' => env('PAWAPAY_BASE_URL', 'https://api.sandbox.pawapay.io'),
            'production' => 'https://api.pawapay.io'
        ],
        'version' => 'v2',
        'timeout' => env('PAWAPAY_API_TIMEOUT', 30),
        'verify_ssl' => env('SSL_VERIFY', true)
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook handling including security settings, retry policy,
    | and callback URLs.
    |
    */
    'webhooks' => [
        'secret' => env('PAWAPAY_WEBHOOK_SECRET'),
        'tolerance' => env('PAWAPAY_WEBHOOK_TOLERANCE', 300), // 5 minutes
        'callback_url' => env('PAWAPAY_CALLBACK_URL'),
        'retries' => [
            'max_attempts' => env('PAWAPAY_WEBHOOK_MAX_RETRIES', 3),
            'delay' => env('PAWAPAY_WEBHOOK_RETRY_DELAY', 60) // seconds
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database connection and table settings for transaction
    | logging and status tracking.
    |
    */
    'database' => [
        'connection' => env('PAWAPAY_DB_CONNECTION', 'default'),
        'transactions_table' => 'pawapay_transactions',
        'logs_table' => 'pawapay_logs'
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging settings including channels, log levels,
    | and rotation policy.
    |
    */
    'logging' => [
        'enabled' => env('PAWAPAY_LOGGING_ENABLED', true),
        'channel' => env('PAWAPAY_LOG_CHANNEL', 'daily'),
        'level' => env('PAWAPAY_LOG_LEVEL', 'error'),
        'path' => storage_path('logs/pawapay.log'),
        'days' => env('PAWAPAY_LOG_DAYS', 7)
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security settings including request signing, encryption,
    | and rate limiting.
    |
    */
    'security' => [
        'sign_requests' => env('PAWAPAY_SIGN_REQUESTS', true),
        'encrypt_data' => env('PAWAPAY_ENCRYPT_DATA', true),
        'rate_limiting' => [
            'enabled' => env('PAWAPAY_RATE_LIMIT_ENABLED', true),
            'max_attempts' => env('PAWAPAY_RATE_LIMIT_MAX', 60),
            'decay_minutes' => env('PAWAPAY_RATE_LIMIT_DECAY', 1)
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching settings for API responses and other data.
    |
    */
    'cache' => [
        'enabled' => env('PAWAPAY_CACHE_ENABLED', true),
        'ttl' => env('PAWAPAY_CACHE_TTL', 3600),
        'prefix' => 'pawapay_'
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Configuration
    |--------------------------------------------------------------------------
    |
    | Configure transaction-specific settings including timeouts,
    | limits, and validation rules.
    |
    */
    'transactions' => [
        'timeout' => env('PAWAPAY_TRANSACTION_TIMEOUT', 60),
        'auto_confirm' => env('PAWAPAY_AUTO_CONFIRM', true),
        'limits' => [
            'min_amount' => env('PAWAPAY_MIN_AMOUNT', 1),
            'max_amount' => env('PAWAPAY_MAX_AMOUNT', 50000)
        ],
        'supported_currencies' => [
            'ZMW',
            'USD'
            // Add more as needed
        ]
    ]
];
