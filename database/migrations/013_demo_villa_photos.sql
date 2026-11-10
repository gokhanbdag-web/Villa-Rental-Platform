-- Replace the 36 illustrative demo covers with photographic villa imagery.
-- Images are served from Unsplash and remain editable through the listing manager.
UPDATE property_media pm
INNER JOIN properties p ON p.id = pm.property_id
SET
  pm.file_path = CASE p.slug
  WHEN 'kas-vista-villa' THEN 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1400&q=82'
  WHEN 'kalkan-infinity' THEN 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1400&q=82'
  WHEN 'oludeniz-blue' THEN 'https://images.unsplash.com/photo-1757264119016-7e6b568b810d?auto=format&fit=crop&w=1400&q=82'
  WHEN 'bodrum-stone' THEN 'https://images.unsplash.com/photo-1741850419928-aa65b3d5e3a6?auto=format&fit=crop&w=1400&q=82'
  WHEN 'cesme-aegean' THEN 'https://images.unsplash.com/photo-1765999906700-2d4dfd392966?auto=format&fit=crop&w=1400&q=82'
  WHEN 'kas-garden' THEN 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1400&q=82'
  WHEN 'fethiye-pine' THEN 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1400&q=82'
  WHEN 'datca-cove' THEN 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=1400&q=82'
  WHEN 'antalya-terrace' THEN 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1400&q=82'
  WHEN 'sapanca-lake' THEN 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1400&q=82'
  WHEN 'sapanca-forest' THEN 'https://images.unsplash.com/photo-1757264119016-7e6b568b810d?auto=format&fit=crop&w=1400&q=82'
  WHEN 'ayvalik-olive' THEN 'https://images.unsplash.com/photo-1741850419928-aa65b3d5e3a6?auto=format&fit=crop&w=1400&q=82'
  WHEN 'urla-vineyard' THEN 'https://images.unsplash.com/photo-1765999906700-2d4dfd392966?auto=format&fit=crop&w=1400&q=82'
  WHEN 'marmaris-bay' THEN 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1400&q=82'
  WHEN 'gocek-marina' THEN 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1400&q=82'
  WHEN 'akyaka-river' THEN 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=1400&q=82'
  WHEN 'kas-horizon' THEN 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1400&q=82'
  WHEN 'kalkan-courtyard' THEN 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1400&q=82'
  WHEN 'fethiye-sunset' THEN 'https://images.unsplash.com/photo-1757264119016-7e6b568b810d?auto=format&fit=crop&w=1400&q=82'
  WHEN 'bodrum-white' THEN 'https://images.unsplash.com/photo-1741850419928-aa65b3d5e3a6?auto=format&fit=crop&w=1400&q=82'
  WHEN 'mugla-citrus' THEN 'https://images.unsplash.com/photo-1765999906700-2d4dfd392966?auto=format&fit=crop&w=1400&q=82'
  WHEN 'antalya-palm' THEN 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1400&q=82'
  WHEN 'kas-bougainvillea' THEN 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1400&q=82'
  WHEN 'koycegiz-lake' THEN 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=1400&q=82'
  WHEN 'foca-coast' THEN 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1400&q=82'
  WHEN 'gokova-view' THEN 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1400&q=82'
  WHEN 'kalkan-sapphire' THEN 'https://images.unsplash.com/photo-1757264119016-7e6b568b810d?auto=format&fit=crop&w=1400&q=82'
  WHEN 'bodrum-citrus' THEN 'https://images.unsplash.com/photo-1741850419928-aa65b3d5e3a6?auto=format&fit=crop&w=1400&q=82'
  WHEN 'fethiye-lagoon' THEN 'https://images.unsplash.com/photo-1765999906700-2d4dfd392966?auto=format&fit=crop&w=1400&q=82'
  WHEN 'sapanca-glass' THEN 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1400&q=82'
  WHEN 'assos-olive' THEN 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1400&q=82'
  WHEN 'cesme-breeze' THEN 'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=1400&q=82'
  WHEN 'marmaris-marina' THEN 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1400&q=82'
  WHEN 'datca-bay' THEN 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1400&q=82'
  WHEN 'urla-olive' THEN 'https://images.unsplash.com/photo-1757264119016-7e6b568b810d?auto=format&fit=crop&w=1400&q=82'
  WHEN 'bozcaada-wind' THEN 'https://images.unsplash.com/photo-1741850419928-aa65b3d5e3a6?auto=format&fit=crop&w=1400&q=82'
    ELSE pm.file_path
  END,
  pm.alt_text = CONCAT(p.title, ' - tatil villası fotoğrafı')
WHERE p.slug IN ('kas-vista-villa', 'kalkan-infinity', 'oludeniz-blue', 'bodrum-stone', 'cesme-aegean', 'kas-garden', 'fethiye-pine', 'datca-cove', 'antalya-terrace', 'sapanca-lake', 'sapanca-forest', 'ayvalik-olive', 'urla-vineyard', 'marmaris-bay', 'gocek-marina', 'akyaka-river', 'kas-horizon', 'kalkan-courtyard', 'fethiye-sunset', 'bodrum-white', 'mugla-citrus', 'antalya-palm', 'kas-bougainvillea', 'koycegiz-lake', 'foca-coast', 'gokova-view', 'kalkan-sapphire', 'bodrum-citrus', 'fethiye-lagoon', 'sapanca-glass', 'assos-olive', 'cesme-breeze', 'marmaris-marina', 'datca-bay', 'urla-olive', 'bozcaada-wind')
  AND pm.is_cover = 1;
