<?php
declare(strict_types=1);

// Theme-specific presentation shell. The shared page supplies all business data and actions.
ob_start();
$pageTemplate = \App\Core\ThemeManager::resolveFile('templates/pages/landing.php');
if ($pageTemplate === null) throw new RuntimeException('Tema sayfa bileşeni bulunamadı: landing');
require $pageTemplate;
$html = (string) ob_get_clean();

$navHtml = '';
if (preg_match('~<nav\\b[^>]*>.*?</nav>~s', $html, $navMatch)) {
    $navHtml = preg_replace('~<nav\\b[^>]*>~', '<nav class="nav" id="siteNavigation">', $navMatch[0], 1) ?? $navMatch[0];
}
if ($navHtml === '') {
    ob_start();
    $navItems = $navigation ?? [];
    $navAllowAuth = true;
    \App\Core\ThemeManager::renderPartial('partials/navigation-items.php', get_defined_vars());
    $navHtml = '<nav class="nav" id="siteNavigation">' . (string) ob_get_clean() . '</nav>';
}

$themeSiteName = (string) ($content['site_name'] ?? $siteName ?? 'VillaStay');
$brand = '<a class="theme-brand theme-brand--coastal" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark" hidden>×</span></button>';
$header = '<header class="theme-header theme-header--coastal"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-home-mediterranean"', $html, 1) ?? $html;
$topPhone=trim((string)($content['home_support_phone']??''));
$topPhoneDigits=preg_replace('/\\D+/','',$topPhone);
if($topPhoneDigits!==''){
    $topbar='<div class="med-topbar"><div class="wrap"><a href="tel:'.e($topPhoneDigits).'">☎ '.e($topPhone).'</a>';
    if(($user['role']??'')==='customer')$topbar.='<a href="'.e(app_url('/favorites')).'">♡ Favorilerim</a>';
    $topbar.='</div></div>';
    $html=preg_replace('~<header\\b~i',$topbar.'<header',$html,1)??$html;
}


/* Mediterranean-only content architecture: all copy comes from site_content and live database records. */
$medSectionActive = static function (string $key) use ($homeSections): bool {
    return !isset($homeSections[$key]) || (int)($homeSections[$key]['is_active'] ?? 1) === 1;
};
$medSectionOrder = static function (string $key, int $fallback) use ($homeSections): int {
    return (int)($homeSections[$key]['sort_order'] ?? $fallback);
};
$medImage = static function (array $row): string {
    $value = (string)($row['cover_image'] ?? '');
    return $value === '' ? '' : (preg_match('~^https?://~i', $value) ? $value : app_url('/' . ltrim($value, '/')));
};
$medBlocks = '';
{
    ob_start();
    if ($medSectionActive('trust')): ?>
    <section class="section med-trust" style="order:<?= $medSectionOrder('trust',2) ?>"><div class="wrap med-trust-grid">
      <?php $trustItems = [
        ['title'=>'home_trust_1_title','text'=>'home_trust_1_text'],
        ['title'=>'home_trust_2_title','text'=>'home_trust_2_text'],
        ['title'=>'home_trust_3_title','text'=>'home_trust_3_text'],
        ['title'=>'home_trust_4_title','text'=>'home_trust_4_text']
      ]; foreach ($trustItems as $i=>$item): ?>
      <article class="med-trust-item"><span class="med-trust-icon" aria-hidden="true"><?= \App\Core\ThemeManager::renderTrustIcon($i, $trustIconSettings ?? []) ?></span><div><h3><?= e($content[$item['title']] ?? '') ?></h3><p><?= e($content[$item['text']] ?? '') ?></p></div></article>
      <?php endforeach; ?>
    </div></section>
    <?php endif;
    if ($medSectionActive('latest') && !empty($latestProperties)): ?>
    <section class="section med-latest" style="order:<?= $medSectionOrder('latest',3) ?>"><div class="wrap">
      <div class="section-head"><div><span class="pill villa-eyebrow"><?= e($content['home_latest_eyebrow'] ?? 'YENİ EKLENENLER') ?></span><h2><?= e($content['home_latest_title'] ?? 'Son Eklenen Villalar') ?></h2><p><?= e($content['home_latest_subtitle'] ?? '') ?></p></div><a class="more" href="<?= e(app_url('/konaklamalar?sort=newest')) ?>"><?= e($content['home_latest_link'] ?? 'Tüm villaları gör ↗') ?></a></div>
      <div class="med-property-grid">
      <?php foreach ($latestProperties as $p):
            $cardImages=$homeCardImages[(int)$p['id']]??[];
            if(!$cardImages && !empty($p['cover_image']))$cardImages=[$medImage($p)];
            $isCardFavorite=in_array((int)$p['id'],$favoritePropertyIds??[],true);
            $filters=[];
            \App\Core\ThemeManager::renderPartial('components/cards/villa-card.php',get_defined_vars());
        endforeach; ?>
      </div>
    </div></section>
    <?php endif;
    if ($medSectionActive('concepts') && !empty($categories)): ?>
    <section class="section med-concepts" style="order:<?= $medSectionOrder('concepts',5) ?>"><div class="wrap">
      <div class="section-head"><div><span class="pill villa-eyebrow"><?= e($content['home_concepts_eyebrow'] ?? 'TATİLİNİZİ KEŞFEDİN') ?></span><h2><?= e($content['home_concepts_title'] ?? 'Size Uygun Tatil Konseptini Seçin') ?></h2><p><?= e($content['home_concepts_subtitle'] ?? '') ?></p></div></div>
      <div class="med-concept-grid"><?php foreach ($categories as $i=>$category): $img=$medImage($category); if($img==='')$img=$stylePhotos[$i%count($stylePhotos)]??''; ?>
        <a class="med-concept-card" href="<?= e(app_url('/konaklamalar?category='.(int)$category['id'])) ?>"><?php if($img): ?><img src="<?= e($img) ?>" alt="<?= e($category['name']) ?>" loading="lazy"><?php endif; ?><span class="med-concept-overlay"></span><span class="med-concept-copy"><strong><?= e($category['name']) ?></strong><small><?= (int)($category['property_count'] ?? 0) ?> <?= e($content['home_concepts_count_label'] ?? 'villa seçeneği') ?></small></span></a>
      <?php endforeach; ?></div>
    </div></section>
    <?php endif;
    if ($medSectionActive('collections') && !empty($homeCategoryCollections)): ?>
    <section class="section med-collections" style="order:<?= $medSectionOrder('collections',7) ?>"><div class="wrap">
      <div class="section-head"><div><span class="pill villa-eyebrow"><?= e($content['home_collections_eyebrow'] ?? 'ÖZENLE HAZIRLANDI') ?></span><h2><?= e($content['home_collections_title'] ?? 'Size Özel Villa Koleksiyonları') ?></h2><p><?= e($content['home_collections_subtitle'] ?? '') ?></p></div></div>
      <?php foreach ($homeCategoryCollections as $collection): ?>
      <div class="med-collection-row"><div class="med-collection-heading"><div><h3><?= e($collection['category']['name']) ?></h3><p><?= (int)$collection['category']['property_count'] ?> <?= e($content['home_collection_count_suffix'] ?? 'yayındaki villa arasından seçtiklerimiz') ?></p></div><a href="<?= e(app_url('/konaklamalar?category='.(int)$collection['category']['id'])) ?>"><?= e($content['home_collection_link'] ?? 'Tümünü gör ↗') ?></a></div><div class="med-property-grid">
      <?php foreach ($collection['properties'] as $p):
            $cardImages=$homeCardImages[(int)$p['id']]??[];
            if(!$cardImages && !empty($p['cover_image']))$cardImages=[$medImage($p)];
            $isCardFavorite=in_array((int)$p['id'],$favoritePropertyIds??[],true);
            $filters=[];
            \App\Core\ThemeManager::renderPartial('components/cards/villa-card.php',get_defined_vars());
        endforeach; ?></div></div>
      <?php endforeach; ?>
    </div></section>
    <?php endif;
    if ($medSectionActive('reviews') && !empty($homeReviews)): ?>
    <section class="section med-reviews" style="order:<?= $medSectionOrder('reviews',8) ?>"><div class="wrap">
      <div class="section-head"><div><span class="pill villa-eyebrow"><?= e($content['home_reviews_eyebrow'] ?? 'MİSAFİR DENEYİMLERİ') ?></span><h2><?= e($content['home_reviews_title'] ?? 'Misafirlerimizin Deneyimleri') ?></h2><p><?= e($content['home_reviews_subtitle'] ?? '') ?></p></div></div>
      <div class="med-review-grid"><?php foreach ($homeReviews as $review): ?><article class="med-review-card"><div class="med-review-stars" aria-label="<?= (int)$review['rating'] ?> üzerinden 10 puan">★★★★★</div><p><?= e((string)$review['body']) ?></p><div class="med-review-author"><strong><?= e($review['username']) ?></strong><span><?= e($review['property_title']) ?></span><small><?= e($content['home_review_verified_label'] ?? 'Doğrulanmış konaklama yorumu') ?> · <?= number_format((float)$review['rating'],1,',','.') ?>/10</small></div></article><?php endforeach; ?></div>
    </div></section>
    <?php endif;
    $faqItems=[]; for($i=1;$i<=5;$i++){ $q=trim((string)($content['home_faq_'.$i.'_question']??''));$a=trim((string)($content['home_faq_'.$i.'_answer']??''));if($q!==''&&$a!=='')$faqItems[]=[$q,$a];}
    if ($medSectionActive('faq') && $faqItems): ?>
    <section class="section med-faq" style="order:<?= $medSectionOrder('faq',9) ?>"><div class="wrap med-faq-layout"><div class="med-faq-intro"><span class="pill villa-eyebrow"><?= e($content['home_faq_eyebrow'] ?? 'YARDIM MERKEZİ') ?></span><h2><?= e($content['home_faq_title'] ?? 'Sıkça Sorulan Sorular') ?></h2><p><?= e($content['home_faq_subtitle'] ?? '') ?></p></div><div class="med-faq-list"><?php foreach($faqItems as $i=>$faq): ?><details class="med-faq-item" <?= $i===0?'open':'' ?>><summary><?= e($faq[0]) ?><span>＋</span></summary><p><?= e($faq[1]) ?></p></details><?php endforeach; ?></div></div></section>
    <?php endif;
    if ($medSectionActive('support')): $phone=trim((string)($content['home_support_phone']??''));$wa=trim((string)($content['home_support_whatsapp']??$phone));$phoneDigits=preg_replace('/\D+/','',$phone);$waDigits=preg_replace('/\D+/','',$wa); ?>
    <section class="section med-support" style="order:<?= $medSectionOrder('support',10) ?>"><div class="wrap"><div class="med-support-panel"><div><span class="pill villa-eyebrow"><?= e($content['home_support_eyebrow'] ?? 'CANLI DESTEK') ?></span><h2><?= e($content['home_support_title'] ?? 'Tatilinizi birlikte planlayalım') ?></h2><p><?= e($content['home_support_subtitle'] ?? '') ?></p></div><div class="med-support-actions"><?php if($phoneDigits): ?><a class="med-support-phone" href="tel:<?= e($phoneDigits) ?>">☎ <?= e($phone) ?></a><?php endif; ?><?php if($waDigits): ?><a class="med-support-whatsapp" href="https://wa.me/<?= e($waDigits) ?>" target="_blank" rel="noopener noreferrer"><?= e($content['home_support_whatsapp_label'] ?? 'WhatsApp ile yazın ↗') ?></a><?php endif; ?></div></div></div></section>
    <?php endif;
    $medBlocks = (string)ob_get_clean();
    if ($medBlocks !== '') $html = preg_replace('~</main>~i', $medBlocks . '</main>', $html, 1) ?? $html;
}
$hostTitle=trim((string)($content['home_host_title']??''));
$hostSubtitle=trim((string)($content['home_host_subtitle']??''));
$hostCta=trim((string)($content['home_host_cta']??''));
if($hostTitle!==''||$hostSubtitle!==''||$hostCta!==''){
    $html=preg_replace_callback('~<div class="host-copy"><h2>.*?</h2><p>.*?</p><a href="[^"]*">.*?</a></div>~s',static function()use($hostTitle,$hostSubtitle,$hostCta):string{
        return '<div class="host-copy"><h2>'.e($hostTitle).'</h2><p>'.e($hostSubtitle).'</p><a href="'.e(app_url('/vendor/register')).'">'.e($hostCta!==''?$hostCta:'Ev sahibi olarak katılın').' ↗</a></div>';
    },$html,1)??$html;
}
$footerColumns = '<div class="med-footer-columns"><div><strong>'.e((string)($content['home_footer_regions_title']??'Popüler Bölgeler')).'</strong><ul>';
foreach(array_slice($destinations??[],0,6) as $destination){$footerColumns.='<li><a href="'.e(app_url('/konaklamalar?location_id='.(int)$destination['location_id'])).'">'.e($destination['city']).'</a></li>';}
$footerColumns.='</ul></div><div><strong>'.e((string)($content['home_footer_categories_title']??'Villa Konseptleri')).'</strong><ul>';
foreach(array_slice($categories??[],0,6) as $category){$footerColumns.='<li><a href="'.e(app_url('/konaklamalar?category='.(int)$category['id'])).'">'.e($category['name']).'</a></li>';}
$footerColumns.='</ul></div><div><strong>'.e((string)($content['home_footer_contact_title']??'İletişim')).'</strong><ul><li>'.e((string)($content['home_support_phone']??'')).'</li></ul></div></div>';
$html = str_replace('</nav></div></footer>', '</nav>'.$footerColumns.'</div></footer>', $html);
$regionTitle=trim((string)($content['home_regions_title']??''));
$regionSubtitle=trim((string)($content['home_regions_subtitle']??''));
$regionEyebrow=trim((string)($content['home_regions_eyebrow']??'KEŞFETMEYE BAŞLA'));
if($medSectionActive('destinations') && ($regionTitle!==''||$regionSubtitle!==''||$regionEyebrow!=='')){
    $html=preg_replace_callback('~(<span class="pill villa-eyebrow">).*?(</span><h2>).*?(</h2><p>).*?(</p>)~s',static function(array $m)use($regionTitle,$regionSubtitle,$regionEyebrow):string{
        return $m[1].e($regionEyebrow).$m[2].e($regionTitle!==''?$regionTitle:'Bölgeleri keşfedin').$m[3].e($regionSubtitle).$m[4];
    },$html,1)??$html;
}
$html=preg_replace('~<section class="section" style="[^"]*"><div class="wrap"><div class="section-head"><div><span class="pill villa-eyebrow">TATİL TARZINI SEÇ</span>.*?</section>~s','',$html,1)??$html;
$html=preg_replace('~<section class="section" style="[^"]*"><div class="wrap"><div class="section-head"><div><span class="pill villa-eyebrow">TATİL İLHAMIN</span>.*?</section>~s','',$html,1)??$html;







$homeCssPath = \App\Core\\ThemeManager::resolveFile('home.css', 'default');
if ($homeCssPath !== null) {
    $homeCssLink = '<link rel="stylesheet" href="' . e(\App\Core\\ThemeManager::assetUrl('home.css', 'default')) . '?v=' . rawurlencode((string)filemtime($homeCssPath)) . '">';
    $html = str_replace('</head>', $homeCssLink . '</head>', $html);
}
$cardGalleryScript = <<<'JS'
<script>(function(){document.querySelectorAll('[data-card-gallery]').forEach(function(card){if(card.dataset.galleryReady)return;card.dataset.galleryReady='1';var data=card.querySelector('.card-gallery-data'),image=card.querySelector('.card-gallery-image'),count=card.querySelector('.card-gallery-count');if(!data||!image)return;var images=[];try{images=JSON.parse(data.textContent||'[]')}catch(e){}var index=0;card.querySelectorAll('[data-gallery-step]').forEach(function(button){button.addEventListener('click',function(event){event.preventDefault();event.stopPropagation();if(images.length<2)return;index=(index+Number(button.dataset.galleryStep)+images.length)%images.length;image.src=images[index];if(count)count.textContent=(index+1)+' / '+images.length})})})})();</script>
JS;
if (stripos($html, '</body>') !== false) {
    $html = preg_replace('~</body>~i', $cardGalleryScript . '</body>', $html, 1) ?? $html;
}

echo $html;
