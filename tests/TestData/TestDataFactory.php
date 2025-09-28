<?php

namespace Tests\TestData;

class TestDataFactory
{
    /**
     * Generate sample product data
     */
    public static function createProduct(
        string $id = null,
        float $price = 100.00,
        int $vendorId = 1
    ): array {
        return [
            'id' => $id ?? uniqid('prod_'),
            'name' => 'Test Product',
            'price' => $price,
            'vendorId' => $vendorId,
            'quantity' => 1
        ];
    }

    /**
     * Generate vendor data with commission rates
     */
    public static function createVendor(
        int $id = null,
        float $commissionRate = 0.10
    ): array {
        return [
            'id' => $id ?? random_int(1, 1000),
            'name' => 'Test Vendor',
            'commissionRate' => $commissionRate,
            'walletBalance' => 0.00
        ];
    }

    /**
     * Generate membership plan data
     */
    public static function createMembershipPlan(
        string $id = null,
        float $price = 50.00,
        string $duration = 'monthly'
    ): array {
        return [
            'id' => $id ?? uniqid('plan_'),
            'name' => 'Test Membership Plan',
            'price' => $price,
            'duration' => $duration,
            'features' => ['feature1', 'feature2']
        ];
    }

    /**
     * Generate promotion package data
     */
    public static function createPromotionPackage(
        string $id = null,
        float $price = 25.00,
        int $duration = 7
    ): array {
        return [
            'id' => $id ?? uniqid('promo_'),
            'name' => 'Featured Product Package',
            'price' => $price,
            'durationDays' => $duration,
            'type' => 'featured_product'
        ];
    }

    /**
     * Generate wallet deposit data
     */
    public static function createWalletDeposit(
        int $userId = null,
        float $amount = 100.00,
        string $currency = 'ZMW'
    ): array {
        return [
            'userId' => $userId ?? random_int(1, 1000),
            'amount' => $amount,
            'currency' => $currency,
            'type' => 'wallet_deposit',
            'timestamp' => time()
        ];
    }

    /**
     * Generate multi-vendor cart data
     */
    public static function createMultiVendorCart(
        int $numVendors = 2,
        int $productsPerVendor = 2
    ): array {
        $cart = ['items' => [], 'vendors' => [], 'commissions' => []];
        
        for ($v = 1; $v <= $numVendors; $v++) {
            $vendor = self::createVendor($v);
            $cart['vendors'][$v] = $vendor;
            
            for ($p = 1; $p <= $productsPerVendor; $p++) {
                $product = self::createProduct(
                    "prod_{$v}_{$p}",
                    100.00 * $p,
                    $v
                );
                $cart['items'][] = $product;
                
                // Calculate commission for this product
                $commission = $product['price'] * $vendor['commissionRate'];
                $cart['commissions'][$v] = ($cart['commissions'][$v] ?? 0) + $commission;
            }
        }
        
        return $cart;
    }

    /**
     * Generate webhook test data
     */
    public static function createWebhookData(
        string $depositId = null,
        string $status = 'COMPLETED',
        array $metadata = []
    ): array {
        return [
            'depositId' => $depositId ?? uniqid('dep_'),
            'status' => $status,
            'amount' => '100.00',
            'currency' => 'ZMW',
            'timestamp' => time(),
            'metadata' => array_merge([
                'sessionId' => uniqid('session_')
            ], $metadata)
        ];
    }
}