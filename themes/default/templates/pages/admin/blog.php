<?php declare(strict_types=1); ?>
<style>
.blog-admin{max-width:1320px;margin:auto;color:#26372f}.blog-admin *{box-sizing:border-box}.blog-head{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px}.blog-head h1{font-size:25px;margin:0}.blog-muted{color:#77857d;font-size:12px}.blog-card{background:#fff;border:1px solid #e7ece8;border-radius:15px;padding:18px;margin:14px 0;min-width:0}.blog-btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border:1px solid var(--villa-admin-primary);border-radius:9px;background:var(--villa-admin-primary);color:#fff;text-decoration:none;font:inherit;font-size:12px;font-weight:750;cursor:pointer}.blog-btn.light{background:#fff;color:var(--villa-admin-primary)}.blog-btn.danger{background:#fff;color:#a33;border-color:#e5cccc}.blog-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:15px 0}.blog-stat{padding:16px;border:1px solid #e8eee9;border-radius:13px;background:#fff}.blog-stat strong{display:block;font-size:25px}.blog-stat span{font-size:12px;color:#77857d}.blog-filter,.blog-actions{display:flex;gap:8px;flex-wrap:wrap}.blog-filter input,.blog-filter select,.blog-field input,.blog-field textarea,.blog-field select{width:100%;min-width:0;padding:11px 12px;border:1px solid #dce5df;border-radius:9px;background:#fff;color:#26372f;font:inherit}.blog-filter input{flex:1;min-width:200px}.blog-table{width:100%;border-collapse:collapse}.blog-table th,.blog-table td{text-align:left;padding:12px 9px;border-bottom:1px solid #edf1ee;font-size:12px;vertical-align:middle}.blog-thumb{width:68px;height:50px;object-fit:cover;border-radius:8px;background:#edf1ee}.blog-status{display:inline-flex;padding:5px 9px;border-radius:30px;background:#f0f3f1;font-size:11px;font-weight:750}.blog-status.published{background:#eaf4ed;color:#24633d}.blog-status.draft{background:#f4f1e9;color:#78602c}.blog-layout{display:grid;grid-template-columns:minmax(0,1fr) 315px;gap:15px;align-items:start}.blog-fields{display:grid;grid-template-columns:1fr 1fr;gap:13px}.blog-field{display:grid;gap:7px;font-size:12px;font-weight:750}.blog-field.full{grid-column:1/-1}.blog-field textarea{min-height:105px;resize:vertical}.blog-editor{min-height:410px!important;line-height:1.7;font-family:ui-monospace,monospace;font-size:13px}.blog-checks{display:grid;grid-template-columns:1fr 1fr;gap:8px;max-height:220px;overflow:auto;padding:9px;border:1px solid #e5ebe6;border-radius:9px}.blog-checks label{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600}.blog-checks input{width:auto}.blog-cover-preview{width:100%;max-height:170px;object-fit:cover;border-radius:10px;background:#f1f3f1}.blog-toolbar{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:7px}.blog-toolbar button{border:1px solid #dce5df;background:#fff;border-radius:7px;padding:7px 10px;cursor:pointer;font-weight:700}.blog-savebar{display:flex;justify-content:flex-end;gap:8px;position:sticky;bottom:8px;background:#fff;padding:12px;border:1px solid #e8eee9;border-radius:12px;box-shadow:0 8px 24px #14251c14}.blog-note{padding:11px 13px;border-radius:9px;background:#f6f8f6;color:#66746c;font-size:12px;line-height:1.6}.blog-seo-meter{font-size:11px;color:#7b8980;text-align:right}
@media(max-width:900px){.blog-layout{grid-template-columns:1fr}.blog-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:640px){.blog-card{padding:13px}.blog-fields{grid-template-columns:1fr}.blog-field.full{grid-column:auto}.blog-table,.blog-table tbody,.blog-table tr,.blog-table td{display:block;width:100%}.blog-table thead{display:none}.blog-table tr{padding:12px 0;border-bottom:1px solid #edf1ee}.blog-table td{border:0;padding:4px 0}.blog-checks{grid-template-columns:1fr}}

/* Admin blog: resilient layouts inside the shared admin shell */
.blog-admin,.blog-admin form,.blog-admin section,.blog-admin aside,.blog-admin>div{min-width:0}
.blog-admin{width:100%;max-width:1320px;overflow:visible}
.blog-layout{grid-template-columns:minmax(0,1fr) minmax(260px,315px);width:100%;min-width:0}
.blog-layout>div,.blog-layout>aside{min-width:0;width:100%}
.blog-card{min-width:0;width:100%;overflow-wrap:anywhere}
.blog-fields{grid-template-columns:repeat(2,minmax(0,1fr));min-width:0}
.blog-field{min-width:0;width:100%;overflow-wrap:anywhere}
.blog-field input,.blog-field select,.blog-field textarea,.blog-filter input,.blog-filter select{display:block;width:100%;max-width:100%;min-width:0}
.blog-editor{display:block;width:100%;max-width:100%;min-width:0;min-height:410px;white-space:pre-wrap;overflow-wrap:anywhere}
.blog-toolbar{align-items:center;max-width:100%}
.blog-toolbar button{flex:0 0 auto;max-width:100%}
.blog-actions{align-items:center;min-width:0}
.blog-actions form{display:flex;min-width:0;margin:0}
.blog-savebar{flex-wrap:wrap;align-items:center}
.blog-savebar>*{max-width:100%}
.blog-filter{display:grid;grid-template-columns:minmax(0,1fr) minmax(140px,190px) auto auto;align-items:center;min-width:0}
.blog-filter input{min-width:0}
.blog-table-scroll{width:100%;min-width:0;overflow-x:auto;overscroll-behavior-x:contain;-webkit-overflow-scrolling:touch}
.blog-table{width:100%;min-width:760px}
@media(max-width:1100px){
 .blog-layout{grid-template-columns:minmax(0,1fr)}
 .blog-layout>aside{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:start}
 .blog-layout>aside>.blog-card{margin:0}
 .blog-layout>aside>.blog-savebar{grid-column:1/-1}
}
@media(max-width:760px){
 .blog-head{align-items:flex-start}
 .blog-head>div{min-width:0;max-width:100%}
 .blog-head h1{font-size:22px;line-height:1.25;overflow-wrap:anywhere}
 .blog-head>.blog-actions{width:100%}
 .blog-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
 .blog-stat{min-width:0;padding:12px}
 .blog-stat strong{font-size:22px}
 .blog-fields{grid-template-columns:minmax(0,1fr);gap:12px}
 .blog-field.full{grid-column:1}
 .blog-layout>aside{grid-template-columns:minmax(0,1fr)}
 .blog-layout>aside>.blog-savebar{grid-column:1}
 .blog-filter{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}
 .blog-filter>*{min-width:0;width:100%}
 .blog-filter input{grid-column:1/-1}
 .blog-filter .blog-btn{white-space:normal}
 .blog-card{padding:14px;margin:10px 0;border-radius:12px}
 .blog-toolbar{gap:6px}
 .blog-toolbar button{padding:8px 9px;font-size:12px}
 .blog-editor{min-height:300px!important}
 .blog-savebar{position:static;justify-content:stretch;box-shadow:none}
 .blog-savebar .blog-btn{flex:1 1 120px}
 .blog-table{display:table!important;width:100%!important;min-width:760px!important}
 .blog-table thead{display:table-header-group!important;position:static!important;width:auto!important;height:auto!important;margin:0!important;overflow:visible!important;clip:auto!important;white-space:normal!important}
 .blog-table tbody{display:table-row-group!important;width:auto!important}
 .blog-table tr{display:table-row!important;width:auto!important;margin:0!important;padding:0!important;border:0!important;background:transparent!important;box-shadow:none!important}
 .blog-table th,.blog-table td{display:table-cell!important;width:auto!important;padding:9px 8px!important;white-space:normal;vertical-align:top}
 .blog-table td:before{content:none!important;display:none!important}
 .blog-actions .blog-btn{padding:8px 10px;font-size:11px}
}
@media(max-width:420px){
 .blog-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
 .blog-stat strong{font-size:20px}
 .blog-head>.blog-actions{display:grid;grid-template-columns:1fr 1fr;width:100%}
 .blog-head>.blog-actions .blog-btn{width:100%;padding:9px 8px;text-align:center}
 .blog-filter{grid-template-columns:minmax(0,1fr)}
 .blog-filter input{grid-column:1}
 .blog-filter>*{width:100%}
 .blog-card{padding:12px}
 .blog-savebar{gap:7px}
}

</style>
<div class="blog-admin">
<?php if(isset($post)): ?>
<div class="blog-head"><div><h1><?= !empty($post['id'])?villa_ui('Yazıyı düzenle'):villa_ui('Yeni blog yazısı') ?></h1><div class="blog-muted"><?= e(villa_ui('İçerik editörü · SEO · Kategoriler · Etiketler')) ?></div></div><a class="blog-btn light" href="<?= e(app_url('/admin/blog')) ?>"><?= e(villa_ui('← Tüm yazılar')) ?></a></div>
<form method="post" action="<?= e(app_url('/admin/blog/save')) ?>" id="blogEditorForm"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)($post['id']??0) ?>">
<div class="blog-layout"><div>
<section class="blog-card"><div class="blog-fields">
<label class="blog-field full"><?= e(villa_ui('Yazı başlığı')) ?><input name="title" maxlength="220" required value="<?= e($post['title']??'') ?>" placeholder="<?= e(villa_ui('Okuyucunun dikkatini çekecek başlık')) ?>"></label>
<label class="blog-field full"><?= e(villa_ui('Kalıcı bağlantı / Slug')) ?><input name="slug" maxlength="190" required value="<?= e($post['slug']??'') ?>"></label>
<label class="blog-field full"><?= e(villa_ui('Kısa açıklama / Özet')) ?><textarea name="excerpt" maxlength="3000" rows="3"><?= e($post['excerpt']??'') ?></textarea></label>
<div class="blog-field full"><span><?= e(villa_ui('Yazı içeriği')) ?> <span class="blog-muted"><?= e(villa_ui('(temel HTML destekli)')) ?></span></span><div class="blog-toolbar"><?php foreach(['h2'=>'H2','h3'=>'H3','p'=>'Paragraf','strong'=>villa_ui('Kalın'),'em'=>villa_ui('İtalik'),'ul'=>'Liste','blockquote'=>villa_ui('Alıntı'),'a'=>'Bağlantı'] as $tag=>$label): ?><button type="button" data-wrap="<?= e($tag) ?>"><?= e($label) ?></button><?php endforeach; ?></div><textarea class="blog-editor" id="blogContent" name="content" required><?= e($post['content']??'') ?></textarea><div class="blog-muted"><?= e(villa_ui('HTML başlık, paragraf, liste, alıntı, bağlantı ve görseller desteklenir. Güvenli olmayan etiketler yayın sırasında temizlenir.')) ?></div></div>
</div></section>
<section class="blog-card"><h3><?= e(villa_ui('SEO ayarları')) ?></h3><div class="blog-fields">
<label class="blog-field full"><?= e(villa_ui('SEO başlığı')) ?><input name="meta_title" maxlength="220" value="<?= e($post['meta_title']??'') ?>" placeholder="<?= e(villa_ui('Boş bırakılırsa yazı başlığı kullanılır')) ?>"><span class="blog-seo-meter" data-count="meta_title"></span></label>
<label class="blog-field full"><?= e(villa_ui('Meta açıklama')) ?><textarea name="meta_description" maxlength="320" rows="3"><?= e($post['meta_description']??'') ?></textarea><span class="blog-seo-meter" data-count="meta_description"></span></label>
<label class="blog-field"><?= e(villa_ui('Odak anahtar kelime')) ?><input name="focus_keyword" maxlength="120" value="<?= e($post['focus_keyword']??'') ?>"></label>
<label class="blog-field"><?= e(villa_ui('Yazar adı')) ?><input name="author_name" maxlength="120" value="<?= e($post['author_name']??villa_ui('Yayın Editörü')) ?>"></label>
</div></section><?php include dirname(__DIR__,2) . '/partials/seo-analyzer.php'; ?></div><aside>
<section class="blog-card"><h3><?= e(villa_ui('Yayınla')) ?></h3><div class="blog-fields">
<label class="blog-field"><?= e(villa_ui('Durum')) ?><select name="status"><option value="draft" <?= ($post['status']??'draft')==='draft'?'selected':'' ?>><?= e(villa_ui('Taslak')) ?></option><option value="published" <?= ($post['status']??'')==='published'?'selected':'' ?>><?= e(villa_ui('Yayında')) ?></option></select></label>
<label class="blog-field"><?= e(villa_ui('Yayın tarihi')) ?><input type="datetime-local" name="published_at" value="<?= !empty($post['published_at'])?e(date('Y-m-d\TH:i',strtotime((string)$post['published_at']))):e(date('Y-m-d\TH:i')) ?>"></label>
<label class="blog-field"><?= e(villa_ui('Okuma süresi (dk)')) ?><input type="number" min="1" max="60" name="reading_time" value="<?= (int)($post['reading_time']??5) ?>"></label>
<label class="blog-field" style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_featured" value="1" style="width:auto" <?= !empty($post['is_featured'])?'checked':'' ?>> <?= e(villa_ui('Öne çıkan yazı')) ?></label></div><p class="blog-note"><?= e(villa_ui('İleri tarihli yazılar yayın saati gelene kadar ziyaretçilere gösterilmez.')) ?></p></section>
<section class="blog-card"><h3><?= e(villa_ui('Kategoriler')) ?></h3><div class="blog-checks"><?php foreach(($categories??[]) as $category): ?><label><input type="checkbox" name="categories[]" value="<?= (int)$category['id'] ?>" <?= in_array((int)$category['id'],$postCategories??[],true)?'checked':'' ?>> <?= e($category['name']) ?></label><?php endforeach; ?></div><p class="blog-muted"><a href="<?= e(app_url('/admin/blog/categories')) ?>"><?= e(villa_ui('Kategorileri düzenle')) ?></a></p><label class="blog-field"><?= e(villa_ui('Etiketler')) ?> <span class="blog-muted"><?= e(villa_ui('Virgülle ayırın')) ?></span><input name="tags" value="<?= e(implode(', ',$postTags??[])) ?>" placeholder="Kaş, özel havuz, aile tatili"></label></section>
<section class="blog-card"><h3><?= e(villa_ui('Öne çıkan görsel')) ?></h3><label class="blog-field"><?= e(villa_ui('Görsel URL')) ?><input id="blogImage" name="featured_image" maxlength="500" value="<?= e($post['featured_image']??'') ?>" placeholder="https://... veya /uploads/..."></label><img class="blog-cover-preview" id="blogCoverPreview" src="<?= e($post['featured_image']??'') ?>" alt="<?= e(villa_ui('Görsel önizleme')) ?>" <?= empty($post['featured_image'])?'style="display:none"':'' ?>><label class="blog-field" style="margin-top:12px"><?= e(villa_ui('Görsel alt metni')) ?><input name="featured_image_alt" maxlength="255" value="<?= e($post['featured_image_alt']??'') ?>"></label></section>
<div class="blog-savebar"><a class="blog-btn light" href="<?= e(app_url('/admin/blog')) ?>"><?= e(villa_ui('İptal')) ?></a><button class="blog-btn" type="submit"><?= e(villa_ui('Yazıyı kaydet')) ?></button></div></aside></div></form>
<script>(function(){var f=document.getElementById('blogEditorForm'),ed=document.getElementById('blogContent'),im=document.getElementById('blogImage'),pv=document.getElementById('blogCoverPreview');if(!f)return;f.querySelectorAll('[data-wrap]').forEach(function(b){b.addEventListener('click',function(){var t=b.dataset.wrap,s=ed.selectionStart,e=ed.selectionEnd,v=ed.value.slice(s,e)||<?= json_encode(villa_ui('Metninizi buraya yazın'),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,h=t==='ul'?'<ul>\n<li>'+v+'</li>\n</ul>':t==='a'?'<a href="https://">'+v+'</a>':'<'+t+'>'+v+'</'+t+'>';ed.setRangeText(h,s,e,'select');ed.focus();});});im.addEventListener('input',function(){pv.src=im.value;pv.style.display=im.value?'block':'none';});pv.addEventListener('error',function(){pv.style.display='none';});f.querySelectorAll('[data-count]').forEach(function(c){var x=f.elements[c.dataset.count];function u(){c.textContent=x.value.length+' karakter';}x.addEventListener('input',u);u();});var ti=f.elements.title,sl=f.elements.slug;ti.addEventListener('input',function(){if(sl.dataset.edited)return;sl.value=ti.value.toLocaleLowerCase('tr').replace(/ı/g,'i').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');});sl.addEventListener('input',function(){sl.dataset.edited='1';});})();</script>
<?php else: ?>
<div class="blog-head"><div><h1><?= e(villa_ui('Blog yönetimi')) ?></h1><div class="blog-muted"><?= e(villa_ui('Yazılar, kategoriler, etiketler ve SEO görünürlüğü')) ?></div></div><div class="blog-actions"><a class="blog-btn light" href="<?= e(app_url('/admin/blog/categories')) ?>"><?= e(villa_ui('Kategoriler')) ?></a><a class="blog-btn" href="<?= e(app_url('/admin/blog/new')) ?>"><?= e(villa_ui('+ Yeni yazı')) ?></a></div></div>
<div class="blog-stats"><div class="blog-stat"><strong><?= (int)($stats['total']??0) ?></strong><span><?= e(villa_ui('Toplam yazı')) ?></span></div><div class="blog-stat"><strong><?= (int)($stats['published']??0) ?></strong><span><?= e(villa_ui('Yayında')) ?></span></div><div class="blog-stat"><strong><?= (int)($stats['drafts']??0) ?></strong><span><?= e(villa_ui('Taslak')) ?></span></div><div class="blog-stat"><strong><?= (int)($stats['featured']??0) ?></strong><span><?= e(villa_ui('Öne çıkan')) ?></span></div></div>
<section class="blog-card"><form class="blog-filter" method="get" action="<?= e(app_url('/admin/blog')) ?>"><input name="q" value="<?= e($q??'') ?>" placeholder="<?= e(villa_ui('Başlık veya URL içinde ara')) ?>"><select name="status"><option value=""><?= e(villa_ui('Tüm durumlar')) ?></option><option value="published" <?= ($status??'')==='published'?'selected':'' ?>><?= e(villa_ui('Yayında')) ?></option><option value="draft" <?= ($status??'')==='draft'?'selected':'' ?>><?= e(villa_ui('Taslak')) ?></option></select><button class="blog-btn"><?= e(villa_ui('Filtrele')) ?></button><a class="blog-btn light" href="<?= e(app_url('/admin/blog')) ?>"><?= e(villa_ui('Temizle')) ?></a></form></section>
<section class="blog-card"><div class="blog-table-scroll"><table class="blog-table"><thead><tr><th><?= e(villa_ui('Görsel')) ?></th><th><?= e(villa_ui('Yazı')) ?></th><th><?= e(villa_ui('Yayın tarihi')) ?></th><th><?= e(villa_ui('Görüntülenme')) ?></th><th><?= e(villa_ui('Durum')) ?></th><th><?= e(villa_ui('İşlemler')) ?></th></tr></thead><tbody><?php foreach(($posts??[]) as $item): ?><tr><td><?php if(!empty($item['featured_image'])): ?><img class="blog-thumb" src="<?= e($item['featured_image']) ?>" alt="<?= e($item['featured_image_alt']??'') ?>" loading="lazy"><?php else: ?><span class="blog-thumb" style="display:inline-block"></span><?php endif; ?></td><td><strong><?= e($item['title']) ?></strong><div class="blog-muted">/blog/<?= e($item['slug']) ?><?= !empty($item['is_featured'])?villa_ui(' · Öne çıkan'):'' ?></div></td><td><?= e((string)($item['published_at']??'—')) ?></td><td><?= number_format((int)($item['views_count']??0),0,',','.') ?></td><td><span class="blog-status <?= e($item['status']) ?>"><?= $item['status']==='published'?villa_ui('Yayında'):villa_ui('Taslak') ?></span></td><td><div class="blog-actions"><a class="blog-btn light" href="<?= e(app_url('/admin/blog/'.(int)$item['id'])) ?>"><?= e(villa_ui('Düzenle')) ?></a><?php if($item['status']==='published'): ?><a class="blog-btn light" target="_blank" rel="noopener" href="<?= e(app_url('/blog/'.$item['slug'])) ?>"><?= e(villa_ui('Görüntüle')) ?></a><?php endif; ?><form method="post" action="<?= e(app_url('/admin/blog/delete')) ?>" onsubmit="return confirm(<?= e(json_encode(villa_ui('Bu yazı silinsin mi?'))) ?>)"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="blog-btn danger"><?= e(villa_ui('Sil')) ?></button></form></div></td></tr><?php endforeach; ?><?php if(empty($posts)): ?><tr><td colspan="6"><?= e(villa_ui('Blog yazısı bulunamadı.')) ?></td></tr><?php endif; ?></tbody></table></div></section>
<?php endif; ?></div>