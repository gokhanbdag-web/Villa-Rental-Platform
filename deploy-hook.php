<?php
declare(strict_types=1);

/**
 * Authenticated GitHub push receiver.
 * Secrets and the host-side deployment script remain outside the web root.
 */
$base = '/opt/villa-deploy';
$secretFile = $base . '/webhook.secret';
$script = $base . '/deploy.sh';
$runner = __DIR__ . '/scripts/deploy-runner.php';
$log = $base . '/deploy.log';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$event = (string)($_SERVER['HTTP_X_GITHUB_EVENT'] ?? '');
$signature = (string)($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
$body = file_get_contents('php://input');

if (!in_array($event, ['ping', 'push'], true) ||
    !is_string($body) || strlen($body) > 1048576 ||
    !is_file($secretFile) || !is_readable($secretFile)) {
    http_response_code(400);
    exit;
}

$secret = trim((string)file_get_contents($secretFile));
$expected = 'sha256=' . hash_hmac('sha256', $body, $secret);
if ($secret === '' || !hash_equals($expected, $signature)) {
    http_response_code(403);
    exit;
}

if ($event === 'ping') {
    http_response_code(200);
    exit('Villa webhook is ready');
}

$data = json_decode($body, true);
if (!is_array($data)) {
    http_response_code(400);
    exit;
}
if (($data['repository']['full_name'] ?? '') !== 'gokhanbdag-web/Villa-Rental-Platform') {
    http_response_code(403);
    exit('Repository not allowed');
}
if (($data['ref'] ?? '') !== 'refs/heads/main') {
    http_response_code(202);
    exit('Ignored');
}

$commit = strtolower((string)($data['after'] ?? ''));
if (!preg_match('/^[a-f0-9]{40}$/', $commit) || $commit === str_repeat('0', 40)) {
    http_response_code(400);
    exit('Invalid push commit');
}
if (!is_file($script) || !is_executable($script) || !is_file($runner)) {
    http_response_code(500);
    exit('Deployment runner or script unavailable');
}

$host = (string)($_SERVER['HTTP_HOST'] ?? '');
if ($host === '' || preg_match('/[^a-zA-Z0-9.\-:\[\]]/', $host)) {
    http_response_code(400);
    exit('Invalid host');
}
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$healthUrl = $scheme . '://' . $host . rtrim(dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';

$php = '/usr/local/lsws/lsphp85/bin/php';
if (!is_executable($php)) {
    $php = PHP_BINARY;
}
$command = '/usr/bin/nohup ' . escapeshellarg($php) . ' '
    . escapeshellarg($runner) . ' '
    . escapeshellarg($commit) . ' '
    . escapeshellarg($healthUrl)
    . ' >> ' . escapeshellarg($log) . ' 2>&1 < /dev/null &';

exec($command, $output, $status);
if ($status !== 0) {
    http_response_code(500);
    exit('Deploy could not start');
}

http_response_code(202);
echo 'Villa deployment accepted: ' . substr($commit, 0, 12);
