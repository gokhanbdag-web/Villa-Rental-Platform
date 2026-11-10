CREATE TABLE IF NOT EXISTS homepage_sections (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  section_key VARCHAR(40) NOT NULL,
  title VARCHAR(190) NOT NULL DEFAULT '',
  subtitle VARCHAR(500) NOT NULL DEFAULT '',
  image_url VARCHAR(1000) NOT NULL DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 10,
  PRIMARY KEY (id),
  UNIQUE KEY uq_homepage_sections_key (section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO homepage_sections(section_key,title,subtitle,image_url,is_active,sort_order) VALUES
('hero','Karşılama alanı','Ana sayfa büyük başlık ve arama alanı','',1,1),
('destinations','Popüler destinasyonlar','En çok ilan bulunan şehirler','',1,2),
('featured','En çok tercih edilenler','Öne çıkan tatil konaklamaları','',1,3),
('styles','Tatil tarzını seç','Farklı tatil deneyimleri','',1,4),
('gallery','Tatil galerisi','İlham veren tatil fotoğrafları','',1,5),
('hosts','Ev sahiplerine özel','İlan yayınlama tanıtımı','',1,6);
