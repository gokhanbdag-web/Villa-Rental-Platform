<?php
declare(strict_types=1);

/**
 * CLI wrapper for the host-side deployment script.
 * Records truthful deployment outcomes and verifies the public health URL.
 * Usage: php scripts/deploy-runner.php <commit-sha> <health-url>
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$base = '/opt/villa-deploy';
$deployScript = $base . '/deploy.sh';
$statusWriter = __DIR__ . '/deploy-status.php';
$commit = strtolower((string)($argv[1] ?? ''));
$healthUrl = (string)($argv[2] ?? '');

if (!preg_match('/^[a-f0-9]{40}$/', $commit)) {
    fwrite(STDERR, "Invalid commit SHA\n");
    exit(2);
}
$parsed = parse_url($healthUrl);
if (!is_array($parsed) || !in_array(($parsed['scheme'] ?? ''), ['http', 'https'], true) || empty($parsed['host'])) {
    fwrite(STDERR, "Invalid health URL\n");
    exit(2);
}
if (!is_file($deployScript) || !is_executable($deployScript)) {
    fwrite(STDERR, "Deployment script unavailable\n");
    exit(3);
}

function villa_runner_status(string $writer, string $state, string $sha, string $message): bool
{
    $command = [PHP_BINARY, $writer, $state, $sha, '-', $message];
    $pipes = [];
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        return false;
    }
    fclose($pipes[0]);
    stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $code = proc_close($process);
    if ($code !== 0 && $error !== '') {
        fwrite(STDERR, $error);
    }
    return $code === 0;
}

if (!villa_runner_status($statusWriter, 'deploying', $commit, 'GitHub dağıtımı başladı')) {
    fwrite(STDERR, "Could not record deployment start\n");
    exit(4);
}

$descriptors = [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR];
$process = proc_open(['/bin/bash', $deployScript], $descriptors, $pipes, $base);
if (!is_resource($process)) {
    villa_runner_status($statusWriter, 'failed', $commit, 'Dağıtım betiği başlatılamadı');
    exit(5);
}
fclose($pipes[0]);
$deployExit = proc_close($process);

if ($deployExit !== 0) {
    villa_runner_status($statusWriter, 'failed', $commit, 'Sunucu dağıtım betiği hata kodu: ' . $deployExit);
    exit($deployExit > 0 && $deployExit < 126 ? $deployExit : 6);
}

$context = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true, 'follow_location' => 1], 'https' => ['timeout' => 20]]);
$response = @file_get_contents($healthUrl, false, $context);
$statusLine = $http_response_header[0] ?? '';
if (!is_string($response) || !preg_match('/\s2\d\d\s/', $statusLine)) {
    villa_runner_status($statusWriter, 'failed', $commit, 'Dağıtım sonrası sağlık kontrolü başarısız: ' . $statusLine);
    fwrite(STDERR, "Post-deploy health check failed\n");
    exit(7);
}

if (!villa_runner_status($statusWriter, 'success', $commit, 'Dağıtım ve HTTP sağlık kontrolü başarılı')) {
    fwrite(STDERR, "Deployment succeeded but status could not be recorded\n");
    exit(8);
}

fwrite(STDOUT, "Deployment completed and health check passed\n");
