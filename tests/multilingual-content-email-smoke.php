<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$read=static fn(string $path):string=>(string)file_get_contents($root.'/'.$path);
$routes=$read('routes/web.php');
$translator=$read('app/Core/Translator.php');
$contentTranslator=$read('app/Core/ContentTranslator.php');
$email=$read('app/Core/EmailService.php');
$schema=$read('database/schema.sql');
$migration=$read('database/migrations/052_multilingual_content_and_email.sql');
$seoMigration=$read('database/migrations/053_property_seo_fields.sql');
$adminView=$read('themes/default/templates/pages/admin/content-translations.php');
$catalogueView=$read('themes/default/templates/pages/admin/translations.php');
$propertyForm=$read('themes/default/templates/pages/admin/property-form.php');
$propertyView=$read('themes/default/templates/pages/property.php');
$listingView=$read('themes/default/templates/pages/listing.php');
$blogIndex=$read('themes/default/templates/pages/blog-index.php');
$blogSingle=$read('themes/default/templates/pages/blog-single.php');

$checks=[
 'translator overlays requested locale on active default-language catalogue'=>
  str_contains($translator,'is_default=1 AND is_active=1')&&str_contains($translator,'$catalogue[$key] = $value;'),
 'content translator supports villa, category, blog and SEO fields'=>
  str_contains($contentTranslator,"'property' => ['title','summary','description','meta_title','meta_description']")&&str_contains($contentTranslator,"'property_category'")&&str_contains($contentTranslator,"'blog_post'")&&str_contains($contentTranslator,"'blog_category'"),
 'content translations are stored in fresh schema and upgrade migration'=>
  str_contains($schema,'CREATE TABLE IF NOT EXISTS content_translations')&&str_contains($migration,'CREATE TABLE IF NOT EXISTS content_translations'),
 'new language translation editor shows default source keys for missing translations'=>
  str_contains($routes,'COALESCE(target.translation_value,base.translation_value)')&&str_contains($catalogueView,'/admin/content-translations'),
 'content translation editor is role-protected, CSRF-protected and validates entities'=>
  str_contains($routes,'/admin/content-translations')&&str_contains($routes,'verify_csrf();')&&str_contains($routes,'Çevrilecek içerik bulunamadı.'),
 'admin can translate properties, villa categories and blog content'=>
  str_contains($routes,"'property_category'=>['table'=>'property_categories'")&&str_contains($routes,"'blog_post'=>['table'=>'blog_posts'")&&str_contains($routes,"'blog_tag'=>['table'=>'blog_tags'"),
 'homepage, search, listing and villa detail overlay localized property data'=>
  substr_count($routes,"ContentTranslator::apply")>=10&&str_contains($routes,"ct.entity_type='property'"),
 'blog archive and detail use localized post, category and tag fields'=>
  str_contains($routes,"'blog_category',$locale")&&str_contains($routes,"'blog_tag',$locale")&&str_contains($routes,"'blog_post',$locale"),
 'site content, menus and CMS pages fall back to default language'=>
  str_contains($routes,'function villa_site_content')&&str_contains($routes,'if(!$rows&&$locale!==$default)')&&str_contains($routes,'$s->execute([$params[\'slug\'],$defaultLocale]);'),
 'booking, message, verification, password reset and SMTP test emails use translation catalogue'=>
  str_contains($routes,"email.booking.created.html")&&str_contains($routes,"email.message.received.html")&&str_contains($routes,"email.account.verify.html")&&str_contains($routes,"email.password.reset.html")&&str_contains($email,"email.smtp.test.html"),
 'property SEO metadata is editable and rendered in active language'=>
  str_contains($seoMigration,'meta_title')&&str_contains($propertyForm,'name="meta_title"')&&str_contains($propertyView,'meta_title')&&str_contains($propertyView,'meta_description'),
 'listing and blog documents advertise their active language and localized SEO'=>
  str_contains($listingView,'seo.listing.title')&&str_contains($blogIndex,'$locale')&&str_contains($blogSingle,'$locale'),
];

$failed=[];
foreach($checks as $name=>$ok){echo ($ok?'PASS':'FAIL').' '.$name.PHP_EOL;if(!$ok)$failed[]=$name;}
if($failed){fwrite(STDERR,'Failed: '.implode('; ',$failed).PHP_EOL);exit(1);}
echo "All multilingual content and email checks passed.\n";
