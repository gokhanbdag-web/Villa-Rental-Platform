<?php
declare(strict_types=1);

$dashboard = file_get_contents(dirname(__DIR__) . '/views/admin/dashboard.php');
if (!is_string($dashboard) || preg_match('~<style>(.*?)</style>~s', $dashboard, $match) !== 1) {
    fwrite(STDERR, "Dashboard CSS block is missing.\n");
    exit(1);
}
$css = $match[1];
$checks = [
    'dashboard styles are scoped to the shared content container' => str_contains($css, '#vrAdminContent'),
    'body styles do not leak into the shared admin shell' => preg_match('~(?:^|})\\s*body\\s*\\{~i', $css) !== 1,
    'main styles do not overwrite the shared admin content frame' => preg_match('~(?:^|})\\s*main\\s*\\{~i', $css) !== 1,
    'aside styles do not overwrite the shared admin sidebar' => preg_match('~(?:^|})\\s*aside\\s*\\{~i', $css) !== 1,
    'anchor styles do not leak into shared admin navigation' => preg_match('~(?:^|})\\s*a\\s*\\{~i', $css) !== 1,
    'overview metric cards retain their component styling' => str_contains($css, '#vrAdminContent .cards'),
    'overview panels retain their component styling' => str_contains($css, '#vrAdminContent .panel'),
    'CSS braces remain balanced' => substr_count($css, '{') === substr_count($css, '}'),
];
$failed = [];
foreach ($checks as $label => $passed) {
    echo ($passed ? 'PASS' : 'FAIL') . ' ' . $label . PHP_EOL;
    if (!$passed) $failed[] = $label;
}
if ($failed !== []) exit(1);
echo "Dashboard CSS isolation checks passed.\n";
