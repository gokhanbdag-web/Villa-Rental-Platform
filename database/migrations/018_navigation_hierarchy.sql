-- Add parent-child hierarchy to navigation items. Existing links remain top-level.
ALTER TABLE navigation_items
    ADD COLUMN parent_id BIGINT UNSIGNED NULL AFTER id,
    ADD INDEX idx_navigation_parent_location (parent_id,location,sort_order);
