<?php declare(strict_types=1);
$navigation = is_array($navigation ?? null) ? $navigation : [];
$resultType = (string)($paymentResultType ?? 'review');
$isSuccess = $resultType === 'success';
$isFailed = $resultType === 'failed';
$title = $isSuccess ? 'Ödemeniz başarılı' : ($isFailed ? 'Ödeme tamamlanamadı' : 'Ödeme sonucu kontrol ediliyor');
$description = $isSuccess
    ? 'Ödemeniz iyzico tarafından doğrulandı. Rezervasyonunuz güvenle oluşturuldu.'
    : ($isFailed
        ? 'iyzico ödeme işlemini başarısız olarak bildirdi. Rezervasyonunuz için henüz tahsilat yapılmadı.'
        : 'Ödeme bildiriminiz alındı ancak sonuç henüz kesinleşmedi. Güvenlik kontrolü veya doğrulama tamamlandığında rezervasyon durumunuz güncellenecek.');
?>
<!doctype html>
<html lang="<?= e($locale ?? 'tr') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · <?= e($booking['booking_code']) ?></title>
<style>
.payment-result-page{min-height:65vh;padding:clamp(34px,6vw,76px) 0;background:var(--villa-background,#f7f8f6);color:var(--villa-text,#20332c)}
.payment-result-wrap{width:min(760px,calc(100% - 32px));margin:0 auto}
.payment-result-card{padding:clamp(26px,5vw,48px);border:1px solid var(--villa-border,#e4eae5);border-radius:24px;background:var(--villa-surface,#fff);box-shadow:0 18px 50px rgba(24,45,34,.07);text-align:center}
.payment-result-icon{display:grid;place-items:center;width:66px;height:66px;margin:0 auto 20px;border-radius:50%;font-size:30px;font-weight:800}
.payment-result-icon.success{background:#e8f5ec;color:#167044}.payment-result-icon.failed{background:#fff0ed;color:#a33d2c}.payment-result-icon.review{background:#fff6df;color:#8a6414}
.payment-result-card h1{margin:0 0 12px;font-size:clamp(27px,4vw,38px);letter-spacing:-.04em}
.payment-result-card>p{max-width:570px;margin:0 auto;color:var(--villa-muted,#718078);font-size:15px;line-height:1.75}
.payment-result-code{margin:26px 0 0;padding:15px;border-radius:13px;background:var(--villa-background,#f7f8f6);font-size:13px}
.payment-result-code strong{display:block;margin-top:5px;font-size:16px;letter-spacing:.05em}
.payment-result-meta{margin-top:9px;color:var(--villa-muted);font-size:12px}
.payment-result-actions{display:flex;justify-content:center;flex-wrap:wrap;gap:11px;margin-top:28px}
.payment-result-actions a{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:11px 18px;border:1px solid var(--villa-border);border-radius:11px;color:var(--villa-text);text-decoration:none;font-size:13px;font-weight:800}
.payment-result-actions a.primary{border-color:var(--villa-primary);background:var(--villa-primary);color:#fff}
@media(max-width:520px){.payment-result-actions{display:grid}.payment-result-actions a{width:100%}}
</style>
</head>
<body>
<?php \App\Core\ThemeManager::renderPartial('partials/header.php', get_defined_vars()); ?>
<main class="payment-result-page">
<div class="payment-result-wrap">
<section class="payment-result-card" aria-live="polite">
<div class="payment-result-icon <?= $isSuccess ? 'success' : ($isFailed ? 'failed' : 'review') ?>"><?= $isSuccess ? '✓' : ($isFailed ? '!' : '…') ?></div>
<h1><?= e($title) ?></h1>
<p><?= e($description) ?></p>
<div class="payment-result-code"><span>Rezervasyon kodunuz</span><strong><?= e($booking['booking_code']) ?></strong><div class="payment-result-meta"><?= e($booking['property_title']) ?> · <?= e(number_format((float)$booking['total'],2,',','.')) ?> <?= e($booking['currency']) ?></div></div>
<nav class="payment-result-actions">
<?php if($isFailed): ?><a class="primary" href="<?= e(app_url('/checkout/'.rawurlencode((string)$booking['booking_code']))) ?>">Tekrar ödeme yap</a><?php endif; ?>
<a class="<?= $isSuccess ? 'primary' : '' ?>" href="<?= e(app_url('/account/bookings')) ?>">Rezervasyonlarım</a>
<a href="<?= e(app_url('/')) ?>">Ana sayfa</a>
</nav>
</section>
</div>
</main>
<?php \App\Core\ThemeManager::renderPartial('partials/footer.php', get_defined_vars()); ?>
</body>
</html>
