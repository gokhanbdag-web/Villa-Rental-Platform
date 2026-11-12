<?php
declare(strict_types=1);

/**
 * Default villa listing card.
 * All visual markup is theme-owned; callers provide prepared data only.
 *
 * Expected variables: $p, $cardImages, $isCardFavorite, $filters, $user.
 */
$cardImages = is_array($cardImages ?? null) ? $cardImages : [];
$isCardFavorite = (bool)($isCardFavorite ?? false);
$cardReturnTo = (string)($_SERVER['REQUEST_URI'] ?? '/konaklamalar');
?>
<article class="stay premium-stay-card">
    <div class="photo card-gallery" data-card-gallery>
        <?php if ($cardImages): ?>
            <img class="card-gallery-image" src="<?= e($cardImages[0]) ?>" alt="<?= e($p['title'] ?? '') ?>" loading="lazy">
        <?php endif; ?>
        <?php if (count($cardImages) > 1): ?>
            <button class="card-gallery-arrow prev" type="button" aria-label="Önceki fotoğraf" data-gallery-step="-1">‹</button>
            <button class="card-gallery-arrow next" type="button" aria-label="Sonraki fotoğraf" data-gallery-step="1">›</button>
            <span class="card-gallery-count">1 / <?= count($cardImages) ?></span>
        <?php endif; ?>
        <?php if ((int)($p['booking_count'] ?? 0) > 0): ?>
            <span class="stay-badge">Çok tercih edilen</span>
        <?php endif; ?>
        <?php if (($user['role'] ?? '') === 'customer'): ?>
            <form class="card-favorite-form js-favorite-form" method="post" action="<?= e(app_url('/favorites/toggle')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="property_id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="return_to" value="<?= e($cardReturnTo) ?>">
                <button class="card-favorite <?= $isCardFavorite ? 'is-active' : '' ?>" type="submit"
                    aria-label="<?= $isCardFavorite ? 'Favorilerden çıkar' : 'Favorilere ekle' ?>"
                    title="<?= $isCardFavorite ? 'Favorilerden çıkar' : 'Favorilere ekle' ?>"><?= $isCardFavorite ? '♥' : '♡' ?></button>
            </form>
        <?php endif; ?>
        <script type="application/json" class="card-gallery-data"><?= json_encode($cardImages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    </div>
    <a class="stay-content" href="<?= e(app_url('/villa/' . rawurlencode((string)$p['slug']) . (!empty($filters['check_in']) && !empty($filters['check_out']) ? '?' . http_build_query(['check_in' => $filters['check_in'], 'check_out' => $filters['check_out']]) : ''))) ?>">
        <h3><?= e($p['title']) ?></h3>
        <div class="meta"><?= e(trim(($p['district'] ?? '') . ', ' . ($p['city'] ?? '') . ', ' . ($p['country'] ?? ''))) ?> · <?= (int)$p['max_guests'] ?> misafir</div>
        <div class="rating-line">
            <?php if ((int)($p['rating_count'] ?? 0) > 0): ?>
                <span class="rating-star">★</span><span class="rating-score"><?= number_format((float)$p['rating_average'], 1, ',', '.') ?>/10</span><span><?= (int)$p['rating_count'] ?> değerlendirme</span>
            <?php else: ?><span>Henüz değerlendirme yok</span><?php endif; ?>
        </div>
        <div class="price"><strong><?= e(number_format((float)$p['base_nightly_price'], 0, ',', '.')) ?> <?= e($p['currency']) ?></strong> / gece</div>
    </a>
</article>
