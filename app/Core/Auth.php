<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function attempt(string $identity, string $password): bool
    {
        if (function_exists('villa_perf_mark')) villa_perf_mark('auth_attempt:db_connect_begin');
        $pdo = Database::connection();
        if (function_exists('villa_perf_mark')) villa_perf_mark('auth_attempt:db_connected');
        $identityHash = hash('sha256', strtolower(trim($identity)));
        $ipHash = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $throttle = $pdo->prepare('SELECT attempts,locked_until,window_started_at FROM login_attempts WHERE identity_hash=? AND ip_hash=? LIMIT 1');
        if (function_exists('villa_perf_mark')) villa_perf_mark('auth_attempt:throttle_query_begin');
        $throttle->execute([$identityHash,$ipHash]);
        $attemptState = $throttle->fetch();
        if (function_exists('villa_perf_mark')) villa_perf_mark('auth_attempt:throttle_query_done');
        if ($attemptState && $attemptState['locked_until'] && strtotime($attemptState['locked_until']) > time()) return false;
        $stmt = $pdo->prepare(
            'SELECT id, username, email, password_hash, role, status, session_version FROM users WHERE email = ? OR username = ? LIMIT 1'
        );
        if (function_exists('villa_perf_mark')) villa_perf_mark('auth_attempt:user_query_begin');
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch();
        if (function_exists('villa_perf_mark')) villa_perf_mark('auth_attempt:user_query_done');
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
        if (function_exists('villa_perf_mark')) villa_perf_mark('auth_attempt:session_regenerate_begin');
        session_regenerate_id(true);
        if (function_exists('villa_perf_mark')) villa_perf_mark('auth_attempt:session_regenerate_done');
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'session_version' => (int)($user['session_version'] ?? 1),
        ];
        return true;
    }

    public static function user(): ?array
    {
        static $validated = false;
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) return null;
        if (!$validated) {
            $stmt = Database::connection()->prepare('SELECT username,email,role,status,session_version FROM users WHERE id=? LIMIT 1');
            $stmt->execute([(int)($_SESSION['user']['id'] ?? 0)]);
            $current = $stmt->fetch();
            if (!$current || $current['status'] !== 'active') {
                unset($_SESSION['user']);
                session_regenerate_id(true);
                return null;
            }
            $_SESSION['user']['username'] = $current['username'];
            $_SESSION['user']['email'] = $current['email'];
            $_SESSION['user']['role'] = $current['role'];
            $validated = true;
        }
        return $_SESSION['user'];
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Enforce the user-management hierarchy independently of route/UI checks.
     * Admins may manage customers and vendors; only super-admins may manage
     * admin accounts. Super-admin accounts are never changed through this flow.
     */
    public static function canManageUserRole(string $actorRole, string $targetRole): bool
    {
        if ($targetRole === 'super_admin') {
            return false;
        }

        if ($actorRole === 'super_admin') {
            return in_array($targetRole, ['admin', 'customer', 'vendor'], true);
        }

        if ($actorRole === 'admin') {
            return in_array($targetRole, ['customer', 'vendor'], true);
        }

        return false;
    }

    /** Pending vendors may prepare drafts, but cannot access guest bookings until approved. */
    public static function canManageVendorBookings(string $vendorStatus): bool
    {
        return $vendorStatus === 'approved';
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
