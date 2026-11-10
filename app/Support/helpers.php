<?php
declare(strict_types=1);

function e(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never {
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    $sent = (string) ($_POST['_csrf'] ?? '');
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Oturum doğrulaması başarısız.');
    }
}

function app_config(): array {
    static $config;
    if ($config === null) {
        $file = APP_ROOT . '/config/config.php';
        $config = is_file($file) ? require $file : [];
    }
    return $config;
}

function store_property_uploads(PDO $pdo, int $propertyId): void {
    if (empty($_FILES['images']) || !is_array($_FILES['images']['name'] ?? null)) return;
    $directory = APP_ROOT . '/public/uploads/properties';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Görsel yükleme klasörü oluşturulamadı.');
    }
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $count = min(count($_FILES['images']['name']), 10);
    $existing = $pdo->prepare("SELECT COUNT(*) FROM property_media WHERE property_id=? AND file_path NOT LIKE 'demo/villas/%'");
    $existing->execute([$propertyId]);
    $cover = (int)$existing->fetchColumn() === 0 ? 1 : 0;
    if ($cover === 1) { $resetCover = $pdo->prepare('UPDATE property_media SET is_cover=0 WHERE property_id=?'); $resetCover->execute([$propertyId]); }
    $insert = $pdo->prepare('INSERT INTO property_media (property_id,file_path,alt_text,is_cover,sort_order) VALUES (?,?,?,?,?)');
    for ($i=0; $i<$count; $i++) {
        $error = (int)($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE || $error !== UPLOAD_ERR_OK) continue;
        $tmp = (string)($_FILES['images']['tmp_name'][$i] ?? '');
        $size = (int)($_FILES['images']['size'][$i] ?? 0);
        if ($tmp === '' || $size < 1 || $size > 8 * 1024 * 1024 || !is_uploaded_file($tmp)) continue;
        $mime = $finfo->file($tmp);
        if (!isset($allowed[$mime])) continue;
        $filename = bin2hex(random_bytes(18)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($tmp, $directory . '/' . $filename)) continue;
        $relative = 'uploads/properties/' . $filename;
        $alt = trim((string)($_POST['title'] ?? 'Villa fotoğrafı'));
        $insert->execute([$propertyId,$relative,$alt,$cover,$i]);
        $cover = 0;
    }
}

function delete_property_uploads(PDO $pdo, int $propertyId): void {
    $stmt = $pdo->prepare('SELECT file_path FROM property_media WHERE property_id=?');
    $stmt->execute([$propertyId]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $path) {
        $path = (string)$path;
        if (!str_starts_with($path, 'uploads/properties/')) continue;
        $full = APP_ROOT . '/public/' . $path;
        if (is_file($full)) @unlink($full);
    }
}

function sync_property_amenities(PDO $pdo, int $propertyId): void {
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['amenities'] ?? [])), static fn(int $id): bool => $id > 0)));
    $delete = $pdo->prepare('DELETE FROM property_amenities WHERE property_id=?');
    $delete->execute([$propertyId]);
    if (!$ids) return;
    $check = $pdo->prepare('SELECT id FROM amenities WHERE id=? AND is_active=1');
    $insert = $pdo->prepare('INSERT IGNORE INTO property_amenities(property_id,amenity_id) VALUES(?,?)');
    foreach ($ids as $amenityId) {
        $check->execute([$amenityId]);
        if ($check->fetchColumn()) $insert->execute([$propertyId,$amenityId]);
    }
}
