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
            <div class="footer-widget footer-widget-6" data-footer-widget="6"></div>
        </div>
    </div>
</footer>
