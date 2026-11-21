<?php
declare(strict_types=1);
$settings = is_array($themeDesignSettings ?? null) ? $themeDesignSettings : [];
$iconSettings = is_array($themeIconSettings ?? null) ? $themeIconSettings : ['library'=>'fontawesome','icons'=>['fa-star','fa-house','fa-heart','fa-shield-halved']];
$homepageStatsSettings = is_array($themeHomepageStatsSettings ?? null) ? $themeHomepageStatsSettings : ['villas'=>null,'destinations'=>null,'customers'=>null,'reviews'=>null];
$iconLibraries = ['fontawesome'=>'Font Awesome 6 Free','bootstrap'=>'Bootstrap Icons','material'=>'Google Material Symbols Rounded','lucide'=>'Lucide','unicode'=>'Unicode / Emoji'];
$iconLabels = ['1. Trust benefit','2. Accommodation benefit','3. Guest experience','4. Safety benefit'];
$labels = [
    'primary'=>'Ana renk',
    'accent'=>'Vurgu rengi',
    'surface'=>'Card and panel surface',
    'background'=>'Page background',
    'text'=>'Ana metin rengi',
    'muted'=>'Secondary text color',
    'border'=>'Border color',
    'eyebrow_background'=>'Eyebrow arka plan rengi',
    'eyebrow_text'=>'Eyebrow text color',
    'image_overlay_text'=>'Primary image overlay text',
    'image_overlay_muted'=>'Secondary image overlay text',
];
?>
<!doctype html><html lang="<?= e(\App\Core\Translator::locale()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e('Theme settings') ?></title>
<style>
.theme-design{max-width:1080px;margin:0 auto;color:#26352e}.theme-design h1{margin:0 0 8px;font-size:26px}.theme-design .intro{color:#718078;font-size:13px;margin:0 0 20px}.design-panel{background:#fff;border:1px solid #e4ebe6;border-radius:15px;padding:20px;margin-bottom:16px}.design-panel h2{font-size:16px;margin:0 0 6px}.design-panel p{font-size:12px;color:#77847c;margin:0 0 16px}.design-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.design-field{display:flex;align-items:center;gap:12px;padding:11px;border:1px solid #e8eee9;border-radius:10px}.design-field label{font-size:13px;font-weight:700;flex:1}.design-field input{width:54px;height:42px;padding:3px;border:1px solid #dce5df;border-radius:8px;background:#fff}.design-field code{font-size:11px;color:#718078}.frontend-preview-frame{width:100%;height:780px;overflow:hidden;border:1px solid #dfe7e2;border-radius:12px;background:#fff}.frontend-preview-frame iframe{display:block;width:100%;height:100%;border:0;background:#fff}.design-save{background:#176b50;color:#fff;border:0;border-radius:9px;padding:12px 18px;font-weight:800;cursor:pointer}.design-notice{padding:12px 14px;background:#eff7f1;color:#176b50;border-radius:9px;margin-bottom:16px}.design-notice.error{background:#fff1f0;color:#a32920}.design-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap}.design-help{font-size:12px;color:#77847c}@media(max-width:650px){.design-grid{grid-template-columns:1fr}.design-panel{padding:14px}}
.theme-design-layout{display:grid;grid-template-columns:190px minmax(0,1fr);gap:18px;align-items:start}.theme-design-tabs{position:sticky;top:18px;display:grid;gap:7px;padding:10px;background:#f5f8f6;border:1px solid #e4ebe6;border-radius:14px}.theme-tab{display:flex;align-items:center;width:100%;min-height:43px;padding:10px 12px;border:0;border-radius:9px;background:transparent;color:#4f6258;text-align:left;font-size:13px;font-weight:700;cursor:pointer}.theme-tab:hover{background:#e8efea}.theme-tab.is-active{background:#176b50;color:#fff}.theme-design-content{min-width:0}.theme-design [data-design-tab-panel][hidden]{display:none!important}.theme-design dialog.footer-widget-modal:not([open]){display:none!important}.theme-design dialog.footer-widget-modal[open]{display:block!important}.footer-layout-preview{display:grid;gap:9px;margin:16px 0 22px;padding:15px;border:1px dashed #d7e2da;border-radius:12px;background:#f8faf8}.footer-preview-row{display:grid;gap:8px}.footer-preview-row span{display:flex;align-items:center;justify-content:center;min-height:54px;border:1px solid #cbd9cf;border-radius:8px;background:#eaf1ec;color:#355343;font-size:12px;font-weight:750}.footer-preview-four{grid-template-columns:repeat(4,minmax(0,1fr))}.footer-preview-two{grid-template-columns:7fr 5fr} .design-field input.homepage-stat-input{width:150px}.design-field input[type=color]{width:58px;height:42px;padding:3px;border:1px solid #dce5df;border-radius:8px;background:#fff}@media(max-width:520px){.footer-preview-four{grid-template-columns:repeat(2,minmax(0,1fr))}.footer-preview-two{grid-template-columns:1fr}}.hero-type-groups{display:grid;gap:14px}.hero-type-group{padding:13px;border:1px solid #e8eee9;border-radius:11px}.hero-type-group h3{font-size:13px;margin:0 0 10px;color:#405449}.design-field select,.design-field input[type=number]{min-width:0;max-width:220px;height:40px;padding:7px 9px;border:1px solid #dce5df;border-radius:8px;background:#fff;color:#26352e}.design-save-sticky{position:sticky;bottom:0;z-index:5;margin-top:12px;padding:12px 0;background:linear-gradient(transparent,#fff 30%)}@media(max-width:760px){.theme-design-layout{grid-template-columns:1fr}.theme-design-tabs{position:static;grid-template-columns:repeat(2,minmax(0,1fr))}.theme-tab{justify-content:center;text-align:center}.design-save-sticky{position:static}}\n
.theme-design [data-design-tab-panel="footer"] .footer-layout-preview{display:grid!important;width:100%;min-width:0;box-sizing:border-box}.theme-design [data-design-tab-panel="footer"] .footer-widget-row{display:grid!important;min-width:0;width:100%;gap:9px}.theme-design [data-design-tab-panel="footer"] .footer-widget-row.footer-widget-four{grid-template-columns:repeat(4,minmax(0,1fr))!important}.theme-design [data-design-tab-panel="footer"] .footer-widget-row.footer-widget-two{grid-template-columns:minmax(0,7fr) minmax(0,5fr)!important}.theme-design [data-design-tab-panel="footer"] .footer-widget-tile{min-width:0;width:100%;box-sizing:border-box}.footer-widget-tile{min-width:0;min-height:82px;padding:12px 8px;border:1px solid #cbd9cf;border-radius:9px;background:#eaf1ec;color:#355343;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;cursor:pointer;font:inherit;transition:background .15s,border-color .15s,transform .15s}.footer-widget-tile:hover{background:#dcebe0;border-color:#176b50;transform:translateY(-1px)}.footer-widget-tile span{font-size:13px;font-weight:800}.footer-widget-tile small{font-size:11px;color:#63766a}.footer-widget-modal{width:min(780px,calc(100% - 28px));max-width:780px;max-height:min(82vh,720px);padding:0;border:1px solid #dce5df;border-radius:16px;box-shadow:0 24px 80px rgba(17,35,26,.28);color:#26352e}.footer-widget-modal::backdrop{background:rgba(13,27,20,.58);backdrop-filter:blur(2px)}.footer-widget-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:20px 22px;border-bottom:1px solid #e8eee9}.footer-widget-modal-head h3{font-size:17px;margin:0 0 5px}.footer-widget-modal-head p{font-size:12px;color:#77847c;margin:0}.footer-widget-modal-close{width:34px;height:34px;flex:0 0 34px;border:0;border-radius:50%;background:#f0f4f1;color:#43574b;font-size:24px;line-height:1;cursor:pointer}.footer-widget-modal-body{display:grid;gap:14px;padding:20px 22px}
.footer-logo-uploader{display:grid;gap:13px;padding:18px;border:1px solid #dce8df;border-radius:14px;background:linear-gradient(180deg,#fbfdfb 0%,#f5f9f6 100%)}
.footer-logo-uploader-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.footer-logo-uploader-heading strong{display:block;font-size:15px;color:#263b30}
.footer-logo-uploader-heading p{margin:4px 0 0;color:#748279;font-size:12px;line-height:1.5}
.footer-logo-format{flex:none;padding:6px 9px;border:1px solid #dce8df;border-radius:7px;background:#fff;color:#587064;font-size:10px;font-weight:800;letter-spacing:.04em}
.footer-logo-dropzone{display:flex;align-items:center;gap:14px;min-height:112px;padding:18px;border:2px dashed #b9cdbf;border-radius:12px;background:#fff}
.footer-logo-upload-icon{display:grid;place-items:center;flex:0 0 46px;width:46px;height:46px;border-radius:12px;background:#eaf4ed;color:#176b50;font-size:27px;font-weight:700}
.footer-logo-upload-copy{display:grid;gap:4px;min-width:0;flex:1}
.footer-logo-upload-copy strong{font-size:13px;color:#2e4437}
.footer-logo-upload-copy span{font-size:11px;line-height:1.5;color:#7a887f}
.footer-logo-choose-button{display:inline-flex!important;align-items:center;justify-content:center;flex:none;min-height:40px;padding:0 17px;border:0;border-radius:8px;background:#176b50;color:#fff!important;font-size:12px!important;font-weight:800;cursor:pointer;transition:background .15s ease,transform .15s ease}
.footer-logo-choose-button:hover{background:#10563f;transform:translateY(-1px)}
.footer-logo-file-input{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;clip-path:inset(50%)!important;border:0!important}
.footer-logo-file-input:focus-visible+.footer-logo-choose-button{outline:3px solid rgba(23,107,80,.25);outline-offset:3px}
.footer-logo-current{min-width:0}
.footer-logo-preview-image{display:flex;align-items:center;gap:16px;min-height:100px;padding:13px;border:1px solid #e2eae4;border-radius:10px;background:#fff}
.footer-logo-preview-image img{display:block;width:auto;max-width:min(100%,280px);max-height:100px;object-fit:contain;object-position:left center}
.footer-logo-preview-caption{padding:5px 8px;border-radius:6px;background:#edf5ef;color:#466b53;font-size:10px;font-weight:800}
.footer-logo-no-preview{display:flex;align-items:center;gap:10px;min-height:68px;padding:12px 14px;border:1px dashed #d8e3db;border-radius:9px;color:#87948b;font-size:12px;background:#fff}
.footer-logo-no-preview span:first-child{font-size:23px;color:#aab8ae}
.footer-logo-upload-status{margin:0;color:#708077;font-size:11px;line-height:1.5}
@media(max-width:560px){.footer-logo-dropzone{align-items:flex-start;flex-wrap:wrap}.footer-logo-upload-copy{flex-basis:calc(100% - 64px)}.footer-logo-choose-button{margin-left:60px}.footer-logo-uploader{padding:13px}.footer-logo-preview-image{align-items:flex-start;flex-direction:column}}
.footer-widget-modal-body .design-field{display:grid;align-items:start;gap:8px}.footer-widget-modal-body .design-field input[type=text],.footer-widget-modal-body textarea{width:100%;min-width:0;max-width:none;box-sizing:border-box;padding:11px;border:1px solid #dce5df;border-radius:8px;background:#fff;color:#26352e;font:inherit}.footer-modal-description textarea{resize:vertical}.footer-rich-editor-wrap{display:grid;gap:9px}.footer-rich-toolbar{display:flex;flex-wrap:wrap;gap:6px;padding:8px;border:1px solid #dce5df;border-bottom:0;border-radius:9px 9px 0 0;background:#f5f8f6}.footer-rich-toolbar button{min-width:34px;height:32px;padding:0 9px;border:1px solid #dce5df;border-radius:6px;background:#fff;color:#34483c;font-size:12px;font-weight:700;cursor:pointer}.footer-rich-toolbar button:hover{background:#eaf1ec;border-color:#176b50}.footer-editor-color-control{display:inline-flex;align-items:center;gap:7px;padding:0 8px;border:1px solid #dce5df;border-radius:6px;background:#fff;color:#34483c;font-size:11px;font-weight:700}.footer-editor-color-control input{width:28px;height:26px;padding:2px;border:0;background:transparent;cursor:pointer}.footer-rich-editor{min-height:170px;max-height:360px;overflow:auto;padding:13px;border:1px solid #dce5df;border-radius:0 0 9px 9px;background:#fff;color:#26352e;font:inherit;line-height:1.6;outline:none}.footer-rich-editor:focus{border-color:#176b50;box-shadow:0 0 0 3px rgba(23,107,80,.10)}.footer-rich-editor:empty:before{content:attr(data-placeholder);color:#9aa69e;pointer-events:none}.footer-rich-editor ul,.footer-rich-editor ol{padding-left:24px}.footer-rich-editor p{margin:0 0 8px}.footer-rich-editor-wrap .design-help{font-size:12px;color:#77847c}.footer-widget-empty-note{margin:0;padding:12px;border-radius:9px;background:#f6f8f6;color:#77847c;font-size:13px}.footer-widget-modal-actions{display:flex;justify-content:flex-end;padding:0 22px 20px}.footer-payment-order{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;min-width:0}.footer-payment-order .design-field{min-width:0;padding:9px 7px}.footer-payment-order .design-field label{font-size:11px}.footer-payment-order select{width:100%;max-width:none!important;font-size:11px;padding:6px 3px!important}
@media(max-width:520px){.theme-design [data-design-tab-panel="footer"] .footer-widget-row.footer-widget-four{grid-template-columns:repeat(4,minmax(0,1fr))!important}.theme-design [data-design-tab-panel="footer"] .footer-widget-row.footer-widget-two{grid-template-columns:minmax(0,7fr) minmax(0,5fr)!important}.theme-design [data-design-tab-panel="footer"] .footer-widget-tile{min-height:68px;padding:8px 3px}.theme-design [data-design-tab-panel="footer"] .footer-widget-tile span{font-size:11px}.theme-design [data-design-tab-panel="footer"] .footer-widget-tile small{font-size:9px}.footer-widget-modal-head,.footer-widget-modal-body{padding-left:16px;padding-right:16px}.footer-payment-order{grid-template-columns:repeat(5,minmax(0,1fr));gap:4px}.footer-payment-order .design-field{padding:7px 3px}.footer-payment-order .design-field label{font-size:10px}.footer-payment-order select{font-size:10px!important}.footer-widget-modal-actions{padding:0 16px 16px}}

.theme-file-uploader{display:grid;gap:10px;margin-top:4px;padding:15px;border:1px solid #dce8df;border-radius:13px;background:linear-gradient(180deg,#fbfdfb 0%,#f4f8f5 100%)}
.theme-file-drop{position:relative;display:flex;align-items:center;gap:13px;min-height:86px;padding:14px;border:2px dashed #b8cdbf;border-radius:11px;background:#fff;cursor:pointer;transition:border-color .16s,background .16s,box-shadow .16s}
.theme-file-drop:hover{border-color:#176b50;background:#f8fcf9}
.theme-file-drop:focus-within{outline:3px solid rgba(23,107,80,.18);outline-offset:2px}
.theme-file-input{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;clip-path:inset(50%)!important;border:0!important}
.theme-file-icon{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;border-radius:11px;background:#eaf4ed;color:#176b50;font-size:23px;font-weight:800}
.theme-file-copy{display:grid;gap:4px;min-width:0;flex:1}
.theme-file-copy strong{font-size:13px;color:#2e4437}
.theme-file-copy small{font-size:11px;line-height:1.45;color:#7a887f}
.theme-file-button{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:0 14px;border-radius:8px;background:#176b50;color:#fff;font-size:12px;font-weight:800;white-space:nowrap}
.theme-file-drop:hover .theme-file-button{background:#10563f}
.theme-file-preview{display:flex;align-items:center;gap:13px;min-height:72px;padding:11px;border:1px solid #e1e9e3;border-radius:10px;background:#fff}
.theme-file-preview[hidden]{display:none}
.theme-file-preview img,.theme-file-preview video{display:block;width:auto;max-width:min(100%,260px);max-height:132px;object-fit:contain;border-radius:7px;background:#17251f}
.theme-file-preview video{width:min(100%,260px)}
.theme-file-preview-copy{display:grid;gap:4px;min-width:0;overflow-wrap:anywhere}
.theme-file-preview-copy strong{font-size:12px;color:#35483c}
.theme-file-preview-copy span{font-size:11px;color:#78867d}
.theme-file-status{margin:0!important;color:#708077!important;font-size:11px!important;line-height:1.5}
@media(max-width:560px){.theme-file-drop{align-items:flex-start;flex-wrap:wrap}.theme-file-copy{flex-basis:calc(100% - 60px)}.theme-file-button{margin-left:55px}.theme-file-preview{align-items:flex-start;flex-direction:column}}
</style></head><body><main class="theme-design">
<h1><?= e('Theme settings') ?></h1>
<p class="intro"><?= e('Active theme:') ?> <strong><?= e((string)($themeDesignSlug ?? 'default')) ?></strong><?= e('. Colors apply only to this theme. Site content is preserved.') ?></p>
<?php if(($themeDesignMessage??'')!==''): ?><div class="design-notice <?= ($themeDesignMessageType??'')==='error'?'error':'' ?>" role="status"><?= e($themeDesignMessage) ?></div><?php endif; ?>
<div class="theme-design-layout"><aside class="theme-design-tabs" role="tablist" aria-label="<?= e('Theme settings sections') ?>"><button type="button" class="theme-tab is-active" role="tab" aria-selected="true" data-design-tab="header"><?= e('Header settings') ?></button><button type="button" class="theme-tab" role="tab" aria-selected="false" data-design-tab="typography"><?= e('Typography') ?></button><button type="button" class="theme-tab" role="tab" aria-selected="false" data-design-tab="cards"><?= e('Villa card settings') ?></button><button type="button" class="theme-tab" role="tab" aria-selected="false" data-design-tab="general"><?= e('General theme settings') ?></button><a class="theme-tab" href="<?= e(app_url('/admin/pages/layout?page=home&lang=en')) ?>">Homepage banner — Page editor</a><button type="button" class="theme-tab" role="tab" aria-selected="false" data-design-tab="footer"><?= e('Footer settings') ?></button></aside><div class="theme-design-content"><section class="design-panel" data-design-tab-panel="header"><h2><?= e('Logo and menu management') ?></h2><p><?= e('Upload your logo and hero video here. Edit menu items and their order in Menu management.') ?></p><div class="design-actions"><a class="design-save" href="<?= e(app_url('/admin/navigation')) ?>" style="text-decoration:none"><?= e('Manage menu items') ?></a><a class="design-save" href="<?= e(app_url('/admin/header-settings')) ?>" style="text-decoration:none;background:#42584d"><?= e('Header and social links') ?></a></div></section><form id="themeDesignForm" method="post" enctype="multipart/form-data" action="<?= e(app_url('/admin/theme-design')) ?>">
<?= csrf_field() ?>
<section class="design-panel" data-design-tab-panel="header"><h2><?= e('Hero text and buttons') ?></h2><p><?= e('Set the font family, size, color and weight of the hero eyebrow, heading, description and search button independently.') ?></p>
<div class="hero-type-groups"><?php foreach(['eyebrow'=>'Hero eyebrow','title'=>'Hero heading','description'=>'Hero description','button'=>'Hero butonu'] as $key=>$label): $item=$themeHeroTypographySettings[$key]??[]; ?>
<div class="hero-type-group"><h3><?= e($label) ?></h3><div class="design-grid">
<div class="design-field"><label><?= e('Font family') ?></label><select name="hero_typography[<?= e($key) ?>][family]"><?php foreach(['Inter','Roboto','Open Sans','Lato','Montserrat','Poppins','Nunito Sans','Playfair Display','Merriweather','DM Sans','Manrope','Source Sans 3','Raleway','PT Sans','Libre Baskerville'] as $font): ?><option value="<?= e($font) ?>" <?= ($item['family']??'')===$font?'selected':'' ?>><?= e($font) ?></option><?php endforeach; ?></select></div>
<div class="design-field"><label><?= e('Font size (px)') ?></label><input type="number" min="10" max="96" name="hero_typography[<?= e($key) ?>][size]" value="<?= (int)($item['size']??16) ?>"></div>
<div class="design-field"><label><?= e('Line height') ?></label><input type="number" min="0.9" max="2.5" step="0.05" name="hero_typography[<?= e($key) ?>][line_height]" value="<?= e((string)($item['line_height']??1.2)) ?>"></div>
<div class="design-field"><label><?= e('Text color') ?></label><input type="color" name="hero_typography[<?= e($key) ?>][color]" value="<?= e((string)($item['color']??'#ffffff')) ?>"></div>
<div class="design-field"><label><?= e('Font weight') ?></label><select name="hero_typography[<?= e($key) ?>][weight]"><?php foreach([300=>'Light',400=>'Normal',500=>'Medium',600=>'Semi Bold',700=>'Bold',800=>'Extra Bold',900=>'Black'] as $weight=>$weightLabel): ?><option value="<?= $weight ?>" <?= (int)($item['weight']??400)===$weight?'selected':'' ?>><?= e($weightLabel) ?></option><?php endforeach; ?></select></div>
<div class="design-field"><label><?= e('Font style') ?></label><select name="hero_typography[<?= e($key) ?>][style]"><option value="normal" <?= ($item['style']??'normal')==='normal'?'selected':'' ?>>Normal</option><option value="italic" <?= ($item['style']??'normal')==='italic'?'selected':'' ?>><?= e('Italic') ?></option></select></div>
</div></div><?php endforeach; ?></div>
<div class="design-grid" style="margin-top:14px"><div class="design-field"><label><?= e('Hero eyebrow background') ?></label><input type="color" name="hero_typography[eyebrow_background]" value="<?= e((string)($themeHeroTypographySettings['eyebrow_background']??'#176b50')) ?>"></div><div class="design-field"><label><?= e('Hero button background') ?></label><input type="color" name="hero_typography[button_background]" value="<?= e((string)($themeHeroTypographySettings['button_background']??'#176b50')) ?>"></div></div>
</section>
<section class="design-panel" data-design-tab-panel="header"><h2><?= e('Header layout') ?></h2><p><?= e('Set the height of the top bar.') ?></p>
<div class="design-grid"><div class="design-field"><label><?= e('Top bar height (px)') ?></label><input type="number" min="52" max="100" name="typography[topbar_height]" value="<?= (int)($themeTypographySettings['topbar_height']??64) ?>"></div></div>
</section>
<section class="design-panel" data-design-tab-panel="typography"><h2><?= e('Typography system') ?></h2><p><?= e('Manage fonts, sizes and line heights for body text, headings, eyebrows and links. Hero typography is managed under Header settings.') ?></p>
<?php $headingSettings=$themeTypographySettings['headings']??[]; $headingLevels=['h1'=>'H1 · Main heading','h2'=>'H2 · Section heading','h3'=>'H3 · Card and subheading','h4'=>'H4 · Subheading','h5'=>'H5 · Small heading','h6'=>'H6 · Smallest heading']; ?>
<div class="hero-type-group" style="margin-top:12px"><h3><?= e('Heading typography (H1–H6)') ?></h3><p><?= e('Headings share a font family. Each level has independent size and line height settings.') ?></p><div class="design-grid">
<div class="design-field"><label><?= e('Heading font family') ?></label><select name="typography[headings][family]"><?php foreach(['Inter','Roboto','Open Sans','Lato','Montserrat','Poppins','Nunito Sans','Playfair Display','Merriweather','DM Sans','Manrope','Source Sans 3','Raleway','PT Sans','Libre Baskerville'] as $font): ?><option value="<?= e($font) ?>" <?= ($headingSettings['family']??'Manrope')===$font?'selected':'' ?>><?= e($font) ?></option><?php endforeach; ?></select></div>
<div class="design-field"><label><?= e('Heading color') ?></label><input type="color" name="typography[headings][color]" value="<?= e((string)($headingSettings['color']??'#263b31')) ?>"></div>
<div class="design-field"><label><?= e('Font weight') ?></label><select name="typography[headings][weight]"><?php foreach([300=>'Light',400=>'Normal',500=>'Medium',600=>'Semi Bold',700=>'Bold',800=>'Extra Bold',900=>'Black'] as $weight=>$weightLabel): ?><option value="<?= $weight ?>" <?= (int)($headingSettings['weight']??700)===$weight?'selected':'' ?>><?= e($weightLabel) ?></option><?php endforeach; ?></select></div>
<div class="design-field"><label><?= e('Font style') ?></label><select name="typography[headings][style]"><option value="normal" <?= ($headingSettings['style']??'normal')==='normal'?'selected':'' ?>>Normal</option><option value="italic" <?= ($headingSettings['style']??'normal')==='italic'?'selected':'' ?>><?= e('Italic') ?></option></select></div>
<?php foreach($headingLevels as $level=>$levelLabel): ?>
<div class="design-field"><label><?= e($levelLabel) ?> <?= e('· Size (px)') ?></label><input type="number" min="12" max="120" name="typography[headings][sizes][<?= e($level) ?>]" value="<?= (int)($headingSettings['sizes'][$level]??(['h1'=>46,'h2'=>34,'h3'=>26,'h4'=>22,'h5'=>19,'h6'=>17][$level])) ?>"></div>
<div class="design-field"><label><?= e($levelLabel) ?> <?= e('· Line height') ?></label><input type="number" min="0.9" max="2.5" step="0.05" name="typography[headings][line_heights][<?= e($level) ?>]" value="<?= e((string)($headingSettings['line_heights'][$level]??1.2)) ?>"></div>
<?php endforeach; ?>
</div></div>
<?php foreach(['span'=>'Eyebrow ve etiketler','paragraph'=>'Body text','paragraph_links'=>'Paragraph links'] as $key=>$label): $item=$themeTypographySettings[$key]??[]; ?>
<div class="hero-type-group" style="margin-top:12px"><h3><?= e('Page eyebrow color') ?></h3><p><?= e('Set the label color above page and section headings. Hero and banner eyebrows have separate settings.') ?></p><div class="design-grid"><div class="design-field"><label><?= e('Eyebrow text color') ?></label><input type="color" name="typography[eyebrow_color]" value="<?= e((string)($themeTypographySettings['eyebrow_color']??'#263b31')) ?>"></div></div></div>
<div class="hero-type-group" style="margin-top:12px"><h3><?= e($label) ?></h3><div class="design-grid">
<div class="design-field"><label><?= e('Font family') ?></label><select name="typography[<?= e($key) ?>][family]"><?php foreach(['Inter','Roboto','Open Sans','Lato','Montserrat','Poppins','Nunito Sans','Playfair Display','Merriweather','DM Sans','Manrope','Source Sans 3','Raleway','PT Sans','Libre Baskerville'] as $font): ?><option value="<?= e($font) ?>" <?= ($item['family']??'')===$font?'selected':'' ?>><?= e($font) ?></option><?php endforeach; ?></select></div>
<div class="design-field"><label><?= e('Font size (px)') ?></label><input type="number" min="10" max="96" name="typography[<?= e($key) ?>][size]" value="<?= (int)($item['size']??16) ?>"></div>
<div class="design-field"><label><?= e('Line height') ?></label><input type="number" min="0.9" max="2.5" step="0.05" name="typography[<?= e($key) ?>][line_height]" value="<?= e((string)($item['line_height']??1.7)) ?>"></div>
<?php if($key!=='span'): ?><div class="design-field"><label><?= e('Text color') ?></label><input type="color" name="typography[<?= e($key) ?>][color]" value="<?= e((string)($item['color']??'#344039')) ?>"></div><?php endif; ?>
<div class="design-field"><label><?= e('Font weight') ?></label><select name="typography[<?= e($key) ?>][weight]"><?php foreach([300=>'Light',400=>'Normal',500=>'Medium',600=>'Semi Bold',700=>'Bold',800=>'Extra Bold',900=>'Black'] as $weight=>$weightLabel): ?><option value="<?= $weight ?>" <?= (int)($item['weight']??400)===$weight?'selected':'' ?>><?= e($weightLabel) ?></option><?php endforeach; ?></select></div>
<div class="design-field"><label><?= e('Font style') ?></label><select name="typography[<?= e($key) ?>][style]"><option value="normal" <?= ($item['style']??'normal')==='normal'?'selected':'' ?>>Normal</option><option value="italic" <?= ($item['style']??'normal')==='italic'?'selected':'' ?>><?= e('Italic') ?></option></select></div>

</div></div><?php endforeach; ?>
</section>
<section class="design-panel" data-design-tab-panel="general"><h2><?= e('Color palette') ?></h2><p><?= e('Choose colors to update the preview before saving.') ?></p>
<div class="design-grid">
<?php foreach($labels as $key=>$label): ?><div class="design-field"><label for="design-<?= e($key) ?>"><?= e($label) ?><br><code><?= e((string)($settings[$key]??'#000000')) ?></code></label><input id="design-<?= e($key) ?>" type="color" name="design[<?= e($key) ?>]" value="<?= e((string)($settings[$key]??'#000000')) ?>" data-design-color="<?= e($key) ?>"></div><?php endforeach; ?>
</div></section>
<section class="design-panel" data-design-tab-panel="cards">
<h2><?= e('Villa card layout') ?></h2><p><?= e('Set columns and spacing for desktop, tablet and mobile.') ?></p>
<div class="design-grid">
<?php foreach(['columns_desktop'=>'Desktop columns','columns_tablet'=>'Tablet columns','columns_mobile'=>'Mobile columns'] as $key=>$label): ?><div class="design-field"><label><?= e($label) ?></label><select name="cards[<?= e($key) ?>]"><?php $max=$key==='columns_desktop'?5:($key==='columns_tablet'?4:2);for($n=1;$n<=$max;$n++): ?><option value="<?= $n ?>" <?= (int)$themeCardSettings[$key]===$n?'selected':'' ?>><?= $n ?> <?= e('columns') ?></option><?php endfor; ?></select></div><?php endforeach; ?>
<div class="design-field"><label><?= e('Card gap (px)') ?></label><input type="number" min="8" max="48" name="cards[grid_gap]" value="<?= (int)$themeCardSettings['grid_gap'] ?>"></div>
</div></section>
<section class="design-panel" data-design-tab-panel="cards"><h2><?= e('Card appearance') ?></h2><p><?= e('Corner radius, borders and padding.') ?></p><div class="design-grid">
<div class="design-field"><label><?= e('Card corner radius (px)') ?></label><input type="number" min="0" max="40" name="cards[radius]" value="<?= (int)$themeCardSettings['radius'] ?>"></div>
<div class="design-field"><label><?= e('Border width (px)') ?></label><input type="number" min="0" max="4" name="cards[border_width]" value="<?= (int)$themeCardSettings['border_width'] ?>"></div>
<div class="design-field"><label><?= e('Border color') ?></label><input type="color" name="cards[border_color]" value="<?= e($themeCardSettings['border_color']) ?>"></div>
<div class="design-field"><label><?= e('Horizontal padding (px)') ?></label><input type="number" min="8" max="32" name="cards[padding_x]" value="<?= (int)$themeCardSettings['padding_x'] ?>"></div>
<div class="design-field"><label><?= e('Vertical padding (px)') ?></label><input type="number" min="8" max="32" name="cards[padding_y]" value="<?= (int)$themeCardSettings['padding_y'] ?>"></div>
<div class="design-field"><label><?= e('Desktop image height (px)') ?></label><input type="number" min="160" max="360" name="cards[image_height]" value="<?= (int)$themeCardSettings['image_height'] ?>"></div>
<div class="design-field"><label><?= e('Mobile image height (px)') ?></label><input type="number" min="120" max="280" name="cards[image_height_mobile]" value="<?= (int)$themeCardSettings['image_height_mobile'] ?>"></div>
</div></section>
<section class="design-panel" data-design-tab-panel="cards"><h2><?= e('Card typography') ?></h2><p><?= e('Set card font size and weight. Font families and text colors come from the theme settings.') ?></p><div class="design-grid">
<?php foreach(['title_size'=>['Villa adı boyutu (px)',12,28,1],'title_weight'=>['Villa adı kalınlığı',300,900,100],'title_line_height'=>['Villa adı satır yüksekliği',1,2,.05],'meta_size'=>['Konum ve detay boyutu (px)',9,18,.5],'meta_line_height'=>['Konum satır yüksekliği',1,2,.05],'price_size'=>['Fiyat boyutu (px)',12,32,1],'price_weight'=>['Fiyat kalınlığı',300,900,100],'rating_size'=>['Puan ve değerlendirme boyutu (px)',8,14,.5],'rating_weight'=>['Değerlendirme kalınlığı',300,900,100],'rating_note_size'=>['Değerlendirme açıklaması (px)',7,13,.5]] as $key=>$field): ?><div class="design-field"><label><?= e(villa_ui($field[0])) ?></label><input type="number" min="<?= $field[1] ?>" max="<?= $field[2] ?>" step="<?= $field[3] ?>" name="cards[<?= e($key) ?>]" value="<?= e((string)$themeCardSettings[$key]) ?>"></div><?php endforeach; ?>
</div></section><section class="design-panel" data-design-tab-panel="general" hidden style="display:none"><h2><?= e('Homepage icons') ?></h2><p><?= e('Choose a library and icons for the four benefit cards below the hero. The library loads when needed and the circular design is retained.') ?></p>
<div class="design-grid">
<div class="design-field"><label for="iconLibrary"><?= e('Icon library') ?></label><select id="iconLibrary" name="icons[library]" style="min-width:220px;height:42px;border:1px solid #dce5df;border-radius:8px;padding:6px;background:#fff"><?php foreach($iconLibraries as $value=>$label): ?><option value="<?= e($value) ?>" <?= ($iconSettings['library']??'fontawesome')===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
<?php foreach($iconLabels as $i=>$label): ?><div class="design-field"><label for="trustIcon<?= $i ?>"><?= e($label) ?><br><code><?= e((string)($iconSettings['icons'][$i]??'')) ?></code></label><input id="trustIcon<?= $i ?>" type="text" name="icons[icons][<?= $i ?>]" value="<?= e((string)($iconSettings['icons'][$i]??'')) ?>" maxlength="60" style="width:190px;height:42px;padding:6px;border:1px solid #dce5df;border-radius:8px"></div><?php endforeach; ?>
</div><p style="margin-top:12px"><?= e('Examples: for Font Awesome use') ?> <code>fa-star</code><?= e(', for Bootstrap Icons use') ?> <code>bi-star-fill</code><?= e(', for Material Symbols use') ?> <code>home</code><?= e(', for Lucide use') ?> <code>house</code><?= e('. The library loads from a CDN.') ?></p></section>
<section class="design-panel" data-design-tab-panel="stats" hidden style="display:none"><h2><?= e('Homepage statistics and counters') ?></h2><p><?= e('Leave a field blank to use the live database count. Enter a number, including zero, to override that counter.') ?></p>
<div class="design-grid">
<?php foreach(['villas'=>'Villa count','destinations'=>'Destination count','customers'=>'Customer count','reviews'=>'Approved review count'] as $statKey=>$statLabel): $statValue=$homepageStatsSettings[$statKey]??null; ?>
<div class="design-field"><label for="homepageStat<?= e($statKey) ?>"><?= e($statLabel) ?><br><code><?= $statValue===null?'Automatic · live data': 'Manual value' ?></code></label><input class="homepage-stat-input" id="homepageStat<?= e($statKey) ?>" type="number" min="0" max="999999999" step="1" name="homepage_stats[<?= e($statKey) ?>]" value="<?= $statValue===null?'':(int)$statValue ?>" placeholder="Otomatik"></div>
<?php endforeach; ?>
</div><p style="margin-top:12px"><?= e('Live counts include published villas, active cities with villas, active customers and approved reviews.') ?></p></section>
<section class="design-panel" data-design-tab-panel="general"><h2><?= e('Live preview') ?></h2><p><?= e('Preview the visitor homepage. Color changes appear here before saving.') ?></p>
<div class="frontend-preview-frame"><iframe id="frontendThemePreview" title="<?= e('Live site design preview') ?>" src="<?= e(app_url('/')) ?>" loading="eager"></iframe></div>
</section>

<section class="design-panel" data-design-tab-panel="footer">
<h2><?= e('Footer layout') ?></h2>
<p><?= e('The footer has six widget areas. Select posts and a heading for the recent posts widget in column 2.') ?></p>
<div class="footer-layout-preview" aria-label="<?= e('Six footer widget areas') ?>">
  <div class="footer-widget-row footer-widget-four">
    <?php foreach([1,2,3,4] as $widget): ?>
      <button type="button" class="footer-widget-tile" data-footer-modal-open="footerWidgetModal<?= $widget ?>"><span><?= e('Column') ?> <?= $widget ?></span><small><?= $widget===2?'Recent blog posts':'Edit content' ?></small></button>
    <?php endforeach; ?>
  </div>
  <div class="footer-widget-row footer-widget-two">
    <button type="button" class="footer-widget-tile" data-footer-modal-open="footerWidgetModal5"><span><?= e('Column 5') ?></span><small><?= e('7/12 · Edit content') ?></small></button>
    <button type="button" class="footer-widget-tile" data-footer-modal-open="footerWidgetModal6"><span><?= e('Column 6') ?></span><small><?= e('5/12 · Edit content') ?></small></button>
  </div>
</div>
<?php for($widget=1;$widget<=6;$widget++): ?>
<dialog class="footer-widget-modal" id="footerWidgetModal<?= $widget ?>" aria-labelledby="footerWidgetModalTitle<?= $widget ?>">
  <div class="footer-widget-modal-head">
    <div><h3 id="footerWidgetModalTitle<?= $widget ?>"><?= e('Footer column') ?> <?= $widget ?> <?= e('content') ?></h3><p><?= e('Edit this column’s content here.') ?></p></div>
    <button type="button" class="footer-widget-modal-close" data-footer-modal-close aria-label="Pencereyi kapat">×</button>
  </div>
  <div class="footer-widget-modal-body">
    <?php if($widget===1): ?>
      <input type="hidden" name="footer[logo_path]" value="<?= e((string)($themeFooterSettings['logo_path']??'')) ?>">
      <section class="footer-logo-uploader" aria-labelledby="footerLogoUploadTitle">
        <div class="footer-logo-uploader-heading">
          <div><strong id="footerLogoUploadTitle"><?= e('Footer logo') ?></strong><p><?= e('Upload the logo shown in place of the first column heading.') ?></p></div>
          <span class="footer-logo-format">PNG · JPG · WEBP</span>
        </div>
        <div class="footer-logo-dropzone">
          <div class="footer-logo-upload-icon" aria-hidden="true">↑</div>
          <div class="footer-logo-upload-copy"><strong><?= e('Select logo file') ?></strong><span><?= e('Choose an image from your computer. Maximum 4 MB.') ?></span></div>
          <label class="footer-logo-choose-button" for="footerLogoUpload"><?= e('Choose logo') ?></label>
          <input class="footer-logo-file-input" id="footerLogoUpload" type="file" name="footer_logo" accept="image/png,image/jpeg,image/webp">
        </div>
        <div class="footer-logo-current" data-footer-logo-preview>
          <?php if(!empty($themeFooterSettings['logo_url'])): ?>
            <div class="footer-logo-preview-image"><img src="<?= e((string)$themeFooterSettings['logo_url']) ?>" alt="Mevcut footer logosu"><span class="footer-logo-preview-caption"><?= e('Current logo') ?></span></div>
          <?php else: ?>
            <div class="footer-logo-no-preview"><span aria-hidden="true">▧</span><span><?= e('No footer logo uploaded.') ?></span></div>
          <?php endif; ?>
        </div>
        <p class="footer-logo-upload-status" data-footer-logo-status aria-live="polite"><?= e('A preview appears when you select a logo.') ?></p>
        <small class="design-help"><?= e('This logo replaces the first footer column heading. The header keeps its own logo.') ?></small>
      </section>
      <div class="design-field"><label for="footerAboutTitle"><?= e('Heading (shown without logo)') ?></label><input id="footerAboutTitle" type="text" name="footer[about_title]" maxlength="150" value="<?= e((string)($themeFooterSettings['about_title']??'')) ?>" placeholder="<?= e('E.g. About us') ?>"></div>
      <div class="footer-rich-editor-wrap">
        <label for="footerAboutDescriptionEditor"><?= e('Descriptions') ?></label>
        <div class="footer-rich-toolbar" role="toolbar" aria-label="<?= e('Footer description formatting tools') ?>">
          <button type="button" data-footer-editor-command="bold" aria-label="<?= e('Bold') ?>"><strong>B</strong></button>
          <button type="button" data-footer-editor-command="italic" aria-label="<?= e('Italic') ?>"><em>I</em></button>
          <button type="button" data-footer-editor-command="underline" aria-label="<?= e('Underline') ?>"><u>U</u></button>
          <label class="footer-editor-color-control"><?= e('Text color') ?> <input type="color" data-footer-editor-color value="#ffffff" aria-label="<?= e('Selected text color') ?>"></label>
          <button type="button" data-footer-editor-apply-color aria-label="<?= e('Apply color to selection') ?>"><?= e('Apply color') ?></button>
          <button type="button" data-footer-editor-command="insertUnorderedList" aria-label="<?= e('Bulleted list') ?>"><?= e('• List') ?></button>
          <button type="button" data-footer-editor-command="insertOrderedList" aria-label="<?= e('Numbered list') ?>"><?= e('1. List') ?></button>
          <button type="button" data-footer-editor-link aria-label="<?= e('Add link') ?>">🔗 Link ekle</button>
          <button type="button" data-footer-editor-command="unlink" aria-label="<?= e('Remove link') ?>"><?= e('Remove link') ?></button>
          <button type="button" data-footer-editor-command="removeFormat" aria-label="<?= e('Clear formatting') ?>"><?= e('Clear formatting') ?></button>
        </div>
        <div id="footerAboutDescriptionEditor" class="footer-rich-editor" contenteditable="true" role="textbox" aria-multiline="true" data-footer-rich-editor data-footer-hidden="footerAboutDescriptionHtml" data-placeholder="<?= e('Enter footer description…') ?>"><?= (string)($themeFooterSettings['about_description']??'') ?></div>
        <input type="hidden" id="footerAboutDescriptionHtml" name="footer[about_description]" value="<?= e((string)($themeFooterSettings['about_description']??'')) ?>">
        <p class="design-help"><?= e('Select text to change its color, formatting or link. The content appears in footer column 1.') ?></p>
      </div>
    <?php elseif($widget===2): ?>
      <div class="design-field"><label for="footerRecentBlogTitle"><?= e('Widget title') ?></label><input id="footerRecentBlogTitle" type="text" name="footer[recent_blog_title]" maxlength="100" value="<?= e((string)($themeFooterSettings['recent_blog_title']??'Recent blog posts')) ?>" placeholder="<?= e('Recent blog posts') ?>"></div>
      <div class="footer-recent-blog-picker" style="display:grid;gap:8px;max-height:340px;overflow:auto;padding:4px 2px">
        <?php $selectedFooterBlogIds=array_map('intval',(array)($themeFooterSettings['recent_blog_post_ids']??[])); ?>
        <?php if(empty($themeFooterBlogPosts)): ?><p class="footer-widget-empty-note"><?= e('No published blog posts yet.') ?></p><?php else: foreach($themeFooterBlogPosts as $footerBlogPost): ?>
          <label style="display:flex;align-items:center;gap:10px;padding:9px 11px;border:1px solid #e5ece7;border-radius:9px;font-size:13px;font-weight:600;min-width:0"><input type="checkbox" name="footer[recent_blog_post_ids][]" value="<?= (int)$footerBlogPost['id'] ?>" <?= in_array((int)$footerBlogPost['id'],$selectedFooterBlogIds,true)?'checked':'' ?> style="width:17px;height:17px;flex:0 0 17px"><span style="min-width:0;overflow-wrap:anywhere"><?= e((string)$footerBlogPost['title']) ?></span></label>
        <?php endforeach; endif; ?>
      </div>
      <p class="design-help"><?= e('Select up to eight posts for column 2. Each shows a thumbnail and title. Unpublished posts are hidden automatically.') ?></p>
    <?php elseif($widget===5): ?>
      <div class="footer-rich-editor-wrap">
        <label for="footerCopyrightEditor"><?= e('Copyright notice') ?></label>
        <div class="footer-rich-toolbar" role="toolbar" aria-label="<?= e('Copyright formatting tools') ?>">
          <button type="button" data-footer-editor-command="bold" aria-label="<?= e('Bold') ?>"><strong>B</strong></button>
          <button type="button" data-footer-editor-command="italic" aria-label="<?= e('Italic') ?>"><em>I</em></button>
          <button type="button" data-footer-editor-command="underline" aria-label="<?= e('Underline') ?>"><u>U</u></button>
          <label class="footer-editor-color-control" for="footerEditorTextColor"><?= e('Text color') ?> <input type="color" data-footer-editor-color value="#ffffff" aria-label="<?= e('Selected text color') ?>"></label>
          <button type="button" data-footer-editor-apply-color aria-label="<?= e('Apply color to selection') ?>"><?= e('Apply color') ?></button>
          <button type="button" data-footer-editor-command="insertUnorderedList" aria-label="<?= e('Bulleted list') ?>"><?= e('• List') ?></button>
          <button type="button" data-footer-editor-command="insertOrderedList" aria-label="<?= e('Numbered list') ?>"><?= e('1. List') ?></button>
          <button type="button" data-footer-editor-link aria-label="<?= e('Add link') ?>">🔗 Link ekle</button>
          <button type="button" data-footer-editor-command="unlink" aria-label="<?= e('Remove link') ?>"><?= e('Remove link') ?></button>
          <button type="button" data-footer-editor-command="removeFormat" aria-label="<?= e('Clear formatting') ?>"><?= e('Clear formatting') ?></button>
        </div>
        <div id="footerCopyrightEditor" class="footer-rich-editor" contenteditable="true" role="textbox" aria-multiline="true" data-footer-rich-editor data-footer-hidden="footerCopyrightHtml" data-placeholder="<?= e('E.g. © 2026 VillaCMS. All rights reserved.') ?>"><?= (string)($themeFooterSettings['copyright_html']??'') ?></div>
        <input type="hidden" id="footerCopyrightHtml" name="footer[copyright_html]" value="<?= e((string)($themeFooterSettings['copyright_html']??'')) ?>">
        <p class="design-help"><?= e('Format text and add or remove links. Saved content appears in footer column 5.') ?></p>
      </div>
    <?php elseif($widget===6): ?>
      <div class="footer-payment-order" aria-label="<?= e('Payment provider order') ?>">
        <?php $paymentLabels=['mastercard'=>'Mastercard','visa'=>'Visa','troy'=>'Troy','diners_club'=>'Diners Club','american_express'=>'American Express']; $paymentOrder=$themeFooterSettings['payment_order']??array_keys($paymentLabels); for($position=0;$position<5;$position++): $selectedPayment=(string)($paymentOrder[$position]??array_keys($paymentLabels)[$position]); ?>
          <div class="design-field"><label for="footerPayment<?= $position+1 ?>"><?= $position+1 ?><?= e('. Order') ?></label><select id="footerPayment<?= $position+1 ?>" name="footer[payment_order][]"><?php foreach($paymentLabels as $value=>$label): ?><option value="<?= e($value) ?>" <?= $selectedPayment===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
        <?php endfor; ?>
      </div>
      <p class="footer-widget-empty-note"><?= e('Order the five payment providers. The footer displays their logos in four columns.') ?></p>
    <?php else: ?>
      <p class="footer-widget-empty-note"><?= e('Content fields for this column will be added later.') ?></p>
    <?php endif; ?>
  </div>
  <div class="footer-widget-modal-actions"><button type="button" class="design-save" data-footer-modal-close><?= e('Close') ?></button></div>
</dialog>
<?php endfor; ?>
<div class="design-grid">
  <div class="design-field"><label for="footerBackground"><?= e('Footer background color') ?></label><input id="footerBackground" type="color" name="footer[background]" value="<?= e((string)($themeFooterSettings['background']??'#20352e')) ?>"></div>
  <div class="design-field"><label for="footerPaddingTop"><?= e('Top padding (px)') ?></label><input id="footerPaddingTop" type="number" min="0" max="160" name="footer[padding_top]" value="<?= (int)($themeFooterSettings['padding_top']??42) ?>"></div>
  <div class="design-field"><label for="footerPaddingBottom"><?= e('Bottom padding (px)') ?></label><input id="footerPaddingBottom" type="number" min="0" max="160" name="footer[padding_bottom]" value="<?= (int)($themeFooterSettings['padding_bottom']??42) ?>"></div>
  <div class="design-field"><label for="footerMarginTop"><?= e('Footer top margin (px)') ?></label><input id="footerMarginTop" type="number" min="0" max="160" name="footer[margin_top]" value="<?= (int)($themeFooterSettings['margin_top']??55) ?>"></div>
  <div class="design-field"><label for="footerContentWidth"><?= e('Maximum content width (px)') ?></label><input id="footerContentWidth" type="number" min="760" max="1600" name="footer[content_width]" value="<?= (int)($themeFooterSettings['content_width']??1180) ?>"></div>
</div>
<p style="margin-top:14px"><?= e('Settings apply to the active theme. The other five widget areas remain empty.') ?></p>
</section>
</form>
<section class="design-panel" data-design-tab-panel="family-banner" hidden style="display:none">
<h2><?= e('Background image after holiday styles') ?></h2>
<p><?= e('Upload a landscape image for the full-width area below holiday styles. The image fits the available height on desktop and mobile.') ?></p>
<?php if(!empty($themeBrandSettings['family_banner_url'])): ?>
<div style="margin:12px 0"><img src="<?= e($themeBrandSettings['family_banner_url']) ?>" alt="<?= e('Homepage banner image') ?>" style="display:block;width:min(100%,720px);height:160px;object-fit:cover;border-radius:10px;border:1px solid #e5ebe7">
<form method="post" action="<?= e(app_url('/admin/theme-design/brand-asset/remove')) ?>" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="asset_type" value="family_banner"><button class="design-save" type="submit" style="background:#a43e35"><?= e('Remove banner image') ?></button></form></div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" action="<?= e(app_url('/admin/theme-design/brand-asset')) ?>">
<?= csrf_field() ?><input type="hidden" name="asset_type" value="family_banner">
<div class="design-actions"><input type="file" name="family_banner" accept="image/jpeg,image/png,image/webp" required><button class="design-save" type="submit"><?= e('Upload background image') ?></button></div>
</form>

<form method="post" action="<?= e(app_url('/admin/theme-design/family-banner-text')) ?>" class="family-banner-text-form">
<?= csrf_field() ?>
<h3 style="margin:22px 0 8px"><?= e('Banner text and typography') ?></h3>
<p><?= e('Text is centered over the banner. Choose a shared color and set each field’s font, size and line height.') ?></p>
<div class="design-field"><label>Eyebrow text</label><input type="text" name="family_banner_text[eyebrow]" maxlength="190" value="<?= e($themeFamilyBannerTextSettings['eyebrow'] ?? '') ?>" placeholder="<?= e('E.g. A MEDITERRANEAN ESCAPE') ?>"></div>
<div class="design-field"><label><?= e('Title') ?></label><input type="text" name="family_banner_text[title]" maxlength="190" value="<?= e($themeFamilyBannerTextSettings['title'] ?? '') ?>" placeholder="<?= e('E.g. Your dream holiday starts here') ?>"></div>
<div class="design-field"><label><?= e('Content') ?></label><textarea name="family_banner_text[description]" rows="3" maxlength="1200" style="width:100%;padding:11px;border:1px solid #dce5df;border-radius:8px;font:inherit"><?= e($themeFamilyBannerTextSettings['description'] ?? '') ?></textarea></div>
<div class="design-field"><label><?= e('Shared text color') ?></label><input type="color" name="family_banner_text[color]" value="<?= e($themeFamilyBannerTextSettings['color'] ?? '#ffffff') ?>" style="width:90px;height:42px;padding:4px"></div>
<?php $bannerFonts=['Inter','Roboto','Open Sans','Lato','Montserrat','Poppins','Nunito Sans','Playfair Display','Merriweather','DM Sans','Manrope','Source Sans 3','Raleway','PT Sans','Libre Baskerville']; foreach(['eyebrow'=>'Eyebrow text','title'=>'Title','description'=>'Content'] as $key=>$label): $type=$themeFamilyBannerTextSettings[$key.'_typography']??[]; ?>
<div class="hero-type-group"><h3><?= e($label) ?> <?= e('typography settings') ?></h3><div class="design-grid">
<div class="design-field"><label><?= e('Font family') ?></label><select name="family_banner_text[<?= e($key) ?>_typography][family]"><?php foreach($bannerFonts as $font): ?><option value="<?= e($font) ?>" <?= ($type['family']??'')===$font?'selected':'' ?>><?= e($font) ?></option><?php endforeach; ?></select></div>
<div class="design-field"><label><?= e('Font size (px)') ?></label><input type="number" min="10" max="100" name="family_banner_text[<?= e($key) ?>_typography][size]" value="<?= (int)($type['size']??16) ?>"></div>
<div class="design-field"><label><?= e('Line height') ?></label><input type="number" min="0.9" max="2.5" step="0.05" name="family_banner_text[<?= e($key) ?>_typography][line_height]" value="<?= e((string)($type['line_height']??1.2)) ?>"></div>
</div></div>
<?php endforeach; ?>
<div class="design-actions" style="margin-top:16px"><button class="design-save" type="submit"><?= e('Save banner text settings') ?></button></div>
</form>
</section>

<?php if(($themeBrandMessage??'')!==''): ?><div class="design-notice <?= ($themeBrandMessageType??'')==='error'?'error':'' ?>" role="status"><?= e($themeBrandMessage) ?></div><?php endif; ?>
<section class="design-panel" data-design-tab-panel="header">
<h2><?= e('Active theme logo') ?></h2>
<p><?= e('The logo replaces the site name in this theme’s header. Use PNG, JPG or WebP up to 4 MB.') ?></p>
<?php if(!empty($themeBrandSettings['logo_url'])): ?>
<div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin:12px 0">
<img src="<?= e($themeBrandSettings['logo_url']) ?>" alt="Mevcut tema logosu" style="max-width:220px;max-height:76px;object-fit:contain;background:#f7faf8;padding:10px;border:1px solid #e5ebe7;border-radius:9px">
<form method="post" action="<?= e(app_url('/admin/theme-design/brand-asset/remove')) ?>"><?= csrf_field() ?><input type="hidden" name="asset_type" value="logo"><button class="design-save" type="submit" style="background:#a43e35"><?= e('Remove logo') ?></button></form>
</div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" action="<?= e(app_url('/admin/theme-design/brand-asset')) ?>">
<?= csrf_field() ?><input type="hidden" name="asset_type" value="logo">
<div class="theme-file-uploader" data-theme-file-uploader><label class="theme-file-drop" for="brandLogoFile"><input id="brandLogoFile" class="theme-file-input" type="file" name="brand_logo" accept="image/png,image/jpeg,image/webp" required data-theme-file-kind="image" data-theme-file-max="4194304"><span class="theme-file-icon" aria-hidden="true">↑</span><span class="theme-file-copy"><strong><?= e('Choose header logo') ?></strong><small><?= e('PNG, JPG or WebP · Up to 4 MB') ?></small></span><span class="theme-file-button"><?= e('Choose file') ?></span></label><div class="theme-file-preview" data-theme-file-preview hidden></div><p class="theme-file-status" data-theme-file-status aria-live="polite"><?= e('No new logo selected.') ?></p><div class="design-actions"><button class="design-save" type="submit"><?= e('Upload and apply logo') ?></button></div></div>
</form>
<form method="post" action="<?= e(app_url('/admin/theme-design/logo-settings')) ?>" style="margin-top:16px;padding:16px;border:1px solid #e5ebe7;border-radius:12px">
<?= csrf_field() ?>
<h3 style="margin:0 0 8px"><?= e('Logo dimensions and preview') ?></h3>
<p><?= e('Change the width and height to update the preview. Settings apply to the active theme.') ?></p>
<div style="display:flex;align-items:center;justify-content:center;min-height:130px;padding:14px;background:#f7faf8;border:1px dashed #d5e1d8;border-radius:10px;overflow:hidden">
<div id="logoSizePreview" style="width:<?= (int)($themeBrandSettings['logo_width']??220) ?>px;height:<?= (int)($themeBrandSettings['logo_height']??54) ?>px;max-width:100%;display:flex;align-items:center;justify-content:center;overflow:hidden">
<?php if(!empty($themeBrandSettings['logo_url'])): ?>
<img src="<?= e($themeBrandSettings['logo_url']) ?>" alt="<?= e('Logo size preview') ?>" style="display:block;width:100%;height:100%;object-fit:contain">
<?php else: ?>
<span style="font-size:20px;font-weight:800;color:#176b50">VillaCMS</span>
<?php endif; ?>
</div>
</div>
<div class="design-grid" style="margin-top:14px">
<div class="design-field"><label for="logoWidth"><?= e('Logo width (px)') ?></label><input id="logoWidth" type="number" name="logo_width" min="80" max="500" value="<?= (int)($themeBrandSettings['logo_width']??220) ?>"></div>
<div class="design-field"><label for="logoHeight"><?= e('Logo height (px)') ?></label><input id="logoHeight" type="number" name="logo_height" min="24" max="160" value="<?= (int)($themeBrandSettings['logo_height']??54) ?>"></div>
</div>
<div class="design-actions" style="margin-top:12px"><button class="design-save" type="submit"><?= e('Save logo dimensions') ?></button></div>
</form>
</section>
<section class="design-panel" data-design-tab-panel="header">
<h2><?= e('Hero video background') ?></h2>
<p><?= e('Use a YouTube, MP4 or WebM background. The fallback image appears when no video is available. YouTube videos autoplay muted on a loop.') ?></p>
<form method="post" action="<?= e(app_url('/admin/theme-design/hero-video-settings')) ?>" style="margin:14px 0;padding:16px;border:1px solid #e5ebe7;border-radius:12px">
<?= csrf_field() ?>
<label style="display:block;font-weight:700;margin-bottom:7px"><?= e('Hero fallback image') ?></label>
<input type="url" name="hero_background_image_url" value="<?= e($themeBrandSettings['hero_background_image_url']??'') ?>" placeholder="https://.../hero-gorseli.webp" style="width:100%;padding:11px;border:1px solid #dce5df;border-radius:8px">
<small style="display:block;color:#718078;margin:6px 0 14px"><?= e('Shown when video is unavailable. Leave blank to use the existing hero image.') ?></small>
<label style="display:block;font-weight:700;margin-bottom:7px"><?= e('YouTube video URL') ?></label>
<input type="url" name="hero_youtube_url" value="<?= e($themeBrandSettings['hero_youtube_url']??'') ?>" placeholder="https://www.youtube.com/watch?v=..." style="width:100%;padding:11px;border:1px solid #dce5df;border-radius:8px">
<small style="display:block;color:#718078;margin:6px 0 14px"><?= e('YouTube, Shorts and youtu.be links are supported. Leave blank to use the uploaded video.') ?></small>
<label for="heroVideoStartSeconds" style="display:block;font-weight:700;margin:14px 0 7px"><?= e('Video start time in seconds') ?></label>
<input id="heroVideoStartSeconds" type="number" name="hero_video_start_seconds" min="0" max="86400" step="1" value="<?= (int)($themeBrandSettings['hero_video_start_seconds']??0) ?>" style="width:100%;padding:11px;border:1px solid #dce5df;border-radius:8px">
<small style="display:block;color:#718078;margin:6px 0 14px"><?= e('For example, enter 15 to start at 15 seconds or 0 to start at the beginning.') ?></small>
<input type="hidden" name="hero_video_loop" value="0">
<label style="display:flex;align-items:center;gap:9px;font-weight:700;margin:12px 0 14px"><input type="checkbox" name="hero_video_loop" value="1" <?= !empty($themeBrandSettings['hero_video_loop'])?'checked':'' ?>> <?= e('Loop video') ?></label>
<label for="heroOverlayColor" style="display:block;font-weight:700;margin:14px 0 7px"><?= e('Overlay color') ?></label>
<div style="display:flex;align-items:center;gap:12px"><input id="heroOverlayColor" type="color" name="hero_overlay_color" value="<?= e($themeBrandSettings['hero_overlay_color']??'#081d19') ?>" style="width:64px;height:42px;padding:3px;border:1px solid #dce5df;border-radius:8px;background:#fff"><small style="color:#718078"><?= e('Applies to the video and fallback image.') ?></small></div>
<label for="heroOverlayTexture" style="display:block;font-weight:700;margin:14px 0 7px"><?= e('Overlay texture') ?></label>
<select id="heroOverlayTexture" name="hero_overlay_texture" style="width:100%;padding:11px;border:1px solid #dce5df;border-radius:8px;background:#fff">
<option value="none" <?= ($themeBrandSettings['hero_overlay_texture']??'none')==='none'?'selected':'' ?>><?= e('Solid color') ?></option>
<option value="diagonal" <?= ($themeBrandSettings['hero_overlay_texture']??'none')==='diagonal'?'selected':'' ?>><?= e('Diagonal lines') ?></option>
<option value="grid" <?= ($themeBrandSettings['hero_overlay_texture']??'none')==='grid'?'selected':'' ?>><?= e('Fine grid') ?></option>
<option value="dots" <?= ($themeBrandSettings['hero_overlay_texture']??'none')==='dots'?'selected':'' ?>><?= e('Dotted') ?></option>
</select>
<label for="heroOverlayOpacity" style="display:block;font-weight:700;margin:14px 0 7px"><?= e('Overlay opacity:') ?> <strong id="heroOverlayOpacityValue"><?= (int)($themeBrandSettings['hero_overlay_opacity']??58) ?>%</strong></label>
<input id="heroOverlayOpacity" type="range" name="hero_overlay_opacity" min="0" max="100" step="1" value="<?= (int)($themeBrandSettings['hero_overlay_opacity']??58) ?>" oninput="document.getElementById('heroOverlayOpacityValue').textContent=this.value+'%'" style="width:100%">
<small style="display:block;color:#718078;margin:6px 0 14px"><?= e('0%: no darkening · 100%: darkest. Controls the overlay on the video and fallback image.') ?></small>
<button class="design-save" type="submit"><?= e('Save video and overlay settings') ?></button>
</form>
<?php if(!empty($themeBrandSettings['hero_video_url'])): ?>
<div style="margin:12px 0"><video src="<?= e($themeBrandSettings['hero_video_url']) ?>" muted playsinline controls preload="metadata" style="display:block;width:min(100%,520px);max-height:180px;background:#17251f;border-radius:10px"></video>
<form method="post" action="<?= e(app_url('/admin/theme-design/brand-asset/remove')) ?>" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="asset_type" value="hero_video"><button class="design-save" type="submit" style="background:#a43e35"><?= e('Remove video') ?></button></form></div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" action="<?= e(app_url('/admin/theme-design/brand-asset')) ?>">
<?= csrf_field() ?><input type="hidden" name="asset_type" value="hero_video">
<div class="theme-file-uploader" data-theme-file-uploader><label class="theme-file-drop" for="heroVideoFile"><input id="heroVideoFile" class="theme-file-input" type="file" name="hero_video" accept="video/mp4,video/webm" required data-theme-file-kind="video"><span class="theme-file-icon" aria-hidden="true">▶</span><span class="theme-file-copy"><strong><?= e('Choose hero video') ?></strong><small><?= e('MP4 or WebM video file') ?></small></span><span class="theme-file-button"><?= e('Choose video') ?></span></label><div class="theme-file-preview" data-theme-file-preview hidden></div><p class="theme-file-status" data-theme-file-status aria-live="polite"><?= e('No new video selected.') ?></p><div class="design-actions"><button class="design-save" type="submit"><?= e('Upload and apply video') ?></button></div></div>
</form>
</section>

</div></div><div class="design-actions design-save-sticky"><button class="design-save" type="submit" form="themeDesignForm"><?= e('Save design settings') ?></button><span class="design-help"><?= e('Settings are stored separately for each theme.') ?></span></div>
</main>
<script>
/* Essential theme controls use delegated events and do not depend on preview scripts. */
(function(){
 const root=document.querySelector('.theme-design');
 if(!root)return;
 const tabs=root.querySelectorAll('[data-design-tab]');
 const panels=root.querySelectorAll('[data-design-tab-panel]');
 function activateTab(name){
  if(!name)return;
  tabs.forEach(function(tab){
   const active=tab.dataset.designTab===name;
   tab.classList.toggle('is-active',active);
   tab.setAttribute('aria-selected',active?'true':'false');
  });
  panels.forEach(function(panel){
   panel.hidden=panel.dataset.designTabPanel!==name;
  });
 }
 const initialTab=root.querySelector('[data-design-tab].is-active');
 if(tabs.length)activateTab(initialTab?initialTab.dataset.designTab:tabs[0].dataset.designTab);
 root.addEventListener('click',function(event){
  const tab=event.target.closest('[data-design-tab]');
  if(tab&&root.contains(tab)){
   event.preventDefault();
   activateTab(tab.dataset.designTab);
   return;
  }
  const openButton=event.target.closest('[data-footer-modal-open]');
  if(openButton&&root.contains(openButton)){
   event.preventDefault();
   const modal=document.getElementById(openButton.dataset.footerModalOpen);
   if(!modal)return;
   try{
    if(typeof modal.showModal==='function'){
     if(!modal.open)modal.showModal();
    }else{
     modal.setAttribute('open','');
    }
   }catch(error){
    modal.setAttribute('open','');
   }
   return;
  }
  const closeButton=event.target.closest('[data-footer-modal-close]');
  if(closeButton&&root.contains(closeButton)){
   event.preventDefault();
   const modal=closeButton.closest('.footer-widget-modal');
   if(!modal)return;
   try{
    if(typeof modal.close==='function'&&modal.open)modal.close();
    else modal.removeAttribute('open');
   }catch(error){
    modal.removeAttribute('open');
   }
   return;
  }
  const modal=event.target.closest('.footer-widget-modal[open]');
  if(modal&&event.target===modal){
   try{
    if(typeof modal.close==='function'&&modal.open)modal.close();
    else modal.removeAttribute('open');
   }catch(error){
    modal.removeAttribute('open');
   }
  }
 });
})();
</script>
<script>
(function(){
 const widthInput=document.getElementById('logoWidth');
 const heightInput=document.getElementById('logoHeight');
 const preview=document.getElementById('logoSizePreview');
 if(!widthInput||!heightInput||!preview)return;
 const updateLogoPreview=function(){
  const width=Math.max(80,Math.min(500,parseInt(widthInput.value,10)||220));
  const height=Math.max(24,Math.min(160,parseInt(heightInput.value,10)||54));
  preview.style.width=width+'px';
  preview.style.height=height+'px';
  preview.style.maxWidth='100%';
 };
 widthInput.addEventListener('input',updateLogoPreview);
 heightInput.addEventListener('input',updateLogoPreview);
 updateLogoPreview();
})();
(function(){const form=document.querySelector('.theme-design form');const frame=document.getElementById('frontendThemePreview');if(!form||!frame)return;const keys=['primary','accent','surface','background','text','muted','border','eyebrow_background','eyebrow_text','image_overlay_text','image_overlay_muted'];const paletteRules='body[data-villa-theme]{background:var(--villa-background)!important;color:var(--villa-text)!important}body[data-villa-theme] .theme-header,body[data-villa-theme] .site-header,body[data-villa-theme] .villa-social-bar,body[data-villa-theme] .theme-header .nav{background:var(--villa-surface)!important;border-color:var(--villa-border)!important}body[data-villa-theme] .hero,body[data-villa-theme] .intro,body[data-villa-theme] .section,body[data-villa-theme] .home-accommodations{background-color:var(--villa-background)!important}body[data-villa-theme] .stay,body[data-villa-theme] .premium-stay-card,body[data-villa-theme] .filter-panel,body[data-villa-theme] .booking,body[data-villa-theme] .panel,body[data-villa-theme] .card,body[data-villa-theme] .med-review-card,body[data-villa-theme] .med-support-panel{background:var(--villa-surface)!important;border-color:var(--villa-border)!important;color:var(--villa-text)!important}body[data-villa-theme] .theme-brand,body[data-villa-theme] .theme-brand-name,body[data-villa-theme] .brand,body[data-villa-theme] .section-head h2,body[data-villa-theme] .section h2,body[data-villa-theme] .stay h3,body[data-villa-theme] .med-collection-heading h3,body[data-villa-theme] .med-faq-item summary,body[data-villa-theme] .med-support h2{color:var(--villa-text)!important}body[data-villa-theme] .theme-header .nav a,body[data-villa-theme] .theme-header .nav-submenu-toggle,body[data-villa-theme] .villa-social-bar,body[data-villa-theme] .med-property-body,body[data-villa-theme] .med-review-card>p{color:var(--villa-text)!important}body[data-villa-theme] .muted,body[data-villa-theme] .meta,body[data-villa-theme] .crumb,body[data-villa-theme] .section-head p,body[data-villa-theme] .med-property-place,body[data-villa-theme] .med-property-meta,body[data-villa-theme] .med-property-price,body[data-villa-theme] .med-collection-heading p,body[data-villa-theme] .med-faq-intro p,body[data-villa-theme] .med-support p{color:var(--villa-muted)!important}body[data-villa-theme] .search-submit,body[data-villa-theme] .submit,body[data-villa-theme] .button-primary,body[data-villa-theme] .med-support-whatsapp,body[data-villa-theme] .pagination .current{background:var(--villa-primary)!important;border-color:var(--villa-primary)!important;color:#fff!important}body[data-villa-theme] .theme-header .nav a:hover,body[data-villa-theme] .theme-header .nav a[aria-current=page],body[data-villa-theme] .pill,body[data-villa-theme] .med-trust-icon,body[data-villa-theme] .rating-score{background:var(--villa-accent)!important;color:var(--villa-text)!important}body[data-villa-theme] input,body[data-villa-theme] select,body[data-villa-theme] textarea,body[data-villa-theme] .search-enhanced,body[data-villa-theme] .search-field,body[data-villa-theme] .med-faq-item{border-color:var(--villa-border)!important}body[data-villa-theme] footer,body[data-villa-theme] .site-footer{background:var(--villa-primary)!important;border-color:var(--villa-primary)!important;color:initial!important}body[data-villa-theme] .theme-header .nav a,body[data-villa-theme] .theme-header .nav-submenu-toggle{color:var(--villa-primary)!important}body[data-villa-theme] .theme-header .nav a:hover,body[data-villa-theme] .theme-header .nav a:focus-visible,body[data-villa-theme] .theme-header .nav a[aria-current="page"],body[data-villa-theme] .theme-header .nav-entry.is-open>.nav-entry-top>a{background:var(--villa-primary)!important;color:#fff!important}body[data-villa-theme] .theme-header .nav-dropdown{border-color:var(--villa-primary)!important}body[data-villa-theme] .theme-header .nav-dropdown a:hover,body[data-villa-theme] .theme-header .nav-dropdown a:focus-visible{background:var(--villa-primary)!important;color:#fff!important}body[data-villa-theme] .search-submit,body[data-villa-theme] .submit,body[data-villa-theme] .button-primary,body[data-villa-theme] .button,body[data-villa-theme] .btn,body[data-villa-theme] .btn-primary,body[data-villa-theme] .cta,body[data-villa-theme] button[type=submit],body[data-villa-theme] input[type=submit],body[data-villa-theme] .med-support-whatsapp,body[data-villa-theme] .pagination .current,body[data-villa-theme] .premium-stay-card .card-gallery-arrow{background:var(--villa-primary)!important;border:1px solid var(--villa-primary)!important;color:#fff!important}body[data-villa-theme] .med-trust-icon,body[data-villa-theme] .trust-icon,body[data-villa-theme] .feature-icon,body[data-villa-theme] .icon-circle{background:var(--villa-primary)!important;border-color:var(--villa-primary)!important;color:#fff!important}body[data-villa-theme] .theme-menu-toggle{background:var(--villa-primary)!important;border-color:var(--villa-primary)!important;color:#fff!important}body[data-villa-theme] input:focus,body[data-villa-theme] select:focus,body[data-villa-theme] textarea:focus,body[data-villa-theme] .search-enhanced:focus-within{border-color:var(--villa-primary)!important;outline-color:var(--villa-primary)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--villa-primary) 16%,transparent)!important}body[data-villa-theme] .stay:hover,body[data-villa-theme] .premium-stay-card:hover,body[data-villa-theme] .card:hover{border-color:color-mix(in srgb,var(--villa-primary) 48%,var(--villa-border))!important}body[data-villa-theme] .pagination a{border-color:var(--villa-primary)!important;color:var(--villa-primary)!important}body[data-villa-theme] .pagination a:hover{background:var(--villa-primary)!important;color:#fff!important}body[data-villa-theme] .villa-eyebrow,body[data-villa-theme] .pill,body[data-villa-theme] .eyebrow,body[data-villa-theme] .section-eyebrow,body[data-villa-theme] .section-head .eyebrow,body[data-villa-theme] .med-faq-intro .eyebrow{display:inline-flex;align-items:center;width:fit-content;padding:7px 13px;border-radius:999px;background:var(--villa-eyebrow_background)!important;border:1px solid var(--villa-eyebrow_background)!important;color:var(--villa-eyebrow_text)!important;font-size:11px;font-weight:700;line-height:1.2;letter-spacing:.08em;text-transform:uppercase}body[data-villa-theme] .premium-stay-card .card-gallery-count,body[data-villa-theme] .premium-stay-card .stay-badge{color:var(--villa-image_overlay_text)!important}body[data-villa-theme] .premium-stay-card .card-gallery-count,body[data-villa-theme] .premium-stay-card .stay-badge{background:rgba(8,29,25,.72)!important;border-color:rgba(255,255,255,.25)!important}';function update(){let doc;try{doc=frame.contentDocument;}catch(e){return;}if(!doc||!doc.body)return;const body=doc.body;const root=doc.documentElement;form.querySelectorAll('[data-design-color]').forEach(input=>{const key=input.dataset.designColor;if(keys.includes(key)){body.style.setProperty('--villa-'+key,input.value);root.style.setProperty('--villa-'+key,input.value);}const code=input.parentElement.querySelector('code');if(code)code.textContent=input.value;});let style=doc.getElementById('villa-theme-live-preview-overrides');if(!style){style=doc.createElement('style');style.id='villa-theme-live-preview-overrides';(doc.head||root).appendChild(style);}const heroVal=(group,field)=>form.querySelector('[name="hero_typography['+group+']['+field+']"]')?.value||'';const heroRule=(group,selector)=>{const family=heroVal(group,'family'),size=heroVal(group,'size'),color=heroVal(group,'color'),weight=heroVal(group,'weight'),styleValue=heroVal(group,'style'),lineHeight=heroVal(group,'line_height');return selector+'{font-family:"'+family+'",sans-serif!important;font-size:'+size+'px!important;line-height:'+lineHeight+'!important;color:'+color+'!important;font-weight:'+weight+'!important;font-style:'+styleValue+'!important}';};const heroPreview=heroRule('eyebrow','body[data-villa-theme] .hero-copy .villa-hero-eyebrow')+heroRule('title','body[data-villa-theme] .hero-copy h1')+heroRule('description','body[data-villa-theme] .hero-copy p')+heroRule('button','body[data-villa-theme] .hero .search-submit')+'body[data-villa-theme] .hero-copy .villa-hero-eyebrow{background:'+form.querySelector('[name="hero_typography[eyebrow_background]"]')?.value+'!important}body[data-villa-theme] .hero .search-submit{background:'+form.querySelector('[name="hero_typography[button_background]"]')?.value+'!important}';const typographyValue=(group,field)=>form.querySelector('[name="typography['+group+']['+field+']"]')?.value||'';const typographyPreview=(()=>{const family=typographyValue('paragraph','family'),bodySize=typographyValue('paragraph','size'),bodyLine=typographyValue('paragraph','line_height'),headFamily=typographyValue('headings','family');let css='body[data-villa-theme]{font-family:"'+family+'",sans-serif!important;font-size:'+bodySize+'px!important;line-height:'+bodyLine+'!important}body[data-villa-theme] p{font-family:"'+family+'",sans-serif!important;font-size:'+bodySize+'px!important;line-height:'+bodyLine+'!important}';['h1','h2','h3','h4','h5','h6'].forEach(level=>{const size=form.querySelector('[name="typography[headings][sizes]['+level+']"]')?.value||'20',line=form.querySelector('[name="typography[headings][line_heights]['+level+']"]')?.value||'1.2';css+='body[data-villa-theme] '+level+'{font-family:"'+headFamily+'",sans-serif!important;font-size:'+size+'px!important;line-height:'+line+'!important}';});return css;})();const mediaPreview='body[data-villa-theme] .destination-label,body[data-villa-theme] .destination-label :is(h1,h2,h3,h4,h5,h6,p,span,strong,small,a),body[data-villa-theme] .style-card,body[data-villa-theme] .style-card :is(h1,h2,h3,h4,h5,h6,p,span,strong,small,a),body[data-villa-theme] .media-overlay,body[data-villa-theme] .media-overlay :is(h1,h2,h3,h4,h5,h6,p,span,strong,small,a),body[data-villa-theme] [data-text-on-media],body[data-villa-theme] [data-text-on-media] :is(h1,h2,h3,h4,h5,h6,p,span,strong,small,a){color:var(--villa-image_overlay_text)!important}body[data-villa-theme] .destination-label small,body[data-villa-theme] .media-overlay p,body[data-villa-theme] [data-text-on-media] p{color:var(--villa-image_overlay_muted)!important}';style.textContent=paletteRules+'\\n'+typographyPreview+'\\n'+mediaPreview+'\\n'+heroPreview;}frame.addEventListener('load',update);form.addEventListener('input',update);update();})();

(function(){
 const input=document.getElementById('footerLogoUpload');
 const preview=document.querySelector('[data-footer-logo-preview]');
 const status=document.querySelector('[data-footer-logo-status]');
 if(!input||!preview||!status)return;
 let currentUrl='';
 input.addEventListener('change',function(){
  const file=input.files&&input.files[0];
  if(!file)return;
  const allowed=['image/png','image/jpeg','image/webp'];
  if(!allowed.includes(file.type)||file.size>4*1024*1024){
   input.value='';
   status.textContent=file.size>4*1024*1024?'Dosya 4 MB sınırını aşıyor.':<?= json_encode('Select a PNG, JPG or WebP image.',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
   return;
  }
  if(currentUrl)URL.revokeObjectURL(currentUrl);
  currentUrl=URL.createObjectURL(file);
  preview.innerHTML='';
  const wrap=document.createElement('div');
  wrap.className='footer-logo-preview-image';
  const image=document.createElement('img');
  image.src=currentUrl;
  image.alt=<?= json_encode('Selected footer logo preview',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
  const caption=document.createElement('span');
  caption.className='footer-logo-preview-caption';
  caption.textContent=<?= json_encode('New logo preview',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
  wrap.append(image,caption);
  preview.appendChild(wrap);
  status.textContent=file.name+' · '+(file.size/1024).toFixed(0)+<?= json_encode(' KB · Uploads when you save.',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
 });
})();

(function(){
 const form=document.getElementById('themeDesignForm');
 if(!form)return;
 form.querySelectorAll('[data-footer-rich-editor]').forEach(function(editor){
  const wrap=editor.closest('.footer-rich-editor-wrap');
  const hidden=document.getElementById(editor.dataset.footerHidden||'');
  if(!wrap||!hidden)return;
  function sync(){hidden.value=editor.innerHTML;}
  const colorInput=wrap.querySelector('[data-footer-editor-color]');
  const colorButton=wrap.querySelector('[data-footer-editor-apply-color]');
  let savedRange=null;
  function rememberSelection(){
   const selection=window.getSelection();
   if(selection&&selection.rangeCount&&editor.contains(selection.anchorNode)&&editor.contains(selection.focusNode))savedRange=selection.getRangeAt(0).cloneRange();
  }
  editor.addEventListener('mouseup',rememberSelection);
  editor.addEventListener('keyup',rememberSelection);
  if(colorButton)colorButton.addEventListener('mousedown',function(event){event.preventDefault();});
  if(colorButton)colorButton.addEventListener('click',function(){
   const color=colorInput&&/^#[0-9a-f]{6}$/i.test(colorInput.value)?colorInput.value:'#ffffff';
   editor.focus();
   const selection=window.getSelection();
   if(!savedRange||!editor.contains(savedRange.commonAncestorContainer)){window.alert(<?= json_encode('Select the text to recolor first.',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>);return;}
   selection.removeAllRanges();selection.addRange(savedRange);
   if(selection.isCollapsed){window.alert(<?= json_encode('Select the text to recolor first.',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>);return;}
   const range=selection.getRangeAt(0),span=document.createElement('span');
   span.style.color=color;span.appendChild(range.extractContents());range.insertNode(span);
   selection.removeAllRanges();
   const nextRange=document.createRange();nextRange.selectNodeContents(span);selection.addRange(nextRange);savedRange=nextRange.cloneRange();
   sync();
  });
  const linkButton=wrap.querySelector('[data-footer-editor-link]');
  if(linkButton)linkButton.addEventListener('mousedown',function(event){event.preventDefault();});
  if(linkButton)linkButton.addEventListener('click',function(){
   editor.focus();
   const selection=window.getSelection();
   if(!selection||selection.isCollapsed){window.alert(<?= json_encode('Select the text to link first.',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>);return;}
   const url=window.prompt('Bağlantı adresini girin (https://, mailto: veya tel:):','https://');
   if(url===null)return;
   const value=url.trim();
   if(!/^(https?:|mailto:|tel:|\/|#)/i.test(value)){window.alert('Lütfen geçerli bir https://, http://, mailto:, tel: veya site içi bağlantı girin.');return;}
   document.execCommand('createLink',false,value);
   sync();
  });
  wrap.querySelectorAll('[data-footer-editor-command]').forEach(function(button){
   button.addEventListener('mousedown',function(event){event.preventDefault();});
   button.addEventListener('click',function(){
    editor.focus();
    document.execCommand(button.dataset.footerEditorCommand,false,null);
    sync();
   });
  });
  editor.addEventListener('input',sync);
  form.addEventListener('submit',sync);
  sync();
 });
})();;
</script>
<script>
(function(){
 document.querySelectorAll('[data-theme-file-uploader]').forEach(function(uploader){
  const input=uploader.querySelector('.theme-file-input');
  const preview=uploader.querySelector('[data-theme-file-preview]');
  const status=uploader.querySelector('[data-theme-file-status]');
  if(!input||!preview||!status||input.dataset.themePreviewReady==='1')return;
  input.dataset.themePreviewReady='1';
  let objectUrl='';
  input.addEventListener('change',function(){
   if(objectUrl){URL.revokeObjectURL(objectUrl);objectUrl='';}
   preview.replaceChildren();
   const file=input.files&&input.files[0];
   if(!file){preview.hidden=true;status.textContent=<?= json_encode('No file selected.',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;return;}
   const maxSize=parseInt(input.dataset.themeFileMax||'0',10);
   if(maxSize>0&&file.size>maxSize){
    input.value='';preview.hidden=true;status.textContent='Dosya boyutu 4 MB sınırını aşıyor.';return;
   }
   const kind=input.dataset.themeFileKind||'';
   const mime=(file.type||'').toLowerCase();
   if(kind==='image'&&mime.indexOf('image/')!==0){input.value='';preview.hidden=true;status.textContent='Lütfen geçerli bir görsel dosyası seçin.';return;}
   if(kind==='video'&&mime.indexOf('video/')!==0){input.value='';preview.hidden=true;status.textContent='Lütfen MP4 veya WebM video dosyası seçin.';return;}
   objectUrl=URL.createObjectURL(file);
   const media=document.createElement(kind==='video'?'video':'img');
   media.src=objectUrl;
   if(kind==='video'){media.controls=true;media.muted=true;media.playsInline=true;media.preload='metadata';}
   else media.alt=<?= json_encode('Selected header logo preview',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
   preview.appendChild(media);
   const copy=document.createElement('div');
   copy.className='theme-file-preview-copy';
   const name=document.createElement('strong');
   name.textContent=file.name;
   const details=document.createElement('span');
   details.textContent=(file.size/1024/1024).toFixed(2)+' MB · '+(file.type||<?= json_encode('Could not identify the file type',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>);
   copy.append(name,details);
   preview.appendChild(copy);
   preview.hidden=false;
   status.textContent=<?= json_encode('File ready. Click upload and apply.',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
  });
 });
})();
</script>
</body></html>
