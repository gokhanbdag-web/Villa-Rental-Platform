<?php declare(strict_types=1);
$fieldLabels=['title'=>'Başlık','summary'=>'Kısa açıklama','description'=>'Açıklama','name'=>'Kategori adı','excerpt'=>'Özet','content'=>'İçerik','meta_title'=>'SEO başlığı','meta_description'=>'SEO açıklaması','featured_image_alt'=>'Görsel alt metni','country'=>'Ülke','city'=>'Şehir','district'=>'İlçe','neighborhood'=>'Mahalle'];
?>
<!doctype html><html lang="<?= e($locale) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>İçerik çevirileri</title>
<style>
body{font-family:system-ui;background:#f5f7f5;color:#26372f;margin:0}.wrap{max-width:1100px;margin:30px auto;padding:0 18px}.panel{background:#fff;border:1px solid #e6ece7;border-radius:14px;padding:20px;margin:14px 0}.filters{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.field{display:grid;gap:7px;font-size:13px;font-weight:700}.field input,.field select,.field textarea{width:100%;box-sizing:border-box;padding:11px;border:1px solid #dce5df;border-radius:9px;background:#fff;font:inherit}.field textarea{min-height:130px;line-height:1.6}.field textarea.long{min-height:320px}.source{margin-top:8px;border:1px solid #edf0ed;border-radius:8px;padding:10px;background:#fafbfa}.source summary{cursor:pointer;color:#68766e;font-size:12px;font-weight:600}.source pre{white-space:pre-wrap;overflow-wrap:anywhere;max-height:230px;overflow:auto;font:12px/1.6 system-ui;color:#59665e}.btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:9px;padding:11px 16px;background:#176b50;color:#fff;font-weight:750;text-decoration:none;cursor:pointer}.muted{color:#718078;font-size:13px;line-height:1.6}.top{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.top h1{margin:0 0 6px}.actions{display:flex;gap:8px;flex-wrap:wrap}.actions a{color:#176b50;text-decoration:none;font-size:13px}@media(max-width:700px){.filters{grid-template-columns:1fr}.wrap{padding:0 12px;margin:18px auto}.panel{padding:14px}}
</style></head><body><main class="wrap">
<div class="top"><div><h1>İçerik çevirileri</h1><p class="muted">Villa ilanlarını, konaklama kategorilerini ve blog içeriklerini dil bazında düzenleyin. Boş bırakılan alanlar varsayılan dildeki metni kullanır.</p></div><div class="actions"><a href="<?= e(app_url('/admin/translations')) ?>">Arayüz ve e-posta metinleri</a><a href="<?= e(app_url('/admin')) ?>">Yönetim paneli</a></div></div>
<section class="panel"><form method="get" action="<?= e(app_url('/admin/content-translations')) ?>" class="filters">
<label class="field">İçerik türü<select name="type" onchange="this.form.submit()"><?php foreach($entityMaps as $key=>$item): ?><option value="<?= e($key) ?>" <?= $type===$key?'selected':'' ?>><?= e($item['title']) ?></option><?php endforeach; ?></select></label>
<label class="field">Dil<select name="lang" onchange="this.form.submit()"><?php foreach($languages as $language): if((int)$language['is_default']===1)continue; ?><option value="<?= e($language['code']) ?>" <?= $locale===$language['code']?'selected':'' ?>><?= e($language['name']) ?> (<?= e($language['code']) ?>)</option><?php endforeach; ?></select></label>
<label class="field">Çevrilecek kayıt<select name="id" onchange="this.form.submit()"><option value="0">Kayıt seçin</option><?php foreach($entities as $item): ?><option value="<?= (int)$item['id'] ?>" <?= $entityId===(int)$item['id']?'selected':'' ?>>#<?= (int)$item['id'] ?> · <?= e($item['label']) ?></option><?php endforeach; ?></select></label>
</form></section>
<?php if($entity&&$locale!==$defaultLocale): ?>
<form method="post" action="<?= e(app_url('/admin/content-translations')) ?>">
<?= csrf_field() ?><input type="hidden" name="type" value="<?= e($type) ?>"><input type="hidden" name="entity_id" value="<?= (int)$entityId ?>"><input type="hidden" name="lang" value="<?= e($locale) ?>">
<section class="panel"><h2><?= e($map['title']) ?>: <?= e($entity['label']) ?></h2><p class="muted">Kaynak içerik varsayılan dildir. Her alanın altındaki kaynak metni açarak karşılaştırabilirsiniz. Çeviri alanını boş bırakırsanız kaynak metin gösterilir.</p>
<?php foreach($map['fields'] as $field): $source=(string)($entity[$field]??'');$translated=(string)($values[$field]??'');$long=in_array($field,['description','content'],true); ?>
<label class="field" style="margin:18px 0"><?= e($fieldLabels[$field]??$field) ?>
<textarea name="fields[<?= e($field) ?>]" <?= $long?'class="long"':'' ?> placeholder="Bu alan için <?= e($locale) ?> çevirisi"><?= e($translated) ?></textarea>
<details class="source"><summary>Varsayılan dildeki kaynak metni görüntüle</summary><pre><?= e($source) ?></pre></details>
</label>
<?php endforeach; ?>
<button class="btn" type="submit">Çevirileri kaydet</button>
</section></form>
<?php else: ?><section class="panel"><p class="muted"><?= $locale===$defaultLocale?'Çeviri düzenlemek için önce Site dilleri bölümünden varsayılan dil dışında en az bir dil ekleyin.':'Çevirisini düzenlemek istediğiniz kaydı yukarıdaki listeden seçin.' ?></p></section><?php endif; ?>
</main></body></html>
