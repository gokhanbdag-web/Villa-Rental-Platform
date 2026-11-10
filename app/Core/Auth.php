<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function attempt(string $identity, string $password): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, username, email, password_hash, role, status FROM users WHERE email = ? OR username = ? LIMIT 1'
        );
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch();
        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            return false;
        }
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
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
