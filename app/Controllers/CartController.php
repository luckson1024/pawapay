<?php
// --- FILE: app/Controllers/CartController.php ---

namespace App\Controllers;

use Myzuwa\PawaPay\PawaPay;
use Myzuwa\PawaPay\Exception\PaymentGatewayException;
use Myzuwa\PawaPay\Service\PhoneValidationService;
use Myzuwa\PawaPay\Support\LogManager;
use Myzuwa\PawaPay\Support\InputHelper;
use Myzuwa\PawaPay\Support\DatabaseHelper;

class CartController
{
    /** @var LogManager */
    private $logger;

    public function __construct()
    {
        $this->logger = new LogManager('cart');
    }

    /**
     * Send a JSON response
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * Render the PawaPay payment form
     */
    public function renderPaymentForm(array $data = [])
    {
        try {
            $operators = $this->getAvailableOperators();
            $viewData = array_merge($data, ['operators' => $operators]);
            extract($viewData);
            include __DIR__ . '/../Views/cart/payment_methods/_pawapay.php';
        } catch (\Exception $e) {
            $this->logger->error('Failed to render payment form', ['error' => $e->getMessage()]);
            echo "Unable to load payment form. Please try again later.";
        }
    }

    /**
     * Get the list of available mobile network operators for the view
     */
    private function getAvailableOperators()
    {
        $gateway = getPaymentGateway('pawapay');
        if (empty($gateway) || $gateway->status != 1) {
            return [];
        }

        try {
            $lib = new PawaPay((array)$gateway);
            $mnoService = new \Myzuwa\PawaPay\Service\MNOService($lib->getHttpClient());
            $operators = $mnoService->getAvailableOperators('ZMB');

            $formattedOperators = [];
            foreach ($operators as $operator) {
                $formattedOperators[$operator['code']] = [
                    'name' => $operator['name'],
                    'code' => $operator['code']
                ];
            }
            return $formattedOperators;
        } catch (\Exception $e) {
            log_system('Failed to fetch operators', ['error' => $e->getMessage()]);
            return [];
        }
    }
    /**
     * Handle payment submission
     */
    public function pawapayPaymentPost()
    {
        try {
            // Get payment gateway configuration
            $gateway = getPaymentGateway('pawapay');
            if (empty($gateway) || $gateway->status != 1) {
                $this->jsonResponse(['result' => 0, 'message' => 'Payment method not found or disabled!'], 400);
            }

            // Get and validate form input
            $netAmount = InputHelper::post('payment_amount');
            $currency = InputHelper::post('currency');
            $mdsPaymentType = InputHelper::post('mds_payment_type');
            $mdsPaymentToken = InputHelper::post('mds_payment_token');
            $msisdn = InputHelper::post('msisdn');

            // Validate required fields
            if (!$netAmount || !$currency || !$msisdn) {
                $this->jsonResponse([
                    'result' => 0,
                    'message' => 'Missing required fields. Please fill in all required information.'
                ], 400);
            }

            // Validate currency
            if ($currency !== 'ZMW') {
                $this->jsonResponse([
                    'result' => 0,
                    'message' => 'Unsupported currency! Only ZMW is allowed.'
                ], 400);
            }

            // Initialize services
            $lib = new PawaPay((array)$gateway);
            $phoneValidationService = new PhoneValidationService($lib->getHttpClient());

            // Validate phone number
            try {
                $validationResult = $phoneValidationService->validateAndPredictProvider($msisdn);
                
                if (!$validationResult['isValid']) {
                    $errorCode = isset($validationResult['errorCode']) ? $validationResult['errorCode'] : '';
                    $errorMessage = $this->getValidationErrorMessage($errorCode, $validationResult['message']);
                    $this->jsonResponse(['result' => 0, 'message' => $errorMessage], 400);
                }
                
                $provider = $validationResult['provider'];
                $sanitizedPhone = $validationResult['phoneNumber'];
            } catch (\Exception $e) {
                $this->logger->error('Phone validation failed', [
                    'error' => $e->getMessage(),
                    'phone' => $msisdn
                ]);
                
                $this->jsonResponse([
                    'result' => 0,
                    'message' => 'Unable to validate phone number. Please try again.'
                ], 400);
            }

            // Prepare deposit payload
            $depositId = generate_unique_id();
            $body = [
                'depositId' => $depositId,
                'amount' => (string)$netAmount,
                'currency' => $currency,
                'payer' => [
                    'type' => 'MMO',
                    'accountDetails' => [
                        'provider' => $provider,
                        'phoneNumber' => $sanitizedPhone
                    ]
                ]
            ];

            // Initiate deposit
            $response = $lib->initiateDeposit($body);

            if ($response && ($response['status'] ?? null) === 'ACCEPTED') {
                // Store transaction details
                try {
                    DatabaseHelper::insert('pending_payments', [
                        'deposit_id' => $depositId,
                        'payment_token' => $mdsPaymentToken,
                        'payment_type' => $mdsPaymentType,
                        'currency' => $currency,
                        'payment_amount' => $netAmount,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error('Failed to store payment details', [
                        'error' => $e->getMessage(),
                        'deposit_id' => $depositId
                    ]);
                }

                $this->logger->info('PawaPay deposit initiated', [
                    'deposit_id' => $depositId,
                    'amount' => $netAmount
                ]);

                $this->jsonResponse([
                    'result' => 1,
                    'message' => 'Payment initiated. Please check your phone to approve.'
                ]);
            } else {
                $errorMessage = $response['failureReason']['failureMessage'] ?? 'Failed to initiate payment. Please try again.';
                $this->logger->error('PawaPay deposit failed', ['error' => $response]);
                $this->jsonResponse(['result' => 0, 'message' => $errorMessage], 400);
            }

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->jsonResponse([
                'result' => 0,
                'message' => 'An unexpected error occurred. Please try again later.'
            ], 500);
        }
    }

    /**
     * Handles the asynchronous webhook notification from PawaPay.
     * This is the final confirmation of the payment status.
     */
    /**
     * AJAX endpoint for predicting the mobile operator from a phone number
     */
    /**
     * Predict operator from phone number
     */
    public function predictOperator()
    {
        try {
            $msisdn = InputHelper::get('phone');
            
            if (empty($msisdn)) {
                $this->jsonResponse(['success' => false, 'error' => 'Phone number is required'], 400);
            }

            $gateway = getPaymentGateway('pawapay');
            if (empty($gateway) || $gateway->status != 1) {
                $this->jsonResponse(['success' => false, 'error' => 'Payment method not available'], 400);
            }

            $lib = new PawaPay((array)$gateway);
            $phoneValidationService = new PhoneValidationService($lib->getHttpClient());
            
            $result = $phoneValidationService->validateAndPredictProvider($msisdn);
            
            if ($result['isValid']) {
                $this->jsonResponse([
                    'success' => true,
                    'provider' => [
                        'code' => $result['provider'],
                        'phoneNumber' => $result['phoneNumber']
                    ]
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => $result['message']
                ], 400);
            }

        } catch (\Exception $e) {
            $this->logger->error('Operator prediction failed', [
                'error' => $e->getMessage(),
                'phone' => $msisdn ?? null
            ]);
            $this->jsonResponse([
                'success' => false,
                'error' => 'Unable to predict operator'
            ], 500);
        }
    }

    /**
     * Get validation error message
     */
    private function getValidationErrorMessage($errorCode, $defaultMessage)
    {
        $messages = [
            'INVALID_LENGTH' => 'Phone number length is incorrect for Zambia. Please enter 10 digits after the country code.',
            'INVALID_PREFIX' => 'Invalid mobile network prefix. Please check your number.',
            'UNSUPPORTED_COUNTRY' => 'Only Zambian phone numbers are supported at this time.',
            'PROVIDER_NOT_FOUND' => 'Unable to determine your mobile money provider. Please check the number.'
        ];

        return $messages[$errorCode] ?? $defaultMessage ?? 'Invalid phone number format!';
    }

    /**
     * Handle PawaPay webhook notifications
     * Processes payment completion and updates Modesy database
     */
    public function pawapayWebhook()
    {
        try {
            $requestBody = file_get_contents('php://input');

            $gateway = getPaymentGateway('pawapay');
            if (empty($gateway) || $gateway->status != 1) {
                $this->jsonResponse(['error' => 'Payment gateway not configured'], 500);
            }

            $lib = new PawaPay((array)$gateway);

            $data = json_decode($requestBody, true);
            if (!$data) {
                $this->logger->error('Invalid JSON payload');
                $this->jsonResponse(['error' => 'Invalid JSON payload'], 400);
            }

            // Verify webhook signature (CRITICAL SECURITY)
            $signature = $_SERVER['HTTP_X_PAWAPAY_SIGNATURE'] ?? '';
            if (!is_array($data)) {
                $this->logger->error('Malformed webhook payload: not an array');
                $this->jsonResponse(['error' => 'Invalid payload format'], 400);
            }

            if (!$lib->verifyWebhookSignature($data, $signature)) {
                $this->logger->error('Invalid webhook signature');
                $this->jsonResponse(['error' => 'Invalid signature'], 401);
            }

            // Validate required webhook data
            if (!isset($data['depositId'], $data['status'])) {
                $this->logger->error('Invalid webhook payload', ['data' => $data]);
                $this->jsonResponse(['error' => 'Invalid payload'], 400);
            }

            $depositId = $data['depositId'];

            // Find pending payment record
            $pendingPayment = $this->getPendingPaymentByDepositId($depositId);
            if (!$pendingPayment) {
                $this->logger->error('Pending payment not found', ['deposit_id' => $depositId]);
                $this->jsonResponse(['error' => 'Payment record not found'], 404);
            }

            // Process webhook based on status
            switch ($data['status']) {
                case 'COMPLETED':
                    $this->processCompletedPayment($data, $pendingPayment);
                    break;

                case 'FAILED':
                    $this->processFailedPayment($data, $pendingPayment);
                    break;

                case 'IN_RECONCILIATION':
                    // Payment is under reconciliation - wait for final status
                    $this->updatePaymentStatus($pendingPayment->id, 'in_reconciliation', $data['status']);
                    $this->logger->info('Payment in reconciliation', [
                        'deposit_id' => $depositId,
                        'pending_payment_id' => $pendingPayment->id
                    ]);
                    break;

                case 'PROCESSING':
                    // Payment still processing
                    $this->updatePaymentStatus($pendingPayment->id, 'processing', $data['status']);
                    break;

                default:
                    $this->logger->warning('Unhandled webhook status', [
                        'deposit_id' => $depositId,
                        'status' => $data['status']
                    ]);
            }

            $this->jsonResponse(['message' => 'Webhook processed successfully'], 200);

        } catch (\Exception $e) {
            $this->logger->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->jsonResponse(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Process completed payment - update Modesy database
     */
    private function processCompletedPayment(array $data, object $pendingPayment): void
    {
        $depositId = $data['depositId'];

        try {
            // Update pending payment status
            $this->updatePaymentStatus($pendingPayment->id, 'completed', $data['status']);

            // Update main order payment status
            $result = $this->updateOrderPaymentStatus($pendingPayment->payment_token, $depositId, 'completed', $pendingPayment);

            // Handle different payment types
            if ($pendingPayment->payment_type === 'membership') {
                $this->activateMembership($pendingPayment->payment_token);
            }

            // Log success
            $this->logger->info('Payment completed successfully', [
                'deposit_id' => $depositId,
                'payment_token' => $pendingPayment->payment_token,
                'payment_type' => $pendingPayment->payment_type
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to process completed payment', [
                'deposit_id' => $depositId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process failed payment
     */
    private function processFailedPayment(array $data, object $pendingPayment): void
    {
        $depositId = $data['depositId'];
        $failureReason = $data['failureReason']['failureMessage'] ?? 'Unknown reason';
        $failureCode = $data['failureReason']['failureCode'] ?? 'UNKNOWN_ERROR';

        try {
            // Update pending payment status with failure
            $this->updatePaymentStatus($pendingPayment->id, 'failed', $data['status'], $failureReason);

            // Update main order payment status
            $this->updateOrderPaymentStatus($pendingPayment->payment_token, $depositId, 'failed', $pendingPayment);

            $this->logger->error('Payment failed', [
                'deposit_id' => $depositId,
                'failure_code' => $failureCode,
                'failure_reason' => $failureReason
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to process payment failure', [
                'deposit_id' => $depositId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get pending payment by deposit ID
     */
    private function getPendingPaymentByDepositId(string $depositId)
    {
        return DatabaseHelper::fetch(
            "SELECT * FROM pending_payments WHERE deposit_id = ? LIMIT 1",
            [$depositId],
            true
        );
    }

    /**
     * Update pending payment status
     */
    private function updatePaymentStatus(int $id, string $internalStatus, string $pawaPayStatus, ?string $failureReason = null): void
    {
        $updatedAt = date('Y-m-d H:i:s');

        DatabaseHelper::update('pending_payments', [
            'internal_status' => $internalStatus,
            'pawapay_status' => $pawaPayStatus,
            'failure_reason' => $failureReason,
            'updated_at' => $updatedAt
        ], ['id' => $id]);
    }

    /**
     * Update order payment status in Modesy
     */
    private function updateOrderPaymentStatus(string $paymentToken, string $depositId, string $status, object $pendingPayment): bool
    {
        $paymentStatus = $status === 'completed' ? 'received' : 'failed';
        $orderStatus = $status === 'completed' ? 1 : 0;
        $datePayment = date('Y-m-d H:i:s');

        try {
            // Update orders table
            $affectedRows = DatabaseHelper::update('orders', [
                'payment_status' => $paymentStatus,
                'payment_method' => 'pawapay',
                'payment_id' => $depositId,
                'date_payment' => $datePayment,
                'status' => $orderStatus
            ], ['option_unique_code' => $paymentToken]);

            if ($affectedRows > 0) {
                // Insert transaction record
                $orderId = $this->getOrderIdByToken($paymentToken);
                if ($orderId) {
                    DatabaseHelper::insert('order_transactions', [
                        'order_id' => $orderId,
                        'payment_method' => 'pawapay',
                        'payment_id' => $depositId,
                        'amount' => $pendingPayment->payment_amount,
                        'currency' => $pendingPayment->currency,
                        'status' => $orderStatus,
                        'created_at' => $datePayment
                    ]);
                }
            }

            return $affectedRows > 0;

        } catch (\Exception $e) {
            $this->logger->error('Failed to update order status', [
                'payment_token' => $paymentToken,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Activate membership after successful payment
     */
    private function activateMembership(string $paymentToken): void
    {
        try {
            $activatedAt = date('Y-m-d H:i:s');

            DatabaseHelper::update('membership_payments', [
                'payment_status' => 'paid',
                'activated_at' => $activatedAt
            ], ['payment_token' => $paymentToken]);

            $this->logger->info('Membership activated', ['payment_token' => $paymentToken]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to activate membership', [
                'payment_token' => $paymentToken,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Helper to get order ID by payment token
     */
    private function getOrderIdByToken(string $token)
    {
        $result = DatabaseHelper::fetch(
            "SELECT id FROM orders WHERE option_unique_code = ? LIMIT 1",
            [$token]
        );

        return $result ? $result['id'] : null;
    }
}
