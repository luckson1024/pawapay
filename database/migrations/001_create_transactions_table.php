<?php

namespace Database\Migrations;

class CreateTransactionsTable
{
    public function up()
    {
        return "
            CREATE TABLE IF NOT EXISTS transactions (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                transaction_id VARCHAR(255) NOT NULL UNIQUE,
                type ENUM('deposit', 'payout', 'refund') NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                currency VARCHAR(3) NOT NULL,
                status VARCHAR(50) NOT NULL,
                provider VARCHAR(50) NOT NULL,
                phone_number VARCHAR(20) NOT NULL,
                metadata JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_transaction_id (transaction_id),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
    }

    public function down()
    {
        return "DROP TABLE IF EXISTS transactions;";
    }
}