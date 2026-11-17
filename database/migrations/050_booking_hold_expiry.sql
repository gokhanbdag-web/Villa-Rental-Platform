-- Add a finite hold window to unpaid website reservations.
ALTER TABLE bookings
    ADD COLUMN hold_expires_at DATETIME NULL AFTER created_at,
    ADD INDEX idx_bookings_pending_hold (property_id,status,hold_expires_at);

-- Existing pending requests receive the same initial hold window. Manual reports
-- and unresolved provider payments are protected by the expiry service.
UPDATE bookings
SET hold_expires_at = DATE_ADD(created_at, INTERVAL 30 MINUTE)
WHERE status='pending' AND hold_expires_at IS NULL;
