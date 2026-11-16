ALTER TABLE property_seasonal_prices
    ADD COLUMN min_nights SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER nightly_price,
    ADD COLUMN checkin_weekdays TINYINT UNSIGNED NOT NULL DEFAULT 127 AFTER min_nights,
    ADD COLUMN checkout_weekdays TINYINT UNSIGNED NOT NULL DEFAULT 127 AFTER checkin_weekdays;
