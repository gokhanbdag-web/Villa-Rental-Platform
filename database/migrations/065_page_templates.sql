-- Reusable page layouts: copying a template never couples future page edits.
CREATE TABLE IF NOT EXISTS cms_page_templates (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 slug VARCHAR(160) NOT NULL,
 category VARCHAR(60) NOT NULL DEFAULT 'general',
 layout_json LONGTEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_cms_page_template_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO cms_page_templates(name,slug,category,layout_json) VALUES
('Blank page','blank','general','{"rows":[]}'),
('Classic villa homepage','villa-classic','homepage','{"rows":[{"columns":[[{"type":"heading","text":"Discover exceptional villas"},{"type":"text","text":"Find your next private villa holiday."}]]},{"columns":[[{"type":"banner","eyebrow":"VILLA COLLECTION","text":"Find your perfect escape","description":"Explore beautiful villas and memorable stays.","url":"https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1800&q=85","button_text":"Browse villas","button_url":"/konaklamalar"}]]}]}'),
('Modern villa homepage','villa-modern','homepage','{"rows":[{"columns":[[{"type":"heading","text":"Your next stay starts here"},{"type":"text","text":"Exceptional places. Effortless escapes."}]]},{"columns":[[{"type":"image","url":"https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1800&q=85","alt":"Modern villa"},{"type":"button","text":"Explore villas","url":"/konaklamalar"}]]}]}');
