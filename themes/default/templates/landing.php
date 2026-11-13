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
body.villa-home-mediterranean{background:#f8f8f1;color:#304235}body.villa-home-mediterranean .site-header{background:#fbfcf7;border-color:#e4e9dc}body.villa-home-mediterranean .hero{padding:24px 0 54px;background:#eef2e7}body.villa-home-mediterranean .hero-card{min-height:530px;background-position:center 55%;box-shadow:0 22px 55px #344c3518}body.villa-home-mediterranean .hero-copy{max-width:850px}body.villa-home-mediterranean .hero h1{font:500 clamp(42px,6vw,72px)/1.02 Georgia,serif;letter-spacing:-2px}body.villa-home-mediterranean .section{padding:66px 0}body.villa-home-mediterranean .section-head h2{font:500 clamp(29px,3.4vw,40px) Georgia,serif;color:#294936}body.villa-home-mediterranean .destination-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}body.villa-home-mediterranean .destination{height:250px;border-radius:22px}body.villa-home-mediterranean .cards{grid-template-columns:repeat(3,minmax(0,1fr));gap:25px}body.villa-home-mediterranean .stay-photo{height:250px;border-radius:22px}body.villa-home-mediterranean .stay h3{font:500 20px Georgia,serif}body.villa-home-mediterranean .home-accommodations{background:#f8f8f1!important}body.villa-home-mediterranean .gallery-strip{grid-template-columns:1.5fr 1fr 1fr;grid-template-rows:210px 210px;gap:15px}body.villa-home-mediterranean .gallery-strip img{border-radius:22px}body.villa-home-mediterranean .host-banner{border-radius:30px}@media(max-width:700px){body.villa-home-mediterranean .hero-card{min-height:560px;}body.villa-home-mediterranean .cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}body.villa-home-mediterranean .stay-photo{height:155px}body.villa-home-mediterranean .destination{height:165px}body.villa-home-mediterranean .section{padding:38px 0}}
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
      <div class="section-head"><div><span class="pill"><?= e($content['home_latest_eyebrow'] ?? 'YENİ EKLENENLER') ?></span><h2><?= e($content['home_latest_title'] ?? 'Son Eklenen Villalar') ?></h2><p><?= e($content['home_latest_subtitle'] ?? '') ?></p></div><a class="more" href="<?= e(app_url('/konaklamalar?sort=newest')) ?>"><?= e($content['home_latest_link'] ?? 'Tüm villaları gör ↗') ?></a></div>
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
      <div class="section-head"><div><span class="pill"><?= e($content['home_concepts_eyebrow'] ?? 'TATİLİNİZİ KEŞFEDİN') ?></span><h2><?= e($content['home_concepts_title'] ?? 'Size Uygun Tatil Konseptini Seçin') ?></h2><p><?= e($content['home_concepts_subtitle'] ?? '') ?></p></div></div>
      <div class="med-concept-grid"><?php foreach ($categories as $i=>$category): $img=$medImage($category); if($img==='')$img=$stylePhotos[$i%count($stylePhotos)]??''; ?>
        <a class="med-concept-card" href="<?= e(app_url('/konaklamalar?category='.(int)$category['id'])) ?>"><?php if($img): ?><img src="<?= e($img) ?>" alt="<?= e($category['name']) ?>" loading="lazy"><?php endif; ?><span class="med-concept-overlay"></span><span class="med-concept-copy"><strong><?= e($category['name']) ?></strong><small><?= (int)($category['property_count'] ?? 0) ?> <?= e($content['home_concepts_count_label'] ?? 'villa seçeneği') ?></small></span></a>
      <?php endforeach; ?></div>
    </div></section>
    <?php endif;
    if ($medSectionActive('collections') && !empty($homeCategoryCollections)): ?>
    <section class="section med-collections" style="order:<?= $medSectionOrder('collections',7) ?>"><div class="wrap">
      <div class="section-head"><div><span class="pill"><?= e($content['home_collections_eyebrow'] ?? 'ÖZENLE HAZIRLANDI') ?></span><h2><?= e($content['home_collections_title'] ?? 'Size Özel Villa Koleksiyonları') ?></h2><p><?= e($content['home_collections_subtitle'] ?? '') ?></p></div></div>
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
      <div class="section-head"><div><span class="pill"><?= e($content['home_reviews_eyebrow'] ?? 'MİSAFİR DENEYİMLERİ') ?></span><h2><?= e($content['home_reviews_title'] ?? 'Misafirlerimizin Deneyimleri') ?></h2><p><?= e($content['home_reviews_subtitle'] ?? '') ?></p></div></div>
      <div class="med-review-grid"><?php foreach ($homeReviews as $review): ?><article class="med-review-card"><div class="med-review-stars" aria-label="<?= (int)$review['rating'] ?> üzerinden 10 puan">★★★★★</div><p><?= e((string)$review['body']) ?></p><div class="med-review-author"><strong><?= e($review['username']) ?></strong><span><?= e($review['property_title']) ?></span><small><?= e($content['home_review_verified_label'] ?? 'Doğrulanmış konaklama yorumu') ?> · <?= number_format((float)$review['rating'],1,',','.') ?>/10</small></div></article><?php endforeach; ?></div>
    </div></section>
    <?php endif;
    $faqItems=[]; for($i=1;$i<=5;$i++){ $q=trim((string)($content['home_faq_'.$i.'_question']??''));$a=trim((string)($content['home_faq_'.$i.'_answer']??''));if($q!==''&&$a!=='')$faqItems[]=[$q,$a];}
    if ($medSectionActive('faq') && $faqItems): ?>
    <section class="section med-faq" style="order:<?= $medSectionOrder('faq',9) ?>"><div class="wrap med-faq-layout"><div class="med-faq-intro"><span class="pill"><?= e($content['home_faq_eyebrow'] ?? 'YARDIM MERKEZİ') ?></span><h2><?= e($content['home_faq_title'] ?? 'Sıkça Sorulan Sorular') ?></h2><p><?= e($content['home_faq_subtitle'] ?? '') ?></p></div><div class="med-faq-list"><?php foreach($faqItems as $i=>$faq): ?><details class="med-faq-item" <?= $i===0?'open':'' ?>><summary><?= e($faq[0]) ?><span>＋</span></summary><p><?= e($faq[1]) ?></p></details><?php endforeach; ?></div></div></section>
    <?php endif;
    if ($medSectionActive('support')): $phone=trim((string)($content['home_support_phone']??''));$wa=trim((string)($content['home_support_whatsapp']??$phone));$phoneDigits=preg_replace('/\D+/','',$phone);$waDigits=preg_replace('/\D+/','',$wa); ?>
    <section class="section med-support" style="order:<?= $medSectionOrder('support',10) ?>"><div class="wrap"><div class="med-support-panel"><div><span class="pill"><?= e($content['home_support_eyebrow'] ?? 'CANLI DESTEK') ?></span><h2><?= e($content['home_support_title'] ?? 'Tatilinizi birlikte planlayalım') ?></h2><p><?= e($content['home_support_subtitle'] ?? '') ?></p></div><div class="med-support-actions"><?php if($phoneDigits): ?><a class="med-support-phone" href="tel:<?= e($phoneDigits) ?>">☎ <?= e($phone) ?></a><?php endif; ?><?php if($waDigits): ?><a class="med-support-whatsapp" href="https://wa.me/<?= e($waDigits) ?>" target="_blank" rel="noopener noreferrer"><?= e($content['home_support_whatsapp_label'] ?? 'WhatsApp ile yazın ↗') ?></a><?php endif; ?></div></div></div></section>
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
    $html=preg_replace_callback('~(<span class="pill">).*?(</span><h2>).*?(</h2><p>).*?(</p>)~s',static function(array $m)use($regionTitle,$regionSubtitle,$regionEyebrow):string{
        return $m[1].e($regionEyebrow).$m[2].e($regionTitle!==''?$regionTitle:'Bölgeleri keşfedin').$m[3].e($regionSubtitle).$m[4];
    },$html,1)??$html;
}
$html=preg_replace('~<section class="section" style="[^"]*"><div class="wrap"><div class="section-head"><div><span class="pill">TATİL TARZINI SEÇ</span>.*?</section>~s','',$html,1)??$html;
$html=preg_replace('~<section class="section" style="[^"]*"><div class="wrap"><div class="section-head"><div><span class="pill">TATİL İLHAMIN</span>.*?</section>~s','',$html,1)??$html;

$menuIconCss = '.theme-menu-toggle .menu-open-mark{display:inline!important}.theme-menu-toggle .menu-close-mark,.theme-menu-toggle .menu-close-mark[hidden]{display:none!important}.theme-menu-toggle[aria-expanded="true"] .menu-open-mark{display:none!important}.theme-menu-toggle[aria-expanded="true"] .menu-close-mark{display:inline!important}.theme-menu-toggle[aria-expanded="true"] .menu-close-mark[hidden]{display:inline!important}';

$homeCss .= <<<'CSS'
/* Mediterranean editorial homepage */
body.villa-home-mediterranean{background:#f8f8f1;color:#304235;font-family:Georgia,"Times New Roman",serif}
.med-topbar{background:#304b37;color:#edf2e8;font:12px Arial,sans-serif}.med-topbar>.wrap{display:flex;justify-content:flex-end;align-items:center;gap:22px;min-height:34px}.med-topbar a{color:#edf2e8}.med-topbar a:hover{color:#d6c29a}
body.villa-home-mediterranean .site-header{background:#fbfcf7;border-color:#e4e9dc}
body.villa-home-mediterranean .brand,body.villa-home-mediterranean .nav a:hover{color:#315a43}
body.villa-home-mediterranean .hero{background:#eef2e7;padding:22px 0 44px}
body.villa-home-mediterranean .hero-card{min-height:560px;box-shadow:0 24px 60px #263e2d20}
body.villa-home-mediterranean .hero h1,body.villa-home-mediterranean .section-head h2,body.villa-home-mediterranean .med-faq-intro h2,body.villa-home-mediterranean .med-support h2{font-family:Georgia,"Times New Roman",serif;font-weight:500;letter-spacing:-1.2px}
body.villa-home-mediterranean .search-enhanced{border:1px solid #e4e8db;box-shadow:0 18px 48px #263e2d1c}
body.villa-home-mediterranean .search-enhanced .search-submit{background:#315a43;color:#fff}
body.villa-home-mediterranean .section{padding:62px 0}
body.villa-home-mediterranean .section-head h2{color:#294936;font-size:clamp(30px,3.5vw,43px)}
body.villa-home-mediterranean .section-head p{font-family:Arial,sans-serif;color:#758176;line-height:1.7}
body.villa-home-mediterranean .pill{background:#e8eee2;color:#536a4e;letter-spacing:1px}
body.villa-home-mediterranean .more{color:#536d4e}
body.villa-home-mediterranean .section:has(.styles),body.villa-home-mediterranean .section:has(.gallery-strip){display:none!important}
.med-trust{padding:24px 0!important;background:#fff}
.med-trust-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:22px}
.med-trust-item{display:flex;align-items:center;gap:13px;min-width:0}
.med-trust-icon{display:grid;place-items:center;flex:0 0 44px;width:44px;height:44px;border-radius:50%;background:#edf1e7;color:#426448;font:22px Arial,sans-serif}
.med-trust-item h3{margin:0 0 5px;color:#314d38;font:700 14px Arial,sans-serif}
.med-trust-item p{margin:0;color:#7a857a;font:12px/1.55 Arial,sans-serif}
.med-property-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}
.med-property-card{display:block;min-width:0;overflow:hidden;border:1px solid #e7e9df;border-radius:19px;background:#fff;box-shadow:0 10px 30px #273c2c0c;transition:transform .25s,box-shadow .25s}
.med-property-card:hover{transform:translateY(-4px);box-shadow:0 18px 38px #273c2c18}
.med-property-photo{height:215px;overflow:hidden;background:#e8ece3}
.med-property-photo img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .4s}
.med-property-card:hover .med-property-photo img{transform:scale(1.04)}
.med-property-body{padding:15px 16px 18px;font-family:Arial,sans-serif}
.med-property-place{display:block;color:#8a927f;font-size:11px;letter-spacing:.3px}
.med-property-body h3{margin:7px 0;color:#304936;font:600 18px/1.35 Georgia,serif}
.med-property-meta{color:#7b857b;font-size:12px}
.med-property-price{margin-top:12px;color:#6e796d;font-size:12px}
.med-property-price strong{color:#365c40;font-size:16px}
.med-concepts{background:#f0f2e9}
.med-concept-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
.med-concept-card{position:relative;display:block;height:245px;overflow:hidden;border-radius:19px;background:#dfe6d9}
.med-concept-card img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.med-concept-card:hover img{transform:scale(1.05)}
.med-concept-overlay{position:absolute;inset:35% 0 0;background:linear-gradient(transparent,#142b1dcc)}
.med-concept-copy{position:absolute;left:18px;right:15px;bottom:17px;color:#fff}
.med-concept-copy strong{display:block;font:500 21px Georgia,serif}
.med-concept-copy small{display:block;margin-top:5px;color:#e0e8df;font:12px Arial,sans-serif}
.med-collections{background:#fbfbf6}
.med-collection-row{margin-top:30px}
.med-collection-heading{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:16px}
.med-collection-heading h3{margin:0;color:#304b37;font:500 27px Georgia,serif}
.med-collection-heading p{margin:6px 0 0;color:#818a7d;font:12px Arial,sans-serif}
.med-collection-heading>a{color:#536d4e;font:700 13px Arial,sans-serif;white-space:nowrap}
.med-property-grid--compact{grid-template-columns:repeat(3,minmax(0,1fr))}
.med-property-grid--compact .med-property-photo{height:190px}
.med-reviews{background:#eef1e7}
.med-review-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.med-review-card{padding:24px;border:1px solid #e0e6d9;border-radius:18px;background:#fff}
.med-review-stars{color:#b28a4f;letter-spacing:3px;font:16px Arial,sans-serif}
.med-review-card>p{min-height:90px;color:#59685b;font:15px/1.75 Georgia,serif}
.med-review-author strong,.med-review-author span,.med-review-author small{display:block}
.med-review-author strong{color:#314b37;font:700 13px Arial,sans-serif}
.med-review-author span{margin-top:4px;color:#7e897d;font:12px Arial,sans-serif}
.med-review-author small{margin-top:7px;color:#a0a696;font:10px Arial,sans-serif}
.med-faq-layout{display:grid;grid-template-columns:minmax(220px,.8fr) minmax(0,1.5fr);gap:60px;align-items:start}
.med-faq-intro h2{margin:12px 0;font-size:42px;color:#304b37}
.med-faq-intro p{color:#7c877b;font:15px/1.8 Arial,sans-serif}
.med-faq-item{border-bottom:1px solid #e2e6dc;padding:18px 0}
.med-faq-item summary{display:flex;align-items:center;justify-content:space-between;gap:15px;cursor:pointer;list-style:none;color:#354e39;font:600 16px Georgia,serif}
.med-faq-item summary::-webkit-details-marker{display:none}
.med-faq-item summary span{color:#8b9b7c;font:20px Arial,sans-serif}
.med-faq-item[open] summary span{transform:rotate(45deg)}
.med-faq-item p{max-width:680px;color:#758174;font:14px/1.8 Arial,sans-serif}
.med-support{padding-top:20px!important}
.med-support-panel{display:flex;align-items:center;justify-content:space-between;gap:30px;padding:42px 48px;border-radius:26px;background:#e9eee3;border:1px solid #e0e7d9}
.med-support h2{margin:10px 0;color:#304b37;font-size:38px}
.med-support p{max-width:620px;margin:0;color:#738071;font:14px/1.7 Arial,sans-serif}
.med-support-actions{display:flex;flex-direction:column;gap:10px;min-width:200px}
.med-support-actions a{display:block;padding:14px 18px;border-radius:11px;text-align:center;font:700 13px Arial,sans-serif}
.med-support-phone{background:#fff;color:#385b40}
.med-support-whatsapp{background:#365b42;color:#fff}
.med-footer-columns{display:grid;grid-column:1/-1;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;min-width:0}
.med-footer-columns strong{display:block;margin-bottom:12px;color:#f0f3e9;font:600 15px Georgia,serif}
.med-footer-columns ul{margin:0;padding:0;list-style:none;display:grid;gap:9px}
.med-footer-columns a,.med-footer-columns li{color:#c6d0c2;font:12px/1.5 Arial,sans-serif}
@media(max-width:900px){.med-trust-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.med-property-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.med-concept-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.med-review-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.med-faq-layout{grid-template-columns:1fr;gap:22px}}
@media(max-width:600px){body.villa-home-mediterranean .hero-card{min-height:560px;}.med-trust-grid{grid-template-columns:1fr 1fr;gap:18px 12px}.med-trust-item{align-items:flex-start;gap:9px}.med-trust-icon{flex-basis:36px;width:36px;height:36px;font-size:18px}.med-trust-item h3{font-size:12px}.med-trust-item p{font-size:11px}.med-property-grid,.med-property-grid--compact{grid-template-columns:1fr;gap:15px}.med-property-photo,.med-property-grid--compact .med-property-photo{height:225px}.med-concept-grid{grid-template-columns:1fr 1fr;gap:10px}.med-concept-card{height:170px;border-radius:14px}.med-concept-copy{left:11px;bottom:12px}.med-concept-copy strong{font-size:16px}.med-collection-heading h3{font-size:22px}.med-review-grid{grid-template-columns:1fr}.med-review-card>p{min-height:0}.med-faq-intro h2{font-size:33px}.med-faq-item summary{font-size:14px}.med-support-panel{display:block;padding:27px 22px;border-radius:19px}.med-support h2{font-size:29px}.med-support-actions{margin-top:22px}.med-footer-columns{grid-template-columns:1fr 1fr;gap:22px}.med-footer-columns>div:last-child{grid-column:1/-1}}
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
