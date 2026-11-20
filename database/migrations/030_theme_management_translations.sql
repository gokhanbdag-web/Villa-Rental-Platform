INSERT INTO translations (language_code, translation_key, translation_value) VALUES
('en','admin.themes.title','Theme Management'),
('en','admin.themes.intro','Manage installed themes and choose your active design.'),
('en','admin.themes.active','Active'),
('en','admin.themes.base','Base theme'),
('en','admin.themes.version','Version'),
('en','admin.themes.author','Author'),
('en','admin.themes.activate','Activate theme'),
('en','admin.themes.fallback_note','The default theme remains available as a fallback.'),
('en','admin.themes.save_success','Theme settings saved successfully.'),
('en','admin.themes.save_error','Theme settings could not be saved.')
ON DUPLICATE KEY UPDATE translation_value = VALUES(translation_value);
