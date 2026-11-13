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
$homeCss = <<<'CSS'
body.villa-home-mediterranean{background:#f8f8f1;color:var(--villa-text);}body.villa-home-mediterranean .site-header{background:#fbfcf7;border-color:#e4e9dc}body.villa-home-mediterranean .hero{padding:24px 0 54px;background:#eef2e7}body.villa-home-mediterranean .hero-card{min-height:530px;background-position:center 55%;box-shadow:0 22px 55px #344c3518}body.villa-home-mediterranean .hero-copy{max-width:850px}body.villa-home-mediterranean .hero h1{font:inherit;letter-spacing:-2px}body.villa-home-mediterranean .section{padding:66px 0}body.villa-home-mediterranean .section-head h2{font:inherit;color:var(--villa-text);}body.villa-home-mediterranean .destination-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}body.villa-home-mediterranean .destination{height:250px;border-radius:22px}body.villa-home-mediterranean .cards{grid-template-columns:repeat(3,minmax(0,1fr));gap:25px}body.villa-home-mediterranean .stay-photo{height:250px;border-radius:22px}body.villa-home-mediterranean .stay h3{font:inherit;}body.villa-home-mediterranean .home-accommodations{background:#f8f8f1!important}body.villa-home-mediterranean .gallery-strip{grid-template-columns:1.5fr 1fr 1fr;grid-template-rows:210px 210px;gap:15px}body.villa-home-mediterranean .gallery-strip img{border-radius:22px}body.villa-home-mediterranean .host-banner{border-radius:30px}@media(max-width:700px){body.villa-home-mediterranean .hero-card{min-height:560px;}body.villa-home-mediterranean .cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}body.villa-home-mediterranean .stay-photo{height:155px}body.villa-home-mediterranean .destination{height:165px}body.villa-home-mediterranean .section{padding:38px 0}}
CSS;

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

    // Keep the destination directory as the final full-width section, immediately above the footer.
    if (preg_match('~<section class="section" style="order:[^"]*"><div class="wrap"><div class="section-head">.*?<div class="destination-editorial-grid">.*?</div></div></section>~s', $html, $destinationSection)) {
        // Add the class targeted by the full-bleed destination-band styles before moving it.
        $destinationMarkup = preg_replace(
            '~<section class="section"~',
            '<section class="section destination-section"',
            $destinationSection[0],
            1
        ) ?? $destinationSection[0];
        $html = str_replace($destinationSection[0], '', $html);
        // The directory is the final content section, directly before the footer.
        $html = preg_replace('~</main>~i', $destinationMarkup . '</main>', $html, 1) ?? $html;
    }
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

$menuIconCss = '.theme-menu-toggle .menu-open-mark{display:inline!important}.theme-menu-toggle .menu-close-mark,.theme-menu-toggle .menu-close-mark[hidden]{display:none!important}.theme-menu-toggle[aria-expanded="true"] .menu-open-mark{display:none!important}.theme-menu-toggle[aria-expanded="true"] .menu-close-mark{display:inline!important}.theme-menu-toggle[aria-expanded="true"] .menu-close-mark[hidden]{display:inline!important}';

$homeCss .= <<<'CSS'
/* Mediterranean editorial homepage */
body.villa-home-mediterranean{background:#f8f8f1;color:var(--villa-text);font-family:var(--villa-font-body);"Times New Roman",serif}
.med-topbar{background:#304b37;color:var(--villa-text);font:inherit;}.med-topbar>.wrap{display:flex;justify-content:flex-end;align-items:center;gap:22px;min-height:34px}.med-topbar a{color:var(--villa-text);}.med-topbar a:hover{color:var(--villa-text);}
body.villa-home-mediterranean .site-header{background:#fbfcf7;border-color:#e4e9dc}
body.villa-home-mediterranean .brand,body.villa-home-mediterranean .nav a:hover{color:var(--villa-text);}
body.villa-home-mediterranean .hero{background:#eef2e7;padding:22px 0 0}
body.villa-home-mediterranean .hero-card{min-height:560px;box-shadow:0 24px 60px #263e2d20}
body.villa-home-mediterranean .hero h1,body.villa-home-mediterranean .section-head h2,body.villa-home-mediterranean .med-faq-intro h2,body.villa-home-mediterranean .med-support h2{font-family:var(--villa-font-body);"Times New Roman",serif;font-weight:var(--villa-body-weight);letter-spacing:-1.2px}
body.villa-home-mediterranean .search-enhanced{border:1px solid #e4e8db;box-shadow:0 18px 48px #263e2d1c}
body.villa-home-mediterranean .search-enhanced .search-submit{background:#315a43;color:var(--villa-text);}
body.villa-home-mediterranean .section{padding:62px 0}
body.villa-home-mediterranean .section-head h2{color:var(--villa-text);font-size:var(--villa-body-size);}
body.villa-home-mediterranean .section-head p{font-family:var(--villa-font-body);color:var(--villa-text);line-height:var(--villa-body-line-height);}
body.villa-home-mediterranean .pill{background:#e8eee2;color:var(--villa-text);letter-spacing:1px}
body.villa-home-mediterranean .more{color:var(--villa-text);}
body.villa-home-mediterranean .section:has(.styles),body.villa-home-mediterranean .section:has(.gallery-strip){display:none!important}
.med-trust{padding:48px 0!important;margin:0!important;background:#fff;box-sizing:border-box}.med-trust-grid{min-height:64px;align-items:center}
.med-trust-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:22px}
.med-trust-item{display:flex;align-items:center;gap:13px;min-width:0}
.med-trust-icon{display:grid;place-items:center;flex:0 0 44px;width:44px;height:44px;border-radius:50%;background:#edf1e7;color:var(--villa-text);font:inherit;}
.med-trust-item h3{margin:0 0 5px;color:var(--villa-text);font:inherit;}
.med-trust-item p{margin:0;color:var(--villa-text);font:inherit;}
.med-property-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}
.med-property-card{display:block;min-width:0;overflow:hidden;border:1px solid #e7e9df;border-radius:19px;background:#fff;box-shadow:0 10px 30px #273c2c0c;transition:transform .25s,box-shadow .25s}
.med-property-card:hover{transform:translateY(-4px);box-shadow:0 18px 38px #273c2c18}
.med-property-photo{height:215px;overflow:hidden;background:#e8ece3}
.med-property-photo img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .4s}
.med-property-card:hover .med-property-photo img{transform:scale(1.04)}
.med-property-body{padding:15px 16px 18px;font-family:var(--villa-font-body);}
.med-property-place{display:block;color:var(--villa-text);font-size:var(--villa-body-size);letter-spacing:.3px}
.med-property-body h3{margin:7px 0;color:var(--villa-text);font:inherit;}
.med-property-meta{color:var(--villa-text);font-size:var(--villa-body-size);}
.med-property-price{margin-top:12px;color:var(--villa-text);font-size:var(--villa-body-size);}
.med-property-price strong{color:var(--villa-text);font-size:var(--villa-body-size);}
.med-concepts{background:#f0f2e9}
body.villa-home-mediterranean .destination-section{width:100vw;margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw);padding:58px 0!important;background:#f3f4f2!important}
body.villa-home-mediterranean .destination-editorial-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));column-gap:56px;border-top:1px solid #e8eae7}
body.villa-home-mediterranean .destination-editorial-item{position:relative;display:grid;grid-template-columns:54px minmax(0,1fr) 34px;align-items:center;gap:18px;min-width:0;padding:25px 4px;border-bottom:1px solid #e8eae7;color:var(--villa-text);transition:padding .22s ease,color .22s ease}
body.villa-home-mediterranean .destination-editorial-item:hover{padding-left:12px;color:var(--villa-primary)}
body.villa-home-mediterranean .destination-editorial-index{font-size:13px;letter-spacing:.12em;color:var(--villa-muted);font-variant-numeric:tabular-nums}
body.villa-home-mediterranean .destination-editorial-copy{display:flex;flex-direction:column;gap:5px;min-width:0}
body.villa-home-mediterranean .destination-editorial-copy strong{font-family:var(--villa-font-heading,var(--villa-font-body));font-size:clamp(21px,2.2vw,30px);font-weight:650;letter-spacing:-.045em;line-height:1.12}
body.villa-home-mediterranean .destination-editorial-copy small{font-size:13px;color:var(--villa-muted);letter-spacing:.02em}
body.villa-home-mediterranean .destination-editorial-arrow{display:grid;place-items:center;width:34px;height:34px;border:1px solid #e8eae7;border-radius:50%;font-size:17px;transition:transform .22s ease,background .22s ease,color .22s ease}
body.villa-home-mediterranean .destination-editorial-item:hover .destination-editorial-arrow{transform:translate(2px,-2px);background:var(--villa-primary);border-color:var(--villa-primary);color:#fff}
.med-concept-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
.med-concept-card{position:relative;display:block;height:245px;overflow:hidden;border-radius:19px;background:#dfe6d9}
.med-concept-card img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.med-concept-card:hover img{transform:scale(1.05)}
.med-concept-overlay{position:absolute;inset:35% 0 0;background:linear-gradient(transparent,#142b1dcc)}
.med-concept-copy{position:absolute;left:18px;right:15px;bottom:17px;color:var(--villa-text);}
.med-concept-copy strong{display:block;font:inherit;}
.med-concept-copy small{display:block;margin-top:5px;color:var(--villa-text);font:inherit;}
.med-collections{background:#fbfbf6}
.med-collection-row{margin-top:30px}
.med-collection-heading{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:16px}
.med-collection-heading h3{margin:0;color:var(--villa-text);font:inherit;}
.med-collection-heading p{margin:6px 0 0;color:var(--villa-text);font:inherit;}
.med-collection-heading>a{color:var(--villa-text);font:inherit;white-space:nowrap}
.med-property-grid--compact{grid-template-columns:repeat(3,minmax(0,1fr))}
.med-property-grid--compact .med-property-photo{height:190px}
.med-reviews{background:#eef1e7}
.med-review-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.med-review-card{padding:24px;border:1px solid #e0e6d9;border-radius:18px;background:#fff}
.med-review-stars{color:var(--villa-text);letter-spacing:3px;font:inherit;}
.med-review-card>p{min-height:90px;color:var(--villa-text);font:inherit;}
.med-review-author strong,.med-review-author span,.med-review-author small{display:block}
.med-review-author strong{color:var(--villa-text);font:inherit;}
.med-review-author span{margin-top:4px;color:var(--villa-text);font:inherit;}
.med-review-author small{margin-top:7px;color:var(--villa-text);font:inherit;}
.med-faq-layout{display:grid;grid-template-columns:minmax(220px,.8fr) minmax(0,1.5fr);gap:60px;align-items:start}
.med-faq-intro h2{margin:12px 0;font-size:var(--villa-body-size);color:var(--villa-text);}
.med-faq-intro p{color:var(--villa-text);font:inherit;}
.med-faq-item{border-bottom:1px solid #e2e6dc;padding:18px 0}
.med-faq-item summary{display:flex;align-items:center;justify-content:space-between;gap:15px;cursor:pointer;list-style:none;color:var(--villa-text);font:inherit;}
.med-faq-item summary::-webkit-details-marker{display:none}
.med-faq-item summary span{color:var(--villa-text);font:inherit;}
.med-faq-item[open] summary span{transform:rotate(45deg)}
.med-faq-item p{max-width:680px;color:var(--villa-text);font:inherit;}
.med-support{padding-top:20px!important}
.med-support-panel{display:flex;align-items:center;justify-content:space-between;gap:30px;padding:42px 48px;border-radius:26px;background:#e9eee3;border:1px solid #e0e7d9}
.med-support h2{margin:10px 0;color:var(--villa-text);font-size:var(--villa-body-size);}
.med-support p{max-width:620px;margin:0;color:var(--villa-text);font:inherit;}
.med-support-actions{display:flex;flex-direction:column;gap:10px;min-width:200px}
.med-support-actions a{display:block;padding:14px 18px;border-radius:11px;text-align:center;font:inherit;}
.med-support-phone{background:#fff;color:var(--villa-text);}
.med-support-whatsapp{background:#365b42;color:var(--villa-text);}
.med-footer-columns{display:grid;grid-column:1/-1;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;min-width:0}
.med-footer-columns strong{display:block;margin-bottom:12px;color:var(--villa-text);font:inherit;}
.med-footer-columns ul{margin:0;padding:0;list-style:none;display:grid;gap:9px}
.med-footer-columns a,.med-footer-columns li{color:var(--villa-text);font:inherit;}
@media(max-width:900px){.med-trust-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.destination-editorial-grid{column-gap:30px}.med-property-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.med-concept-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.med-review-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.med-faq-layout{grid-template-columns:1fr;gap:22px}}
@media(max-width:600px){body.villa-home-mediterranean .hero-card{min-height:560px;}.med-trust{padding:24px 0!important}.destination-editorial-grid{grid-template-columns:1fr;column-gap:0}.destination-editorial-item{grid-template-columns:38px minmax(0,1fr) 32px;padding:19px 2px}.destination-editorial-copy strong{font-size:22px}.med-trust-grid{grid-template-columns:1fr 1fr;gap:18px 12px}.med-trust-item{align-items:flex-start;gap:9px}.med-trust-icon{flex-basis:36px;width:36px;height:36px;font-size:var(--villa-body-size);}.med-trust-item h3{font-size:var(--villa-body-size);}.med-trust-item p{font-size:var(--villa-body-size);}.med-property-grid,.med-property-grid--compact{grid-template-columns:1fr;gap:15px}.med-property-photo,.med-property-grid--compact .med-property-photo{height:225px}.med-concept-grid{grid-template-columns:1fr 1fr;gap:10px}.med-concept-card{height:170px;border-radius:14px}.med-concept-copy{left:11px;bottom:12px}.med-concept-copy strong{font-size:var(--villa-body-size);}.med-collection-heading h3{font-size:var(--villa-body-size);}.med-review-grid{grid-template-columns:1fr}.med-review-card>p{min-height:0}.med-faq-intro h2{font-size:var(--villa-body-size);}.med-faq-item summary{font-size:var(--villa-body-size);}.med-support-panel{display:block;padding:27px 22px;border-radius:19px}.med-support h2{font-size:var(--villa-body-size);}.med-support-actions{margin-top:22px}.med-footer-columns{grid-template-columns:1fr 1fr;gap:22px}.med-footer-columns>div:last-child{grid-column:1/-1}}
CSS;

$homeCss .= <<<'CSS'

/* Keep the mobile burger button anchored inside the main menu bar, below the info bar. */
@media(max-width:760px){
 body.villa-home-mediterranean .med-topbar{position:relative;z-index:1}
 body.villa-home-mediterranean .theme-header{position:relative!important;z-index:10010!important;width:100%;height:auto!important;min-height:0!important;overflow:visible!important}
 body.villa-home-mediterranean .theme-header-inner{
  position:relative!important;display:flex!important;flex-flow:row nowrap!important;
  align-items:center!important;justify-content:space-between!important;
  width:calc(100% - 30px)!important;min-height:74px!important;height:auto!important;
  margin:0 auto!important;gap:12px!important;box-sizing:border-box!important
 }
 body.villa-home-mediterranean .theme-brand{flex:0 1 auto!important;min-width:0!important;max-width:calc(100% - 60px)!important}
 body.villa-home-mediterranean .theme-menu-toggle{
  display:inline-flex!important;position:relative!important;inset:auto!important;
  top:auto!important;right:auto!important;bottom:auto!important;left:auto!important;
  transform:none!important;order:2!important;flex:0 0 44px!important;
  width:44px!important;height:44px!important;margin:0 0 0 auto!important;
  align-self:center!important;z-index:10030!important
 }
 body.villa-home-mediterranean .theme-header .nav{
  position:fixed!important;top:0!important;right:0!important;bottom:0!important;left:auto!important;
  width:min(84vw,360px)!important;height:100dvh!important;z-index:10020!important
 }
}

CSS;

$html = str_replace('</head>', '<style id="villa-menu-icon-state">' . $menuIconCss . '</style><style>' . $homeCss . '</style></head>', $html);
$cardGalleryScript = <<<'JS'
<script>(function(){document.querySelectorAll('[data-card-gallery]').forEach(function(card){if(card.dataset.galleryReady)return;card.dataset.galleryReady='1';var data=card.querySelector('.card-gallery-data'),image=card.querySelector('.card-gallery-image'),count=card.querySelector('.card-gallery-count');if(!data||!image)return;var images=[];try{images=JSON.parse(data.textContent||'[]')}catch(e){}var index=0;card.querySelectorAll('[data-gallery-step]').forEach(function(button){button.addEventListener('click',function(event){event.preventDefault();event.stopPropagation();if(images.length<2)return;index=(index+Number(button.dataset.galleryStep)+images.length)%images.length;image.src=images[index];if(count)count.textContent=(index+1)+' / '+images.length})})})})();</script>
JS;
if (stripos($html, '</body>') !== false) {
    $html = preg_replace('~</body>~i', $cardGalleryScript . '</body>', $html, 1) ?? $html;
}

echo $html;
