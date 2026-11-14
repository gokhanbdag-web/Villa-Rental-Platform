<?php
declare(strict_types=1);

function e(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_base_path(): string {
    static $base = null;
    if ($base !== null) return $base;

    $normalize = static function (string $path): string {
        $path = str_replace(chr(92), '/', $path);
        $path = preg_replace('#/+#', '/', $path) ?? $path;
        return rtrim($path, '/');
    };

    // The application directory is stable; SCRIPT_NAME is not. Under some
    // Apache/FastCGI rewrite setups it contains the requested route
    // (/villaed/admin/properties), not the executing index.php path.
    $appRoot = realpath(defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2));
    $documentRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($appRoot !== false && $documentRoot !== false) {
        $root = $normalize($appRoot);
        $doc = $normalize($documentRoot);

        // When the application root itself is the web document root, it is
        // already mounted at /. Do not infer a URL prefix from the physical
        // directory name (for example, /villa), or routes such as /villa/{slug}
        // would be incorrectly rewritten to /villa/villa/{slug}.
        if ($root === $doc) {
            $base = '';
            return $base;
        }

        // Standard deployment: project root is a subdirectory of document root.
        if (str_starts_with($root, $doc . '/')) {
            $relative = trim(substr($root, strlen($doc)), '/');
            $base = $relative === '' ? '' : '/' . $relative;
            return $base;
        }

        // Public-directory document root: the URL-visible script path is
        // authoritative only when it actually names an index.php entrypoint.
        if (str_starts_with($doc, $root . '/')) {
            $script = $normalize((string)($_SERVER['SCRIPT_NAME'] ?? ''));
            if (strtolower(basename($script)) === 'index.php') {
                $dir = $normalize(dirname($script));
                if (in_array(basename($dir), ['public', 'install'], true)) $dir = $normalize(dirname($dir));
                $base = ($dir === '' || $dir === '/' || $dir === '.') ? '' : $dir;
                return $base;
            }
            // The public folder itself is not part of the public URL.
            $base = '';
            return $base;
        }

        // Aliased/symlinked document roots: compare the physical front controller.
        $scriptFile = realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
        if ($scriptFile !== false) {
            $scriptPath = $normalize($scriptFile);
            if (str_starts_with($scriptPath, $doc . '/')) {
                $relative = ltrim(substr($scriptPath, strlen($doc)), '/');
                $dir = $normalize(dirname('/' . $relative));
                if (in_array(basename($dir), ['public', 'install'], true)) $dir = $normalize(dirname($dir));
                $base = ($dir === '' || $dir === '/' || $dir === '.') ? '' : $dir;
                return $base;
            }
        }
    }

    // Add-on domains and aliased document roots can make DOCUMENT_ROOT point
    // outside APP_ROOT. In a subdirectory install, the physical application
    // folder name is also the URL prefix produced by the supplied rewrite rules.
    // Use it only when the current request actually starts with that exact segment.
    if ($appRoot !== false) {
        $appDirectory = basename($appRoot);
        $requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        if ($appDirectory !== '' && $appDirectory !== '.' &&
            ($requestPath === '/' . $appDirectory || str_starts_with($requestPath, '/' . $appDirectory . '/'))) {
            $base = '/' . $appDirectory;
            return $base;
        }
    }

    // Last-resort fallback: only trust SCRIPT_NAME if it names a PHP entry file.
    $script = $normalize((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if (preg_match('#/(?:index|install)\.php$#i', $script)) {
        $dir = $normalize(dirname($script));
        if (in_array(basename($dir), ['public', 'install'], true)) $dir = $normalize(dirname($dir));
        $base = ($dir === '' || $dir === '/' || $dir === '.') ? '' : $dir;
        return $base;
    }

    $base = '';
    return $base;
}

function app_url(string $path = '/'): string {
    if (preg_match('#^(?:https?:)?//#i', $path)) return $path;
    $base = app_base_path();
    $path = '/' . ltrim($path, '/');
    // Keep URLs idempotent when a saved CMS/menu URL already includes
    // this installation's subfolder prefix.
    if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) return $path;
    return $base . $path;
}

function redirect(string $path): never {
    header('Location: ' . (str_starts_with($path, '/') ? app_url($path) : $path));
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
    $count = min(count($_FILES['images']['name']), 30);
    $existing = $pdo->prepare("SELECT COUNT(*) FROM property_media WHERE property_id=? AND file_path NOT LIKE 'demo/villas/%'");
    $existing->execute([$propertyId]);
    $cover = (int)$existing->fetchColumn() === 0 ? 1 : 0;
    $maxOrderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order),-1) FROM property_media WHERE property_id=?');
    $maxOrderStmt->execute([$propertyId]);
    $nextSortOrder = (int)$maxOrderStmt->fetchColumn() + 1;
    if ($cover === 1) { $resetCover = $pdo->prepare('UPDATE property_media SET is_cover=0 WHERE property_id=?'); $resetCover->execute([$propertyId]); }
    $insert = $pdo->prepare('INSERT INTO property_media (property_id,file_path,alt_text,is_cover,sort_order) VALUES (?,?,?,?,?)');
    for ($i=0; $i<$count; $i++) {
        $error = (int)($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE || $error !== UPLOAD_ERR_OK) continue;
        $tmp = (string)($_FILES['images']['tmp_name'][$i] ?? '');
        $size = (int)($_FILES['images']['size'][$i] ?? 0);
        if ($tmp === '' || $size < 1 || $size > 12 * 1024 * 1024 || !is_uploaded_file($tmp)) continue;
        $mime = $finfo->file($tmp);
        if (!isset($allowed[$mime])) continue;
        $filename = bin2hex(random_bytes(18)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($tmp, $directory . '/' . $filename)) continue;
        $relative = 'uploads/properties/' . $filename;
        $alt = trim((string)($_POST['title'] ?? 'Villa fotoğrafı'));
        $insert->execute([$propertyId,$relative,$alt,$cover,$nextSortOrder++]);
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

function delete_property_media(PDO $pdo, int $propertyId, int $mediaId): void {
    $find = $pdo->prepare('SELECT file_path,is_cover FROM property_media WHERE id=? AND property_id=?');
    $find->execute([$mediaId,$propertyId]);
    $media = $find->fetch(PDO::FETCH_ASSOC);
    if (!$media) return;
    $path = (string)$media['file_path'];
    if (str_starts_with($path, 'uploads/properties/')) {
        $full = APP_ROOT . '/public/' . $path;
        if (is_file($full)) @unlink($full);
    }
    $delete = $pdo->prepare('DELETE FROM property_media WHERE id=? AND property_id=?');
    $delete->execute([$mediaId,$propertyId]);
    if ((int)$media['is_cover'] === 1) {
        $next = $pdo->prepare('SELECT id FROM property_media WHERE property_id=? ORDER BY sort_order,id LIMIT 1');
        $next->execute([$propertyId]);
        $nextId = $next->fetchColumn();
        if ($nextId) { $set = $pdo->prepare('UPDATE property_media SET is_cover=1 WHERE id=?'); $set->execute([(int)$nextId]); }
    }
}
