<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$translator = (string) file_get_contents($root . '/app/Core/Translator.php');
$routes = (string) file_get_contents($root . '/routes/web.php');

$checks = [
    'translator stores the active locale after loading its catalogue' =>
        str_contains($translator, "private static string $locale = 'tr';")
        && str_contains($translator, 'self::$locale = $locale;')
        && str_contains($translator, 'public static function locale(): string'),
    'account renderer uses the active locale' =>
        str_contains($routes, "function villa_render_account_view(string $view, array $variables): void {\n    $pdo = Database::connection();\n    $locale = Translator::locale();"),
    'homepage uses the active locale' =>
        str_contains($routes, "$router->get('/', static function (): void {")
        && str_contains(substr($routes, strpos($routes, "$router->get('/', static function (): void {"), 9000), '$locale = Translator::locale();'),
    'listing page uses the active locale' =>
        str_contains(substr($routes, strpos($routes, "$router->get('/konaklamalar'"), 9000), '$locale = Translator::locale();'),
    'destinations page uses the active locale' =>
        str_contains(substr($routes, strpos($routes, "$router->get('/destinasyonlar'"), 6000), '$locale=Translator::locale();'),
    'villa detail uses the active locale for content and navigation' =>
        str_contains(substr($routes, strpos($routes, "$router->get('/villa/{slug}'"), 5000), '$contentStmt->execute([$locale])')
        && str_contains(substr($routes, strpos($routes, "$router->get('/villa/{slug}'"), 5000), '$locale = Translator::locale();'),
    'checkout page uses the active locale' =>
        str_contains(substr($routes, strpos($routes, "$router->get('/checkout/{bookingCode}'"), 7000), '$locale = Translator::locale();'),
    'payment result uses the active locale' =>
        str_contains(substr($routes, strpos($routes, "foreach(['success','failed','review']"), 5000), '$locale = Translator::locale();'),
    'comparison page uses the active locale' =>
        str_contains(substr($routes, strpos($routes, "$router->get('/karsilastir'"), 5000), '$locale = Translator::locale();'),
    'CMS page uses the active locale for page, content, and navigation' =>
        str_contains(substr($routes, strpos($routes, "$router->get('/page/{slug}'"), 5000), 'language_code=?')
        && str_contains(substr($routes, strpos($routes, "$router->get('/page/{slug}'"), 5000), '$locale=Translator::locale();'),
    'blog archive and detail use the active locale for site content and navigation' =>
        str_contains(substr($routes, strpos($routes, "$router->get('/blog',"), 9000), '$locale=Translator::locale();')
        && str_contains(substr($routes, strpos($routes, "$router->get('/blog/{slug}'"), 9000), '$locale=Translator::locale();'),
    'public routes no longer independently read app.locale and drift from the translator catalogue' =>
        !str_contains($routes, "setting_key='app.locale'"),
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
echo 'All public locale consistency checks passed.' . PHP_EOL;
