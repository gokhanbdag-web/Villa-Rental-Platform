CREATE TABLE IF NOT EXISTS sms_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_phone VARCHAR(24) NOT NULL,
    message_body VARCHAR(912) NOT NULL,
    event_key VARCHAR(190) NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'pending',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL,
    locked_at DATETIME NULL,
    sent_at DATETIME NULL,
    provider_job_id VARCHAR(80) NULL,
    last_error VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_sms_queue_event (event_key),
    INDEX idx_sms_queue_ready (status,available_at,id),
    INDEX idx_sms_queue_recipient (recipient_phone,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings(setting_key,setting_value,autoload) VALUES
('sms.enabled','0',1),
('sms.provider','netgsm',1),
('sms.netgsm.username','',1),
('sms.netgsm.password','',0),
('sms.netgsm.sender','',1),
('sms.netgsm.encoding','TR',1),
('sms.netgsm.iys_filter','0',1),
('sms.cron_token','',0);

INSERT INTO translations(language_code,translation_key,translation_value) VALUES
('en','sms.test.message','VillaCMS SMS connection test successful.'),
('en','sms.booking.created','Your booking request was received. Reference: :code. Details: :url'),
('en','sms.booking.confirmed','Your booking is confirmed. Reference: :code. Check-in: :checkin.'),
('en','sms.booking.cancelled','Your booking was cancelled. Reference: :code.'),
('en','sms.payment.received','Your payment was received. Booking reference: :code.')
ON DUPLICATE KEY UPDATE translation_value=VALUES(translation_value);

INSERT INTO translations(language_code,translation_key,translation_value) VALUES
('en','sms.message.received','You have a new booking message. Messages: :url')
ON DUPLICATE KEY UPDATE translation_value=VALUES(translation_value);
