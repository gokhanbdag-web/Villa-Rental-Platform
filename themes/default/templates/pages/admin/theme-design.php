<?php
declare(strict_types=1);
$settings = is_array($themeDesignSettings ?? null) ? $themeDesignSettings : [];
$labels = [
    'primary'=>'Ana renk',
    'accent'=>'Vurgu rengi',
    'surface'=>'Kart ve panel yüzeyi',
    'background'=>'Sayfa arka planı',
    'text'=>'Ana metin rengi',
    'muted'=>'İkincil metin rengi',
    'border'=>'Kenarlık rengi',
];
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tema Ayarları</title>
<style>
.theme-design{max-width:1080px;margin:0 auto;color:#26352e}.theme-design h1{margin:0 0 8px;font-size:26px}.theme-design .intro{color:#718078;font-size:13px;margin:0 0 20px}.design-panel{background:#fff;border:1px solid #e4ebe6;border-radius:15px;padding:20px;margin-bottom:16px}.design-panel h2{font-size:16px;margin:0 0 6px}.design-panel p{font-size:12px;color:#77847c;margin:0 0 16px}.design-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.design-field{display:flex;align-items:center;gap:12px;padding:11px;border:1px solid #e8eee9;border-radius:10px}.design-field label{font-size:13px;font-weight:700;flex:1}.design-field input{width:54px;height:42px;padding:3px;border:1px solid #dce5df;border-radius:8px;background:#fff}.design-field code{font-size:11px;color:#718078}.design-preview{border:1px solid var(--preview-border);border-radius:14px;background:var(--preview-bg);padding:18px;color:var(--preview-text)}.design-preview .preview-card{background:var(--preview-surface);border:1px solid var(--preview-border);border-radius:11px;padding:15px;margin-top:12px}.design-preview .preview-button{display:inline-block;background:var(--preview-primary);color:#fff;border-radius:8px;padding:9px 14px;font-weight:700;margin-top:10px}.design-preview .preview-accent{color:var(--preview-accent);font-weight:800}.design-save{background:#176b50;color:#fff;border:0;border-radius:9px;padding:12px 18px;font-weight:800;cursor:pointer}.design-notice{padding:12px 14px;background:#eff7f1;color:#176b50;border-radius:9px;margin-bottom:16px}.design-notice.error{background:#fff1f0;color:#a32920}.design-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap}.design-help{font-size:12px;color:#77847c}@media(max-width:650px){.design-grid{grid-template-columns:1fr}.design-panel{padding:14px}}
</style></head><body><main class="theme-design">
<h1>Tema Ayarları</h1>
<p class="intro">Aktif tema: <strong><?= e((string)($themeDesignSlug ?? 'default')) ?></strong>. Renkler yalnızca bu temaya kaydedilir; site metinleri ve içerikleri etkilenmez.</p>
<?php if(($themeDesignMessage??'')!==''): ?><div class="design-notice <?= ($themeDesignMessageType??'')==='error'?'error':'' ?>" role="status"><?= e($themeDesignMessage) ?></div><?php endif; ?>
<form method="post" action="<?= e(app_url('/admin/theme-design')) ?>">
<?= csrf_field() ?>
<section class="design-panel"><h2>Renk paleti</h2><p>Renk seçicilerden değerleri değiştirin. Önizleme kaydetmeden önce anında güncellenir.</p>
<div class="design-grid">
<?php foreach($labels as $key=>$label): ?><div class="design-field"><label for="design-<?= e($key) ?>"><?= e($label) ?><br><code><?= e((string)($settings[$key]??'#000000')) ?></code></label><input id="design-<?= e($key) ?>" type="color" name="design[<?= e($key) ?>]" value="<?= e((string)($settings[$key]??'#000000')) ?>" data-design-color="<?= e($key) ?>"></div><?php endforeach; ?>
</div></section>
<section class="design-panel"><h2>Canlı önizleme</h2><p>Bu örnek, seçtiğiniz paletin temel arayüz elemanlarına etkisini gösterir.</p>
<div class="design-preview" id="designPreview"><span class="preview-accent">ÖNE ÇIKAN KONAKLAMA</span><h2>Hayalinizdeki tatil burada başlıyor</h2><div class="preview-card"><strong>Deniz manzaralı özel villa</strong><p>Konforlu alanlar, özel havuz ve unutulmaz bir konum.</p><span class="preview-button">Konaklamayı incele</span></div></div>
</section>
<div class="design-actions"><button class="design-save" type="submit">Tasarım ayarlarını kaydet</button><span class="design-help">Ayarlar veritabanında tema bazında saklanır ve diğer temaları değiştirmez.</span></div>
</form>
<?php if(($themeBrandMessage??'')!==''): ?><div class="design-notice <?= ($themeBrandMessageType??'')==='error'?'error':'' ?>" role="status"><?= e($themeBrandMessage) ?></div><?php endif; ?>
<section class="design-panel">
<h2>Aktif temanın logosu</h2>
<p>Logo, bu temanın üst alanında site adının yerine kullanılır. PNG, JPG veya WebP; en fazla 4 MB.</p>
<?php if(!empty($themeBrandSettings['logo_url'])): ?>
<div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin:12px 0">
<img src="<?= e($themeBrandSettings['logo_url']) ?>" alt="Mevcut tema logosu" style="max-width:220px;max-height:76px;object-fit:contain;background:#f7faf8;padding:10px;border:1px solid #e5ebe7;border-radius:9px">
<form method="post" action="<?= e(app_url('/admin/theme-design/brand-asset/remove')) ?>"><?= csrf_field() ?><input type="hidden" name="asset_type" value="logo"><button class="design-save" type="submit" style="background:#a43e35">Logoyu kaldır</button></form>
</div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" action="<?= e(app_url('/admin/theme-design/brand-asset')) ?>">
<?= csrf_field() ?><input type="hidden" name="asset_type" value="logo">
<div class="design-actions"><input type="file" name="brand_logo" accept="image/png,image/jpeg,image/webp" required><button class="design-save" type="submit">Logoyu yükle ve uygula</button></div>
</form>
</section>
<section class="design-panel">
<h2>Aktif temanın hero video arka planı</h2>
<p>MP4 veya WebM yükleyin. Video sessiz, döngülü ve otomatik oynatılır; en fazla 45 MB. Video kaldırılırsa temanın varsayılan hero görseli kullanılır.</p>
<?php if(!empty($themeBrandSettings['hero_video_url'])): ?>
<div style="margin:12px 0"><video src="<?= e($themeBrandSettings['hero_video_url']) ?>" muted playsinline controls style="display:block;width:min(100%,520px);max-height:180px;background:#17251f;border-radius:10px"></video>
<form method="post" action="<?= e(app_url('/admin/theme-design/brand-asset/remove')) ?>" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="asset_type" value="hero_video"><button class="design-save" type="submit" style="background:#a43e35">Videoyu kaldır</button></form></div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" action="<?= e(app_url('/admin/theme-design/brand-asset')) ?>">
<?= csrf_field() ?><input type="hidden" name="asset_type" value="hero_video">
<div class="design-actions"><input type="file" name="hero_video" accept="video/mp4,video/webm" required><button class="design-save" type="submit">Videoyu yükle ve uygula</button></div>
</form>
</section>
</main>
<script>
(function(){const form=document.querySelector('.theme-design form');const preview=document.getElementById('designPreview');if(!form||!preview)return;const map={primary:'--preview-primary',accent:'--preview-accent',surface:'--preview-surface',background:'--preview-bg',text:'--preview-text',border:'--preview-border'};function update(){form.querySelectorAll('[data-design-color]').forEach(input=>{const key=input.dataset.designColor;if(map[key])preview.style.setProperty(map[key],input.value);const code=input.parentElement.querySelector('code');if(code)code.textContent=input.value;});}form.addEventListener('input',update);update();})();
</script></body></html>
