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
$mobileFormStart = strpos($landing, '<form class="mobile-premium-search__form"');
$mobileFormEnd = $mobileFormStart === false ? false : strpos($landing, '</form>', $mobileFormStart);
$mobileForm = ($mobileFormStart !== false && $mobileFormEnd !== false)
    ? substr($landing, $mobileFormStart, $mobileFormEnd - $mobileFormStart)
    : '';
if ($mobileForm === '' || !str_contains($mobileForm, '<select name="guests"')
    || !str_contains($mobileForm, '<button type="submit>')
    || !str_contains($landing, '.mobile-premium-search__form button{grid-column:2;')) {
    throw new RuntimeException('Mobile search button must sit in the same second column as the guest selector.');
}
echo "Mobile premium search placement and responsive visibility smoke test passed.\n";
