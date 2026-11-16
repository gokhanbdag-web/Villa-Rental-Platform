CREATE TABLE IF NOT EXISTS property_seasonal_prices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    nightly_price DECIMAL(12,2) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX idx_seasonal_price_property_dates (property_id,date_from,date_to),
    CONSTRAINT fk_seasonal_price_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    CONSTRAINT chk_seasonal_price_dates CHECK (date_to > date_from),
    CONSTRAINT chk_seasonal_price_amount CHECK (nightly_price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
