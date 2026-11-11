-- Link existing listings to the canonical locations tree.
-- Location rows are derived only from properties already stored in this installation;
-- no external/geographic dataset is imported by this migration.

INSERT INTO locations (parent_id,location_type,name,sort_order,is_active)
SELECT NULL,'country',src.country,10,1
FROM (SELECT DISTINCT TRIM(country) AS country FROM properties WHERE TRIM(COALESCE(country,''))<>'') src
WHERE NOT EXISTS (
  SELECT 1 FROM locations l
  WHERE l.parent_id IS NULL AND l.location_type='country' AND l.name=src.country
);

INSERT INTO locations (parent_id,location_type,name,sort_order,is_active)
SELECT c.id,'city',src.city,10,1
FROM (
  SELECT DISTINCT TRIM(p.country) AS country,TRIM(p.city) AS city
  FROM properties p
  WHERE TRIM(COALESCE(p.country,''))<>'' AND TRIM(COALESCE(p.city,''))<>''
) src
JOIN locations c ON c.parent_id IS NULL AND c.location_type='country' AND c.name=src.country
WHERE NOT EXISTS (
  SELECT 1 FROM locations l
  WHERE l.parent_id=c.id AND l.location_type='city' AND l.name=src.city
);

INSERT INTO locations (parent_id,location_type,name,sort_order,is_active)
SELECT c.id,'district',src.district,10,1
FROM (
  SELECT DISTINCT TRIM(p.country) AS country,TRIM(p.city) AS city,TRIM(p.district) AS district
  FROM properties p
  WHERE TRIM(COALESCE(p.country,''))<>'' AND TRIM(COALESCE(p.city,''))<>'' AND TRIM(COALESCE(p.district,''))<>''
) src
JOIN locations country ON country.parent_id IS NULL AND country.location_type='country' AND country.name=src.country
JOIN locations c ON c.parent_id=country.id AND c.location_type='city' AND c.name=src.city
WHERE NOT EXISTS (
  SELECT 1 FROM locations l
  WHERE l.parent_id=c.id AND l.location_type='district' AND l.name=src.district
);

INSERT INTO locations (parent_id,location_type,name,sort_order,is_active)
SELECT d.id,'neighborhood',src.neighborhood,10,1
FROM (
  SELECT DISTINCT TRIM(p.country) AS country,TRIM(p.city) AS city,TRIM(p.district) AS district,TRIM(p.neighborhood) AS neighborhood
  FROM properties p
  WHERE TRIM(COALESCE(p.country,''))<>'' AND TRIM(COALESCE(p.city,''))<>'' AND TRIM(COALESCE(p.district,''))<>'' AND TRIM(COALESCE(p.neighborhood,''))<>''
) src
JOIN locations country ON country.parent_id IS NULL AND country.location_type='country' AND country.name=src.country
JOIN locations city ON city.parent_id=country.id AND city.location_type='city' AND city.name=src.city
JOIN locations d ON d.parent_id=city.id AND d.location_type='district' AND d.name=src.district
WHERE NOT EXISTS (
  SELECT 1 FROM locations l
  WHERE l.parent_id=d.id AND l.location_type='neighborhood' AND l.name=src.neighborhood
);

UPDATE properties p
JOIN locations country ON country.parent_id IS NULL AND country.location_type='country' AND country.name=TRIM(p.country)
JOIN locations city ON city.parent_id=country.id AND city.location_type='city' AND city.name=TRIM(p.city)
LEFT JOIN locations district ON district.parent_id=city.id AND district.location_type='district' AND district.name=TRIM(p.district)
LEFT JOIN locations neighborhood ON neighborhood.parent_id=district.id AND neighborhood.location_type='neighborhood' AND neighborhood.name=TRIM(p.neighborhood)
SET p.location_country_id=country.id,
    p.location_city_id=city.id,
    p.location_district_id=district.id,
    p.location_neighborhood_id=neighborhood.id
WHERE p.location_country_id IS NULL
   OR p.location_city_id IS NULL
   OR (TRIM(COALESCE(p.district,''))<>'' AND p.location_district_id IS NULL)
   OR (TRIM(COALESCE(p.neighborhood,''))<>'' AND p.location_neighborhood_id IS NULL);
