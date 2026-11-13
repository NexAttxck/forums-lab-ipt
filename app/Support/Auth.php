<?php
namespace App\Support;

class Auth
{
    public static function start(): void
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;
    }

    public static function login(int $id, string $name): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = $id;
        $_SESSION['name'] = $name;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function csrf(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function csrfOk(): bool
    {
        $t = $_POST['csrf'] ?? '';
        return is_string($t) && hash_equals(self::csrf(), $t);
    }
}
