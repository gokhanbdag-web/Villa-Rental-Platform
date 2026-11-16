<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\SmtpMailer;

$config = [
    'host' => 'smtp.example.test',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'mailer@example.test',
    'password' => 'test-secret',
    'from_email' => 'noreply@example.test',
    'from_name' => 'Villa Platform',
];

SmtpMailer::validate($config, 'guest@example.test', 'Rezervasyon bildirimi');

$invalid = [
    ['recipient' => "guest@example.test\r\nBcc: attacker@example.test", 'subject' => 'Test'],
    ['recipient' => 'guest@example.test', 'subject' => "Test\r\nBcc: attacker@example.test"],
    ['recipient' => 'not-an-email', 'subject' => 'Test'],
];
foreach ($invalid as $case) {
    try {
        SmtpMailer::validate($config, $case['recipient'], $case['subject']);
        throw new RuntimeException('SMTP validation accepted a header-injection or invalid-address payload.');
    } catch (InvalidArgumentException) {
        // Expected rejection.
    }
}

$badAuth = $config;
$badAuth['password'] = '';
try {
    SmtpMailer::validate($badAuth, 'guest@example.test', 'Test');
    throw new RuntimeException('SMTP validation accepted an incomplete authentication pair.');
} catch (InvalidArgumentException) {
    // Expected rejection.
}

fwrite(STDOUT, "SMTP message validation smoke tests passed.\n");
