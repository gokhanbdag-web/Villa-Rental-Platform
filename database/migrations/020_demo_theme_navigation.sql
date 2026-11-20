-- Add a visitor-facing demo theme switcher to the frontend navigation.
-- The selected theme is stored in the visitor's session and does not alter theme.active.
INSERT IGNORE INTO navigation_items
    (location, language_code, parent_id, label, url, target, sort_order, is_active)
VALUES
    ('header', 'en', NULL, 'Demos', '#', '_self', 15, 1);

INSERT IGNORE INTO navigation_items
    (location, language_code, parent_id, label, url, target, sort_order, is_active)
SELECT 'header', 'en', p.id, 'Mediterranean', '/demo-theme/mediterranean', '_self', 10, 1
FROM navigation_items p
WHERE p.location='header' AND p.language_code='en' AND p.label='Demos' AND p.url='#'
LIMIT 1;

INSERT IGNORE INTO navigation_items
    (location, language_code, parent_id, label, url, target, sort_order, is_active)
SELECT 'header', 'en', p.id, 'Modern', '/demo-theme/modern', '_self', 20, 1
FROM navigation_items p
WHERE p.location='header' AND p.language_code='en' AND p.label='Demos' AND p.url='#'
LIMIT 1;

INSERT IGNORE INTO navigation_items
    (location, language_code, parent_id, label, url, target, sort_order, is_active)
SELECT 'header', 'en', p.id, 'Boutique', '/demo-theme/boutique', '_self', 30, 1
FROM navigation_items p
WHERE p.location='header' AND p.language_code='en' AND p.label='Demos' AND p.url='#'
LIMIT 1;

INSERT IGNORE INTO navigation_items
    (location, language_code, parent_id, label, url, target, sort_order, is_active)
SELECT 'header', 'en', p.id, 'Prestige', '/demo-theme/prestige', '_self', 40, 1
FROM navigation_items p
WHERE p.location='header' AND p.language_code='en' AND p.label='Demos' AND p.url='#'
LIMIT 1;
