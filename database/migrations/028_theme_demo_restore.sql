ALTER TABLE theme_demo_layouts
  ADD COLUMN previous_layout_json LONGTEXT NULL AFTER content_hash,
  ADD COLUMN previous_status ENUM('draft','published') NULL AFTER previous_layout_json,
  ADD COLUMN previous_version INT UNSIGNED NULL AFTER previous_status,
  ADD COLUMN previous_updated_at DATETIME NULL AFTER previous_version;
