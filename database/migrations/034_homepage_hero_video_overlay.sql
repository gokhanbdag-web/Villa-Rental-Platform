-- Add per-homepage-hero YouTube background and adjustable overlay opacity.
ALTER TABLE homepage_sections
    ADD COLUMN video_url VARCHAR(500) NOT NULL DEFAULT '' AFTER image_url,
    ADD COLUMN overlay_opacity TINYINT UNSIGNED NOT NULL DEFAULT 58 AFTER video_url;
