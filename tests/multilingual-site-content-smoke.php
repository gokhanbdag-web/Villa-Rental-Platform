<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$routes = (string) file_get_contents($root . '/routes/web.php');
$view = (string) file_get_contents($root . '/themes/default/templates/pages/admin/content.php');

$checks = [
    'site content editor lists active languages and selects a valid requested language' =>
        str_contains($routes, 'SELECT code,name,is_default FROM languages WHERE is_active=1')
        && str_contains($routes, '$_GET[\'lang\'] ?? $defaultLanguage')
        && str_contains($routes, 'WHERE language_code=?'),
    'site content editor shows the language selector and posts the selected locale' =>
        str_contains($view, 'name="lang"')
        && str_contains($view, 'name="lang" value="<?= e($locale) ?>"')
        && str_contains($view, 'foreach($languages as $language)'),
    'site content save validates that the selected language is active' =>
        str_contains($routes, '$_POST[\'lang\'] ??')
        && str_contains($routes, 'SELECT code FROM languages WHERE code=? AND is_active=1 LIMIT 1'),
    'site content writes are parameterized by language and key' =>
        str_contains($routes, 'INSERT INTO site_content (content_key,language_code,content_value,updated_at) VALUES (?,?,?,NOW())')
        && str_contains($routes, '$stmt->execute([$key,$locale,trim((string)$value)]);'),
    'saving returns to the same language editor' =>
        str_contains($routes, 'redirect(\'/admin/content?lang=\' . rawurlencode($locale));'),
];

$failed = [];
foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS' : 'FAIL') . ' ' . $name . PHP_EOL;
    if (!$ok) $failed[] = $name;
}

if ($failed) {
    fwrite(STDERR, 'Failed: ' . implode('; ', $failed) . PHP_EOL);
    exit(1);
}
echo 'All multilingual site content checks passed.' . PHP_EOL;
