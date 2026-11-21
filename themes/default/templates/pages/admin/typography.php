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
    'headings'=>['Headings','H1, H2, H3 and other heading elements'],
    'span'=>['Span text','Short text displayed in span elements'],
    'paragraph'=>['Paragraphs','All paragraph text'],
    'paragraph_links'=>['Paragraph links','Text links inside paragraphs only; excluding buttons'],
];
?>
<!doctype html><html lang="<?= e(\App\Core\Translator::locale()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e(villa_ui('Theme typography')) ?></title>
<style>
.typography-settings{max-width:1120px;margin:0 auto;color:#26352e}.typography-settings h1{font-size:25px;margin:0 0 7px}.typography-settings>.intro{color:#738078;font-size:13px;margin:0 0 22px}.type-notice{padding:12px 14px;border-radius:9px;background:#eff7f1;color:#176b50;margin:0 0 16px;font-size:13px}.type-notice.error{background:#fff1f0;color:#a32920;border:1px solid #f0cbc7}.type-panel{background:#fff;border:1px solid #e5ebe7;border-radius:14px;padding:19px;margin:0 0 15px}.type-panel h2{font-size:16px;margin:0 0 5px}.type-panel>p{font-size:12px;color:#78847c;margin:0 0 16px}.type-fields{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.type-field label{display:block;font-size:11px;font-weight:750;color:#68766d;margin:0 0 6px}.type-field input,.type-field select{width:100%;height:40px;padding:8px 10px;border:1px solid #dce5dd;border-radius:8px;background:#fff;color:#26352e;font-size:13px;min-width:0}.type-field input[type=color]{padding:4px;height:40px}.type-preview{margin-top:15px;padding:14px 16px;border:1px dashed #dce5dd;border-radius:10px;background:#fbfcfa}.type-preview strong{display:block;font-size:12px;color:#738078;margin-bottom:5px}.type-preview span{font-size:14px;color:#344039}.type-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.type-save{border:0;border-radius:9px;background:#176b50;color:#fff;padding:12px 18px;font-weight:800;cursor:pointer}.type-save:hover{background:#12583f}.type-help{font-size:12px;color:#78847c}.topbar-height{max-width:260px}@media(max-width:850px){.type-fields{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.type-fields{grid-template-columns:1fr}.type-panel{padding:14px}}
</style></head><body><main class="typography-settings">
<h1><?= e(villa_ui('Theme typography')) ?></h1>
<p class="intro"><?= e(villa_ui('Settings apply to the selected theme. Selected Google Fonts load automatically.')) ?></p>
<?php if(($typographyMessage??'')!==''): ?><div class="type-notice <?= e((string)($typographyMessageType??'success'))==='error'?'error':'' ?>" role="status"><?= e($typographyMessage) ?></div><?php endif; ?>
<form method="post" action="<?= e(app_url('/admin/typography')) ?>">
<?= csrf_field() ?>
<section class="type-panel">
<h2><?= e(villa_ui('Shared top bar height')) ?></h2>
<p><?= e(villa_ui('Vertical height of the top bar in pixels.')) ?></p>
<div class="type-field topbar-height"><label for="topbarHeight"><?= e(villa_ui('Minimum height (px)')) ?></label><input id="topbarHeight" type="number" name="typography[topbar_height]" min="52" max="100" value="<?= (int)($settings['topbar_height']??64) ?>"></div>
</section>
<section class="type-panel">
<h2><?= e(villa_ui('Page eyebrow color')) ?></h2>
<p><?= e(villa_ui('Sets the label color above page and section headings. Hero and banner settings are independent.')) ?></p>
<div class="type-fields">
<div class="type-field"><label for="eyebrowColor"><?= e(villa_ui('Text color')) ?></label><input id="eyebrowColor" type="color" name="typography[eyebrow_color]" value="<?= e((string)($settings['eyebrow_color']??'#263b31')) ?>"></div>
</div>
<div class="type-preview"><strong><?= e(villa_ui('Live preview')) ?></strong><span id="eyebrowColorPreview" style="display:inline-block;padding:7px 12px;border-radius:20px;background:#e8eee2;color:<?= e((string)($settings['eyebrow_color']??'#263b31')) ?>;font-size:12px;font-weight:800;letter-spacing:1px"><?= e(villa_ui('PAGE EYEBROW')) ?></span></div>
</section>
<?php foreach($groups as $key=>$meta): $item=$settings[$key]??[]; ?>
<section class="type-panel">
<h2><?= e($meta[0]) ?></h2><p><?= e($meta[1]) ?></p>
<div class="type-fields">
<div class="type-field"><label for="<?= e($key) ?>Family"><?= e(villa_ui('Google font family')) ?></label><select id="<?= e($key) ?>Family" name="typography[<?= e($key) ?>][family]"><?php foreach($fontOptions as $font=>$label): ?><option value="<?= e($font) ?>" <?= ($item['family']??'')===$font?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
<div class="type-field"><label for="<?= e($key) ?>Size"><?= e(villa_ui('Font size (px)')) ?></label><input id="<?= e($key) ?>Size" type="number" min="10" max="64" name="typography[<?= e($key) ?>][size]" value="<?= (int)($item['size']??16) ?>"></div>
<div class="type-field"><label for="<?= e($key) ?>Color"><?= e(villa_ui('Text color')) ?></label><input id="<?= e($key) ?>Color" type="color" name="typography[<?= e($key) ?>][color]" value="<?= e((string)($item['color']??'#344039')) ?>"></div>
<div class="type-field"><label for="<?= e($key) ?>Weight"><?= e(villa_ui('Font weight')) ?></label><select id="<?= e($key) ?>Weight" name="typography[<?= e($key) ?>][weight]"><?php foreach([300=>'Light',400=>'Normal',500=>'Medium',600=>'Semi Bold',700=>'Bold',800=>'Extra Bold',900=>'Black'] as $weight=>$label): ?><option value="<?= $weight ?>" <?= (int)($item['weight']??400)===$weight?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
<div class="type-field"><label for="<?= e($key) ?>Style"><?= e(villa_ui('Font style')) ?></label><select id="<?= e($key) ?>Style" name="typography[<?= e($key) ?>][style]"><option value="normal" <?= ($item['style']??'normal')==='normal'?'selected':'' ?>>Normal</option><option value="italic" <?= ($item['style']??'normal')==='italic'?'selected':'' ?>><?= e(villa_ui('Italic')) ?></option></select></div>
</div>
<div class="type-preview" data-type-preview="<?= e($key) ?>"><strong><?= e(villa_ui('Live preview')) ?></strong>
<?php if($key==='headings'): ?><h2 class="type-preview-content"><?= e(villa_ui('Enjoy your villa holiday')) ?></h2>
<?php elseif($key==='span'): ?><span class="type-preview-content"><?= e(villa_ui('Sample text — explore special offers.')) ?></span>
<?php elseif($key==='paragraph'): ?><p class="type-preview-content"><?= e(villa_ui('Enjoy an unforgettable holiday in our comfortable villas near the sea.')) ?></p>
<?php else: ?><p><?= e(villa_ui('Explore our villas and')) ?> <a class="type-preview-content" href="#preview-link" onclick="return false"><?= e(villa_ui('find your ideal holiday')) ?></a>.</p><?php endif; ?>
</div>
</section>
<?php endforeach; ?>
<div class="type-actions"><button class="type-save" type="submit"><?= e(villa_ui('Save theme typography')) ?></button><span class="type-help"><?= e(villa_ui('Saved settings are retained when the page reloads.')) ?></span></div>
</form>
</main>
<script>
(function () {
    const form = document.querySelector('.typography-settings form');
    if (!form) return;
    const weights = 'ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
    let fontLink = document.getElementById('typography-preview-fonts');
    if (!fontLink) {
        fontLink = document.createElement('link');
        fontLink.id = 'typography-preview-fonts';
        fontLink.rel = 'stylesheet';
        document.head.appendChild(fontLink);
    }
    function refreshFonts() {
        const families = Array.from(form.querySelectorAll('select[id$="Family"]'))
            .map(select => select.value)
            .filter((value, index, list) => list.indexOf(value) === index);
        const query = families.map(family => 'family=' + encodeURIComponent(family).replace(/%20/g, '+') + ':' + weights).join('&');
        fontLink.href = 'https://fonts.googleapis.com/css2?' + query + '&display=swap';
    }
    function applyGroup(group) {
        const preview = form.querySelector('[data-type-preview="' + group + '"] .type-preview-content');
        if (!preview) return;
        const fields = {
            family: form.querySelector('[name="typography[' + group + '][family]"]'),
            size: form.querySelector('[name="typography[' + group + '][size]"]'),
            color: form.querySelector('[name="typography[' + group + '][color]"]'),
            weight: form.querySelector('[name="typography[' + group + '][weight]"]'),
            style: form.querySelector('[name="typography[' + group + '][style]"]')
        };
        if (fields.family) preview.style.fontFamily = '"' + fields.family.value + '", sans-serif';
        if (fields.size) preview.style.fontSize = Math.max(10, Math.min(64, Number(fields.size.value) || 16)) + 'px';
        if (fields.color) preview.style.color = fields.color.value;
        if (fields.weight) preview.style.fontWeight = fields.weight.value;
        if (fields.style) preview.style.fontStyle = fields.style.value;
    }
    ['headings', 'span', 'paragraph', 'paragraph_links'].forEach(group => {
        const preview = form.querySelector('[data-type-preview="' + group + '"]');
        if (!preview) return;
        ['family', 'size', 'color', 'weight', 'style'].forEach(field => {
            const input = form.querySelector('[name="typography[' + group + '][' + field + ']"]');
            if (input) input.addEventListener('input', function () { applyGroup(group); if (field === 'family') refreshFonts(); });
            if (input && input.tagName === 'SELECT') input.addEventListener('change', function () { applyGroup(group); if (field === 'family') refreshFonts(); });
        });
        applyGroup(group);
    });
    const eyebrowColorInput = document.getElementById('eyebrowColor');
    const eyebrowColorPreview = document.getElementById('eyebrowColorPreview');
    if (eyebrowColorInput && eyebrowColorPreview) {
        eyebrowColorInput.addEventListener('input', function () { eyebrowColorPreview.style.color = eyebrowColorInput.value; });
    }
    const heightInput = document.getElementById('topbarHeight');
    if (heightInput) {
        const preview = document.createElement('div');
        preview.className = 'type-preview';
        preview.innerHTML = '<strong>Üst bilgi çubuğu yüksekliği önizlemesi</strong><div class="topbar-height-sample" style="display:flex;align-items:center;justify-content:center;min-height:64px;background:#176b50;color:#fff;border-radius:7px;padding:6px 12px;font-size:13px;font-weight:700">Telefon · Sosyal medya · İletişim</div>';
        heightInput.closest('.type-panel').appendChild(preview);
        const sample = preview.querySelector('.topbar-height-sample');
        const updateHeight = function () { sample.style.minHeight = Math.max(52, Math.min(100, Number(heightInput.value) || 64)) + 'px'; };
        heightInput.addEventListener('input', updateHeight);
        updateHeight();
    }
    refreshFonts();
})();
</script>
</body></html>