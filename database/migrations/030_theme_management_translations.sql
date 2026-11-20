INSERT INTO translations (language_code, translation_key, translation_value) VALUES
('en','admin.themes.title','Theme management'),
('en','admin.themes.intro','Installed themes'),
('en','admin.themes.active','Activate'),
('en','admin.themes.base','Customize'),
('en','admin.themes.version','Preview'),
('en','admin.themes.author','Theme settings'),
('en','admin.themes.activate','Import demo'),
('en','admin.themes.fallback_note','Restore demo'),
('en','admin.themes.save_success','Active theme'),
('en','admin.themes.save_error','Available themes')
ON DUPLICATE KEY UPDATE translation_value = VALUES(translation_value);
