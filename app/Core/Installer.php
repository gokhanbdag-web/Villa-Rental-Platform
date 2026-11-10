<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class Installer
{
    public static function isInstalled(): bool
    {
        $config = dirname(__DIR__, 2) . '/config/config.php';
        $lock = dirname(__DIR__, 2) . '/storage/installed.lock';
        return is_file($config) && is_file($lock);
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
        $mode = $input['mode'] ?? '';
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
        if ($db['host'] === '' || $db['name'] === '' || $db['user'] === '') {
            throw new \InvalidArgumentException('Veritabanı alanları zorunludur.');
        }

        $pdo = self::connect($db);
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');
        if ($schema === false) throw new \RuntimeException('Veritabanı şeması okunamadı.');
        $pdo->beginTransaction();
        try {
            foreach (preg_split('/;\s*(?:\r?\n|$)/', trim($schema)) as $statement) {
                $statement = trim($statement);
                if ($statement !== '') $pdo->exec($statement);
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username,email,password_hash,role,status,created_at) VALUES (?,?,?,?,?,NOW())');
            $stmt->execute([$username, $email, $hash, 'super_admin', 'active']);
            $settings = [
                'app.mode' => $mode,
                'app.locale' => 'tr',
                'app.timezone' => 'Europe/Istanbul',
                'app.installed_at' => date(DATE_ATOM),
                'app.currency' => 'TRY'
            ];
            $stmt = $pdo->prepare('INSERT INTO settings (setting_key,setting_value,autoload) VALUES (?,?,1)');
            foreach ($settings as $key => $value) $stmt->execute([$key, $value]);
            $pdo->commit();

            $root = dirname(__DIR__, 2);
            if (!is_dir($root . '/config')) mkdir($root . '/config', 0750, true);
            if (!is_dir($root . '/storage')) mkdir($root . '/storage', 0750, true);
            $config = ['database' => $db, 'app' => ['timezone' => 'Europe/Istanbul']];
            $php = "<?php\ndeclare(strict_types=1);\nreturn " . var_export($config, true) . ";\n";
            if (file_put_contents($root . '/config/config.php', $php, LOCK_EX) === false) {
                throw new \RuntimeException('Yapılandırma dosyası yazılamadı. config dizinini yazılabilir yapın.');
            }
            file_put_contents($root . '/storage/installed.lock', date(DATE_ATOM), LOCK_EX);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
