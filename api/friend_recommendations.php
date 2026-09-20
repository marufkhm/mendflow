<?php
/**
 * Friend recommendations — scoring by social graph signals.
 */
require_once __DIR__ . '/feed_ranking.php';
require_once __DIR__ . '/badges_lib.php';

function frRecTableExists(PDO $pdo, string $table): bool
{
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];
    try {
        $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
        $cache[$table] = (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$table] = false;
    }
    return $cache[$table];
}

function frRecExcludeIds(PDO $pdo, int $userId): array
{
    $exclude = array_merge([$userId], frGetFriendIds($pdo, $userId));
    try {
        $st = $pdo->prepare('SELECT to_id FROM friend_requests WHERE from_id = ? AND status = "pending"');
        $st->execute([$userId]);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $exclude[] = (int)$id;
        }
        $st = $pdo->prepare('SELECT from_id FROM friend_requests WHERE to_id = ? AND status = "pending"');
        $st->execute([$userId]);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $exclude[] = (int)$id;
        }
    } catch (Throwable $e) {}
    return array_values(array_unique(array_map('intval', $exclude)));
}

function frRecAddReason(array &$scores, int $uid, string $type, string $label, float $weight): void
{
    if ($uid <= 0 || $weight <= 0) return;
    if (!isset($scores[$uid])) {
        $scores[$uid] = ['score' => 0, 'reasons' => []];
    }
    $scores[$uid]['score'] += $weight;
    $scores[$uid]['reasons'][] = ['type' => $type, 'label' => $label, 'weight' => $weight];
}

function frRecPlaceholders(array $ids): string
{
    return $ids ? implode(',', array_fill(0, count($ids), '?')) : '0';
}

function frRecMutualFriends(PDO $pdo, int $userId, array $friendIds, array $exclude, array &$scores): void
{
    if (!$friendIds) return;
    $fPh = frRecPlaceholders($friendIds);
    $xPh = frRecPlaceholders($exclude);
    $params = array_merge($friendIds, $friendIds, $friendIds, $exclude);
    try {
        $sql = "
            SELECT candidate_id, COUNT(DISTINCT mutual_id) AS cnt
            FROM (
                SELECT
                    CASE WHEN f.sender_id IN ($fPh) THEN f.receiver_id ELSE f.sender_id END AS candidate_id,
                    CASE WHEN f.sender_id IN ($fPh) THEN f.sender_id ELSE f.receiver_id END AS mutual_id
                FROM friendships f
                WHERE f.status = 'accepted'
                  AND (f.sender_id IN ($fPh) OR f.receiver_id IN ($fPh))
            ) t
            WHERE candidate_id IS NOT NULL
              AND candidate_id NOT IN ($xPh)
              AND candidate_id != mutual_id
            GROUP BY candidate_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cnt = (int)$row['cnt'];
            if ($cnt <= 0) continue;
            $uid = (int)$row['candidate_id'];
            $label = $cnt === 1 ? '1 общий друг' : $cnt . ' общих друга';
            frRecAddReason($scores, $uid, 'mutual_friends', $label, $cnt * 15);
        }
    } catch (Throwable $e) {}
}

function frRecSharedProjects(PDO $pdo, int $userId, array $exclude, array &$scores): void
{
    if (!frRecTableExists($pdo, 'project_members') || !frRecTableExists($pdo, 'projects')) return;
    $xPh = frRecPlaceholders($exclude);
    $params = array_merge([$userId], $exclude);
    try {
        $sql = "
            SELECT pm2.user_id AS candidate_id, COUNT(DISTINCT pm1.project_id) AS cnt,
                   MIN(p.title) AS sample_title
            FROM project_members pm1
            JOIN project_members pm2 ON pm1.project_id = pm2.project_id AND pm1.user_id != pm2.user_id
            JOIN projects p ON p.id = pm1.project_id
            WHERE pm1.user_id = ?
              AND pm2.user_id NOT IN ($xPh)
            GROUP BY pm2.user_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cnt = (int)$row['cnt'];
            $uid = (int)$row['candidate_id'];
            $title = trim((string)($row['sample_title'] ?? ''));
            $label = $cnt === 1 && $title !== ''
                ? 'Проект «' . mb_substr($title, 0, 28) . '»'
                : ($cnt . ' общих проекта');
            frRecAddReason($scores, $uid, 'shared_projects', $label, $cnt * 12);
        }
    } catch (Throwable $e) {}
}

function frRecSharedSubscriptions(PDO $pdo, int $userId, array $exclude, array &$scores): void
{
    if (frRecTableExists($pdo, 'uni_follows')) {
        $xPh = frRecPlaceholders($exclude);
        $params = array_merge([$userId], $exclude);
        try {
            $sql = "
                SELECT uf2.user_id AS candidate_id, COUNT(DISTINCT uf1.university_id) AS cnt
                FROM uni_follows uf1
                JOIN uni_follows uf2 ON uf1.university_id = uf2.university_id AND uf1.user_id != uf2.user_id
                WHERE uf1.user_id = ?
                  AND uf2.user_id NOT IN ($xPh)
                GROUP BY uf2.user_id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cnt = (int)$row['cnt'];
                $uid = (int)$row['candidate_id'];
                $label = $cnt === 1 ? '1 общая подписка' : $cnt . ' общих подписок';
                frRecAddReason($scores, $uid, 'shared_subscriptions', $label, $cnt * 8);
            }
        } catch (Throwable $e) {}
    }

    if (frRecTableExists($pdo, 'project_followers')) {
        $xPh = frRecPlaceholders($exclude);
        $params = array_merge([$userId], $exclude);
        try {
            $sql = "
                SELECT pf2.user_id AS candidate_id, COUNT(DISTINCT pf1.project_id) AS cnt
                FROM project_followers pf1
                JOIN project_followers pf2 ON pf1.project_id = pf2.project_id AND pf1.user_id != pf2.user_id
                WHERE pf1.user_id = ?
                  AND pf2.user_id NOT IN ($xPh)
                GROUP BY pf2.user_id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cnt = (int)$row['cnt'];
                $uid = (int)$row['candidate_id'];
                $label = $cnt === 1 ? 'Следите за одним проектом' : 'Общие подписки на проекты';
                frRecAddReason($scores, $uid, 'shared_subscriptions', $label, $cnt * 8);
            }
        } catch (Throwable $e) {}
    }
}

function frRecSharedLikes(PDO $pdo, int $userId, array $exclude, array &$scores): void
{
    if (!frRecTableExists($pdo, 'likes')) return;
    $xPh = frRecPlaceholders($exclude);
    $params = array_merge([$userId], $exclude);
    try {
        $sql = "
            SELECT l2.user_id AS candidate_id, COUNT(DISTINCT l1.post_id) AS cnt
            FROM likes l1
            JOIN likes l2 ON l1.post_id = l2.post_id AND l1.user_id != l2.user_id
            WHERE l1.user_id = ?
              AND l2.user_id NOT IN ($xPh)
            GROUP BY l2.user_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cnt = (int)$row['cnt'];
            $uid = (int)$row['candidate_id'];
            $label = $cnt === 1 ? 'Лайкнули один пост' : 'Лайки одних постов';
            frRecAddReason($scores, $uid, 'shared_likes', $label, $cnt * 6);
        }
    } catch (Throwable $e) {}
}

function frRecSharedComments(PDO $pdo, int $userId, array $exclude, array &$scores): void
{
    if (!frRecTableExists($pdo, 'comments')) return;
    $xPh = frRecPlaceholders($exclude);
    $params = array_merge([$userId], $exclude);
    try {
        $sql = "
            SELECT c2.user_id AS candidate_id, COUNT(DISTINCT c1.post_id) AS cnt
            FROM comments c1
            JOIN comments c2 ON c1.post_id = c2.post_id AND c1.user_id != c2.user_id
            WHERE c1.user_id = ?
              AND c2.user_id NOT IN ($xPh)
            GROUP BY c2.user_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cnt = (int)$row['cnt'];
            $uid = (int)$row['candidate_id'];
            $label = $cnt === 1 ? 'Комментировали один пост' : 'Общие обсуждения';
            frRecAddReason($scores, $uid, 'shared_comments', $label, $cnt * 5);
        }
    } catch (Throwable $e) {}
}

function frRecSharedReposts(PDO $pdo, int $userId, array $exclude, array &$scores): void
{
    if (!frRecTableExists($pdo, 'reposts')) return;
    $xPh = frRecPlaceholders($exclude);
    $params = array_merge([$userId], $exclude);
    try {
        $sql = "
            SELECT r2.user_id AS candidate_id, COUNT(DISTINCT r1.post_id) AS cnt
            FROM reposts r1
            JOIN reposts r2 ON r1.post_id = r2.post_id AND r1.user_id != r2.user_id
            WHERE r1.user_id = ?
              AND r2.user_id NOT IN ($xPh)
            GROUP BY r2.user_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cnt = (int)$row['cnt'];
            $uid = (int)$row['candidate_id'];
            $label = $cnt === 1 ? 'Репост одного поста' : 'Общие репосты';
            frRecAddReason($scores, $uid, 'shared_reposts', $label, $cnt * 7);
        }
    } catch (Throwable $e) {}
}

function frRecSameUniversity(PDO $pdo, int $userId, array $exclude, array &$scores): void
{
    try {
        $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('university_id', $cols, true)) return;
        $st = $pdo->prepare('SELECT university_id, organization FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $me = $st->fetch(PDO::FETCH_ASSOC);
        if (!$me) return;

        $xPh = frRecPlaceholders($exclude);
        if (!empty($me['university_id'])) {
            $params = array_merge([(int)$me['university_id']], $exclude);
            $sql = "SELECT id FROM users WHERE university_id = ? AND id NOT IN ($xPh) LIMIT 80";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                frRecAddReason($scores, (int)$uid, 'same_university', 'Один университет', 25);
            }
        } elseif (!empty($me['organization'])) {
            $params = array_merge([$me['organization']], $exclude);
            $sql = "SELECT id FROM users WHERE organization = ? AND id NOT IN ($xPh) LIMIT 80";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                frRecAddReason($scores, (int)$uid, 'same_university', 'Один вуз: ' . $me['organization'], 20);
            }
        }
    } catch (Throwable $e) {}
}

function frRecSameCity(PDO $pdo, int $userId, array $exclude, array &$scores): void
{
    try {
        $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('city', $cols, true)) return;
        $st = $pdo->prepare('SELECT city FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $city = trim((string)$st->fetchColumn());
        if ($city === '') return;

        $xPh = frRecPlaceholders($exclude);
        $params = array_merge([$city], $exclude);
        $sql = "SELECT id FROM users WHERE city = ? AND id NOT IN ($xPh) LIMIT 80";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            frRecAddReason($scores, (int)$uid, 'same_city', 'Город: ' . $city, 12);
        }
    } catch (Throwable $e) {}
}

function frRecAffinity(PDO $pdo, int $userId, array $exclude, array &$scores): void
{
    if (!frRecTableExists($pdo, 'user_affinity')) return;
    $xPh = frRecPlaceholders($exclude);
    $params = array_merge([$userId, $userId, $userId], $exclude);
    try {
        $sql = "
            SELECT other_id, score FROM (
                SELECT user_high AS other_id, score FROM user_affinity WHERE user_low = ?
                UNION ALL
                SELECT user_low AS other_id, score FROM user_affinity WHERE user_high = ?
            ) a
            WHERE other_id != ?
              AND other_id NOT IN ($xPh)
              AND score > 0
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $uid = (int)$row['other_id'];
            $s = (float)$row['score'];
            if ($s <= 0) continue;
            frRecAddReason($scores, $uid, 'content_interaction', 'Взаимодействие с контентом', round($s * 3, 1));
        }
    } catch (Throwable $e) {}
}

function frRecSecondCircle(PDO $pdo, int $userId, array $exclude, array &$scores): void
{
    $second = frGetSecondCircleIds($pdo, $userId);
    foreach ($second as $uid) {
        if (in_array($uid, $exclude, true)) continue;
        frRecAddReason($scores, $uid, 'second_circle', 'Друг ваших друзей', 8);
    }
}

function frGetRecommendations(PDO $pdo, int $userId, int $limit = 12): array
{
    $limit = max(1, min(30, $limit));
    $exclude = frRecExcludeIds($pdo, $userId);
    $friendIds = frGetFriendIds($pdo, $userId);
    $scores = [];

    frRecMutualFriends($pdo, $userId, $friendIds, $exclude, $scores);
    frRecSharedProjects($pdo, $userId, $exclude, $scores);
    frRecSharedSubscriptions($pdo, $userId, $exclude, $scores);
    frRecSharedLikes($pdo, $userId, $exclude, $scores);
    frRecSharedComments($pdo, $userId, $exclude, $scores);
    frRecSharedReposts($pdo, $userId, $exclude, $scores);
    frRecSameUniversity($pdo, $userId, $exclude, $scores);
    frRecSameCity($pdo, $userId, $exclude, $scores);
    frRecAffinity($pdo, $userId, $exclude, $scores);
    frRecSecondCircle($pdo, $userId, $exclude, $scores);

    if (!$scores) {
        return ['recommendations' => [], 'logic' => frRecLogicMeta()];
    }

    uasort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
    $topIds = array_slice(array_keys($scores), 0, $limit);

    $ph = frRecPlaceholders($topIds);
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, avatar, is_verified, organization, city
        FROM users
        WHERE id IN ($ph)
    ");
    $stmt->execute($topIds);
    $users = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[(int)$row['id']] = $row;
    }

    $badgeMap = mfComputeUserBadgesBatch($pdo, $topIds);

    $recommendations = [];
    foreach ($topIds as $uid) {
        if (!isset($users[$uid], $scores[$uid])) continue;
        $u = $users[$uid];
        $reasons = $scores[$uid]['reasons'];
        usort($reasons, fn($a, $b) => $b['weight'] <=> $a['weight']);
        $badges = $badgeMap[$uid] ?? mfBuildBadgePayload([]);
        $recommendations[] = [
            'id'          => (int)$u['id'],
            'first_name'  => $u['first_name'],
            'last_name'   => $u['last_name'],
            'avatar'      => $u['avatar'],
            'is_verified' => (bool)$u['is_verified'],
            'organization'=> $u['organization'] ?? null,
            'city'        => $u['city'] ?? null,
            'score'       => round($scores[$uid]['score'], 1),
            'reasons'     => array_slice($reasons, 0, 3),
            'badges'      => $badges,
            'primary_reason' => $reasons[0]['label'] ?? '',
        ];
    }

    return [
        'recommendations' => $recommendations,
        'logic' => frRecLogicMeta(),
    ];
}

function frRecLogicMeta(): array
{
    return [
        ['key' => 'mutual_friends',       'label' => 'Общие друзья',              'weight' => 15],
        ['key' => 'shared_projects',      'label' => 'Участники проектов',        'weight' => 12],
        ['key' => 'shared_subscriptions', 'label' => 'Общие подписки',             'weight' => 8],
        ['key' => 'shared_likes',         'label' => 'Лайки одних постов',        'weight' => 6],
        ['key' => 'same_university',      'label' => 'Один университет',          'weight' => 25],
        ['key' => 'same_city',            'label' => 'Один город',                'weight' => 12],
        ['key' => 'shared_comments',      'label' => 'Комментарии',               'weight' => 5],
        ['key' => 'shared_reposts',       'label' => 'Репосты',                   'weight' => 7],
        ['key' => 'content_interaction',  'label' => 'Взаимодействие с контентом', 'weight' => 3],
    ];
}
