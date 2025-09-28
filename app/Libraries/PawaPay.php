<?php
namespace App\Libraries;

use Myzuwa\PawaPay\PawaPay as PawaPaySDK;
use Myzuwa\PawaPay\Exception\PaymentGatewayException;

/**
 * PawaPay Integration Library for Modesy
 *
 * This class serves as the integration layer between Modesy's payment system
 * and the independent PawaPay SDK. It handles configuration loading,
 * payment processing, and data transformation.
 *
 * @package App\Libraries
 * @version 1.0.0
 */
class PawaPay
{
    /** @var PawaPaySDK */
    private $sdk;

    /** @var object|array */
    private $config;

    /** @var \Myzuwa\PawaPay\Support\LogManager */
    private $logger;

    /**
     * Initialize PawaPay integration
     *
     * @param array|null $config Optional configuration override
     */
    public function __construct(?array $config = null)
    {
        $this->logger = new \Myzuwa\PawaPay\Support\LogManager('pawapay');

        try {
            // Load payment gateway configuration
            $this->config = $config ?? getPaymentGateway('pawapay');

            if (empty($this->config)) {
                throw new PaymentGatewayException('PawaPay gateway configuration not found');
            }

            if ($this->config->status != 1) {
                throw new PaymentGatewayException('PawaPay gateway is disabled');
            }

            // Transform Modesy config to SDK format
            $sdkConfig = $this->transformConfigForSDK($this->config);

            // Initialize SDK
            $this->sdk = new PawaPaySDK($sdkConfig);

        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize PawaPay integration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get available mobile network operators
     *
     * @param string $country ISO country code (default: ZMB)
     * @return array
     */
    public function getAvailableOperators(string $country = 'ZMB'): array
    {
        try {
            return $this->sdk->getAvailableOperators($country);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch operators', [
                'country' => $country,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Predict operator from phone number
     *
     * @param string $phoneNumber
     * @param string $country
     * @return array|null
     */
    public function predictOperator(string $phoneNumber, string $country = 'ZMB'): ?array
    {
        try {
            return $this->sdk->predictOperator($phoneNumber, $country);
        } catch (\Exception $e) {
            $this->logger->error('Failed to predict operator', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate phone number for specific operator
     *
     * @param string $phoneNumber
     * @param string $operatorCode
     * @return bool
     */
    public function validatePhoneNumber(string $phoneNumber, string $operatorCode): bool
    {
        try {
            return $this->sdk->validatePhoneNumber($phoneNumber, $operatorCode);
        } catch (\Exception $e) {
            $this->logger->error('Phone validation failed', [
                'phone' => $phoneNumber,
                'operator' => $operatorCode,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Initiate a deposit (customer payment)
     *
     * @param array $data Deposit data
     * @return array
     * @throws PaymentGatewayException
     */
    public function initiateDeposit(array $data): array
    {
        try {
            // Validate required fields
            $this->validateDepositData($data);

            // Transform data for SDK
            $sdkData = $this->transformDepositData($data);

            // Initiate deposit
            $response = $this->sdk->initiateDeposit($sdkData);

            // Log success
            $this->logger->info('Deposit initiated successfully', [
                'deposit_id' => $response['depositId'] ?? 'unknown',
                'amount' => $data['amount'],
                'currency' => $data['currency']
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Deposit initiation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new PaymentGatewayException('Failed to initiate deposit: ' . $e->getMessage());
        }
    }

    /**
     * Check deposit status
     *
     * @param string $depositId
     * @return array
     * @throws PaymentGatewayException
     */
    public function checkDepositStatus(string $depositId): array
    {
        try {
            return $this->sdk->checkDepositStatus($depositId);
        } catch (\Exception $e) {
            $this->logger->error('Deposit status check failed', [
                'deposit_id' => $depositId,
                'error' => $e->getMessage()
            ]);
            throw new PaymentGatewayException('Failed to check deposit status: ' . $e->getMessage());
        }
    }

    /**
     * Initiate a payout (vendor payment)
     *
     * @param array $data Payout data
     * @return array
     * @throws PaymentGatewayException
     */
    public function initiatePayout(array $data): array
    {
        try {
            $this->validatePayoutData($data);

            $sdkData = $this->transformPayoutData($data);

            $response = $this->sdk->initiatePayout($sdkData);

            $this->logger->info('Payout initiated successfully', [
                'payout_id' => $response['payoutId'] ?? 'unknown',
                'amount' => $data['amount']
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Payout initiation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new PaymentGatewayException('Failed to initiate payout: ' . $e->getMessage());
        }
    }

    /**
     * Verify webhook signature
     *
     * @param array $data Webhook payload
     * @param string|null $signature
     * @return bool
     */
    public function verifyWebhookSignature(array $data, ?string $signature = null): bool
    {
        try {
            return $this->sdk->verifyWebhookSignature($data, $signature);
        } catch (\Exception $e) {
            $this->logger->error('Webhook signature verification failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get SDK instance for advanced usage
     *
     * @return PawaPaySDK
     */
    public function getSDK(): PawaPaySDK
    {
        return $this->sdk;
    }

    /**
     * Get current configuration
     *
     * @return object|array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Transform Modesy config to SDK format
     *
     * @param object $modesyConfig
     * @return array
     */
    private function transformConfigForSDK($modesyConfig): array
    {
        return [
            'api' => [
                'token' => $modesyConfig->public_key ?? env('PAWAPAY_API_TOKEN'),
                'base_url' => $this->getApiBaseUrl($modesyConfig->environment ?? 'sandbox')
            ],
            'webhook_secret' => $modesyConfig->webhook_secret ?? env('PAWAPAY_WEBHOOK_SECRET'),
            'environment' => $modesyConfig->environment ?? 'sandbox'
        ];
    }

    /**
     * Get API base URL based on environment
     *
     * @param string $environment
     * @return string
     */
    private function getApiBaseUrl(string $environment): string
    {
        return $environment === 'production'
            ? 'https://api.pawapay.io'
            : 'https://api.sandbox.pawapay.io';
    }

    /**
     * Transform deposit data for SDK
     *
     * @param array $data
     * @return array
     */
    private function transformDepositData(array $data): array
    {
        return [
            'depositId' => $data['depositId'] ?? generate_unique_id(),
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency']),
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'provider' => $data['provider'],
                    'phoneNumber' => $data['phoneNumber']
                ]
            ],
            'reference' => $data['reference'] ?? ('TXN-' . uniqid()),
            'metadata' => $data['metadata'] ?? []
        ];
    }

    /**
     * Transform payout data for SDK
     *
     * @param array $data
     * @return array
     */
    private function transformPayoutData(array $data): array
    {
        return [
            'payoutId' => $data['payoutId'],
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency']),
            'recipient' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'provider' => $data['recipient']['provider'],
                    'phoneNumber' => $data['recipient']['phoneNumber']
                ]
            ],
            'customerMessage' => $data['customerMessage'] ?? 'Payout from Myzuwa.com',
            'metadata' => $data['metadata'] ?? []
        ];
    }

    /**
     * Validate deposit data
     *
     * @param array $data
     * @throws PaymentGatewayException
     */
    private function validateDepositData(array $data): void
    {
        $required = ['amount', 'currency', 'phoneNumber', 'provider'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new PaymentGatewayException("Missing required deposit field: {$field}");
            }
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new PaymentGatewayException('Invalid amount');
        }

        if (!in_array(strtoupper($data['currency']), ['ZMW', 'USD'])) {
            throw new PaymentGatewayException('Unsupported currency');
        }
    }

    /**
     * Validate payout data
     *
     * @param array $data
     * @throws PaymentGatewayException
     */
    private function validatePayoutData(array $data): void
    {
        $required = ['payoutId', 'amount', 'currency', 'recipient'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new PaymentGatewayException("Missing required payout field: {$field}");
            }
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new PaymentGatewayException('Invalid payout amount');
        }

        if (!in_array(strtoupper($data['currency']), ['ZMW', 'USD'])) {
            throw new PaymentGatewayException('Unsupported payout currency');
        }
    }
}
