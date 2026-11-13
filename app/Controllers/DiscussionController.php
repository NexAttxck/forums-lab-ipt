<?php
namespace App\Controllers;

use App\Models\{Comment, Discussion};
use App\Support\{Auth, View};

class DiscussionController
{
    const MAX_WALK = 50; // traversal limit for the renderer

    public function show(string $id): void
    {
        $did = $this->id($id);
        if ($did === null) {
            View::render('error', ['title' => 'Bad request', 'message' => 'Invalid discussion id.'], 400);
            return;
        }
        $d = Discussion::find($did);
        if (!$d) {
            View::render('error', ['title' => 'Not found', 'message' => 'That discussion does not exist.'], 404);
            return;
        }

        $byParent = [];
        foreach (Comment::forDiscussion($did) as $c) {
            $byParent[$c['parent_id'] ?? 0][] = $c;
        }
        $seen = [];
        $error = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        View::render('discussion', [
            'd' => $d,
            'did' => $did,
            'comments' => $this->tree($byParent, 0, 0, $seen),
            'error' => $error,
        ]);
    }

    public function store(string $id): void
    {
        $did = $this->id($id);
        if (!Auth::userId()) {
            View::render('error', ['title' => 'Login required', 'message' => 'You must log in to post.'], 401);
            return;
        }
        if (!Auth::csrfOk()) {
            View::render('error', ['title' => 'Forbidden', 'message' => 'Invalid CSRF token.'], 403);
            return;
        }
        if ($did === null || !Discussion::find($did)) {
            View::render('error', ['title' => 'Not found', 'message' => 'That discussion does not exist.'], 404);
            return;
        }

        $body = trim((string) ($_POST['body'] ?? ''));
        $parent = null;
        $err = null;
        if ($body === '' || mb_strlen($body) > 2000) {
            $err = 'Comment must be 1 to 2000 characters.';
        } elseif (($_POST['parent_id'] ?? '') !== '') {
            $p = ctype_digit((string) $_POST['parent_id']) ? Comment::find((int) $_POST['parent_id']) : null;
            if (!$p || (int) $p['discussion_id'] !== $did) {
                $err = 'Parent comment is not in this discussion.';
            } elseif (Comment::depth((int) $p['id']) + 1 > Comment::MAX_DEPTH) {
                $err = 'Reply is nested too deep.';
            } else {
                $parent = (int) $p['id'];
            }
        }
        if ($err) {
            $_SESSION['flash'] = $err;
            View::redirect("/discussions/$did");
            return;
        }

        // user id always comes from the session. A new row can only point at an
        // older existing row, so cycles are impossible and parents never change.
        $new = Comment::create($did, Auth::userId(), $parent, $body);
        View::redirect("/discussions/$did#comment-$new");
    }

    private function id(string $s): ?int
    {
        return ctype_digit($s) && strlen($s) <= 9 && (int) $s > 0 ? (int) $s : null;
    }

    private function tree(array $by, int $pid, int $depth, array &$seen): array
    {
        $out = [];
        if ($depth > self::MAX_WALK) return $out;
        foreach ($by[$pid] ?? [] as $c) {
            if (isset($seen[$c['id']])) continue;
            $seen[$c['id']] = true;
            $kids = $this->tree($by, (int) $c['id'], $depth + 1, $seen);
            $out[] = [
                'id' => $c['id'],
                'username' => $c['username'],
                'body' => $c['body'],
                'created' => gmdate('Y-m-d H:i', strtotime($c['created_at'] . ' UTC')),
                'depth' => $depth,
                'indent' => $depth <= 8 ? 24 : 0, // visual cap, label stays real
                'children' => $kids,
                'hasChildren' => (bool) $kids,
            ];
        }
        return $out;
    }
}
