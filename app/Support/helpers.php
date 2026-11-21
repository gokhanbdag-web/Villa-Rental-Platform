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

    // Never infer a URL prefix from APP_ROOT's physical folder name. A route
    // may legitimately begin with the same segment (for example /villa/{slug}),
    // so that guess can strip part of the route and cause a false 404. For
    // aliased document roots, the SCRIPT_NAME / front-controller fallback
    // below is safer; hosts with unusual mappings can set an explicit base path.
    
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


/**
 * Build a browser URL for files stored below APP_ROOT/public/uploads.
 * Supports both project-root and public/ document-root hosting without
 * relying on rewrite rules or assuming that "public" is URL-visible.
 */
function uploaded_media_url(string $storedPath): string {
    // Uploads are always exposed through the stable /uploads URL.
    // The project-root .htaccess maps that URL to public/uploads when the
    // repository root is the document root; when public/ is the document
    // root, the same URL addresses the file directly. Avoid emitting
    // /public/uploads, which bypasses the application's intended mapping
    // and breaks installations mounted below a parent document root.
    $path = str_replace(chr(92), '/', trim($storedPath));
    $path = ltrim($path, '/');
    if (str_starts_with($path, 'public/')) $path = substr($path, 7);
    if (!str_starts_with($path, 'uploads/')) return '';
    $parts = explode('/', $path);
    if (in_array('', $parts, true) || in_array('.', $parts, true) || in_array('..', $parts, true)) return '';

    return app_url('/' . implode('/', array_map('rawurlencode', $parts)));
}

function app_absolute_url(string $path = '/'): string {
    $host = trim((string)($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '' || !preg_match('/^(?=.{1,253}$)[a-z0-9.-]+(?::[0-9]{1,5})?$/i', $host)) {
        throw new RuntimeException('Ödeme bildirimi için geçerli site alan adı belirlenemedi.');
    }
    $hostName = (string)(parse_url('https://' . $host, PHP_URL_HOST) ?? '');
    if ($hostName === '' || (filter_var($hostName, FILTER_VALIDATE_IP) === false && filter_var($hostName, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false)) {
        throw new RuntimeException('Ödeme bildirimi için site alan adı doğrulanamadı.');
    }
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
    if (!$https) {
        throw new RuntimeException('iyzico ödeme bildirimi için HTTPS bağlantısı gereklidir.');
    }
    return 'https://' . $host . app_url($path);
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

/**
 * Prepare an upload directory with shared-hosting-safe permissions.
 * Never grants world-write access and never attempts to change file ownership.
 */
function ensure_upload_directory(string $directory): void {
    $uploadRoot = rtrim(str_replace('\\\\', '/', APP_ROOT . '/public/uploads'), '/');
    $target = rtrim(str_replace('\\\\', '/', $directory), '/');
    if ($target !== $uploadRoot && !str_starts_with($target, $uploadRoot . '/')) {
        throw new RuntimeException('Yükleme klasörü izin verilen uploads dizininin dışında.');
    }
    if (!is_dir($uploadRoot)) {
        if (!@mkdir($uploadRoot, 0755, true) && !is_dir($uploadRoot)) {
            throw new RuntimeException('uploads klasörü oluşturulamadı. Hosting dosya izinlerini ve klasör sahipliğini kontrol edin.');
        }
    }
    $relative = trim(substr($target, strlen($uploadRoot)), '/');
    $current = $uploadRoot;
    $directories = [$current];
    if ($relative !== '') {
        foreach (explode('/', $relative) as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                throw new RuntimeException('Yükleme klasörü yolu geçersiz.');
            }
            $current .= '/' . $part;
            $directories[] = $current;
        }
    }
    foreach ($directories as $path) {
        if (!is_dir($path)) {
            if (!@mkdir($path, 0755) && !is_dir($path)) {
                throw new RuntimeException('Yükleme klasörü oluşturulamadı: ' . $path . '. PHP kullanıcısının üst klasöre yazma izni olmalı.');
            }
            @chmod($path, 0755);
        }
        if (!is_writable($path)) {
            @chmod($path, 0755);
        }
        if (!is_dir($path) || !is_writable($path)) {
            error_log('Upload directory is not writable by PHP: ' . $path . ' (perms=' . substr(sprintf('%o', (int)@fileperms($path)), -4) . ')');
            throw new RuntimeException('Yükleme klasörüne PHP tarafından yazılamıyor: ' . $path . '. Klasör sahipliği PHP çalışma kullanıcısıyla eşleşmeli; PHP güvenlik nedeniyle sahipliği değiştiremez.');
        }
    }
}

/** Normalize uploaded files to web-readable, non-world-writable permissions. */
function normalize_uploaded_file_permissions(string $file): void {
    if (!is_file($file)) {
        throw new RuntimeException('Yüklenen dosya diskte bulunamadı.');
    }
    if (!@chmod($file, 0644) && !is_readable($file)) {
        throw new RuntimeException('Yüklenen dosyanın okuma izinleri ayarlanamadı.');
    }
    if (!is_readable($file)) {
        throw new RuntimeException('Yüklenen dosya PHP tarafından okunamıyor.');
    }
}

function store_property_uploads(PDO $pdo, int $propertyId): void {
    if (empty($_FILES['images']) || !is_array($_FILES['images']['name'] ?? null)) return;
    $directory = APP_ROOT . '/public/uploads/properties';
    ensure_upload_directory($directory);
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
        $target = $directory . '/' . $filename;
        if (!move_uploaded_file($tmp, $target)) continue;
        try {
            normalize_uploaded_file_permissions($target);
        } catch (Throwable $e) {
            @unlink($target);
            throw $e;
        }
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

/** Translate application-owned interface labels before escaping for their output context. */
function villa_ui(string $label): string
{
    if (\App\Core\Translator::locale() !== 'en') return $label;
    static $english;
    $english ??= require __DIR__ . '/ui-en.php';
    return $english[$label] ?? $label;
}
