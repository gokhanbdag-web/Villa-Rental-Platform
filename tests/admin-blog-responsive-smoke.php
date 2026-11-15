<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$blog=(string)file_get_contents($root.'/themes/default/templates/pages/admin/blog.php');
$checks=[
 'blog layout uses shrink-safe grid columns'=>str_contains($blog,'grid-template-columns:minmax(0,1fr) minmax(260px,315px)'),
 'editor fields stack on compact screens'=>str_contains($blog,'@media(max-width:760px)')&&str_contains($blog,'.blog-fields{grid-template-columns:minmax(0,1fr)'),
 'editor sidebar cards reflow on tablet'=>str_contains($blog,'.blog-layout>aside{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))'),
 'editor inputs and textarea cannot force overflow'=>str_contains($blog,'.blog-editor{display:block;width:100%;max-width:100%;min-width:0'),
 'mobile save bar does not cover form fields'=>str_contains($blog,'.blog-savebar{position:static;justify-content:stretch'),
 'post table has a mobile horizontal scroll wrapper'=>str_contains($blog,'class="blog-table-scroll"')&&str_contains($blog,'overflow-x:auto'),
 'mobile table rules override shared admin table card rules'=>str_contains($blog,'.blog-table{display:table!important'),
 'mobile filters become a single column on narrow phones'=>str_contains($blog,'@media(max-width:420px)')&&str_contains($blog,'.blog-filter{grid-template-columns:minmax(0,1fr)}'),
];
$failed=[];
foreach($checks as $name=>$ok){echo ($ok?'PASS':'FAIL').' '.$name.PHP_EOL;if(!$ok)$failed[]=$name;}
if($failed){fwrite(STDERR,'Failed: '.implode(', ',$failed).PHP_EOL);exit(1);}
echo 'All admin blog responsive checks passed.'.PHP_EOL;
