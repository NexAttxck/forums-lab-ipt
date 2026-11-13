<?php
namespace App\Models;

class User
{
    public static function byUsername(string $name): ?array
    {
        return Db::one('SELECT * FROM users WHERE username = ?', [$name]);
    }

    public static function exists(string $username, string $email): bool
    {
        return (bool) Db::one('SELECT id FROM users WHERE username = ? OR email = ?', [$username, $email]);
    }

    public static function create(string $username, string $email, string $hash): int
    {
        Db::pdo()->prepare('INSERT INTO users (username, email, password_hash) VALUES (?,?,?)')
            ->execute([$username, $email, $hash]);
        return (int) Db::pdo()->lastInsertId();
    }
}
