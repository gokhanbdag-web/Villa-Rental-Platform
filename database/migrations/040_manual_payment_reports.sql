-- Customer-submitted manual bank transfer reports with one active report per booking.
CREATE TABLE IF NOT EXISTS manual_payment_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    guest_user_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL,
    sender_name VARCHAR(190) NOT NULL,
    sender_bank VARCHAR(120) NULL,
    transfer_reference VARCHAR(120) NOT NULL,
    note VARCHAR(1000) NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'pending',
    active_booking_id BIGINT UNSIGNED GENERATED ALWAYS AS (
        CASE WHEN status='pending' THEN booking_id ELSE NULL END
    ) STORED,
    reviewed_by BIGINT UNSIGNED NULL,
    review_note VARCHAR(1000) NULL,
    submitted_at DATETIME NOT NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_manual_report_active_booking (active_booking_id),
    INDEX idx_manual_report_status_submitted (status, submitted_at),
    INDEX idx_manual_report_booking (booking_id, submitted_at),
    CONSTRAINT fk_manual_report_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_manual_report_guest FOREIGN KEY (guest_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_manual_report_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
