-- Do not publish the reference business's contact number as the site's own contact.
UPDATE site_content
SET content_value=''
WHERE language_code='tr'
  AND content_key IN ('home_support_phone','home_support_whatsapp')
  AND content_value='+90 252 611 07 48';

UPDATE site_content
SET content_value='Şeffaf ödeme süreci'
WHERE language_code='tr'
  AND content_key='home_trust_4_title'
  AND content_value='Güvenli ödeme süreci';
