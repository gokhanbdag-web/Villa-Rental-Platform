CREATE TABLE IF NOT EXISTS content_translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(40) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    language_code VARCHAR(12) NOT NULL,
    field_key VARCHAR(64) NOT NULL,
    translation_value LONGTEXT NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_content_translation (entity_type, entity_id, language_code, field_key),
    KEY idx_content_translation_locale (language_code, entity_type, entity_id),
    CONSTRAINT fk_content_translation_language FOREIGN KEY (language_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO translations(language_code,translation_key,translation_value) VALUES
('tr','email.booking.created.subject','Rezervasyon talebiniz alındı'),
('tr','email.booking.created.html','<h2>Rezervasyon talebiniz alındı</h2><p>Merhaba :name,</p><p><strong>:property</strong> için rezervasyon talebiniz oluşturuldu.</p><p>Rezervasyon kodunuz: <strong>:code</strong></p><p>Giriş: :checkin · Çıkış: :checkout</p><p>Toplam: :total :currency</p><p><a href=":checkout_url">Rezervasyon ayrıntılarını görüntüle</a></p>'),
('tr','email.booking.created.text','Merhaba :name, :property için rezervasyon talebiniz oluşturuldu. Kod: :code. Giriş: :checkin, çıkış: :checkout. Toplam: :total :currency. Ayrıntılar: :checkout_url'),
('en','email.booking.created.subject','Your booking request has been received'),
('en','email.booking.created.html','<h2>Your booking request has been received</h2><p>Hello :name,</p><p>Your booking request for <strong>:property</strong> has been created.</p><p>Booking reference: <strong>:code</strong></p><p>Check-in: :checkin · Check-out: :checkout</p><p>Total: :total :currency</p><p><a href=":checkout_url">View booking details</a></p>'),
('en','email.booking.created.text','Hello :name, your booking request for :property has been created. Reference: :code. Check-in: :checkin, check-out: :checkout. Total: :total :currency. Details: :checkout_url'),
('tr','email.message.received.subject','Rezervasyon görüşmesinde yeni mesajınız var'),
('tr','email.message.received.html','<p>Rezervasyon görüşmesinde yeni bir mesajınız var.</p><p><a href=":messages_url">Mesajı görüntüle</a></p>'),
('tr','email.message.received.text','Rezervasyon görüşmesinde yeni bir mesajınız var: :messages_url'),
('en','email.message.received.subject','You have a new booking message'),
('en','email.message.received.html','<p>You have received a new message about your booking.</p><p><a href=":messages_url">View message</a></p>'),
('en','email.message.received.text','You have received a new message about your booking: :messages_url'),
('tr','email.account.verify.subject','E-posta adresinizi doğrulayın'),
('tr','email.account.verify.html','<p>E-posta adresinizi değiştirmek için aşağıdaki bağlantıyı kullanın. Bağlantı 60 dakika geçerlidir.</p><p><a href=":verify_url">E-posta adresimi doğrula</a></p>'),
('tr','email.account.verify.text','E-posta adresinizi doğrulamak için (60 dakika geçerli): :verify_url'),
('en','email.account.verify.subject','Verify your email address'),
('en','email.account.verify.html','<p>Use the link below to verify your new email address. This link is valid for 60 minutes.</p><p><a href=":verify_url">Verify my email address</a></p>'),
('en','email.account.verify.text','Verify your email address (valid for 60 minutes): :verify_url'),
('tr','email.password.reset.subject','Şifre yenileme bağlantınız'),
('tr','email.password.reset.html','<p>Şifrenizi yenilemek için aşağıdaki bağlantıyı kullanın. Bağlantı 60 dakika geçerlidir ve yalnızca bir kez kullanılabilir.</p><p><a href=":reset_url">Yeni şifre belirle</a></p><p>Bu isteği siz yapmadıysanız bu e-postayı dikkate almayın.</p>'),
('tr','email.password.reset.text','Şifrenizi yenilemek için (60 dakika geçerli): :reset_url'),
('en','email.password.reset.subject','Your password reset link'),
('en','email.password.reset.html','<p>Use the link below to reset your password. It is valid for 60 minutes and can only be used once.</p><p><a href=":reset_url">Set a new password</a></p><p>If you did not request this, you can ignore this email.</p>'),
('en','email.password.reset.text','Reset your password (valid for 60 minutes): :reset_url')
ON DUPLICATE KEY UPDATE translation_value=VALUES(translation_value);

INSERT INTO translations(language_code,translation_key,translation_value) VALUES
('tr','email.smtp.test.subject','Villa platformu SMTP test e-postası'),
('tr','email.smtp.test.html','<p>SMTP bağlantınız başarıyla doğrulandı.</p><p>Villa platformu e-posta teslimat testi.</p>'),
('tr','email.smtp.test.text','SMTP bağlantınız başarıyla doğrulandı. Villa platformu e-posta teslimat testi.'),
('en','email.smtp.test.subject','Villa platform SMTP test email'),
('en','email.smtp.test.html','<p>Your SMTP connection has been verified successfully.</p><p>Villa platform email delivery test.</p>'),
('en','email.smtp.test.text','Your SMTP connection has been verified successfully. Villa platform email delivery test.')
ON DUPLICATE KEY UPDATE translation_value=VALUES(translation_value);
