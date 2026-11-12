-- Remove database structures and menu entries belonging only to the retired theme demos and Page Builder.
-- Preserve any previously saved builder records in a compact archive before dropping runtime tables.
-- Historical migrations 020/021/027/028 remain in place as an immutable upgrade record.
DELETE FROM navigation_items
WHERE url IN (
    '/demo-theme/mediterranean',
    '/demo-theme/modern',
    '/demo-theme/boutique',
    '/demo-theme/prestige'
);

DELETE parent_item
FROM navigation_items parent_item
LEFT JOIN navigation_items child_item ON child_item.parent_id = parent_item.id
WHERE parent_item.location = 'header'
  AND parent_item.language_code = 'tr'
  AND parent_item.label = 'Demolar'
  AND parent_item.url = '#'
  AND child_item.id IS NULL;

DELETE FROM settings
WHERE setting_key IN ('theme.active', 'theme.demo.active');

CREATE TABLE IF NOT EXISTS legacy_cleanup_archive (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_table VARCHAR(100) NOT NULL,
    source_id BIGINT UNSIGNED NOT NULL,
    record_json JSON NOT NULL,
    archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_legacy_archive_source (source_table, source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO legacy_cleanup_archive (source_table, source_id, record_json)
SELECT 'page_builder_layouts', id,
       JSON_OBJECT('theme_slug',theme_slug,'page_key',page_key,'layout_json',layout_json,
                   'status',status,'version',version,'created_by',created_by,'updated_by',updated_by,
                   'created_at',created_at,'updated_at',updated_at)
FROM page_builder_layouts;

INSERT IGNORE INTO legacy_cleanup_archive (source_table, source_id, record_json)
SELECT 'theme_demo_imports', id,
       JSON_OBJECT('theme_slug',theme_slug,'package_version',package_version,'imported_by',imported_by,
                   'imported_at',imported_at,'created_layouts',created_layouts,'skipped_layouts',skipped_layouts)
FROM theme_demo_imports;

INSERT IGNORE INTO legacy_cleanup_archive (source_table, source_id, record_json)
SELECT 'theme_demo_layouts', id,
       JSON_OBJECT('theme_slug',theme_slug,'page_key',page_key,'layout_id',layout_id,'content_hash',content_hash,
                   'previous_layout_json',previous_layout_json,'previous_status',previous_status,
                   'previous_version',previous_version,'previous_updated_at',previous_updated_at,'created_at',created_at)
FROM theme_demo_layouts;

DROP TABLE IF EXISTS theme_demo_layouts, theme_demo_imports, page_builder_layouts;
