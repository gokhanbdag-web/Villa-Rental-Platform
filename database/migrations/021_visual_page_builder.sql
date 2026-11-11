CREATE TABLE IF NOT EXISTS page_builder_layouts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  theme_slug VARCHAR(50) NOT NULL,
  page_key VARCHAR(40) NOT NULL,
  layout_json LONGTEXT NOT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  version INT UNSIGNED NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_page_builder_theme_page (theme_slug,page_key),
  KEY idx_page_builder_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
