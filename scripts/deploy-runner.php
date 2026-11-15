<?php
declare(strict_types=1);

/**
 * CLI deployment coordinator.
 * Runs the host deployment script, checks the live URL and restores the
 * newest pre-deployment archive when deployment or the health check fails.
 * Usage: php scripts/deploy-runner.php <commit-sha> <health-url>
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$base = '/opt/villa-deploy';
$deployScript = $base . '/deploy.sh';
$live = '/home/kodvadisi.com.tr/public_html/villa';
// Deployment status must be written into the LIVE application storage,
// because the Update Center reads storage/deploy-status.json from this path.
$statusWriter = $live . '/scripts/deploy-status.php';
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

// Keep the complete deployment, health check and possible restore in one lock.
$lock = @fopen($base . '/runner.lock', 'c');
if (!is_resource($lock) || !flock($lock, LOCK_EX)) {
    fwrite(STDERR, "Could not acquire the Villa deployment lock\n");
    exit(9);
}

function villa_runner_status(string $writer, string $state, string $sha, string $message, string $backup = '-'): bool
{
    $command = [PHP_BINARY, $writer, $state, $sha, $backup, $message];
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

function villa_previous_deployed_commit(string $live): string
{
    $file = $live . '/storage/deploy-status.json';
    if (!is_file($file) || !is_readable($file)) {
        return '';
    }
    $data = json_decode((string)@file_get_contents($file), true);
    if (!is_array($data) || !in_array(($data['status'] ?? ''), ['success', 'rolled_back'], true)) {
        return '';
    }
    $sha = strtolower((string)($data['commit'] ?? ''));
    return preg_match('/^[a-f0-9]{40,64}$/', $sha) ? $sha : '';
}

function villa_staging_commit(string $staging): string
{
    $pipes = [];
    $process = proc_open(['git', '-C', $staging, 'rev-parse', 'HEAD'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        return '';
    }
    fclose($pipes[0]);
    $output = trim((string)stream_get_contents($pipes[1]));
    fclose($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $code = proc_close($process);
    return $code === 0 && preg_match('/^[a-f0-9]{40,64}$/i', $output)
        ? strtolower($output)
        : '';
}

function villa_latest_backup(string $directory): ?array
{
    $files = glob($directory . '/backups/villa-*.tar.gz') ?: [];
    if ($files === []) {
        return null;
    }
    usort($files, static fn(string $a, string $b): int => filemtime($b) <=> filemtime($a));
    $path = $files[0];
    $hash = hash_file('sha256', $path);
    return is_string($hash) ? ['path' => $path, 'hash' => $hash] : null;
}

function villa_health_check_local(string $url, string &$statusLine): bool
{
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['host'])) {
        $statusLine = 'Invalid URL for loopback probe';
        return false;
    }

    $scheme = strtolower((string)($parts['scheme'] ?? 'https'));
    $host = (string)$parts['host'];
    $port = (int)($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
    $path = (string)($parts['path'] ?? '/');
    if (isset($parts['query']) && $parts['query'] !== '') {
        $path .= '?' . $parts['query'];
    }

    $transport = $scheme === 'https' ? 'tls' : 'tcp';
    $contextOptions = [];
    if ($transport === 'tls') {
        // Connect to the local web server while preserving the site's TLS SNI
        // and Host name. Certificate verification is intentionally disabled
        // only for this loopback diagnostic request.
        $contextOptions['ssl'] = [
            'peer_name' => $host,
            'SNI_enabled' => true,
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ];
    }

    $context = stream_context_create($contextOptions);
    $errno = 0;
    $error = '';
    $socket = @stream_socket_client(
        $transport . '://127.0.0.1:' . $port,
        $errno,
        $error,
        12,
        STREAM_CLIENT_CONNECT,
        $context
    );
    if (!is_resource($socket)) {
        $statusLine = 'Loopback connection failed (' . $errno . '): ' . ($error !== '' ? $error : 'no local listener');
        return false;
    }

    stream_set_timeout($socket, 20);
    $request = "GET " . $path . " HTTP/1.1\r\n"
        . "Host: " . $host . "\r\n"
        . "User-Agent: Villa-Deploy-Health-Check/1.0\r\n"
        . "Accept: text/html,application/json;q=0.9,*/*;q=0.8\r\n"
        . "Connection: close\r\n\r\n";
    $written = @fwrite($socket, $request);
    if (!is_int($written) || $written <= 0) {
        fclose($socket);
        $statusLine = 'Loopback request could not be written';
        return false;
    }

    $response = stream_get_contents($socket);
    $meta = stream_get_meta_data($socket);
    fclose($socket);
    if (!is_string($response) || $response === '') {
        $statusLine = !empty($meta['timed_out'])
            ? 'Loopback HTTP response timed out'
            : 'Loopback HTTP server returned no response';
        return false;
    }

    $firstLine = strtok($response, "\r\n");
    if (!is_string($firstLine) || !preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $firstLine, $match)) {
        $statusLine = 'Loopback server returned an invalid HTTP response';
        return false;
    }

    $statusLine = 'Loopback ' . $firstLine;
    return (int)$match[1] >= 200 && (int)$match[1] < 300;
}

function villa_health_check(string $url, string &$statusLine): bool
{
    // Prefer the normal public URL. If the server cannot reach its own public
    // hostname (DNS/CDN/firewall/TLS hairpin), retry against its local listener
    // with the original Host header and TLS SNI. Real HTTP 4xx/5xx responses
    // remain failures and are never hidden by the fallback.
    if (function_exists('error_clear_last')) {
        error_clear_last();
    }
    $context = stream_context_create([
        'http' => ['timeout' => 20, 'ignore_errors' => true, 'follow_location' => 1, 'max_redirects' => 5],
        'https' => ['timeout' => 20]
    ]);
    $response = @file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    foreach ($headers as $header) {
        if (preg_match('/^HTTP\/\S+\s+\d{3}/', $header)) {
            $statusLine = $header;
        }
    }

    if (is_string($response)) {
        if ($statusLine === '') {
            $statusLine = 'HTTP probe returned a body but no HTTP status header';
            return false;
        }
        return preg_match('/^HTTP\/\S+\s+2\d\d\b/', $statusLine) === 1;
    }

    $lastError = error_get_last();
    $transportError = is_array($lastError) ? (string)($lastError['message'] ?? '') : '';
    $publicFailure = $transportError !== '' ? $transportError : 'no response received';
    $loopbackStatus = '';
    if (villa_health_check_local($url, $loopbackStatus)) {
        $statusLine = $loopbackStatus . ' (public URL probe unavailable: ' . $publicFailure . ')';
        return true;
    }

    $statusLine = 'Public probe failed: ' . $publicFailure . '; ' . $loopbackStatus;
    return false;
}

function villa_restore_backup(string $archive, string $base, string $live): bool
{
    if (!is_file($archive) || !is_readable($archive) || !is_dir($live)) {
        return false;
    }
    $restoreDir = $base . '/restore-' . bin2hex(random_bytes(6));
    if (!mkdir($restoreDir, 0700, true)) {
        return false;
    }
    try {
        $extract = proc_open(['/bin/tar', '-xzf', $archive, '-C', $restoreDir],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($extract)) {
            return false;
        }
        fclose($pipes[0]);
        stream_get_contents($pipes[1]); fclose($pipes[1]);
        $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
        if (proc_close($extract) !== 0) {
            fwrite(STDERR, "Backup extraction failed: " . $err . "\n");
            return false;
        }

        $sync = proc_open(['/usr/bin/rsync', '-a', '--delete', $restoreDir . '/', $live . '/'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($sync)) {
            return false;
        }
        fclose($pipes[0]);
        stream_get_contents($pipes[1]); fclose($pipes[1]);
        $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $code = proc_close($sync);
        if ($code !== 0) {
            fwrite(STDERR, "Backup restore failed: " . $err . "\n");
            return false;
        }
        return true;
    } finally {
        $cleanup = proc_open(['/bin/rm', '-rf', '--', $restoreDir],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (is_resource($cleanup)) {
            fclose($pipes[0]); stream_get_contents($pipes[1]); fclose($pipes[1]);
            stream_get_contents($pipes[2]); fclose($pipes[2]); proc_close($cleanup);
        }
    }
}

$previousCommit = villa_previous_deployed_commit($live);

if (!villa_runner_status($statusWriter, 'deploying', $commit, 'GitHub dağıtımı başladı')) {
    fwrite(STDERR, "Could not record deployment start\n");
    exit(4);
}

$backupBefore = villa_latest_backup($base);
$descriptors = [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR];
$process = proc_open(['/bin/bash', $deployScript], $descriptors, $pipes, $base);
if (!is_resource($process)) {
    villa_runner_status($statusWriter, 'failed', $previousCommit, 'Dağıtım betiği başlatılamadı');
    exit(5);
}
fclose($pipes[0]);
$deployExit = proc_close($process);

$backupAfter = villa_latest_backup($base);
$newBackup = null;
if ($backupAfter !== null && ($backupBefore === null
    || $backupAfter['path'] !== $backupBefore['path']
    || !hash_equals($backupBefore['hash'], $backupAfter['hash']))) {
    $newBackup = $backupAfter['path'];
}
$statusLine = '';
$healthy = ($deployExit === 0) && villa_health_check($healthUrl, $statusLine);

if ($healthy) {
    $deployedCommit = villa_staging_commit('/opt/villa-staging');
    if (!villa_runner_status($statusWriter, 'success', $deployedCommit, 'Dağıtım ve HTTP sağlık kontrolü başarılı: ' . $statusLine, $newBackup !== null ? basename($newBackup) : '-')) {
        fwrite(STDERR, "Deployment succeeded but status could not be recorded\n");
        exit(8);
    }
    fwrite(STDOUT, "Deployment completed and health check passed\n");
    exit(0);
}

$reason = $deployExit !== 0
    ? 'Dağıtım betiği hata kodu: ' . $deployExit
    : 'Dağıtım sonrası sağlık kontrolü başarısız: ' . $statusLine;

if ($newBackup !== null && villa_restore_backup($newBackup, $base, $live)) {
    $restoreStatus = '';
    if (villa_health_check($healthUrl, $restoreStatus)) {
        villa_runner_status($statusWriter, 'rolled_back', $previousCommit,
            $reason . '; önceki yedek geri yüklendi ve sağlık kontrolü başarılı: ' . $restoreStatus, basename($newBackup));
        fwrite(STDERR, "Deployment failed; previous version restored and healthy\n");
        exit(10);
    }
    villa_runner_status($statusWriter, 'failed', '',
        $reason . '; yedek geri yüklendi ancak eski sürüm sağlık kontrolünden geçmedi: ' . $restoreStatus, basename($newBackup));
    fwrite(STDERR, "Rollback was applied but restored site health check failed\n");
    exit(11);
}

villa_runner_status($statusWriter, 'failed', '',
    $reason . '; otomatik geri alma yapılamadı: yeni ve doğrulanmış yedek bulunamadı veya geri yükleme başarısız', $newBackup !== null ? basename($newBackup) : '-');
fwrite(STDERR, "Deployment failed and automatic rollback could not be completed\n");
exit(12);
