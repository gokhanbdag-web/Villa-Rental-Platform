<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="<?= e(\App\Core\Translator::locale()) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My reviews</title>
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
<h1>My reviews</h1>
<p class="muted">Track the reviews you submitted for your stays and their publication status.</p>
<?php foreach($reviews as $review): ?>
<article class="review-card">
<div class="review-main">
<div class="review-title"><a href="<?= e(app_url('/villa/'.rawurlencode((string)$review['property_slug']))) ?>"><?= e($review['property_title']) ?></a></div>
<div class="rating">★ <?= (int)$review['rating'] ?>/10</div>
<?php if(trim((string)($review['body']??''))!==''): ?><p class="review-body"><?= nl2br(e($review['body'])) ?></p><?php else: ?><p class="muted">You did not add a written review.</p><?php endif; ?>
<div class="review-meta"><span><?= e(date('d.m.Y',strtotime((string)$review['created_at']))) ?></span><?php if(!empty($review['booking_code'])): ?><span>Booking: <?= e($review['booking_code']) ?></span><?php endif; ?></div>
</div>
<span class="status"><?php
$statusLabels=['pending'=>'Pending approval','approved'=>'Published','rejected'=>'Not published'];
echo e($statusLabels[(string)$review['status']]??'Under review');
?></span>
</article>
<?php endforeach; ?>
<?php if(!$reviews): ?><section class="empty"><strong>You have no reviews yet.</strong><p class="muted">You can review a completed stay from your bookings.</p><a href="<?= e(app_url('/account/bookings')) ?>">Go to my bookings</a></section><?php endif; ?>
<p><a href="<?= e(app_url('/account/bookings')) ?>">My bookings</a> · <a href="<?= e(app_url('/')) ?>">Discover properties</a></p>
<?php require APP_ROOT . '/views/site/partials/logout-button.php'; ?>
</main></body></html>
