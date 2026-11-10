CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 identity_hash CHAR(64) NOT NULL,
 ip_hash CHAR(64) NOT NULL,
 attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 window_started_at DATETIME NOT NULL,
 locked_until DATETIME NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_login_identity_ip (identity_hash,ip_hash),
 INDEX idx_login_locked (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
