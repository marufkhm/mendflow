<?php
/**
 * feed_ranking.php — алгоритм рекомендаций ленты (smart feed)
 */
error_reporting(0);
ini_set('display_errors', 0);

const FR_W_LIKE       = 1.0;
const FR_W_COMMENT    = 1.5;
const FR_W_REPOST     = 2.0;
const FR_W_DM_SHARE   = 4.0;
const FR_W_READ_SEC   = 0.8;
const FR_W_WATCH_SEC  = 2.5;
const FR_DECAY_LAMBDA = 0.05;
const FR_REPOST_HOUR_CAP = 15;
const FR_REPOST_SPIKE_MULT = 0.2;
const FR_TEST_MIN_IMPRESSIONS = 15;
const FR_TEST_CTR_EXPAND = 0.06;
const FR_TEST_AUDIENCE_BOOST = 28.0;
const FR_AFFINITY_MULT = 0.6;

function ensureFeedRankingSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS post_engagement (
        post_id         INT NOT NULL PRIMARY KEY,
        impressions     INT NOT NULL DEFAULT 0,
        clicks          INT NOT NULL DEFAULT 0,
        read_time_ms    BIGINT NOT NULL DEFAULT 0,
        watch_time_ms   BIGINT NOT NULL DEFAULT 0,
        dm_shares       INT NOT NULL DEFAULT 0,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS post_dm_shares (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        post_id     INT NOT NULL,
        from_user_id INT NOT NULL,
        to_user_id  INT NOT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_pds_post (post_id),
        KEY idx_pds_from (from_user_id),
        KEY idx_pds_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_affinity (
        user_low    INT NOT NULL,
        user_high   INT NOT NULL,
        score       FLOAT NOT NULL DEFAULT 0,
        likes       INT NOT NULL DEFAULT 0,
        comments    INT NOT NULL DEFAULT 0,
        dm_shares   INT NOT NULL DEFAULT 0,
        updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_low, user_high),
        KEY idx_affinity_score (score)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS post_distribution (
        post_id             INT NOT NULL PRIMARY KEY,
        tier                ENUM('test','expanded','full') NOT NULL DEFAULT 'test',
        test_impressions    INT NOT NULL DEFAULT 0,
        test_clicks         INT NOT NULL DEFAULT 0,
        test_started_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expanded_at         TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS post_signal_events (
        id          BIGINT AUTO_INCREMENT PRIMARY KEY,
        post_id     INT NOT NULL,
        user_id     INT NOT NULL,
        signal_type ENUM('impression','click','read_ms','watch_ms') NOT NULL,
        value       INT NOT NULL DEFAULT 0,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_pse_post (post_id),
        KEY idx_pse_user (user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function frNormalizePair(int $a, int $b): array
{
    return $a < $b ? [$a, $b] : [$b, $a];
}

function frGetFriendIds(PDO $pdo, int $userId): array
{
    if (!$userId) return [];
    static $cache = [];
    if (isset($cache[$userId])) return $cache[$userId];

    $friendIds = [];
    try {
        $fCols = $pdo->query('SHOW COLUMNS FROM friendships')->fetchAll(PDO::FETCH_COLUMN);
        if ($fCols) {
            if (in_array('sender_id', $fCols, true)) {
                $fStmt = $pdo->prepare("
                    SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS fid
                    FROM friendships
                    WHERE status = 'accepted' AND (sender_id = ? OR receiver_id = ?)
                ");
                $fStmt->execute([$userId, $userId, $userId]);
            } else {
                $fStmt = $pdo->prepare("
                    SELECT CASE WHEN user1_id = ? THEN user2_id ELSE user1_id END AS fid
                    FROM friendships
                    WHERE user1_id = ? OR user2_id = ?
                ");
                $fStmt->execute([$userId, $userId, $userId]);
            }
            $friendIds = array_values(array_unique(array_map('intval', $fStmt->fetchAll(PDO::FETCH_COLUMN))));
        }
    } catch (Throwable $e) {}

    $cache[$userId] = $friendIds;
    return $friendIds;
}

function frGetSecondCircleIds(PDO $pdo, int $userId, ?array $friendIds = null): array
{
    if (!$userId) return [];
    static $cache = [];
    $key = 'sc_' . $userId;
    if (isset($cache[$key])) return $cache[$key];

    $friendIds = $friendIds ?? frGetFriendIds($pdo, $userId);
    if (!$friendIds) {
        $cache[$key] = [];
        return [];
    }

    $exclude = array_flip(array_merge($friendIds, [$userId]));
    $second = [];
    foreach ($friendIds as $fid) {
        foreach (frGetFriendIds($pdo, (int)$fid) as $fof) {
            if (!isset($exclude[$fof])) $second[$fof] = true;
        }
    }
    $cache[$key] = array_map('intval', array_keys($second));
    return $cache[$key];
}

function frAuthorSecondCircle(PDO $pdo, int $authorId): array
{
    static $cache = [];
    if (isset($cache[$authorId])) return $cache[$authorId];
    $friends = frGetFriendIds($pdo, $authorId);
    $cache[$authorId] = frGetSecondCircleIds($pdo, $authorId, $friends);
    return $cache[$authorId];
}

function frBumpAffinity(PDO $pdo, int $userA, int $userB, string $kind): void
{
    if (!$userA || !$userB || $userA === $userB) return;
    ensureFeedRankingSchema($pdo);

    if ($kind === 'like') {
        $delta = 1.0;
    } elseif ($kind === 'comment') {
        $delta = 2.0;
    } elseif ($kind === 'dm_share') {
        $delta = 5.0;
    } else {
        $delta = 0.5;
    }
    [$low, $high] = frNormalizePair($userA, $userB);
    $col = in_array($kind, ['like', 'comment', 'dm_share'], true) ? ($kind === 'dm_share' ? 'dm_shares' : ($kind . 's')) : null;

    $pdo->prepare("
        INSERT INTO user_affinity (user_low, user_high, score, likes, comments, dm_shares)
        VALUES (?, ?, ?, 0, 0, 0)
        ON DUPLICATE KEY UPDATE score = score + VALUES(score)
    ")->execute([$low, $high, $delta]);

    if ($col) {
        $pdo->prepare("UPDATE user_affinity SET {$col} = {$col} + 1 WHERE user_low = ? AND user_high = ?")
            ->execute([$low, $high]);
    }
}

function frGetAffinity(PDO $pdo, int $viewerId, int $authorId): float
{
    if (!$viewerId || !$authorId || $viewerId === $authorId) return 0.0;
    [$low, $high] = frNormalizePair($viewerId, $authorId);
    $stmt = $pdo->prepare('SELECT score FROM user_affinity WHERE user_low = ? AND user_high = ?');
    $stmt->execute([$low, $high]);
    return (float)($stmt->fetchColumn() ?: 0);
}

function frInitPostDistribution(PDO $pdo, int $postId): void
{
    ensureFeedRankingSchema($pdo);
    $pdo->prepare("INSERT IGNORE INTO post_distribution (post_id, tier) VALUES (?, 'test')")->execute([$postId]);
    $pdo->prepare("INSERT IGNORE INTO post_engagement (post_id) VALUES (?)")->execute([$postId]);
}

function frRecordDmShare(PDO $pdo, int $postId, int $fromUserId, int $toUserId): void
{
    if (!$postId || !$fromUserId || !$toUserId) return;
    ensureFeedRankingSchema($pdo);

    $pdo->prepare('INSERT INTO post_dm_shares (post_id, from_user_id, to_user_id) VALUES (?,?,?)')
        ->execute([$postId, $fromUserId, $toUserId]);

    $pdo->prepare('
        INSERT INTO post_engagement (post_id, dm_shares) VALUES (?, 1)
        ON DUPLICATE KEY UPDATE dm_shares = dm_shares + 1
    ')->execute([$postId]);

    $authorStmt = $pdo->prepare('SELECT user_id FROM posts WHERE id = ?');
    $authorStmt->execute([$postId]);
    $authorId = (int)$authorStmt->fetchColumn();
    if ($authorId) {
        frBumpAffinity($pdo, $fromUserId, $authorId, 'dm_share');
    }
    frBumpAffinity($pdo, $fromUserId, $toUserId, 'dm_share');
}

function frRecordSignals(PDO $pdo, int $userId, array $batch): void
{
    if (!$userId || !$batch) return;
    ensureFeedRankingSchema($pdo);

    $engUpd = $pdo->prepare('
        INSERT INTO post_engagement (post_id, impressions, clicks, read_time_ms, watch_time_ms)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            impressions   = impressions + VALUES(impressions),
            clicks        = clicks + VALUES(clicks),
            read_time_ms  = read_time_ms + VALUES(read_time_ms),
            watch_time_ms = watch_time_ms + VALUES(watch_time_ms)
    ');
    $evtIns = $pdo->prepare('
        INSERT INTO post_signal_events (post_id, user_id, signal_type, value) VALUES (?,?,?,?)
    ');
    $distUpd = $pdo->prepare('
        UPDATE post_distribution SET test_impressions = test_impressions + ?, test_clicks = test_clicks + ?
        WHERE post_id = ? AND tier = \'test\'
    ');
    $distGet = $pdo->prepare('SELECT tier, test_impressions, test_clicks FROM post_distribution WHERE post_id = ?');
    $distExpand = $pdo->prepare("UPDATE post_distribution SET tier = 'expanded', expanded_at = NOW() WHERE post_id = ?");

    foreach ($batch as $item) {
        $postId = (int)($item['post_id'] ?? 0);
        if (!$postId) continue;

        $impressions = (int)($item['impression'] ?? 0);
        $clicks      = (int)($item['click'] ?? 0);
        $readMs      = (int)($item['read_ms'] ?? 0);
        $watchMs     = (int)($item['watch_ms'] ?? 0);

        if (!$impressions && !$clicks && !$readMs && !$watchMs) continue;

        $engUpd->execute([$postId, $impressions, $clicks, $readMs, $watchMs]);

        if ($impressions) $evtIns->execute([$postId, $userId, 'impression', $impressions]);
        if ($clicks)      $evtIns->execute([$postId, $userId, 'click', $clicks]);
        if ($readMs)      $evtIns->execute([$postId, $userId, 'read_ms', $readMs]);
        if ($watchMs)     $evtIns->execute([$postId, $userId, 'watch_ms', $watchMs]);

        if ($impressions || $clicks) {
            $distUpd->execute([$impressions, $clicks, $postId]);
            $distGet->execute([$postId]);
            $dist = $distGet->fetch(PDO::FETCH_ASSOC);
            if ($dist && $dist['tier'] === 'test') {
                $imp = (int)$dist['test_impressions'];
                $clk = (int)$dist['test_clicks'];
                if ($imp >= FR_TEST_MIN_IMPRESSIONS && $imp > 0 && ($clk / $imp) >= FR_TEST_CTR_EXPAND) {
                    $distExpand->execute([$postId]);
                }
            }
        }
    }
}

function frRepostsLastHour(PDO $pdo, int $postId): int
{
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reposts WHERE post_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)');
        $stmt->execute([$postId]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function frTimeDecay(string $createdAt): float
{
    $ts = strtotime($createdAt);
    if (!$ts) return 1.0;
    $ageHours = max(0, (time() - $ts) / 3600);
    return exp(-FR_DECAY_LAMBDA * $ageHours);
}

function frComputePostScore(PDO $pdo, array $post, int $viewerId, array $ctx = []): float
{
    $postId   = (int)($post['id'] ?? 0);
    $authorId = (int)($post['user_id'] ?? 0);
    if (!$postId) return 0.0;

    ensureFeedRankingSchema($pdo);

    $likes    = (int)($post['likes_count'] ?? 0);
    $comments = (int)($post['comments_count'] ?? 0);
    $reposts  = (int)($post['reposts_count'] ?? 0);

    $eng = ['impressions' => 0, 'read_time_ms' => 0, 'watch_time_ms' => 0, 'dm_shares' => 0];
    $stmt = $pdo->prepare('SELECT impressions, read_time_ms, watch_time_ms, dm_shares FROM post_engagement WHERE post_id = ?');
    $stmt->execute([$postId]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $eng = $row;

    $dmShares = (int)$eng['dm_shares'];
    $readSec  = ((int)$eng['read_time_ms']) / 1000;
    $watchSec = ((int)$eng['watch_time_ms']) / 1000;

    $repostsHour = frRepostsLastHour($pdo, $postId);
    $repostEffective = $reposts;
    if ($repostsHour > FR_REPOST_HOUR_CAP) {
        $repostEffective = (int)round($reposts * FR_REPOST_SPIKE_MULT);
    }

    $engagement =
        $likes * FR_W_LIKE +
        $comments * FR_W_COMMENT +
        $repostEffective * FR_W_REPOST +
        $dmShares * FR_W_DM_SHARE +
        $readSec * FR_W_READ_SEC +
        $watchSec * FR_W_WATCH_SEC;

    $decay = frTimeDecay($post['created_at'] ?? $post['sort_at'] ?? 'now');
    $score = $engagement * $decay;

    if ($viewerId && $authorId) {
        $score += frGetAffinity($pdo, $viewerId, $authorId) * FR_AFFINITY_MULT;
    }

    $distStmt = $pdo->prepare('SELECT tier FROM post_distribution WHERE post_id = ?');
    $distStmt->execute([$postId]);
    $tier = $distStmt->fetchColumn() ?: 'test';

    $authorSecond = $ctx['author_second_circles'][$authorId] ?? frAuthorSecondCircle($pdo, $authorId);
    $inTestAudience = $viewerId && in_array($viewerId, $authorSecond, true);

    if ($tier === 'test') {
        if ($inTestAudience) {
            $score += FR_TEST_AUDIENCE_BOOST;
        } else {
            $score *= 0.15;
        }
    } elseif ($tier === 'expanded') {
        $score *= 1.15;
    }

    return round($score, 4);
}

function frRankPosts(PDO $pdo, array $posts, int $viewerId): array
{
    if (count($posts) < 2) return $posts;

    $authorCircles = [];
    foreach ($posts as $p) {
        $aid = (int)($p['user_id'] ?? 0);
        if ($aid && !isset($authorCircles[$aid])) {
            $authorCircles[$aid] = frAuthorSecondCircle($pdo, $aid);
        }
    }

    foreach ($posts as &$p) {
        $p['feed_score'] = frComputePostScore($pdo, $p, $viewerId, ['author_second_circles' => $authorCircles]);
    }
    unset($p);

    usort($posts, function ($a, $b) {
        $sa = (float)($a['feed_score'] ?? 0);
        $sb = (float)($b['feed_score'] ?? 0);
        if ($sa !== $sb) return $sb <=> $sa;
        return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
    });

    return $posts;
}

function frFetchRecommendedCandidates(PDO $pdo, int $viewerId, array $excludeIds, string $textCol, string $imageCol, int $limit = 120): array
{
    if (!$viewerId) return [];
    $excludeIds = array_values(array_unique(array_filter(array_map('intval', $excludeIds))));
    $exSql = $excludeIds ? 'AND p.user_id NOT IN (' . implode(',', $excludeIds) . ')' : '';
    $viewerSql = (int)$viewerId;

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.{$textCol}   AS text,
            p.{$imageCol}  AS image_url,
            p.created_at,
            u.id           AS user_id,
            u.first_name,
            u.last_name,
            u.avatar,
            u.is_verified,
            'user'         AS author_type,
            NULL           AS entity_id,
            NULL           AS university_id,
            0              AS is_repost,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = {$viewerSql}) AS user_liked,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
            (SELECT COUNT(*) FROM reposts WHERE post_id = p.id) AS reposts_count,
            (SELECT COUNT(*) FROM reposts WHERE post_id = p.id AND user_id = {$viewerSql}) AS user_reposted
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 21 DAY)
        {$exSql}
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
