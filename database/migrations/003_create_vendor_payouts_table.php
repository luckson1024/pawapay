<?php

namespace Database\Migrations;

class CreateVendorPayoutsTable
{
    public function up()
    {
        return "
            CREATE TABLE IF NOT EXISTS vendor_payouts (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                payout_id VARCHAR(255) NOT NULL UNIQUE,
                earnings_id BIGINT NOT NULL,
                vendor_id BIGINT NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                currency VARCHAR(3) DEFAULT 'ZMB',
                pawaPay_status VARCHAR(50) NOT NULL,
                internal_status VARCHAR(50) DEFAULT 'pending',
                failure_reason TEXT NULL,
                created_by BIGINT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_payout_id (payout_id),
                INDEX idx_vendor_id (vendor_id),
                INDEX idx_earnings_id (earnings_id),
                INDEX idx_status (pawaPay_status),
                INDEX idx_internal_status (internal_status),
                INDEX idx_created_at (created_at),

                FOREIGN KEY (earnings_id) REFERENCES vendor_earnings(id) ON DELETE CASCADE,
                FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            -- Add net_amount field to pending_payments for better tracking
            ALTER TABLE pending_payments ADD COLUMN IF NOT EXISTS net_amount DECIMAL(15,2) NULL AFTER payment_amount;
            ALTER TABLE pending_payments ADD COLUMN IF NOT EXISTS fee DECIMAL(15,2) DEFAULT 0.00 AFTER net_amount;
            ALTER TABLE pending_payments ADD COLUMN IF NOT EXISTS internal_status VARCHAR(50) DEFAULT 'pending' AFTER payment_type;
            ALTER TABLE pending_payments ADD COLUMN IF NOT EXISTS pawapay_status VARCHAR(50) NULL AFTER internal_status;
            ALTER TABLE pending_payments ADD COLUMN IF NOT EXISTS failure_reason TEXT NULL AFTER pawapay_status;
        ";
    }

    public function down()
    {
        return "
            DROP TABLE IF EXISTS vendor_payouts;
            ALTER TABLE pending_payments DROP COLUMN IF EXISTS net_amount;
            ALTER TABLE pending_payments DROP COLUMN IF EXISTS fee;
            ALTER TABLE pending_payments DROP COLUMN IF EXISTS internal_status;
            ALTER TABLE pending_payments DROP COLUMN IF EXISTS pawapay_status;
            ALTER TABLE pending_payments DROP COLUMN IF EXISTS failure_reason;
        ";
    }
}
