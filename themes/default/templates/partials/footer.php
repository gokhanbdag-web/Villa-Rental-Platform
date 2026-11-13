<?php
declare(strict_types=1);

$footerSettings = \App\Core\ThemeManager::footerSettings();
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
                $paymentSvg = [
                    'mastercard'=>'<svg viewBox="0 0 72 44" role="img" aria-label="Mastercard"><circle cx="28" cy="22" r="15" fill="#EB001B"/><circle cx="44" cy="22" r="15" fill="#F79E1B"/><path d="M36 10.4a15 15 0 0 1 0 23.2 15 15 0 0 1 0-23.2" fill="#FF5F00"/></svg>',
                    'visa'=>'<svg viewBox="0 0 72 44" role="img" aria-label="Visa"><path fill="#1A1F71" d="M8 12h10l6 18 7-18h10L29 34H18zM43 12h9l-4 22h-9zM59 12h7l5 22h-9l-.7-4h-8l-1.7 4h-9zm-3 12h5l-1.2-7z"/></svg>',
                    'troy'=>'<svg viewBox="0 0 72 44" role="img" aria-label="Troy"><path fill="#E52629" d="M8 12h26v6H24v16h-7V18H8z"/><path fill="#1D4E89" d="M29 18h7v4c2-3 5-5 9-5v7c-5 0-8 2-9 7v3h-7z"/><path fill="#E52629" d="M48 18h7l4 11 4-11h7l-8 19c-2 5-5 7-11 7v-6c3 0 4-1 5-3z"/></svg>',
                    'diners_club'=>'<svg viewBox="0 0 72 44" role="img" aria-label="Diners Club"><rect x="8" y="7" width="56" height="30" rx="15" fill="#0877B9"/><circle cx="36" cy="22" r="11" fill="#fff"/><path d="M36 11a11 11 0 0 0 0 22zm0 0a11 11 0 0 1 0 22z" fill="#0877B9"/></svg>',
                    'american_express'=>'<svg viewBox="0 0 72 44" role="img" aria-label="American Express"><rect x="5" y="7" width="62" height="30" rx="4" fill="#2E77BC"/><path fill="#fff" d="M10 15h7l2 4 2-4h7v14h-6v-6l-3 5-3-5v6h-6zm21 0h17v4H37v1h10v4H37v1h11v4H31zm19 0h9l2 4 2-4h5l-5 7 5 7h-9l-3-4-3 4h-6l5-7z"/></svg>'
                ];
                ?>
                <div class="footer-payment-logos" aria-label="Kabul edilen ödeme kartları">
                    <?php foreach (array_slice($paymentOrder, 0, 5) as $payment): if (!isset($paymentSvg[$payment])) continue; ?>
                        <span class="footer-payment-logo" title="<?= e($paymentNames[$payment]) ?>"><?= $paymentSvg[$payment] ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</footer>
