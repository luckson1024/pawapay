<?php
// --- STANDALONE PAWAPAY TEST INTERFACE ---
// Load only what we need for testing
require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';

// --- SIMPLE ROUTER ---
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$route = '/';
if (substr($requestUri, 0, strlen('/pawapay-v2-integration')) == '/pawapay-v2-integration') {
    $route = substr($requestUri, strlen('/pawapay-v2-integration'));
}

// Handle API routes
if ($route === '/cart/pawapay-payment-post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePaymentPost();
}
else if ($route === '/cart/predict-operator' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    handleOperatorPrediction();
}
else if ($route === '/webhook/pawapay' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleWebhook();
}
else {
    // Display the main test interface
    displayTestInterface();
}

/**
 * Handle payment form submission
 */
function handlePaymentPost() {
    try {
        // Get form data
        $phoneNumber = $_POST['phone_number'] ?? '';
        $amount = $_POST['amount'] ?? '10.00';
        $operator = $_POST['operator'] ?? '';

        if (empty($phoneNumber) || empty($operator)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Phone number and operator are required']);
            exit;
        }

        // Load PawaPay configuration from config file
        $config = require __DIR__ . '/config/pawapay.php';

        // Create PawaPay instance with proper config structure
        $pawaPay = new Myzuwa\PawaPay\PawaPay([
            'api' => [
                'token' => $config['api']['token'],
                'base_url' => $config['api']['base_url'][$config['api']['environment']] ?? 'https://api.sandbox.pawapay.io'
            ],
            'webhook_secret' => $config['webhooks']['secret'],
            'environment' => $config['api']['environment']
        ]);

        // Prepare deposit data
        $depositId = 'TEST-' . uniqid();
        $depositData = [
            'depositId' => $depositId,
            'amount' => $amount,
            'currency' => 'ZMW',
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'provider' => $operator,
                    'phoneNumber' => $phoneNumber
                ]
            ]
        ];

        // Attempt to initiate deposit
        $response = $pawaPay->initiateDeposit($depositData);

        if ($response && isset($response['status']) && $response['status'] === 'ACCEPTED') {
            echo json_encode([
                'success' => true,
                'message' => 'Payment initiated successfully!',
                'deposit_id' => $depositId,
                'response' => $response
            ]);
        } else {
            $errorMessage = $response['failureReason']['failureMessage'] ?? 'Unknown error occurred';
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Payment initiation failed: ' . $errorMessage,
                'response' => $response
            ]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Exception occurred: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Handle operator prediction
 */
function handleOperatorPrediction() {
    try {
        $phone = $_GET['phone'] ?? '';

        if (empty($phone)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Phone number is required']);
            exit;
        }

        // Load PawaPay configuration from config file
        $config = require __DIR__ . '/config/pawapay.php';

        // Create PawaPay instance with proper config structure
        $pawaPay = new Myzuwa\PawaPay\PawaPay([
            'api' => [
                'token' => $config['api']['token'],
                'base_url' => $config['api']['base_url'][$config['api']['environment']] ?? 'https://api.sandbox.pawapay.io'
            ],
            'webhook_secret' => $config['webhooks']['secret'],
            'environment' => $config['api']['environment']
        ]);
        $phoneValidationService = new Myzuwa\PawaPay\Service\PhoneValidationService($pawaPay->getHttpClient());

        $result = $phoneValidationService->validateAndPredictProvider($phone);

        if ($result['isValid']) {
            echo json_encode([
                'success' => true,
                'provider' => [
                    'code' => $result['provider'],
                    'phoneNumber' => $result['phoneNumber']
                ]
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $result['message']
            ]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Unable to predict operator: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Handle webhook
 */
function handleWebhook() {
    try {
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody, true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            exit;
        }

        // For testing, just log the webhook and return success
        $logFile = 'logs/webhook_test.log';
        $logEntry = date('Y-m-d H:i:s') . ' - Webhook received: ' . json_encode($data) . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        echo json_encode(['message' => 'Webhook processed successfully']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Webhook processing failed: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Display the main test interface
 */
function displayTestInterface() {
    // Get available operators
    $operators = getAvailableOperators();
    $testResult = null;

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $testResult = handleTestPayment();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawaPay Payment Test</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .button {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .button:hover {
            background: #0056b3;
        }
        .result {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .success {
            background: #d4edda;
            border: #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: #f5c6cb;
            color: #721c24;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b8daff;
            color: #004085;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .test-phone {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PawaPay Standalone Payment Test</h1>
        <p>Test the PawaPay payment flow in isolation without requiring the full Modesy framework.</p>

        <div class="info-box">
            <strong>Test Information:</strong>
            <ul>
                <li>Use the sandbox environment for testing</li>
                <li>Test phone number: <code>260763456789</code></li>
                <li>Minimum amount: 1 ZMW</li>
                <li>Maximum amount: 20,000 ZMW</li>
            </ul>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="phone_number">Phone Number:</label>
                <input type="tel"
                       id="phone_number"
                       name="phone_number"
                       value=""
                       placeholder="260763456789"
                       pattern="^260[0-9]{9}$"
                       required>
                <small>Enter a Zambian phone number starting with 260</small>
            </div>

            <div class="form-group">
                <label for="operator">Mobile Network Operator:</label>
                <select id="operator" name="operator" required>
                    <option value="">Select operator...</option>
                    <?php foreach ($operators as $code => $name): ?>
                        <option value="<?= htmlspecialchars($code) ?>">
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="amount">Amount (ZMW):</label>
                <input type="number"
                       id="amount"
                       name="amount"
                       value="10.00"
                       min="1"
                       max="20000"
                       step="0.01"
                       required>
                <small>Amount in Zambian Kwacha (1 - 20,000 ZMW)</small>
            </div>

            <button type="submit" class="button">Test Payment</button>
        </form>

        <?php if ($testResult): ?>
            <div class="result <?= $testResult['success'] ? 'success' : 'error' ?>"
                 style="display: block;">
                <h3><?= $testResult['success'] ? '✅ Success' : '❌ Failed' ?></h3>
                <p><strong>Message:</strong> <?= htmlspecialchars($testResult['message']) ?></p>

                <?php if (isset($testResult['deposit_id'])): ?>
                    <p><strong>Deposit ID:</strong> <code><?= htmlspecialchars($testResult['deposit_id']) ?></code></p>
                <?php endif; ?>

                <?php if (isset($testResult['response'])): ?>
                    <details>
                        <summary>Full Response</summary>
                        <pre><?= htmlspecialchars(json_encode($testResult['response'], JSON_PRETTY_PRINT)) ?></pre>
                    </details>
                <?php endif; ?>

                <?php if (isset($testResult['trace'])): ?>
                    <details>
                        <summary>Error Trace</summary>
                        <pre><?= htmlspecialchars($testResult['trace']) ?></pre>
                    </details>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="test-phone">
            <strong>Test Phone Numbers:</strong><br>
            MTN: 260763456789<br>
            Airtel: 260976123456<br>
            Zamtel: 260951234567
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="tests/test_config.php" class="button">← Configuration Test</a>
            <a href="tests/test_webhook.php" class="button">Webhook Test →</a>
        </div>
    </div>

    <script>
        // Auto-detect operator based on phone number
        document.getElementById('phone_number').addEventListener('input', function() {
            const phone = this.value;
            const operatorSelect = document.getElementById('operator');

            // Simple operator detection based on prefixes
            if (phone.startsWith('26076')) {
                operatorSelect.value = 'MTN_MOMO_ZMB';
            } else if (phone.startsWith('26097')) {
                operatorSelect.value = 'AIRTEL_OAPI_ZMB';
            } else if (phone.startsWith('26095')) {
                operatorSelect.value = 'ZAMTEL_MOMO_ZMB';
            }
        });
    </script>
</body>
</html>
<?php
}

/**
 * Handle test payment submission
 */
function handleTestPayment() {
    try {
        $phoneNumber = $_POST['phone_number'] ?? '';
        $amount = $_POST['amount'] ?? '10.00';
        $operator = $_POST['operator'] ?? '';

        if (empty($phoneNumber) || empty($operator)) {
            return [
                'success' => false,
                'message' => 'Phone number and operator are required'
            ];
        }

        // Load PawaPay configuration from config file
        $config = require __DIR__ . '/config/pawapay.php';

        // Create PawaPay instance with proper config structure
        $pawaPay = new Myzuwa\PawaPay\PawaPay([
            'api' => [
                'token' => $config['api']['token'],
                'base_url' => $config['api']['base_url'][$config['api']['environment']] ?? 'https://api.sandbox.pawapay.io'
            ],
            'webhook_secret' => $config['webhooks']['secret'],
            'environment' => $config['api']['environment']
        ]);

        // Prepare deposit data
        $depositId = 'TEST-' . uniqid();
        $depositData = [
            'depositId' => $depositId,
            'amount' => $amount,
            'currency' => 'ZMW',
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'provider' => $operator,
                    'phoneNumber' => $phoneNumber
                ]
            ]
        ];

        // Attempt to initiate deposit
        $response = $pawaPay->initiateDeposit($depositData);

        if ($response && isset($response['status']) && $response['status'] === 'ACCEPTED') {
            return [
                'success' => true,
                'message' => 'Payment initiated successfully!',
                'deposit_id' => $depositId,
                'response' => $response
            ];
        } else {
            $errorMessage = $response['failureReason']['failureMessage'] ?? 'Unknown error occurred';
            return [
                'success' => false,
                'message' => 'Payment initiation failed: ' . $errorMessage,
                'response' => $response
            ];
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Exception occurred: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

/**
 * Get available operators for dropdown
 */
function getAvailableOperators() {
    try {
        // Load PawaPay configuration from config file
        $config = require __DIR__ . '/config/pawapay.php';

        // Create PawaPay instance with proper config structure
        $pawaPay = new Myzuwa\PawaPay\PawaPay([
            'api' => [
                'token' => $config['api']['token'],
                'base_url' => $config['api']['base_url'][$config['api']['environment']] ?? 'https://api.sandbox.pawapay.io'
            ],
            'webhook_secret' => $config['webhooks']['secret'],
            'environment' => $config['api']['environment']
        ]);

        $mnoService = new Myzuwa\PawaPay\Service\MNOService($pawaPay->getHttpClient());
        $operatorsData = $mnoService->getAvailableOperators('ZMB');

        $operators = [];
        foreach ($operatorsData as $operator) {
            $operators[$operator['code']] = $operator['name'];
        }
        return $operators;
    } catch (Exception $e) {
        // Use fallback operators if API fails
        return [
            'MTN_MOMO_ZMB' => 'MTN Mobile Money',
            'AIRTEL_OAPI_ZMB' => 'Airtel Money',
            'ZAMTEL_MOMO_ZMB' => 'Zamtel Money'
        ];
    }
}
?>
