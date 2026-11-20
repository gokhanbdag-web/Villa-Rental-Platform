INSERT INTO translations(language_code,translation_key,translation_value) VALUES
('en','account.notifications.empty','No notifications yet.'),
('en','account.notifications.title','Notifications'),
('en','account.common.home','Home'),
('en','account.messages.empty','No messages yet. You can send the first message.'),
('en','account.messages.title','Booking messages'),
('en','account.messages.heading','Messages'),
('en','account.messages.your_message','Your message'),
('en','account.messages.send','Send message'),
('en','account.common.back','Go back'),
('en','account.messages.you','You')
ON DUPLICATE KEY UPDATE translation_value=VALUES(translation_value);
