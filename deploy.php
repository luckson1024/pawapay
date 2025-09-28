<?php
/**
 * Production Deployment Script
 * 
 * This script performs necessary checks and setup for production deployment
 */

require_once __DIR__ . '/vendor/autoload.php';

class ProductionDeployment
{
    private $errors = [];
    private $warnings = [];

    public function run()
    {
        $this->checkEnvironment()
             ->checkDatabaseConnection()
             ->checkApiConnection()
             ->checkSecuritySettings()
             ->optimizeAutoloader()
             ->clearCaches()
             ->runMigrations()
             ->verifyWebhooks();

        $this->displayResults();
    }

    private function checkEnvironment()
    {
        echo "Checking environment configuration...\n";

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $this->errors[] = "PHP version must be at least 7.4.0. Current version: " . PHP_VERSION;
        }

        // Check required extensions
        $required_extensions = ['json', 'curl', 'mbstring', 'openssl', 'bcmath', 'pdo_mysql'];
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $this->errors[] = "Required PHP extension not loaded: {$ext}";
            }
        }

        // Check environment variables
        $required_env = [
            'PAWAPAY_API_TOKEN',
            'PAWAPAY_ENVIRONMENT',
            'PAWAPAY_WEBHOOK_SECRET',
            'DB_HOST',
            'DB_DATABASE',
            'DB_USERNAME'
        ];

        foreach ($required_env as $env) {
            if (empty(getenv($env))) {
                $this->errors[] = "Required environment variable not set: {$env}";
            }
        }

        // Check production settings
        if (getenv('APP_ENV') !== 'production') {
            $this->warnings[] = "APP_ENV is not set to 'production'";
        }

        if (getenv('APP_DEBUG') === 'true') {
            $this->warnings[] = "APP_DEBUG is enabled in production";
        }

        return $this;
    }

    private function checkDatabaseConnection()
    {
        echo "Verifying database connection...\n";
        
        try {
            $config = require __DIR__ . '/config/database.php';
            $db = $config['default'];
            
            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $db['driver'],
                $db['host'],
                $db['port'],
                $db['database'],
                $db['charset']
            );
            
            new PDO($dsn, $db['username'], $db['password'], $db['options']);
        } catch (\Exception $e) {
            $this->errors[] = "Database connection failed: " . $e->getMessage();
        }

        return $this;
    }

    private function checkApiConnection()
    {
        echo "Testing API connection...\n";
        
        try {
            $pawaPay = new \Myzuwa\PawaPay\PawaPay([
                'api' => [
                    'token' => getenv('PAWAPAY_API_TOKEN'),
                    'base_url' => getenv('PAWAPAY_ENVIRONMENT') === 'production' 
                        ? 'https://api.pawapay.io' 
                        : 'https://api.sandbox.pawapay.io'
                ]
            ]);

            $response = $pawaPay->getAvailableOperators('ZMB');
            if (!$response) {
                throw new \Exception("Invalid API response");
            }
        } catch (\Exception $e) {
            $this->errors[] = "API connection failed: " . $e->getMessage();
        }

        return $this;
    }

    private function checkSecuritySettings()
    {
        echo "Checking security settings...\n";

        // Check SSL/TLS settings
        if (empty($_SERVER['HTTPS']) && getenv('APP_ENV') === 'production') {
            $this->errors[] = "HTTPS is not enabled";
        }

        // Check file permissions
        $paths_to_check = [
            __DIR__ . '/.env',
            __DIR__ . '/config',
            __DIR__ . '/logs'
        ];

        foreach ($paths_to_check as $path) {
            if (file_exists($path)) {
                $perms = fileperms($path) & 0777;
                if ($perms > 0755) {
                    $this->warnings[] = "Insecure permissions on {$path}: " . decoct($perms);
                }
            }
        }

        return $this;
    }

    private function optimizeAutoloader()
    {
        echo "Optimizing autoloader...\n";
        
        exec('php composer.phar dump-autoload -o', $output, $return_var);
        if ($return_var !== 0) {
            $this->errors[] = "Autoloader optimization failed";
        }

        return $this;
    }

    private function clearCaches()
    {
        echo "Clearing caches...\n";
        
        // Clear OPcache if enabled
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        // Clear application cache files
        $cache_dir = __DIR__ . '/cache';
        if (is_dir($cache_dir)) {
            array_map('unlink', glob("{$cache_dir}/*"));
        }

        return $this;
    }

    private function runMigrations()
    {
        echo "Running database migrations...\n";
        
        try {
            require_once __DIR__ . '/database/MigrationRunner.php';
            $config = require __DIR__ . '/config/database.php';
            $db = $config['default'];
            
            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $db['driver'],
                $db['host'],
                $db['port'],
                $db['database'],
                $db['charset']
            );
            
            $pdo = new PDO($dsn, $db['username'], $db['password'], $db['options']);
            $runner = new MigrationRunner($pdo);
            $runner->migrate();
        } catch (\Exception $e) {
            $this->errors[] = "Migration failed: " . $e->getMessage();
        }

        return $this;
    }

    private function verifyWebhooks()
    {
        echo "Verifying webhook configuration...\n";

        if (empty(getenv('PAWAPAY_WEBHOOK_SECRET'))) {
            $this->errors[] = "Webhook secret not configured";
        }

        // Additional webhook endpoint checks could be added here

        return $this;
    }

    private function displayResults()
    {
        echo "\nDeployment Check Results:\n";
        echo "------------------------\n";

        if (!empty($this->errors)) {
            echo "\nErrors (must be fixed):\n";
            foreach ($this->errors as $error) {
                echo "❌ {$error}\n";
            }
        }

        if (!empty($this->warnings)) {
            echo "\nWarnings (should be reviewed):\n";
            foreach ($this->warnings as $warning) {
                echo "⚠️ {$warning}\n";
            }
        }

        if (empty($this->errors) && empty($this->warnings)) {
            echo "✅ All checks passed successfully!\n";
        } elseif (empty($this->errors)) {
            echo "\n⚠️ Deployment possible but has warnings\n";
        } else {
            echo "\n❌ Deployment blocked due to errors\n";
            exit(1);
        }
    }
}

// Run deployment checks
$deployment = new ProductionDeployment();
$deployment->run();
