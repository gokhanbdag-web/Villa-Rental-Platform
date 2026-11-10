INSERT INTO translations (language_code, translation_key, translation_value) VALUES
('tr','auth.login_title','Yönetici girişi'),
('tr','auth.login_subtitle','Yönetim panelinize güvenli şekilde giriş yapın.'),
('tr','auth.identity','E-posta veya kullanıcı adı'),
('tr','auth.password','Şifre'),
('tr','auth.sign_in','Giriş yap'),
('tr','auth.sign_out','Çıkış'),
('tr','auth.invalid_credentials','Giriş bilgileri hatalı veya hesabınız aktif değil.'),
('tr','admin.dashboard','Yönetim paneli'),
('tr','admin.overview','Genel bakış'),
('tr','admin.properties','Villalar'),
('tr','admin.bookings','Rezervasyonlar'),
('tr','admin.users','Kullanıcılar'),
('tr','admin.total_properties','Toplam ilan'),
('tr','admin.total_bookings','Toplam rezervasyon'),
('tr','admin.total_users','Toplam kullanıcı'),
('tr','admin.pending_bookings','Bekleyen rezervasyonlar')
ON DUPLICATE KEY UPDATE translation_value = VALUES(translation_value);
