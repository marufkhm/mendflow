<?php
/**
 * discussions.php — Q&A threads with voting
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';

function discJson($data, $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function discTableExists(PDO $pdo, string $table): bool
{
    try {
        $st = $pdo->prepare(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1"
        );
        $st->execute([$table]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function ensureDiscussionsSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!discTableExists($pdo, 'discussions')) {
        try {
            $pdo->exec("CREATE TABLE discussions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                author_id INT NOT NULL,
                title VARCHAR(200) NOT NULL,
                body TEXT,
                tags VARCHAR(300),
                status ENUM('open','resolved') NOT NULL DEFAULT 'open',
                best_answer_id INT NULL,
                answers_count INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_disc_author (author_id),
                INDEX idx_disc_status (status),
                INDEX idx_disc_activity (last_activity_at),
                INDEX idx_disc_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }

    if (!discTableExists($pdo, 'discussion_answers')) {
        try {
            $pdo->exec("CREATE TABLE discussion_answers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                discussion_id INT NOT NULL,
                parent_id INT NULL,
                author_id INT NOT NULL,
                body TEXT NOT NULL,
                votes INT NOT NULL DEFAULT 0,
                is_best BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_da_disc (discussion_id),
                INDEX idx_da_parent (parent_id),
                INDEX idx_da_author (author_id),
                INDEX idx_da_votes (votes),
                FOREIGN KEY (discussion_id) REFERENCES discussions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    } else {
        try {
            $st = $pdo->prepare(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'discussion_answers'
                   AND COLUMN_NAME = 'parent_id' LIMIT 1"
            );
            $st->execute();
            if (!$st->fetchColumn()) {
                $pdo->exec('ALTER TABLE discussion_answers ADD COLUMN parent_id INT NULL AFTER discussion_id');
                $pdo->exec('ALTER TABLE discussion_answers ADD INDEX idx_da_parent (parent_id)');
            }
        } catch (Throwable $e) {
        }
    }

    if (!discTableExists($pdo, 'discussion_votes')) {
        try {
            $pdo->exec("CREATE TABLE discussion_votes (
                user_id INT NOT NULL,
                answer_id INT NOT NULL,
                value TINYINT NOT NULL,
                PRIMARY KEY (user_id, answer_id),
                INDEX idx_dv_answer (answer_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }
}

function discShapeAuthor(PDO $pdo, int $userId): array
{
    $st = $pdo->prepare(
        'SELECT id, first_name, last_name, avatar, is_verified FROM users WHERE id = ? LIMIT 1'
    );
    $st->execute([$userId]);
    $u = $st->fetch(PDO::FETCH_ASSOC) ?: [];
    return [
        'id'          => (int)($u['id'] ?? $userId),
        'first_name'  => $u['first_name'] ?? '',
        'last_name'   => $u['last_name'] ?? '',
        'avatar'      => $u['avatar'] ?? null,
        'is_verified' => !empty($u['is_verified']),
        'name'        => trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')),
    ];
}

function discParseTags(?string $tags): array
{
    if ($tags === null || trim($tags) === '') {
        return [];
    }
    $parts = preg_split('/[,#]+/u', $tags) ?: [];
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '' && mb_strlen($p) <= 40) {
            $out[] = $p;
        }
    }
    return array_values(array_unique($out));
}

function discTagsString(array $tags): string
{
    return implode(', ', array_slice($tags, 0, 12));
}

define('DISC_TITLE_MAX_LEN', 80);

function discValidateTitle(string $title): ?string
{
    if ($title === '') {
        return 'Укажите заголовок';
    }
    if (mb_strlen($title) > DISC_TITLE_MAX_LEN) {
        return 'Заголовок — не более ' . DISC_TITLE_MAX_LEN . ' символов';
    }
    return null;
}

function discShapeThread(array $row, ?array $author = null): array
{
    $tags = discParseTags($row['tags'] ?? '');
    return [
        'id'               => (int)$row['id'],
        'author_id'        => (int)$row['author_id'],
        'title'            => $row['title'] ?? '',
        'body'             => $row['body'] ?? '',
        'tags'             => $tags,
        'tags_raw'         => $row['tags'] ?? '',
        'status'           => $row['status'] ?? 'open',
        'best_answer_id'   => isset($row['best_answer_id']) ? (int)$row['best_answer_id'] : null,
        'answers_count'    => (int)($row['answers_count'] ?? 0),
        'created_at'       => utcDate($row['created_at'] ?? null),
        'last_activity_at' => utcDate($row['last_activity_at'] ?? null),
        'time_ago'         => timeAgo($row['last_activity_at'] ?? $row['created_at'] ?? null),
        'author'           => $author,
        'author_name'      => $author['name'] ?? '',
    ];
}

function discRecalcAnswerVotes(PDO $pdo, int $answerId): int
{
    $st = $pdo->prepare('SELECT COALESCE(SUM(value), 0) FROM discussion_votes WHERE answer_id = ?');
    $st->execute([$answerId]);
    $votes = (int)$st->fetchColumn();
    $pdo->prepare('UPDATE discussion_answers SET votes = ? WHERE id = ?')->execute([$votes, $answerId]);
    return $votes;
}

function discShapeAnswerRow(array $row, int $userVote = 0): array
{
    return [
        'id'            => (int)$row['id'],
        'discussion_id' => (int)$row['discussion_id'],
        'parent_id'     => isset($row['parent_id']) && $row['parent_id'] !== null ? (int)$row['parent_id'] : null,
        'author_id'     => (int)$row['author_id'],
        'body'          => $row['body'] ?? '',
        'votes'         => (int)($row['votes'] ?? 0),
        'is_best'       => !empty($row['is_best']),
        'created_at'    => utcDate($row['created_at']),
        'time_ago'      => timeAgo($row['created_at']),
        'user_vote'     => $userVote,
        'author'        => [
            'id'          => (int)$row['author_id'],
            'first_name'  => $row['first_name'] ?? '',
            'last_name'   => $row['last_name'] ?? '',
            'avatar'      => $row['avatar'] ?? null,
            'is_verified' => !empty($row['is_verified']),
            'name'        => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
        ],
        'replies'       => [],
    ];
}

function discBuildAnswerTree(array $flat): array
{
    $items = [];
    foreach ($flat as $row) {
        $row['replies'] = [];
        $items[$row['id']] = $row;
    }

    $roots = [];
    foreach ($items as $id => &$node) {
        $parentId = $node['parent_id'];
        if ($parentId && isset($items[$parentId])) {
            $items[$parentId]['replies'][] = &$node;
        } else {
            $roots[] = &$node;
        }
    }
    unset($node);

    usort($roots, static function ($a, $b) {
        if ($a['is_best'] && !$b['is_best']) {
            return -1;
        }
        if (!$a['is_best'] && $b['is_best']) {
            return 1;
        }
        $voteDiff = ($b['votes'] ?? 0) <=> ($a['votes'] ?? 0);
        if ($voteDiff !== 0) {
            return $voteDiff;
        }
        return strcmp($a['created_at'] ?? '', $b['created_at'] ?? '');
    });

    $sortReplies = static function (&$nodes) use (&$sortReplies) {
        usort($nodes, static function ($a, $b) {
            if ($a['is_best'] && !$b['is_best']) {
                return -1;
            }
            if (!$a['is_best'] && $b['is_best']) {
                return 1;
            }
            $voteDiff = ($b['votes'] ?? 0) <=> ($a['votes'] ?? 0);
            if ($voteDiff !== 0) {
                return $voteDiff;
            }
            return strcmp($a['created_at'] ?? '', $b['created_at'] ?? '');
        });
        foreach ($nodes as &$node) {
            if (!empty($node['replies'])) {
                $sortReplies($node['replies']);
            }
        }
    };
    unset($node);
    $sortReplies($roots);

    return $roots;
}

function discCountAllAnswers(array $answers): int
{
    $count = 0;
    $walk = static function (array $items) use (&$walk, &$count) {
        foreach ($items as $item) {
            $count++;
            if (!empty($item['replies'])) {
                $walk($item['replies']);
            }
        }
    };
    $walk($answers);
    return $count;
}

function discFetchAnswers(PDO $pdo, int $discussionId, ?int $viewerId): array
{
    $st = $pdo->prepare(
        'SELECT a.*, u.first_name, u.last_name, u.avatar, u.is_verified
         FROM discussion_answers a
         JOIN users u ON u.id = a.author_id
         WHERE a.discussion_id = ?
         ORDER BY a.created_at ASC'
    );
    $st->execute([$discussionId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $flat = [];

    foreach ($rows as $row) {
        $userVote = 0;
        if ($viewerId) {
            $vs = $pdo->prepare(
                'SELECT value FROM discussion_votes WHERE user_id = ? AND answer_id = ? LIMIT 1'
            );
            $vs->execute([$viewerId, (int)$row['id']]);
            $userVote = (int)($vs->fetchColumn() ?: 0);
        }
        $flat[] = discShapeAnswerRow($row, $userVote);
    }

    return discBuildAnswerTree($flat);
}

try {
    ensureDiscussionsSchema($pdo);

    $method = $_SERVER['REQUEST_METHOD'];
    $viewerId = verifyTokenSoft();
    $action = trim($_GET['action'] ?? $_POST['action'] ?? '');

    if ($method === 'GET') {
        if ($action === 'list' || $action === '') {
            $tab = trim($_GET['tab'] ?? 'new');
            if (!in_array($tab, ['new', 'active', 'unanswered', 'my'], true)) {
                $tab = 'new';
            }
            if ($tab === 'my' && !$viewerId) {
                discJson(['error' => 'Требуется авторизация'], 401);
            }

            $where = '1=1';
            $params = [];
            $order = 'd.created_at DESC';

            if ($tab === 'active') {
                $where .= " AND d.status = 'open'";
                $order = 'd.last_activity_at DESC';
            } elseif ($tab === 'unanswered') {
                $where .= " AND d.answers_count = 0 AND d.status = 'open'";
                $order = 'd.created_at DESC';
            } elseif ($tab === 'my') {
                $where .= ' AND d.author_id = ?';
                $params[] = (int)$viewerId;
                $order = 'd.last_activity_at DESC';
            }

            $sql = "
                SELECT d.*, u.first_name, u.last_name, u.avatar, u.is_verified
                FROM discussions d
                JOIN users u ON u.id = d.author_id
                WHERE {$where}
                ORDER BY {$order}
                LIMIT 50
            ";
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $items = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $author = [
                    'id'          => (int)$row['author_id'],
                    'first_name'  => $row['first_name'] ?? '',
                    'last_name'   => $row['last_name'] ?? '',
                    'avatar'      => $row['avatar'] ?? null,
                    'is_verified' => !empty($row['is_verified']),
                    'name'        => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                ];
                $thread = discShapeThread($row, $author);
                $thread['is_author'] = $viewerId && (int)$row['author_id'] === (int)$viewerId;
                $items[] = $thread;
            }
            discJson(['discussions' => $items, 'tab' => $tab]);
        }

        if ($action === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                discJson(['error' => 'id required'], 400);
            }
            $st = $pdo->prepare(
                'SELECT d.*, u.first_name, u.last_name, u.avatar, u.is_verified
                 FROM discussions d
                 JOIN users u ON u.id = d.author_id
                 WHERE d.id = ? LIMIT 1'
            );
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                discJson(['error' => 'Обсуждение не найдено'], 404);
            }
            $author = [
                'id'          => (int)$row['author_id'],
                'first_name'  => $row['first_name'] ?? '',
                'last_name'   => $row['last_name'] ?? '',
                'avatar'      => $row['avatar'] ?? null,
                'is_verified' => !empty($row['is_verified']),
                'name'        => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            ];
            $discussion = discShapeThread($row, $author);
            $discussion['is_author'] = $viewerId && (int)$row['author_id'] === (int)$viewerId;
            $answers = discFetchAnswers($pdo, $id, $viewerId ? (int)$viewerId : null);
            $discussion['answers'] = $answers;
            $discussion['comments_count'] = discCountAllAnswers($answers);
            discJson(['discussion' => $discussion]);
        }

        discJson(['error' => 'Unknown action'], 400);
    }

    if ($method === 'POST') {
        $userId = verifyToken();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = trim($data['action'] ?? $action);

        if ($action === 'create') {
            $title = trim((string)($data['title'] ?? ''));
            $body = trim((string)($data['body'] ?? ''));
            $tags = discTagsString(discParseTags((string)($data['tags'] ?? '')));

            $titleError = discValidateTitle($title);
            if ($titleError) {
                discJson(['error' => $titleError], 400);
            }

            $pdo->prepare(
                'INSERT INTO discussions (author_id, title, body, tags, status, answers_count, last_activity_at)
                 VALUES (?, ?, ?, ?, \'open\', 0, UTC_TIMESTAMP())'
            )->execute([$userId, $title, $body ?: null, $tags ?: null]);
            $id = (int)$pdo->lastInsertId();

            $st = $pdo->prepare('SELECT * FROM discussions WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $discussion = discShapeThread($row, discShapeAuthor($pdo, (int)$userId));
            discJson(['success' => true, 'discussion' => $discussion]);
        }

        if ($action === 'answer') {
            $discussionId = (int)($data['discussion_id'] ?? 0);
            $parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : 0;
            $body = trim((string)($data['body'] ?? ''));
            if (!$discussionId || $body === '') {
                discJson(['error' => 'discussion_id и body обязательны'], 400);
            }

            $st = $pdo->prepare('SELECT id, status FROM discussions WHERE id = ? LIMIT 1');
            $st->execute([$discussionId]);
            $disc = $st->fetch(PDO::FETCH_ASSOC);
            if (!$disc) {
                discJson(['error' => 'Обсуждение не найдено'], 404);
            }
            if (($disc['status'] ?? 'open') === 'resolved') {
                discJson(['error' => 'Обсуждение закрыто'], 403);
            }

            $parentIdVal = null;
            if ($parentId > 0) {
                $pst = $pdo->prepare(
                    'SELECT id, discussion_id FROM discussion_answers WHERE id = ? LIMIT 1'
                );
                $pst->execute([$parentId]);
                $parent = $pst->fetch(PDO::FETCH_ASSOC);
                if (!$parent) {
                    discJson(['error' => 'Родительский ответ не найден'], 404);
                }
                if ((int)$parent['discussion_id'] !== $discussionId) {
                    discJson(['error' => 'Ответ принадлежит другому обсуждению'], 400);
                }
                $parentIdVal = $parentId;
            }

            $pdo->prepare(
                'INSERT INTO discussion_answers (discussion_id, parent_id, author_id, body) VALUES (?, ?, ?, ?)'
            )->execute([$discussionId, $parentIdVal, $userId, $body]);

            if ($parentIdVal === null) {
                $pdo->prepare(
                    'UPDATE discussions SET answers_count = answers_count + 1, last_activity_at = UTC_TIMESTAMP() WHERE id = ?'
                )->execute([$discussionId]);
            } else {
                $pdo->prepare(
                    'UPDATE discussions SET last_activity_at = UTC_TIMESTAMP() WHERE id = ?'
                )->execute([$discussionId]);
            }

            $answerId = (int)$pdo->lastInsertId();
            $answers = discFetchAnswers($pdo, $discussionId, (int)$userId);
            $answer = null;
            $findAnswer = static function (array $items) use (&$findAnswer, $answerId, &$answer) {
                foreach ($items as $a) {
                    if ($a['id'] === $answerId) {
                        $answer = $a;
                        return;
                    }
                    if (!empty($a['replies'])) {
                        $findAnswer($a['replies']);
                    }
                }
            };
            $findAnswer($answers);
            discJson([
                'success'         => true,
                'answer'          => $answer,
                'answers_count'   => count($answers),
                'comments_count'  => discCountAllAnswers($answers),
            ]);
        }

        if ($action === 'vote') {
            $answerId = (int)($data['answer_id'] ?? 0);
            $value = (int)($data['value'] ?? 0);
            if (!$answerId || !in_array($value, [1, -1], true)) {
                discJson(['error' => 'answer_id и value (1|-1) обязательны'], 400);
            }

            $st = $pdo->prepare('SELECT id FROM discussion_answers WHERE id = ? LIMIT 1');
            $st->execute([$answerId]);
            if (!$st->fetchColumn()) {
                discJson(['error' => 'Ответ не найден'], 404);
            }

            $existing = $pdo->prepare(
                'SELECT value FROM discussion_votes WHERE user_id = ? AND answer_id = ? LIMIT 1'
            );
            $existing->execute([$userId, $answerId]);
            $prev = $existing->fetchColumn();

            if ($prev !== false && (int)$prev === $value) {
                $pdo->prepare('DELETE FROM discussion_votes WHERE user_id = ? AND answer_id = ?')
                    ->execute([$userId, $answerId]);
                $userVote = 0;
            } elseif ($prev !== false) {
                $pdo->prepare('UPDATE discussion_votes SET value = ? WHERE user_id = ? AND answer_id = ?')
                    ->execute([$value, $userId, $answerId]);
                $userVote = $value;
            } else {
                $pdo->prepare('INSERT INTO discussion_votes (user_id, answer_id, value) VALUES (?, ?, ?)')
                    ->execute([$userId, $answerId, $value]);
                $userVote = $value;
            }

            $votes = discRecalcAnswerVotes($pdo, $answerId);
            discJson(['success' => true, 'votes' => $votes, 'user_vote' => $userVote]);
        }

        if ($action === 'mark_best') {
            $answerId = (int)($data['answer_id'] ?? 0);
            if (!$answerId) {
                discJson(['error' => 'answer_id required'], 400);
            }

            $st = $pdo->prepare(
                'SELECT a.id, a.discussion_id, a.parent_id, d.author_id
                 FROM discussion_answers a
                 JOIN discussions d ON d.id = a.discussion_id
                 WHERE a.id = ? LIMIT 1'
            );
            $st->execute([$answerId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                discJson(['error' => 'Ответ не найден'], 404);
            }
            if ($row['parent_id'] !== null) {
                discJson(['error' => 'Лучшим можно отметить только основной ответ'], 400);
            }
            if ((int)$row['author_id'] !== (int)$userId) {
                discJson(['error' => 'Только автор треда может отметить лучший ответ'], 403);
            }

            $discussionId = (int)$row['discussion_id'];
            $pdo->prepare('UPDATE discussion_answers SET is_best = FALSE WHERE discussion_id = ?')
                ->execute([$discussionId]);
            $pdo->prepare('UPDATE discussion_answers SET is_best = TRUE WHERE id = ?')
                ->execute([$answerId]);
            $pdo->prepare(
                'UPDATE discussions SET best_answer_id = ?, status = \'resolved\', last_activity_at = UTC_TIMESTAMP() WHERE id = ?'
            )->execute([$answerId, $discussionId]);

            discJson(['success' => true, 'status' => 'resolved', 'best_answer_id' => $answerId]);
        }

        if ($action === 'resolve') {
            $discussionId = (int)($data['discussion_id'] ?? 0);
            if (!$discussionId) {
                discJson(['error' => 'discussion_id required'], 400);
            }
            $st = $pdo->prepare('SELECT author_id FROM discussions WHERE id = ? LIMIT 1');
            $st->execute([$discussionId]);
            $authorId = (int)$st->fetchColumn();
            if (!$authorId) {
                discJson(['error' => 'Обсуждение не найдено'], 404);
            }
            if ($authorId !== (int)$userId) {
                discJson(['error' => 'Только автор может закрыть обсуждение'], 403);
            }
            $pdo->prepare(
                'UPDATE discussions SET status = \'resolved\', last_activity_at = UTC_TIMESTAMP() WHERE id = ?'
            )->execute([$discussionId]);
            discJson(['success' => true, 'status' => 'resolved']);
        }

        if ($action === 'delete') {
            $discussionId = (int)($data['discussion_id'] ?? 0);
            if (!$discussionId) {
                discJson(['error' => 'discussion_id required'], 400);
            }

            $st = $pdo->prepare('SELECT author_id FROM discussions WHERE id = ? LIMIT 1');
            $st->execute([$discussionId]);
            $authorId = (int)$st->fetchColumn();
            if (!$authorId) {
                discJson(['error' => 'Обсуждение не найдено'], 404);
            }
            if ($authorId !== (int)$userId) {
                discJson(['error' => 'Только автор может удалить обсуждение'], 403);
            }

            $pdo->prepare(
                'DELETE dv FROM discussion_votes dv
                 INNER JOIN discussion_answers da ON da.id = dv.answer_id
                 WHERE da.discussion_id = ?'
            )->execute([$discussionId]);
            $pdo->prepare('DELETE FROM discussions WHERE id = ? AND author_id = ?')
                ->execute([$discussionId, $userId]);

            discJson(['success' => true, 'deleted' => true, 'discussion_id' => $discussionId]);
        }

        discJson(['error' => 'Unknown action'], 400);
    }

    discJson(['error' => 'Method not allowed'], 405);
} catch (PDOException $e) {
    discJson(['error' => 'DB: ' . $e->getMessage()], 500);
} catch (Throwable $e) {
    discJson(['error' => $e->getMessage()], 500);
}
