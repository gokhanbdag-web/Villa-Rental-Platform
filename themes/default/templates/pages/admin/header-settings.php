<?php declare(strict_types=1);
$platforms = [
    'instagram'=>'Instagram','facebook'=>'Facebook','youtube'=>'YouTube','tiktok'=>'TikTok',
    'x'=>'X','whatsapp'=>'WhatsApp','pinterest'=>'Pinterest','telegram'=>'Telegram','linkedin'=>'LinkedIn'
];
$settings = is_array($socialSettings ?? null) ? $socialSettings : [];
?>
<!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Üst Bilgi Ayarları</title>
<style>
*{box-sizing:border-box}.top-settings{max-width:980px;margin:0 auto;color:#26352e}.top-settings h1{font-size:25px;margin:0 0 8px}.top-settings>p{color:#738078;margin:0 0 22px}.notice{padding:12px 14px;border-radius:9px;background:#eff7f1;color:#176b50;margin:0 0 16px;font-size:13px}.notice.error{background:#fff1f0;color:#a32920;border:1px solid #f0cbc7}.panel{background:#fff;border:1px solid #e5ebe7;border-radius:13px;padding:18px;margin:0 0 16px}.panel h2{font-size:16px;margin:0 0 5px}.panel>p{font-size:12px;color:#78847c;margin:0 0 14px}.fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.field label{display:block;font-size:12px;font-weight:700;color:#68766d;margin:0 0 5px}.field input,.field select{width:100%;padding:10px;border:1px solid #dce5dd;border-radius:8px;background:#fff;color:#26352e;font-size:13px}.social-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px}.social{padding:12px;border:1px solid #e5ebe7;border-radius:10px;background:#fbfcfa}.social strong{display:block;font-size:13px;margin-bottom:9px}.social .fields{grid-template-columns:1fr 90px}.save{border:0;border-radius:9px;background:#176b50;color:#fff;padding:12px 18px;font-weight:800;cursor:pointer}.save:hover{background:#12583f}@media(max-width:600px){.fields{grid-template-columns:1fr}.panel{padding:14px}.social .fields{grid-template-columns:1fr 80px}}
</style></head><body><main class="top-settings">
<h1>Üst Bilgi Ayarları</h1><p>Bu alan, tüm temalarda ortak kullanılan üst şerit ve sosyal medya bilgilerini yönetir.</p>
<?php if(($themeMessage??'')!==''): ?><div class="notice <?= e((string)($themeMessageType??'success'))==='error'?'error':'' ?>" role="status"><?= e($themeMessage) ?></div><?php endif; ?>
<form method="post" action="<?= e(app_url('/admin/header-settings/social-icons')) ?>">
<?= csrf_field() ?>
<section class="panel"><h2>Adres, çağrı merkezi ve TÜRSAB lisansı</h2><p>Her bilgi kendi alanı dolu olduğunda görüntülenir.</p>
<div class="fields">
<div class="field"><label for="topAddress">Adres</label><input id="topAddress" type="text" name="social[_address][text]" maxlength="180" value="<?= e((string)($settings['_address']['text']??'')) ?>"></div>
<div class="field"><label for="callTitle">Çağrı merkezi başlığı</label><input id="callTitle" type="text" name="social[_call_center][title]" maxlength="60" value="<?= e((string)($settings['_call_center']['title']??'')) ?>"></div>
<div class="field"><label for="callPhone">Çağrı merkezi telefonu</label><input id="callPhone" type="text" name="social[_call_center][phone]" maxlength="40" value="<?= e((string)($settings['_call_center']['phone']??'')) ?>"></div>
<div class="field"><label for="licenseTitle">TÜRSAB lisans başlığı</label><input id="licenseTitle" type="text" name="social[_license][title]" maxlength="60" value="<?= e((string)($settings['_license']['title']??'')) ?>"></div>
<div class="field"><label for="licenseNumber">TÜRSAB lisans numarası</label><input id="licenseNumber" type="text" name="social[_license][number]" maxlength="40" value="<?= e((string)($settings['_license']['number']??'')) ?>"></div>
</div></section>
<section class="panel"><h2>Mobil alt menü</h2><p>Bu ayar yalnızca aktif temanın mobil alt menüsündeki telefon kısayolunu kontrol eder.</p><div class="fields"><div class="field"><label for="mobileBarPhone">Telefon numarası</label><input id="mobileBarPhone" type="tel" name="mobile_bar_phone" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="905xxxxxxxxx" value="<?= e((string)($mobileBarPhone??'')) ?>"></div></div><p>Ülke koduyla birlikte girin. Örnek: 905xxxxxxxxx.</p></section>
<section class="panel"><h2>Sosyal medya bağlantıları</h2><p>Bağlantı adresi girilen platformlar otomatik görüntülenir.</p><div class="social-grid">
<?php foreach($platforms as $key=>$label): $item=$settings[$key]??[]; ?>
<div class="social"><strong><?= e($label) ?></strong><div class="field"><label for="social-<?= e($key) ?>">Profil bağlantısı</label><input id="social-<?= e($key) ?>" type="url" name="social[<?= e($key) ?>][url]" placeholder="https://" value="<?= e((string)($item['url']??'')) ?>"></div><div class="fields" style="margin-top:9px"><div class="field"><label>İkon biçimi</label><select name="social[<?= e($key) ?>][family]"><option value="brand" <?= ($item['family']??'brand')==='brand'?'selected':'' ?>>Marka</option><option value="circle" <?= ($item['family']??'brand')==='circle'?'selected':'' ?>>Çizgisel</option></select></div><div class="field"><label>Boyut (px)</label><input type="number" min="14" max="36" name="social[<?= e($key) ?>][size]" value="<?= (int)($item['size']??20) ?>"></div></div></div>
<?php endforeach; ?>
</div></section>
<button class="save" type="submit">Üst bilgi ayarlarını kaydet</button>
</form></main></body></html>
