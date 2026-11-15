<?php
declare(strict_types=1);

// The shared admin shell must display the exact application VERSION file value.
$root = dirname(__DIR__);
$shell = file_get_contents($root . '/themes/default/layouts/admin-shell.php');
$version = trim((string) file_get_contents($root . '/VERSION'));

if (!is_string($shell) || $version === '') {
    throw new RuntimeException('Admin shell or application VERSION file could not be read.');
}
if (!str_contains($shell, "file_get_contents(dirname(__DIR__, 3) . '/VERSION')")) {
    throw new RuntimeException('Admin shell must read the application version from the root VERSION file.');
}
if (!str_contains($shell, 'vr-admin-version') || !str_contains($shell, "e(\$appVersion)")) {
    throw new RuntimeException('Admin sidebar must render the current application version.');
}
echo "Admin panel application version display smoke test passed (VERSION {$version}).\n";
