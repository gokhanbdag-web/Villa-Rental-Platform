CREATE TABLE IF NOT EXISTS property_calendar_feeds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(120) NOT NULL,
    direction VARCHAR(12) NOT NULL,
    feed_url_encrypted TEXT NULL,
    export_token_hash CHAR(64) NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    last_sync_status VARCHAR(20) NULL,
    last_sync_message VARCHAR(500) NULL,
    last_synced_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_calendar_export_token (export_token_hash),
    INDEX idx_calendar_feed_property (property_id,direction,enabled),
    CONSTRAINT fk_calendar_feed_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendar_feed_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feed_id BIGINT UNSIGNED NOT NULL,
    property_id BIGINT UNSIGNED NOT NULL,
    external_uid VARCHAR(255) NOT NULL,
    availability_id BIGINT UNSIGNED NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    event_status VARCHAR(20) NOT NULL DEFAULT 'blocked',
    missed_syncs TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_seen_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_calendar_feed_external_uid (feed_id,external_uid),
    INDEX idx_calendar_feed_event_availability (availability_id),
    INDEX idx_calendar_feed_event_property_dates (property_id,date_from,date_to),
    CONSTRAINT fk_calendar_feed_event_feed FOREIGN KEY (feed_id) REFERENCES property_calendar_feeds(id) ON DELETE CASCADE,
    CONSTRAINT fk_calendar_feed_event_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    CONSTRAINT fk_calendar_feed_event_availability FOREIGN KEY (availability_id) REFERENCES availability(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendar_sync_credentials (
    singleton_id TINYINT UNSIGNED PRIMARY KEY,
    token_encrypted VARCHAR(255) NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
