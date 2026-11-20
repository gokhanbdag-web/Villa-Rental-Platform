INSERT IGNORE INTO property_categories(name,slug,description,is_active,sort_order) VALUES
('Pool Villas','pool-villas','Private villas with swimming pools',1,10),
('Sea View','sea-view','Properties with beautiful sea views',1,20),
('Nature Retreats','nature-retreats','Quiet stays near forests, lakes and natural scenery',1,30),
('Family Villas','family-villas','Spacious villas for families and groups',1,40),
('Boutique Stays','boutique-stays','Private escapes for couples and smaller groups',1,50);

UPDATE properties SET category_id=(SELECT id FROM property_categories WHERE slug='sea-view' LIMIT 1)
WHERE category_id IS NULL AND city IN ('Kas','Kalkan','Fethiye','Bodrum','Datca','Gocek','Marmaris','Cesme');
UPDATE properties SET category_id=(SELECT id FROM property_categories WHERE slug='nature-retreats' LIMIT 1)
WHERE category_id IS NULL AND city IN ('Sapanca','Akyaka','Koycegiz','Urla','Masukiye');
UPDATE properties SET category_id=(SELECT id FROM property_categories WHERE slug='family-villas' LIMIT 1)
WHERE category_id IS NULL AND max_guests>=8;
UPDATE properties SET category_id=(SELECT id FROM property_categories WHERE slug='pool-villas' LIMIT 1)
WHERE category_id IS NULL;
