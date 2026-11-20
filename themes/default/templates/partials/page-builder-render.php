<?php declare(strict_types=1);
/** Render saved page widgets with escaped text and validated links. */
$layoutRows=$pageLayout['rows']??[];
?>
<div class="villa-builder-layout">
<?php foreach($layoutRows as $layoutRow): $cols=$layoutRow['columns']??[]; if(!is_array($cols)||!$cols)continue; ?>
<section class="villa-builder-row" style="display:grid;grid-template-columns:repeat(<?= min(6,max(1,count($cols))) ?>,minmax(0,1fr));gap:20px;margin:24px 0">
<?php foreach($cols as $widgets): ?><div class="villa-builder-column">
<?php foreach((is_array($widgets)?$widgets:[]) as $widget): if(!is_array($widget))continue; $type=$widget['type']??'';$text=(string)($widget['text']??'');$url=(string)($widget['url']??'');$safeUrl=(preg_match('~^(https?://|/|#)~i',$url)&&!str_contains($url,'"')&&!str_contains($url,"'"))?$url:'#'; ?>
<?php if($type==='heading'): ?><h2><?= e($text) ?></h2>
<?php elseif($type==='text'): ?><p><?= nl2br(e($text)) ?></p>
<?php elseif($type==='image'): ?><img src="<?= e($safeUrl) ?>" alt="<?= e((string)($widget['alt']??'')) ?>" loading="lazy" style="width:100%;height:auto;display:block">
<?php elseif($type==='button'): ?><a href="<?= e($safeUrl) ?>" class="villa-builder-button" style="display:inline-block;padding:12px 20px;background:#176b50;color:white;border-radius:8px;text-decoration:none"><?= e($text) ?></a>
<?php elseif($type==='spacer'): ?><div aria-hidden="true" style="height:<?= max(0,min(300,(int)($widget['height']??40))) ?>px"></div>
<?php endif; endforeach; ?></div><?php endforeach; ?></section>
<?php endforeach; ?>
</div>
<style>@media(max-width:700px){.villa-builder-row{grid-template-columns:1fr!important}}</style>
