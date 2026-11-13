<?php
namespace App\Models;

class Comment
{
    const MAX_DEPTH = 20; // write limit, parent chain longer than this is refused

    public static function forDiscussion(int $did): array
    {
        return Db::all(
            'SELECT c.id, c.parent_id, c.body, c.created_at, u.username
             FROM comments c JOIN users u ON u.id = c.user_id
             WHERE c.discussion_id = ? ORDER BY c.id',
            [$did]
        );
    }

    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM comments WHERE id = ?', [$id]);
    }

    // displayed depth of a comment: top level is 0
    public static function depth(int $id): int
    {
        $d = 0;
        while ($d <= self::MAX_DEPTH + 1) {
            $c = self::find($id);
            if (!$c || $c['parent_id'] === null) return $d;
            $id = (int) $c['parent_id'];
            $d++;
        }
        return $d;
    }

    public static function create(int $did, int $uid, ?int $parent, string $body): int
    {
        Db::pdo()->prepare('INSERT INTO comments (discussion_id, parent_id, user_id, body) VALUES (?,?,?,?)')
            ->execute([$did, $parent, $uid, $body]);
        return (int) Db::pdo()->lastInsertId();
    }
}
