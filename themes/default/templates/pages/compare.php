<?php
declare(strict_types=1);
$siteName = $content['site_name'] ?? 'VillaStay';
$imageUrl = static function (string $path): string {
    if ($path === '') return '';
    return preg_match('~^https?://~i', $path) ? $path : uploaded_media_url($path);
};
$propertyCount = count($properties);
?>
<!doctype html><html lang="<?= e($locale ?? 'tr') ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e(villa_ui('Villa Karşılaştırma ·')) ?> <?= e($siteName) ?></title>
<style>
*{box-sizing:border-box}body{margin:0;background:#fff;color:var(--villa-text);font-family:var(--villa-font-body)}a{color:inherit;text-decoration:none}.compare-wrap{width:min(1280px,calc(100% - 36px));margin:0 auto 90px}.compare-header{min-height:74px;border-bottom:1px solid #e8ece9;display:flex;align-items:center}.compare-head-inner{width:min(1280px,calc(100% - 36px));margin:auto;display:flex;align-items:center;justify-content:space-between;gap:20px}.compare-brand{font-weight:700}.compare-nav{display:flex;align-items:center;gap:20px;font-size:14px}.compare-title{padding:35px 0 24px}.compare-title h1{margin:0;font-size:clamp(25px,4vw,36px);letter-spacing:-1px}.compare-title p{margin:8px 0 0;color:#68766e}.compare-table-wrap{overflow-x:auto;border:1px solid #e4eae6;border-radius:18px;box-shadow:0 8px 26px #20332c0b}.compare-table{direction:ltr;width:100%;min-width:760px;border-collapse:separate;border-spacing:0;table-layout:fixed}.compare-table th,.compare-table td{padding:15px 16px;border-bottom:1px solid #e9eeeb;text-align:left;vertical-align:top}.compare-table thead th{background:#f8faf8}.compare-table thead th:first-child{width:190px}.compare-table tbody th{background:#f8faf8;color:#65736b;font-size:13px;font-weight:700}.compare-property-image{width:100%;height:155px;display:block;object-fit:cover;border-radius:12px;background:#e9efeb}.compare-property-title{display:block;margin-top:12px;font-weight:750;font-size:15px;line-height:1.4}.compare-property-remove{margin-top:10px;padding:7px 10px;border:1px solid #dfe6e0;border-radius:9px;background:#fff;color:#59685f;font:inherit;font-size:12px;cursor:pointer}.compare-price{font-weight:800;font-size:17px;white-space:nowrap}.compare-rating{font-weight:700}.compare-amenities{margin:0;padding-left:18px}.compare-empty{margin:18px 0 70px;padding:52px 20px;border:1px dashed #d7e1da;border-radius:18px;text-align:center}.compare-empty h2{margin:0 0 8px}.compare-empty p{color:#68766e;margin:0 0 20px}.compare-empty a{display:inline-flex;padding:12px 18px;border-radius:11px;background:#203d32;color:#fff;font-weight:700}.compare-add-more{margin:18px 0 0;color:#68766e;font-size:13px}
@media(max-width:700px){.compare-nav{gap:10px;font-size:12px}.compare-wrap{width:calc(100% - 24px)}.compare-title{padding:25px 0 18px}.compare-table th,.compare-table td{padding:12px}.compare-table thead th:first-child{width:125px}.compare-property-image{height:112px}}
</style></head><body>
<header class="compare-header"><div class="compare-head-inner"><a class="compare-brand" href="<?= e(app_url('/')) ?>"><?= e($siteName) ?></a><nav class="compare-nav" aria-label="Ana gezinme"><a href="<?= e(app_url('/konaklamalar')) ?>"><?= e(villa_ui('Konaklamalar')) ?></a><a href="<?= e(app_url('/')) ?>"><?= e(villa_ui('Ana sayfa')) ?></a></nav></div></header>
<main class="compare-wrap">
 <div class="compare-title"><h1><?= e(villa_ui('Villa karşılaştırma')) ?></h1><p><?= e(villa_ui('Seçtiğiniz villaların fiyat, kapasite ve özelliklerini yan yana inceleyin.')) ?></p></div>
 <?php if($propertyCount < 2): ?>
  <section class="compare-empty"><h2><?= e(villa_ui('Karşılaştırmak için en az 2 villa seçin')) ?></h2><p><?= e(villa_ui('Konaklama listesinden veya villa detay sayfasından karşılaştırma listenize villa ekleyebilirsiniz. En fazla 4 villa karşılaştırabilirsiniz.')) ?></p><a href="<?= e(app_url('/konaklamalar')) ?>"><?= e(villa_ui('Villaları keşfet')) ?></a></section>
 <?php else: ?>
  <div class="compare-table-wrap"><table class="compare-table"><thead><tr><th><?= e(villa_ui('Karşılaştırma')) ?></th>
   <?php foreach($properties as $p): $img=$imageUrl((string)($p['cover_image']??'')); ?>
    <th><a href="<?= e(app_url('/villa/'.rawurlencode((string)$p['slug']))) ?>"><?php if($img!==''): ?><img class="compare-property-image" src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy"><?php else: ?><div class="compare-property-image"></div><?php endif; ?><span class="compare-property-title"><?= e($p['title']) ?></span></a><button class="compare-property-remove" type="button" data-compare-remove-id="<?= (int)$p['id'] ?>"><?= e(villa_ui('Listeden çıkar')) ?></button></th>
   <?php endforeach; ?>
  </tr></thead><tbody>
   <tr><th><?= e(villa_ui('Gecelik fiyat')) ?></th><?php foreach($properties as $p): ?><td><span class="compare-price"><?= e(number_format((float)$p['base_nightly_price'],0,',','.')) ?> <?= e((string)$p['currency']) ?></span> <?= e(villa_ui('/ gece')) ?></td><?php endforeach; ?></tr>
   <tr><th><?= e(villa_ui('Konum')) ?></th><?php foreach($properties as $p): ?><td><?= e(trim((string)($p['neighborhood']??'').', '.(string)($p['district']??'').', '.(string)($p['city']??''),', ')) ?></td><?php endforeach; ?></tr>
   <tr><th><?= e(villa_ui('Misafir kapasitesi')) ?></th><?php foreach($properties as $p): ?><td><?= (int)$p['max_guests'] ?> <?= e(villa_ui('kişi')) ?></td><?php endforeach; ?></tr>
   <tr><th><?= e(villa_ui('Yatak odası')) ?></th><?php foreach($properties as $p): ?><td><?= (int)$p['bedrooms'] ?></td><?php endforeach; ?></tr>
   <tr><th><?= e(villa_ui('Yatak sayısı')) ?></th><?php foreach($properties as $p): ?><td><?= (int)$p['beds'] ?></td><?php endforeach; ?></tr>
   <tr><th><?= e(villa_ui('Banyo')) ?></th><?php foreach($properties as $p): ?><td><?= e((string)$p['bathrooms']) ?></td><?php endforeach; ?></tr>
   <tr><th><?= e(villa_ui('Misafir puanı')) ?></th><?php foreach($properties as $p): ?><td class="compare-rating"><?php if((int)$p['rating_count']>0): ?>★ <?= number_format((float)$p['rating_average'],1,',','.') ?>/10 <small>(<?= (int)$p['rating_count'] ?> <?= e(villa_ui('değerlendirme)')) ?></small><?php else: ?><?= e(villa_ui('Henüz değerlendirme yok')) ?><?php endif; ?></td><?php endforeach; ?></tr>
   <tr><th><?= e(villa_ui('Açıklama')) ?></th><?php foreach($properties as $p): ?><td><?= e((string)($p['summary'] ?? $p['description'] ?? '')) ?></td><?php endforeach; ?></tr>
   <tr><th><?= e(villa_ui('Öne çıkan özellikler')) ?></th><?php foreach($properties as $p): ?><td><?php $items=$compareAmenities[(int)$p['id']]??[]; if($items): ?><ul class="compare-amenities"><?php foreach(array_slice($items,0,12) as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul><?php else: ?><?= e(villa_ui('Belirtilmemiş')) ?><?php endif; ?></td><?php endforeach; ?></tr>
  </tbody></table></div>
  <p class="compare-add-more"><?= e(villa_ui('En fazla 4 villayı aynı anda karşılaştırabilirsiniz. Yeni villa eklemek için konaklama listesine dönün.')) ?></p>
 <?php endif; ?>
</main>
<?php \App\Core\ThemeManager::renderPartial('partials/compare-bar.php', get_defined_vars()); ?>
<?php \App\Core\ThemeManager::renderPartial('partials/login-modal.php', get_defined_vars()); ?>
</body></html>
