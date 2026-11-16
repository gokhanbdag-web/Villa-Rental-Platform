-- Track the exact completed-booking balances reserved by each vendor payout.
CREATE TABLE IF NOT EXISTS vendor_payout_allocations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payout_id BIGINT UNSIGNED NOT NULL,
    booking_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_vendor_payout_booking (payout_id, booking_id),
    INDEX idx_vendor_payout_allocation_booking (booking_id),
    CONSTRAINT fk_vendor_payout_allocation_payout FOREIGN KEY (payout_id) REFERENCES vendor_payouts(id) ON DELETE CASCADE,
    CONSTRAINT fk_vendor_payout_allocation_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vendor_payout_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payout_id BIGINT UNSIGNED NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    from_status VARCHAR(24) NULL,
    to_status VARCHAR(24) NOT NULL,
    payout_reference VARCHAR(190) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_vendor_payout_event_payout (payout_id, created_at),
    INDEX idx_vendor_payout_event_actor (actor_user_id),
    CONSTRAINT fk_vendor_payout_event_payout FOREIGN KEY (payout_id) REFERENCES vendor_payouts(id) ON DELETE CASCADE,
    CONSTRAINT fk_vendor_payout_event_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE vendor_payouts
    ADD INDEX idx_vendor_payout_vendor_currency_status (vendor_id, currency, status);
