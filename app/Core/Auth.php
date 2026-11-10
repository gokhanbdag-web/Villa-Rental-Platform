<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function attempt(string $identity, string $password): bool
    {
        $pdo = Database::connection();
        $identityHash = hash('sha256', strtolower(trim($identity)));
        $ipHash = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $throttle = $pdo->prepare('SELECT attempts,locked_until,window_started_at FROM login_attempts WHERE identity_hash=? AND ip_hash=? LIMIT 1');
        $throttle->execute([$identityHash,$ipHash]);
        $attemptState = $throttle->fetch();
        if ($attemptState && $attemptState['locked_until'] && strtotime($attemptState['locked_until']) > time()) return false;
        $stmt = $pdo->prepare(
            'SELECT id, username, email, password_hash, role, status FROM users WHERE email = ? OR username = ? LIMIT 1'
        );
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch();
        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            $now = new \DateTimeImmutable('now');
            $windowExpired = !$attemptState || strtotime($attemptState['window_started_at']) < time() - 900;
            $attempts = $windowExpired ? 1 : (int)$attemptState['attempts'] + 1;
            $lockedUntil = $attempts >= 5 ? $now->modify('+15 minutes')->format('Y-m-d H:i:s') : null;
            $windowStarted = $windowExpired ? $now->format('Y-m-d H:i:s') : $attemptState['window_started_at'];
            $saveAttempt = $pdo->prepare('INSERT INTO login_attempts(identity_hash,ip_hash,attempts,window_started_at,locked_until,updated_at) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE attempts=VALUES(attempts),window_started_at=VALUES(window_started_at),locked_until=VALUES(locked_until),updated_at=NOW()');
            $saveAttempt->execute([$identityHash,$ipHash,$attempts,$windowStarted,$lockedUntil]);
            return false;
        }
        $clear = $pdo->prepare('DELETE FROM login_attempts WHERE identity_hash=? AND ip_hash=?');
        $clear->execute([$identityHash,$ipHash]);
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        return true;
    }

    public static function user(): ?array
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireRole(array $roles): void
    {
        $user = self::user();
        if (!$user || !in_array($user['role'], $roles, true)) {
            redirect('/login');
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }
}
