<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PawaPay\PawaPay;
use PawaPay\WebhookHandler;
use PawaPay\Service\MNOService;
use PawaPay\Payment\Model\Money;
use PawaPay\Payment\Model\PaymentContext;
use PawaPay\Exception\PaymentGatewayException;

class PaymentIntegrationTest extends TestCase
{
    private PawaPay $pawaPay;
    private WebhookHandler $webhookHandler;
    private MNOService $mnoService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize SDK with test credentials
        $this->pawaPay = new PawaPay(
            $_ENV['PAWAPAY_API_KEY'],
            $_ENV['PAWAPAY_SECRET_KEY'],
            'sandbox'
        );

        $this->webhookHandler = new WebhookHandler($_ENV['PAWAPAY_WEBHOOK_SECRET']);
        $this->mnoService = new MNOService($this->pawaPay);
    }

    public function testInitiateDeposit()
    {
        // Test actual deposit initiation
        $depositId = uniqid('test_dep_');
        $response = $this->pawaPay->initiateDeposit([
            'depositId' => $depositId,
            'amount' => '100.00',
            'currency' => 'ZMW',
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'provider' => 'ZMB_AIRTEL',
                    'phoneNumber' => '260976000000'
                ]
            ]
        ]);

        $this->assertNotEmpty($response['depositId']);
        $this->assertEquals('ACCEPTED', $response['status']);
        
        return $depositId;
    }

    /**
     * @depends testInitiateDeposit
     */
    public function testGetDepositStatus(string $depositId)
    {
        $status = $this->pawaPay->getDepositStatus($depositId);
        $this->assertNotEmpty($status);
        $this->assertContains($status['status'], ['PENDING', 'COMPLETED', 'FAILED']);
    }

    public function testWebhookSignatureValidation()
    {
        $payload = json_encode([
            'depositId' => 'test_dep_123',
            'status' => 'COMPLETED',
            'amount' => '100.00'
        ]);

        $secret = $_ENV['PAWAPAY_WEBHOOK_SECRET'];
        $signature = hash_hmac('sha256', $payload, $secret);

        $this->assertTrue(
            $this->webhookHandler->verifySignature($payload, $signature)
        );
    }

    public function testGetMobileMoneyOperators()
    {
        $operators = $this->mnoService->getProviders('ZMW');
        
        $this->assertNotEmpty($operators);
        $this->assertContains('ZMB_AIRTEL', array_column($operators, 'code'));
        $this->assertContains('ZMB_MTN', array_column($operators, 'code'));
    }

    public function testPaymentContextCreation()
    {
        $money = new Money('100.00', 'ZMW');
        $context = new PaymentContext($money);
        
        $this->assertEquals('100.00', $context->getAmount());
        $this->assertEquals('ZMW', $context->getCurrency());
    }

    public function testFailedPaymentWithInvalidAmount()
    {
        $this->expectException(PaymentGatewayException::class);
        
        $this->pawaPay->initiateDeposit([
            'depositId' => uniqid('test_dep_'),
            'amount' => '-100.00', // Invalid negative amount
            'currency' => 'ZMW',
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'provider' => 'ZMB_AIRTEL',
                    'phoneNumber' => '260976000000'
                ]
            ]
        ]);
    }

    public function testDuplicateDepositIdDetection()
    {
        $depositId = uniqid('test_dep_');
        
        // First deposit should succeed
        $this->pawaPay->initiateDeposit([
            'depositId' => $depositId,
            'amount' => '100.00',
            'currency' => 'ZMW',
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'provider' => 'ZMB_AIRTEL',
                    'phoneNumber' => '260976000000'
                ]
            ]
        ]);

        // Second deposit with same ID should throw exception
        $this->expectException(PaymentGatewayException::class);
        $this->pawaPay->initiateDeposit([
            'depositId' => $depositId,
            'amount' => '100.00',
            'currency' => 'ZMW',
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'provider' => 'ZMB_AIRTEL',
                    'phoneNumber' => '260976000000'
                ]
            ]
        ]);
    }

    public function testInvalidWebhookSignature()
    {
        $payload = json_encode([
            'depositId' => 'test_dep_123',
            'status' => 'COMPLETED'
        ]);
        
        $invalidSignature = 'invalid_signature';
        
        $this->assertFalse(
            $this->webhookHandler->verifySignature($payload, $invalidSignature)
        );
    }
}