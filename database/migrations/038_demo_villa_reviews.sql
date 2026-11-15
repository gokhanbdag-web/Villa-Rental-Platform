-- Clearly labeled sample ratings for every published villa.
-- Demo reviews have no booking row, cannot be mistaken for verified stays,
-- and are excluded from each customer's private "My Reviews" page.
ALTER TABLE reviews
  MODIFY booking_id BIGINT UNSIGNED NULL,
  ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

INSERT IGNORE INTO users (username,email,password_hash,role,status,created_at)
VALUES
 ('Demo Misafir 01','demo-reviewer-01@demo.invalid','!DEMO_ACCOUNT_DISABLED!','customer','inactive',NOW()),
 ('Demo Misafir 02','demo-reviewer-02@demo.invalid','!DEMO_ACCOUNT_DISABLED!','customer','inactive',NOW()),
 ('Demo Misafir 03','demo-reviewer-03@demo.invalid','!DEMO_ACCOUNT_DISABLED!','customer','inactive',NOW()),
 ('Demo Misafir 04','demo-reviewer-04@demo.invalid','!DEMO_ACCOUNT_DISABLED!','customer','inactive',NOW()),
 ('Demo Misafir 05','demo-reviewer-05@demo.invalid','!DEMO_ACCOUNT_DISABLED!','customer','inactive',NOW());

INSERT INTO reviews (booking_id,property_id,guest_user_id,rating,body,status,created_at,is_demo)
SELECT
 NULL,
 p.id,
 u.id,
 8 + MOD(p.id + samples.slot_no,3),
 CASE samples.slot_no
   WHEN 1 THEN CONCAT(p.title, ' için örnek değerlendirme: Villa temiz, ferah ve fotoğraflardaki gibiydi. Konaklama öncesi iletişim de oldukça rahattı.')
   WHEN 2 THEN CONCAT(p.title, ' için örnek değerlendirme: Konumu ve manzarası tatilimizi güzelleştirdi. Ailemizle keyifli ve huzurlu vakit geçirdik.')
   WHEN 3 THEN CONCAT(p.title, ' için örnek değerlendirme: Mutfak ve yaşam alanları kullanışlıydı. Giriş süreci kolaydı, tekrar tercih edebiliriz.')
   WHEN 4 THEN CONCAT(p.title, ' için örnek değerlendirme: Havuz ve dış alanlar çok keyifliydi. Genel temizlik ve konfor beklentimizi karşıladı.')
   ELSE CONCAT(p.title, ' için örnek değerlendirme: Sessiz bir tatil arayanlar için güzel bir seçenek. Küçük detaylar dışında memnun kaldık.')
 END,
 'approved',
 DATE_SUB(NOW(), INTERVAL (samples.slot_no * 13 + MOD(p.id,17)) DAY),
 1
FROM properties p
CROSS JOIN (
 SELECT 1 AS slot_no UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
) samples
JOIN users u ON u.email=CONCAT('demo-reviewer-',LPAD(samples.slot_no,2,'0'),'@demo.invalid')
LEFT JOIN reviews existing ON existing.property_id=p.id AND existing.guest_user_id=u.id AND existing.is_demo=1
WHERE p.status='published' AND existing.id IS NULL;
