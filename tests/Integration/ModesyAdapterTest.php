<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PawaPay\Adapter\ModesyAdapter;
use PawaPay\PawaPay;
use PawaPay\Payment\Model\Money;
use PawaPay\Exception\PaymentGatewayException;

class ModesyAdapterTest extends TestCase
{
    private ModesyAdapter $adapter;
    private PawaPay $pawaPay;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pawaPay = new PawaPay(
            $_ENV['PAWAPAY_API_KEY'],
            $_ENV['PAWAPAY_SECRET_KEY'],
            'sandbox'
        );
        
        $this->adapter = new ModesyAdapter($this->pawaPay);
    }

    public function testHandlePaymentForSingleProduct()
    {
        $orderData = [
            'order_id' => 'test_order_123',
            'amount' => '100.00',
            'currency' => 'ZMW',
            'vendor_id' => 1,
            'commission_rate' => 0.10,
            'items' => [
                [
                    'product_id' => 1,
                    'price' => '100.00',
                    'quantity' => 1
                ]
            ]
        ];

        $result = $this->adapter->handlePayment($orderData);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('PENDING', $result->getStatus());
        $this->assertNotEmpty($result->getDepositId());
    }

    public function testHandlePaymentForMultiVendor()
    {
        $orderData = [
            'order_id' => 'test_order_456',
            'amount' => '200.00',
            'currency' => 'ZMW',
            'items' => [
                [
                    'product_id' => 1,
                    'price' => '100.00',
                    'quantity' => 1,
                    'vendor_id' => 1,
                    'commission_rate' => 0.10
                ],
                [
                    'product_id' => 2,
                    'price' => '100.00',
                    'quantity' => 1,
                    'vendor_id' => 2,
                    'commission_rate' => 0.15
                ]
            ]
        ];

        $result = $this->adapter->handlePayment($orderData);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertNotEmpty($result->getCommissions());
        
        // Verify commission calculations
        $commissions = $result->getCommissions();
        $this->assertEquals(10.00, $commissions[1]); // 10% of 100
        $this->assertEquals(15.00, $commissions[2]); // 15% of 100
    }

    public function testHandleWalletDeposit()
    {
        $depositData = [
            'user_id' => 1,
            'amount' => '500.00',
            'currency' => 'ZMW',
            'type' => 'wallet_deposit'
        ];

        $result = $this->adapter->handleWalletDeposit($depositData);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertNotEmpty($result->getDepositId());
    }

    public function testHandleMembershipPayment()
    {
        $membershipData = [
            'user_id' => 1,
            'plan_id' => 'premium',
            'amount' => '50.00',
            'currency' => 'ZMW',
            'duration' => '1_month'
        ];

        $result = $this->adapter->handleMembershipPayment($membershipData);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('membership_payment', $result->getType());
    }

    public function testHandlePromotionPayment()
    {
        $promotionData = [
            'user_id' => 1,
            'product_id' => 1,
            'promotion_type' => 'featured',
            'amount' => '25.00',
            'currency' => 'ZMW',
            'duration_days' => 7
        ];

        $result = $this->adapter->handlePromotionPayment($promotionData);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('promotion_payment', $result->getType());
    }

    public function testFailedPaymentHandling()
    {
        $this->expectException(PaymentGatewayException::class);
        
        $invalidOrderData = [
            'order_id' => 'test_order_789',
            'amount' => '-100.00', // Invalid amount
            'currency' => 'ZMW'
        ];

        $this->adapter->handlePayment($invalidOrderData);
    }

    public function testCurrencyMismatchHandling()
    {
        $this->expectException(PaymentGatewayException::class);
        
        $invalidCurrencyData = [
            'order_id' => 'test_order_999',
            'amount' => '100.00',
            'currency' => 'USD' // Unsupported currency
        ];

        $this->adapter->handlePayment($invalidCurrencyData);
    }
}