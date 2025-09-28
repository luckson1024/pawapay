<?php

// Load Composer's autoloader first
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
try {
    // Try multiple path resolution methods for robust .env file loading
    $possiblePaths = [
        __DIR__ . '/../',                                    // Relative to bootstrap.php
        getcwd(),                                           // Current working directory
        $_SERVER['DOCUMENT_ROOT'] . '/../',                 // Web root relative
        dirname(__DIR__, 2),                                // Project root
    ];

    $envPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path . '/.env')) {
            $envPath = $path;
            break;
        }
    }

    if ($envPath) {
        $dotenv = Dotenv\Dotenv::createImmutable($envPath);
        $dotenv->load();
        error_log("Environment variables loaded from: " . $envPath . '/.env');
    } else {
        error_log("Could not find .env file in any of the expected locations");
    }
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // Handle the case where .env file is not found
    error_log("Could not find .env file: " . $e->getMessage());
} catch (\Exception $e) {
    // Handle any other errors during environment loading
    error_log("Error loading environment variables: " . $e->getMessage());
}

// Define global function aliases for the namespaced functions
if (!function_exists('env')) {
    function env($key, $default = null) {
        return \Myzuwa\PawaPay\Support\env($key, $default);
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        return \Myzuwa\PawaPay\Support\base_url($path);
    }
}

if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return \Myzuwa\PawaPay\Support\storage_path($path);
    }
}
