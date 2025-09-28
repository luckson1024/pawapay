<?php

/**
 * Configuration Validator
 * 
 * This script validates the configuration settings and environment variables
 * to ensure all required values are set and properly formatted.
 */

class ConfigValidator
{
    private $errors = [];
    private $warnings = [];

    /**
     * Run all configuration validations
     */
    public function validate()
    {
        $this->validateEnvironment()
             ->validateApiConfig()
             ->validateDatabaseConfig()
             ->validateLoggingConfig()
             ->validateSecurityConfig()
             ->validateWebhookConfig();

        return $this->getResults();
    }

    /**
     * Validate environment settings
     */
    private function validateEnvironment()
    {
        $env = getenv('APP_ENV');
        if (!in_array($env, ['development', 'production', 'testing'])) {
            $this->errors[] = "APP_ENV must be one of: development, production, testing";
        }

        if ($env === 'production' && getenv('APP_DEBUG') === 'true') {
            $this->warnings[] = "APP_DEBUG should be false in production";
        }

        return $this;
    }

    /**
     * Validate API configuration
     */
    private function validateApiConfig()
    {
        $token = getenv('PAWAPAY_API_TOKEN');
        if (empty($token)) {
            $this->errors[] = "PAWAPAY_API_TOKEN is required";
        }

        $env = getenv('PAWAPAY_ENVIRONMENT');
        if (!in_array($env, ['production', 'sandbox'])) {
            $this->errors[] = "PAWAPAY_ENVIRONMENT must be either 'production' or 'sandbox'";
        }

        $webhookSecret = getenv('PAWAPAY_WEBHOOK_SECRET');
        if (empty($webhookSecret)) {
            $this->errors[] = "PAWAPAY_WEBHOOK_SECRET is required";
        }

        return $this;
    }

    /**
     * Validate database configuration
     */
    private function validateDatabaseConfig()
    {
        $required = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME'];
        foreach ($required as $key) {
            if (empty(getenv($key))) {
                $this->errors[] = "{$key} is required";
            }
        }

        $port = getenv('DB_PORT');
        if (!empty($port) && !is_numeric($port)) {
            $this->errors[] = "DB_PORT must be numeric";
        }

        return $this;
    }

    /**
     * Validate logging configuration
     */
    private function validateLoggingConfig()
    {
        $logPath = getenv('LOG_PATH');
        if (!empty($logPath)) {
            if (!is_dir(dirname($logPath))) {
                $this->errors[] = "Log directory does not exist: " . dirname($logPath);
            } elseif (!is_writable(dirname($logPath))) {
                $this->errors[] = "Log directory is not writable: " . dirname($logPath);
            }
        }

        $logLevel = getenv('LOG_LEVEL');
        if (!empty($logLevel)) {
            $validLevels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
            if (!in_array(strtolower($logLevel), $validLevels)) {
                $this->errors[] = "Invalid LOG_LEVEL. Must be one of: " . implode(', ', $validLevels);
            }
        }

        return $this;
    }

    /**
     * Validate security configuration
     */
    private function validateSecurityConfig()
    {
        if (getenv('APP_ENV') === 'production') {
            if (getenv('HTTPS_ONLY') !== 'true') {
                $this->warnings[] = "HTTPS_ONLY should be true in production";
            }

            $origins = getenv('CORS_ALLOWED_ORIGINS');
            if ($origins === '*') {
                $this->warnings[] = "CORS_ALLOWED_ORIGINS should not be * in production";
            }
        }

        return $this;
    }

    /**
     * Validate webhook configuration
     */
    private function validateWebhookConfig()
    {
        $webhookUrl = getenv('WEBHOOK_URL');
        if (!empty($webhookUrl)) {
            if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
                $this->errors[] = "Invalid WEBHOOK_URL format";
            }

            $parsed = parse_url($webhookUrl);
            if (getenv('APP_ENV') === 'production' && (!isset($parsed['scheme']) || $parsed['scheme'] !== 'https')) {
                $this->errors[] = "WEBHOOK_URL must use HTTPS in production";
            }
        }

        $timeout = getenv('WEBHOOK_TIMEOUT');
        if (!empty($timeout) && (!is_numeric($timeout) || $timeout < 0)) {
            $this->errors[] = "WEBHOOK_TIMEOUT must be a positive number";
        }

        $retries = getenv('WEBHOOK_RETRIES');
        if (!empty($retries) && (!is_numeric($retries) || $retries < 0)) {
            $this->errors[] = "WEBHOOK_RETRIES must be a positive number";
        }

        return $this;
    }

    /**
     * Get validation results
     */
    public function getResults()
    {
        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'valid' => empty($this->errors)
        ];
    }
}

// Run the validation if the script is executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $validator = new ConfigValidator();
    $results = $validator->validate();

    if (!empty($results['errors'])) {
        echo "\nConfiguration Errors:\n";
        foreach ($results['errors'] as $error) {
            echo "❌ {$error}\n";
        }
    }

    if (!empty($results['warnings'])) {
        echo "\nConfiguration Warnings:\n";
        foreach ($results['warnings'] as $warning) {
            echo "⚠️ {$warning}\n";
        }
    }

    if ($results['valid'] && empty($results['warnings'])) {
        echo "✅ Configuration is valid!\n";
    } elseif ($results['valid']) {
        echo "⚠️ Configuration is valid but has warnings\n";
    } else {
        echo "❌ Configuration is invalid\n";
        exit(1);
    }
}