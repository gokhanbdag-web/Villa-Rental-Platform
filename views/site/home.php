<?php
declare(strict_types=1);
/** @var array $properties */
/** @var array $categories */
/** @var array $navigation */
/** @var array $content */
/** @var array $filters */
$siteName = $content['site_name'] ?? 'VillaStay';
$cover = static function (array $property): string {
    return !empty($property['cover_image']) ? '/' . ltrim((string)$property['cover_image'], '/') : '';
};
?>
<!doctype html>
<html lang="<?= e($locale ?? 'tr') ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($content['home_title'] ?? $siteName) ?> · <?= e($siteName) ?></title>
<meta name="description" content="<?= e($content['home_subtitle'] ?? '') ?>">
<style>
:root{--ink:#222b27;--muted:#68736d;--line:#e8ece9;--green:#176b50;--cream:#f6f7f4}
*{box-sizing:border-box}body{margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--ink);background:#fff}a{color:inherit;text-decoration:none}.wrap{width:min(1180px,calc(100% - 40px));margin:auto}
header{height:78px;border-bottom:1px solid var(--line);display:flex;align-items:center}.head{display:flex;align-items:center;justify-content:space-between;gap:24px}.brand{font-size:25px;font-weight:850;letter-spacing:-1.2px;color:var(--green)}nav{display:flex;gap:26px;align-items:center;font-size:14px;font-weight:650}.nav-cta{border:1px solid var(--line);padding:11px 17px;border-radius:30px}
.hero{padding:76px 0 60px;background:linear-gradient(120deg,#f0f5ef,#f9f7f1)}.eyebrow{color:var(--green);font-size:12px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase}.hero h1{font-size:clamp(34px,5vw,58px);line-height:1.06;letter-spacing:-2.4px;max-width:760px;margin:16px 0}.hero p{color:var(--muted);font-size:17px;max-width:650px;line-height:1.7}
.search{margin-top:32px;background:#fff;border:1px solid #e4e9e4;border-radius:18px;padding:10px;display:flex;gap:8px;max-width:850px;box-shadow:0 12px 35px #21372b12}.search input,.search select{min-width:0;flex:1;border:0;padding:14px 15px;font:inherit;outline:none;background:transparent}.search button{border:0;border-radius:13px;padding:0 28px;background:var(--green);color:#fff;font-weight:750;font-size:15px;cursor:pointer}
main{padding:58px 0 80px}.section-top{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:26px}.section-top h2{font-size:30px;letter-spacing:-1px;margin:0 0 8px}.section-top p{margin:0;color:var(--muted)}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:26px}.property{min-width:0}.photo{height:245px;border-radius:16px;background:linear-gradient(135deg,#dce8df,#e8e4d8);overflow:hidden;position:relative}.photo img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s}.property:hover .photo img{transform:scale(1.04)}.photo-empty{height:100%;display:grid;place-items:center;color:#738278;font-size:13px}.property h3{font-size:17px;margin:13px 0 5px}.meta{color:var(--muted);font-size:14px}.price{margin-top:9px;font-size:15px}.price strong{font-size:18px}.empty{padding:42px;text-align:center;background:var(--cream);border-radius:16px;color:var(--muted)}footer{background:#1f3029;color:#e8f0eb;padding:35px 0}footer p{color:#b9c8bf;font-size:14px}
@media(max-width:800px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}.hero{padding:55px 0}.search{flex-wrap:wrap}.search input,.search select{flex-basis:40%}.search button{height:48px;flex:1}}
@media(max-width:560px){.wrap{width:min(100% - 28px,1180px)}header{height:68px}nav{gap:12px;font-size:12px}.brand{font-size:21px}.hero h1{letter-spacing:-1.5px}.search{display:grid;grid-template-columns:1fr}.search input,.search select{width:100%;padding:12px}.search button{height:48px}.grid{grid-template-columns:1fr;gap:23px}.photo{height:235px}.section-top h2{font-size:25px}}
</style>
</head>
<body>
<header><div class="wrap head"><a class="brand" href="/"><?= e($siteName) ?></a><nav>
<?php foreach ($navigation as $item): ?><a href="<?= e($item['url']) ?>" target="<?= e($item['target']) ?>" <?= $item['target']==='_blank'?'rel="noopener noreferrer"':'' ?>><?= e($item['label']) ?></a><?php endforeach; ?><?php if (($mode ?? 'single_vendor') === 'multi_vendor'): ?><a href="/vendor/register">Ev sahibi olun</a><?php endif; ?>
</nav></div></header>
<section class="hero"><div class="wrap">
<div class="eyebrow"><?= e($content['home_eyebrow'] ?? '') ?></div>
<h1><?= e($content['home_title'] ?? '') ?></h1><p><?= e($content['home_subtitle'] ?? '') ?></p>
<form class="search" action="/" method="get">
<input type="search" name="city" placeholder="<?= e($content['home_search_placeholder'] ?? 'Şehir veya bölge') ?>" value="<?= e($filters['city'] ?? '') ?>">
<select name="category"><option value="0">Tüm kategoriler</option><?php foreach($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= (int)($filters['category']??0)===(int)$category['id']?'selected':'' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select>
<select name="guests"><option value="">Misafir sayısı</option><?php for($i=1;$i<=16;$i++): ?><option value="<?= $i ?>" <?= (int)($filters['guests'] ?? 0)===$i?'selected':'' ?>><?= $i ?> misafir</option><?php endfor; ?></select>
<button type="submit">Villaları keşfet</button>
</form>
</div></section>
<main class="wrap">
<div class="section-top"><div><h2><?= e($content['featured_title'] ?? '') ?></h2><p><?= e($content['featured_subtitle'] ?? '') ?></p></div></div>
<?php if (!$properties): ?><div class="empty"><?= e($content['empty_properties'] ?? '') ?></div><?php else: ?>
<div class="grid"><?php foreach($properties as $property): ?>
<a class="property" href="/villa/<?= e($property['slug']) ?>"><div class="photo"><?php if($cover($property)): ?><img src="<?= e($cover($property)) ?>" alt="<?= e($property['title']) ?>" loading="lazy"><?php else: ?><div class="photo-empty">Görsel yakında eklenecek</div><?php endif; ?></div>
<h3><?= e($property['title']) ?></h3><div class="meta"><?= e(trim(($property['district'] ? $property['district'].', ' : '').($property['city'] ?? '').($property['country'] ? ', '.$property['country'] : ''))) ?> · <?= (int)$property['max_guests'] ?> misafir</div>
<div class="price"><strong><?= e(number_format((float)$property['base_nightly_price'],0,',','.')) ?> <?= e($property['currency']) ?></strong> / gece</div></a>
<?php endforeach; ?></div><?php endif; ?>
</main>
<footer><div class="wrap"><strong><?= e($siteName) ?></strong><p><?= e($content['footer_text'] ?? '') ?></p><nav><?php foreach(($footerNavigation ?? []) as $item): ?><a href="<?= e($item['url']) ?>" target="<?= e($item['target']) ?>" <?= $item['target']==='_blank'?'rel="noopener noreferrer"':'' ?>><?= e($item['label']) ?></a><?php endforeach; ?></nav></div></footer>
</body></html>