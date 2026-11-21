<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
$catalogue = require dirname(__DIR__) . '/app/Support/ui-en.php';
if (!is_array($catalogue) || $catalogue === []) throw new RuntimeException('English UI catalogue missing.');
$legacy = 'Platform ayarları';
$english = $catalogue[$legacy] ?? null;
if (!is_string($english) || $english === '') throw new RuntimeException('English label missing.');
$helper = (string)file_get_contents(dirname(__DIR__) . '/app/Support/helpers.php');
foreach (['Translator::get($source)', 'Translator::get($label)', "Translator::locale() === 'tr'", 'array_flip($english)'] as $needle) {
    if (!str_contains($helper, $needle)) throw new RuntimeException('Locale resolver missing: '.$needle);
}
echo "English-first locale resolution smoke passed.\n";
