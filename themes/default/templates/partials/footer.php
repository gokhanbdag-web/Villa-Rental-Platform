<?php
declare(strict_types=1);

$footerSiteName = (string) ($siteName ?? $content['site_name'] ?? 'VillaStay');
$footerText = trim((string) ($content['footer_text'] ?? ''));
$footerNavItems = is_array($footerNavigation ?? null) ? $footerNavigation : [];
$footerExtraColumns = (string) ($footerColumns ?? '');
?>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-copy">
      <strong><?= e($footerSiteName) ?></strong>
      <?php if ($footerText !== ''): ?><p><?= e($footerText) ?></p><?php endif; ?>
    </div>
    <nav class="footer-links" aria-label="Alt menü">
      <?php
      $navItems = $footerNavItems;
      $navAllowAuth = false;
      \\App\\Core\\ThemeManager::renderPartial('partials/navigation-items.php', get_defined_vars());
      ?>
    </nav>
    <?= $footerExtraColumns ?>
  </div>
</footer>
