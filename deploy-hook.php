<?php
declare(strict_types=1);

/**
 * Villa Rental Platform GitHub deployment receiver.
 * The HMAC secret and deploy script are stored outside the web root.
 */
$base = '/opt/villa-deploy';
$secretFile = $base . '/webhook.secret';
$script = $base . '/deploy.sh';
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

if (!is_file($script) || !is_executable($script)) {
    http_response_code(500);
    exit('Deploy script unavailable');
}

$command = '/usr/bin/nohup /bin/bash '
    . escapeshellarg($script)
    . ' >> ' . escapeshellarg($log)
    . ' 2>&1 < /dev/null &';

exec($command, $output, $status);
if ($status !== 0) {
    http_response_code(500);
    exit('Deploy could not start');
}

http_response_code(202);
echo 'Villa deployment accepted';
