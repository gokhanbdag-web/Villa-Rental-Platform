<?php
declare(strict_types=1);

// The dedicated premium search must sit after the hero and remain mobile-only.
$root = dirname(__DIR__);
$landing = file_get_contents($root . '/themes/default/templates/pages/landing.php');
$compare = file_get_contents($root . '/themes/default/partials/compare-bar.php');
if (!is_string($landing) || !is_string($compare)) {
    throw new RuntimeException('Homepage or floating compare/search partial could not be read.');
}
$heroEnd = strpos($landing, '</form></div></section><?php endif; ?>');
$mobileSearch = strpos($landing, '<section class="mobile-premium-search"');
$destination = strpos($landing, 'name="city"', $mobileSearch ?: 0);
if ($heroEnd === false || $mobileSearch === false || $mobileSearch < $heroEnd || $destination === false) {
    throw new RuntimeException('Premium search must render immediately after the hero section.');
}
$mobileCss = strpos($landing, '@media(max-width:600px){', strpos($landing, '/* Premium search strip: mobile only'));
$desktopHide = strpos($landing, '.mobile-premium-search{display:none}');
if ($mobileCss === false || $desktopHide === false || $desktopHide > $mobileCss) {
    throw new RuntimeException('Premium search must be hidden by default and enabled only on mobile.');
}
if (!str_contains($landing, 'style="order:<?= $order(\'hero\',1) ?>"')) {
    throw new RuntimeException('Premium search must share the hero order so it renders immediately after the hero.');
}
if (!str_contains($landing, 'name="check_in"') || !str_contains($landing, 'name="check_out"') || !str_contains($landing, 'name="guests"')) {
    throw new RuntimeException('Premium search must submit destination, dates and guest count.');
}
echo "Mobile premium search placement and responsive visibility smoke test passed.\n";
