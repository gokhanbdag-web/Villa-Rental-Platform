ALTER TABLE properties
    ADD COLUMN early_checkin_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER cleaning_fee,
    ADD COLUMN early_checkin_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER early_checkin_enabled,
    ADD COLUMN late_checkout_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER early_checkin_fee,
    ADD COLUMN late_checkout_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER late_checkout_enabled;

ALTER TABLE bookings
    ADD COLUMN early_checkin_requested TINYINT(1) NOT NULL DEFAULT 0 AFTER fees,
    ADD COLUMN early_checkin_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER early_checkin_requested,
    ADD COLUMN late_checkout_requested TINYINT(1) NOT NULL DEFAULT 0 AFTER early_checkin_fee,
    ADD COLUMN late_checkout_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER late_checkout_requested;
