INSERT INTO translations(language_code,translation_key,translation_value) VALUES
('en','account.favorites.title','My favorites'),
('en','account.favorites.intro','Save accommodations you like here.'),
('en','account.favorites.count','saved accommodations'),
('en','account.favorites.empty_title','No favorite accommodations yet'),
('en','account.favorites.empty_help','Tap the heart on a villa you like to add it to your list.'),
('en','account.favorites.discover','Explore accommodations'),
('en','account.favorites.view','View accommodation'),
('en','account.favorites.remove','Remove from favorites'),
('en','account.favorites.remove_error','Could not remove favorite.'),
('en','account.favorites.guests','guests'),
('en','account.favorites.per_night','/ night')
ON DUPLICATE KEY UPDATE translation_value=VALUES(translation_value);
