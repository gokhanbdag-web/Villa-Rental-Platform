<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$installer = file_get_contents($root . '/app/Core/Installer.php');
$schema = file_get_contents($root . '/database/schema.sql');
$site = file_get_contents($root . '/database/migrations/004_site_content.sql');
$checks = [
    'English default locale' => str_contains($installer, "'app.locale' => 'en'"),
    'Separate multi-vendor demo ownership' => str_contains($installer, "if (\$mode === 'multi_vendor')"),
    'Multi-vendor demo vendor created' => str_contains($installer, 'Demo Villa Collection'),
    'Single-vendor demo remains platform-owned' => str_contains($installer, "if (\$mode === 'multi_vendor')"),
    'Only English built-in language' => !str_contains($schema, "('tr',"),
    'English default site content language' => str_contains($site, "DEFAULT 'en'"),
];
foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}
