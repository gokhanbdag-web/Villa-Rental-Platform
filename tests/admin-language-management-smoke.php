<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$routes = (string) file_get_contents($root . '/routes/web.php');
$view = (string) file_get_contents($root . '/themes/default/templates/pages/admin/translations.php');
$header = (string) file_get_contents($root . '/themes/default/templates/partials/header.php');

$checks = [
    'translation admin lists active languages and their default status' =>
        str_contains($routes, 'SELECT code,name,is_default FROM languages WHERE is_active=1')
        && str_contains($view, 'foreach($languages as $language)'),
    'language creation is restricted to administrators and protected by CSRF' =>
        str_contains($routes, '$router->post(\'/admin/languages/save\', static function (): void {')
        && str_contains($routes, "Auth::requireRole(['super_admin','admin']);")
        && str_contains($routes, 'verify_csrf();'),
    'language code and display name are validated before insert' =>
        str_contains($routes, '/^[a-z]{2}(?:-[A-Z]{2})?$/')
        && str_contains($routes, 'mb_strlen($name) > 80')
        && str_contains($routes, 'SELECT id FROM languages WHERE code=? LIMIT 1'),
    'new languages are enabled and can atomically become the default locale' =>
        str_contains($routes, 'INSERT INTO languages(code,name,is_default,is_active) VALUES(?,?,?,1)')
        && str_contains($routes, 'UPDATE languages SET is_default=0')
        && str_contains($routes, "setting->execute(['app.locale', $code])")
        && str_contains($routes, '$pdo->commit();'),
    'admin UI exposes language code, name, and default selection' =>
        str_contains($view, 'name="code"')
        && str_contains($view, 'name="name"')
        && str_contains($view, 'name="is_default"'),
    'active new languages become available in the public header selector' =>
        str_contains($header, 'SELECT code,name FROM languages WHERE is_active=1'),
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
echo 'All admin language management checks passed.' . PHP_EOL;
