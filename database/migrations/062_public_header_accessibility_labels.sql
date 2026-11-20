INSERT INTO translations(language_code,translation_key,translation_value) VALUES
('tr','nav.open_menu','Menüyü aç'),
('en','nav.open_menu','Open menu'),
('tr','nav.social_media','Sosyal medya'),
('en','nav.social_media','Social media'),
('tr','nav.explore','Keşfet'),
('en','nav.explore','Explore')
ON DUPLICATE KEY UPDATE translation_value=VALUES(translation_value);
