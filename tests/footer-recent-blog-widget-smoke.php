<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$themeManager=(string)file_get_contents($root.'/app/Core/ThemeManager.php');
$footer=(string)file_get_contents($root.'/themes/default/templates/partials/footer.php');
$admin=(string)file_get_contents($root.'/themes/default/templates/pages/admin/theme-design.php');
$routes=(string)file_get_contents($root.'/routes/web.php');
$checks=[
 'footer defaults include recent blog widget settings'=>str_contains($themeManager,"'recent_blog_title'=>'Son Blog Yazıları'")&&str_contains($themeManager,"'recent_blog_post_ids'=>[]"),
 'widget title and selected post ids are normalized'=>str_contains($themeManager,"$clean['recent_blog_title']")&&str_contains($themeManager,"$clean['recent_blog_post_ids']"),
 'admin widget exposes configurable title'=>str_contains($admin,'footerRecentBlogTitle')&&str_contains($admin,'footer[recent_blog_title]'),
 'admin widget allows selecting individual blog posts'=>str_contains($admin,'footer[recent_blog_post_ids][]')&&str_contains($admin,'$themeFooterBlogPosts'),
 'admin route loads published posts for selector'=>str_contains($routes,'$themeFooterBlogPosts = Database::connection()->query("SELECT id,title,slug,featured_image,status,published_at FROM blog_posts'),
 'footer renders selected published posts in configured order'=>str_contains($footer,'$footerRecentBlogIds')&&str_contains($footer,'$recentById[$recentId]'),
 'footer links to blog posts and shows thumbnails'=>str_contains($footer,"app_url('/blog/' . rawurlencode((string)$recentPost['slug']))")&&str_contains($footer,'featured_image_alt'),
 'widget output is scoped and responsive-safe'=>str_contains($footer,'.footer-recent-blog img')&&str_contains($footer,'overflow-wrap:anywhere'),
 'widget is mounted in footer column 2'=>str_contains($footer,'footer-widget-2')&&str_contains($footer,'footer-recent-blog'),
];
$failed=[];
foreach($checks as $name=>$ok){echo ($ok?'PASS':'FAIL').' '.$name.PHP_EOL;if(!$ok)$failed[]=$name;}
if($failed){fwrite(STDERR,'Failed: '.implode(', ',$failed).PHP_EOL);exit(1);}
echo 'All footer recent blog widget checks passed.'.PHP_EOL;
