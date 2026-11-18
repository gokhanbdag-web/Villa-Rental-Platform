CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(80) NOT NULL UNIQUE,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role VARCHAR(40) NOT NULL,
 status VARCHAR(24) NOT NULL DEFAULT 'active',
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 setting_key VARCHAR(190) NOT NULL UNIQUE,
 setting_value LONGTEXT NULL,
 autoload TINYINT(1) NOT NULL DEFAULT 0,
 updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS languages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(12) NOT NULL UNIQUE,
 name VARCHAR(80) NOT NULL,
 is_default TINYINT(1) NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS translations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 language_code VARCHAR(12) NOT NULL,
 translation_key VARCHAR(190) NOT NULL,
 translation_value TEXT NOT NULL,
 UNIQUE KEY uq_translation (language_code, translation_key),
 CONSTRAINT fk_translation_language FOREIGN KEY (language_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO languages (code,name,is_default,is_active) VALUES ('tr','Türkçe',1,1);

CREATE TABLE IF NOT EXISTS content_translations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 entity_type VARCHAR(40) NOT NULL,
 entity_id BIGINT UNSIGNED NOT NULL,
 language_code VARCHAR(12) NOT NULL,
 field_key VARCHAR(64) NOT NULL,
 translation_value LONGTEXT NOT NULL,
 updated_at DATETIME NULL,
 UNIQUE KEY uq_content_translation (entity_type,entity_id,language_code,field_key),
 KEY idx_content_translation_locale (language_code,entity_type,entity_id),
 CONSTRAINT fk_content_translation_language FOREIGN KEY (language_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


