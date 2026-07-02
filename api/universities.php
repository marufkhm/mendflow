<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

function uniJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function uniTableExists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             LIMIT 1"
        );
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function uniColumns(PDO $pdo, string $table): array {
    try {
        $stmt = $pdo->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION"
        );
        $stmt->execute([$table]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return [];
    }
}

function uniTableHasColumns(PDO $pdo, string $table, array $required): bool {
    if (!uniTableExists($pdo, $table)) return false;
    $existing = uniColumns($pdo, $table);
    foreach ($required as $col) {
        if (!in_array($col, $existing, true)) return false;
    }
    return true;
}

function ensureUniTable(PDO $pdo, string $table, string $createSql, array $requiredCols): void {
    if (uniTableHasColumns($pdo, $table, $requiredCols)) return;
    try {
        if ($table === 'uni_clubs') {
            $pdo->exec("DROP TABLE IF EXISTS uni_club_members");
        }
        $pdo->exec("DROP TABLE IF EXISTS {$table}");
        $pdo->exec($createSql);
    } catch (Throwable $e) {}
}

function ensureUniversitySchema(PDO $pdo): void {
    if (!uniTableExists($pdo, 'university_profiles')) return;

    $extraCols = [
        'cover'          => "ALTER TABLE university_profiles ADD COLUMN cover VARCHAR(255) NULL",
        'logo'           => "ALTER TABLE university_profiles ADD COLUMN logo VARCHAR(255) NULL",
        'instagram'      => "ALTER TABLE university_profiles ADD COLUMN instagram VARCHAR(120) NULL",
        'founded_year'   => "ALTER TABLE university_profiles ADD COLUMN founded_year INT NULL",
        'students_count' => "ALTER TABLE university_profiles ADD COLUMN students_count INT NULL",
    ];
    $existing = uniColumns($pdo, 'university_profiles');
    foreach ($extraCols as $col => $sql) {
        if (!in_array($col, $existing, true)) {
            try { $pdo->exec($sql); } catch (Throwable $e) {}
        }
    }

    ensureUniTable($pdo, 'uni_courses', "
        CREATE TABLE uni_courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            university_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            direction VARCHAR(50) NOT NULL DEFAULT 'other',
            duration VARCHAR(50) NULL,
            language VARCHAR(50) NULL,
            credits INT NULL,
            tuition_kzt INT NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_uni_courses_uni (university_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ", ['id', 'university_id', 'title']);

    ensureUniTable($pdo, 'uni_clubs', "
        CREATE TABLE uni_clubs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            university_id INT NOT NULL,
            name VARCHAR(190) NOT NULL,
            category VARCHAR(50) NOT NULL DEFAULT 'other',
            description TEXT NULL,
            logo VARCHAR(255) NULL,
            instagram VARCHAR(120) NULL,
            telegram VARCHAR(120) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_uni_clubs_uni (university_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ", ['id', 'university_id', 'name']);

    ensureUniTable($pdo, 'uni_club_members', "
        CREATE TABLE uni_club_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            club_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('member','admin','owner') NOT NULL DEFAULT 'member',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_club_member (club_id, user_id),
            INDEX idx_club_members_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ", ['id', 'club_id', 'user_id', 'role']);

    // Если таблица существовала раньше без колонки role — добавляем её безопасно
    if (uniTableExists($pdo, 'uni_club_members') && !in_array('role', uniColumns($pdo, 'uni_club_members'), true)) {
        try {
            $pdo->exec("ALTER TABLE uni_club_members ADD COLUMN role ENUM('member','admin','owner') NOT NULL DEFAULT 'member'");
        } catch (Throwable $e) {}
    }

    // Уведомления, связанные с клубами (приглашение, назначение/снятие роли, удаление)
    ensureUniTable($pdo, 'club_notifications', "
        CREATE TABLE club_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            club_id INT NOT NULL,
            from_user_id INT NULL,
            type ENUM('added','promoted','demoted','removed') NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_club_notif_user (user_id, is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ", ['id', 'user_id', 'club_id', 'type']);

    ensureUniTable($pdo, 'uni_exchange', "
        CREATE TABLE uni_exchange (
            id INT AUTO_INCREMENT PRIMARY KEY,
            university_id INT NOT NULL,
            partner_name VARCHAR(190) NOT NULL,
            partner_country VARCHAR(120) NULL,
            partner_flag VARCHAR(10) NULL,
            directions VARCHAR(255) NULL,
            duration VARCHAR(50) NULL,
            language VARCHAR(50) NULL,
            deadline DATE NULL,
            spots INT NULL,
            scholarship TINYINT(1) NOT NULL DEFAULT 0,
            url VARCHAR(255) NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_uni_exchange_uni (university_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ", ['id', 'university_id', 'partner_name']);

    ensureUniTable($pdo, 'uni_posts', "
        CREATE TABLE uni_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            university_id INT NOT NULL,
            user_id INT NULL,
            author_type ENUM('university', 'club') NOT NULL DEFAULT 'university',
            entity_id INT NULL,
            text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_uni_posts_uni (university_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ", ['id', 'university_id', 'text']);

    ensureUniTable($pdo, 'uni_follows', "
        CREATE TABLE uni_follows (
            id INT AUTO_INCREMENT PRIMARY KEY,
            university_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_uni_follow (university_id, user_id),
            INDEX idx_uni_follows_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ", ['id', 'university_id', 'user_id']);

    if (!uniTableExists($pdo, 'uni_likes')) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS uni_likes (
                    id          INT AUTO_INCREMENT PRIMARY KEY,
                    post_id     INT NOT NULL,
                    user_id     INT NOT NULL,
                    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_uni_like (post_id, user_id),
                    INDEX idx_uni_likes_post (post_id),
                    INDEX idx_uni_likes_user (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Throwable $e) {}
    }
}

function fetchUniversity(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("
        SELECT up.*, u.email, u.avatar
        FROM university_profiles up
        JOIN users u ON u.id = up.user_id
        WHERE up.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function universityCounts(PDO $pdo, int $uniId): array {
    $counts = [
        'courses_count'           => 0,
        'clubs_count'             => 0,
        'exchange_count'          => 0,
        'followers'               => 0,
        'students_count_platform' => 0,
    ];
    if (uniTableExists($pdo, 'uni_courses')) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM uni_courses WHERE university_id = ?");
        $s->execute([$uniId]);
        $counts['courses_count'] = (int)$s->fetchColumn();
    }
    if (uniTableExists($pdo, 'uni_clubs')) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM uni_clubs WHERE university_id = ?");
        $s->execute([$uniId]);
        $counts['clubs_count'] = (int)$s->fetchColumn();
    }
    if (uniTableExists($pdo, 'uni_exchange')) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM uni_exchange WHERE university_id = ?");
        $s->execute([$uniId]);
        $counts['exchange_count'] = (int)$s->fetchColumn();
    }
    if (uniTableExists($pdo, 'uni_follows')) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM uni_follows WHERE university_id = ?");
        $s->execute([$uniId]);
        $counts['followers'] = (int)$s->fetchColumn();
    }
    // Реальное число студентов платформы, привязавших этот вуз в профиле
    try {
        $s = $pdo->prepare("SELECT COUNT(*) FROM users WHERE university_id = ?");
        $s->execute([$uniId]);
        $counts['students_count_platform'] = (int)$s->fetchColumn();
    } catch (Throwable $e) {}
    return $counts;
}

function normalizeUniversity(PDO $pdo, array $row, ?int $viewerId): array {
    $uniId = (int)$row['id'];
    $counts = universityCounts($pdo, $uniId);
    $isOwner = $viewerId && (int)$row['user_id'] === $viewerId;
    $isFollowing = false;
    if ($viewerId && uniTableExists($pdo, 'uni_follows')) {
        $s = $pdo->prepare("SELECT 1 FROM uni_follows WHERE university_id = ? AND user_id = ? LIMIT 1");
        $s->execute([$uniId, $viewerId]);
        $isFollowing = (bool)$s->fetchColumn();
    }

    return [
        'id'              => $uniId,
        'user_id'         => (int)$row['user_id'],
        'university_name' => $row['university_name'],
        'city'            => $row['city'],
        'country_name'    => $row['country_name'],
        'website'         => $row['website'],
        'description'     => $row['description'],
        'cover'           => $row['cover'] ?? null,
        'logo'            => $row['logo'] ?? null,
        'instagram'       => $row['instagram'] ?? null,
        'founded_year'    => isset($row['founded_year']) ? (int)$row['founded_year'] : null,
        'students_count'          => isset($row['students_count']) ? (int)$row['students_count'] : null,
        'students_count_platform' => $counts['students_count_platform'],
        'verified'        => !empty($row['verified']),
        'followers'       => $counts['followers'],
        'courses_count'   => $counts['courses_count'],
        'clubs_count'     => $counts['clubs_count'],
        'exchange_count'  => $counts['exchange_count'],
        'is_owner'        => $isOwner,
        'is_following'    => $isFollowing,
    ];
}

function assertUniversityOwner(PDO $pdo, int $viewerId, int $uniId): array {
    $uni = fetchUniversity($pdo, $uniId);
    if (!$uni) uniJson(['error' => 'Университет не найден'], 404);
    if ((int)$uni['user_id'] !== $viewerId) uniJson(['error' => 'Нет доступа'], 403);
    return $uni;
}

/**
 * Возвращает массив клуба, если $viewerId либо владелец вуза-родителя,
 * либо имеет роль admin/owner в самом клубе. Иначе — 403.
 */
function assertClubManager(PDO $pdo, int $viewerId, int $clubId): array {
    $st = $pdo->prepare("SELECT c.*, u.user_id AS uni_owner_id FROM uni_clubs c JOIN universities u ON u.id = c.university_id WHERE c.id = ? LIMIT 1");
    $st->execute([$clubId]);
    $club = $st->fetch(PDO::FETCH_ASSOC);
    if (!$club) uniJson(['error' => 'Клуб не найден'], 404);

    if ((int)$club['uni_owner_id'] === $viewerId) return $club; // владелец вуза управляет всеми клубами

    $stRole = $pdo->prepare("SELECT role FROM uni_club_members WHERE club_id = ? AND user_id = ? LIMIT 1");
    $stRole->execute([$clubId, $viewerId]);
    $role = $stRole->fetchColumn();
    if (!in_array($role, ['admin', 'owner'], true)) uniJson(['error' => 'Нет доступа'], 403);

    return $club;
}

try {
    ensureUniversitySchema($pdo);
    $viewerId = verifyTokenSoft();
    $method   = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'detail';
        $id     = (int)($_GET['id'] ?? 0);

        /* ── action=list — поиск вузов для автокомплита в профиле студента ── */
        if ($action === 'list') {
            $q     = trim($_GET['q'] ?? '');
            $limit = min(max((int)($_GET['limit'] ?? 10), 1), 20);

            // Нужные колонки
            $cols    = uniColumns($pdo, 'university_profiles');
            $hasLogo = in_array('logo', $cols, true);
            $logoSel = $hasLogo ? 'up.logo' : 'NULL AS logo';

            if ($q === '') {
                // Без запроса — возвращаем вузы отсортированные по числу студентов на платформе
                $stmt = $pdo->prepare("
                    SELECT up.id,
                           up.university_name,
                           {$logoSel},
                           up.city,
                           up.country_name,
                           COUNT(u.id) AS students_count_platform
                    FROM university_profiles up
                    LEFT JOIN users u ON u.university_id = up.id
                    GROUP BY up.id
                    ORDER BY students_count_platform DESC, up.university_name ASC
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
            } else {
                // С запросом — LIKE-поиск по названию, городу, стране
                $like = '%' . $q . '%';
                $stmt = $pdo->prepare("
                    SELECT up.id,
                           up.university_name,
                           {$logoSel},
                           up.city,
                           up.country_name,
                           COUNT(u.id) AS students_count_platform
                    FROM university_profiles up
                    LEFT JOIN users u ON u.university_id = up.id
                    WHERE up.university_name LIKE ?
                       OR up.city           LIKE ?
                       OR up.country_name   LIKE ?
                    GROUP BY up.id
                    ORDER BY students_count_platform DESC, up.university_name ASC
                    LIMIT ?
                ");
                $stmt->execute([$like, $like, $like, $limit]);
            }

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $universities = [];
            foreach ($rows as $row) {
                $universities[] = [
                    'id'                      => (int)$row['id'],
                    'university_name'         => $row['university_name'],
                    'logo'                    => $row['logo'],
                    'city'                    => $row['city'],
                    'country_name'            => $row['country_name'],
                    'students_count_platform' => (int)$row['students_count_platform'],
                ];
            }
            uniJson(['universities' => $universities]);
        }

        if ($action === 'my') {
            if (!$viewerId) uniJson(['error' => 'Требуется авторизация'], 401);
            $stmt = $pdo->prepare("SELECT id FROM university_profiles WHERE user_id = ? LIMIT 1");
            $stmt->execute([$viewerId]);
            $myId = (int)$stmt->fetchColumn();
            if (!$myId) uniJson(['error' => 'Профиль университета не найден'], 404);
            $row = fetchUniversity($pdo, $myId);
            uniJson(['university' => normalizeUniversity($pdo, $row, $viewerId)]);
        }

        if (!$id) uniJson(['error' => 'ID университета обязателен'], 400);

        if ($action === 'detail') {
            $row = fetchUniversity($pdo, $id);
            if (!$row) uniJson(['error' => 'Университет не найден'], 404);
            uniJson(['university' => normalizeUniversity($pdo, $row, $viewerId)]);
        }

        if ($action === 'courses') {
            if (!uniTableExists($pdo, 'uni_courses')) uniJson(['courses' => []]);
            $direction = trim($_GET['direction'] ?? '');
            $sql = "SELECT * FROM uni_courses WHERE university_id = ?";
            $params = [$id];
            if ($direction !== '') {
                $sql .= " AND direction = ?";
                $params[] = $direction;
            }
            $sql .= " ORDER BY created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            uniJson(['courses' => $stmt->fetchAll()]);
        }

        if ($action === 'clubs') {
            if (!uniTableExists($pdo, 'uni_clubs')) uniJson(['clubs' => []]);
            $hasUniPosts = uniTableExists($pdo, 'uni_posts');
            $stmt = $pdo->prepare("
                SELECT c.*,
                    (SELECT COUNT(*) FROM uni_club_members m WHERE m.club_id = c.id) AS member_count,
                    " . ($hasUniPosts ? "(SELECT COUNT(*) FROM uni_posts up WHERE up.entity_id = c.id AND up.author_type = 'club')" : "0") . " AS posts_count,
                    " . ($viewerId ? "(SELECT 1 FROM uni_club_members m2 WHERE m2.club_id = c.id AND m2.user_id = {$viewerId} LIMIT 1)" : "0") . " AS is_member,
                    " . ($viewerId ? "(SELECT role FROM uni_club_members m3 WHERE m3.club_id = c.id AND m3.user_id = {$viewerId} LIMIT 1)" : "NULL") . " AS my_role
                FROM uni_clubs c
                WHERE c.university_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$id]);
            $clubs = $stmt->fetchAll();
            foreach ($clubs as &$club) {
                $club['member_count'] = (int)$club['member_count'];
                $club['posts_count']  = (int)$club['posts_count'];
                $club['is_member']    = !empty($club['is_member']);
            }
            uniJson(['clubs' => $clubs]);
        }

        /* ── Участники конкретного клуба (с ролями) ───────────────────── */
        if ($action === 'club_members') {
            $clubId = (int)($_GET['club_id'] ?? 0);
            if (!$clubId) uniJson(['error' => 'club_id required'], 400);
            if (!uniTableExists($pdo, 'uni_club_members')) uniJson(['members' => []]);

            $userCols = uniColumns($pdo, 'users');
            $hasSpecialty = in_array('specialty', $userCols, true);
            $specialtySel = $hasSpecialty ? 'u.specialty' : 'NULL AS specialty';

            $stmt = $pdo->prepare("
                SELECT u.id, u.first_name, u.last_name, u.avatar, {$specialtySel} AS specialty, m.role
                FROM uni_club_members m
                JOIN users u ON u.id = m.user_id
                WHERE m.club_id = ?
                ORDER BY FIELD(m.role,'owner','admin','member'), m.created_at ASC
                LIMIT 200
            ");
            $stmt->execute([$clubId]);
            uniJson(['members' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }

        /* ── Уведомления о действиях в клубах текущего пользователя ──── */
        if ($action === 'club_notifications') {
            if (!$viewerId) uniJson(['notifications' => [], 'count' => 0]);
            if (!uniTableExists($pdo, 'club_notifications')) uniJson(['notifications' => [], 'count' => 0]);

            $since = $_GET['since'] ?? null;
            $sql = "
                SELECT n.id, n.type, n.club_id, n.is_read, n.created_at,
                       c.name AS club_name, c.logo AS club_logo,
                       u.first_name, u.last_name, u.avatar
                FROM club_notifications n
                JOIN uni_clubs c ON c.id = n.club_id
                LEFT JOIN users u ON u.id = n.from_user_id
                WHERE n.user_id = ?
            ";
            $params = [$viewerId];
            if ($since) { $sql .= " AND n.created_at > ?"; $params[] = $since; }
            $sql .= " ORDER BY n.created_at DESC LIMIT 30";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $labels = [
                'added'    => 'добавил(а) вас в клуб',
                'promoted' => 'назначил(а) вас администратором клуба',
                'demoted'  => 'снял(а) с вас роль администратора клуба',
                'removed'  => 'удалил(а) вас из клуба',
            ];
            foreach ($notifs as &$n) {
                $n['is_read'] = (bool)$n['is_read'];
                $n['message'] = trim(($n['first_name'] ?? '') . ' ' . ($n['last_name'] ?? '')) . ' ' . ($labels[$n['type']] ?? '') . ' «' . $n['club_name'] . '»';
            }
            uniJson(['notifications' => $notifs, 'count' => count($notifs)]);
        }

        if ($action === 'exchange') {
            if (!uniTableExists($pdo, 'uni_exchange')) uniJson(['exchange' => []]);
            $stmt = $pdo->prepare("SELECT * FROM uni_exchange WHERE university_id = ? ORDER BY created_at DESC");
            $stmt->execute([$id]);
            uniJson(['exchange' => $stmt->fetchAll()]);
        }

        if ($action === 'posts') {
            $source  = $_GET['source'] ?? 'all';
            $clubId  = (int)($_GET['club_id'] ?? 0);
            $posts   = [];
            $hasUniLikes = uniTableExists($pdo, 'uni_likes');
            $viewerSql   = $viewerId ? (int)$viewerId : 0;

            // ── Посты вуза и клубов (из uni_posts) ──────────────────────
            if (in_array($source, ['all', 'university', 'clubs'], true)
                && uniTableExists($pdo, 'uni_posts'))
            {
                $likesSel = $hasUniLikes
                    ? "(SELECT COUNT(*) FROM uni_likes ul WHERE ul.post_id = p.id)"
                    : "0";
                $userLikedSel = ($hasUniLikes && $viewerSql)
                    ? "(SELECT COUNT(*) FROM uni_likes ul WHERE ul.post_id = p.id AND ul.user_id = {$viewerSql})"
                    : "0";

                $uniSql = "
                    SELECT p.*,
                        CASE
                            WHEN p.author_type = 'club'
                                THEN (SELECT name FROM uni_clubs WHERE id = p.entity_id LIMIT 1)
                            ELSE (SELECT university_name FROM university_profiles WHERE id = p.university_id LIMIT 1)
                        END AS author_name,
                        NULL  AS avatar,
                        p.author_type AS author_type,
                        COALESCE({$likesSel}, 0) AS likes_count,
                        COALESCE({$userLikedSel}, 0) AS user_liked
                    FROM uni_posts p
                    WHERE p.university_id = ?
                ";
                $uniParams = [$id];
                if ($source === 'university') {
                    $uniSql .= " AND p.author_type = 'university'";
                } elseif ($source === 'clubs') {
                    $uniSql .= " AND p.author_type = 'club'";
                    if ($clubId) {
                        $uniSql .= " AND p.entity_id = ?";
                        $uniParams[] = $clubId;
                    }
                }
                $uniSql .= " ORDER BY p.created_at DESC LIMIT 50";
                $stmt = $pdo->prepare($uniSql);
                $stmt->execute($uniParams);
                $uniPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($uniPosts as &$post) {
                    $post['likes_count']    = (int)($post['likes_count'] ?? 0);
                    $post['user_liked']     = !empty($post['user_liked']);
                    $post['comments_count'] = (int)($post['comments_count'] ?? 0);
                    $post['created_at']     = utcDate($post['created_at']);
                }
                unset($post);
                $posts = array_merge($posts, $uniPosts);
            }

            // ── Посты студентов этого вуза (из таблицы posts) ────────────
            if (in_array($source, ['all', 'students'], true)
                && uniTableExists($pdo, 'posts'))
            {
                try {
                    // Определяем название колонки с текстом поста
                    $postCols   = uniColumns($pdo, 'posts');
                    $textCol    = in_array('text', $postCols, true) ? 'text' : 'content';

                    $studSql = "
                        SELECT
                            p.id,
                            p.{$textCol}        AS text,
                            p.created_at,
                            p.user_id,
                            'student'           AS author_type,
                            NULL                AS university_id,
                            NULL                AS entity_id,
                            CONCAT(u.first_name, ' ', u.last_name) AS author_name,
                            u.avatar            AS avatar,
                            COALESCE(
                                (SELECT COUNT(*) FROM likes   WHERE post_id = p.id), 0
                            )                   AS likes_count,
                            COALESCE(
                                (SELECT COUNT(*) FROM comments WHERE post_id = p.id), 0
                            )                   AS comments_count
                        FROM posts p
                        JOIN users u ON u.id = p.user_id
                        WHERE u.university_id = ?
                        ORDER BY p.created_at DESC
                        LIMIT 50
                    ";
                    $stmt = $pdo->prepare($studSql);
                    $stmt->execute([$id]);
                    $studPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($studPosts as &$post) {
                        $post['created_at'] = utcDate($post['created_at']);
                    }
                    $posts = array_merge($posts, $studPosts);
                } catch (Throwable $e) {
                    // Таблица posts или колонки недоступны — пропускаем тихо
                }
            }

            // ── Сортируем объединённый массив по дате (новые первыми) ────
            usort($posts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

            // Обрезаем до 50 после объединения
            if (count($posts) > 50) {
                $posts = array_slice($posts, 0, 50);
            }

            uniJson(['posts' => $posts]);
        }

        if ($action === 'events') {
            $events = [];
            $uni = fetchUniversity($pdo, $id);
            if ($uni && uniTableExists($pdo, 'events')) {
                $stmt = $pdo->prepare("
                    SELECT e.*
                    FROM events e
                    WHERE e.creator_id = ?
                    ORDER BY e.start_datetime ASC
                    LIMIT 50
                ");
                $stmt->execute([(int)$uni['user_id']]);
                $events = $stmt->fetchAll();
                foreach ($events as &$ev) {
                    $ev['organizer_type'] = ($ev['creator_type'] ?? '') === 'university' ? 'university' : 'club';
                }
            }
            uniJson(['events' => $events]);
        }

        uniJson(['error' => 'Неизвестное действие'], 400);
    }

    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $data['action'] ?? '';

        if ($action === 'update_profile') {
            $uniId = (int)($data['university_id'] ?? 0);
            assertUniversityOwner($pdo, $userId, $uniId);

            $name = trim($data['university_name'] ?? '');
            if (!$name) uniJson(['error' => 'Название обязательно'], 400);

            $website = trim($data['website'] ?? '');
            if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
                uniJson(['error' => 'Неверный формат сайта'], 400);
            }

            $pdo->prepare("
                UPDATE university_profiles SET
                    university_name = ?,
                    city = ?,
                    country_name = ?,
                    website = ?,
                    description = ?,
                    instagram = ?,
                    founded_year = ?,
                    students_count = ?,
                    cover = COALESCE(?, cover),
                    logo = COALESCE(?, logo)
                WHERE id = ?
            ")->execute([
                $name,
                trim($data['city'] ?? '') ?: null,
                trim($data['country_name'] ?? '') ?: null,
                $website ?: null,
                trim($data['description'] ?? '') ?: null,
                trim($data['instagram'] ?? '') ?: null,
                ($data['founded_year'] ?? '') !== '' ? (int)$data['founded_year'] : null,
                ($data['students_count'] ?? '') !== '' ? (int)$data['students_count'] : null,
                $data['cover'] ?? null,
                $data['logo'] ?? null,
                $uniId,
            ]);

            $pdo->prepare("UPDATE users SET first_name = ? WHERE id = ?")->execute([$name, $userId]);

            $row = fetchUniversity($pdo, $uniId);
            uniJson(['success' => true, 'university' => normalizeUniversity($pdo, $row, $userId)]);
        }

        if ($action === 'add_course') {
            $uniId = (int)($data['university_id'] ?? 0);
            assertUniversityOwner($pdo, $userId, $uniId);
            $title = trim($data['title'] ?? '');
            if (!$title) uniJson(['error' => 'Название курса обязательно'], 400);
            $pdo->prepare("
                INSERT INTO uni_courses (university_id, title, direction, duration, language, credits, tuition_kzt, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $uniId, $title,
                $data['direction'] ?? 'other',
                trim($data['duration'] ?? '') ?: null,
                trim($data['language'] ?? '') ?: null,
                ($data['credits'] ?? '') !== '' ? (int)$data['credits'] : null,
                ($data['tuition_kzt'] ?? '') !== '' ? (int)$data['tuition_kzt'] : null,
                trim($data['description'] ?? '') ?: null,
            ]);
            uniJson(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
        }

        if ($action === 'delete_course') {
            $courseId = (int)($data['course_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT university_id FROM uni_courses WHERE id = ?");
            $stmt->execute([$courseId]);
            $uniId = (int)$stmt->fetchColumn();
            if (!$uniId) uniJson(['error' => 'Курс не найден'], 404);
            assertUniversityOwner($pdo, $userId, $uniId);
            $pdo->prepare("DELETE FROM uni_courses WHERE id = ?")->execute([$courseId]);
            uniJson(['success' => true]);
        }

        if ($action === 'add_club') {
            $uniId = (int)($data['university_id'] ?? 0);
            assertUniversityOwner($pdo, $userId, $uniId);
            $name = trim($data['name'] ?? '');
            if (!$name) uniJson(['error' => 'Название клуба обязательно'], 400);
            $pdo->prepare("
                INSERT INTO uni_clubs (university_id, name, category, description, instagram, telegram)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([
                $uniId, $name,
                $data['category'] ?? 'other',
                trim($data['description'] ?? '') ?: null,
                trim($data['instagram'] ?? '') ?: null,
                trim($data['telegram'] ?? '') ?: null,
            ]);
            uniJson(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
        }

        if ($action === 'delete_club') {
            $clubId = (int)($data['club_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT university_id FROM uni_clubs WHERE id = ?");
            $stmt->execute([$clubId]);
            $uniId = (int)$stmt->fetchColumn();
            if (!$uniId) uniJson(['error' => 'Клуб не найден'], 404);
            assertUniversityOwner($pdo, $userId, $uniId);
            $pdo->prepare("DELETE FROM uni_clubs WHERE id = ?")->execute([$clubId]);
            uniJson(['success' => true]);
        }

        if ($action === 'add_exchange') {
            $uniId = (int)($data['university_id'] ?? 0);
            assertUniversityOwner($pdo, $userId, $uniId);
            $partner = trim($data['partner_name'] ?? '');
            if (!$partner) uniJson(['error' => 'Партнёр обязателен'], 400);
            $pdo->prepare("
                INSERT INTO uni_exchange (university_id, partner_name, partner_country, partner_flag, directions, duration, language, deadline, spots, scholarship, url, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $uniId, $partner,
                trim($data['partner_country'] ?? '') ?: null,
                trim($data['partner_flag'] ?? '') ?: null,
                trim($data['directions'] ?? '') ?: null,
                trim($data['duration'] ?? '') ?: null,
                trim($data['language'] ?? '') ?: null,
                ($data['deadline'] ?? '') ?: null,
                ($data['spots'] ?? '') !== '' ? (int)$data['spots'] : null,
                !empty($data['scholarship']) ? 1 : 0,
                trim($data['url'] ?? '') ?: null,
                trim($data['description'] ?? '') ?: null,
            ]);
            uniJson(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
        }

        if ($action === 'delete_exchange') {
            $exId = (int)($data['exchange_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT university_id FROM uni_exchange WHERE id = ?");
            $stmt->execute([$exId]);
            $uniId = (int)$stmt->fetchColumn();
            if (!$uniId) uniJson(['error' => 'Программа не найдена'], 404);
            assertUniversityOwner($pdo, $userId, $uniId);
            $pdo->prepare("DELETE FROM uni_exchange WHERE id = ?")->execute([$exId]);
            uniJson(['success' => true]);
        }

        if ($action === 'club_join') {
            $clubId = (int)($data['club_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT id FROM uni_clubs WHERE id = ?");
            $stmt->execute([$clubId]);
            if (!$stmt->fetchColumn()) uniJson(['error' => 'Клуб не найден'], 404);

            $check = $pdo->prepare("SELECT id FROM uni_club_members WHERE club_id = ? AND user_id = ?");
            $check->execute([$clubId, $userId]);
            if ($check->fetchColumn()) {
                $pdo->prepare("DELETE FROM uni_club_members WHERE club_id = ? AND user_id = ?")->execute([$clubId, $userId]);
                uniJson(['success' => true, 'member' => false]);
            }
            $pdo->prepare("INSERT INTO uni_club_members (club_id, user_id, role) VALUES (?, ?, 'member')")->execute([$clubId, $userId]);
            uniJson(['success' => true, 'member' => true]);
        }

        /* ── Добавить участника в клуб (владелец вуза или админ клуба) ── */
        if ($action === 'club_add_member') {
            $clubId    = (int)($data['club_id'] ?? 0);
            $targetId  = (int)($data['user_id'] ?? 0);
            $role      = in_array($data['role'] ?? 'member', ['member', 'admin'], true) ? $data['role'] : 'member';
            if (!$clubId || !$targetId) uniJson(['error' => 'club_id и user_id обязательны'], 400);

            assertClubManager($pdo, $userId, $clubId);

            $stTarget = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stTarget->execute([$targetId]);
            if (!$stTarget->fetchColumn()) uniJson(['error' => 'Пользователь не найден'], 404);

            $check = $pdo->prepare("SELECT id FROM uni_club_members WHERE club_id = ? AND user_id = ?");
            $check->execute([$clubId, $targetId]);
            if ($check->fetchColumn()) {
                // Уже в клубе — просто обновляем роль, если отличается
                $pdo->prepare("UPDATE uni_club_members SET role = ? WHERE club_id = ? AND user_id = ?")
                    ->execute([$role, $clubId, $targetId]);
            } else {
                $pdo->prepare("INSERT INTO uni_club_members (club_id, user_id, role) VALUES (?, ?, ?)")
                    ->execute([$clubId, $targetId, $role]);
            }

            // Уведомление добавленному пользователю
            try {
                $pdo->prepare("
                    INSERT INTO club_notifications (user_id, club_id, from_user_id, type)
                    VALUES (?, ?, ?, 'added')
                ")->execute([$targetId, $clubId, $userId]);
            } catch (Throwable $e) {}

            uniJson(['success' => true]);
        }

        /* ── Изменить роль участника / удалить из клуба ───────────────── */
        if ($action === 'club_member_role') {
            $clubId     = (int)($data['club_id'] ?? 0);
            $targetId   = (int)($data['user_id'] ?? 0);
            $roleAction = $data['role_action'] ?? '';
            if (!$clubId || !$targetId) uniJson(['error' => 'club_id и user_id обязательны'], 400);
            if (!in_array($roleAction, ['promote', 'demote', 'remove'], true)) uniJson(['error' => 'Некорректное действие'], 400);

            assertClubManager($pdo, $userId, $clubId);

            $stCur = $pdo->prepare("SELECT role FROM uni_club_members WHERE club_id = ? AND user_id = ?");
            $stCur->execute([$clubId, $targetId]);
            $curRole = $stCur->fetchColumn();
            if ($curRole === false) uniJson(['error' => 'Пользователь не состоит в клубе'], 404);
            if ($curRole === 'owner') uniJson(['error' => 'Нельзя изменить создателя клуба'], 403);

            if ($roleAction === 'promote') {
                $pdo->prepare("UPDATE uni_club_members SET role = 'admin' WHERE club_id = ? AND user_id = ?")->execute([$clubId, $targetId]);
                $notifType = 'promoted';
            } elseif ($roleAction === 'demote') {
                $pdo->prepare("UPDATE uni_club_members SET role = 'member' WHERE club_id = ? AND user_id = ?")->execute([$clubId, $targetId]);
                $notifType = 'demoted';
            } else { // remove
                $pdo->prepare("DELETE FROM uni_club_members WHERE club_id = ? AND user_id = ?")->execute([$clubId, $targetId]);
                $notifType = 'removed';
            }

            try {
                $pdo->prepare("
                    INSERT INTO club_notifications (user_id, club_id, from_user_id, type)
                    VALUES (?, ?, ?, ?)
                ")->execute([$targetId, $clubId, $userId, $notifType]);
            } catch (Throwable $e) {}

            uniJson(['success' => true]);
        }

        if ($action === 'post_create') {
            $uniId = (int)($data['university_id'] ?? $data['entity_id'] ?? 0);
            assertUniversityOwner($pdo, $userId, $uniId);
            $text = trim($data['text'] ?? '');
            if (!$text) uniJson(['error' => 'Текст поста обязателен'], 400);
            if (!uniTableHasColumns($pdo, 'uni_posts', ['university_id', 'text'])) {
                uniJson(['error' => 'Таблица uni_posts не настроена. Запустите uni_posts_migration.sql в phpMyAdmin.'], 500);
            }
            $authorType = ($data['author_type'] ?? 'university') === 'club' ? 'club' : 'university';
            $entityId = $authorType === 'club' ? (int)($data['entity_id'] ?? 0) : $uniId;
            $pdo->prepare("
                INSERT INTO uni_posts (university_id, user_id, author_type, entity_id, text)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$uniId, $userId, $authorType, $entityId ?: $uniId, $text]);
            uniJson(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
        }

        if ($action === 'follow') {
            $uniId = (int)($data['id'] ?? 0);
            if (!$uniId || !fetchUniversity($pdo, $uniId)) uniJson(['error' => 'Университет не найден'], 404);

            $check = $pdo->prepare("SELECT id FROM uni_follows WHERE university_id = ? AND user_id = ?");
            $check->execute([$uniId, $userId]);
            if ($check->fetchColumn()) {
                $pdo->prepare("DELETE FROM uni_follows WHERE university_id = ? AND user_id = ?")->execute([$uniId, $userId]);
                $following = false;
            } else {
                $pdo->prepare("INSERT INTO uni_follows (university_id, user_id) VALUES (?, ?)")->execute([$uniId, $userId]);
                $following = true;
            }
            $s = $pdo->prepare("SELECT COUNT(*) FROM uni_follows WHERE university_id = ?");
            $s->execute([$uniId]);
            uniJson(['success' => true, 'following' => $following, 'followers' => (int)$s->fetchColumn()]);
        }

        uniJson(['error' => 'Неизвестное действие'], 400);
    }

    uniJson(['error' => 'Метод не разрешён'], 405);

} catch (PDOException $e) {
    uniJson(['error' => 'Ошибка БД: ' . $e->getMessage()], 500);
}