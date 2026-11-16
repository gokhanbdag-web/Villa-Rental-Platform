<?php
declare(strict_types=1);

namespace App\Core;

final class Translator
{
    private static array $catalogue = [];
    private static string $locale = 'tr';
    public static function setLocale(string $locale): void
    {
        $stmt = Database::connection()->prepare('SELECT translation_key, translation_value FROM translations WHERE language_code = ?');
        $stmt->execute([$locale]);
        self::$catalogue = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
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
