<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$shell = (string) file_get_contents($root . '/themes/default/layouts/admin-shell.php');

$checks = [
    'server renders only the single most-specific active navigation item' =>
        str_contains($shell, '$activeNavLength = -1;')
        && str_contains($shell, '$active = ($url === $activeNavUrl);'),
    'dashboard is active only for the exact admin root' =>
        str_contains($shell, '($url === \'/admin\')')
        && str_contains($shell, '($requestPath === \'/admin\' || $requestPath === \'/admin/\')'),
    'client active-state matching is scoped to the sidebar, not the header logo' =>
        str_contains($shell, 'document.querySelectorAll(".vr-admin-sidebar [data-admin-link]")'),
    'browser back and forward restore the active page without adding history entries' =>
        str_contains($shell, 'window.addEventListener("popstate"')
        && str_contains($shell, 'navigate(location.href,false)'),
    'client navigation clears all active states before selecting one' =>
        str_contains($shell, 'link.classList.remove("is-active");')
        && str_contains($shell, 'if(activeLink)activeLink.classList.add("is-active");'),
    'client navigation prefers the longest matching route' =>
        str_contains($shell, 'if(matches&&linkPath.length>activeLength){activeLink=link;activeLength=linkPath.length;}'),
    'old multi-active prefix matcher is removed' =>
        !str_contains($shell, 'a.classList.toggle("is-active",u.pathname===url'),
];

$failed = [];
foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS' : 'FAIL') . ' ' . $name . PHP_EOL;
    if (!$ok) $failed[] = $name;
}

$resolve = static function (string $path, array $links, string $adminRoot): ?string {
    $active = null;
    $length = -1;
    foreach ($links as $link) {
        $matches = ($link === $adminRoot)
            ? $path === $adminRoot
            : ($path === $link || str_starts_with($path, $link . '/'));
        if ($matches && strlen($link) > $length) {
            $active = $link;
            $length = strlen($link);
        }
    }
    return $active;
};

$links = ['/admin', '/admin/properties', '/admin/blog', '/admin/blog/categories', '/admin/bookings'];
$scenarios = [
    '/admin' => '/admin',
    '/admin/blog' => '/admin/blog',
    '/admin/blog/categories' => '/admin/blog/categories',
    '/admin/blog/42' => '/admin/blog',
    '/admin/blog/categories/9' => '/admin/blog/categories',
    '/admin/properties/42' => '/admin/properties',
    '/admin/bookings' => '/admin/bookings',
    '/admin/unknown' => null,
];
foreach ($scenarios as $path => $expected) {
    $actual = $resolve($path, $links, '/admin');
    $ok = $actual === $expected;
    echo ($ok ? 'PASS' : 'FAIL') . ' active item for ' . $path . PHP_EOL;
    if (!$ok) $failed[] = 'active item for ' . $path . ' expected ' . var_export($expected, true) . ' got ' . var_export($actual, true);
}

$subdirectoryLinks = ['/villa/admin', '/villa/admin/blog', '/villa/admin/blog/categories'];
$subdirectoryScenarios = [
    '/villa/admin' => '/villa/admin',
    '/villa/admin/blog' => '/villa/admin/blog',
    '/villa/admin/blog/categories' => '/villa/admin/blog/categories',
    '/villa/admin/blog/42' => '/villa/admin/blog',
];
foreach ($subdirectoryScenarios as $path => $expected) {
    $actual = $resolve($path, $subdirectoryLinks, '/villa/admin');
    $ok = $actual === $expected;
    echo ($ok ? 'PASS' : 'FAIL') . ' subdirectory active item for ' . $path . PHP_EOL;
    if (!$ok) $failed[] = 'subdirectory active item for ' . $path;
}

if ($failed) {
    fwrite(STDERR, 'Failed: ' . implode('; ', $failed) . PHP_EOL);
    exit(1);
}
echo 'All admin sidebar active-state checks passed.' . PHP_EOL;
