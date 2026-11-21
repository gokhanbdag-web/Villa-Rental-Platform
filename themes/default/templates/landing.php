<?php
declare(strict_types=1);

// Theme-specific presentation shell. The shared page supplies all business data and actions.
ob_start();
$pageTemplate = \App\Core\ThemeManager::resolveFile('templates/pages/landing.php');
if ($pageTemplate === null) throw new RuntimeException('Tema sayfa bileşeni bulunamadı: landing');
require $pageTemplate;
$html = (string) ob_get_clean();

ob_start();
require __DIR__ . '/partials/header.php';
$header = (string) ob_get_clean();
$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="(?:menuOverlay|siteMenuOverlay)"[^>]*></div>)?~s', $header, $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-home-mediterranean"', $html, 1) ?? $html;
$homeCss = <<<'CSS'
body.villa-home-mediterranean{background:#f8f8f1;color:var(--villa-text);}body.villa-home-mediterranean .site-header{background:#fbfcf7;border-color:#e4e9dc}body.villa-home-mediterranean .hero{padding:24px 0 54px;background:#eef2e7}body.villa-home-mediterranean .hero-card{min-height:530px;background-position:center 55%;box-shadow:0 22px 55px #344c3518}body.villa-home-mediterranean .hero-copy{max-width:850px}body.villa-home-mediterranean .hero h1{font:inherit;letter-spacing:-2px}body.villa-home-mediterranean .section{padding:66px 0}body.villa-home-mediterranean .section-head h2{font:inherit;color:var(--villa-text);}body.villa-home-mediterranean .destination-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}body.villa-home-mediterranean .destination{height:250px;border-radius:22px}body.villa-home-mediterranean .cards{grid-template-columns:repeat(3,minmax(0,1fr));gap:25px}body.villa-home-mediterranean .stay-photo{height:250px;border-radius:22px}body.villa-home-mediterranean .stay h3{font:inherit;}body.villa-home-mediterranean .gallery-strip{grid-template-columns:1.5fr 1fr 1fr;grid-template-rows:210px 210px;gap:15px}body.villa-home-mediterranean .gallery-strip img{border-radius:22px}body.villa-home-mediterranean .host-banner{border-radius:30px}@media(max-width:700px){body.villa-home-mediterranean .hero-card{min-height:560px;}body.villa-home-mediterranean .cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}body.villa-home-mediterranean .stay-photo{height:155px}body.villa-home-mediterranean .destination{height:165px}body.villa-home-mediterranean .section{padding:38px 0}}
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
      <div class="section-head"><div><span class="pill villa-eyebrow"><?= e($content['home_latest_eyebrow'] ?? villa_ui('YENİ EKLENENLER')) ?></span><h2><?= e($content['home_latest_title'] ?? villa_ui('Son Eklenen Villalar')) ?></h2><p><?= e($content['home_latest_subtitle'] ?? '') ?></p></div><a class="more" href="<?= e(app_url('/konaklamalar?sort=newest')) ?>"><?= e($content['home_latest_link'] ?? villa_ui('Tüm villaları gör ↗')) ?></a></div>
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
    ?>
    <?php
    if ($medSectionActive('concepts') && !empty($categories)): ?>
    <section class="section med-concepts" style="order:<?= $medSectionOrder('concepts',5) ?>"><div class="wrap">
      <div class="section-head"><div><span class="pill villa-eyebrow"><?= e($content['home_concepts_eyebrow'] ?? villa_ui('TATİLİNİZİ KEŞFEDİN')) ?></span><h2><?= e($content['home_concepts_title'] ?? villa_ui('Size Uygun Tatil Konseptini Seçin')) ?></h2><p><?= e($content['home_concepts_subtitle'] ?? '') ?></p></div></div>
      <div class="med-concept-grid"><?php foreach (array_slice($categories, 0, 4) as $i=>$category): $img=$medImage($category); if($img==='')$img=$stylePhotos[$i%count($stylePhotos)]??''; ?>
        <a class="med-concept-card" href="<?= e(app_url('/konaklamalar?category='.(int)$category['id'])) ?>"><?php if($img): ?><img src="<?= e($img) ?>" alt="<?= e($category['name']) ?>" loading="lazy"><?php endif; ?><span class="med-concept-overlay"></span><span class="med-concept-copy" data-villa-overlay-text><strong><?= e($category['name']) ?></strong><small><?= (int)($category['property_count'] ?? 0) ?> <?= e($content['home_concepts_count_label'] ?? villa_ui('villa seçeneği')) ?></small></span></a>
      <?php endforeach; ?></div>
    </div></section>
    <?php endif;
    if ($homeDesign['banner']['visible'] ?? true):
    $familyBannerAsset = $brandSettings ?? \App\Core\ThemeManager::brandSettings();
    $familyBannerText = \App\Core\ThemeManager::familyBannerTextSettings();
    foreach (['eyebrow','title','description'] as $field) {
        if (isset($homeDesign['banner']['banner_'.$field])) $familyBannerText[$field]=$homeDesign['banner']['banner_'.$field];
    }
    if (isset($homeDesign['banner']['banner_image'])) $familyBannerAsset['family_banner_url']=$homeDesign['banner']['banner_image'];
    $bannerFontFamilies = array_unique(array_map(static fn(array $row): string => (string)$row['family'], [$familyBannerText['eyebrow_typography'],$familyBannerText['title_typography'],$familyBannerText['description_typography']]));
    $bannerFontQuery = implode('&', array_map(static fn(string $font): string => 'family=' . str_replace('%20','+',rawurlencode($font)) . ':wght@400;500;600;700;800', $bannerFontFamilies));
    ?>
    <section class="section med-family-banner" style="order:<?= (int)($homeDesign['banner']['order']??4) ?>" aria-label="Ana sayfa banner">
      <?php if($bannerFontQuery!==''): ?><style>@import url('https://fonts.googleapis.com/css2?<?= $bannerFontQuery ?>&display=swap');</style><?php endif; ?>
      <?php if(!empty($familyBannerAsset['family_banner_url'])): ?><img class="med-family-banner-image" src="<?= e($familyBannerAsset['family_banner_url']) ?>" alt="" loading="lazy"><?php endif; ?>
      <?php if(trim((string)$familyBannerText['eyebrow'])!=='' || trim((string)$familyBannerText['title'])!=='' || trim((string)$familyBannerText['description'])!==''): ?>
      <div class="med-family-banner-inner" data-villa-overlay-text style="--family-banner-text-color:<?= e($familyBannerText['color']) ?>">
        <?php if(trim((string)$familyBannerText['eyebrow'])!==''): ?><span class="med-family-banner-eyebrow" style="color:<?= e($familyBannerText['color']) ?>!important;font-family:<?= e($familyBannerText['eyebrow_typography']['family']) ?>;font-size:<?= (int)$familyBannerText['eyebrow_typography']['size'] ?>px;line-height:<?= e((string)$familyBannerText['eyebrow_typography']['line_height']) ?>"><?= e($familyBannerText['eyebrow']) ?></span><?php endif; ?>
        <?php if(trim((string)$familyBannerText['title'])!==''): ?><span class="med-family-banner-title" role="heading" aria-level="2" style="color:<?= e($familyBannerText['color']) ?>!important;font-family:<?= e($familyBannerText['title_typography']['family']) ?>;font-size:<?= (int)$familyBannerText['title_typography']['size'] ?>px;line-height:<?= e((string)$familyBannerText['title_typography']['line_height']) ?>"><?= e($familyBannerText['title']) ?></span><?php endif; ?>
        <?php if(trim((string)$familyBannerText['description'])!==''): ?><span class="med-family-banner-description" style="color:<?= e($familyBannerText['color']) ?>!important;font-family:<?= e($familyBannerText['description_typography']['family']) ?>;font-size:<?= (int)$familyBannerText['description_typography']['size'] ?>px;line-height:<?= e((string)$familyBannerText['description_typography']['line_height']) ?>"><?= nl2br(e($familyBannerText['description'])) ?></span><?php endif; ?>
      </div>
      <?php endif; ?>
    </section>
    <?php
    endif;
    if ($medSectionActive('reviews') && !empty($homeReviews)): ?>
    <section class="section med-reviews" style="order:<?= $medSectionOrder('reviews',8) ?>"><div class="wrap">
      <div class="section-head"><div><span class="pill villa-eyebrow"><?= e($content['home_reviews_eyebrow'] ?? villa_ui('MİSAFİR DENEYİMLERİ')) ?></span><h2><?= e($content['home_reviews_title'] ?? villa_ui('Misafirlerimizin Deneyimleri')) ?></h2><p><?= e($content['home_reviews_subtitle'] ?? '') ?></p></div></div>
      <div class="med-review-grid"><?php foreach ($homeReviews as $review): ?><article class="med-review-card"><div class="med-review-stars" aria-label="<?= (int)$review['rating'] ?> üzerinden 10 puan">★★★★★</div><p><?= e((string)$review['body']) ?></p><div class="med-review-author"><strong><?= e($review['username']) ?></strong><span><?= e($review['property_title']) ?></span><small><?= e($content['home_review_verified_label'] ?? villa_ui('Doğrulanmış konaklama yorumu')) ?> · <?= number_format((float)$review['rating'],1,',','.') ?>/10</small></div></article><?php endforeach; ?></div>
    </div></section>
    <?php endif;
    if ($medSectionActive('support')): $phone=trim((string)($content['home_support_phone']??''));$wa=trim((string)($content['home_support_whatsapp']??$phone));$phoneDigits=preg_replace('/\D+/','',$phone);$waDigits=preg_replace('/\D+/','',$wa); ?>
    <section class="section med-support" style="order:<?= $medSectionOrder('support',10) ?>">
      <div class="wrap">
        <div class="med-home-stats-heading">
          <span><?= e($content['home_stats_eyebrow'] ?? villa_ui('RAKAMLARLA BİZ')) ?></span>
          <h2><?= e($content['home_stats_title'] ?? villa_ui('Tatiliniz için güven veren deneyim')) ?></h2>
          <p><?= e($content['home_stats_subtitle'] ?? villa_ui('Villa seçeneklerimizi, destinasyonlarımızı ve misafir deneyimimizi keşfedin.')) ?></p>
        </div>
        <?php $homeStatLabels=['villas'=>villa_ui('Yayındaki villa'),'destinations'=>'Destinasyon','customers'=>villa_ui('Müşteri'),'reviews'=>villa_ui('Onaylı yorum')]; $homeStatIcons=['villas'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z"/></svg>','destinations'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>','customers'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>','reviews'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z"/></svg>']; ?>
        <div class="med-home-stats" aria-label="Villa platformu istatistikleri">
          <?php foreach($homeStatLabels as $statKey=>$statLabel): $statCount=max(0,(int)($homepageStats[$statKey]??0)); ?>
            <div class="med-home-stat">
              <span class="med-home-stat-icon"><?= $homeStatIcons[$statKey] ?></span>
              <strong data-home-counter="<?= $statCount ?>"><?= number_format($statCount,0,',','.') ?></strong>
              <span class="med-home-stat-label"><?= e($statLabel) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
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
        // Keep the moved section last in the flex layout, regardless of its original admin sort order.
        $destinationMarkup = preg_replace('~style="order:[^"]*"~', 'style="order:'.(int)($homeDesign['destinations']['order']??2147483647).'"', $destinationMarkup, 1) ?? $destinationMarkup;
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
        return '<div class="host-copy" data-villa-overlay-text><h2>'.e($hostTitle).'</h2><p>'.e($hostSubtitle).'</p><a href="'.e(app_url('/vendor/register')).'">'.e($hostCta!==''?$hostCta:'Ev sahibi olarak katılın').' ↗</a></div>';
    },$html,1)??$html;
}
// Footer content is rendered only by the shared six-slot footer partial.
$regionTitle=trim((string)($content['home_regions_title']??''));
$regionSubtitle=trim((string)($content['home_regions_subtitle']??''));
$regionEyebrow=trim((string)($content['home_regions_eyebrow']??'KEŞFETMEYE BAŞLA'));
if(empty($homeDesign['destinations']) && $medSectionActive('destinations') && ($regionTitle!==''||$regionSubtitle!==''||$regionEyebrow!=='')){
    $html=preg_replace_callback('~(<span class="pill villa-eyebrow">).*?(</span><h2>).*?(</h2><p>).*?(</p>)~s',static function(array $m)use($regionTitle,$regionSubtitle,$regionEyebrow):string{
        return $m[1].e($regionEyebrow).$m[2].e($regionTitle!==''?$regionTitle:'Bölgeleri keşfedin').$m[3].e($regionSubtitle).$m[4];
    },$html,1)??$html;
}
$html=preg_replace('~<section class="section" style="[^"]*"><div class="wrap"><div class="section-head"><div><span class="pill villa-eyebrow">TATİL TARZINI SEÇ</span>.*?</section>~s','',$html,1)??$html;
$html=preg_replace('~<section class="section" style="[^"]*"><div class="wrap"><div class="section-head"><div><span class="pill villa-eyebrow">TATİL İLHAMIN</span>.*?</section>~s','',$html,1)??$html;

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
.med-support{padding:48px 0!important;background:linear-gradient(180deg,color-mix(in srgb,var(--villa-primary,#315a43) 5%,#fff),#fff 88%)}
.med-home-stats-heading{max-width:720px;margin:0 auto 26px;text-align:center}
.med-home-stats-heading>span{display:inline-flex;align-items:center;gap:8px;color:var(--villa-primary,#315a43);font-size:11px;font-weight:850;letter-spacing:.14em}
.med-home-stats-heading>span:before,.med-home-stats-heading>span:after{content:"";width:24px;height:1px;background:var(--villa-primary,#315a43);opacity:.45}
.med-home-stats-heading h2{margin:9px 0 7px;color:var(--villa-text);font-size:clamp(23px,3vw,32px);line-height:1.2;letter-spacing:-.035em}
.med-home-stats-heading p{margin:0;color:var(--villa-text);opacity:.72;font-size:14px;line-height:1.65}
.med-home-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px;margin:0}
.med-home-stat{position:relative;isolation:isolate;overflow:hidden;min-width:0;min-height:190px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px 16px;border:1px solid color-mix(in srgb,var(--villa-primary,#315a43) 14%,#e7ece5);border-radius:20px;background:linear-gradient(145deg,#fff 15%,color-mix(in srgb,var(--villa-primary,#315a43) 4%,#fff));box-shadow:0 12px 32px rgba(35,57,39,.075);text-align:center;transition:transform .25s ease,box-shadow .25s ease,border-color .25s ease}
.med-home-stat:after{content:"";position:absolute;z-index:-1;width:150px;height:150px;right:-72px;top:-78px;border-radius:50%;background:color-mix(in srgb,var(--villa-primary,#315a43) 8%,transparent)}
.med-home-stat:hover{transform:translateY(-4px);border-color:color-mix(in srgb,var(--villa-primary,#315a43) 35%,#e7ece5);box-shadow:0 18px 38px rgba(35,57,39,.12)}
.med-home-stat-icon{width:48px;height:48px;display:grid;place-items:center;margin-bottom:13px;border:1px solid color-mix(in srgb,var(--villa-primary,#315a43) 20%,transparent);border-radius:50%;background:color-mix(in srgb,var(--villa-primary,#315a43) 9%,#fff);color:var(--villa-primary,#315a43)}
.med-home-stat-icon svg{width:23px;height:23px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
.med-home-stat strong{display:block;color:var(--villa-primary,#315a43);font-size:clamp(30px,3.3vw,42px);font-weight:850;line-height:1.1;font-variant-numeric:tabular-nums;letter-spacing:-.04em}
.med-home-stat-label{display:block;margin-top:9px;color:var(--villa-text);font-size:13px;font-weight:700;letter-spacing:.015em}
.med-support-date-form{display:grid;grid-template-columns:minmax(0,1fr);gap:14px}
.med-calendar-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px}
.med-calendar-nav{width:42px;height:42px;flex:0 0 42px;border:1px solid var(--villa-primary,#315a43);border-radius:50%;background:#fff;color:var(--villa-primary,#315a43);font-size:27px;line-height:1;cursor:pointer}
.med-calendar-nav:disabled{opacity:.35;cursor:default}
@media(max-width:900px){.med-home-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.med-home-stat{min-height:174px}}
@media(max-width:600px){.med-support{padding:34px 0!important}.med-home-stats-heading{margin-bottom:20px}.med-home-stats-heading p{font-size:13px}.med-home-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.med-home-stat{min-height:156px;padding:18px 10px;border-radius:16px}.med-home-stat-icon{width:42px;height:42px;margin-bottom:10px}.med-home-stat-icon svg{width:20px;height:20px}.med-home-stat strong{font-size:clamp(25px,7vw,34px)}.med-home-stat-label{font-size:12px;margin-top:7px}}
@media(prefers-reduced-motion:reduce){.med-home-stat{transition:none}.med-home-stat:hover{transform:none}}
.med-calendar-selection{display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap;color:var(--villa-primary,#315a43)}
.med-calendar-selection span{font-size:10px;font-weight:800;letter-spacing:.08em}
.med-calendar-selection strong{font-size:13px}
.med-calendar-selection i{height:22px;border-left:1px solid var(--villa-primary,#315a43);opacity:.35}
.med-calendar-months{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:28px}
.med-calendar-month{min-width:0}
.med-calendar-month-title{margin:0 0 15px;text-align:center;color:var(--villa-primary,#315a43);font-size:17px;font-weight:800}
.med-calendar-weekdays,.med-calendar-days{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));text-align:center}
.med-calendar-weekdays{margin-bottom:7px}
.med-calendar-weekdays span{padding:7px 0;color:var(--villa-primary,#315a43);font-size:11px;font-weight:800}
.med-calendar-days{row-gap:5px}
.med-calendar-day,.med-calendar-empty{width:100%;min-width:0;aspect-ratio:1/1;display:grid;place-items:center}
.med-calendar-day{padding:0;border:0;border-radius:50%;background:transparent;color:var(--villa-primary,#315a43);font:inherit;font-size:13px;cursor:pointer}
.med-calendar-day:not(:disabled):hover{background:color-mix(in srgb,var(--villa-primary,#315a43) 13%,white)}
.med-calendar-day.is-today{outline:1px solid var(--villa-primary,#315a43);outline-offset:-3px}
.med-calendar-day.is-between{border-radius:0;background:color-mix(in srgb,var(--villa-primary,#315a43) 13%,white)}
.med-calendar-day.is-start,.med-calendar-day.is-end{border-radius:50%;background:var(--villa-primary,#315a43);color:#fff;font-weight:800}
.med-calendar-day.is-past{color:#c6c9c4;cursor:not-allowed;text-decoration:line-through}
.med-support-date-hint{grid-column:1/-1;margin:0;color:var(--villa-primary,#315a43);font-size:13px}
.med-support-actions{display:flex;flex-wrap:wrap;gap:10px;min-width:0}
.med-support-actions a{display:block;padding:12px 18px;border-radius:10px;text-align:center;font:inherit}
.med-support-phone{background:#fff;color:var(--villa-primary,#315a43);border:1px solid var(--villa-primary,#315a43)}
.med-support-whatsapp{background:var(--villa-primary,#315a43);color:#fff}
@media(max-width:600px){.med-support-panel{padding:25px 18px;gap:19px}.med-calendar-months{grid-template-columns:minmax(0,1fr)}.med-calendar-month-next{display:none}.med-calendar-day{font-size:14px}.med-calendar-toolbar{gap:7px}.med-calendar-selection{gap:7px}.med-calendar-selection strong{font-size:11px}.med-support-date-hint{grid-column:auto}.med-support-actions{flex-direction:column}}
.med-footer-columns{display:grid;grid-column:1/-1;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;min-width:0}
.med-footer-columns strong{display:block;margin-bottom:12px;color:var(--villa-text);font:inherit;}
.med-footer-columns ul{margin:0;padding:0;list-style:none;display:grid;gap:9px}
.med-footer-columns a,.med-footer-columns li{color:var(--villa-text);font:inherit;}
@media(max-width:760px){body.villa-home-mediterranean .destination-section .destination-editorial-grid{grid-template-columns:minmax(0,1fr)!important;column-gap:0!important;row-gap:0!important}body.villa-home-mediterranean .destination-section .destination-editorial-item{grid-column:1/-1!important;min-width:0!important}}
@media(max-width:900px){.med-trust-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.destination-editorial-grid{column-gap:30px}.med-property-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.med-concept-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.med-review-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.med-faq-layout{grid-template-columns:1fr;gap:22px}}
@media(max-width:600px){body.villa-home-mediterranean .hero-card{min-height:560px;}.med-trust{padding:24px 0!important}.destination-editorial-grid{grid-template-columns:1fr;column-gap:0}.destination-editorial-item{grid-template-columns:38px minmax(0,1fr) 32px;padding:19px 2px}.destination-editorial-copy strong{font-size:22px}.med-trust-grid{grid-template-columns:1fr 1fr;gap:18px 12px}.med-trust-item{align-items:flex-start;gap:9px}.med-trust-icon{flex-basis:36px;width:36px;height:36px;font-size:var(--villa-body-size);}.med-trust-item h3{font-size:var(--villa-body-size);}.med-trust-item p{font-size:var(--villa-body-size);}.med-property-grid,.med-property-grid--compact{grid-template-columns:1fr;gap:15px}.med-property-photo,.med-property-grid--compact .med-property-photo{height:225px}.med-concept-grid{grid-template-columns:1fr 1fr;gap:10px}.med-concept-card{height:170px;border-radius:14px}.med-concept-copy{left:11px;bottom:12px}.med-concept-copy strong{font-size:var(--villa-body-size);}.med-collection-heading h3{font-size:var(--villa-body-size);}.med-review-grid{grid-template-columns:1fr}.med-review-card>p{min-height:0}.med-faq-intro h2{font-size:var(--villa-body-size);}.med-faq-item summary{font-size:var(--villa-body-size);}.med-home-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.med-home-stat{padding:14px 8px}.med-home-stat strong{font-size:27px}.med-support-actions{margin-top:22px}.med-footer-columns{grid-template-columns:1fr 1fr;gap:22px}.med-footer-columns>div:last-child{grid-column:1/-1}}
CSS;


/* Mobile search readability: isolated to the homepage, desktop and search logic unchanged. */
$homeCss .= <<<'CSS'
@media(max-width:600px){
  body.villa-home-mediterranean .hero-filter-panel{width:100%;max-width:none;padding:18px 16px;border-radius:18px}
  body.villa-home-mediterranean .hero-filter-panel .search-enhanced{grid-template-columns:repeat(2,minmax(0,1fr));gap:0}
  body.villa-home-mediterranean .hero-filter-panel .search-field{min-height:76px;padding:12px 10px;border-bottom:1px solid #e8eeea}
  body.villa-home-mediterranean .hero-filter-panel .destination-field,
  body.villa-home-mediterranean .hero-filter-panel .guest-field{grid-column:1/-1;border-right:0}
  body.villa-home-mediterranean .hero-filter-panel .date-field{border-right:1px solid #e8eeea}
  body.villa-home-mediterranean .hero-filter-panel .date-field:nth-of-type(2){border-right:0}
  body.villa-home-mediterranean .hero-filter-panel .search-label{font-size:14px;line-height:1.35;font-weight:700}
  body.villa-home-mediterranean .hero-filter-panel .search-icon{font-size:18px}
  body.villa-home-mediterranean .hero-filter-panel .destination-field input,
  body.villa-home-mediterranean .hero-filter-panel .date-display,
  body.villa-home-mediterranean .hero-filter-panel .guest-trigger{font-size:16px;line-height:1.4;min-height:26px}
  body.villa-home-mediterranean .hero-filter-panel .search-submit{grid-column:1/-1;min-height:56px;margin-top:12px;font-size:16px;font-weight:700}
  body.villa-home-mediterranean .hero-filter-panel .search-submit span{font-size:20px}
  body.villa-home-mediterranean .hero-filter-panel .guest-popover{font-size:15px}
  body.villa-home-mediterranean .hero-filter-panel .guest-row strong,
  body.villa-home-mediterranean .hero-filter-panel .guest-row small{font-size:14px}
  body.villa-home-mediterranean .hero-filter-panel .counter button{width:36px;height:36px;font-size:18px}
  body.villa-home-mediterranean .mobile-hero-search{padding:18px 0 24px}
  body.villa-home-mediterranean .mobile-hero-search-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
  body.villa-home-mediterranean .mobile-hero-search-form label{display:flex;flex-direction:column;justify-content:center;gap:7px;min-width:0;min-height:76px;padding:10px 12px;border:1px solid #e2e9e2;border-radius:12px;background:#fff}
  body.villa-home-mediterranean .mobile-hero-search-form .mobile-search-destination{grid-column:1/-1}
  body.villa-home-mediterranean .mobile-hero-search-form label>span{font-size:14px;line-height:1.3;font-weight:700;color:var(--villa-text)}
  body.villa-home-mediterranean .mobile-hero-search-form input,
  body.villa-home-mediterranean .mobile-hero-search-form select{width:100%;min-width:0;min-height:28px;padding:2px 0;border:0;background:transparent;color:var(--villa-text);font:inherit;font-size:16px;line-height:1.4;outline-offset:3px}
  body.villa-home-mediterranean .mobile-hero-search-form button{grid-column:1/-1;min-height:56px;border:0;border-radius:12px;background:#315a43;color:#fff;font-size:16px;font-weight:700}
}
CSS;

/* Lightweight viewport reveal: content remains visible unless the observer is available. */
$homeCss .= <<<'CSS'
body.villa-home-mediterranean.villa-reveal-ready .villa-reveal-item{
  opacity:0;
  transform:translate3d(0,22px,0);
  transition:opacity .68s cubic-bezier(.2,.65,.25,1),transform .68s cubic-bezier(.2,.65,.25,1);
  transition-delay:var(--villa-reveal-delay,0ms);
  will-change:opacity,transform;
}
body.villa-home-mediterranean.villa-reveal-ready .villa-reveal-item.is-revealed{
  opacity:1;
  transform:translate3d(0,0,0);
}
@media(max-width:600px){
  body.villa-home-mediterranean.villa-reveal-ready .villa-reveal-item{
    transform:translate3d(0,14px,0);
    transition-duration:.48s;
    will-change:auto;
  }
}
@media(prefers-reduced-motion:reduce){
  body.villa-home-mediterranean.villa-reveal-ready .villa-reveal-item,
  body.villa-home-mediterranean.villa-reveal-ready .villa-reveal-item.is-revealed{
    opacity:1!important;
    transform:none!important;
    transition:none!important;
    will-change:auto!important;
  }
}
CSS;

$html = str_replace('</head>', '<style>' . $homeCss . '</style></head>', $html);
$cardGalleryScript = <<<'JS'
<script>(function(){document.querySelectorAll('[data-card-gallery]').forEach(function(card){if(card.dataset.galleryReady)return;card.dataset.galleryReady='1';var data=card.querySelector('.card-gallery-data'),image=card.querySelector('.card-gallery-image'),count=card.querySelector('.card-gallery-count');if(!data||!image)return;var images=[];try{images=JSON.parse(data.textContent||'[]')}catch(e){}var index=0;card.querySelectorAll('[data-gallery-step]').forEach(function(button){button.addEventListener('click',function(event){event.preventDefault();event.stopPropagation();if(images.length<2)return;index=(index+Number(button.dataset.galleryStep)+images.length)%images.length;image.src=images[index];if(count)count.textContent=(index+1)+' / '+images.length})})})})();</script>
JS;
$homeCounterScript = <<<'JS'
<script>(function(){var counters=document.querySelectorAll('[data-home-counter]');if(!counters.length)return;var format=new Intl.NumberFormat('tr-TR');function run(el){if(el.dataset.counterDone)return;el.dataset.counterDone='1';var target=Math.max(0,Number(el.dataset.homeCounter)||0);if(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches){el.textContent=format.format(target);return;}var start=null,duration=950;function frame(now){if(start===null)start=now;var progress=Math.min(1,(now-start)/duration);var eased=1-Math.pow(1-progress,3);el.textContent=format.format(Math.round(target*eased));if(progress<1)window.requestAnimationFrame(frame);}window.requestAnimationFrame(frame);}if('IntersectionObserver' in window){var observer=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){run(entry.target);observer.unobserve(entry.target);}});},{threshold:.25});counters.forEach(function(el){observer.observe(el);});}else{counters.forEach(run);}})();</script>
JS;
$scrollRevealScript = <<<'JS'
<script>(function(){
  var body=document.body;
  if(!body||!('IntersectionObserver' in window))return;
  if(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
  var selectors=[
    '.home-sections > .section',
    '.med-trust',
    '.destination-editorial-item',
    '.destination-grid > .destination',
    '.cards > .stay',
    '.styles > .style-card',
    '.med-property-card',
    '.med-concept-card',
    '.med-review-card',
    '.med-home-stat',
    '.gallery-strip img',
    '.host-banner'
  ];
  var items=[];
  selectors.forEach(function(selector){
    document.querySelectorAll(selector).forEach(function(el){
      if(items.indexOf(el)===-1)items.push(el);
    });
  });
  if(!items.length)return;
  items.forEach(function(el){
    el.classList.add('villa-reveal-item');
    var parent=el.parentElement;
    if(parent){
      var siblings=Array.prototype.filter.call(parent.children,function(child){return child.classList.contains('villa-reveal-item');});
      var index=siblings.indexOf(el);
      if(index>0)el.style.setProperty('--villa-reveal-delay',Math.min(index,4)*75+'ms');
    }
  });
  body.classList.add('villa-reveal-ready');
  var observer=new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if(!entry.isIntersecting)return;
      entry.target.classList.add('is-revealed');
      observer.unobserve(entry.target);
    });
  },{root:null,rootMargin:'0px 0px -7% 0px',threshold:0.08});
  items.forEach(function(el){observer.observe(el);});
})();</script>
JS;
if (stripos($html, '</body>') !== false) {
    $html = preg_replace('~</body>~i', $cardGalleryScript . $homeCounterScript . $scrollRevealScript . '</body>', $html, 1) ?? $html;
}

ob_start();
require __DIR__ . '/partials/footer.php';
$footerMarkup = (string) ob_get_clean();
$html = preg_replace('~</body>~i', $footerMarkup . '</body>', $html, 1) ?? $html;

if (isset($preview)) {
    $selectSection=<<<'HTML'
<script>
document.addEventListener('click',function(event) {
    const section=event.target.closest('main.home-sections section');
    if (!section) return;
    const selectors={
        hero:'.hero,.mobile-premium-search',trust:'.med-trust',featured:'.home-accommodations',
        latest:'.med-latest',banner:'.med-family-banner',concepts:'.med-concepts',
        hosts:'.wrap:has(.host-banner)',reviews:'.med-reviews',support:'.med-support',
        destinations:'.destination-section,:has(> .wrap > .destination-editorial-grid)'
    };
    for (const [key,selector] of Object.entries(selectors)) {
        if (!section.matches(selector)) continue;
        event.preventDefault();
        event.stopImmediatePropagation();
        window.parent.postMessage({homepageSection:key},window.location.origin);
        break;
    }
},true);
</script>
HTML;
    $html=str_replace('</body>',$selectSection.'</body>',$html);
}
echo $html;
