<?php
declare(strict_types=1);

/**
 * CLI-only deployment status writer.
 * Usage: php scripts/deploy-status.php <deploying|success|failed|rolled_back> <commit-sha> [backup-id] [message]
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$status = (string)($argv[1] ?? '');
$commit = strtolower(trim((string)($argv[2] ?? '')));
$backup = trim((string)($argv[3] ?? ''));
$message = trim((string)($argv[4] ?? ''));

$allowed = ['deploying', 'success', 'failed', 'rolled_back'];
if (!in_array($status, $allowed, true) || ($status === 'deploying' && !preg_match('/^[a-f0-9]{40,64}$/', $commit)) || ($commit !== '' && !preg_match('/^[a-f0-9]{40,64}$/', $commit))) {
    fwrite(STDERR, "Usage: php scripts/deploy-status.php <deploying|success|failed|rolled_back> <commit-sha-or-empty-for-final-state> [backup-id] [message]\n");
    exit(2);
}

$root = dirname(__DIR__);
$storage = $root . '/storage';
if (!is_dir($storage) && !@mkdir($storage, 0750, true) && !is_dir($storage)) {
    fwrite(STDERR, "Cannot create storage directory.\n");
    exit(3);
}

$entry = [
    'status' => $status,
    'commit' => $commit,
    'backup' => substr(preg_replace('/[^a-zA-Z0-9._-]/', '', $backup) ?? '', 0, 120),
    'message' => substr(preg_replace('/[\r\n\t]+/', ' ', $message) ?? '', 0, 500),
    'updated_at' => gmdate('c'),
];

$json = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (!is_string($json)) {
    fwrite(STDERR, "Cannot encode deployment status.\n");
    exit(4);
}

$statusFile = $storage . '/deploy-status.json';
$tempFile = $statusFile . '.tmp.' . getmypid();
if (@file_put_contents($tempFile, $json . PHP_EOL, LOCK_EX) === false || !@rename($tempFile, $statusFile)) {
    @unlink($tempFile);
    fwrite(STDERR, "Cannot write deployment status file.\n");
    exit(5);
}
@chmod($statusFile, 0640);

$historyFile = $storage . '/deploy-history.jsonl';
$historyLine = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (is_string($historyLine)) {
    $written = @file_put_contents($historyFile, $historyLine . PHP_EOL, FILE_APPEND | LOCK_EX);
    if ($written === false) {
        fwrite(STDERR, "Status saved, but deployment history could not be appended.\n");
        exit(6);
    }
    @chmod($historyFile, 0640);
}

fwrite(STDOUT, strtoupper($status) . ': ' . substr($commit, 0, 12) . PHP_EOL);
