<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class Migrator
{
    public static function run(PDO $pdo, ?string $root = null): void
    {
        $root ??= dirname(__DIR__, 2);
        $lock = (int)$pdo->query("SELECT GET_LOCK('villa_platform_migrations',15)")->fetchColumn();
        if ($lock !== 1) throw new \RuntimeException('Could not acquire migration lock.');
        try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
            migration VARCHAR(190) PRIMARY KEY,
            applied_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $files = glob($root . '/database/migrations/*.sql') ?: [];
        sort($files, SORT_STRING);
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE migration=? LIMIT 1');
        $record = $pdo->prepare('INSERT INTO schema_migrations(migration,applied_at) VALUES(?,NOW())');

        foreach ($files as $file) {
            $name = basename($file);
            $check->execute([$name]);
            if ($check->fetchColumn()) continue;
            $sql = file_get_contents($file);
            if ($sql === false) throw new \RuntimeException('Migration file could not be read: ' . $name);
            try {
                foreach (preg_split('/;\\s*(?:\\r?\\n|$)/', trim($sql)) as $statement) {
                    $statement = trim($statement);
                    if ($statement !== '') $pdo->exec($statement);
                }
                $record->execute([$name]);
            } catch (Throwable $e) {
                throw new \RuntimeException('Migration failed: ' . $name . ' - ' . $e->getMessage(), 0, $e);
            }
        }
        // Self-heal the navigation schema if a previous deployment skipped
        // this migration or recorded it without applying the column.
        self::selfHealNavigationHierarchy($pdo);
        $pdo->prepare('INSERT IGNORE INTO schema_migrations(migration, applied_at) VALUES(?, NOW())')->execute(['018_navigation_hierarchy.sql']);
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('villa_platform_migrations')")->fetchColumn();
        }
    }
    private static function selfHealNavigationHierarchy(PDO $pdo): void
    {
        $table = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'navigation_items'");
        if ((int)$table->fetchColumn() === 0) {
            return;
        }

        $column = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'navigation_items' AND COLUMN_NAME = 'parent_id'");
        if ((int)$column->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE navigation_items ADD COLUMN parent_id BIGINT UNSIGNED NULL AFTER id");
        }

        $index = $pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'navigation_items' AND INDEX_NAME = 'idx_navigation_parent_location'");
        if ((int)$index->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE navigation_items ADD INDEX idx_navigation_parent_location (parent_id, location, sort_order)");
        }
    }

}
