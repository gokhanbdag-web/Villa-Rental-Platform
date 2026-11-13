-- Provider-neutral payment safety foundation.
-- No provider is enabled by this migration; online payments remain disabled by default.

ALTER TABLE payment_transactions
    ADD COLUMN idempotency_key CHAR(64) NULL AFTER provider_reference,
    ADD COLUMN checkout_url VARCHAR(1000) NULL AFTER response_payload,
    ADD COLUMN expires_at DATETIME NULL AFTER paid_at,
    ADD COLUMN updated_at DATETIME NULL AFTER created_at,
    ADD UNIQUE KEY uq_payment_idempotency (idempotency_key),
    ADD INDEX idx_payment_provider_reference (provider, provider_reference),
    ADD INDEX idx_payment_status_created (status, created_at);

CREATE TABLE IF NOT EXISTS payment_webhook_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(40) NOT NULL,
    provider_event_id VARCHAR(190) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    transaction_reference VARCHAR(190) NULL,
    payload_hash CHAR(64) NOT NULL,
    processing_status VARCHAR(24) NOT NULL DEFAULT 'received',
    received_at DATETIME NOT NULL,
    processed_at DATETIME NULL,
    error_message VARCHAR(500) NULL,
    UNIQUE KEY uq_payment_webhook_provider_event (provider, provider_event_id),
    INDEX idx_payment_webhook_status (processing_status, received_at),
    INDEX idx_payment_webhook_transaction (provider, transaction_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
