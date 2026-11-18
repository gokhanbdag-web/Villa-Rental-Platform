<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class ContentTranslator
{
    private const FIELDS = [
        'property' => ['title','summary','description','country','city','district','neighborhood','meta_title','meta_description'],
        'property_category' => ['name','description'],
        'blog_post' => ['title','excerpt','content','meta_title','meta_description','featured_image_alt'],
        'blog_category' => ['name','description'],
        'blog_tag' => ['name'],
        'amenity' => ['name'],
        'location' => ['name'],
    ];

    /**
     * Overlay non-empty localized fields onto a batch of database rows.
     * Original fields remain the fallback when a translation is not yet supplied.
     */
    public static function apply(PDO $pdo, array $rows, string $entityType, string $locale): array
    {
        $allowed = self::FIELDS[$entityType] ?? [];
        if (!$rows || !$allowed || $locale === '') return $rows;
        $default = $pdo->query('SELECT code FROM languages WHERE is_default=1 AND is_active=1 LIMIT 1')->fetchColumn();
        if ($default !== false && hash_equals((string)$default, $locale)) return $rows;

        $ids = [];
        foreach ($rows as $row) {
            if (isset($row['id']) && (int)$row['id'] > 0) $ids[] = (int)$row['id'];
        }
        $ids = array_values(array_unique($ids));
        if (!$ids) return $rows;

        $marks = implode(',', array_fill(0, count($ids), '?'));
        $fields = implode(',', array_fill(0, count($allowed), '?'));
        $stmt = $pdo->prepare(
            "SELECT entity_id,field_key,translation_value FROM content_translations WHERE entity_type=? AND language_code=? AND entity_id IN ($marks) AND field_key IN ($fields)"
        );
        $stmt->execute(array_merge([$entityType,$locale],$ids,$allowed));
        $byEntity = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $translation) {
            $byEntity[(int)$translation['entity_id']][(string)$translation['field_key']] = (string)$translation['translation_value'];
        }
        foreach ($rows as &$row) {
            $id = (int)($row['id'] ?? 0);
            foreach ($byEntity[$id] ?? [] as $field => $value) {
                if (trim($value) !== '') $row[$field] = $value;
            }
        }
        unset($row);
        return $rows;
    }

    public static function fields(string $entityType): array
    {
        return self::FIELDS[$entityType] ?? [];
    }
}
