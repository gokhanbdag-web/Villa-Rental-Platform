<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$index = (string) file_get_contents($root . '/index.php');
$routes = (string) file_get_contents($root . '/routes/web.php');
$header = (string) file_get_contents($root . '/themes/default/templates/partials/header.php');

$checks = [
    'request locale prefers a session choice only when the language is active' =>
        str_contains($index, '$sessionLocale = trim((string)($_SESSION[\'villa_locale\'] ?? \'\'));')
        && str_contains($index, 'in_array($sessionLocale, $activeLocales, true)')
        && str_contains($index, 'unset($_SESSION[\'villa_locale\']);'),
    'configured default locale is validated against active languages' =>
        str_contains($index, 'SELECT code,is_default FROM languages WHERE is_active=1')
        && str_contains($index, 'in_array($configuredLocale, $activeLocales, true)'),
    'language switch endpoint validates the posted code against active languages' =>
        str_contains($routes, '$router->post(\'/language\', static function (): void {')
        && str_contains($routes, 'SELECT code FROM languages WHERE code=? AND is_active=1 LIMIT 1')
        && str_contains($routes, 'verify_csrf();'),
    'language switch persists the selected locale and safely returns to the current page' =>
        str_contains($routes, '$_SESSION[\'villa_locale\'] = $locale;')
        && str_contains($routes, 'villa_safe_return_url((string)($_POST[\'return_to\'] ?? \'/\'))'),
    'header selector appears only when more than one active language exists' =>
        str_contains($header, 'SELECT code,name FROM languages WHERE is_active=1')
        && str_contains($header, 'if (count($availableLanguages) > 1)'),
    'header selector reflects the current locale and posts a CSRF token' =>
        str_contains($header, '$currentLocale = \App\Core\Translator::locale();')
        && str_contains($header, 'name="_csrf"')
        && str_contains($header, 'name="locale"')
        && str_contains($header, 'name="return_to"'),
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
echo 'All public language selector checks passed.' . PHP_EOL;
