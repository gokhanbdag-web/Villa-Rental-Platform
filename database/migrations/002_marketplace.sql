CREATE TABLE IF NOT EXISTS vendors (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL UNIQUE,
 business_name VARCHAR(190) NOT NULL,
 legal_name VARCHAR(190) NULL,
 phone VARCHAR(40) NULL,
 status VARCHAR(24) NOT NULL DEFAULT 'pending',
 commission_rate DECIMAL(6,3) NULL,
 created_at DATETIME NOT NULL,
 CONSTRAINT fk_vendor_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS property_categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 parent_id BIGINT UNSIGNED NULL,
 name VARCHAR(190) NOT NULL,
 slug VARCHAR(210) NOT NULL UNIQUE,
 description TEXT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0,
 CONSTRAINT fk_category_parent FOREIGN KEY (parent_id) REFERENCES property_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS properties (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 vendor_id BIGINT UNSIGNED NULL,
 category_id BIGINT UNSIGNED NULL,
 title VARCHAR(220) NOT NULL,
 slug VARCHAR(240) NOT NULL UNIQUE,
 summary VARCHAR(500) NULL,
 description LONGTEXT NULL,
 country_code CHAR(2) NULL,
 country VARCHAR(100) NULL,
 city VARCHAR(120) NULL,
 district VARCHAR(120) NULL,
 address TEXT NULL,
 latitude DECIMAL(10,7) NULL,
 longitude DECIMAL(10,7) NULL,
 max_guests SMALLINT UNSIGNED NOT NULL DEFAULT 1,
 bedrooms SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 beds SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 bathrooms DECIMAL(4,1) NOT NULL DEFAULT 1,
 base_nightly_price DECIMAL(12,2) NOT NULL DEFAULT 0,
 currency CHAR(3) NOT NULL DEFAULT 'TRY',
 status VARCHAR(24) NOT NULL DEFAULT 'draft',
 booking_type VARCHAR(24) NOT NULL DEFAULT 'request',
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 INDEX idx_property_search (status, city, base_nightly_price),
 CONSTRAINT fk_property_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL,
 CONSTRAINT fk_property_category FOREIGN KEY (category_id) REFERENCES property_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS property_media (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 property_id BIGINT UNSIGNED NOT NULL,
 file_path VARCHAR(500) NOT NULL,
 alt_text VARCHAR(255) NULL,
 is_cover TINYINT(1) NOT NULL DEFAULT 0,
 sort_order INT NOT NULL DEFAULT 0,
 CONSTRAINT fk_media_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS amenities (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL UNIQUE,
 icon VARCHAR(80) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS property_amenities (
 property_id BIGINT UNSIGNED NOT NULL,
 amenity_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY (property_id, amenity_id),
 CONSTRAINT fk_pa_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
 CONSTRAINT fk_pa_amenity FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS availability (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 property_id BIGINT UNSIGNED NOT NULL,
 date_from DATE NOT NULL,
 date_to DATE NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'available',
 nightly_price DECIMAL(12,2) NULL,
 INDEX idx_availability_dates (property_id, date_from, date_to),
 CONSTRAINT fk_availability_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booking_code VARCHAR(32) NOT NULL UNIQUE,
 property_id BIGINT UNSIGNED NOT NULL,
 guest_user_id BIGINT UNSIGNED NOT NULL,
 check_in DATE NOT NULL,
 check_out DATE NOT NULL,
 guests SMALLINT UNSIGNED NOT NULL,
 subtotal DECIMAL(12,2) NOT NULL,
 fees DECIMAL(12,2) NOT NULL DEFAULT 0,
 total DECIMAL(12,2) NOT NULL,
 currency CHAR(3) NOT NULL,
 status VARCHAR(24) NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL,
 INDEX idx_booking_dates (property_id, check_in, check_out, status),
 CONSTRAINT fk_booking_property FOREIGN KEY (property_id) REFERENCES properties(id),
 CONSTRAINT fk_booking_guest FOREIGN KEY (guest_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booking_id BIGINT UNSIGNED NOT NULL UNIQUE,
 property_id BIGINT UNSIGNED NOT NULL,
 guest_user_id BIGINT UNSIGNED NOT NULL,
 rating TINYINT UNSIGNED NOT NULL,
 body TEXT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL,
 CONSTRAINT fk_review_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
 CONSTRAINT fk_review_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
 CONSTRAINT fk_review_guest FOREIGN KEY (guest_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 conversation_key CHAR(64) NOT NULL,
 sender_user_id BIGINT UNSIGNED NOT NULL,
 recipient_user_id BIGINT UNSIGNED NOT NULL,
 booking_id BIGINT UNSIGNED NULL,
 body TEXT NOT NULL,
 read_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_message_conversation (conversation_key, created_at),
 CONSTRAINT fk_message_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_message_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_message_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS favorites (
 user_id BIGINT UNSIGNED NOT NULL,
 property_id BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 PRIMARY KEY (user_id, property_id),
 CONSTRAINT fk_favorite_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_favorite_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_pages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 language_code VARCHAR(12) NOT NULL,
 title VARCHAR(220) NOT NULL,
 slug VARCHAR(240) NOT NULL,
 content LONGTEXT NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'draft',
 meta_title VARCHAR(220) NULL,
 meta_description VARCHAR(320) NULL,
 UNIQUE KEY uq_cms_slug_lang (language_code, slug),
 CONSTRAINT fk_cms_language FOREIGN KEY (language_code) REFERENCES languages(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NULL,
 action VARCHAR(120) NOT NULL,
 entity_type VARCHAR(80) NULL,
 entity_id BIGINT UNSIGNED NULL,
 ip_address VARCHAR(45) NULL,
 details JSON NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_audit_created (created_at),
 CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
