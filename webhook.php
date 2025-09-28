<?php
require_once __DIR__ . '/vendor/autoload.php';

use Myzuwa\PawaPay\PawaPay;
use Myzuwa\PawaPay\Controller\WebhookController;

// Load configuration
$config = require __DIR__ . '/tests/config/test_config.php';

// Initialize PawaPay SDK
$pawaPay = new PawaPay($config);

// Initialize webhook controller
$webhookController = new WebhookController($pawaPay, $config);

// Handle the webhook
$result = $webhookController->handleCallback();

// Send response
header('Content-Type: application/json');
echo json_encode($result);