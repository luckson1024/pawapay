<?php

namespace App\Controllers\Admin;

use Myzuwa\PawaPay\PawaPay;
use Myzuwa\PawaPay\Exception\PaymentGatewayException;

/**
 * PawaPay Admin Payout Controller - Myzuwa Marketplace Integration
 *
 * @package     App\Controllers\Admin
 * @version     1.0.0
 * @author      AI Assistant - September 2025
 *
 * Manages Myzuwa-Vendor payment flows (reverse of customer deposits).
 * Provides admin interface for payout monitoring and bulk processing.
 *
 * AI INSTRUCTIONS:
 * - Always use DatabaseHelper::static methods, never raw PDO
 * - Validate vendor data before payout calls
 * - Wrap operations in try/catch with proper logging
 * - Process payouts individually to isolate failures
 *
 * @link ../src/PawaPay.php - PawaPay SDK for API communication
 * @link ../src/Support/DatabaseHelper.php - Database abstraction layer
 * @link ../database/migrations/003_create_vendor_payouts_table.php - Database schema
 * @link ../app/Config/RoutesStatic.php - Route definitions for payout endpoints
 * @link PRODUCTION_READINESS_CHECKLIST.md - Production deployment steps
 * @link ../src/Support/LogManager.php - Logging system used
 */
class PayoutController
{
    protected $logger;

    public function __construct()
    {
        // Initialize logging
        $this->logger = new \Myzuwa\PawaPay\Support\LogManager('payout');
    }

    /**
     * Display payout management dashboard
     *
     * @return string|void
     */
    public function index()
    {
        try {
            // Get pending payouts
            $pendingPayouts = $this->getPendingPayouts();

            // Get payout statistics
            $stats = $this->getPayoutStats();

            // Get recent payouts
            $recentPayouts = $this->getRecentPayouts();

            // Load the admin view
            $data = [
                'pendingPayouts' => $pendingPayouts,
                'stats' => $stats,
                'recentPayouts' => $recentPayouts,
                'pageTitle' => 'PawaPay Payout Management'
            ];

            $this->loadAdminView('pawapay/payouts/index', $data);

        } catch (\Exception $e) {
            $this->logger->error('Failed to load payout dashboard', [
                'error' => $e->getMessage()
            ]);

            // Show error message
            $this->showAdminError('Unable to load payout dashboard. Please try again later.');
        }
    }

    /**
     * Process single payout initiation
     *
     * @return string|void
     */
    public function processPayout(int $earningsId)
    {
        try {
            // Get vendor earnings record
            $earnings = $this->getVendorEarnings($earningsId);
            if (!$earnings) {
                $this->showAdminError('Earnings record not found.');
                return;
            }

            // Check if payout already exists
            if ($this->hasExistingPayout($earningsId)) {
                $this->showAdminError('Payout already initiated for these earnings.');
                return;
            }

            // Get payment gateway config
            $gateway = getPaymentGateway('pawapay');
            if (empty($gateway) || $gateway->status != 1) {
                $this->showAdminError('PawaPay gateway is not configured or disabled.');
                return;
            }

            // Initialize PawaPay SDK
            $pawapay = new PawaPay((array)$gateway);

            // Generate payout ID
            $payoutId = 'MZ-PAY-' . time() . '-' . generate_unique_id();

            // Prepare payout data
            $payoutData = [
                'payoutId' => $payoutId,
                'amount' => (string)$earnings->available_amount,
                'currency' => 'ZMW',
                'recipient' => [
                    'type' => 'MMO',
                    'accountDetails' => [
                        'provider' => $earnings->mno_provider,
                        'phoneNumber' => $earnings->phone_number
                    ]
                ],
                'customerMessage' => "Vendor payout from Myzuwa.com - Order earnings"
            ];

            // Initiate payout
            $response = $pawapay->initiatePayout($payoutData);

            if ($response && isset($response['status']) && $response['status'] === 'ACCEPTED') {
                // Create payout record
                $this->createPayoutRecord([
                    'payout_id' => $payoutId,
                    'earnings_id' => $earningsId,
                    'vendor_id' => $earnings->vendor_id,
                    'amount' => $earnings->available_amount,
                    'currency' => 'ZMW',
                    'pawaPay_status' => 'ACCEPTED',
                    'created_by' => $this->getCurrentAdminUserId()
                ]);

                $this->logger->info('Payout initiated successfully', [
                    'payout_id' => $payoutId,
                    'vendor_id' => $earnings->vendor_id,
                    'amount' => $earnings->available_amount
                ]);

                // Redirect with success message
                $this->setAdminSuccessMessage('Payout initiated successfully. Funds will be transferred to the vendor.');

            } else {
                $errorMsg = $response['failureReason']['failureMessage'] ?? 'Unknown error occurred';
                $this->logger->error('Payout initiation failed', [
                    'earnings_id' => $earningsId,
                    'error' => $errorMsg
                ]);

                $this->showAdminError('Payout initiation failed: ' . $errorMsg);
            }

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in payout processing', [
                'earnings_id' => $earningsId,
                'error' => $e->getMessage()
            ]);

            $this->showAdminError('An unexpected error occurred. Please try again.');
        }
    }

    /**
     * Bulk process multiple payouts
     *
     * @return string|void
     */
    public function processBulkPayouts(array $earningsIds)
    {
        $results = [
            'successful' => [],
            'failed' => []
        ];

        foreach ($earningsIds as $earningsId) {
            try {
                // Get gateway for each payout
                $gateway = getPaymentGateway('pawapay');
                if (empty($gateway) || $gateway->status != 1) {
                    $results['failed'][] = [
                        'earnings_id' => $earningsId,
                        'error' => 'Gateway not configured'
                    ];
                    continue;
                }

                $earnings = $this->getVendorEarnings($earningsId);
                if (!$earnings) {
                    $results['failed'][] = [
                        'earnings_id' => $earningsId,
                        'error' => 'Earnings record not found'
                    ];
                    continue;
                }

                $pawapay = new PawaPay((array)$gateway);
                $payoutId = 'MZ-PAY-BULK-' . time() . '-' . $earningsId;

                $payoutData = [
                    'payoutId' => $payoutId,
                    'amount' => (string)$earnings->available_amount,
                    'currency' => 'ZMW',
                    'recipient' => [
                        'type' => 'MMO',
                        'accountDetails' => [
                            'provider' => $earnings->mno_provider,
                            'phoneNumber' => $earnings->phone_number
                        ]
                    ],
                    'customerMessage' => "Bulk vendor payout from Myzuwa.com"
                ];

                $response = $pawapay->initiatePayout($payoutData);

                if ($response && isset($response['status']) && $response['status'] === 'ACCEPTED') {
                    $this->createPayoutRecord([
                        'payout_id' => $payoutId,
                        'earnings_id' => $earningsId,
                        'vendor_id' => $earnings->vendor_id,
                        'amount' => $earnings->available_amount,
                        'currency' => 'ZMW',
                        'pawaPay_status' => 'ACCEPTED',
                        'created_by' => $this->getCurrentAdminUserId()
                    ]);

                    $results['successful'][] = $earningsId;

                } else {
                    $errorMsg = $response['failureReason']['failureMessage'] ?? 'Unknown error';
                    $results['failed'][] = [
                        'earnings_id' => $earningsId,
                        'error' => $errorMsg
                    ];
                }

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'earnings_id' => $earningsId,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Log summary
        $this->logger->info('Bulk payout processing completed', [
            'successful_count' => count($results['successful']),
            'failed_count' => count($results['failed'])
        ]);

        return $results;
    }

    /**
     * Check payout status from PawaPay
     */
    public function checkPayoutStatus(string $payoutId)
    {
        try {
            $gateway = getPaymentGateway('pawapay');
            if (empty($gateway)) {
                return ['status' => 'error', 'message' => 'Gateway not configured'];
            }

            $pawapay = new PawaPay((array)$gateway);
            $response = $pawapay->checkDepositStatus($payoutId); // Note: Using same endpoint for consistency

            return [
                'status' => 'success',
                'data' => $response
            ];

        } catch (\Exception $e) {
            $this->logger->error('Payout status check failed', [
                'payout_id' => $payoutId,
                'error' => $e->getMessage()
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get vendor earnings data
     */
    private function getVendorEarnings(int $earningsId)
    {
        // Assuming vendor_earnings table exists
        return \Myzuwa\PawaPay\Support\DatabaseHelper::fetch(
            "SELECT * FROM vendor_earnings WHERE id = ? AND status = 'available' LIMIT 1",
            [$earningsId]
        );
    }

    /**
     * Check if payout already exists
     */
    private function hasExistingPayout(int $earningsId)
    {
        $result = \Myzuwa\PawaPay\Support\DatabaseHelper::fetch(
            "SELECT COUNT(*) as count FROM vendor_payouts WHERE earnings_id = ?",
            [$earningsId]
        );

        return ($result && $result['count'] > 0);
    }

    /**
     * Create payout record
     */
    private function createPayoutRecord(array $data): int
    {
        return \Myzuwa\PawaPay\Support\DatabaseHelper::insert('vendor_payouts', $data);
    }

    /**
     * Get pending payouts for dashboard
     */
    private function getPendingPayouts(): array
    {
        $sql = "SELECT ve.*, v.shop_name, v.contact_phone
                FROM vendor_earnings ve
                LEFT JOIN vendors v ON v.id = ve.vendor_id
                WHERE ve.status = 'available' AND ve.available_amount >= 10
                ORDER BY ve.created_at DESC";

        return \Myzuwa\PawaPay\Support\DatabaseHelper::query($sql);
    }

    /**
     * Get payout statistics
     */
    private function getPayoutStats(): array
    {
        $stats = [];

        // Total pending amount
        $result = \Myzuwa\PawaPay\Support\DatabaseHelper::fetch(
            "SELECT SUM(available_amount) as total FROM vendor_earnings WHERE status = 'available'"
        );
        $stats['pending_amount'] = $result ? (float)$result['total'] : 0;

        // Payouts this month
        $monthStart = date('Y-m-01');
        $result = \Myzuwa\PawaPay\Support\DatabaseHelper::fetch(
            "SELECT COUNT(*) as count FROM vendor_payouts WHERE created_at >= ?",
            [$monthStart]
        );
        $stats['this_month'] = $result ? (int)$result['count'] : 0;

        return $stats;
    }

    /**
     * Get recent payouts
     */
    private function getRecentPayouts(): array
    {
        $sql = "SELECT vp.*, v.shop_name
                FROM vendor_payouts vp
                LEFT JOIN vendors v ON v.id = vp.vendor_id
                ORDER BY vp.created_at DESC LIMIT 20";

        return \Myzuwa\PawaPay\Support\DatabaseHelper::query($sql);
    }

    /**
     * Get current admin user ID
     */
    private function getCurrentAdminUserId()
    {
        // Implement based on your admin authentication system
        return $_SESSION['admin_user_id'] ?? 1;
    }

    /**
     * Load admin view (placeholder - adapt to your admin system)
     */
    private function loadAdminView(string $view, array $data): void
    {
        // Implement based on your admin template system
        include __DIR__ . "/../../Views/admin/{$view}.php";
    }

    /**
     * Show admin error message
     */
    private function showAdminError(string $message): void
    {
        // Adapt to your admin error display system
        echo "<div class='alert alert-danger'>{$message}</div>";
    }

    /**
     * Set admin success message
     */
    private function setAdminSuccessMessage(string $message): void
    {
        // Adapt to your session flash message system
        $_SESSION['flash_success'] = $message;

        // Redirect back to payout dashboard
        header('Location: /admin/pawapay-payouts');
        exit;
    }
}
