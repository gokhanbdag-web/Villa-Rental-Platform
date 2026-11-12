-- Remove database structures and menu entries belonging only to the retired theme demos and Page Builder.
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

DROP TABLE IF EXISTS theme_demo_layouts, theme_demo_imports, page_builder_layouts;
