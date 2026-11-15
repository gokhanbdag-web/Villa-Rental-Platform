<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$routes=(string)file_get_contents($root.'/routes/web.php');
$migration=(string)file_get_contents($root.'/database/migrations/036_wordpress_style_blog.sql');
$admin=(string)file_get_contents($root.'/themes/default/templates/pages/admin/blog.php');
$archive=(string)file_get_contents($root.'/themes/default/templates/pages/blog-index.php');
$single=(string)file_get_contents($root.'/themes/default/templates/pages/blog-single.php');
$checks=[
 'blog archive route'=>str_contains($routes,'$router->get(\'/blog\''),
 'category archive route'=>str_contains($routes,'$router->get(\'/blog/kategori/{slug}\''),
 'tag archive route'=>str_contains($routes,'$router->get(\'/blog/etiket/{slug}\''),
 'single post route'=>str_contains($routes,'$router->get(\'/blog/{slug}\''),
 'admin category routes'=>str_contains($routes,'$router->get(\'/admin/blog/categories\'')&&str_contains($routes,'$router->post(\'/admin/blog/categories/save\''),
 'SEO metadata persistence'=>str_contains($routes,'meta_title=?,meta_description=?,focus_keyword=?'),
 'category and tag relations'=>str_contains($routes,'blog_post_category')&&str_contains($routes,'blog_post_tag'),
 'future scheduled post visibility'=>str_contains($routes,'p.published_at<=NOW()'),
 'HTML allowlist sanitization'=>str_contains($routes,'strip_tags($html'),
 'blog SEO database fields'=>str_contains($migration,'ADD COLUMN meta_title')&&str_contains($migration,'ADD COLUMN meta_description'),
 'blog categories and tags schema'=>str_contains($migration,'CREATE TABLE IF NOT EXISTS blog_categories')&&str_contains($migration,'CREATE TABLE IF NOT EXISTS blog_tags'),
 '15 demo articles'=>substr_count($migration,'WHERE NOT EXISTS(SELECT 1 FROM blog_posts WHERE slug=')>=15,
 'archive sidebar widgets'=>str_contains($archive,'blog-sidebar')&&str_contains($archive,'En çok okunanlar')&&str_contains($archive,'Kategoriler'),
 'single post sidebar and schema'=>str_contains($single,'post-sidebar')&&str_contains($single,'application/ld+json'),
 'admin SEO editor'=>str_contains($admin,'meta_description')&&str_contains($admin,'focus_keyword'),
];
$failed=[];
foreach($checks as $name=>$ok){echo ($ok?'PASS':'FAIL').' '.$name.PHP_EOL;if(!$ok)$failed[]=$name;}
if($failed){fwrite(STDERR,'Failed: '.implode(', ',$failed).PHP_EOL);exit(1);}
echo 'All blog CMS smoke checks passed.'.PHP_EOL;
