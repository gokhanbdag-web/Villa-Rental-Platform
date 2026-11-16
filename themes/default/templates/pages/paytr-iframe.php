<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>PayTR güvenli ödeme</title>
<style>
*{box-sizing:border-box}body{margin:0;background:var(--villa-background,#f7f8f6);color:var(--villa-text,#20332c);font-family:system-ui,-apple-system,sans-serif}.wrap{width:min(980px,calc(100% - 28px));margin:32px auto}.panel{background:var(--villa-surface,#fff);border:1px solid var(--villa-border,#e5ebe6);border-radius:18px;padding:clamp(18px,3vw,30px);box-shadow:0 12px 34px rgba(24,45,34,.06)}h1{font-size:clamp(23px,4vw,32px);margin:0 0 8px}.muted{color:var(--villa-muted,#718078);line-height:1.65;font-size:14px}.frame{width:100%;height:760px;border:0;display:block;margin-top:20px}.back{display:inline-flex;margin-top:16px;color:var(--villa-primary,#176b50);font-weight:700;text-decoration:none}@media(max-width:600px){.wrap{margin:14px auto}.panel{padding:15px}.frame{height:720px}}
</style></head><body>
<?php \App\Core\ThemeManager::renderPartial('partials/header.php',get_defined_vars()); ?>
<main class="wrap"><section class="panel"><h1>Güvenli ödeme</h1><p class="muted">Ödeme işleminiz PayTR'ın güvenli ödeme alanında gerçekleşir. Kart bilgileri VillaCMS tarafından alınmaz veya saklanmaz.</p>
<iframe class="frame" src="<?= e($iframeUrl) ?>" id="paytriframe" title="PayTR güvenli ödeme formu" frameborder="0" scrolling="no" allow="payment *"></iframe>
<script src="https://www.paytr.com/js/iframeResizer.min.js"></script><script>if(window.iFrameResize){iFrameResize({},'#paytriframe');}</script>
<a class="back" href="<?= e(app_url('/checkout/'.rawurlencode((string)$row['booking_code']))) ?>">Ödeme sayfasına dön</a>
</section></main>
<?php \App\Core\ThemeManager::renderPartial('partials/footer.php',get_defined_vars()); ?>
</body></html>
