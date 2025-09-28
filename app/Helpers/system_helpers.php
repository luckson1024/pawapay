<?php
/**
 * System Helper Functions for Modesy Integration
 *
 * This file contains helper functions that bridge the gap between
 * the independent PawaPay SDK and the Modesy platform.
 *
 * @package App\Helpers
 * @version 1.0.0
 */

/**
 * Get payment gateway configuration
 *
 * @param string $gateway Name of the payment gateway
 * @return object|null Payment gateway configuration object
 */
function getPaymentGateway(string $gateway)
{
    try {
        // Load configuration from database or cache
        // This is a placeholder implementation
        // In a real Modesy system, this would query the payment_gateways table

        $config = [
            'pawapay' => (object)[
                'name' => 'PawaPay',
                'name_key' => 'pawapay',
                'public_key' => env('PAWAPAY_API_TOKEN'),
                'secret_key' => env('PAWAPAY_SECRET_KEY'),
                'webhook_secret' => env('PAWAPAY_WEBHOOK_SECRET'),
                'environment' => env('PAWAPAY_ENVIRONMENT', 'sandbox'),
                'status' => 1,
                'logos' => 'pawapay'
            ]
        ];

        return $config[$gateway] ?? null;
    } catch (\Exception $e) {
        error_log("Failed to load payment gateway configuration: " . $e->getMessage());
        return null;
    }
}

/**
 * Generate a unique ID for transactions
 *
 * @param string $prefix Optional prefix for the ID
 * @return string Unique ID
 */
function generate_unique_id(string $prefix = ''): string
{
    try {
        // Generate a UUID v4
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set bits 6-7 to 10

        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        return $prefix ? $prefix . '-' . $uuid : $uuid;
    } catch (\Exception $e) {
        // Fallback to timestamp-based ID if random_bytes fails
        return $prefix . uniqid(time() . '-', true);
    }
}

/**
 * System logging function
 *
 * @param string $message Log message
 * @param array $context Additional context data
 * @return void
 */
function log_system(string $message, array $context = []): void
{
    try {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'context' => $context,
            'level' => 'info'
        ];

        // Determine log level based on message content
        if (stripos($message, 'error') !== false || stripos($message, 'failed') !== false) {
            $logEntry['level'] = 'error';
        } elseif (stripos($message, 'warning') !== false) {
            $logEntry['level'] = 'warning';
        }

        // Log to file
        $logFile = __DIR__ . '/../storage/logs/system.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logLine = json_encode($logEntry) . PHP_EOL;
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

        // Also log to PHP error log for immediate visibility
        error_log("PawaPay System: {$message}");

    } catch (\Exception $e) {
        // Fallback to error_log if file logging fails
        error_log("PawaPay System Log Error: " . $e->getMessage());
        error_log("PawaPay System: {$message}");
    }
}

/**
 * Get storage path for the application (alias for bootstrap function)
 *
 * @param string $path Optional path to append
 * @return string Storage path with optional path
 */
function app_storage_path(string $path = ''): string
{
    $storagePath = __DIR__ . '/../storage';

    if (!empty($path)) {
        $storagePath .= '/' . ltrim($path, '/');
    }

    return $storagePath;
}

/**
 * Check if application is running in production
 *
 * @return bool
 */
function is_production(): bool
{
    return env('APP_ENV') === 'production';
}

/**
 * Check if application is running in sandbox/development
 *
 * @return bool
 */
function is_sandbox(): bool
{
    return env('APP_ENV') !== 'production';
}

/**
 * Format currency amount
 *
 * @param float $amount
 * @param string $currency
 * @return string Formatted currency string
 */
function format_currency(float $amount, string $currency = 'ZMW'): string
{
    $formatters = [
        'ZMW' => 'ZMW %01.2f',
        'USD' => '$%01.2f',
        'EUR' => '€%01.2f'
    ];

    $formatter = $formatters[$currency] ?? '%01.2f ' . $currency;
    return sprintf($formatter, $amount);
}

/**
 * Sanitize phone number for PawaPay
 *
 * @param string $phoneNumber
 * @return string Sanitized phone number
 */
function sanitize_phone_number(string $phoneNumber): string
{
    // Remove all non-numeric characters
    $sanitized = preg_replace('/[^0-9]/', '', $phoneNumber);

    // Ensure it starts with country code (260 for Zambia)
    if (strlen($sanitized) === 9) {
        $sanitized = '260' . $sanitized;
    } elseif (strlen($sanitized) === 12 && substr($sanitized, 0, 3) !== '260') {
        $sanitized = '260' . substr($sanitized, -9);
    }

    return $sanitized;
}

/**
 * Get client IP address
 *
 * @return string Client IP address
 */
function get_client_ip(): string
{
    $ipHeaders = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($ipHeaders as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];

            // Handle comma-separated IPs (like X-Forwarded-For)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }

            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Generate secure random string
 *
 * @param int $length Length of the string
 * @return string Random string
 */
function generate_random_string(int $length = 32): string
{
    try {
        $bytes = random_bytes(ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
    } catch (\Exception $e) {
        // Fallback for systems without random_bytes
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }
}

/**
 * Validate webhook signature (basic implementation)
 *
 * @param string $payload Raw payload data
 * @param string $signature Signature from header
 * @param string $secret Webhook secret
 * @return bool
 */
function validate_webhook_signature(string $payload, string $signature, string $secret): bool
{
    try {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    } catch (\Exception $e) {
        error_log("Webhook signature validation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Create HTTP response for API endpoints
 *
 * @param mixed $data Response data
 * @param int $statusCode HTTP status code
 * @param array $headers Additional headers
 * @return void
 */
function api_response($data, int $statusCode = 200, array $headers = []): void
{
    // Set status code
    http_response_code($statusCode);

    // Set default headers
    $defaultHeaders = [
        'Content-Type: application/json',
        'Access-Control-Allow-Origin: *',
        'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'
    ];

    // Set headers
    $allHeaders = array_merge($defaultHeaders, $headers);
    foreach ($allHeaders as $header) {
        header($header);
    }

    // Output JSON response
    echo json_encode([
        'success' => $statusCode >= 200 && $statusCode < 300,
        'status_code' => $statusCode,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    exit();
}

/**
 * Handle API errors consistently
 *
 * @param string $message Error message
 * @param int $statusCode HTTP status code
 * @param \Exception|null $exception Original exception
 * @return void
 */
function api_error(string $message, int $statusCode = 400, ?\Exception $exception = null): void
{
    $errorData = [
        'message' => $message,
        'error_code' => $statusCode
    ];

    if ($exception && env('APP_DEBUG', false)) {
        $errorData['debug'] = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
    }

    // Log error
    log_system("API Error: {$message}", [
        'status_code' => $statusCode,
        'exception' => $exception ? $exception->getMessage() : null
    ]);

    api_response($errorData, $statusCode);
}
