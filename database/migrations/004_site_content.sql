CREATE TABLE IF NOT EXISTS site_content (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 content_key VARCHAR(190) NOT NULL,
 language_code VARCHAR(12) NOT NULL DEFAULT 'tr',
 content_value LONGTEXT NOT NULL,
 updated_at DATETIME NULL,
 UNIQUE KEY uq_site_content (content_key, language_code),
 CONSTRAINT fk_site_content_language FOREIGN KEY (language_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS navigation_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 location VARCHAR(40) NOT NULL DEFAULT 'header',
 language_code VARCHAR(12) NOT NULL DEFAULT 'tr',
 label VARCHAR(120) NOT NULL,
 url VARCHAR(500) NOT NULL,
 target VARCHAR(20) NOT NULL DEFAULT '_self',
 sort_order INT NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 INDEX idx_navigation_location (location, language_code, is_active, sort_order),
 UNIQUE KEY uq_navigation_link (location, language_code, url(191)),
 CONSTRAINT fk_navigation_language FOREIGN KEY (language_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO site_content (content_key,language_code,content_value) VALUES
('site_name','tr','VillaStay'),
('home_eyebrow','tr','Kendinize ait bir kaçamak'),
('home_title','tr','Bir sonraki tatilinizin hikâyesi burada başlasın'),
('home_subtitle','tr','Size özel havuzlu villaları, eşsiz manzaraları ve unutulmaz konaklama deneyimlerini keşfedin.'),
('home_search_placeholder','tr','Nereye gitmek istersiniz?'),
('featured_title','tr','Öne çıkan konaklama yerleri'),
('featured_subtitle','tr','Bir sonraki tatiliniz için özenle seçilmiş villalar'),
('empty_properties','tr','Aramanıza uygun konaklama yeri bulunamadı.'),
('footer_text','tr','Hayalinizdeki tatil için doğru konaklama yerini keşfedin.'),
('currency_symbol','tr','₺');

INSERT IGNORE INTO navigation_items (location,language_code,label,url,target,sort_order,is_active) VALUES
('header','tr','Konaklamalar','/','_self',10,1),
('header','tr','Giriş Yap','/login','_self',20,1)
;