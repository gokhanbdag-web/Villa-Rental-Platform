<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Imports complete theme-owned page-builder layouts.
 * Existing layouts are backed up before replacement and restored only when the
 * imported demo has not subsequently been edited by the site owner.
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
        if (filesize($path) > 1024 * 1024) throw new RuntimeException('Tema demo paketi 1 MB sınırını aşıyor.');
        $data = json_decode((string)file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($data) || (string)($data['theme'] ?? '') !== $slug || !is_array($data['pages'] ?? null) || count($data['pages']) > 20) {
            throw new RuntimeException('Tema demo paketi geçersiz.');
        }
        $allowedWidgets = ['hero','destinations','properties','image_text','features','cta','text','image','contact','faq'];
        foreach ($data['pages'] as $page) {
            if (!is_array($page) || !is_array($page['widgets'] ?? null) || count($page['widgets']) > 30) {
                throw new RuntimeException('Demo sayfa düzeni geçersiz veya çok büyük.');
            }
            foreach ($page['widgets'] as $widget) {
                if (!is_array($widget) || !in_array((string)($widget['type'] ?? ''), $allowedWidgets, true)) {
                    throw new RuntimeException('Demo paketinde izin verilmeyen bir widget türü var.');
                }
                if (isset($widget['settings']['html_content'])) {
                    throw new RuntimeException('Demo paketleri özel HTML içeremez.');
                }
            }
        }
        return $data;
    }

    public static function hasPackage(string $slug): bool
    {
        try { self::package($slug); return true; }
        catch (Throwable $e) { return false; }
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
        $version = (string)($package['version'] ?? '1.0.0');
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $check = $pdo->prepare('SELECT id,package_version FROM theme_demo_imports WHERE theme_slug=? LIMIT 1 FOR UPDATE');
            $check->execute([$slug]);
            $previousImport = $check->fetch(PDO::FETCH_ASSOC);
            if ($previousImport && version_compare((string)$previousImport['package_version'], $version, '>=')) {
                $pdo->commit();
                return ['created'=>0,'skipped'=>count($package['pages']),'already_imported'=>true,'updated'=>false];
            }

            $created = 0;
            $skipped = 0;
            $existsPage = $pdo->prepare("SELECT id FROM cms_pages WHERE slug=? AND language_code='tr' AND status='published' LIMIT 1");
            $readLayout = $pdo->prepare('SELECT id,layout_json,status,version,updated_at FROM page_builder_layouts WHERE theme_slug=? AND page_key=? LIMIT 1 FOR UPDATE');
            $readTrack = $pdo->prepare('SELECT * FROM theme_demo_layouts WHERE theme_slug=? AND page_key=? LIMIT 1 FOR UPDATE');
            $track = $pdo->prepare('INSERT INTO theme_demo_layouts(theme_slug,page_key,layout_id,content_hash,previous_layout_json,previous_status,previous_version,previous_updated_at) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE layout_id=VALUES(layout_id),content_hash=VALUES(content_hash),previous_layout_json=VALUES(previous_layout_json),previous_status=VALUES(previous_status),previous_version=VALUES(previous_version),previous_updated_at=VALUES(previous_updated_at)');

            foreach ($package['pages'] as $pageKey => $payload) {
                $pageKey = (string)$pageKey;
                if ($pageKey !== 'home') {
                    if (!preg_match('/^[a-z0-9-]{1,40}$/', $pageKey)) { $skipped++; continue; }
                    $existsPage->execute([$pageKey]);
                    if (!$existsPage->fetchColumn()) { $skipped++; continue; }
                }
                if (!is_array($payload)) { $skipped++; continue; }
                $clean = PageBuilder::sanitizePreview($slug, $payload);
                if (empty($clean['widgets'])) { $skipped++; continue; }

                $readLayout->execute([$slug,$pageKey]);
                $existing = $readLayout->fetch(PDO::FETCH_ASSOC) ?: null;
                $readTrack->execute([$slug,$pageKey]);
                $oldTrack = $readTrack->fetch(PDO::FETCH_ASSOC) ?: null;

                // Keep the original pre-demo backup across package upgrades.
                $previousJson = $oldTrack['previous_layout_json'] ?? ($existing['layout_json'] ?? null);
                $previousStatus = $oldTrack['previous_status'] ?? ($existing['status'] ?? null);
                $previousVersion = $oldTrack['previous_version'] ?? ($existing['version'] ?? null);
                $previousUpdatedAt = $oldTrack['previous_updated_at'] ?? ($existing['updated_at'] ?? null);

                PageBuilder::save($slug, $pageKey, $clean, 'published', max(0,$userId));
                $readLayout->execute([$slug,$pageKey]);
                $saved = $readLayout->fetch(PDO::FETCH_ASSOC);
                if (!$saved) throw new RuntimeException('Demo sayfa düzeni kaydedilemedi: ' . $pageKey);
                $hash = hash('sha256', (string)$saved['layout_json']);
                $track->execute([
                    $slug,$pageKey,(int)$saved['id'],$hash,
                    $previousJson,$previousStatus,$previousVersion,$previousUpdatedAt
                ]);
                $created++;
            }

            $insert = $pdo->prepare('INSERT INTO theme_demo_imports(theme_slug,package_version,imported_by,created_layouts,skipped_layouts) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE package_version=VALUES(package_version),imported_by=VALUES(imported_by),imported_at=NOW(),created_layouts=VALUES(created_layouts),skipped_layouts=VALUES(skipped_layouts)');
            $insert->execute([$slug,$version,$userId?:null,$created,$skipped]);
            $pdo->commit();
            return ['created'=>$created,'skipped'=>$skipped,'already_imported'=>false,'updated'=>(bool)$previousImport];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function reset(string $slug): array
    {
        if (!isset(ThemeManager::installed()[$slug])) throw new RuntimeException('Tema paketi bulunamadı.');
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT layout_id,content_hash,previous_layout_json,previous_status,previous_version,previous_updated_at FROM theme_demo_layouts WHERE theme_slug=? FOR UPDATE');
            $stmt->execute([$slug]);
            $tracked = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $read = $pdo->prepare('SELECT id,layout_json FROM page_builder_layouts WHERE id=? AND theme_slug=? LIMIT 1 FOR UPDATE');
            $delete = $pdo->prepare('DELETE FROM page_builder_layouts WHERE id=? AND theme_slug=?');
            $restore = $pdo->prepare('UPDATE page_builder_layouts SET layout_json=?,status=?,version=?,updated_at=? WHERE id=? AND theme_slug=?');
            $removed = 0;
            $restored = 0;
            $preserved = 0;
            foreach ($tracked as $item) {
                $read->execute([(int)$item['layout_id'],$slug]);
                $current = $read->fetch(PDO::FETCH_ASSOC);
                if (!$current || !hash_equals((string)$item['content_hash'],hash('sha256',(string)$current['layout_json']))) {
                    $preserved++;
                    continue;
                }
                if ($item['previous_layout_json'] !== null) {
                    $restore->execute([
                        $item['previous_layout_json'],
                        $item['previous_status'] ?: 'draft',
                        max(1,(int)($item['previous_version'] ?? 1)),
                        $item['previous_updated_at'] ?: date('Y-m-d H:i:s'),
                        (int)$item['layout_id'],$slug
                    ]);
                    $restored++;
                } else {
                    $delete->execute([(int)$item['layout_id'],$slug]);
                    $removed += $delete->rowCount();
                }
            }
            $pdo->prepare('DELETE FROM theme_demo_layouts WHERE theme_slug=?')->execute([$slug]);
            $pdo->prepare('DELETE FROM theme_demo_imports WHERE theme_slug=?')->execute([$slug]);
            $pdo->commit();
            return ['removed'=>$removed,'restored'=>$restored,'preserved'=>$preserved];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
