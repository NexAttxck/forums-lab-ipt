<?php
namespace App\Models;

class Discussion
{
    public static function find(int $id): ?array
    {
        return Db::one(
            'SELECT d.*, u.username FROM discussions d JOIN users u ON u.id = d.user_id WHERE d.id = ?',
            [$id]
        );
    }
}
