<?php
declare(strict_types=1);

$logoUrl = trim((string)($logoUrl ?? ''));
$siteName = (string)($siteName ?? 'VillaStay');
if ($logoUrl === '') return;
$socialLinks = \App\Core\ThemeManager::desktopSocialLinks();
?>
<div class="site-logo-topbar">
  <a class="site-logo-topbar__brand" href="<?= e(app_url('/')) ?>" aria-label="<?= e($siteName) ?>">
    <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>">
  </a>
  <?php if ($socialLinks !== ''): ?>
    <nav class="site-logo-topbar__social" aria-label="Sosyal medya">
      <?= $socialLinks ?>
    </nav>
  <?php endif; ?>
</div>
