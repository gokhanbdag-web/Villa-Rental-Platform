-- Unified page editor layout storage, including the homepage.
CREATE TABLE IF NOT EXISTS cms_page_layouts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 page_key VARCHAR(190) NOT NULL,
 language_code VARCHAR(12) NOT NULL DEFAULT 'en',
 layout_json LONGTEXT NOT NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_cms_page_layout (page_key,language_code),
 CONSTRAINT fk_cms_page_layout_language FOREIGN KEY (language_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
