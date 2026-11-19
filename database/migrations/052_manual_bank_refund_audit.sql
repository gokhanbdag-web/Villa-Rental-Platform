-- Traceable bank refund instructions for manually paid bookings.
-- Bank refunds are never marked settled automatically; reconciliation requires a reviewer.
CREATE TABLE IF NOT EXISTS manual_bank_refunds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    payment_transaction_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'pending',
    bank_reference VARCHAR(190) NULL,
    reason VARCHAR(500) NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    reviewed_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    completed_at DATETIME NULL,
    INDEX idx_manual_bank_refunds_booking (booking_id, status),
    INDEX idx_manual_bank_refunds_payment (payment_transaction_id, status),
    INDEX idx_manual_bank_refunds_reference (bank_reference),
    CONSTRAINT fk_manual_bank_refunds_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE RESTRICT,
    CONSTRAINT fk_manual_bank_refunds_payment FOREIGN KEY (payment_transaction_id) REFERENCES payment_transactions(id) ON DELETE RESTRICT,
    CONSTRAINT fk_manual_bank_refunds_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_manual_bank_refunds_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_manual_bank_refund_amount CHECK (amount > 0),
    CONSTRAINT chk_manual_bank_refund_status CHECK (status IN ('pending','processing','succeeded','failed','cancelled'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
