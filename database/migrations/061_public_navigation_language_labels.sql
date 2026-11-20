INSERT INTO translations(language_code,translation_key,translation_value) VALUES
('en','nav.admin','Admin dashboard'),
('en','nav.vendor','Host dashboard'),
('en','nav.account','My account'),
('en','nav.login','Log in'),
('en','nav.language','Language'),
('en','nav.site_language','Site language'),
('en','nav.apply','Apply')
ON DUPLICATE KEY UPDATE translation_value=VALUES(translation_value);
