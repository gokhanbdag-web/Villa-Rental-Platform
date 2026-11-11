-- Upgrade guest reviews from a five-point scale to a ten-point scale.
-- Existing ratings are multiplied by two to preserve their relative value.
UPDATE reviews SET rating = LEAST(10, GREATEST(1, rating * 2));
ALTER TABLE reviews MODIFY rating TINYINT UNSIGNED NOT NULL;
ALTER TABLE reviews ADD INDEX idx_reviews_property_status_created (property_id, status, created_at);
