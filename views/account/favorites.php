<?php declare(strict_types=1); ?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Favorilerim</title>
<style>
.favorites-page{max-width:1120px;margin:26px auto 60px;padding:0 18px}
.favorites-heading{display:flex;justify-content:space-between;align-items:end;gap:16px;margin-bottom:22px}
.favorites-heading h1{margin:0}.favorites-heading p{margin:7px 0 0;opacity:.72}
.favorites-count{white-space:nowrap;font-size:13px;font-weight:750;opacity:.75}
.favorites-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
.favorite-card{min-width:0;overflow:hidden;border:1px solid rgba(100,120,100,.18);border-radius:18px;background:var(--account-card,#fff);box-shadow:0 10px 28px rgba(20,40,25,.06)}
.favorite-card-image{display:block;width:100%;height:210px;object-fit:cover;background:#e8eee8}
.favorite-card-body{padding:16px}.favorite-card-title{display:block;font-size:16px;font-weight:800;text-decoration:none;color:inherit}
.favorite-card-meta{margin:8px 0;color:inherit;opacity:.68;font-size:13px}
.favorite-card-price{font-weight:850;font-size:15px}
.favorite-card-actions{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px}
.favorite-card-actions a{font-size:13px;font-weight:750;text-decoration:none}
.favorite-remove{border:1px solid rgba(100,120,100,.25);border-radius:999px;padding:9px 12px;background:transparent;color:inherit;font:inherit;font-size:12px;font-weight:750;cursor:pointer}
.favorites-empty{padding:42px 22px;text-align:center;border:1px dashed rgba(100,120,100,.35);border-radius:18px}
.favorites-empty strong{display:block;font-size:18px;margin-bottom:8px}.favorites-empty p{opacity:.7;margin:0 0 18px}
.favorites-empty a{display:inline-flex;padding:11px 17px;border-radius:999px;background:#456d50;color:white;text-decoration:none;font-weight:750}
@media(max-width:850px){.favorites-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.favorites-page{padding:0 14px;margin-top:20px}.favorites-heading{align-items:start;flex-direction:column}.favorites-grid{grid-template-columns:1fr;gap:14px}.favorite-card{display:grid;grid-template-columns:112px minmax(0,1fr)}.favorite-card-image{height:100%;min-height:150px}.favorite-card-body{padding:13px}.favorite-card-title{font-size:14px}.favorite-card-actions{align-items:flex-start;flex-direction:column}.favorite-remove{padding:8px 10px}}
</style><?php require APP_ROOT . '/views/site/partials/account-theme.php'; ?></head><body>
<main class="favorites-page">
<div class="favorites-heading"><div><h1>Favorilerim</h1><p>Beğendiğiniz konaklamaları burada saklayabilirsiniz.</p></div><span class="favorites-count"><?= count($properties) ?> kayıtlı konaklama</span></div>
<?php if($properties): ?><div class="favorites-grid">
<?php foreach($properties as $p): $image=(string)($p['cover_image']??''); $imageUrl=$image!==''?(preg_match('~^https?://~i',$image)?$image:app_url('/'.ltrim($image,'/'))):''; ?>
<article class="favorite-card">
<a href="<?= e(app_url('/villa/'.rawurlencode((string)$p['slug']))) ?>" aria-label="<?= e($p['title']) ?>"><?php if($imageUrl!==''): ?><img class="favorite-card-image" src="<?= e($imageUrl) ?>" alt="<?= e($p['title']) ?>" loading="lazy"><?php else: ?><div class="favorite-card-image"></div><?php endif; ?></a>
<div class="favorite-card-body"><a class="favorite-card-title" href="<?= e(app_url('/villa/'.rawurlencode((string)$p['slug']))) ?>"><?= e($p['title']) ?></a>
<p class="favorite-card-meta"><?= e(trim((string)($p['district']??'').', '.(string)($p['city']??''),', ')) ?> · <?= (int)$p['max_guests'] ?> misafir</p>
<div class="favorite-card-price"><?= e(number_format((float)$p['base_nightly_price'],0,',','.')) ?> <?= e($p['currency']) ?> / gece</div>
<div class="favorite-card-actions"><a href="<?= e(app_url('/villa/'.rawurlencode((string)$p['slug']))) ?>">Konaklamayı incele</a>
<form method="post" action="<?= e(app_url('/favorites/toggle')) ?>" class="favorite-remove-form"><?= csrf_field() ?><input type="hidden" name="property_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="return_to" value="<?= e(app_url('/favorites')) ?>"><button class="favorite-remove" type="submit">♥ Favorilerimden çıkar</button></form></div></div></article>
<?php endforeach; ?></div><?php else: ?><div class="favorites-empty"><strong>Henüz favori konaklamanız yok</strong><p>Beğendiğiniz villalardaki kalp simgesine dokunarak listenize ekleyebilirsiniz.</p><a href="<?= e(app_url('/konaklamalar')) ?>">Konaklamaları keşfet</a></div><?php endif; ?>
</main></body></html>