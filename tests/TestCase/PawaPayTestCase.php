<?php

namespace Tests\TestCase;

use PHPUnit\Framework\TestCase;
use PawaPay\PawaPay;
use PawaPay\WebhookHandler;
use PawaPay\Adapter\ModesyAdapter;

abstract class PawaPayTestCase extends TestCase
{
    protected PawaPay $sdk;
    protected WebhookHandler $webhookHandler;
    protected ModesyAdapter $adapter;
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadTestConfig();
        $this->initializeSdk();
        $this->setupWebhookHandler();
        $this->initializeModesyAdapter();
    }

    protected function loadTestConfig(): void
    {
        $this->config = [
            'apiKey' => $_ENV['PAWAPAY_API_KEY'] ?? 'test_key',
            'secretKey' => $_ENV['PAWAPAY_SECRET_KEY'] ?? 'test_secret',
            'environment' => 'sandbox',
            'webhookSecret' => $_ENV['PAWAPAY_WEBHOOK_SECRET'] ?? 'test_webhook_secret'
        ];
    }

    protected function initializeSdk(): void
    {
        $this->sdk = new PawaPay(
            $this->config['apiKey'],
            $this->config['secretKey'],
            $this->config['environment']
        );
    }

    protected function setupWebhookHandler(): void
    {
        $this->webhookHandler = new WebhookHandler($this->config['webhookSecret']);
    }

    protected function initializeModesyAdapter(): void
    {
        $this->adapter = new ModesyAdapter($this->sdk);
    }

    /**
     * Helper to simulate a checkout session
     */
    protected function simulateCheckoutSession(array $items, array $vendorCommissions = []): array
    {
        return [
            'orderTotal' => $this->calculateOrderTotal($items),
            'items' => $items,
            'vendorCommissions' => $vendorCommissions,
            'sessionId' => uniqid('test_session_')
        ];
    }

    /**
     * Calculate order total including commissions
     */
    protected function calculateOrderTotal(array $items): float
    {
        return array_reduce($items, function($total, $item) {
            return $total + ($item['price'] * $item['quantity']);
        }, 0.0);
    }

    /**
     * Generate a mock webhook payload
     */
    protected function generateWebhookPayload(string $depositId, string $status = 'COMPLETED'): array
    {
        return [
            'depositId' => $depositId,
            'status' => $status,
            'timestamp' => time(),
            'amount' => '100.00',
            'currency' => 'ZMW',
            'metadata' => [
                'sessionId' => 'test_session_123'
            ]
        ];
    }

    /**
     * Verify order state after payment
     */
    protected function verifyOrderState(string $orderId, array $expectedState): void
    {
        // Implement state verification logic here
        $this->assertEquals(
            $expectedState['status'],
            $this->adapter->getOrderStatus($orderId),
            'Order status mismatch'
        );

        if (isset($expectedState['vendorCommissions'])) {
            foreach ($expectedState['vendorCommissions'] as $vendorId => $commission) {
                $this->assertEquals(
                    $commission,
                    $this->adapter->getVendorCommission($orderId, $vendorId),
                    "Commission mismatch for vendor {$vendorId}"
                );
            }
        }
    }

    /**
     * Verify wallet balance changes
     */
    protected function verifyWalletBalance(int $userId, float $expectedBalance): void
    {
        $actualBalance = $this->adapter->getWalletBalance($userId);
        $this->assertEquals(
            $expectedBalance,
            $actualBalance,
            'Wallet balance mismatch'
        );
    }
}