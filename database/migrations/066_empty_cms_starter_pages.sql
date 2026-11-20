-- Recover installations where Page Management is empty after a non-demo setup.
-- Drafts are intentionally unpublished: existing frontend routes remain untouched.
-- Only seed a genuinely empty CMS, never overwrite real administrator pages.
INSERT INTO cms_pages (language_code,title,slug,content,status,meta_title,meta_description)
SELECT l.code, starter.title, starter.slug, '', 'draft', starter.title, ''
FROM (SELECT code FROM languages WHERE is_active=1 ORDER BY is_default DESC,code LIMIT 1) AS l
CROSS JOIN (
 SELECT 'Home' AS title,'home' AS slug
 UNION ALL SELECT 'About Us','about-us'
 UNION ALL SELECT 'Contact','contact'
 UNION ALL SELECT 'Frequently Asked Questions','faq'
 UNION ALL SELECT 'Privacy Policy','privacy-policy'
 UNION ALL SELECT 'Terms and Conditions','terms-and-conditions'
) AS starter
WHERE NOT EXISTS (SELECT 1 FROM cms_pages);
INSERT INTO cms_page_layouts (page_key,language_code,layout_json)
SELECT CONCAT('page:',p.id),p.language_code,'{"rows":[]}'
FROM cms_pages p
LEFT JOIN cms_page_layouts layout ON layout.page_key=CONCAT('page:',p.id) AND layout.language_code=p.language_code
WHERE layout.id IS NULL;
