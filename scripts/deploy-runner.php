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
$statusWriter = __DIR__ . '/deploy-status.php';
$live = '/home/kodvadisi.com.tr/public_html/villa';
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

function villa_health_check(string $url, string &$statusLine): bool
{
    $context = stream_context_create([
        'http' => ['timeout' => 25, 'ignore_errors' => true, 'follow_location' => 1, 'max_redirects' => 5],
        'https' => ['timeout' => 25]
    ]);
    $response = @file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    foreach ($headers as $header) {
        if (preg_match('/^HTTP\/\S+\s+\d{3}/', $header)) {
            $statusLine = $header;
        }
    }
    return is_string($response) && preg_match('/^HTTP\/\S+\s+2\d\d\b/', $statusLine) === 1;
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
