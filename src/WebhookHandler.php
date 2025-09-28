<?php

namespace App\Libraries;

class WebhookHandler
{
    private $webhookSecret;

    public function __construct(string $webhookSecret)
    {
        $this->webhookSecret = $webhookSecret;
    }

    public function verifySignature(string $payload, string $signature): bool
    {
        // Compute expected signature using HMAC SHA-256
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        
        // Compare computed signature with received signature
        return hash_equals($expectedSignature, $signature);
    }

    public function handleWebhook(string $payload, array $headers): array
    {
        // Extract PawaPay signature from headers
        $signature = $headers['X-PawaPay-Signature'] ?? '';
        
        if (empty($signature)) {
            throw new \Exception('Missing webhook signature');
        }

        // Verify the signature
        if (!$this->verifySignature($payload, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }

        // Parse and validate the payload
        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid webhook payload');
        }

        // Process webhook based on type
        switch ($data['type'] ?? '') {
            case 'deposit.completed':
            case 'deposit.failed':
                return $this->handleDepositWebhook($data);
            
            case 'payout.completed':
            case 'payout.failed':
                return $this->handlePayoutWebhook($data);
            
            default:
                throw new \Exception('Unknown webhook type');
        }
    }

    private function handleDepositWebhook(array $data): array
    {
        // Implement deposit webhook handling logic
        return [
            'handled' => 'processed',
            'type' => 'deposit',
            'depositId' => $data['depositId'] ?? null,
            'status' => $data['status'] ?? null
        ];
    }

    private function handlePayoutWebhook(array $data): array
    {
        // Implement payout webhook handling logic
        return [
            'handled' => 'processed',
            'type' => 'payout',
            'payoutId' => $data['payoutId'] ?? null,
            'status' => $data['status'] ?? null
        ];
    }
}