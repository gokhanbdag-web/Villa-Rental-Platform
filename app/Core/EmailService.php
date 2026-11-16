<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class EmailService
{
    public static function smtpConfig(?PDO $pdo = null): array
    {
        $pdo ??= Database::connection();
        $keys = [
            'mail.enabled', 'mail.smtp.host', 'mail.smtp.port', 'mail.smtp.encryption',
            'mail.smtp.username', 'mail.smtp.password', 'mail.from_email', 'mail.from_name',
        ];
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare("SELECT setting_key,setting_value FROM settings WHERE setting_key IN ($placeholders)");
        $stmt->execute($keys);
        $stored = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $password = (string)($stored['mail.smtp.password'] ?? '');
        if ($password !== '') $password = PaymentSecretVault::decrypt($password);

        return [
            'enabled' => (string)($stored['mail.enabled'] ?? '0') === '1',
            'host' => trim((string)($stored['mail.smtp.host'] ?? '')),
            'port' => (int)($stored['mail.smtp.port'] ?? 587),
            'encryption' => (string)($stored['mail.smtp.encryption'] ?? 'tls'),
            'username' => (string)($stored['mail.smtp.username'] ?? ''),
            'password' => $password,
            'from_email' => trim((string)($stored['mail.from_email'] ?? '')),
            'from_name' => trim((string)($stored['mail.from_name'] ?? '')),
        ];
    }

    public static function cronToken(?PDO $pdo = null): string
    {
        $pdo ??= Database::connection();
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key='mail.cron_token' LIMIT 1");
        $stmt->execute();
        $stored = (string)($stmt->fetchColumn() ?: '');
        return $stored === '' ? '' : PaymentSecretVault::decrypt($stored);
    }

    public static function enqueue(PDO $pdo, string $recipient, string $subject, string $html, string $text = '', ?string $eventKey = null): void
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || trim($subject) === '' || preg_match('/[\r\n]/', $subject)
            || strlen($subject) > 500 || strlen($html) > 1048576 || strlen($text) > 1048576) {
            throw new \InvalidArgumentException('E-posta kuyruğu için alıcı, konu veya içerik geçersiz.');
        }
        if ($eventKey !== null && (strlen($eventKey) > 190 || $eventKey === '')) {
            throw new \InvalidArgumentException('E-posta olay anahtarı geçersiz.');
        }
        $stmt = $pdo->prepare("INSERT IGNORE INTO email_queue(recipient_email,subject,html_body,text_body,event_key,status,attempts,available_at,created_at) VALUES(?,?,?,?,?,'pending',0,NOW(),NOW())");
        $stmt->execute([$recipient, $subject, $html, $text, $eventKey]);
    }

    public static function sendTest(PDO $pdo, string $recipient): void
    {
        $config = self::smtpConfig($pdo);
        if (!$config['enabled']) throw new \RuntimeException('SMTP e-posta gönderimi etkin değil.');
        SmtpMailer::send($config, $recipient, 'Villa platformu SMTP test e-postası', '<p>SMTP bağlantınız başarıyla doğrulandı.</p><p>Villa platformu e-posta teslimat testi.</p>', "SMTP bağlantınız başarıyla doğrulandı.\nVilla platformu e-posta teslimat testi.");
    }

    public static function processPending(PDO $pdo, int $limit = 10): array
    {
        $limit = max(1, min(25, $limit));
        $config = self::smtpConfig($pdo);
        if (!$config['enabled']) return ['processed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 'disabled'];

        $pdo->exec("UPDATE email_queue SET status='pending',locked_at=NULL WHERE status='sending' AND locked_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        $stmt = $pdo->prepare("SELECT id,recipient_email,subject,html_body,text_body,attempts FROM email_queue WHERE status='pending' AND available_at<=NOW() ORDER BY id ASC LIMIT {$limit}");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = ['processed' => 0, 'sent' => 0, 'failed' => 0];

        foreach ($items as $item) {
            $claim = $pdo->prepare("UPDATE email_queue SET status='sending',locked_at=NOW(),updated_at=NOW() WHERE id=? AND status='pending'");
            $claim->execute([(int)$item['id']]);
            if ($claim->rowCount() !== 1) continue;
            $result['processed']++;
            try {
                SmtpMailer::send($config, (string)$item['recipient_email'], (string)$item['subject'], (string)$item['html_body'], (string)$item['text_body']);
                $done = $pdo->prepare("UPDATE email_queue SET status='sent',sent_at=NOW(),locked_at=NULL,last_error=NULL,updated_at=NOW() WHERE id=? AND status='sending'");
                $done->execute([(int)$item['id']]);
                $result['sent']++;
            } catch (Throwable $e) {
                $attempts = (int)$item['attempts'] + 1;
                $status = $attempts >= 5 ? 'failed' : 'pending';
                $delay = min(60, 2 ** $attempts * 2);
                $error = mb_substr(preg_replace('/[\r\n]+/', ' ', $e->getMessage()) ?? 'SMTP delivery failed', 0, 450);
                $failed = $pdo->prepare("UPDATE email_queue SET status=?,attempts=?,available_at=DATE_ADD(NOW(), INTERVAL {$delay} MINUTE),locked_at=NULL,last_error=?,updated_at=NOW() WHERE id=? AND status='sending'");
                $failed->execute([$status, $attempts, $error, (int)$item['id']]);
                $result['failed']++;
            }
        }
        return $result;
    }
}
