<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/Core/ThemeManager.php';

$normalize = new ReflectionMethod(App\Core\ThemeManager::class, 'normalizeHomepageStats');
$normalize->setAccessible(true);

$actual = $normalize->invoke(null, [
    'villas' => '',
    'destinations' => '0',
    'customers' => '125',
    'reviews' => null,
]);
$expected = ['villas' => null, 'destinations' => 0, 'customers' => 125, 'reviews' => null];
if ($actual !== $expected) {
    throw new RuntimeException('Homepage statistic overrides did not normalize as expected.');
}

foreach (['-1', '1.5', '1000000000', 'not-a-number'] as $invalid) {
    try {
        $normalize->invoke(null, ['villas' => $invalid]);
        throw new RuntimeException('Invalid homepage statistic override was accepted: ' . $invalid);
    } catch (ReflectionException $e) {
        throw $e;
    } catch (RuntimeException $e) {
        if ($e->getMessage() === 'Invalid homepage statistic override was accepted: ' . $invalid) throw $e;
    }
}

$landingTemplate = file_get_contents(APP_ROOT . '/themes/default/templates/pages/landing.php');
$desktopSearchFix = 'body .hero-card .hero-filter-panel form#homeSearch.search-enhanced > .search-submit';
if (!is_string($landingTemplate) || !str_contains($landingTemplate, $desktopSearchFix) || !str_contains($landingTemplate, 'grid-column:1 / -1!important;') || !str_contains($landingTemplate, 'width:100%!important;max-width:100%!important;min-width:0!important;')) {
    throw new RuntimeException('Desktop hero search submit button must span the search card without exceeding its width.');
}

echo "Homepage statistic settings smoke test passed.\n";
