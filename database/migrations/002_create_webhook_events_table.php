<?php

namespace Database\Migrations;

class CreateWebhookEventsTable
{
    public function up()
    {
        return "
            CREATE TABLE IF NOT EXISTS webhook_events (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                event_id VARCHAR(255) NOT NULL UNIQUE,
                event_type VARCHAR(50) NOT NULL,
                payload JSON NOT NULL,
                processed BOOLEAN DEFAULT FALSE,
                processed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_event_type (event_type),
                INDEX idx_processed (processed)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
    }

    public function down()
    {
        return "DROP TABLE IF EXISTS webhook_events;";
    }
}