<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * Records only slow SQL execution metadata. Bound values are never logged.
 * This keeps production diagnostics useful without exposing credentials or
 * personal data from query parameters.
 */
final class TimedPDOStatement extends \PDOStatement
{
    protected function __construct() {}

    public function execute(?array $params = null): bool
    {
        $started = microtime(true);
        try {
            return parent::execute($params);
        } finally {
            $elapsedMs = (microtime(true) - $started) * 1000;
            if ($elapsedMs >= 200) {
                $sql = preg_replace('/\\s+/', ' ', trim($this->queryString));
                $sql = is_string($sql) ? substr($sql, 0, 180) : '';
                $record = json_encode([
                    'duration_ms' => round($elapsedMs, 1),
                    'sql' => $sql,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if (is_string($record) && defined('APP_STORAGE') && is_dir(APP_STORAGE)) {
                    @file_put_contents(APP_STORAGE . '/sql-performance.log', $record . PHP_EOL, FILE_APPEND | LOCK_EX);
                }
                error_log('[VILLA_SQL] ' . (is_string($record) ? $record : '{}'));
            }
        }
    }
}

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
            PDO::ATTR_STATEMENT_CLASS => [TimedPDOStatement::class, []],
        ]);
    }

    public static function install(array $input): void
    {
        if (self::isInstalled()) throw new \RuntimeException('The application is already installed.');
        $mode = (string)($input['mode'] ?? '');
        $installDemo = (string)($input['install_demo'] ?? '') === '1';
        if (!in_array($mode, ['single_vendor', 'multi_vendor'], true)) throw new \InvalidArgumentException('Invalid business model.');
        $username = trim((string)($input['admin_username'] ?? ''));
        $email = filter_var(trim((string)($input['admin_email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string)($input['admin_password'] ?? '');
        if (strlen($username) < 3 || !$email || strlen($password) < 12) {
            throw new \InvalidArgumentException('Check the administrator details. Password must be at least 12 characters.');
        }
        if (!hash_equals($password, (string)($input['admin_password_confirm'] ?? ''))) {
            throw new \InvalidArgumentException('Passwords do not match.');
        }

        $db = [
            'host' => trim((string)($input['db_host'] ?? '')),
            'port' => (int)($input['db_port'] ?? 3306),
            'name' => trim((string)($input['db_name'] ?? '')),
            'user' => trim((string)($input['db_user'] ?? '')),
            'password' => (string)($input['db_password'] ?? ''),
        ];
        if ($db['host'] === '' || !preg_match('/^[A-Za-z0-9._:-]+$/', $db['host']) || $db['name'] === '' || $db['user'] === '' || $db['port'] < 1 || $db['port'] > 65535) {
            throw new \InvalidArgumentException('Check the database connection fields.');
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $db['name'])) throw new \InvalidArgumentException('Database name may contain only letters, digits, and underscores.');

        $root = dirname(__DIR__, 2);
        foreach (['config', 'storage'] as $directory) {
            $path = $root . '/' . $directory;
            if (!is_dir($path) && !mkdir($path, 0750, true) && !is_dir($path)) {
                throw new \RuntimeException($directory . ' directory could not be created.');
            }
            if (!is_writable($path)) throw new \RuntimeException($directory . ' directory must be writable by PHP.');
        }

        $pdo = self::connect($db);
        try {
            $schemaFile = $root . '/database/schema.sql';
            if (!is_file($schemaFile) || !is_readable($schemaFile)) throw new \RuntimeException('Database schema file could not be read.');
            $schemaSql = file_get_contents($schemaFile);
            if ($schemaSql === false) throw new \RuntimeException('Database schema file could not be read.');
            foreach (preg_split('/;\\s*(?:\\r?\
|$)/', trim($schemaSql)) as $statement) {
                $statement = trim($statement);
                if ($statement !== '') $pdo->exec($statement);
            }
            Migrator::run($pdo, $root);

            // Seed the selected storefront model after the shared schema and content.
            // Single-vendor installations retain platform-owned properties.
            // Multi-vendor installations assign the demo portfolio to a dedicated
            // approved demo vendor backed by a separate account.
            if ($mode === 'multi_vendor' && $installDemo) {
                $demoPassword = bin2hex(random_bytes(24));
                $demoEmail = 'demo-vendor@localhost.invalid';
                $demoUsername = 'demo_vendor';
                $demoUser = $pdo->prepare("INSERT INTO users (username,email,password_hash,role,status,created_at) VALUES (?,?,?,'vendor','active',NOW()) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
                $demoUser->execute([$demoUsername, $demoEmail, password_hash($demoPassword, PASSWORD_DEFAULT)]);
                $demoUserId = (int)$pdo->lastInsertId();
                $demoVendor = $pdo->prepare("INSERT INTO vendors (user_id,business_name,status,created_at) VALUES (?,'Demo Villa Collection','approved',NOW()) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
                $demoVendor->execute([$demoUserId]);
                $demoVendorId = (int)$pdo->lastInsertId();
                $pdo->prepare('UPDATE properties SET vendor_id=? WHERE vendor_id IS NULL')->execute([$demoVendorId]);
            }

            // Demo seed migrations run before installer options are known. Remove their
            // sample content on clean installations, without touching structural tables.
            if (!$installDemo) {
                $pdo->exec('DELETE FROM cms_page_layouts');
                $pdo->exec('DELETE FROM navigation_items');
                $pdo->exec('DELETE FROM cms_pages');
                $pdo->exec('DELETE FROM blog_posts');
                $pdo->exec('DELETE FROM properties');
            } else {
                $home=$pdo->prepare("SELECT id FROM cms_pages WHERE slug='home' AND language_code='en' LIMIT 1");
                $home->execute();
                $homeId=(int)($home->fetchColumn() ?: 0);
                if($homeId===0){
                    $add=$pdo->prepare("INSERT INTO cms_pages(language_code,title,slug,content,status,meta_title,meta_description) VALUES('en','Home','home','','published','Home | VillaStay','Explore private villa rentals')");
                    $add->execute();
                    $homeId=(int)$pdo->lastInsertId();
                }
                // Use the same reusable homepage template exposed to the page editor.
                // Avoid a second, divergent hard-coded installer homepage.
                $templateStmt=$pdo->prepare("SELECT layout_json FROM cms_page_templates WHERE slug='villa-classic' LIMIT 1");
                $templateStmt->execute();
                $templateJson=$templateStmt->fetchColumn();
                $demoLayout=is_string($templateJson) ? json_decode($templateJson,true) : null;
                if(!is_array($demoLayout)||!isset($demoLayout['rows'])){
                    throw new \RuntimeException('Default homepage template is missing or invalid.');
                }
                $layoutStmt=$pdo->prepare('INSERT INTO cms_page_layouts(page_key,language_code,layout_json) VALUES(?,?,?) ON DUPLICATE KEY UPDATE layout_json=layout_json');
                $layoutStmt->execute(['page:'.$homeId,'en',json_encode($demoLayout,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO users (username,email,password_hash,role,status,created_at) VALUES (?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE username=VALUES(username),email=VALUES(email),password_hash=VALUES(password_hash),role=\'super_admin\',status=\'active\'');
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), 'super_admin', 'active']);
            $settings = [
                'app.mode' => $mode,
                'app.demo_enabled' => $installDemo ? '1' : '0',
                'cms.homepage_id' => $installDemo ? (string)$homeId : '0',
                'app.locale' => 'en',
                'app.timezone' => 'Europe/Istanbul',
                'app.installed_at' => date(DATE_ATOM),
                'app.currency' => 'TRY',
            ];
            $stmt = $pdo->prepare('INSERT INTO settings (setting_key,setting_value,autoload) VALUES (?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
            foreach ($settings as $key => $value) $stmt->execute([$key, $value]);
            $pdo->commit();

            $config = ['database' => $db, 'app' => ['timezone' => 'Europe/Istanbul']];
            $php = "<?php
declare(strict_types=1);
return " . var_export($config, true) . ";
";
            if (file_put_contents($root . '/config/config.php', $php, LOCK_EX) === false) {
                throw new \RuntimeException('Configuration file could not be written.');
            }
            @chmod($root . '/config/config.php', 0640);
            if (file_put_contents($root . '/storage/installed.lock', date(DATE_ATOM), LOCK_EX) === false) {
                throw new \RuntimeException('Installation lock file could not be created.');
            }
            @chmod($root . '/storage/installed.lock', 0640);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
