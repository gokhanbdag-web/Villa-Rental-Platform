-- Upgrade legacy Turkish defaults once. Later administrator language choices remain effective.
SET @previous_locale = COALESCE((SELECT setting_value FROM settings WHERE setting_key='app.locale' LIMIT 1),(SELECT code FROM languages WHERE is_default=1 LIMIT 1),'en');
INSERT IGNORE INTO languages(code,name,is_default,is_active) VALUES ('en','English',0,1);
UPDATE languages SET is_default=(code='en'), is_active=IF(code='en',1,is_active) WHERE @previous_locale IN ('tr','en');
INSERT INTO settings(setting_key,setting_value,autoload) SELECT 'app.locale','en',1 FROM DUAL WHERE @previous_locale IN ('tr','en') ON DUPLICATE KEY UPDATE setting_value='en';
