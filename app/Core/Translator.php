<?php
declare(strict_types=1);

namespace App\Core;

final class Translator
{
    private static array $catalogue = [];
    private static string $locale = 'tr';
    public static function setLocale(string $locale): void
    {
        $pdo = Database::connection();
        $default = $pdo->query('SELECT code FROM languages WHERE is_default=1 AND is_active=1 LIMIT 1')->fetchColumn();
        $defaultLocale = is_string($default) && $default !== '' ? $default : 'tr';
        $catalogue = [];

        if ($locale !== $defaultLocale) {
            $fallback = $pdo->prepare('SELECT translation_key,translation_value FROM translations WHERE language_code=?');
            $fallback->execute([$defaultLocale]);
            $catalogue = $fallback->fetchAll(\\PDO::FETCH_KEY_PAIR);
        }

        $stmt = $pdo->prepare('SELECT translation_key,translation_value FROM translations WHERE language_code=?');
        $stmt->execute([$locale]);
        foreach ($stmt->fetchAll(\\PDO::FETCH_KEY_PAIR) as $key => $value) {
            $catalogue[$key] = $value;
        }

        self::$catalogue = $catalogue;
        self::$locale = $locale;
    }
    public static function locale(): string
    {
        return self::$locale;
    }

    public static function get(string $key, array $replace = []): string
    {
        $value = self::$catalogue[$key] ?? $key;
        foreach ($replace as $name => $replacement) $value = str_replace(':' . $name, (string)$replacement, $value);
        return $value;
    }
}
