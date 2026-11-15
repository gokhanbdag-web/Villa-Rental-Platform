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
        <?php elseif (empty($user)): ?>
            <button class="card-favorite" type="button" data-login-trigger data-favorite-login-trigger
                data-favorite-property-id="<?= (int)$p['id'] ?>"
                aria-label="Giriş yaparak favorilere ekle" title="Giriş yaparak favorilere ekle">♡</button>
        <?php else: ?>
            <button class="card-favorite is-unavailable" type="button" disabled
                aria-label="Favoriler müşteri hesaplarında kullanılabilir"
                title="Favorilere eklemek için müşteri hesabı kullanın">♡</button>
        <?php endif; ?>
        <button class="card-compare-button" type="button" data-compare-property
            data-property-id="<?= (int)$p['id'] ?>"
            data-property-title="<?= e((string)($p['title'] ?? '')) ?>"
            data-property-url="<?= e(app_url('/villa/' . rawurlencode((string)$p['slug']))) ?>"
            data-property-image="<?= e((string)($cardImages[0] ?? '')) ?>"
            data-property-price="<?= e((string)($p['base_nightly_price'] ?? 0)) ?>"
            data-property-currency="<?= e((string)($p['currency'] ?? 'TRY')) ?>"
            data-property-location="<?= e(trim((string)($p['district'] ?? '') . ', ' . (string)($p['city'] ?? ''), ', ')) ?>"
            data-property-guests="<?= (int)($p['max_guests'] ?? 0) ?>"
            aria-pressed="false" title="Karşılaştırmaya ekle">⇄</button>
        <script type="application/json" class="card-gallery-data"><?= json_encode($cardImages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    </div>
    <a class="stay-content" href="<?= e(app_url('/villa/' . rawurlencode((string)$p['slug']) . (!empty($filters['check_in']) && !empty($filters['check_out']) ? '?' . http_build_query(['check_in' => $filters['check_in'], 'check_out' => $filters['check_out']]) : ''))) ?>">
        <h3><?= e($p['title']) ?></h3>
        <div class="meta"><?= e(trim(($p['district'] ?? '') . ', ' . ($p['city'] ?? '') . ', ' . ($p['country'] ?? ''))) ?> · <?= (int)$p['max_guests'] ?> misafir</div>
        <div class="card-bottom-row">
            <div class="price"><strong><?= e(number_format((float)$p['base_nightly_price'], 0, ',', '.')) ?> <?= e($p['currency']) ?></strong> <span>/ gece</span></div>
            <div class="rating-line">
                <?php if ((int)($p['rating_count'] ?? 0) > 0): ?>
                    <span class="rating-star">★</span><span class="rating-score"><?= number_format((float)$p['rating_average'], 1, ',', '.') ?>/10</span><span><?= (int)$p['rating_count'] ?> değerlendirme</span>
                <?php else: ?><span>Henüz değerlendirme yok</span><?php endif; ?>
            </div>
        </div>
    </a>
</article>
