INSERT INTO translations (language_code, translation_key, translation_value) VALUES
('en','auth.login_title','Administrator sign in'),
('en','auth.login_subtitle','Sign in securely to your administration panel.'),
('en','auth.identity','Email or username'),
('en','auth.password','Password'),
('en','auth.sign_in','Sign in'),
('en','auth.sign_out','Sign out'),
('en','auth.invalid_credentials','Invalid credentials or inactive account.'),
('en','admin.dashboard','Dashboard'),
('en','admin.overview','Overview'),
('en','admin.properties','Properties'),
('en','admin.bookings','Bookings'),
('en','admin.users','Users'),
('en','admin.total_properties','Total properties'),
('en','admin.total_bookings','Total bookings'),
('en','admin.total_users','Total users'),
('en','admin.pending_bookings','Pending bookings')
ON DUPLICATE KEY UPDATE translation_value = VALUES(translation_value);
