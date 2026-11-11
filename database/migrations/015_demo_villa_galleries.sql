-- Add a five-photo gallery to every published demo property.
-- Safe to run more than once: existing cover photos and existing gallery rows are preserved.
-- The four additional photos use real property/interior photography hosted by Unsplash.
INSERT INTO property_media (property_id, file_path, alt_text, is_cover, sort_order)
SELECT
    p.id,
    CASE photo.slot
        WHEN 1 THEN 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1400&q=82'
        WHEN 2 THEN 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=1400&q=82'
        WHEN 3 THEN 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1400&q=82'
        WHEN 4 THEN 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1400&q=82'
    END,
    CONCAT(p.title, ' - ', CASE photo.slot
        WHEN 1 THEN 'salon ve yaşam alanı'
        WHEN 2 THEN 'yatak odası'
        WHEN 3 THEN 'teras ve dış mekân'
        ELSE 'mutfak ve iç mekân'
    END),
    0,
    photo.slot
FROM properties p
CROSS JOIN (
    SELECT 1 AS slot UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
) photo
WHERE p.status = 'published'
  AND p.slug IN (
    'kas-vista-villa','kalkan-infinity','oludeniz-blue','bodrum-stone','cesme-aegean',
    'kas-garden','fethiye-pine','datca-cove','antalya-terrace','sapanca-lake',
    'sapanca-forest','ayvalik-olive','urla-vineyard','marmaris-bay','gocek-marina',
    'akyaka-river','kas-horizon','kalkan-courtyard','fethiye-sunset','bodrum-white',
    'mugla-citrus','antalya-palm','kas-bougainvillea','koycegiz-lake','foca-coast',
    'gokova-view','kalkan-sapphire','bodrum-citrus','fethiye-lagoon','sapanca-glass',
    'assos-olive','cesme-breeze','marmaris-marina','datca-bay','urla-olive','bozcaada-wind'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM property_media existing
      WHERE existing.property_id = p.id
        AND existing.sort_order = photo.slot
        AND existing.is_cover = 0
  );
