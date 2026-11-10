CREATE TABLE IF NOT EXISTS booking_financials (
 booking_id BIGINT UNSIGNED PRIMARY KEY,
 gross_amount DECIMAL(12,2) NOT NULL,
 platform_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
 vendor_net_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
 currency CHAR(3) NOT NULL,
 payout_status VARCHAR(24) NOT NULL DEFAULT 'unpaid',
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 CONSTRAINT fk_financial_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booking_id BIGINT UNSIGNED NOT NULL,
 provider VARCHAR(40) NOT NULL,
 provider_reference VARCHAR(190) NULL,
 amount DECIMAL(12,2) NOT NULL,
 currency CHAR(3) NOT NULL,
 status VARCHAR(24) NOT NULL DEFAULT 'pending',
 request_payload JSON NULL,
 response_payload JSON NULL,
 paid_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_payment_booking (booking_id,status),
 CONSTRAINT fk_payment_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vendor_payouts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 vendor_id BIGINT UNSIGNED NOT NULL,
 amount DECIMAL(12,2) NOT NULL,
 currency CHAR(3) NOT NULL,
 status VARCHAR(24) NOT NULL DEFAULT 'requested',
 payout_reference VARCHAR(190) NULL,
 notes TEXT NULL,
 requested_at DATETIME NOT NULL,
 processed_at DATETIME NULL,
 INDEX idx_vendor_payout_status (vendor_id,status),
 CONSTRAINT fk_payout_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 notification_type VARCHAR(60) NOT NULL,
 title VARCHAR(190) NOT NULL,
 body TEXT NULL,
 read_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_notification_user (user_id,read_at,created_at),
 CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings(setting_key,setting_value,autoload) VALUES
('platform.commission_percent','12',1),
('payments.enabled','0',1),
('payments.provider','none',1);
