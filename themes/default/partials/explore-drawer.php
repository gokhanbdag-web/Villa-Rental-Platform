<?php
declare(strict_types=1);
$exploreCategories = is_array($categories ?? null) ? array_slice($categories, 0, 6) : [];
$exploreImage = static function (array $row): string {
    $value = trim((string)($row['cover_image'] ?? ''));
    if ($value === '') return '';
    return preg_match('~^https?://~i', $value) ? $value : app_url('/' . ltrim($value, '/'));
};
$exploreMode = (string)($mode ?? $appMode ?? '');
?>
<div class="villa-explore-backdrop" id="villaExploreBackdrop" hidden></div>
<aside class="villa-explore-drawer" id="villaExploreDrawer" role="dialog" aria-modal="true" aria-labelledby="villaExploreTitle" aria-hidden="true" hidden>
  <div class="villa-explore-head"><div><span class="villa-explore-kicker">VILLACMS · KEŞFET</span><h2 id="villaExploreTitle">Tatilinizi keşfedin</h2><p>Hayalinizdeki konaklamaya giden yolu bulun.</p></div><button type="button" class="villa-explore-close" id="villaExploreClose" aria-label="Keşfet menüsünü kapat">×</button></div>
  <div class="villa-explore-feature-grid">
    <a class="villa-explore-feature villa-explore-feature--destinations" href="<?= e(app_url('/destinasyonlar')) ?>"><span class="villa-explore-feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20.5 3.5 14 20l-3.5-7-7-3.5 17-6Z"/><path d="m10.5 13 4-4"/></svg></span><span><strong>Destinasyonlar</strong><small>Yeni tatil rotaları keşfedin</small></span><span class="villa-explore-arrow" aria-hidden="true">↗</span></a>
    <a class="villa-explore-feature villa-explore-feature--stays" href="<?= e(app_url('/konaklamalar')) ?>"><span class="villa-explore-feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-9Z"/></svg></span><span><strong>Tüm Villalar</strong><small>Size uygun villayı bulun</small></span><span class="villa-explore-arrow" aria-hidden="true">↗</span></a>
  </div>
  <?php if ($exploreCategories !== []): ?>
  <section class="villa-explore-section" aria-labelledby="villaExploreCategoriesTitle">
    <div class="villa-explore-section-heading"><div><span>TERCİHİNİZE GÖRE</span><h3 id="villaExploreCategoriesTitle">Villa konseptleri</h3></div><a href="<?= e(app_url('/konaklamalar')) ?>">Tümünü gör <span aria-hidden="true">→</span></a></div>
    <div class="villa-explore-categories">
      <?php foreach ($exploreCategories as $category): $categoryId=(int)($category['id']??0); if($categoryId<1)continue; $categoryImage=$exploreImage($category); ?>
      <a class="villa-explore-category" href="<?= e(app_url('/konaklamalar?category='.$categoryId)) ?>">
        <?php if($categoryImage!==''): ?><img src="<?= e($categoryImage) ?>" alt="" loading="lazy" decoding="async"><?php else: ?><span class="villa-explore-category-placeholder" aria-hidden="true">⌂</span><?php endif; ?>
        <span class="villa-explore-category-copy"><strong><?= e((string)($category['name']??'Villa')) ?></strong><small><?= (int)($category['property_count']??0) ?> konaklama</small></span><span class="villa-explore-category-arrow" aria-hidden="true">›</span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
  <div class="villa-explore-bottom"><div class="villa-explore-note"><span class="villa-explore-note-icon" aria-hidden="true">✦</span><div><strong>Bir sonraki tatiliniz burada başlıyor.</strong><p>Size ilham verecek konaklamaları ve rotaları keşfedin.</p></div></div>
  <?php if($exploreMode==='multi_vendor'): ?><a class="villa-explore-host-link" href="<?= e(app_url('/vendor/register')) ?>">Villa sahibi misiniz? <span>İlanınızı yayınlayın ↗</span></a><?php endif; ?>
  </div>
</aside>
<style>
.villa-explore-backdrop{position:fixed;inset:0;z-index:2147481000;background:rgba(12,25,19,.43);backdrop-filter:blur(3px);opacity:0;visibility:hidden;transition:opacity .28s ease,visibility .28s ease}
.villa-explore-backdrop[hidden],.villa-explore-drawer[hidden]{display:none!important}.villa-explore-backdrop.is-open{opacity:1;visibility:visible}
.villa-explore-drawer{position:fixed;z-index:2147481001;top:0;right:0;bottom:0;box-sizing:border-box;display:flex;flex-direction:column;width:min(460px,100vw);height:100dvh;max-height:100dvh;overflow-y:auto;padding:34px 30px calc(26px + env(safe-area-inset-bottom,0px));background:#fff;border-left:1px solid #e8eee8;box-shadow:-24px 0 70px rgba(10,24,18,.17);transform:translateX(105%);visibility:hidden;transition:transform .38s cubic-bezier(.22,.75,.25,1),visibility .38s ease}
.villa-explore-drawer.is-open{transform:translateX(0);visibility:visible}
.villa-explore-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:8px 0 24px;border-bottom:1px solid #edf1ed}.villa-explore-kicker{display:block;margin-bottom:9px;color:#7b8b80;font-size:10px;font-weight:800;letter-spacing:.17em}
.villa-explore-head h2{margin:0;color:#1d3328;font-size:27px;line-height:1.2;letter-spacing:-.045em}.villa-explore-head p{margin:7px 0 0;color:#78847d;font-size:13px;line-height:1.5}
.villa-explore-close{flex:0 0 40px;width:40px;height:40px;border:1px solid #e7eee8;border-radius:50%;background:#f7faf7;color:#263b30;font-size:25px;line-height:1;cursor:pointer}
.villa-explore-feature-grid{display:grid;grid-template-columns:1fr 1fr;gap:11px;margin:23px 0 27px}.villa-explore-feature{position:relative;display:flex;flex-direction:column;align-items:flex-start;gap:18px;min-height:145px;padding:17px;border:1px solid #e7eee7;border-radius:16px;background:linear-gradient(145deg,#f5f8f2,#fff);color:#263b30;text-decoration:none;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
.villa-explore-feature:hover{transform:translateY(-3px);border-color:#cbdccc;box-shadow:0 12px 26px rgba(25,65,42,.08)}.villa-explore-feature--stays{background:linear-gradient(145deg,#f2f6f5,#fff)}
.villa-explore-feature-icon{display:grid;place-items:center;width:39px;height:39px;border-radius:12px;background:#e7f0e7;color:#286344}.villa-explore-feature--stays .villa-explore-feature-icon{background:#e8f0ef;color:#315f5b}
.villa-explore-feature-icon svg{width:21px;height:21px;fill:none;stroke:currentColor;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round}.villa-explore-feature strong,.villa-explore-feature small{display:block}.villa-explore-feature strong{font-size:14px;font-weight:750}.villa-explore-feature small{margin-top:5px;color:#7b8980;font-size:11px;line-height:1.4}.villa-explore-arrow{position:absolute;top:17px;right:16px;color:#91a095;font-size:17px}
.villa-explore-section-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:13px}.villa-explore-section-heading>div>span{color:#8a978e;font-size:9px;font-weight:800;letter-spacing:.14em}.villa-explore-section-heading h3{margin:5px 0 0;color:#20372b;font-size:19px;letter-spacing:-.03em}.villa-explore-section-heading>a{color:#477257;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap}
.villa-explore-categories{display:grid;grid-template-columns:1fr 1fr;gap:9px}.villa-explore-category{display:flex;align-items:center;gap:10px;min-width:0;padding:8px;border:1px solid #edf1ed;border-radius:12px;color:#304238;text-decoration:none;transition:background .18s ease,border-color .18s ease}.villa-explore-category:hover{background:#f6f9f5;border-color:#dce8dc}
.villa-explore-category>img,.villa-explore-category-placeholder{flex:0 0 48px;width:48px;height:46px;border-radius:9px;object-fit:cover;background:#edf3ec}.villa-explore-category-placeholder{display:grid;place-items:center;color:#64816b;font-size:22px}.villa-explore-category-copy{display:block;min-width:0;flex:1}.villa-explore-category-copy strong{display:block;overflow:hidden;color:#304238;font-size:11px;font-weight:750;text-overflow:ellipsis;white-space:nowrap}.villa-explore-category-copy small{display:block;margin-top:4px;color:#8a968e;font-size:10px}.villa-explore-category-arrow{color:#9aa69d;font-size:20px}
.villa-explore-bottom{margin-top:auto;padding-top:24px}.villa-explore-note{display:flex;align-items:center;gap:13px;padding:16px;border:1px solid #e8eee6;border-radius:14px;background:#f7f9f4}.villa-explore-note-icon{display:grid;place-items:center;flex:0 0 35px;width:35px;height:35px;border-radius:50%;background:#e8efe3;color:#56744f;font-size:17px}.villa-explore-note strong{display:block;color:#304238;font-size:12px}.villa-explore-note p{margin:5px 0 0;color:#7d8981;font-size:11px;line-height:1.5}.villa-explore-host-link{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:12px;padding:12px 2px;color:#78847d;font-size:11px;text-decoration:none}.villa-explore-host-link span{color:#286344;font-weight:750}
body.villa-explore-drawer-open{overflow:hidden!important}.site-header-auth{gap:9px}.site-header-auth .villa-explore-trigger{padding:9px!important}
@media(max-width:760px){.villa-explore-trigger,.villa-explore-backdrop,.villa-explore-drawer{display:none!important}}
@media(prefers-reduced-motion:reduce){.villa-explore-drawer,.villa-explore-backdrop,.villa-explore-feature,.villa-explore-category{transition:none}}
</style>
<script>
(function(){
 var drawer=document.getElementById('villaExploreDrawer'),backdrop=document.getElementById('villaExploreBackdrop'),closeButton=document.getElementById('villaExploreClose');
 if(!drawer||!backdrop||!closeButton||drawer.dataset.bound==='1')return;
 drawer.dataset.bound='1';var previousFocus=null,closeTimer=null;
 function openDrawer(trigger){
  if(!drawer.hidden){window.clearTimeout(closeTimer);drawer.classList.add('is-open');backdrop.classList.add('is-open');return;}
  previousFocus=trigger||document.activeElement;drawer.hidden=false;backdrop.hidden=false;drawer.setAttribute('aria-hidden','false');document.body.classList.add('villa-explore-drawer-open');
  document.querySelectorAll('[data-explore-drawer-trigger]').forEach(function(el){el.setAttribute('aria-expanded','true');});
  window.requestAnimationFrame(function(){backdrop.classList.add('is-open');drawer.classList.add('is-open');});window.setTimeout(function(){closeButton.focus();},80);
 }
 function closeDrawer(){
  if(drawer.hidden)return;drawer.classList.remove('is-open');backdrop.classList.remove('is-open');drawer.setAttribute('aria-hidden','true');document.body.classList.remove('villa-explore-drawer-open');
  document.querySelectorAll('[data-explore-drawer-trigger]').forEach(function(el){el.setAttribute('aria-expanded','false');});
  window.clearTimeout(closeTimer);closeTimer=window.setTimeout(function(){drawer.hidden=true;backdrop.hidden=true;if(previousFocus&&typeof previousFocus.focus==='function')previousFocus.focus();},390);
 }
 document.addEventListener('click',function(event){var trigger=event.target.closest('[data-explore-drawer-trigger]');if(trigger){event.preventDefault();openDrawer(trigger);return;}if(event.target===backdrop||event.target.closest('#villaExploreClose'))closeDrawer();});
 document.addEventListener('keydown',function(event){if(drawer.hidden)return;if(event.key==='Escape'){closeDrawer();return;}if(event.key==='Tab'){var focusable=Array.prototype.slice.call(drawer.querySelectorAll('a[href],button:not([disabled])')).filter(function(el){return el.offsetParent!==null;});if(!focusable.length)return;var first=focusable[0],last=focusable[focusable.length-1];if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}}});
})();
</script>
