<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Installer;
use App\Core\Migrator;
use App\Core\ThemeManager;

function assertSmoke(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$root = dirname(__DIR__);
$configFile = $root . '/config/config.php';
$lockFile = $root . '/storage/installed.lock';
if (Installer::isInstalled()) throw new RuntimeException('Smoke test requires a clean checkout.');

try {
    Installer::install([
        'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
        'db_port' => (int)(getenv('DB_PORT') ?: 3306),
        'db_name' => getenv('DB_NAME') ?: 'villa_test',
        'db_user' => getenv('DB_USER') ?: 'root',
        'db_password' => getenv('DB_PASSWORD') ?: 'root',
        'admin_username' => 'smoke_admin',
        'admin_email' => 'smoke@example.test',
        'admin_password' => 'SmokeTestPassword!2026',
        'admin_password_confirm' => 'SmokeTestPassword!2026',
        'mode' => 'multi_vendor',
    ]);

    assertSmoke(Installer::isInstalled(), 'Installer did not create the installation lock.');
    $config = require $configFile;
    $pdo = Installer::connect($config['database']);

    $admin = $pdo->query("SELECT role,status FROM users WHERE email='smoke@example.test' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    assertSmoke(is_array($admin) && $admin['role'] === 'super_admin' && $admin['status'] === 'active', 'Initial administrator was not created correctly.');

    $mode = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn();
    assertSmoke($mode === 'multi_vendor', 'Selected marketplace mode was not saved.');

    $villaCount = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status='published'")->fetchColumn();
    assertSmoke($villaCount >= 30, 'Demo villa seed did not create at least 30 published villas.');

    $migrationCount = (int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    assertSmoke($migrationCount >= 20, 'Expected database migrations were not applied.');

    foreach (['vendors','properties','property_media','bookings','booking_financials','payment_transactions','vendor_payouts','reviews','notifications','cms_pages','navigation_items','contact_messages','newsletter_subscribers'] as $table) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $check->execute([$table]);
        assertSmoke((int)$check->fetchColumn() === 1, 'Missing required table: ' . $table);
    }

    $themeManifests = glob($root . '/themes/*/theme.json') ?: [];
    assertSmoke(count($themeManifests) === 1 && basename(dirname($themeManifests[0])) === 'default', 'Only the default theme manifest may remain installed.');
    assertSmoke(array_keys(ThemeManager::installed()) === ['default'] && ThemeManager::activeSlug() === 'default', 'Theme manager must expose and activate only the default theme.');
    $defaultManifestPath = $root . '/themes/default/theme.json';
    $defaultManifest = json_decode((string)file_get_contents($defaultManifestPath), true);
    assertSmoke(is_array($defaultManifest) && ($defaultManifest['slug'] ?? '') === 'default', 'The default base theme manifest is missing or invalid.');
    foreach (['style.css','templates/landing.php','templates/listing.php','templates/property.php','templates/destinations.php','templates/booking.php','templates/page.php','templates/account.php'] as $themeFile) {
        assertSmoke(is_file($root . '/themes/default/' . $themeFile), 'Missing default theme file: ' . $themeFile);
    }
    assertSmoke(!is_file($root . '/app/Core/PageBuilder.php') && !is_file($root . '/app/Core/ThemeDemoImporter.php') && !is_file($root . '/views/admin/builder.php') && !is_file($root . '/views/site/partials/page-builder-home.php'), 'Page Builder and widget implementation files must be removed.');

    $themeManagerSource = (string)file_get_contents($root . '/app/Core/ThemeManager.php');
    assertSmoke(str_contains($themeManagerSource, "return 'default';") && str_contains($themeManagerSource, "self::root() . '/default'"), 'The default theme must be the only active theme.');
    $defaultThemeCssSource = (string)file_get_contents($root . '/themes/default/style.css');
    assertSmoke(str_contains($themeManagerSource, 'rawurlencode($renderSlug) . \'/style.css\'') && str_contains($defaultThemeCssSource, 'body[data-villa-theme]{background-color:#fff!important;}'), 'Default theme assets and the shared white body background must be enforced by the theme stylesheet.');
    assertSmoke(str_contains($themeManagerSource, 'public static function socialSettings()') && str_contains($themeManagerSource, 'public static function saveSocialSettings(array $input)') && str_contains($themeManagerSource, 'villa-social-bar') && str_contains($themeManagerSource, 'renderSocialIcon'), 'Shared top information settings and rendering must remain available.');
    assertSmoke(!str_contains($themeManagerSource, 'PageBuilder') && !str_contains($themeManagerSource, 'ThemeDemoImporter') && !str_contains($themeManagerSource, 'ZipArchive'), 'Theme manager must not contain builder, demo importer or theme package upload dependencies.');

    $themeSettingsViewSource = (string)file_get_contents($root . '/views/admin/header-settings.php');
    assertSmoke(str_contains($themeSettingsViewSource, 'social[_address][text]') && str_contains($themeSettingsViewSource, 'social[_call_center][title]') && str_contains($themeSettingsViewSource, 'social[_call_center][phone]') && str_contains($themeSettingsViewSource, 'social[_license][title]') && str_contains($themeSettingsViewSource, 'social[_license][number]') && str_contains($themeSettingsViewSource, 'Üst Bilgi Ayarları'), 'Shared top information settings must remain editable.');
    $sharedTopbarPath = $root . '/themes/default/partials/shared-topbar.php';
    assertSmoke(is_file($sharedTopbarPath), 'The shared top information bar must live in one reusable theme partial.');
    assertSmoke(str_contains($themeManagerSource, "'/themes/default/partials/shared-topbar.php'") && !str_contains($themeManagerSource, 'villa-mobile-single-column-global'), 'Shared topbar markup and global responsive CSS must not be duplicated in ThemeManager.');
    $obsoleteTables = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('page_builder_layouts','theme_demo_imports','theme_demo_layouts')")->fetchColumn();
    assertSmoke($obsoleteTables === 0, 'Obsolete Page Builder and theme demo tables must be removed by migrations.');
    $archiveTable = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='legacy_cleanup_archive'")->fetchColumn();
    assertSmoke($archiveTable === 1, 'Legacy Page Builder records must be archived before obsolete tables are removed.');
    $obsoleteNavigation = (int)$pdo->query("SELECT COUNT(*) FROM navigation_items WHERE url LIKE '/demo-theme/%' OR (label='Demolar' AND url='#')")->fetchColumn();
    assertSmoke($obsoleteNavigation === 0, 'Obsolete demo theme navigation entries must be removed.');

    $rootHtaccess = (string)file_get_contents($root . '/.htaccess');
    assertSmoke(!str_contains($rootHtaccess, 'public/demo/') && str_contains($rootHtaccess, 'public/uploads/'), 'Root rewrite rules must not point to the removed demo asset directory and must retain upload routing.');

    $themeRoutesSource = (string)file_get_contents($root . '/routes/web.php');
    $adminShellSource = (string)file_get_contents($root . '/index.php');
    assertSmoke(str_contains($themeRoutesSource, "/admin/header-settings/social-icons") && !str_contains($themeRoutesSource, 'PageBuilder') && !str_contains($themeRoutesSource, '/admin/builder') && !str_contains($adminShellSource, '/admin/builder'), 'Builder routes and navigation must be removed while shared top information settings remain available.');

    // A second migration pass must be safe and leave the migration ledger intact.
    Migrator::run($pdo, $root);
    $migrationCountAfter = (int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    assertSmoke($migrationCountAfter === $migrationCount, 'Second migration pass changed the migration ledger.');

    echo "Installer smoke tests passed: admin, platform mode, {$villaCount} demo villas, {$migrationCount} migrations, required tables, single default theme, removed builder/widget code, shared top information settings, and repeat migration.\n";
} finally {
    if (is_file($configFile)) unlink($configFile);
    if (is_file($lockFile)) unlink($lockFile);
    if (is_dir($root . '/config')) @rmdir($root . '/config');
    if (is_dir($root . '/storage')) @rmdir($root . '/storage');
}
