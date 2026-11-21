<?php
declare(strict_types=1);
use App\Core\HomepageDesign;

$defaults=[
    'home_eyebrow'=>'MAKE YOUR HOLIDAY SPECIAL',
    'home_title'=>'Your dream holiday awaits',
    'home_subtitle'=>'Discover beautiful holiday villas.',
    'home_mobile_search_button'=>'Find villas',
    'home_properties_link'=>'All properties',
    'home_latest_eyebrow'=>'NEW ARRIVALS',
    'home_latest_title'=>'Latest villas',
    'home_latest_link'=>'View all villas ↗',
    'home_concepts_eyebrow'=>'EXPLORE YOUR HOLIDAY',
    'home_concepts_title'=>'Find your holiday style',
    'home_host_cta'=>'Become a host',
    'home_host_banner_image'=>'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1800&q=85',
    'home_reviews_eyebrow'=>'GUEST EXPERIENCES',
    'home_reviews_title'=>'Guest experiences',
    'home_stats_eyebrow'=>'BY THE NUMBERS',
    'home_stats_title'=>'Book with confidence',
    'home_stats_subtitle'=>'Explore our villas, destinations and guest experiences.',
    'home_regions_eyebrow'=>'START EXPLORING',
    'home_destinations_link'=>'All destinations',
];
$allowedFields=array_merge(...array_values(array_map('array_keys',HomepageDesign::fields())));
$defaults=array_replace($defaults,array_intersect_key($content,array_flip($allowedFields)));
$defaults['hero_image']=($brand['hero_background_image_url']??'') ?: ($homeSections['hero']['image_url']??'');
$defaults['featured_title']=$homeSections['featured']['title']??'Featured places to stay';
$defaults['featured_subtitle']=$homeSections['featured']['subtitle']??'Unique holiday homes worth discovering.';
foreach (['title','eyebrow','description'] as $field) $defaults['banner_'.$field]=$banner[$field]??'';
$defaults['banner_image']=$brand['family_banner_url']??'';
$defaults['home_host_title']=$content['home_host_title']??$homeSections['hosts']['title']??'Share your villa with travelers';
$defaults['home_host_subtitle']=$content['home_host_subtitle']??$homeSections['hosts']['subtitle']??'List your property, manage availability and welcome new guests.';
$defaults['home_regions_title']=$content['home_regions_title']??$homeSections['destinations']['title']??'Popular destinations';
$defaults['home_regions_subtitle']=$content['home_regions_subtitle']??$homeSections['destinations']['subtitle']??'Find your perfect stay in beautiful holiday destinations.';
$fields=HomepageDesign::fields();
$sections=HomepageDesign::SECTIONS;
$orders=[];
foreach ($sections as $key=>[$label,$order]) {
    $orders[$key]=$design[$key]['order']??(in_array($key,['banner','destinations'],true)?$order:($homeSections[$key]['sort_order']??$order));
}
uksort($sections,static fn($a,$b)=>$orders[$a]<=>$orders[$b]);
?>
<!doctype html><html lang="<?= e(\App\Core\Translator::locale()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(villa_ui('Homepage design')) ?></title>
<style>
.home-editor{padding:1rem;color:#24332a;font:1rem system-ui}.home-editor .workspace{display:grid;grid-template-columns:minmax(17rem,24rem) 1fr;gap:1rem}.home-editor details{border:1px solid #ccd8cd;padding:.75rem;border-radius:.5rem;margin:.5rem 0}.home-editor summary{cursor:pointer;font-weight:600}.home-editor label{display:block;margin:.6rem 0}.home-editor input:not([type=checkbox]),.home-editor textarea{display:block;width:100%;box-sizing:border-box;font:inherit;padding:.5rem}.home-editor iframe{position:sticky;top:1rem;width:100%;height:76vh;border:1px solid #ccd8cd;background:white}.home-editor button{padding:.55rem .85rem;cursor:pointer}.home-editor .toolbar{display:flex;gap:.5rem;flex-wrap:wrap;margin:1rem 0}@media(max-width:800px){.home-editor .workspace{grid-template-columns:1fr}.home-editor iframe{position:static;height:65vh}}
</style></head><body><main class="home-editor">
<h1><?= e(villa_ui('Homepage design ·')) ?> <?= e($locale) ?></h1>
<?php if(isset($_GET['saved'])): ?><p role="status"><?= e(villa_ui('Homepage layout saved.')) ?></p><?php endif; ?>
<form method="post" action="<?= e(app_url('/admin/pages/home-design')) ?>" id="home-design">
<?= csrf_field() ?><input type="hidden" name="language_code" value="<?= e($locale) ?>"><input type="hidden" name="design_key" value="<?= e($designKey) ?>"><input type="hidden" name="design" id="home-data">
<div class="toolbar"><button type="submit"><?= e(villa_ui('Save homepage')) ?></button><button type="submit" id="home-preview" formaction="<?= e(app_url('/homepage-design-preview')) ?>" formtarget="homepage-preview"><?= e(villa_ui('Update preview')) ?></button><a href="<?= e(app_url('/admin/pages?lang='.rawurlencode($locale))) ?>"><?= e(villa_ui('Back to pages')) ?></a></div>
<p><?= e(villa_ui('Select a section in the preview to edit its fields.')) ?></p><div class="workspace"><div>
<?php foreach($sections as $key=>[$label,$order]):
    $baseSection=in_array($key,['hero','featured','hosts','destinations'],true);
    $visible=($key==='featured'||$key==='banner'||(bool)($homeSections[$key]['is_active']??true)) && (!$baseSection || ($demo && empty($genericLayout['rows'])));
    $settings=$design[$key]??[]; ?>
<details data-section="<?= e($key) ?>"><summary><?= e(villa_ui($label)) ?></summary>
<label><input type="checkbox" data-field="visible" data-default="<?= $visible?1:0 ?>" <?= ($settings['visible']??$visible)?'checked':'' ?>> <?= e(villa_ui('Visible')) ?></label>
<label><?= e(villa_ui('Order')) ?> <input type="number" min="0" max="2147483647" required data-field="order" data-default="<?= (int)(in_array($key,['banner','destinations'],true)?$order:($homeSections[$key]['sort_order']??$order)) ?>" value="<?= (int)$orders[$key] ?>"></label>
<?php foreach($fields[$key] as $field=>$fieldLabel): ?><label><?= e(villa_ui($fieldLabel)) ?><textarea rows="2" data-field="<?= e($field) ?>"><?= e($settings[$field]??$defaults[$field]??'') ?></textarea></label><?php endforeach; ?>
<button type="button" data-move="-1"><?= e(villa_ui('Move up')) ?></button> <button type="button" data-move="1"><?= e(villa_ui('Move down')) ?></button> <button type="button" data-reset><?= e(villa_ui('Restore theme defaults')) ?></button></details>
<?php endforeach; ?></div><iframe name="homepage-preview" title="<?= e(villa_ui('Homepage preview')) ?>" sandbox="allow-scripts"></iframe></div></form></main>
<script>
(() => {
    const form=document.getElementById('home-design');
    const state=<?= json_encode((object)$design,JSON_HEX_TAG|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_HEX_APOS) ?>;
    const defaults=<?= json_encode((object)$defaults,JSON_HEX_TAG|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_HEX_APOS|JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    for (const section of form.querySelectorAll('[data-section]')) {
        const key=section.dataset.section;
        for (const field of section.querySelectorAll('[data-field]')) {
            field.addEventListener('input', () => {
                const value=field.type==='checkbox'?field.checked:field.type==='number'?field.valueAsNumber:field.value;
                (state[key] ??= {})[field.dataset.field]=value;
            });
        }
        section.querySelector('[data-reset]').addEventListener('click', () => {
            delete state[key];
            for (const field of section.querySelectorAll('[data-field]')) {
                if (field.type==='checkbox') field.checked=field.dataset.default==='1';
                else field.value=field.type==='number'?field.dataset.default:(defaults[field.dataset.field]??'');
            }
        });
    }
    const panels=Array.from(form.querySelectorAll('[data-section]'));
    const previewFrame=form.querySelector('iframe');
    window.addEventListener('message',event=>{
        if (event.source!==previewFrame.contentWindow) return;
        const panel=panels.find(item=>item.dataset.section===event.data?.homepageSection);
        if (panel) {
            panel.open=true;
            panel.querySelector('textarea')?.focus();
        }
    });
    for (const panel of panels) {
        for (const button of panel.querySelectorAll('[data-move]')) {
            button.addEventListener('click',()=>{
                const ordered=Array.from(panel.parentElement.querySelectorAll('[data-section]'));
                ordered.sort((a,b)=>a.querySelector('[data-field=order]').valueAsNumber-b.querySelector('[data-field=order]').valueAsNumber);
                const index=ordered.indexOf(panel), next=index+Number(button.dataset.move);
                if (next<0 || next>=ordered.length) return;
                [ordered[index],ordered[next]]=[ordered[next],ordered[index]];
                const parent=panel.parentElement;
                ordered.forEach((item,i)=>{
                    const input=item.querySelector('[data-field=order]');
                    input.value=(i+1)*10;
                    input.dispatchEvent(new Event('input'));
                    parent.appendChild(item);
                });
                button.focus();
            });
        }
    }
    form.addEventListener('submit',()=>{ document.getElementById('home-data').value=JSON.stringify(state); });
    form.requestSubmit(document.getElementById('home-preview'));
})();
</script></body></html>
