<?php
declare(strict_types=1);

use App\\Core\\Auth;
use App\\Core\\Database;
use App\\Core\\ThemeManager;

$router->get('/admin/properties/locations', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    $pdo = Database::connection();
    $counts = $pdo->query("SELECT
        SUM(CASE WHEN latitude IS NULL AND longitude IS NULL THEN 1 ELSE 0 END) AS missing,
        SUM(CASE WHEN (latitude IS NULL AND longitude IS NOT NULL) OR (latitude IS NOT NULL AND longitude IS NULL) THEN 1 ELSE 0 END) AS partial,
        SUM(CASE WHEN latitude IS NULL AND longitude IS NULL AND COALESCE(NULLIF(TRIM(address),''),NULLIF(TRIM(city),'')) IS NULL THEN 1 ELSE 0 END) AS no_address
        FROM properties")->fetch(PDO::FETCH_ASSOC) ?: [];
    $missingCount = (int)($counts['missing'] ?? 0);
    $partialCount = (int)($counts['partial'] ?? 0);
    $noAddressCount = (int)($counts['no_address'] ?? 0);
    ThemeManager::renderTemplate('templates/pages/admin/property-locations.php', get_defined_vars());
});

$router->post('/admin/properties/locations/process', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    verify_csrf();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');

    $pdo = Database::connection();
    $afterId = max(0, (int)($_POST['after_id'] ?? 0));
    $limit = 2;

    $select = $pdo->prepare("SELECT id,title,address,country,city,district,neighborhood
        FROM properties
        WHERE id > ? AND latitude IS NULL AND longitude IS NULL
        ORDER BY id ASC LIMIT " . $limit);
    $select->execute([$afterId]);
    $rows = $select->fetchAll(PDO::FETCH_ASSOC);

    $processed = 0;
    $updated = 0;
    $failed = [];
    $lastId = $afterId;

    foreach ($rows as $property) {
        $id = (int)$property['id'];
        $lastId = $id;
        $processed++;

        $parts = [];
        foreach (['address', 'neighborhood', 'district', 'city', 'country'] as $field) {
            $value = trim((string)($property[$field] ?? ''));
            if ($value !== '' && !in_array(mb_strtolower($value), array_map('mb_strtolower', $parts), true)) {
                $parts[] = $value;
            }
        }
        $query = implode(', ', $parts);
        if ($query === '') {
            $failed[] = ['id' => $id, 'title' => (string)$property['title'], 'reason' => 'Adres veya şehir bilgisi bulunamadı'];
            continue;
        }

        $url = 'https://photon.komoot.io/api/?q=' . rawurlencode($query) . '&limit=1&lang=tr';
        $body = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 7,
                CURLOPT_USERAGENT => 'VillaRentalPlatform/1.0 (admin bulk geocoding)',
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);
            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            if ($httpCode < 200 || $httpCode >= 300) $body = false;
        } else {
            $context = stream_context_create(['http' => [
                'method' => 'GET',
                'timeout' => 7,
                'ignore_errors' => true,
                'header' => "User-Agent: VillaRentalPlatform/1.0 (admin bulk geocoding)\r\nAccept: application/json\r\n",
            ]]);
            $body = @file_get_contents($url, false, $context);
        }

        $decoded = is_string($body) ? json_decode($body, true) : null;
        $feature = is_array($decoded) ? ($decoded['features'][0] ?? null) : null;
        $coords = is_array($feature) ? ($feature['geometry']['coordinates'] ?? null) : null;
        $longitude = is_array($coords) && isset($coords[0]) && is_numeric($coords[0]) ? (float)$coords[0] : null;
        $latitude = is_array($coords) && isset($coords[1]) && is_numeric($coords[1]) ? (float)$coords[1] : null;

        if ($latitude === null || $longitude === null || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            $failed[] = ['id' => $id, 'title' => (string)$property['title'], 'reason' => 'Adres için koordinat bulunamadı'];
            usleep(350000);
            continue;
        }

        $save = $pdo->prepare('UPDATE properties SET latitude=?,longitude=?,updated_at=NOW() WHERE id=? AND latitude IS NULL AND longitude IS NULL');
        $save->execute([$latitude, $longitude, $id]);
        if ($save->rowCount() > 0) $updated++;
        usleep(350000);
    }

    $remaining = (int)$pdo->query('SELECT COUNT(*) FROM properties WHERE latitude IS NULL AND longitude IS NULL')->fetchColumn();
    echo json_encode([
        'ok' => true,
        'processed' => $processed,
        'updated' => $updated,
        'failed' => $failed,
        'last_id' => $lastId,
        'remaining' => $remaining,
        'done' => count($rows) < $limit,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
});
