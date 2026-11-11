<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Menü yönetimi</title>
<style>
*{box-sizing:border-box}body{font-family:system-ui,-apple-system,sans-serif;background:#f4f7f5;color:#26332b;margin:0}.wrap{max-width:1180px;margin:30px auto;padding:0 16px}.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.top a,.btn{display:inline-block;background:#176b50;color:#fff;text-decoration:none;border:0;border-radius:9px;padding:10px 14px;font:700 14px system-ui;cursor:pointer}.panel{background:#fff;padding:20px;border-radius:14px;margin:16px 0;box-shadow:0 5px 22px #20332c08}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.field{min-width:0}.field.full{grid-column:1/-1}label{display:block;font-size:13px;font-weight:750;margin:3px 0 6px}input,select{width:100%;min-width:0;padding:11px;border:1px solid #dfe6e0;border-radius:8px;font:inherit;background:#fff}.hint{font-size:12px;color:#718078;margin-top:5px}.check{display:flex;align-items:center;gap:8px;margin:12px 0}.check input{width:auto}.actions{display:flex;gap:7px;flex-wrap:wrap}.btn.secondary{background:#eaf1ed;color:#294338}.btn.danger{background:#a43}.btn.warning{background:#8b671c}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:760px}td,th{text-align:left;padding:12px;border-bottom:1px solid #edf0ed;vertical-align:middle}th{font-size:12px;color:#718078}td small{display:block;color:#718078;margin-top:3px}.status{font-size:12px;font-weight:800}.muted{color:#718078}.section-title{margin:0 0 5px}.empty{padding:24px;text-align:center;color:#718078}@media(max-width:650px){.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}.panel{padding:15px}.wrap{margin:18px auto}}
</style>
</head>
<body><main class="wrap">
<div class="top"><div><h1>Menü yönetimi</h1><p class="muted">Üst ve alt menü bağlantılarını buradan ekleyin, düzenleyin ve sıralayın.</p></div><a href="/admin">Yönetim paneli</a></div>
<section class="panel" id="menu-form">
<h2 class="section-title"><?= !empty($editItem['id'])?'Menü öğesini düzenle':'Yeni menü öğesi ekle' ?></h2>
<p class="hint">Sistem sayfası veya yayınlanmış bir içerik sayfası seçebilirsiniz. Özel bağlantı için URL alanını kendiniz doldurun.</p>
<form method="post" action="/admin/navigation/save">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= (int)($editItem['id']??0) ?>">
<div class="form-grid">
<div class="field"><label for="menu-label">Menüde görünecek ad</label><input id="menu-label" name="label" maxlength="120" required value="<?= e($editItem['label']??'') ?>" placeholder="Örn. Hakkımızda"></div>
<div class="field"><label for="menu-page">Bağlantı türü / sayfa seçin</label><select id="menu-page"><option value="">Özel bağlantı (URL girin)</option><optgroup label="Sistem sayfaları"><?php foreach(($systemPages??[]) as $page): ?><option value="<?= e($page['url']) ?>" <?= ($editItem['url']??'')===$page['url']?'selected':'' ?>><?= e($page['label']) ?> — <?= e($page['url']) ?></option><?php endforeach; ?></optgroup><?php if(!empty($cmsPages)): ?><optgroup label="Yayınlanmış içerik sayfaları"><?php foreach($cmsPages as $page): ?><option value="/page/<?= e($page['slug']) ?>" <?= ($editItem['url']??'')==='/page/'.$page['slug']?'selected':'' ?>><?= e($page['title']) ?> — /page/<?= e($page['slug']) ?></option><?php endforeach; ?></optgroup><?php endif; ?></select></div>
<div class="field full"><label for="menu-url">Bağlantı adresi</label><input id="menu-url" name="url" required value="<?= e($editItem['url']??'') ?>" placeholder="/konaklamalar veya https://ornek.com"><div class="hint">Dahili bağlantılar / ile başlamalıdır. Harici bağlantılarda https:// kullanın.</div></div>
<div class="field"><label for="menu-location">Menü konumu</label><select id="menu-location" name="location"><option value="header" <?= ($editItem['location']??'header')==='header'?'selected':'' ?>>Üst menü</option><option value="footer" <?= ($editItem['location']??'')==='footer'?'selected':'' ?>>Alt menü</option></select></div>
<div class="field"><label for="menu-target">Bağlantı açılışı</label><select id="menu-target" name="target"><option value="_self" <?= ($editItem['target']??'_self')==='_self'?'selected':'' ?>>Aynı sekmede</option><option value="_blank" <?= ($editItem['target']??'')==='_blank'?'selected':'' ?>>Yeni sekmede</option></select></div>
<div class="field"><label for="menu-sort">Sıralama</label><input id="menu-sort" type="number" name="sort_order" min="0" max="9999" value="<?= (int)($editItem['sort_order']??10) ?>"><div class="hint">Küçük sayı önce görünür. Aynı sayıda kayıtlar eklenme sırasına göre dizilir.</div></div>
<div class="field"><label>Yayın durumu</label><label class="check"><input type="checkbox" name="is_active" value="1" <?= !isset($editItem['is_active'])||(int)$editItem['is_active']===1?'checked':'' ?>> Menüde aktif olarak göster</label></div>
</div>
<div class="actions"><button class="btn" type="submit"><?= !empty($editItem['id'])?'Değişiklikleri kaydet':'Menüyü ekle' ?></button><?php if(!empty($editItem['id'])): ?><a class="btn secondary" href="/admin/navigation">Vazgeç</a><?php endif; ?></div>
</form>
</section>
<section class="panel"><h2 class="section-title">Mevcut menü öğeleri</h2><p class="hint">Sıralamayı değiştirmek için ilgili öğeyi düzenleyip sıra numarasını güncelleyin.</p>
<div class="table-wrap"><table><thead><tr><th>Menü adı</th><th>Bağlantı</th><th>Konum</th><th>Sıra</th><th>Durum</th><th>İşlemler</th></tr></thead><tbody>
<?php foreach(($items??[]) as $item): ?><tr>
<td><strong><?= e($item['label']) ?></strong><small>#<?= (int)$item['id'] ?></small></td><td><?= e($item['url']) ?></td><td><?= $item['location']==='footer'?'Alt menü':'Üst menü' ?></td><td><?= (int)$item['sort_order'] ?></td><td><span class="status" style="color:<?= (int)$item['is_active']?'#176b50':'#a43' ?>"><?= (int)$item['is_active']?'Aktif':'Pasif' ?></span></td>
<td><div class="actions"><a class="btn secondary" href="/admin/navigation?edit=<?= (int)$item['id'] ?>#menu-form">Düzenle</a><form method="post" action="/admin/navigation/toggle"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="btn warning" type="submit"><?= (int)$item['is_active']?'Pasifleştir':'Etkinleştir' ?></button></form><form method="post" action="/admin/navigation/delete" onsubmit="return confirm('Bu menü öğesi silinsin mi?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="btn danger" type="submit">Sil</button></form></div></td>
</tr><?php endforeach; ?>
<?php if(empty($items)): ?><tr><td colspan="6" class="empty">Henüz menü öğesi yok. Yukarıdaki formdan ilk bağlantıyı ekleyebilirsiniz.</td></tr><?php endif; ?>
</tbody></table></div></section>
</main>
<script>
(function(){var picker=document.getElementById('menu-page'),url=document.getElementById('menu-url');if(!picker||!url)return;picker.addEventListener('change',function(){if(picker.value){url.value=picker.value;url.dispatchEvent(new Event('change',{bubbles:true}));}});})();
</script>
</body></html>