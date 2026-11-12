CREATE TABLE IF NOT EXISTS theme_demo_imports (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  theme_slug VARCHAR(50) NOT NULL,
  package_version VARCHAR(30) NOT NULL,
  imported_by BIGINT UNSIGNED NULL,
  imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_layouts INT UNSIGNED NOT NULL DEFAULT 0,
  skipped_layouts INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_theme_demo_import (theme_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS theme_demo_layouts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  theme_slug VARCHAR(50) NOT NULL,
  page_key VARCHAR(40) NOT NULL,
  layout_id BIGINT UNSIGNED NOT NULL,
  content_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_theme_demo_layout (theme_slug,page_key),
  KEY idx_theme_demo_layout_id (layout_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
