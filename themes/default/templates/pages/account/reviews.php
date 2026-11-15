<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Yorumlarım</title>
<style>
body{font-family:var(--villa-font-body);background:#f5f7f5;color:var(--villa-text);margin:0}
.wrap{max-width:1000px;margin:35px auto;padding:0 18px}
.review-card{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:16px;background:#fff;border:1px solid #e7ece8;border-radius:14px;padding:18px;margin:12px 0;min-width:0}
.review-main{min-width:0}.review-title{font-weight:700;overflow-wrap:anywhere}.review-body{white-space:normal;overflow-wrap:anywhere;line-height:1.65}
.review-meta{display:flex;flex-wrap:wrap;gap:8px 14px;color:var(--villa-text);font-size:13px;margin-top:10px}
.rating{white-space:nowrap;font-weight:800}.status{display:inline-flex;align-items:center;border-radius:999px;padding:6px 10px;background:#eef3ef;font-size:12px;font-weight:700;white-space:nowrap}
.empty{background:#fff;border:1px dashed #d8e2dc;border-radius:14px;padding:26px}
@media(max-width:600px){.review-card{grid-template-columns:minmax(0,1fr);gap:10px}.review-card>.status{justify-self:start}.wrap{padding:0 14px}}
</style>
<?php require APP_ROOT . '/views/site/partials/account-theme.php'; ?>
</head>
<body><main class="wrap">
<h1>Yorumlarım</h1>
<p class="muted">Konaklamalarınız için gönderdiğiniz değerlendirmeleri ve yayınlanma durumlarını buradan takip edebilirsiniz.</p>
<?php foreach($reviews as $review): ?>
<article class="review-card">
<div class="review-main">
<div class="review-title"><a href="<?= e(app_url('/villa/'.rawurlencode((string)$review['property_slug']))) ?>"><?= e($review['property_title']) ?></a></div>
<div class="rating">★ <?= (int)$review['rating'] ?>/10</div>
<?php if(trim((string)($review['body']??''))!==''): ?><p class="review-body"><?= nl2br(e($review['body'])) ?></p><?php else: ?><p class="muted">Yazılı yorum eklemediniz.</p><?php endif; ?>
<div class="review-meta"><span><?= e(date('d.m.Y',strtotime((string)$review['created_at']))) ?></span><?php if(!empty($review['booking_code'])): ?><span>Rezervasyon: <?= e($review['booking_code']) ?></span><?php endif; ?></div>
</div>
<span class="status"><?php
$statusLabels=['pending'=>'Onay bekliyor','approved'=>'Yayında','rejected'=>'Yayınlanmadı'];
echo e($statusLabels[(string)$review['status']]??'İnceleniyor');
?></span>
</article>
<?php endforeach; ?>
<?php if(!$reviews): ?><section class="empty"><strong>Henüz yorumunuz bulunmuyor.</strong><p class="muted">Tamamlanan bir konaklamanız varsa rezervasyonlarınızdan değerlendirme gönderebilirsiniz.</p><a href="<?= e(app_url('/account/bookings')) ?>">Rezervasyonlarıma git</a></section><?php endif; ?>
<p><a href="<?= e(app_url('/account/bookings')) ?>">Rezervasyonlarım</a> · <a href="<?= e(app_url('/')) ?>">Yeni konaklama keşfet</a></p>
<?php require APP_ROOT . '/views/site/partials/logout-button.php'; ?>
</main></body></html>
