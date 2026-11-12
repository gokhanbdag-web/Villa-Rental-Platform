<?php declare(strict_types=1);
$settings = is_array($typographySettings ?? null) ? $typographySettings : [];
$fontOptions = [
    'Inter'=>'Inter','Roboto'=>'Roboto','Open Sans'=>'Open Sans','Lato'=>'Lato',
    'Montserrat'=>'Montserrat','Poppins'=>'Poppins','Nunito Sans'=>'Nunito Sans',
    'Playfair Display'=>'Playfair Display','Merriweather'=>'Merriweather',
    'DM Sans'=>'DM Sans','Manrope'=>'Manrope','Source Sans 3'=>'Source Sans 3',
    'Raleway'=>'Raleway','PT Sans'=>'PT Sans','Libre Baskerville'=>'Libre Baskerville',
];
$groups = [
    'headings'=>['Başlıklar','H1, H2, H3 ve diğer başlık etiketleri'],
    'span'=>['Span metinleri','Span etiketiyle gösterilen kısa metinler'],
    'paragraph'=>['Paragraflar','Tüm paragraf metinleri'],
    'paragraph_links'=>['Paragraf içi bağlantılar','Yalnızca paragrafların içindeki metin bağlantıları; butonlar hariç'],
];
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tema Tipografi Ayarları</title>
<style>
.typography-settings{max-width:1120px;margin:0 auto;color:#26352e}.typography-settings h1{font-size:25px;margin:0 0 7px}.typography-settings>.intro{color:#738078;font-size:13px;margin:0 0 22px}.type-notice{padding:12px 14px;border-radius:9px;background:#eff7f1;color:#176b50;margin:0 0 16px;font-size:13px}.type-notice.error{background:#fff1f0;color:#a32920;border:1px solid #f0cbc7}.type-panel{background:#fff;border:1px solid #e5ebe7;border-radius:14px;padding:19px;margin:0 0 15px}.type-panel h2{font-size:16px;margin:0 0 5px}.type-panel>p{font-size:12px;color:#78847c;margin:0 0 16px}.type-fields{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.type-field label{display:block;font-size:11px;font-weight:750;color:#68766d;margin:0 0 6px}.type-field input,.type-field select{width:100%;height:40px;padding:8px 10px;border:1px solid #dce5dd;border-radius:8px;background:#fff;color:#26352e;font-size:13px;min-width:0}.type-field input[type=color]{padding:4px;height:40px}.type-preview{margin-top:15px;padding:14px 16px;border:1px dashed #dce5dd;border-radius:10px;background:#fbfcfa}.type-preview strong{display:block;font-size:12px;color:#738078;margin-bottom:5px}.type-preview span{font-size:14px;color:#344039}.type-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.type-save{border:0;border-radius:9px;background:#176b50;color:#fff;padding:12px 18px;font-weight:800;cursor:pointer}.type-save:hover{background:#12583f}.type-help{font-size:12px;color:#78847c}.topbar-height{max-width:260px}@media(max-width:850px){.type-fields{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.type-fields{grid-template-columns:1fr}.type-panel{padding:14px}}
</style></head><body><main class="typography-settings">
<h1>Tema Tipografi Ayarları</h1>
<p class="intro">Bu tercihler yalnızca seçili temaya uygulanır. Google Fonts üzerinden seçtiğiniz yazı tipleri otomatik yüklenir; diğer temaların tipografisi değişmez.</p>
<?php if(($typographyMessage??'')!==''): ?><div class="type-notice <?= e((string)($typographyMessageType??'success'))==='error'?'error':'' ?>" role="status"><?= e($typographyMessage) ?></div><?php endif; ?>
<form method="post" action="<?= e(app_url('/admin/typography')) ?>">
<?= csrf_field() ?>
<section class="type-panel">
<h2>Ortak üst bilgi çubuğu yüksekliği</h2>
<p>Üst bilgi çubuğunun dikey yüksekliği. Değer piksel cinsindendir.</p>
<div class="type-field topbar-height"><label for="topbarHeight">Minimum yükseklik (px)</label><input id="topbarHeight" type="number" name="typography[topbar_height]" min="52" max="100" value="<?= (int)($settings['topbar_height']??64) ?>"></div>
</section>
<?php foreach($groups as $key=>$meta): $item=$settings[$key]??[]; ?>
<section class="type-panel">
<h2><?= e($meta[0]) ?></h2><p><?= e($meta[1]) ?></p>
<div class="type-fields">
<div class="type-field"><label for="<?= e($key) ?>Family">Google Font ailesi</label><select id="<?= e($key) ?>Family" name="typography[<?= e($key) ?>][family]"><?php foreach($fontOptions as $font=>$label): ?><option value="<?= e($font) ?>" <?= ($item['family']??'')===$font?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
<div class="type-field"><label for="<?= e($key) ?>Size">Yazı boyutu (px)</label><input id="<?= e($key) ?>Size" type="number" min="10" max="64" name="typography[<?= e($key) ?>][size]" value="<?= (int)($item['size']??16) ?>"></div>
<div class="type-field"><label for="<?= e($key) ?>Color">Yazı rengi</label><input id="<?= e($key) ?>Color" type="color" name="typography[<?= e($key) ?>][color]" value="<?= e((string)($item['color']??'#344039')) ?>"></div>
<div class="type-field"><label for="<?= e($key) ?>Weight">Font kalınlığı</label><select id="<?= e($key) ?>Weight" name="typography[<?= e($key) ?>][weight]"><?php foreach([300=>'Light',400=>'Normal',500=>'Medium',600=>'Semi Bold',700=>'Bold',800=>'Extra Bold',900=>'Black'] as $weight=>$label): ?><option value="<?= $weight ?>" <?= (int)($item['weight']??400)===$weight?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
<div class="type-field"><label for="<?= e($key) ?>Style">Yazı biçimi</label><select id="<?= e($key) ?>Style" name="typography[<?= e($key) ?>][style]"><option value="normal" <?= ($item['style']??'normal')==='normal'?'selected':'' ?>>Normal</option><option value="italic" <?= ($item['style']??'normal')==='italic'?'selected':'' ?>>İtalik</option></select></div>
</div>
<div class="type-preview"><strong>Örnek görünüm</strong><span><?= e($meta[0]) ?> — Villa tatiliniz için keyifli bir deneyim.</span></div>
</section>
<?php endforeach; ?>
<div class="type-actions"><button class="type-save" type="submit">Tema tipografisini kaydet</button><span class="type-help">Kaydettiğiniz ayarlar sayfa yenilendiğinde korunur.</span></div>
</form>
</main></body></html>