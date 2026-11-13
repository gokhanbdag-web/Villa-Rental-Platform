-- Give each of the 36 built-in demo villas its own five distinct photo URLs.
-- Covers (sort_order 0) and gallery images (sort_order 1-4) use separate locked image selections.
-- Only the known demo-villa slugs are touched; customer/vendor listings remain unchanged.
-- The migration runner records this file once, so existing admin photo edits are not overwritten again.

UPDATE property_media pm
INNER JOIN properties p ON p.id = pm.property_id
SET
    pm.file_path = CONCAT(
        'https://loremflickr.com/1400/900/',
        CASE pm.sort_order
            WHEN 0 THEN 'villa,modern,exterior,pool'
            WHEN 1 THEN 'villa,modern,interior,living-room'
            WHEN 2 THEN 'villa,modern,bedroom,interior'
            WHEN 3 THEN 'villa,modern,terrace,garden'
            WHEN 4 THEN 'villa,modern,kitchen,interior'
            ELSE 'villa,modern,architecture'
        END,
        '?lock=',
        (CASE p.slug
        WHEN 'kas-vista-villa' THEN 32000
        WHEN 'kalkan-infinity' THEN 32010
        WHEN 'oludeniz-blue' THEN 32020
        WHEN 'bodrum-stone' THEN 32030
        WHEN 'cesme-aegean' THEN 32040
        WHEN 'kas-garden' THEN 32050
        WHEN 'fethiye-pine' THEN 32060
        WHEN 'datca-cove' THEN 32070
        WHEN 'antalya-terrace' THEN 32080
        WHEN 'sapanca-lake' THEN 32090
        WHEN 'sapanca-forest' THEN 32100
        WHEN 'ayvalik-olive' THEN 32110
        WHEN 'urla-vineyard' THEN 32120
        WHEN 'marmaris-bay' THEN 32130
        WHEN 'gocek-marina' THEN 32140
        WHEN 'akyaka-river' THEN 32150
        WHEN 'kas-horizon' THEN 32160
        WHEN 'kalkan-courtyard' THEN 32170
        WHEN 'fethiye-sunset' THEN 32180
        WHEN 'bodrum-white' THEN 32190
        WHEN 'mugla-citrus' THEN 32200
        WHEN 'antalya-palm' THEN 32210
        WHEN 'kas-bougainvillea' THEN 32220
        WHEN 'koycegiz-lake' THEN 32230
        WHEN 'foca-coast' THEN 32240
        WHEN 'gokova-view' THEN 32250
        WHEN 'kalkan-sapphire' THEN 32260
        WHEN 'bodrum-citrus' THEN 32270
        WHEN 'fethiye-lagoon' THEN 32280
        WHEN 'sapanca-glass' THEN 32290
        WHEN 'assos-olive' THEN 32300
        WHEN 'cesme-breeze' THEN 32310
        WHEN 'marmaris-marina' THEN 32320
        WHEN 'datca-bay' THEN 32330
        WHEN 'urla-olive' THEN 32340
        WHEN 'bozcaada-wind' THEN 32350
        END) + pm.sort_order
    ),
    pm.alt_text = CONCAT(
        p.title,
        ' - ',
        CASE pm.sort_order
            WHEN 0 THEN 'kapak fotoğrafı'
            WHEN 1 THEN 'salon ve yaşam alanı'
            WHEN 2 THEN 'yatak odası'
            WHEN 3 THEN 'teras ve bahçe'
            WHEN 4 THEN 'mutfak ve iç mekân'
            ELSE 'villa fotoğrafı'
        END
    )
WHERE p.slug IN ('kas-vista-villa', 'kalkan-infinity', 'oludeniz-blue', 'bodrum-stone', 'cesme-aegean', 'kas-garden', 'fethiye-pine', 'datca-cove', 'antalya-terrace', 'sapanca-lake', 'sapanca-forest', 'ayvalik-olive', 'urla-vineyard', 'marmaris-bay', 'gocek-marina', 'akyaka-river', 'kas-horizon', 'kalkan-courtyard', 'fethiye-sunset', 'bodrum-white', 'mugla-citrus', 'antalya-palm', 'kas-bougainvillea', 'koycegiz-lake', 'foca-coast', 'gokova-view', 'kalkan-sapphire', 'bodrum-citrus', 'fethiye-lagoon', 'sapanca-glass', 'assos-olive', 'cesme-breeze', 'marmaris-marina', 'datca-bay', 'urla-olive', 'bozcaada-wind')
  AND pm.sort_order BETWEEN 0 AND 4;
