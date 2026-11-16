<?php
declare(strict_types=1);

namespace App\Core;

final class SmtpMailer
{
    public static function validate(array $config, string $recipient, string $subject): void
    {
        $host = trim((string)($config['host'] ?? ''));
        $port = (int)($config['port'] ?? 0);
        $encryption = (string)($config['encryption'] ?? 'tls');
        $from = trim((string)($config['from_email'] ?? ''));
        $username = (string)($config['username'] ?? '');
        $password = (string)($config['password'] ?? '');

        if ($host === '' || strlen($host) > 253 || preg_match('/[\s\r\n]/', $host) === 1 || str_contains($host, '/') || str_contains($host, '\\')
            || (!filter_var($host, FILTER_VALIDATE_IP) && preg_match('/^(?=.{1,253}$)(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)(?:\.(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?))*$/', $host) !== 1)) {
            throw new \InvalidArgumentException('SMTP sunucu adresini kontrol edin.');
        }
        if ($port < 1 || $port > 65535 || !in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            throw new \InvalidArgumentException('SMTP portu veya şifreleme türü geçersiz.');
        }
        if (!filter_var($from, FILTER_VALIDATE_EMAIL) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Gönderen veya alıcı e-posta adresi geçersiz.');
        }
        if (preg_match('/[\r\n]/', $subject) || trim($subject) === '' || strlen($subject) > 500) {
            throw new \InvalidArgumentException('E-posta konusu geçersiz.');
        }
        if (preg_match('/[\r\n]/', (string)($config['from_name'] ?? ''))) {
            throw new \InvalidArgumentException('Gönderen adı geçersiz.');
        }
        if (strlen($username) > 190 || strlen($password) > 512) {
            throw new \InvalidArgumentException('SMTP kimlik bilgileri izin verilen boyutu aşıyor.');
        }
        if (($username === '') !== ($password === '')) {
            throw new \InvalidArgumentException('SMTP kullanıcı adı ve şifresi birlikte girilmelidir.');
        }
    }

    public static function send(array $config, string $recipient, string $subject, string $html, string $text = ''): void
    {
        self::validate($config, $recipient, $subject);
        if (strlen($html) > 1048576 || strlen($text) > 1048576) {
            throw new \InvalidArgumentException('E-posta içeriği izin verilen boyutu aşıyor.');
        }

        $host = (string)$config['host'];
        $port = (int)$config['port'];
        $encryption = (string)($config['encryption'] ?? 'tls');
        $target = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $context = stream_context_create(['ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ]]);
        if (!function_exists('stream_socket_client')) {
            throw new \RuntimeException('Bu hosting hesabında PHP stream_socket_client işlevi devre dışı. Hosting sağlayıcınızdan etkinleştirilmesini isteyin.');
        }

        $errno = 0;
        $error = '';
        $socket = @stream_socket_client($target, $errno, $error, 12, STREAM_CLIENT_CONNECT, $context);
        if (!is_resource($socket)) {
            $detail = trim(preg_replace('/[\\r\\n\\t]+/', ' ', $error) ?? '');
            $reason = match (true) {
                $errno === 0 && $detail === '' => 'PHP bağlantı kuramadı; ağ veya DNS ayrıntısı alınamadı.',
                stripos($detail, 'getaddrinfo') !== false || stripos($detail, 'name or service not known') !== false
                    || stripos($detail, 'no such host') !== false => 'SMTP sunucu adı DNS üzerinden çözümlenemedi. Sunucu adresini kontrol edin.',
                stripos($detail, 'timed out') !== false || stripos($detail, 'timeout') !== false => 'Bağlantı zaman aşımına uğradı. Hosting sağlayıcısı giden SMTP portunu engelliyor olabilir.',
                stripos($detail, 'refused') !== false => 'Bağlantı SMTP sunucusu tarafından reddedildi. Port ve sunucu adresini kontrol edin.',
                default => ($detail !== '' ? $detail : 'Ağ bağlantısı kurulamadı.'),
            };
            throw new \RuntimeException(
                'SMTP bağlantısı kurulamadı: ' . $reason
                . ' (sunucu: ' . $host . ', port: ' . $port . ', hata kodu: ' . $errno . ').'
            );
        }

        stream_set_timeout($socket, 12);
        try {
            self::expect($socket, [220]);
            self::command($socket, 'EHLO localhost', [250]);

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($crypto !== true) {
                    throw new \RuntimeException('SMTP TLS bağlantısı kurulamadı.');
                }
                self::command($socket, 'EHLO localhost', [250]);
            }

            $username = (string)($config['username'] ?? '');
            if ($username !== '') {
                self::command($socket, 'AUTH LOGIN', [334]);
                self::command($socket, base64_encode($username), [334]);
                self::command($socket, base64_encode((string)$config['password']), [235]);
            }

            $from = (string)$config['from_email'];
            self::command($socket, 'MAIL FROM:<' . $from . '>', [250]);
            self::command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);

            $fromName = trim((string)($config['from_name'] ?? ''));
            $fromHeader = $fromName !== ''
                ? '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>'
                : $from;
            $boundary = 'villa_' . bin2hex(random_bytes(16));
            $text = $text !== '' ? $text : trim(strip_tags($html));
            $body = '--' . $boundary . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . rtrim(chunk_split(base64_encode($text), 76, "\r\n")) . "\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . rtrim(chunk_split(base64_encode($html), 76, "\r\n")) . "\r\n"
                . '--' . $boundary . "--\r\n";
            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . $fromHeader,
                'To: <' . $recipient . '>',
                'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
                'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $host . '>',
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            ];
            $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
            $message = preg_replace('/\r?\n\./', "\r\n..", $message) ?? $message;
            self::write($socket, $message . "\r\n.\r\n");
            self::expect($socket, [250]);
            @fwrite($socket, "QUIT\r\n");
        } finally {
            fclose($socket);
        }
    }

    private static function command($socket, string $command, array $expected): string
    {
        self::write($socket, $command . "\r\n");
        return self::expect($socket, $expected);
    }

    private static function write($socket, string $data): void
    {
        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $result = fwrite($socket, substr($data, $written));
            if ($result === false || $result === 0) {
                throw new \RuntimeException('SMTP sunucusuna veri gönderilemedi.');
            }
            $written += $result;
        }
    }

    private static function expect($socket, array $expected): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (preg_match('/^([0-9]{3}) /', $line, $match) === 1) {
                $code = (int)$match[1];
                if (!in_array($code, $expected, true)) {
                    throw new \RuntimeException('SMTP sunucusu beklenmeyen yanıt verdi: ' . $code . '.');
                }
                return $response;
            }
        }
        $meta = stream_get_meta_data($socket);
        throw new \RuntimeException(!empty($meta['timed_out']) ? 'SMTP yanıt zaman aşımına uğradı.' : 'SMTP sunucusundan yanıt alınamadı.');
    }
}
