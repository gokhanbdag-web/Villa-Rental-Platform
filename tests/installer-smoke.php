<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Installer;
use App\Core\Migrator;
use App\Core\PageBuilder;

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

    foreach (['vendors','properties','property_media','bookings','booking_financials','payment_transactions','vendor_payouts','reviews','notifications','cms_pages','navigation_items','page_builder_layouts','contact_messages','newsletter_subscribers'] as $table) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $check->execute([$table]);
        assertSmoke((int)$check->fetchColumn() === 1, 'Missing required table: ' . $table);
    }

    $registry = PageBuilder::widgets();
    assertSmoke(isset($registry['image_text'], $registry['features']), 'Expected visual editor widgets are missing.');
    $builderPayload = [
        'widgets' => [
            ['id' => 'image-text-smoke', 'type' => 'image_text', 'style' => $registry['image_text']['default_style'], 'settings' => [
                'media_width' => 67, 'content_gap' => 48, 'media_radius' => 22, 'media_ratio' => '16:9',
                'content_position' => 'left', 'button_alignment' => 'center',
            ]],
            ['id' => 'features-smoke', 'type' => 'features', 'style' => $registry['features']['default_style'], 'settings' => [
                'columns' => 2, 'item_gap' => 26, 'item_radius' => 18, 'item_padding' => 28,
                'item_icon_size' => 32, 'item_alignment' => 'center',
            ]],
        ],
    ];
    $preview = PageBuilder::sanitizePreview('mediterranean', $builderPayload);
    $imageSettings = $preview['widgets'][0]['settings'];
    $featureSettings = $preview['widgets'][1]['settings'];
    assertSmoke(($imageSettings['media_width'] ?? null) === 67 && ($imageSettings['content_gap'] ?? null) === 48 && ($imageSettings['content_position'] ?? '') === 'left', 'Image-text inspector settings were not validated correctly.');
    assertSmoke(($featureSettings['item_gap'] ?? null) === 26 && ($featureSettings['item_padding'] ?? null) === 28 && ($featureSettings['item_alignment'] ?? '') === 'center', 'Item-card inspector settings were not validated correctly.');

    $adminId = (int)$pdo->query("SELECT id FROM users WHERE email='smoke@example.test'")->fetchColumn();
    PageBuilder::save('mediterranean', 'home', $preview, 'draft', $adminId);
    $savedLayout = PageBuilder::load('mediterranean', 'home');
    assertSmoke(($savedLayout['widgets'][0]['settings']['media_width'] ?? null) === 67, 'Image-text layout settings did not persist.');
    assertSmoke(($savedLayout['widgets'][1]['settings']['item_alignment'] ?? '') === 'center', 'Item-card layout settings did not persist.');

    $themeCount = count(glob($root . '/themes/*/theme.json') ?: []);
    assertSmoke($themeCount === 4, 'Expected four built-in themes.');

    // A second migration pass must be safe and leave the migration ledger intact.
    Migrator::run($pdo, $root);
    $migrationCountAfter = (int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    assertSmoke($migrationCountAfter === $migrationCount, 'Second migration pass changed the migration ledger.');

    echo "Installer and builder smoke tests passed: admin, platform mode, {$villaCount} demo villas, {$migrationCount} migrations, required tables, four themes, image-text and item-card settings preview/save/load, and repeat migration.\n";
} finally {
    if (is_file($configFile)) unlink($configFile);
    if (is_file($lockFile)) unlink($lockFile);
    if (is_dir($root . '/config')) @rmdir($root . '/config');
    if (is_dir($root . '/storage')) @rmdir($root . '/storage');
}
