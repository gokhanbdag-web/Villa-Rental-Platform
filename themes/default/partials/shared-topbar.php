<?php
declare(strict_types=1);

$logoUrl = trim((string)($logoUrl ?? ''));
$siteName = (string)($siteName ?? 'VillaStay');
if ($logoUrl === '') return;
?>
<div class="site-logo-topbar">
  <a class="site-logo-topbar__brand" href="<?= e(app_url('/')) ?>" aria-label="<?= e($siteName) ?>">
    <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>">
  </a>
</div>
