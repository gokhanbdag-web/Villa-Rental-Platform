<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Installer;
use App\Core\Migrator;
use App\Core\PageBuilder;
use App\Core\ThemeDemoImporter;

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

    foreach (['vendors','properties','property_media','bookings','booking_financials','payment_transactions','vendor_payouts','reviews','notifications','cms_pages','navigation_items','page_builder_layouts','theme_demo_imports','theme_demo_layouts','contact_messages','newsletter_subscribers'] as $table) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $check->execute([$table]);
        assertSmoke((int)$check->fetchColumn() === 1, 'Missing required table: ' . $table);
    }

    $homepageBuilderSource = (string)file_get_contents($root . '/views/site/partials/page-builder-home.php');
    assertSmoke(str_contains($homepageBuilderSource, '$viewer=\\App\\Core\\Auth::user();') && !str_contains($homepageBuilderSource, '$viewer=Auth::user();'), 'Homepage builder must resolve the authenticated user through the fully qualified Auth class.');

    $registry = PageBuilder::widgets();
    assertSmoke(isset($registry['image_text'], $registry['features']) && count($registry) >= 47, 'Expected all built-in visual editor widgets to be registered.');
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
            ['id' => 'hero-sanitizer-smoke', 'type' => 'hero', 'style' => $registry['hero']['default_style'], 'settings' => [
                'show_search' => 'false', 'hero_height' => 1200, 'button_url' => 'javascript:alert(1)',
                'untrusted_setting' => 'must-not-persist',
            ]],
        ],
    ];
    $preview = PageBuilder::sanitizePreview('mediterranean', $builderPayload);
    $imageSettings = $preview['widgets'][0]['settings'];
    $featureSettings = $preview['widgets'][1]['settings'];
    $heroSettings = $preview['widgets'][2]['settings'];
    assertSmoke(($imageSettings['media_width'] ?? null) === 67 && ($imageSettings['content_gap'] ?? null) === 48 && ($imageSettings['content_position'] ?? '') === 'left', 'Image-text inspector settings were not validated correctly.');
    assertSmoke(($featureSettings['item_gap'] ?? null) === 26 && ($featureSettings['item_padding'] ?? null) === 28 && ($featureSettings['item_alignment'] ?? '') === 'center', 'Item-card inspector settings were not validated correctly.');
    assertSmoke(($heroSettings['show_search'] ?? true) === false && ($heroSettings['hero_height'] ?? 0) === 900, 'Widget boolean and numeric settings were not normalized consistently.');
    assertSmoke(($heroSettings['button_url'] ?? null) === '' && !array_key_exists('untrusted_setting', $heroSettings), 'Widget setting allowlist did not reject an unsafe URL or unknown field.');

    $adminId = (int)$pdo->query("SELECT id FROM users WHERE email='smoke@example.test'")->fetchColumn();
    PageBuilder::save('mediterranean', 'home', $preview, 'draft', $adminId);
    $savedLayout = PageBuilder::load('mediterranean', 'home');
    assertSmoke(($savedLayout['widgets'][0]['settings']['media_width'] ?? null) === 67, 'Image-text layout settings did not persist.');
    assertSmoke(($savedLayout['widgets'][1]['settings']['item_alignment'] ?? '') === 'center', 'Item-card layout settings did not persist.');
    assertSmoke(($savedLayout['widgets'][2]['settings']['show_search'] ?? true) === false, 'Normalized widget settings did not persist consistently.');

    $expectedThemes = ['mediterranean','modern','prestige','boutique','riviera','alpine','nocturne','kyoto','loft','bohem'];
    $themeManifests = glob($root . '/themes/*/theme.json') ?: [];
    $themeSlugs = [];
    foreach ($themeManifests as $manifestPath) {
        $manifest = json_decode((string)file_get_contents($manifestPath), true);
        if (is_array($manifest) && !empty($manifest['slug'])) $themeSlugs[] = (string)$manifest['slug'];
    }
    sort($themeSlugs);
    $expectedSorted = $expectedThemes;
    sort($expectedSorted);
    assertSmoke($themeSlugs === $expectedSorted, 'Expected all ten built-in theme manifests to be installed and valid.');

    $themeManagerSource = (string)file_get_contents($root . '/app/Core/ThemeManager.php');
    assertSmoke(str_contains($themeManagerSource, '$isSharedDefaultLanding') && str_contains($themeManagerSource, '\'mediterranean\' : $slug'), 'All themes must fall back to the preserved Mediterranean homepage when no published demo layout exists.');
    assertSmoke(str_contains($themeManagerSource, 'rawurlencode($renderSlug) . \'/style.css\'') && str_contains($themeManagerSource, 'e($renderSlug)'), 'Shared default homepage must load the Mediterranean template stylesheet and CSS scope.');
    assertSmoke(str_contains($themeManagerSource, 'flex-wrap:nowrap!important') && str_contains($themeManagerSource, '.nav-logout-entry{flex:0 0 auto!important') && str_contains($themeManagerSource, '.villa-logout-form{display:flex!important'), 'Desktop logout action must remain on the same navigation row across themes.');
    assertSmoke(str_contains($themeManagerSource, 'public static function socialSettings()') && str_contains($themeManagerSource, 'public static function saveSocialSettings(array $input)') && str_contains($themeManagerSource, 'villa-social-bar') && str_contains($themeManagerSource, 'renderSocialIcon'), 'Shared social media settings and header rendering must be available to every theme.');
    assertSmoke(str_contains($themeManagerSource, 'villa-social-address') && str_contains($themeManagerSource, 'villa-social-call-center') && str_contains($themeManagerSource, 'grid-template-columns:repeat(4,minmax(0,1fr))') && str_contains($themeManagerSource, 'max-width:none') && str_contains($themeManagerSource, "\$clean['_address']") && str_contains($themeManagerSource, "\$clean['_call_center']") && str_contains($themeManagerSource, "'title'=>\$callCenterTitle") && str_contains($themeManagerSource, 'e($callCenterTitle)') && str_contains($themeManagerSource, "\$clean['_license']") && str_contains($themeManagerSource, 'villa-social-license') && str_contains($themeManagerSource, 'e($licenseTitle)') && str_contains($themeManagerSource, 'e($licenseNumber)'), 'Shared header strip must span the viewport, use four columns, and support dynamic address/contact/license fields.');
    $themeSettingsViewSource = (string)file_get_contents($root . '/views/admin/themes.php');
    assertSmoke(str_contains($themeSettingsViewSource, 'social[_address][text]') && str_contains($themeSettingsViewSource, 'social[_call_center][title]') && str_contains($themeSettingsViewSource, 'social[_call_center][phone]') && str_contains($themeSettingsViewSource, 'social[_license][title]') && str_contains($themeSettingsViewSource, 'social[_license][number]') && str_contains($themeSettingsViewSource, 'Üst bilgi ayarları') && !str_contains($themeSettingsViewSource, 'social[_call_center][active]') && !str_contains($themeSettingsViewSource, 'social[_address][active]') && !str_contains($themeSettingsViewSource, 'social-admin-active'), 'Theme settings must expose dynamic address/contact fields without visibility toggles.');
    $themeRoutesSource = (string)file_get_contents($root . '/routes/web.php');
    assertSmoke(str_contains($themeRoutesSource, "/admin/themes/social-icons") && str_contains($themeSettingsViewSource, 'social[instagram][url]') && str_contains($themeSettingsViewSource, 'social[instagram][family]') && str_contains($themeSettingsViewSource, 'social[instagram][size]') && !str_contains($themeSettingsViewSource, 'social[instagram][active]'), 'Theme settings must expose social profile URL, icon family and size without visibility toggles.');

    foreach ($expectedThemes as $themeSlug) {
        $demoResult = ThemeDemoImporter::import($themeSlug, $adminId);
        assertSmoke(!$demoResult['already_imported'] && $demoResult['created'] >= 4, 'Theme demo package did not apply all of its independent page layouts: ' . $themeSlug);
        $themeHome = PageBuilder::load($themeSlug, 'home');
        assertSmoke(($themeHome['status'] ?? '') === 'published' && count($themeHome['widgets'] ?? []) >= 6, 'Theme demo did not replace the old homepage with the complete six-section layout: ' . $themeSlug);
        $demoStatus = ThemeDemoImporter::status($themeSlug);
        $expectedDemoVersion = $themeSlug === 'mediterranean' ? '1.2.0' : '1.1.1';
        assertSmoke(($demoStatus['package_version'] ?? '') === $expectedDemoVersion, 'Theme demo import ledger is missing the current package version: ' . $themeSlug);
        if ($themeSlug === 'mediterranean') {
            $medTypes = array_column($themeHome['widgets'], 'type');
            assertSmoke($medTypes === ['hero','destinations','properties','image_text','features','faq','cta'], 'Mediterranean demo must keep its distinctive seven-section homepage sequence.');
        }
        if ($themeSlug === 'mediterranean') {
            $restored = ThemeDemoImporter::reset($themeSlug);
            $oldHome = PageBuilder::load('mediterranean', 'home');
            assertSmoke($restored['restored'] === 1 && ($oldHome['status'] ?? '') === 'draft' && count($oldHome['widgets'] ?? []) === 3, 'Demo reset did not restore the pre-existing homepage layout.');
        }
    }

    // A reset removes untouched imported layouts but preserves a layout edited after import.
    $alpineHome = PageBuilder::load('alpine', 'home');
    $alpineHome['widgets'][0]['settings']['title'] = 'Smoke test: edited by site owner';
    PageBuilder::save('alpine', 'home', $alpineHome, 'published', $adminId);
    $resetResult = ThemeDemoImporter::reset('alpine');
    assertSmoke($resetResult['preserved'] === 1 && $resetResult['removed'] >= 2, 'Demo reset did not preserve the owner-edited layout safely.');
    $preservedAlpine = PageBuilder::load('alpine', 'home');
    assertSmoke(($preservedAlpine['widgets'][0]['settings']['title'] ?? '') === 'Smoke test: edited by site owner', 'Demo reset deleted owner-edited content.');

    // A second migration pass must be safe and leave the migration ledger intact.
    Migrator::run($pdo, $root);
    $migrationCountAfter = (int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    assertSmoke($migrationCountAfter === $migrationCount, 'Second migration pass changed the migration ledger.');

    echo "Installer and builder smoke tests passed: admin, platform mode, {$villaCount} demo villas, {$migrationCount} migrations, required tables, ten themes, ten isolated demo packages, safe demo import/reset, all 47+ widget registrations, image-text/item-card settings, shared widget sanitizer security and preview/save/load, and repeat migration.\n";
} finally {
    if (is_file($configFile)) unlink($configFile);
    if (is_file($lockFile)) unlink($lockFile);
    if (is_dir($root . '/config')) @rmdir($root . '/config');
    if (is_dir($root . '/storage')) @rmdir($root . '/storage');
}
