<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class Installer
{
    public static function isInstalled(): bool
    {
        $root = dirname(__DIR__, 2);
        return is_file($root . '/config/config.php') && is_file($root . '/storage/installed.lock');
    }

    public static function connect(array $db): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $db['host'], (int)($db['port'] ?? 3306), $db['name']);
        return new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function install(array $input): void
    {
        if (self::isInstalled()) throw new \RuntimeException('Sistem zaten kurulu.');
        $mode = (string)($input['mode'] ?? '');
        if (!in_array($mode, ['single_vendor', 'multi_vendor'], true)) throw new \InvalidArgumentException('İşletme modeli geçersiz.');
        $username = trim((string)($input['admin_username'] ?? ''));
        $email = filter_var(trim((string)($input['admin_email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string)($input['admin_password'] ?? '');
        if (strlen($username) < 3 || !$email || strlen($password) < 12) {
            throw new \InvalidArgumentException('Yönetici bilgilerini kontrol edin. Şifre en az 12 karakter olmalıdır.');
        }
        if (!hash_equals($password, (string)($input['admin_password_confirm'] ?? ''))) {
            throw new \InvalidArgumentException('Şifre tekrarı eşleşmiyor.');
        }

        $db = [
            'host' => trim((string)($input['db_host'] ?? '')),
            'port' => (int)($input['db_port'] ?? 3306),
            'name' => trim((string)($input['db_name'] ?? '')),
            'user' => trim((string)($input['db_user'] ?? '')),
            'password' => (string)($input['db_password'] ?? ''),
        ];
        if ($db['host'] === '' || $db['name'] === '' || $db['user'] === '' || $db['port'] < 1 || $db['port'] > 65535) {
            throw new \InvalidArgumentException('Veritabanı bağlantı alanlarını kontrol edin.');
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $db['name'])) throw new \InvalidArgumentException('Veritabanı adı yalnızca harf, rakam ve alt çizgi içerebilir.');

        $root = dirname(__DIR__, 2);
        foreach (['config', 'storage'] as $directory) {
            $path = $root . '/' . $directory;
            if (!is_dir($path) && !mkdir($path, 0750, true) && !is_dir($path)) {
                throw new \RuntimeException($directory . ' dizini oluşturulamadı.');
            }
            if (!is_writable($path)) throw new \RuntimeException($directory . ' dizini PHP tarafından yazılabilir olmalıdır.');
        }

        $pdo = self::connect($db);
        $schemaFiles = [$root . '/database/schema.sql'];
        $migrations = glob($root . '/database/migrations/*.sql') ?: [];
        sort($migrations, SORT_STRING);
        $schemaFiles = array_merge($schemaFiles, $migrations);

        try {
            foreach ($schemaFiles as $file) {
                if (!is_file($file) || !is_readable($file)) throw new \RuntimeException('Şema dosyası okunamadı: ' . basename($file));
                $sql = file_get_contents($file);
                if ($sql === false) throw new \RuntimeException('Şema dosyası okunamadı.');
                foreach (preg_split('/;\\s*(?:\\r?\\n|$)/', trim($sql)) as $statement) {
                    $statement = trim($statement);
                    if ($statement !== '') $pdo->exec($statement);
                }
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO users (username,email,password_hash,role,status,created_at) VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE username=VALUES(username),email=VALUES(email),password_hash=VALUES(password_hash),role=\'super_admin\',status=\'active\'');
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), 'super_admin', 'active']);
            $settings = [
                'app.mode' => $mode,
                'app.locale' => 'tr',
                'app.timezone' => 'Europe/Istanbul',
                'app.installed_at' => date(DATE_ATOM),
                'app.currency' => 'TRY',
            ];
            $stmt = $pdo->prepare('INSERT INTO settings (setting_key,setting_value,autoload) VALUES (?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
            foreach ($settings as $key => $value) $stmt->execute([$key, $value]);
            $pdo->commit();

            $config = ['database' => $db, 'app' => ['timezone' => 'Europe/Istanbul']];
            $php = "<?php\ndeclare(strict_types=1);\nreturn " . var_export($config, true) . ";\n";
            if (file_put_contents($root . '/config/config.php', $php, LOCK_EX) === false) {
                throw new \RuntimeException('Yapılandırma dosyası yazılamadı.');
            }
            if (file_put_contents($root . '/storage/installed.lock', date(DATE_ATOM), LOCK_EX) === false) {
                throw new \RuntimeException('Kurulum kilidi oluşturulamadı.');
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
