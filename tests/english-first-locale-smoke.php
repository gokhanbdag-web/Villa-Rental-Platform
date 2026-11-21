<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
$catalogue = require dirname(__DIR__) . '/app/Support/ui-en.php';
if (!is_array($catalogue) || $catalogue === []) throw new RuntimeException('English UI catalogue missing.');
$legacy = 'Platform ayarları';
$english = $catalogue[$legacy] ?? null;
if (!is_string($english) || $english === '') throw new RuntimeException('English label missing.');

$locale = new ReflectionProperty(\App\Core\Translator::class, 'locale');
$translations = new ReflectionProperty(\App\Core\Translator::class, 'catalogue');
try {
    $locale->setValue(null, 'en');
    $translations->setValue(null, []);
    if (villa_ui($legacy) !== $english || villa_ui($english) !== $english) {
        throw new RuntimeException('English locale did not normalize legacy UI labels.');
    }
    foreach (['Your dream holiday awaits' => 'Hayalinizdeki tatil sizi bekliyor', 'MAKE YOUR HOLIDAY SPECIAL' => 'TATİLİNİZİ GÜZELLEŞTİRİN', 'Sea, nature and peace… Discover your ideal holiday home.' => 'Deniz, doğa ve huzur… Size en uygun tatil evini keşfedin.'] as $source => $expectedTurkish) {
        if (villa_ui($source) !== $source) throw new RuntimeException('Homepage source is not English-first: ' . $source);
    }
    $locale->setValue(null, 'tr');
    foreach (['Your dream holiday awaits' => 'Hayalinizdeki tatil sizi bekliyor', 'MAKE YOUR HOLIDAY SPECIAL' => 'TATİLİNİZİ GÜZELLEŞTİRİN', 'Sea, nature and peace… Discover your ideal holiday home.' => 'Deniz, doğa ve huzur… Size en uygun tatil evini keşfedin.'] as $source => $expectedTurkish) {
        if (villa_ui($source) !== $expectedTurkish) throw new RuntimeException('Homepage Turkish fallback missing: ' . $source);
    }
    if (villa_ui($legacy) !== $legacy || villa_ui($english) !== $legacy) {
        throw new RuntimeException('Turkish locale did not resolve English-first UI labels.');
    }
    $translations->setValue(null, [$english => 'Özel çeviri']);
    if (villa_ui($english) !== 'Özel çeviri' || villa_ui($legacy) !== 'Özel çeviri') {
        throw new RuntimeException('Localized database overrides were not applied.');
    }
    $locale->setValue(null, 'de');
    $translations->setValue(null, []);
    if (villa_ui($english) !== $english || villa_ui($legacy) !== $english) {
        throw new RuntimeException('Untranslated locale did not fall back to English.');
    }
} finally {
    $locale->setValue(null, 'en');
    $translations->setValue(null, []);
}
echo "English-first locale resolution smoke passed.\n";
