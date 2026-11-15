<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$routes=(string)file_get_contents($root.'/routes/web.php');
$pageForm=(string)file_get_contents($root.'/themes/default/templates/pages/admin/page-form.php');
$blogForm=(string)file_get_contents($root.'/themes/default/templates/pages/admin/blog.php');
$partial=(string)file_get_contents($root.'/themes/default/templates/partials/seo-analyzer.php');
$pageTemplate=(string)file_get_contents($root.'/themes/default/templates/pages/page.php');
$migration=(string)file_get_contents($root.'/database/migrations/037_cms_page_focus_keyword.sql');
$checks=[
 'page focus keyword saved'=>str_contains($routes,'focus_keyword=? WHERE id=? AND language_code='),
 'page focus keyword inserted'=>str_contains($routes,'meta_description,focus_keyword) VALUES'),
 'page editor exposes focus keyword'=>str_contains($pageForm,'name="focus_keyword"'),
 'blog editor shares analyzer'=>str_contains($blogForm,'partials/seo-analyzer.php'),
 'page editor shares analyzer'=>str_contains($pageForm,'partials/seo-analyzer.php'),
 'live score and snippet preview'=>str_contains($partial,'data-seo-score')&&str_contains($partial,'data-seo-preview-title'),
 'SEO criteria present'=>str_contains($partial,'Meta açıklaması')&&str_contains($partial,'H2/H3')&&str_contains($partial,'alternatif metinleri')&&str_contains($partial,'İç bağlantı'),
 'score capped at 100'=>str_contains($partial,'Math.min(100,score)'),
 'page canonical and robots metadata'=>str_contains($pageTemplate,'rel="canonical"')&&str_contains($pageTemplate,'name="robots"'),
 'versioned page keyword migration'=>str_contains($migration,'ALTER TABLE cms_pages ADD COLUMN focus_keyword'),
];
$failed=[];
foreach($checks as $name=>$ok){echo ($ok?'PASS':'FAIL').' '.$name.PHP_EOL;if(!$ok)$failed[]=$name;}
if($failed){fwrite(STDERR,'Failed: '.implode(', ',$failed).PHP_EOL);exit(1);}
echo 'All SEO analyzer smoke checks passed.'.PHP_EOL;
