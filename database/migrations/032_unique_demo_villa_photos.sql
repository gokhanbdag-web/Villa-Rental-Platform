-- Give each of the 36 built-in demo villas its own five distinct photo URLs.
-- Covers (sort_order 0) and gallery images (sort_order 1-4) use separate locked image selections.
-- Only the known demo-villa slugs are touched; customer/vendor listings remain unchanged.
-- The migration runner records this file once, so existing admin photo edits are not overwritten again.

UPDATE property_media pm
INNER JOIN properties p ON p.id = pm.property_id
SET
    pm.file_path = CONCAT(
        'https://picsum.photos/seed/villa-',
        'https://picsum.photos/seed/villa-',
        p.slug,
        '-',
        pm.sort_order,
        '/1400/900'
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
