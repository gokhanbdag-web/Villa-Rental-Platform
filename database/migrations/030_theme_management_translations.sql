INSERT INTO translations (language_code, translation_key, translation_value) VALUES
('tr','admin.themes.title','Tema Yönetimi'),
('tr','admin.themes.intro','Kurulu temaları görüntüleyin ve sitenin aktif temasını seçin. Default tema zorunlu temel katmandır.'),
('tr','admin.themes.active','Aktif tema'),
('tr','admin.themes.base','Zorunlu temel tema'),
('tr','admin.themes.version','Sürüm'),
('tr','admin.themes.author','Geliştirici'),
('tr','admin.themes.activate','Bu temayı etkinleştir'),
('tr','admin.themes.fallback_note','Aktif temada bulunmayan CSS kuralları, bileşenler ve görsel varlıklar default temadan tamamlanır.'),
('tr','admin.themes.save_success','Aktif tema başarıyla değiştirildi.'),
('tr','admin.themes.save_error','Tema etkinleştirilemedi. Lütfen tema dosyalarını ve manifestini kontrol edin.')
ON DUPLICATE KEY UPDATE translation_value = VALUES(translation_value);
