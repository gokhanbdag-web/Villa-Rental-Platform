CREATE TABLE IF NOT EXISTS site_content (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 content_key VARCHAR(190) NOT NULL,
 language_code VARCHAR(12) NOT NULL DEFAULT 'en',
 content_value LONGTEXT NOT NULL,
 updated_at DATETIME NULL,
 UNIQUE KEY uq_site_content (content_key, language_code),
 CONSTRAINT fk_site_content_language FOREIGN KEY (language_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS navigation_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 location VARCHAR(40) NOT NULL DEFAULT 'header',
 language_code VARCHAR(12) NOT NULL DEFAULT 'en',
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
('site_name','en','VillaStay'),
('home_eyebrow','en','Your private escape'),
('home_title','en','Your next getaway starts here'),
('home_subtitle','en','Discover private pool villas, beautiful views and unforgettable stays.'),
('home_search_placeholder','en','Where would you like to go?'),
('featured_title','en','Featured places to stay'),
('featured_subtitle','en','Handpicked villas for your next holiday'),
('empty_properties','en','No properties match your search.'),
('footer_text','en','Find the perfect place for your dream holiday.'),
('currency_symbol','en','₺'),
('search_button','en','Explore villas'),
('guest_select_label','en','Guests'),
('guest_unit','en','guests'),
('all_categories','en','All categories'),
('property_about','en','About this property'),
('property_guests','en','guests'),
('property_bedrooms','en','bedrooms'),
('property_beds','en','beds'),
('property_bathrooms','en','bathrooms'),
('property_reserve','en','Send booking request'),
('property_no_images','en','No photos have been added for this property yet.'),
('booking_notice','en','No payment is collected at this stage. Payment becomes available after host or administrator approval.');

INSERT IGNORE INTO navigation_items (location,language_code,label,url,target,sort_order,is_active) VALUES
('header','en','Properties','/','_self',10,1),
('header','en','Log in','/login','_self',20,1);
