-- Native SMTP delivery queue. Sending remains disabled until an administrator configures SMTP.
CREATE TABLE IF NOT EXISTS email_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(190) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    html_body MEDIUMTEXT NOT NULL,
    text_body MEDIUMTEXT NULL,
    event_key VARCHAR(190) NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'pending',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL,
    locked_at DATETIME NULL,
    sent_at DATETIME NULL,
    last_error VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_email_queue_event (event_key),
    INDEX idx_email_queue_ready (status, available_at, id),
    INDEX idx_email_queue_recipient (recipient_email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings(setting_key,setting_value,autoload) VALUES
('mail.enabled','0',1),
('mail.smtp.port','587',1),
('mail.smtp.encryption','tls',1),
('mail.smtp.host','',1),
('mail.smtp.username','',1),
('mail.smtp.password','',0),
('mail.from_email','',1),
('mail.from_name','',1),
('mail.cron_token','',0);
