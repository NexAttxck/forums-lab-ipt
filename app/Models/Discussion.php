<?php
namespace App\Models;

class Discussion
{
    public static function all(): array
    {
        return Db::all('SELECT d.id, d.title, u.username FROM discussions d JOIN users u ON u.id = d.user_id ORDER BY d.id');
    }

    public static function find(int $id): ?array
    {
        return Db::one(
            'SELECT d.*, u.username FROM discussions d JOIN users u ON u.id = d.user_id WHERE d.id = ?',
            [$id]
        );
    }
}
