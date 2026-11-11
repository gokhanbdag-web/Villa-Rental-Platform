-- Allow administrators to record reservations made outside the website.
-- Existing customer bookings remain linked to their user accounts.
ALTER TABLE bookings
 MODIFY COLUMN guest_user_id BIGINT UNSIGNED NULL,
 ADD COLUMN guest_name VARCHAR(190) NULL AFTER guest_user_id,
 ADD COLUMN guest_email VARCHAR(190) NULL AFTER guest_name,
 ADD COLUMN guest_phone VARCHAR(40) NULL AFTER guest_email,
 ADD COLUMN booking_source VARCHAR(30) NOT NULL DEFAULT 'website' AFTER guest_phone;
