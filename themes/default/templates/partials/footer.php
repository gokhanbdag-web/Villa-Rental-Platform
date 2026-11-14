<?php
declare(strict_types=1);

$footerSettings = \App\Core\ThemeManager::footerSettings();
$mobileBarSettings = [];
try {
    $mobileBarQuery = \App\Core\Database::connection()->prepare("SELECT setting_key,setting_value FROM settings WHERE setting_key IN ('mobile_bar.enabled','mobile_bar.whatsapp','mobile_bar.phone')");
    $mobileBarQuery->execute();
    $mobileBarSettings = $mobileBarQuery->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Throwable $mobileBarError) {
    $mobileBarSettings = [];
}
$mobileBarEnabled = ($mobileBarSettings['mobile_bar.enabled'] ?? '1') === '1';
$mobileWhatsapp = preg_replace('/\\D+/', '', (string)($mobileBarSettings['mobile_bar.whatsapp'] ?? '')) ?: '';
$mobilePhone = preg_replace('/\\D+/', '', (string)($mobileBarSettings['mobile_bar.phone'] ?? '')) ?: '';
?>
<footer class="site-footer" aria-label="Alt bilgi"
    style="--footer-bg:<?= e($footerSettings['background']) ?>;--footer-text:<?= e($footerSettings['text']) ?>;--footer-padding-top:<?= (int)$footerSettings['padding_top'] ?>px;--footer-padding-bottom:<?= (int)$footerSettings['padding_bottom'] ?>px;--footer-margin-top:<?= (int)$footerSettings['margin_top'] ?>px;--footer-content-width:<?= (int)$footerSettings['content_width'] ?>px">
    <div class="footer-inner">
        <div class="footer-row footer-row-primary" aria-label="Üst widget satırı">
            <div class="footer-widget footer-widget-1" data-footer-widget="1">
                <?php if (trim((string)($footerSettings['about_title'] ?? '')) !== '' || trim((string)($footerSettings['about_description'] ?? '')) !== ''): ?>
                    <section class="footer-about" aria-label="Hakkında">
                        <?php if (trim((string)($footerSettings['about_title'] ?? '')) !== ''): ?><h2><?= e($footerSettings['about_title']) ?></h2><?php endif; ?>
                        <?php if (trim((string)($footerSettings['about_description'] ?? '')) !== ''): ?><p><?= nl2br(e($footerSettings['about_description'])) ?></p><?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
            <div class="footer-widget footer-widget-2" data-footer-widget="2"></div>
            <div class="footer-widget footer-widget-3" data-footer-widget="3"></div>
            <div class="footer-widget footer-widget-4" data-footer-widget="4"></div>
        </div>
        <div class="footer-row footer-row-secondary" aria-label="Alt widget satırı">
            <div class="footer-widget footer-widget-5" data-footer-widget="5"></div>
            <div class="footer-widget footer-widget-6" data-footer-widget="6">
                <?php
                $paymentNames = ['mastercard'=>'Mastercard','visa'=>'Visa','troy'=>'Troy','diners_club'=>'Diners Club','american_express'=>'American Express'];
                $paymentOrder = is_array($footerSettings['payment_order'] ?? null) ? $footerSettings['payment_order'] : array_keys($paymentNames);
                ?>
                <div class="footer-payment-logos" aria-label="Kabul edilen ödeme kartları">
                    <?php foreach (array_slice($paymentOrder, 0, 5) as $payment): if (!isset($paymentNames[$payment])) continue; ?>
                        <span class="footer-payment-logo" title="<?= e($paymentNames[$payment]) ?>">
                            <img src="<?= e(\App\Core\ThemeManager::assetUrl('payment-logos/' . $payment . '.svg')) ?>" alt="<?= e($paymentNames[$payment]) ?>" loading="lazy" decoding="async">
                        </span>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>
</footer>

<?php if ($mobileBarEnabled): ?>
<style>
.villa-mobile-bar,.villa-mobile-search-overlay{display:none}
@media(max-width:760px){
 body{padding-bottom:calc(76px + env(safe-area-inset-bottom,0px))!important}
 .villa-mobile-bar{position:fixed;z-index:9998;left:0;right:0;bottom:0;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:2px;padding:7px 8px calc(7px + env(safe-area-inset-bottom,0px));background:rgba(255,255,255,.97);border-top:1px solid #e6ebe5;box-shadow:0 -8px 28px rgba(29,52,38,.10);backdrop-filter:blur(14px)}
 .villa-mobile-bar a,.villa-mobile-bar button{min-width:0;min-height:52px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;border:0;background:transparent;color:#334b3b;text-decoration:none;font:inherit;cursor:pointer;-webkit-tap-highlight-color:transparent}
 .villa-mobile-bar svg{width:22px;height:22px;display:block;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
 .villa-mobile-bar span{font-size:10px;font-weight:650;line-height:1.15;white-space:nowrap}
 .villa-mobile-bar .villa-mobile-whatsapp svg{color:#159b62}.villa-mobile-bar .villa-mobile-phone svg{color:#315a43}
 .villa-mobile-search-overlay{position:fixed;z-index:10000;inset:0;display:flex;align-items:flex-end;justify-content:center;padding:16px;background:rgba(17,34,25,.48);opacity:0;visibility:hidden;transition:opacity .2s ease,visibility .2s ease}
 .villa-mobile-search-overlay.is-open{opacity:1;visibility:visible}
 .villa-mobile-search-card{width:min(520px,100%);padding:20px 18px calc(18px + env(safe-area-inset-bottom,0px));border-radius:20px 20px 14px 14px;background:#fff;box-shadow:0 -12px 45px rgba(0,0,0,.16);transform:translateY(18px);transition:transform .22s ease}
 .villa-mobile-search-overlay.is-open .villa-mobile-search-card{transform:translateY(0)}
 .villa-mobile-search-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
 .villa-mobile-search-head strong{font-size:17px;color:#263b2d}
 .villa-mobile-search-close{width:34px;height:34px;border:0;border-radius:50%;background:#f0f4ef;color:#344b3b;font-size:22px;line-height:1;cursor:pointer}
 .villa-mobile-search-form{display:flex;gap:8px}
 .villa-mobile-search-form input{flex:1;min-width:0;height:48px;padding:0 14px;border:1px solid #dce6dc;border-radius:11px;font:inherit;font-size:14px;outline:none}
 .villa-mobile-search-form input:focus{border-color:#547b5d;box-shadow:0 0 0 3px rgba(84,123,93,.12)}
 .villa-mobile-search-form button{height:48px;padding:0 16px;border:0;border-radius:11px;background:#315a43;color:#fff;font:inherit;font-size:13px;font-weight:700;white-space:nowrap;cursor:pointer}
}
@media(prefers-reduced-motion:reduce){.villa-mobile-search-overlay,.villa-mobile-search-card{transition:none}}
</style>
<nav class="villa-mobile-bar" aria-label="Mobil hızlı menü">
 <a href="<?= e(app_url('/')) ?>" aria-label="Ana Sayfa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7"></path><path d="M5 9.5V21h14V9.5M9 21v-7h6v7"></path></svg><span>Ana Sayfa</span></a>
 <button type="button" data-villa-search-open aria-label="Villa adıyla ara"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 5 5"></path></svg><span>Villa Ara</span></button>
 <?php if ($mobileWhatsapp !== ''): ?><a class="villa-mobile-whatsapp" href="https://wa.me/<?= e($mobileWhatsapp) ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp ile iletişim"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.2 11.7a8.2 8.2 0 0 1-12.1 7.2L3 20l1.2-4.8a8.2 8.2 0 1 1 16-3.5Z"></path><path d="M8.4 8.1c.3-.6.6-.6.9-.6h.5c.2 0 .4.1.5.4l.8 1.8c.1.2.1.4-.1.6l-.6.7c-.2.2-.2.4 0 .7.5.9 1.3 1.6 2.2 2.1.2.1.4.1.6-.1l.8-.9c.2-.2.4-.2.6-.1l1.7.8c.3.1.4.3.4.5 0 .3-.2 1.1-.7 1.5-.5.5-1.2.7-1.9.6-1.1-.2-2.4-.8-3.7-2-1.1-1-2.1-2.5-2.3-3.5-.2-.8 0-1.6.3-2.1Z"></path></svg><span>WhatsApp</span></a><?php else: ?><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20.2 11.7a8.2 8.2 0 0 1-12.1 7.2L3 20l1.2-4.8a8.2 8.2 0 1 1 16-3.5Z"></path></svg><span>WhatsApp</span></span><?php endif; ?>
 <?php if ($mobilePhone !== ''): ?><a class="villa-mobile-phone" href="tel:<?= e($mobilePhone) ?>" aria-label="Telefonla ara"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3H4.8A1.8 1.8 0 0 0 3 4.8C3 13.7 10.3 21 19.2 21a1.8 1.8 0 0 0 1.8-1.8V17l-4.7-2-1.4 2a14.7 14.7 0 0 1-6-6l2-1.4L9 3H7Z"></path></svg><span>Ara</span></a><?php else: ?><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3H4.8A1.8 1.8 0 0 0 3 4.8C3 13.7 10.3 21 19.2 21a1.8 1.8 0 0 0 1.8-1.8V17l-4.7-2-1.4 2a14.7 14.7 0 0 1-6-6l2-1.4L9 3H7Z"></path></svg><span>Ara</span></span><?php endif; ?>
</nav>
<div class="villa-mobile-search-overlay" data-villa-search-overlay aria-hidden="true"><section class="villa-mobile-search-card" role="dialog" aria-modal="true" aria-labelledby="villaMobileSearchTitle"><div class="villa-mobile-search-head"><strong id="villaMobileSearchTitle">Villa adıyla ara</strong><button type="button" class="villa-mobile-search-close" data-villa-search-close aria-label="Kapat">×</button></div><form class="villa-mobile-search-form" method="get" action="<?= e(app_url('/konaklamalar')) ?>"><input type="search" name="q" maxlength="120" placeholder="Villa adını yazın..." aria-label="Villa adı" required><button type="submit">Ara</button></form></section></div>
<script>(function(){var overlay=document.querySelector('[data-villa-search-overlay]'),openButton=document.querySelector('[data-villa-search-open]');if(!overlay||!openButton||overlay.dataset.ready==='1')return;overlay.dataset.ready='1';var closeButton=overlay.querySelector('[data-villa-search-close]'),input=overlay.querySelector('input[name="q"]');function close(){overlay.classList.remove('is-open');overlay.setAttribute('aria-hidden','true');document.body.style.overflow=''}function open(){overlay.classList.add('is-open');overlay.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';window.setTimeout(function(){if(input)input.focus()},80)}openButton.addEventListener('click',open);if(closeButton)closeButton.addEventListener('click',close);overlay.addEventListener('click',function(event){if(event.target===overlay)close()});document.addEventListener('keydown',function(event){if(event.key==='Escape'&&overlay.classList.contains('is-open'))close()})})();</script>
<?php endif; ?>
