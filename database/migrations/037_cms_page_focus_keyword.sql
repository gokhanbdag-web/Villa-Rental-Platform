-- Add a focus keyword to regular CMS pages; existing records remain valid.
ALTER TABLE cms_pages ADD COLUMN focus_keyword VARCHAR(120) NOT NULL DEFAULT '' AFTER meta_description;
