ALTER TABLE properties
 ADD COLUMN weekend_nightly_price DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER base_nightly_price,
 ADD COLUMN cleaning_fee DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER weekend_nightly_price;
