INSERT INTO translations(language_code,translation_key,translation_value) VALUES
('en','nav.open_menu','Open menu'),
('en','nav.social_media','Social media'),
('en','nav.explore','Explore')
ON DUPLICATE KEY UPDATE translation_value=VALUES(translation_value);
