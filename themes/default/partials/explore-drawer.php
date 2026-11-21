<?php
declare(strict_types=1);

$exploreContent = is_array($content ?? null) ? $content : [];
$exploreText = static function (string $key, string $fallback = '') use ($exploreContent): string {
    $value = trim((string)($exploreContent[$key] ?? ''));
    return $value !== '' ? $value : $fallback;
};
$exploreImageUrl = static function (string $key, string $fallback = '') use ($exploreText): string {
    $value = $exploreText($key, $fallback);
    if ($value === '') return '';
    if (preg_match('~^https?://~i', $value)) {
        return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
    }
    if (str_starts_with($value, '//') || preg_match('~(^|/)\.\.(/|$)~', $value)) return '';
    return app_url('/' . ltrim($value, '/'));
};
$exploreLink = static function (string $key, string $fallback) use ($exploreText): string {
    $value = $exploreText($key, $fallback);
    if (str_starts_with($value, '/') && !str_starts_with($value, '//')) return app_url($value);
    if (preg_match('~^https://~i', $value) && filter_var($value, FILTER_VALIDATE_URL)) return $value;
    return app_url($fallback);
};
$exploreCards = [];
for ($i = 1; $i <= 4; $i++) {
    $exploreCards[] = [
        'title' => $exploreText('explore_card_' . $i . '_title', ['Özel havuzlu villalar','Balayı kaçamakları','Deniz manzaralı','Doğayla iç içe'][$i - 1]),
        'text' => $exploreText('explore_card_' . $i . '_text', ['Kendi havuzunuzda, size özel anlar.','İki kişilik unutulmaz bir yolculuk.','Her sabah maviliklere uyanın.','Sakinlik ve dinginlik için özel alanlar.'][$i - 1]),
        'image' => $exploreImageUrl('explore_card_' . $i . '_image', ''),
        'url' => $exploreLink('explore_card_' . $i . '_link', '/konaklamalar'),
    ];
}
$exploreHeroImage = $exploreImageUrl('explore_hero_image', '');
$exploreDestinationImage = $exploreImageUrl('explore_destination_image', '');
$explorePhone = preg_replace('/\\D+/', '', $exploreText('explore_support_phone', $exploreText('home_support_phone')));
$exploreWhatsapp = preg_replace('/\\D+/', '', $exploreText('explore_support_whatsapp', $exploreText('home_support_whatsapp')));
$exploreMode = (string)($mode ?? $appMode ?? '');
?>
<div class="villa-explore-backdrop" id="villaExploreBackdrop" hidden></div>
<aside class="villa-explore-drawer" id="villaExploreDrawer" role="dialog" aria-modal="true" aria-labelledby="villaExploreTitle" aria-hidden="true" hidden>
  <div class="villa-explore-scroll">
    <div class="villa-explore-head">
      <div><span class="villa-explore-kicker"><?= e($exploreText('explore_hero_eyebrow','VILLACMS · TATİLİ KEŞFET')) ?></span><h2 id="villaExploreTitle"><?= e($exploreText('explore_hero_title','Bir sonraki kaçamağınız burada başlıyor.')) ?></h2><p><?= e($exploreText('explore_hero_text','Akdeniz’in en özel köşelerinde, size ait bir tatil deneyimi keşfedin.')) ?></p></div>
      <button type="button" class="villa-explore-close" id="villaExploreClose" aria-label="<?= e(villa_ui('Keşfet menüsünü kapat')) ?>">×</button>
    </div>

    <a class="villa-explore-hero" href="<?= e($exploreLink('explore_hero_link','/konaklamalar')) ?>">
      <?php if ($exploreHeroImage !== ''): ?><img src="<?= e($exploreHeroImage) ?>" alt="" loading="eager" decoding="async"><?php endif; ?>
      <span class="villa-explore-hero-shade"></span>
      <span class="villa-explore-hero-copy" data-villa-overlay-text><span><?= e($exploreText('explore_hero_eyebrow','VILLACMS · TATİLİ KEŞFET')) ?></span><strong><?= e($exploreText('explore_hero_title','Bir sonraki kaçamağınız burada başlıyor.')) ?></strong><em><?= e($exploreText('explore_hero_cta','Villaları keşfet')) ?> <b aria-hidden="true">↗</b></em></span>
    </a>

    <section class="villa-explore-section" aria-labelledby="villaExploreConceptTitle">
      <div class="villa-explore-section-heading"><div><span><?= e(villa_ui('ÖZEL OLARAK SİZİN İÇİN')) ?></span><h3 id="villaExploreConceptTitle"><?= e(villa_ui('Nasıl bir tatil hayal ediyorsunuz?')) ?></h3></div></div>
      <div class="villa-explore-cards">
        <?php foreach ($exploreCards as $card): ?>
        <a class="villa-explore-card" href="<?= e($card['url']) ?>">
          <span class="villa-explore-card-photo"><?php if ($card['image'] !== ''): ?><img src="<?= e($card['image']) ?>" alt="" loading="lazy" decoding="async"><?php else: ?><span class="villa-explore-card-placeholder" aria-hidden="true">✦</span><?php endif; ?><span class="villa-explore-card-shade"></span></span>
          <span class="villa-explore-card-copy"><strong><?= e($card['title']) ?></strong><small><?= e($card['text']) ?></small></span><span class="villa-explore-card-arrow" aria-hidden="true">↗</span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="villa-explore-destination" aria-label="<?= e(villa_ui('Destinasyon keşfi')) ?>">
      <?php if ($exploreDestinationImage !== ''): ?><img src="<?= e($exploreDestinationImage) ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
      <div class="villa-explore-destination-copy" data-villa-overlay-text><span><?= e($exploreText('explore_destination_eyebrow','AKDENİZ’İN GİZLİ GÜZELLİKLERİ')) ?></span><h3><?= e($exploreText('explore_destination_title','Yeni bir rota, bambaşka bir hikâye.')) ?></h3><p><?= e($exploreText('explore_destination_text','Kıyı kasabalarını, gün batımını ve size iyi gelecek tatil duraklarını keşfedin.')) ?></p><a href="<?= e($exploreLink('explore_destination_link','/destinasyonlar')) ?>"><?= e($exploreText('explore_destination_cta','Destinasyonları keşfet')) ?> <b aria-hidden="true">→</b></a></div>
    </section>

    <section class="villa-explore-support" aria-label="<?= e(villa_ui('Yardım ve rezervasyon')) ?>">
      <span class="villa-explore-support-icon" aria-hidden="true">✳</span>
      <div class="villa-explore-support-copy"><span><?= e(villa_ui('YANINIZDAYIZ')) ?></span><strong><?= e($exploreText('explore_support_title','Tatilinizi birlikte planlayalım.')) ?></strong><p><?= e($exploreText('explore_support_text','Aklınızdaki villa veya rezervasyon hakkında ekibimizden destek alın.')) ?></p></div>
      <div class="villa-explore-support-actions">
        <?php if ($explorePhone !== ''): ?><a href="tel:<?= e($explorePhone) ?>" aria-label="<?= e(villa_ui('Telefonla arayın')) ?>">☎</a><?php endif; ?>
        <?php if ($exploreWhatsapp !== ''): ?><a class="villa-explore-whatsapp" href="https://wa.me/<?= e($exploreWhatsapp) ?>" target="_blank" rel="noopener noreferrer"><?= e($exploreText('explore_support_cta','WhatsApp’tan yazın')) ?> <b aria-hidden="true">↗</b></a><?php endif; ?>
      </div>
    </section>
    <?php if ($exploreMode === 'multi_vendor'): ?><a class="villa-explore-host-link" href="<?= e(app_url('/vendor/register')) ?>"><?= e(villa_ui('Villa sahibi misiniz?')) ?> <strong><?= e(villa_ui('İlanınızı yayınlayın ↗')) ?></strong></a><?php endif; ?>
    <div class="villa-explore-footer-note">VILLACMS <span>·</span> <?= e(villa_ui('Bir sonraki güzel anınız için.')) ?></div>
  </div>
</aside>
<style>
.villa-explore-backdrop{position:fixed;inset:0;z-index:2147481000;background:rgba(12,25,19,.48);backdrop-filter:blur(5px);opacity:0;visibility:hidden;transition:opacity .3s ease,visibility .3s ease}
.villa-explore-backdrop[hidden],.villa-explore-drawer[hidden]{display:none!important}.villa-explore-backdrop.is-open{opacity:1;visibility:visible}
.villa-explore-drawer{position:fixed;z-index:2147481001;top:0;right:0;bottom:0;box-sizing:border-box;width:min(540px,100vw);height:100dvh;max-height:100dvh;overflow:hidden;background:#fbfaf6;border-left:1px solid #e8e5db;box-shadow:-26px 0 80px rgba(10,24,18,.22);transform:translateX(105%);visibility:hidden;transition:transform .42s cubic-bezier(.22,.75,.25,1),visibility .42s ease}
.villa-explore-drawer.is-open{transform:translateX(0);visibility:visible}.villa-explore-scroll{height:100%;overflow-y:auto;overscroll-behavior:contain;padding:34px 30px calc(25px + env(safe-area-inset-bottom,0px));scrollbar-width:thin;scrollbar-color:#c8d0c4 transparent}
.villa-explore-head{position:relative;padding:5px 48px 19px 0}.villa-explore-kicker{display:block;margin-bottom:10px;color:#8a8c79;font-size:9px;font-weight:800;letter-spacing:.19em}.villa-explore-head h2{max-width:410px;margin:0;color:#24382c;font-family:Georgia,serif;font-size:29px;font-weight:500;line-height:1.13;letter-spacing:-.045em}.villa-explore-head p{margin:9px 0 0;color:#778078;font-size:12px;line-height:1.55}
.villa-explore-close{position:absolute;top:0;right:0;display:grid;place-items:center;width:39px;height:39px;border:1px solid #e4e5dc;border-radius:50%;background:#fff;color:#344638;font-size:25px;line-height:1;cursor:pointer;transition:background .2s,transform .2s}.villa-explore-close:hover{background:#edf2e9;transform:rotate(90deg)}
.villa-explore-hero{position:relative;display:block;height:215px;overflow:hidden;border-radius:18px;background:linear-gradient(135deg,#778b76,#263e30);color:#fff;text-decoration:none;box-shadow:0 13px 30px rgba(30,51,36,.12)}
.villa-explore-hero>img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:transform .65s cubic-bezier(.2,.7,.2,1)}.villa-explore-hero:hover>img{transform:scale(1.045)}.villa-explore-hero-shade{position:absolute;inset:0;background:linear-gradient(90deg,rgba(13,29,21,.78),rgba(13,29,21,.35) 62%,rgba(13,29,21,.08)),linear-gradient(0deg,rgba(12,25,19,.25),transparent 55%)}
.villa-explore-hero-copy{position:absolute;inset:0;display:flex;flex-direction:column;align-items:flex-start;justify-content:flex-end;padding:22px 24px}.villa-explore-hero-copy>span{font-size:8px;font-weight:800;letter-spacing:.17em;color:#e2e8dc}.villa-explore-hero-copy strong{display:block;max-width:340px;margin-top:8px;font-family:Georgia,serif;font-size:24px;font-weight:500;line-height:1.12;letter-spacing:-.035em}.villa-explore-hero-copy em{display:inline-flex;align-items:center;gap:13px;margin-top:14px;padding:9px 13px;border:1px solid rgba(255,255,255,.6);border-radius:30px;background:rgba(255,255,255,.13);font-size:10px;font-style:normal;font-weight:750;backdrop-filter:blur(8px)}.villa-explore-hero-copy em b{font-size:15px;font-weight:500}
.villa-explore-section{margin-top:25px}.villa-explore-section-heading{margin-bottom:12px}.villa-explore-section-heading span{color:#8d9588;font-size:8px;font-weight:800;letter-spacing:.17em}.villa-explore-section-heading h3{margin:5px 0 0;color:#2d4033;font-family:Georgia,serif;font-size:19px;font-weight:500;letter-spacing:-.025em}
.villa-explore-cards{display:grid;grid-template-columns:1fr 1fr;gap:10px}.villa-explore-card{position:relative;display:flex;align-items:center;gap:11px;min-width:0;padding:7px;border:1px solid #e9e9df;border-radius:13px;background:#fff;color:#304237;text-decoration:none;transition:transform .22s,border-color .22s,box-shadow .22s}.villa-explore-card:hover{transform:translateY(-2px);border-color:#d2ddcf;box-shadow:0 9px 22px rgba(34,57,39,.08)}
.villa-explore-card-photo{position:relative;display:block;flex:0 0 67px;width:67px;height:67px;overflow:hidden;border-radius:9px;background:#e8ede4}.villa-explore-card-photo img{width:100%;height:100%;object-fit:cover;transition:transform .45s}.villa-explore-card:hover .villa-explore-card-photo img{transform:scale(1.08)}.villa-explore-card-shade{position:absolute;inset:45% 0 0;background:linear-gradient(transparent,rgba(0,0,0,.18))}.villa-explore-card-placeholder{display:grid;place-items:center;width:100%;height:100%;color:#789078;font-size:23px}
.villa-explore-card-copy{display:block;min-width:0;flex:1;padding-right:7px}.villa-explore-card-copy strong{display:block;color:#304237;font-family:Georgia,serif;font-size:12px;font-weight:600;line-height:1.25}.villa-explore-card-copy small{display:block;margin-top:5px;color:#879087;font-size:9px;line-height:1.4}.villa-explore-card-arrow{position:absolute;right:10px;bottom:8px;color:#8d9a8b;font-size:12px}
.villa-explore-destination{position:relative;display:flex;align-items:flex-end;min-height:190px;overflow:hidden;margin-top:23px;border-radius:16px;background:#536a56;color:#fff}.villa-explore-destination>img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.villa-explore-destination:after{position:absolute;inset:0;content:"";background:linear-gradient(90deg,rgba(14,31,22,.78),rgba(14,31,22,.36) 78%),linear-gradient(0deg,rgba(12,25,19,.22),transparent)}.villa-explore-destination-copy{position:relative;z-index:1;max-width:390px;padding:19px 20px}.villa-explore-destination-copy>span{font-size:8px;font-weight:800;letter-spacing:.16em;color:#e2e8dd}.villa-explore-destination-copy h3{margin:7px 0;color:#fff;font-family:Georgia,serif;font-size:22px;font-weight:500;line-height:1.12}.villa-explore-destination-copy p{max-width:330px;margin:0;color:rgba(255,255,255,.84);font-size:10px;line-height:1.5}.villa-explore-destination-copy a{display:inline-flex;gap:12px;align-items:center;margin-top:11px;color:#fff;font-size:10px;font-weight:750;text-decoration:none}.villa-explore-destination-copy a b{font-size:15px;font-weight:400}
.villa-explore-support{display:flex;align-items:center;gap:12px;margin-top:17px;padding:15px;border:1px solid #e5e8dd;border-radius:14px;background:#f2f3eb}.villa-explore-support-icon{display:grid;place-items:center;flex:0 0 36px;width:36px;height:36px;border-radius:50%;background:#e2e9dc;color:#526e50;font-size:18px}.villa-explore-support-copy{min-width:0;flex:1}.villa-explore-support-copy>span{display:block;color:#879080;font-size:8px;font-weight:800;letter-spacing:.14em}.villa-explore-support-copy strong{display:block;margin-top:4px;color:#304237;font-family:Georgia,serif;font-size:14px;font-weight:600}.villa-explore-support-copy p{margin:4px 0 0;color:#7c877e;font-size:9px;line-height:1.4}.villa-explore-support-actions{display:flex;flex-direction:column;align-items:flex-end;gap:7px}.villa-explore-support-actions a{display:inline-flex;align-items:center;justify-content:center;min-width:31px;min-height:30px;padding:0 9px;border-radius:20px;background:#fff;color:#315d41;font-size:13px;font-weight:750;text-decoration:none;white-space:nowrap}.villa-explore-support-actions .villa-explore-whatsapp{background:#315d41;color:#fff;font-size:9px}.villa-explore-host-link{display:flex;justify-content:space-between;gap:10px;margin-top:14px;padding:12px 2px;color:#7b857b;font-size:10px;text-decoration:none}.villa-explore-host-link strong{color:#315d41}.villa-explore-footer-note{padding:17px 0 2px;color:#a1a79c;text-align:center;font-size:8px;font-weight:800;letter-spacing:.16em}.villa-explore-footer-note span{padding:0 5px;color:#66836b}
body.villa-explore-drawer-open{overflow:hidden!important}
.site-header-auth .villa-explore-trigger{padding:9px!important}
@media(max-width:760px){.villa-explore-drawer{width:100vw;border-left:0}.villa-explore-scroll{padding:22px 18px calc(20px + env(safe-area-inset-bottom,0px))}.villa-explore-head h2{font-size:26px}.villa-explore-hero{height:205px}.villa-explore-hero-copy{padding:18px}.villa-explore-hero-copy strong{font-size:22px}.villa-explore-card{gap:8px}.villa-explore-card-photo{flex-basis:58px;width:58px;height:62px}.villa-explore-card-copy strong{font-size:11px}.villa-explore-destination{min-height:180px}}
@media(max-width:370px){.villa-explore-cards{grid-template-columns:1fr}.villa-explore-card-photo{flex-basis:68px;width:68px;height:62px}}
@media(prefers-reduced-motion:reduce){.villa-explore-drawer,.villa-explore-backdrop,.villa-explore-hero>img,.villa-explore-card,.villa-explore-card-photo img,.villa-explore-close{transition:none}}
</style>
<script>
(function(){
 var drawer=document.getElementById('villaExploreDrawer'),backdrop=document.getElementById('villaExploreBackdrop'),closeButton=document.getElementById('villaExploreClose');
 if(!drawer||!backdrop||!closeButton||drawer.dataset.bound==='1')return;
 drawer.dataset.bound='1';var previousFocus=null,closeTimer=null;
 function openDrawer(trigger){
  window.clearTimeout(closeTimer);
  if(!drawer.hidden){drawer.classList.add('is-open');backdrop.classList.add('is-open');return;}
  previousFocus=trigger||document.activeElement;drawer.hidden=false;backdrop.hidden=false;drawer.setAttribute('aria-hidden','false');document.body.classList.add('villa-explore-drawer-open');
  document.querySelectorAll('[data-explore-drawer-trigger]').forEach(function(el){el.setAttribute('aria-expanded','true');});
  window.requestAnimationFrame(function(){backdrop.classList.add('is-open');drawer.classList.add('is-open');});window.setTimeout(function(){closeButton.focus();},100);
 }
 function closeDrawer(){
  if(drawer.hidden)return;drawer.classList.remove('is-open');backdrop.classList.remove('is-open');drawer.setAttribute('aria-hidden','true');document.body.classList.remove('villa-explore-drawer-open');
  document.querySelectorAll('[data-explore-drawer-trigger]').forEach(function(el){el.setAttribute('aria-expanded','false');});
  window.clearTimeout(closeTimer);closeTimer=window.setTimeout(function(){drawer.hidden=true;backdrop.hidden=true;if(previousFocus&&typeof previousFocus.focus==='function')previousFocus.focus();},430);
 }
 document.addEventListener('click',function(event){var trigger=event.target.closest('[data-explore-drawer-trigger]');if(trigger){event.preventDefault();openDrawer(trigger);return;}if(event.target===backdrop||event.target.closest('#villaExploreClose'))closeDrawer();});
 document.addEventListener('keydown',function(event){if(drawer.hidden)return;if(event.key==='Escape'){closeDrawer();return;}if(event.key==='Tab'){var focusable=Array.prototype.slice.call(drawer.querySelectorAll('a[href],button:not([disabled])')).filter(function(el){return el.offsetParent!==null;});if(!focusable.length)return;var first=focusable[0],last=focusable[focusable.length-1];if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}}});
})();
</script>
