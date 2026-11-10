CREATE TABLE IF NOT EXISTS locations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 parent_id BIGINT UNSIGNED NULL,
 location_type ENUM('country','city','district','neighborhood') NOT NULL,
 name VARCHAR(190) NOT NULL,
 code VARCHAR(32) NULL,
 sort_order INT NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_location_parent_name (parent_id, location_type, name),
 INDEX idx_location_parent_type (parent_id, location_type, is_active),
 CONSTRAINT fk_location_parent FOREIGN KEY (parent_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE properties
 ADD COLUMN location_country_id BIGINT UNSIGNED NULL,
 ADD COLUMN location_city_id BIGINT UNSIGNED NULL,
 ADD COLUMN location_district_id BIGINT UNSIGNED NULL,
 ADD COLUMN location_neighborhood_id BIGINT UNSIGNED NULL,
 ADD COLUMN neighborhood VARCHAR(190) NULL,
 ADD INDEX idx_property_location (location_country_id,location_city_id,location_district_id,location_neighborhood_id),
 ADD CONSTRAINT fk_property_location_country FOREIGN KEY (location_country_id) REFERENCES locations(id) ON DELETE SET NULL,
 ADD CONSTRAINT fk_property_location_city FOREIGN KEY (location_city_id) REFERENCES locations(id) ON DELETE SET NULL,
 ADD CONSTRAINT fk_property_location_district FOREIGN KEY (location_district_id) REFERENCES locations(id) ON DELETE SET NULL,
 ADD CONSTRAINT fk_property_location_neighborhood FOREIGN KEY (location_neighborhood_id) REFERENCES locations(id) ON DELETE SET NULL;
