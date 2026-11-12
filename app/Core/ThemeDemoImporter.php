<?php
declare(strict_types=1);

namespace App\\Core;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Imports theme-owned page-builder layouts without touching shared business data.
 * Existing layouts are never overwritten. Reset only removes layouts whose content
 * still matches the exact hash originally imported by this class.
 */
final class ThemeDemoImporter
{
    private static function packagePath(string $slug): string
    {
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{1,49}$/', $slug)) {
            throw new RuntimeException('Geçersiz tema kimliği.');
        }
        return APP_ROOT . '/themes/' . $slug . '/demo.json';
    }

    public static function package(string $slug): array
    {
        if (!isset(ThemeManager::installed()[$slug])) {
            throw new RuntimeException('Tema paketi bulunamadı.');
        }
        $path = self::packagePath($slug);
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Bu tema için demo içerik paketi bulunamadı.');
        }
        $data = json_decode((string)file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($data) || (string)($data['theme'] ?? '') !== $slug || !is_array($data['pages'] ?? null)) {
            throw new RuntimeException('Tema demo paketi geçersiz.');
        }
        return $data;
    }

    public static function status(string $slug): array
    {
        $stmt = Database::connection()->prepare('SELECT package_version,imported_at,created_layouts,skipped_layouts FROM theme_demo_imports WHERE theme_slug=? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : [];
    }

    public static function import(string $slug, int $userId): array
    {
        $package = self::package($slug);
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $check = $pdo->prepare('SELECT id FROM theme_demo_imports WHERE theme_slug=? LIMIT 1');
            $check->execute([$slug]);
            if ($check->fetchColumn()) {
                $pdo->commit();
                return ['created'=>0,'skipped'=>count($package['pages']),'already_imported'=>true];
            }

            $created = 0;
            $skipped = 0;
            $existsPage = $pdo->prepare("SELECT id FROM cms_pages WHERE slug=? AND language_code='tr' AND status='published' LIMIT 1");
            $existsLayout = $pdo->prepare('SELECT id FROM page_builder_layouts WHERE theme_slug=? AND page_key=? LIMIT 1');
            $readLayout = $pdo->prepare('SELECT id,layout_json FROM page_builder_layouts WHERE theme_slug=? AND page_key=? LIMIT 1');
            $track = $pdo->prepare('INSERT INTO theme_demo_layouts(theme_slug,page_key,layout_id,content_hash) VALUES(?,?,?,?)');

            foreach ($package['pages'] as $pageKey => $payload) {
                $pageKey = (string)$pageKey;
                if ($pageKey !== 'home') {
                    if (!preg_match('/^[a-z0-9-]{1,40}$/', $pageKey)) { $skipped++; continue; }
                    $existsPage->execute([$pageKey]);
                    if (!$existsPage->fetchColumn()) { $skipped++; continue; }
                }
                $existsLayout->execute([$slug,$pageKey]);
                if ($existsLayout->fetchColumn()) { $skipped++; continue; }
                if (!is_array($payload)) { $skipped++; continue; }
                $clean = PageBuilder::sanitizePreview($slug, $payload);
                if (empty($clean['widgets'])) { $skipped++; continue; }
                PageBuilder::save($slug, $pageKey, $clean, 'published', max(0,$userId));
                $readLayout->execute([$slug,$pageKey]);
                $row = $readLayout->fetch(PDO::FETCH_ASSOC);
                if (!$row) throw new RuntimeException('Demo sayfa düzeni kaydedilemedi: ' . $pageKey);
                $hash = hash('sha256', (string)$row['layout_json']);
                $track->execute([$slug,$pageKey,(int)$row['id'],$hash]);
                $created++;
            }

            $insert = $pdo->prepare('INSERT INTO theme_demo_imports(theme_slug,package_version,imported_by,created_layouts,skipped_layouts) VALUES(?,?,?,?,?)');
            $insert->execute([$slug,(string)($package['version']??'1.0.0'),$userId?:null,$created,$skipped]);
            $pdo->commit();
            return ['created'=>$created,'skipped'=>$skipped,'already_imported'=>false];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function reset(string $slug): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT layout_id,content_hash FROM theme_demo_layouts WHERE theme_slug=?');
            $stmt->execute([$slug]);
            $tracked = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $delete = $pdo->prepare('DELETE FROM page_builder_layouts WHERE id=? AND theme_slug=? AND SHA2(layout_json,256)=?');
            $removed = 0;
            foreach ($tracked as $item) {
                $delete->execute([(int)$item['layout_id'],$slug,(string)$item['content_hash']]);
                $removed += $delete->rowCount();
            }
            $pdo->prepare('DELETE FROM theme_demo_layouts WHERE theme_slug=?')->execute([$slug]);
            $pdo->prepare('DELETE FROM theme_demo_imports WHERE theme_slug=?')->execute([$slug]);
            $pdo->commit();
            return ['removed'=>$removed,'preserved'=>count($tracked)-$removed];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
