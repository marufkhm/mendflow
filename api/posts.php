<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once __DIR__ . '/feed_ranking.php';

function postsEnsureArticleIdColumn(PDO $pdo): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    try {
        $st = $pdo->prepare(
            "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'article_id' LIMIT 1"
        );
        $st->execute();
        $has = (bool)$st->fetchColumn();
        if (!$has) {
            $pdo->exec('ALTER TABLE posts ADD COLUMN article_id INT NULL DEFAULT NULL');
            $has = true;
        }
    } catch (Throwable $e) {
        $has = false;
    }
    return $has;
}

function postsArticlePreview(?string $content, int $max = 120): string
{
    $plain = trim(preg_replace('/[#*`>\[\]_\-]+/u', ' ', (string)$content));
    $plain = preg_replace('/\s+/u', ' ', $plain) ?: '';
    if (mb_strlen($plain) <= $max) {
        return $plain;
    }
    return mb_substr($plain, 0, $max) . '…';
}

function postsShapeArticleRow(array $row): array
{
    return [
        'id'               => (int)$row['id'],
        'title'            => $row['title'] ?? '',
        'cover_image'      => $row['cover_image'] ?? null,
        'reading_time_min' => (int)($row['reading_time_min'] ?? 0),
        'preview'          => postsArticlePreview($row['content'] ?? ''),
        'status'           => $row['status'] ?? 'published',
    ];
}

function postsEnrichArticles(PDO $pdo, array &$posts): void
{
    if (!$posts) {
        return;
    }
    postsEnsureArticleIdColumn($pdo);

    $lookup = [];
    foreach ($posts as $i => $p) {
        $aid = (int)($p['article_id'] ?? 0);
        if ($aid) {
            $lookup[$aid] = true;
            continue;
        }
        $text = (string)($p['content'] ?? $p['text'] ?? '');
        if (preg_match('/Новая статья: «(.+?)»/u', $text, $m)) {
            $posts[$i]['_art_title_lookup'] = $m[1];
        }
    }

    foreach ($posts as $i => $p) {
        if (!empty($p['article_id']) || empty($p['_art_title_lookup'])) {
            continue;
        }
        try {
            $st = $pdo->prepare(
                "SELECT id, title, content, cover_image, reading_time_min, status
                 FROM articles WHERE author_id = ? AND title = ? ORDER BY id DESC LIMIT 1"
            );
            $st->execute([(int)($p['user_id'] ?? 0), $p['_art_title_lookup']]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $posts[$i]['article_id'] = (int)$row['id'];
                $lookup[(int)$row['id']] = true;
            }
        } catch (Throwable $e) {
        }
        unset($posts[$i]['_art_title_lookup']);
    }

    $ids = array_keys($lookup);
    if (!$ids) {
        return;
    }

    $in = implode(',', array_map('intval', $ids));
    $map = [];
    try {
        $st = $pdo->query(
            "SELECT id, title, content, cover_image, reading_time_min, status
             FROM articles WHERE id IN ({$in})"
        );
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $map[(int)$row['id']] = postsShapeArticleRow($row);
        }
    } catch (Throwable $e) {
        return;
    }

    foreach ($posts as &$p) {
        unset($p['_art_title_lookup']);
        $aid = (int)($p['article_id'] ?? 0);
        if ($aid && isset($map[$aid])) {
            $p['article'] = $map[$aid];
        }
    }
    unset($p);
}

try {

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['single']) && isset($_GET['id'])) {
        // ── Получить один пост по ID ─────────────────────────────────
        $userId  = verifyTokenSoft();
        $postId  = (int)$_GET['id'];
        if (!$postId) { http_response_code(400); echo json_encode(['error' => 'id required']); exit; }

        $cols    = $pdo->query("SHOW COLUMNS FROM posts")->fetchAll(PDO::FETCH_COLUMN);
        $textCol = in_array('text', $cols) ? 'text' : 'content';
        $imgCol  = in_array('image_url', $cols) ? 'image_url' : 'image';
        $postTypeCol = in_array('post_type', $cols, true) ? 'p.post_type' : "'post' AS post_type";
        $hasArtCol = postsEnsureArticleIdColumn($pdo);
        $artIdCol = $hasArtCol ? 'p.article_id' : 'NULL AS article_id';

        $stmt = $pdo->prepare("
            SELECT p.id, p.user_id, p.{$textCol} AS content, p.{$imgCol} AS media_url,
                   p.created_at, {$postTypeCol}, {$artIdCol},
                   u.first_name, u.last_name, u.avatar,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count
            FROM posts p
            JOIN users u ON u.id = p.user_id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$post) { http_response_code(404); echo json_encode(['error' => 'Пост не найден']); exit; }

        $post['id']             = (int)$post['id'];
        $post['user_id']        = (int)$post['user_id'];
        $post['likes_count']    = (int)$post['likes_count'];
        $post['comments_count'] = (int)$post['comments_count'];
        $post['image_url']      = $post['media_url'] ?? null;
        $post['created_at']     = utcDate($post['created_at']);
        if ($userId) {
            $lk = $pdo->prepare("SELECT 1 FROM likes WHERE post_id=? AND user_id=?");
            $lk->execute([$postId, $userId]);
            $post['user_liked'] = (bool)$lk->fetchColumn();
        }
        $one = [$post];
        postsEnrichArticles($pdo, $one);
        $post = $one[0];
        echo json_encode(['post' => $post]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId      = verifyTokenSoft();
        $canModerate = canModeratePosts($userId);
        $page        = max(1, (int)($_GET['page']   ?? 1));
        $limit       = min(50, (int)($_GET['limit']  ?? 10));
        $offset      = ($page - 1) * $limit;
        $targetUserId = (int)($_GET['user_id'] ?? 0);
        $mode        = trim($_GET['mode'] ?? 'mixed'); // mixed | friends | subscriptions | recommendations
        $section     = trim($_GET['section'] ?? 'feed'); // feed | discussions | articles
        $q           = trim($_GET['q']    ?? '');   // поисковый запрос
        if (!in_array($mode, ['mixed', 'friends', 'subscriptions', 'recommendations'], true)) {
            $mode = 'mixed';
        }
        if (!in_array($section, ['feed', 'discussions', 'articles'], true)) {
            $section = 'feed';
        }

        // ── Определяем реальные имена колонок в таблице posts ───────────
        $cols     = $pdo->query("SHOW COLUMNS FROM posts")->fetchAll(PDO::FETCH_COLUMN);
        $textCol  = in_array('text',      $cols) ? 'text'      : 'content';
        $imageCol = in_array('image_url', $cols) ? 'image_url' : 'image';
        $hasPostType = in_array('post_type', $cols, true);
        if (!$hasPostType) {
            try {
                $pdo->exec("ALTER TABLE posts ADD COLUMN post_type VARCHAR(32) NOT NULL DEFAULT 'post'");
                $hasPostType = true;
                $cols[] = 'post_type';
            } catch (Throwable $e) {}
        }
        $postTypeSelect = $hasPostType ? 'p.post_type' : "'post' AS post_type";
        $hasArticleIdCol = postsEnsureArticleIdColumn($pdo);
        $articleIdSelect = $hasArticleIdCol ? 'p.article_id' : 'NULL AS article_id';

        // ── ID друзей (для режимов ленты) ───────────────────────────────
        $friendIds = [];
        $isMainFeed = ($targetUserId === 0 && $q === '');
        if ($isMainFeed && $userId) {
            try {
                $fCols = $pdo->query('SHOW COLUMNS FROM friendships')->fetchAll(PDO::FETCH_COLUMN);
                if ($fCols) {
                    if (in_array('sender_id', $fCols, true)) {
                        $fStmt = $pdo->prepare("
                            SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS fid
                            FROM friendships
                            WHERE status = 'accepted' AND (sender_id = ? OR receiver_id = ?)
                        ");
                        $fStmt->execute([(int)$userId, (int)$userId, (int)$userId]);
                    } else {
                        $fStmt = $pdo->prepare("
                            SELECT CASE WHEN user1_id = ? THEN user2_id ELSE user1_id END AS fid
                            FROM friendships
                            WHERE (user1_id = ? OR user2_id = ?)
                        ");
                        $fStmt->execute([(int)$userId, (int)$userId, (int)$userId]);
                    }
                    $friendIds = array_values(array_unique(array_map('intval', $fStmt->fetchAll(PDO::FETCH_COLUMN))));
                }
            } catch (Throwable $e) {}
        }

        // ── 1. Пользовательские посты ───────────────────────────────────
        $params = [$userId ?? 0];          // для user_liked subquery
        $whereClauses = [];
        $orderSQL = 'ORDER BY p.created_at DESC';

        if ($targetUserId > 0) {
            $whereClauses[] = "p.user_id = ?";
            $params[] = $targetUserId;
        }
        if ($q !== '') {
            $whereClauses[] = "p.{$textCol} LIKE ?";
            $params[] = '%' . $q . '%';
        }

        // Режимы главной ленты
        // mixed — все публичные посты пользователей; friends — только друзья и свои.
        if ($isMainFeed && $userId && $mode === 'friends') {
            $allowed = array_unique(array_merge($friendIds, [(int)$userId]));
            $in = implode(',', array_map('intval', $allowed));
            $whereClauses[] = "p.user_id IN ({$in})";
        } elseif ($isMainFeed && $userId && $mode === 'recommendations') {
            $exclude = array_unique(array_merge($friendIds, [(int)$userId]));
            if ($exclude) {
                $in = implode(',', array_map('intval', $exclude));
                $whereClauses[] = "p.user_id NOT IN ({$in})";
            }
            $orderSQL = 'ORDER BY p.created_at DESC';
        } elseif ($isMainFeed && $userId && $mode === 'subscriptions') {
            $whereClauses[] = '1 = 0';
        }

        if ($isMainFeed && $hasPostType) {
            if ($section === 'discussions') {
                $whereClauses[] = "p.post_type = 'discussion'";
            } elseif ($section === 'articles') {
                $whereClauses[] = "p.post_type = 'article'";
            } else {
                $whereClauses[] = "(p.post_type IS NULL OR p.post_type = '' OR p.post_type = 'post')";
            }
        }

        $where = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $isGlobalMixed = ($isMainFeed && $mode === 'mixed' && $section === 'feed');
        $params[] = $limit;
        $params[] = $offset;

        // Таблица репостов (создаётся при первом обращении)
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS reposts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    post_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_post (user_id, post_id),
                    INDEX idx_reposts_user (user_id),
                    INDEX idx_reposts_post (post_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Throwable $e) {}

        $viewerSql = $userId ? (int)$userId : 0;
        $useSmartRec = ($isMainFeed && $userId && $mode === 'recommendations');

        if ($useSmartRec) {
            ensureFeedRankingSchema($pdo);
            $exclude = array_unique(array_merge($friendIds, [(int)$userId]));
            $candidates = frFetchRecommendedCandidates($pdo, (int)$userId, $exclude, $textCol, $imageCol, 200);
            foreach ($candidates as &$cp) {
                $cp['created_at']    = utcDate($cp['created_at']);
                $cp['time_ago']      = timeAgo($cp['created_at']);
                $cp['sort_at']       = $cp['created_at'];
                $cp['user_liked']    = (bool)$cp['user_liked'];
                $cp['user_reposted'] = !empty($cp['user_reposted']);
                $cp['reposts_count'] = (int)($cp['reposts_count'] ?? 0);
                $cp['is_repost']     = false;
                $cp['is_recommended'] = true;
                $cp['can_delete']    = $canModerate;
                $cp['author_name']   = trim($cp['first_name'] . ' ' . $cp['last_name']);
            }
            unset($cp);
            $ranked = frRankPosts($pdo, $candidates, (int)$userId);
            $userPosts = array_slice($ranked, $offset, $limit);
        } else {
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
                {$postTypeSelect},
                {$articleIdSelect},
                'user'         AS author_type,
                NULL           AS entity_id,
                NULL           AS university_id,
                0              AS is_repost,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id)                 AS likes_count,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) AS user_liked,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id)              AS comments_count,
                (SELECT COUNT(*) FROM reposts WHERE post_id = p.id)                 AS reposts_count,
                " . ($viewerSql ? "(SELECT COUNT(*) FROM reposts WHERE post_id = p.id AND user_id = {$viewerSql})" : "0") . " AS user_reposted
            FROM posts p
            JOIN users u ON p.user_id = u.id
            {$where}
            {$orderSQL}
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);
        $userPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($userPosts as &$p) {
            $p['created_at']  = utcDate($p['created_at']);
            $p['time_ago']    = timeAgo($p['created_at']);
            $p['sort_at']     = $p['created_at'];
            $p['user_liked']  = (bool)$p['user_liked'];
            $p['user_reposted'] = !empty($p['user_reposted']);
            $p['reposts_count'] = (int)($p['reposts_count'] ?? 0);
            $p['is_repost']   = false;
            $p['can_delete']  = $canModerate || ((int)$p['user_id'] === (int)($userId ?? 0));
            $p['author_name'] = trim($p['first_name'] . ' ' . $p['last_name']);
        }
        unset($p);
        } // end !useSmartRec

        // ── 1b. Репосты в ленту друзей (стр. 1) ──
        $repostItems = [];
        $mergeReposts = ($isMainFeed && $userId && $page === 1 && $section === 'feed' && ($mode === 'friends' || $mode === 'mixed'));

        if ($mergeReposts) {
            try {
                $allowed = array_unique(array_merge($friendIds, [(int)$userId]));
                $friendIn = implode(',', array_map('intval', $allowed));
                $rpStmt = $pdo->prepare("
                    SELECT
                        r.id AS repost_id,
                        r.created_at AS reposted_at,
                        r.user_id AS repost_user_id,
                        ru.first_name AS repost_first_name,
                        ru.last_name AS repost_last_name,
                        ru.avatar AS repost_avatar,
                        p.id,
                        p.{$textCol} AS text,
                        p.{$imageCol} AS image_url,
                        p.created_at,
                        u.id AS user_id,
                        u.first_name,
                        u.last_name,
                        u.avatar,
                        u.is_verified,
                        {$postTypeSelect},
                        {$articleIdSelect},
                        'user' AS author_type,
                        NULL AS entity_id,
                        NULL AS university_id,
                        1 AS is_repost,
                        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count,
                        " . ($viewerSql ? "(SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = {$viewerSql})" : "0") . " AS user_liked,
                        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
                        (SELECT COUNT(*) FROM reposts WHERE post_id = p.id) AS reposts_count,
                        " . ($viewerSql ? "(SELECT COUNT(*) FROM reposts WHERE post_id = p.id AND user_id = {$viewerSql})" : "0") . " AS user_reposted
                    FROM reposts r
                    JOIN posts p ON p.id = r.post_id
                    JOIN users u ON u.id = p.user_id
                    JOIN users ru ON ru.id = r.user_id
                    WHERE r.user_id IN ({$friendIn})
                    ORDER BY r.created_at DESC
                    LIMIT 30
                ");
                $rpStmt->execute();
                $repostItems = $rpStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($repostItems as &$rp) {
                    $rp['created_at']      = utcDate($rp['created_at']);
                    $rp['reposted_at']     = utcDate($rp['reposted_at']);
                    $rp['sort_at']         = $rp['reposted_at'];
                    $rp['time_ago']        = timeAgo($rp['reposted_at']);
                    $rp['user_liked']      = !empty($rp['user_liked']);
                    $rp['user_reposted']   = !empty($rp['user_reposted']);
                    $rp['reposts_count']   = (int)($rp['reposts_count'] ?? 0);
                    $rp['can_delete']      = $viewerSql && (int)$rp['repost_user_id'] === $viewerSql;
                    $rp['author_name']     = trim(($rp['first_name'] ?? '') . ' ' . ($rp['last_name'] ?? ''));
                    $rp['repost_author_name'] = trim(($rp['repost_first_name'] ?? '') . ' ' . ($rp['repost_last_name'] ?? ''));
                }
                unset($rp);
            } catch (Throwable $e) {
                $repostItems = [];
            }
        }

        // ── 2. Посты вузов/клубов из uni_posts ──────────────────────────
        // Подмешиваем только для главной ленты (не для профиля конкретного
        // пользователя, не для поиска) и только на первой странице.
        // Источники:
        //   a) вуз, к которому привязан студент (users.university_id)
        //   b) вузы, на которые студент подписан (uni_follows)
        $uniPosts = [];
        $mergeUni = ($isMainFeed && $page === 1 && $section === 'feed' && ($isGlobalMixed || ($userId && $mode === 'subscriptions')));

        if ($mergeUni) {
            $hasUniPosts  = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='uni_posts' LIMIT 1")->fetchColumn();
            $hasUniFollow = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='uni_follows' LIMIT 1")->fetchColumn();
            $hasUniProf   = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='university_profiles' LIMIT 1")->fetchColumn();
            $hasUniLikes  = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='uni_likes' LIMIT 1")->fetchColumn();

            if ($hasUniPosts && $hasUniProf) {
                $uniSelect = "
                    SELECT
                        p.id,
                        p.text                                                      AS text,
                        NULL                                                        AS image_url,
                        p.created_at,
                        NULL                                                        AS user_id,
                        NULL                                                        AS first_name,
                        NULL                                                        AS last_name,
                        NULL                                                        AS avatar,
                        0                                                           AS is_verified,
                        p.author_type                                               AS author_type,
                        p.entity_id                                                 AS entity_id,
                        p.university_id                                             AS university_id,
                        CASE
                            WHEN p.author_type = 'club'
                                THEN (SELECT c.name FROM uni_clubs c WHERE c.id = p.entity_id LIMIT 1)
                            ELSE (SELECT up.university_name FROM university_profiles up WHERE up.id = p.university_id LIMIT 1)
                        END                                                         AS author_name,
                        CASE
                            WHEN p.author_type = 'club'
                                THEN (SELECT c.logo FROM uni_clubs c WHERE c.id = p.entity_id LIMIT 1)
                            ELSE (SELECT up.logo FROM university_profiles up WHERE up.id = p.university_id LIMIT 1)
                        END                                                         AS author_logo,
                        " . ($hasUniLikes
                            ? '(SELECT COUNT(*) FROM uni_likes ul WHERE ul.post_id = p.id)'
                            : '0') . "                                              AS likes_count,
                        " . (($hasUniLikes && $viewerSql)
                            ? "(SELECT COUNT(*) FROM uni_likes ul WHERE ul.post_id = p.id AND ul.user_id = {$viewerSql})"
                            : '0') . "                                              AS user_liked,
                        0                                                           AS comments_count
                ";

                if ($isGlobalMixed) {
                    $uniSql = $uniSelect . "
                        FROM uni_posts p
                        ORDER BY p.created_at DESC
                        LIMIT 30
                    ";
                    try {
                        $stUni = $pdo->query($uniSql);
                        $uniPosts = $stUni->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Throwable $e) {
                        $uniPosts = [];
                    }
                } elseif ($userId) {
                    $uniIds = [];
                    $stOwn = $pdo->prepare("SELECT university_id FROM users WHERE id = ? AND university_id IS NOT NULL LIMIT 1");
                    $stOwn->execute([$userId]);
                    $ownUniId = $stOwn->fetchColumn();

                    if ($hasUniFollow) {
                        $stFol = $pdo->prepare("SELECT university_id FROM uni_follows WHERE user_id = ?");
                        $stFol->execute([$userId]);
                        foreach ($stFol->fetchAll(PDO::FETCH_COLUMN) as $fid) {
                            $uniIds[] = (int)$fid;
                        }
                    }

                    $uniIds = array_unique($uniIds);
                    $subIds = array_diff($uniIds, $ownUniId ? [(int)$ownUniId] : []);

                    if ($subIds) {
                        $in = implode(',', array_map('intval', $subIds));
                        $uniSql = $uniSelect . "
                            FROM uni_posts p
                            WHERE p.university_id IN ({$in})
                            ORDER BY p.created_at DESC
                            LIMIT 20
                        ";
                        try {
                            $stUni = $pdo->query($uniSql);
                            $uniPosts = $stUni->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Throwable $e) {
                            $uniPosts = [];
                        }
                    }
                }

                foreach ($uniPosts as &$up) {
                    $up['created_at'] = utcDate($up['created_at']);
                    $up['time_ago']   = timeAgo($up['created_at']);
                    $up['sort_at']    = $up['created_at'];
                    $up['user_liked'] = !empty($up['user_liked']);
                    $up['user_reposted'] = false;
                    $up['reposts_count'] = 0;
                    $up['is_repost']  = false;
                    $up['can_delete'] = false;
                    $up['likes_count'] = (int)($up['likes_count'] ?? 0);
                }
                unset($up);
            }
        }

        // ── 2b. Посты компаний из company_posts ─────────────────────────
        $companyPosts = [];
        $mergeCompany = ($isMainFeed && $page === 1 && $section === 'feed' && ($isGlobalMixed || ($userId && $mode === 'subscriptions')));

        if ($mergeCompany) {
            $hasCoPosts   = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='company_posts' LIMIT 1")->fetchColumn();
            $hasCoFollow  = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='company_follows' LIMIT 1")->fetchColumn();
            $hasCoProf    = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='company_profiles' LIMIT 1")->fetchColumn();
            $hasCoLikes   = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='company_likes' LIMIT 1")->fetchColumn();
            $hasCoComments = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='company_comments' LIMIT 1")->fetchColumn();
            $hasCoReposts  = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='company_reposts' LIMIT 1")->fetchColumn();

            if ($hasCoPosts && $hasCoProf) {
                $likesSub = $hasCoLikes
                    ? '(SELECT COUNT(*) FROM company_likes cl WHERE cl.post_id = p.id)'
                    : '0';
                $commentsSub = $hasCoComments
                    ? '(SELECT COUNT(*) FROM company_comments cc WHERE cc.post_id = p.id)'
                    : '0';
                $repostsSub = $hasCoReposts
                    ? '(SELECT COUNT(*) FROM company_reposts cr WHERE cr.post_id = p.id)'
                    : '0';
                $userLikedSub = ($hasCoLikes && $viewerSql)
                    ? "(SELECT COUNT(*) FROM company_likes cl WHERE cl.post_id = p.id AND cl.user_id = {$viewerSql})"
                    : '0';
                $userRepostedSub = ($hasCoReposts && $viewerSql)
                    ? "(SELECT COUNT(*) FROM company_reposts cr WHERE cr.post_id = p.id AND cr.user_id = {$viewerSql})"
                    : '0';
                $coSelect = "
                    SELECT
                        p.id,
                        p.text                                                      AS text,
                        p.attachment_url                                            AS image_url,
                        p.created_at,
                        c.user_id                                                   AS user_id,
                        NULL                                                        AS first_name,
                        NULL                                                        AS last_name,
                        c.logo                                                      AS avatar,
                        IF(c.verified, 1, 0)                                      AS is_verified,
                        'company'                                                   AS author_type,
                        p.company_id                                                AS entity_id,
                        NULL                                                        AS university_id,
                        c.company_name                                              AS author_name,
                        c.logo                                                      AS author_logo,
                        {$likesSub}                                                 AS likes_count,
                        {$userLikedSub}                                             AS user_liked,
                        {$commentsSub}                                              AS comments_count,
                        {$repostsSub}                                               AS reposts_count,
                        {$userRepostedSub}                                          AS user_reposted
                ";

                if ($isGlobalMixed) {
                    $coSql = $coSelect . "
                        FROM company_posts p
                        JOIN company_profiles c ON c.id = p.company_id
                        ORDER BY p.created_at DESC
                        LIMIT 30
                    ";
                    try {
                        $stCo = $pdo->query($coSql);
                        $companyPosts = $stCo->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Throwable $e) {
                        $companyPosts = [];
                    }
                } elseif ($userId) {
                    $coIds = [];
                    if ($hasCoFollow) {
                        $stFol = $pdo->prepare('SELECT company_id FROM company_follows WHERE user_id = ?');
                        $stFol->execute([$userId]);
                        foreach ($stFol->fetchAll(PDO::FETCH_COLUMN) as $fid) {
                            $coIds[] = (int)$fid;
                        }
                    }
                    try {
                        $viewer = getUser((int)$userId);
                        if (($viewer['role'] ?? '') === 'company') {
                            $stOwn = $pdo->prepare('SELECT id FROM company_profiles WHERE user_id = ? LIMIT 1');
                            $stOwn->execute([(int)$userId]);
                            $ownCo = (int)$stOwn->fetchColumn();
                            if ($ownCo) {
                                $coIds[] = $ownCo;
                            }
                        }
                    } catch (Throwable $e) {
                    }
                    $coIds = array_values(array_unique(array_filter($coIds)));

                    if ($coIds) {
                        $in = implode(',', array_map('intval', $coIds));
                        $coSql = $coSelect . "
                            FROM company_posts p
                            JOIN company_profiles c ON c.id = p.company_id
                            WHERE p.company_id IN ({$in})
                            ORDER BY p.created_at DESC
                            LIMIT 20
                        ";
                        try {
                            $stCo = $pdo->query($coSql);
                            $companyPosts = $stCo->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Throwable $e) {
                            $companyPosts = [];
                        }
                    }
                }

                foreach ($companyPosts as &$cp) {
                    $cp['created_at'] = utcDate($cp['created_at']);
                    $cp['time_ago']   = timeAgo($cp['created_at']);
                    $cp['sort_at']    = $cp['created_at'];
                    $cp['user_liked'] = !empty($cp['user_liked']);
                    $cp['user_reposted'] = !empty($cp['user_reposted']);
                    $cp['reposts_count'] = (int)($cp['reposts_count'] ?? 0);
                    $cp['is_repost']  = false;
                    $cp['can_delete'] = false;
                    $cp['likes_count'] = (int)($cp['likes_count'] ?? 0);
                    $cp['comments_count'] = (int)($cp['comments_count'] ?? 0);
                    $name = trim((string)($cp['author_name'] ?? ''));
                    if ($name === '' || preg_match('/^\?+$/u', $name)) {
                        $cp['author_name'] = 'Компания';
                    }
                }
                unset($cp);
            }
        }

        // ── 3. Объединяем ───────────────────────────────────────────────
        $merged = $userPosts;
        if ($repostItems) {
            $merged = array_merge($merged, $repostItems);
        }
        if ($uniPosts) {
            $merged = array_merge($merged, $uniPosts);
        }
        if ($companyPosts) {
            $merged = array_merge($merged, $companyPosts);
        }
        if (count($merged) > count($userPosts) || $repostItems || $uniPosts || $companyPosts) {
            usort($merged, fn($a, $b) => strcmp($b['sort_at'] ?? $b['created_at'], $a['sort_at'] ?? $a['created_at']));
            if (count($merged) > $limit) {
                $merged = array_slice($merged, 0, $limit);
            }
            $posts = $merged;
        } else {
            $posts = $userPosts;
        }

        // Обратная совместимость: поле content (как было раньше) + text
        foreach ($posts as &$p) {
            if (!isset($p['content'])) $p['content'] = $p['text'] ?? '';
        }
        unset($p);

        postsEnrichArticles($pdo, $posts);

        echo json_encode(['posts' => $posts]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userId  = verifyToken();
        $data    = json_decode(file_get_contents('php://input'), true);
        $content = trim($data['content'] ?? '');
        $image   = $data['image'] ?? null;
        $postType = trim((string)($data['post_type'] ?? 'post'));
        if (!in_array($postType, ['post', 'discussion', 'article'], true)) {
            $postType = 'post';
        }
        $articleId = isset($data['article_id']) ? (int)$data['article_id'] : null;
        if ($articleId !== null && $articleId <= 0) {
            $articleId = null;
        }

        if (empty($content)) {
            http_response_code(400);
            echo json_encode(['error' => 'Текст поста не может быть пустым']);
            exit;
        }

        // Определяем реальные имена колонок
        $cols     = $pdo->query("SHOW COLUMNS FROM posts")->fetchAll(PDO::FETCH_COLUMN);
        $textCol  = in_array('text', $cols)     ? 'text'      : 'content';
        $imageCol = in_array('image_url', $cols) ? 'image_url' : 'image';
        $hasPostType = in_array('post_type', $cols, true);
        if (!$hasPostType) {
            try {
                $pdo->exec("ALTER TABLE posts ADD COLUMN post_type VARCHAR(32) NOT NULL DEFAULT 'post'");
                $hasPostType = true;
            } catch (Throwable $e) {}
        }
        $hasArticleId = in_array('article_id', $cols, true);
        if (!$hasArticleId) {
            try {
                $pdo->exec("ALTER TABLE posts ADD COLUMN article_id INT NULL DEFAULT NULL");
                $hasArticleId = true;
            } catch (Throwable $e) {}
        }

        if ($hasPostType && $hasArticleId) {
            $pdo->prepare("INSERT INTO posts (user_id, {$textCol}, {$imageCol}, post_type, article_id) VALUES (?, ?, ?, ?, ?)")
                ->execute([$userId, $content, $image, $postType, $articleId]);
        } elseif ($hasPostType) {
            $pdo->prepare("INSERT INTO posts (user_id, {$textCol}, {$imageCol}, post_type) VALUES (?, ?, ?, ?)")
                ->execute([$userId, $content, $image, $postType]);
        } else {
            $pdo->prepare("INSERT INTO posts (user_id, {$textCol}, {$imageCol}) VALUES (?, ?, ?)")
                ->execute([$userId, $content, $image]);
        }
        $postId = (int)$pdo->lastInsertId();
        try {
            frInitPostDistribution($pdo, $postId);
        } catch (Throwable $e) {}

        $postTypeSelect = $hasPostType ? 'p.post_type' : "'post' AS post_type";
        $artIdCol = $hasArticleId ? 'p.article_id' : 'NULL AS article_id';
        $stmt = $pdo->prepare("
            SELECT p.id, p.{$textCol} AS content, p.{$imageCol} AS image_url,
                   p.created_at, {$postTypeSelect}, {$artIdCol},
                   u.id AS user_id, u.first_name, u.last_name, u.avatar, u.is_verified
            FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?
        ");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        $post['created_at']      = utcDate($post['created_at']);
        $post['time_ago']       = timeAgo($post['created_at']);
        $post['user_liked']     = false;
        $post['user_reposted']  = false;
        $post['likes_count']    = 0;
        $post['comments_count'] = 0;
        $post['reposts_count']  = 0;
        $post['can_delete']     = true;
        if ($hasArticleId && !empty($post['article_id'])) {
            $post['article_id'] = (int)$post['article_id'];
        }
        $one = [$post];
        postsEnrichArticles($pdo, $one);
        $post = $one[0];

        echo json_encode(['post' => $post]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $userId = verifyToken();
        $canModerate = canModeratePosts($userId);
        $postId = (int)($_GET['id'] ?? 0);
        if (!$postId) { http_response_code(400); echo json_encode(['error' => 'ID поста не указан']); exit; }

        if ($canModerate) {
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
            $stmt->execute([$postId, $userId]);
        }
        if ($stmt->rowCount() === 0) { http_response_code(403); echo json_encode(['error' => 'Нет прав или пост не найден']); exit; }

        echo json_encode(['success' => true]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не разрешён']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка БД: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>