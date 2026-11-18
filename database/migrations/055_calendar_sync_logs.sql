CREATE TABLE IF NOT EXISTS calendar_sync_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feed_id BIGINT UNSIGNED NOT NULL,
    property_id BIGINT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NOT NULL,
    status VARCHAR(16) NOT NULL,
    event_count INT UNSIGNED NOT NULL DEFAULT 0,
    blocked_count INT UNSIGNED NOT NULL DEFAULT 0,
    cancelled_count INT UNSIGNED NOT NULL DEFAULT 0,
    conflict_count INT UNSIGNED NOT NULL DEFAULT 0,
    error_message VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_calendar_sync_log_feed (feed_id,id),
    INDEX idx_calendar_sync_log_property (property_id,id),
    CONSTRAINT fk_calendar_sync_log_feed FOREIGN KEY (feed_id) REFERENCES property_calendar_feeds(id) ON DELETE CASCADE,
    CONSTRAINT fk_calendar_sync_log_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
