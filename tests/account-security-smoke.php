<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$routes = (string)file_get_contents($root . '/routes/web.php');
$auth = (string)file_get_contents($root . '/app/Core/Auth.php');
$login = (string)file_get_contents($root . '/views/auth/login.php');
$profile = (string)file_get_contents($root . '/views/account/profile.php');
$migration = (string)file_get_contents($root . '/database/migrations/049_account_profile_security.sql');
$forgot = (string)file_get_contents($root . '/themes/default/templates/pages/auth/forgot-password.php');
$reset = (string)file_get_contents($root . '/themes/default/templates/pages/auth/reset-password.php');

$checks = [
    'profile route and CSRF-protected updates' => str_contains($routes, "$router->get('/account/profile'") && str_contains($routes, "verify_csrf();"),
    'current password verification' => str_contains($routes, 'password_verify($current,$hash)'),
    'password reset request and completion routes' => str_contains($routes, "$router->post('/forgot-password'") && str_contains($routes, "$router->post('/reset-password'"),
    'hashed random recovery tokens' => str_contains($routes, "bin2hex(random_bytes(32))") && str_contains($routes, "hash('sha256',$token)"),
    'single-use expiring recovery tokens' => str_contains($routes, "used_at IS NULL AND expires_at>NOW()") && str_contains($routes, "SET used_at=NOW()"),
    'generic recovery response and throttling' => str_contains($routes, 'Bu e-posta adresi sistemde kayıtlıysa') && str_contains($routes, 'account_recovery_limits'),
    'email change verification' => str_contains($routes, "purpose='email_change'") && str_contains($routes, '/account/email/verify'),
    'session revocation version' => str_contains($auth, 'session_version') && str_contains($migration, 'ADD COLUMN session_version'),
    'login recovery link' => str_contains($login, '/forgot-password'),
    'profile and password forms' => str_contains($profile, '/account/profile') && str_contains($profile, '/account/password'),
    'forgot and reset templates' => str_contains($forgot, '/forgot-password') && str_contains($reset, 'password_confirm'),
];

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}
fwrite(STDOUT, "Account profile and security smoke tests passed (" . count($checks) . " checks).\n");
