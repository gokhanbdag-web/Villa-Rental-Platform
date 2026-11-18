<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$rules = file_get_contents($root . '/.htaccess');
if (!is_string($rules)) {
    fwrite(STDERR, "Root .htaccess is missing.\n");
    exit(1);
}

if (preg_match('~RewriteRule\\s+\\^\\(([^)]+)\\)/\\s+-\\s+\\[F,L,NC\\]~', $rules, $match) !== 1) {
    fwrite(STDERR, "Root document-root protection rule is missing.\n");
    exit(1);
}

$protected = array_fill_keys(explode('|', $match[1]), true);
$required = ['app', 'config', 'database', 'storage', 'views', 'routes', 'scripts', 'tests', 'docs'];
$missing = array_values(array_filter($required, static fn(string $directory): bool => !isset($protected[$directory])));
if ($missing !== []) {
    fwrite(STDERR, 'Root document-root protection is missing: ' . implode(', ', $missing) . PHP_EOL);
    exit(1);
}

$protectPosition = strpos($rules, $match[0]);
$uploadPosition = strpos($rules, 'RewriteRule ^uploads/');
$fallbackPosition = strpos($rules, 'RewriteRule ^ index.php');
if ($protectPosition === false || $uploadPosition === false || $fallbackPosition === false
    || $protectPosition > $uploadPosition || $protectPosition > $fallbackPosition) {
    fwrite(STDERR, "Private-directory denial must run before upload mapping and front-controller fallback.\n");
    exit(1);
}

fwrite(STDOUT, "Root document-root hardening smoke test passed.\n");
