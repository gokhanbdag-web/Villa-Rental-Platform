ALTER TABLE properties ADD COLUMN meta_title VARCHAR(220) NULL AFTER description, ADD COLUMN meta_description VARCHAR(320) NULL AFTER meta_title;
