INSERT IGNORE INTO property_categories(name,slug,description,is_active,sort_order) VALUES
('Havuzlu Villalar','havuzlu-villalar','Özel havuzlu konaklama seçenekleri',1,10),
('Deniz Manzaralı','deniz-manzarali','Deniz manzarası sunan konaklama yerleri',1,20),
('Doğa İçinde','doga-icinde','Orman, göl ve doğa çevresinde konaklama',1,30),
('Aile Villaları','aile-villalari','Aile ve grup tatilleri için geniş konaklamalar',1,40),
('Butik Konaklama','butik-konaklama','Çiftler ve küçük gruplar için özel seçenekler',1,50);

UPDATE properties SET category_id=(SELECT id FROM property_categories WHERE slug='deniz-manzarali' LIMIT 1)
WHERE category_id IS NULL AND city IN ('Kaş','Kalkan','Fethiye','Bodrum','Datça','Göcek','Marmaris','Çeşme');

UPDATE properties SET category_id=(SELECT id FROM property_categories WHERE slug='doga-icinde' LIMIT 1)
WHERE category_id IS NULL AND city IN ('Sapanca','Akyaka','Köyceğiz','Urla','Maşukiye');

UPDATE properties SET category_id=(SELECT id FROM property_categories WHERE slug='aile-villalari' LIMIT 1)
WHERE category_id IS NULL AND max_guests>=8;

UPDATE properties SET category_id=(SELECT id FROM property_categories WHERE slug='havuzlu-villalar' LIMIT 1)
WHERE category_id IS NULL;
