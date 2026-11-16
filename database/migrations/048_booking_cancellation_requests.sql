CREATE TABLE IF NOT EXISTS booking_cancellation_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(1000) NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'pending',
    days_before_checkin SMALLINT NOT NULL,
    policy_days_snapshot SMALLINT UNSIGNED NOT NULL,
    refund_percent_snapshot TINYINT UNSIGNED NOT NULL,
    refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    decision_note VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL,
    reviewed_at DATETIME NULL,
    active_booking_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN status='pending' THEN booking_id ELSE NULL END) STORED,
    UNIQUE KEY uq_cancellation_active_booking (active_booking_id),
    INDEX idx_cancellation_status_created (status,created_at),
    INDEX idx_cancellation_booking (booking_id),
    INDEX idx_cancellation_requester (requested_by),
    INDEX idx_cancellation_reviewer (reviewed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings(setting_key,setting_value,autoload) VALUES
('booking.cancellation.free_days','7',1),
('booking.cancellation.early_refund_percent','100',1),
('booking.cancellation.late_refund_percent','0',1)
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);