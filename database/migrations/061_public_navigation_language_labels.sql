INSERT INTO translations(language_code,translation_key,translation_value) VALUES
('tr','nav.admin','Yönetim Paneli'),
('en','nav.admin','Admin dashboard'),
('tr','nav.vendor','Ev Sahibi Paneli'),
('en','nav.vendor','Host dashboard'),
('tr','nav.account','Hesabım'),
('en','nav.account','My account'),
('tr','nav.login','Giriş Yap'),
('en','nav.login','Log in'),
('tr','nav.language','Dil'),
('en','nav.language','Language'),
('tr','nav.site_language','Site dili'),
('en','nav.site_language','Site language'),
('tr','nav.apply','Uygula'),
('en','nav.apply','Apply')
ON DUPLICATE KEY UPDATE translation_value=VALUES(translation_value);
