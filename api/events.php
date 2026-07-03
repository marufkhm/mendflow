<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

function eventsJson($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function eventsTableExists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             LIMIT 1"
        );
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) { return false; }
}

function eventsColumns(PDO $pdo, string $table): array {
    try {
        $stmt = $pdo->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION"
        );
        $stmt->execute([$table]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) { return []; }
}

function ensureEventsSchema(PDO $pdo): void {
    $userCols = eventsColumns($pdo, 'users');
    if (!in_array('city', $userCols, true)) {
        try {
            if (in_array('last_seen', $userCols, true)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN city VARCHAR(120) NULL AFTER last_seen");
            } else {
                $pdo->exec("ALTER TABLE users ADD COLUMN city VARCHAR(120) NULL");
            }
        } catch (Throwable $e) {}
    }

    if (!eventsTableExists($pdo, 'user_interests')) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS user_interests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    interest_name VARCHAR(80) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_user_interest (user_id, interest_name),
                    INDEX idx_user_interests_user (user_id),
                    INDEX idx_user_interests_name (interest_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Throwable $e) {}
    }

    if (!eventsTableExists($pdo, 'events')) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(180) NOT NULL,
                    description TEXT NOT NULL,
                    cover_image VARCHAR(255) DEFAULT NULL,
                    creator_id INT NOT NULL,
                    creator_type ENUM('user', 'university', 'company') NOT NULL DEFAULT 'user',
                    event_format ENUM('online', 'offline') NOT NULL DEFAULT 'offline',
                    city VARCHAR(120) DEFAULT NULL,
                    location VARCHAR(255) DEFAULT NULL,
                    meeting_link VARCHAR(255) DEFAULT NULL,
                    category VARCHAR(80) NOT NULL DEFAULT 'Другое',
                    max_participants INT DEFAULT NULL,
                    start_datetime DATETIME NOT NULL,
                    end_datetime DATETIME DEFAULT NULL,
                    visibility ENUM('public', 'private') NOT NULL DEFAULT 'public',
                    status ENUM('active', 'cancelled', 'finished') NOT NULL DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_events_start (start_datetime),
                    INDEX idx_events_city (city),
                    INDEX idx_events_category (category),
                    INDEX idx_events_format (event_format),
                    INDEX idx_events_creator (creator_id, creator_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Throwable $e) {}
    }

    if (!eventsTableExists($pdo, 'event_participants')) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS event_participants (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_id INT NOT NULL,
                    user_id INT NOT NULL,
                    status ENUM('going', 'interested') NOT NULL DEFAULT 'going',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_event_user (event_id, user_id),
                    INDEX idx_event_participants_event (event_id),
                    INDEX idx_event_participants_user (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Throwable $e) {}
    }

    if (!eventsTableExists($pdo, 'event_tags')) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS event_tags (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_id INT NOT NULL,
                    tag_name VARCHAR(80) NOT NULL,
                    INDEX idx_event_tags_event (event_id),
                    INDEX idx_event_tags_name (tag_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Throwable $e) {}
    }

    if (!eventsTableExists($pdo, 'event_reviews')) {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS event_reviews (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_id INT NOT NULL,
                    user_id INT NOT NULL,
                    rating TINYINT NOT NULL,
                    comment TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_event_review_user (event_id, user_id),
                    INDEX idx_event_reviews_event (event_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Throwable $e) {}
    }
}

function eventCreatorType(array $user): string {
    $role = $user['role'] ?? 'student';
    if ($role === 'university') return 'university';
    if ($role === 'company')    return 'company';
    return 'user';
}

function eventCreatorName(PDO $pdo, int $userId, string $creatorType, string $fallback): string {
    try {
        if ($creatorType === 'company' && eventsTableExists($pdo, 'company_profiles')) {
            $stmt = $pdo->prepare("SELECT company_name FROM company_profiles WHERE user_id=? LIMIT 1");
            $stmt->execute([$userId]);
            $name = $stmt->fetchColumn();
            if ($name) return $name;
        }
        if ($creatorType === 'university' && eventsTableExists($pdo, 'university_profiles')) {
            $stmt = $pdo->prepare("SELECT university_name FROM university_profiles WHERE user_id=? LIMIT 1");
            $stmt->execute([$userId]);
            $name = $stmt->fetchColumn();
            if ($name) return $name;
        }
    } catch (Throwable $e) {}
    return $fallback;
}

function currentUserInterests(PDO $pdo, int $userId): array {
    if (!$userId || !eventsTableExists($pdo, 'user_interests')) return [];
    $stmt = $pdo->prepare("SELECT interest_name FROM user_interests WHERE user_id=?");
    $stmt->execute([$userId]);
    return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function currentFriendIds(PDO $pdo, int $userId): array {
    if (!$userId || !eventsTableExists($pdo, 'friendships')) return [];
    $cols = eventsColumns($pdo, 'friendships');
    try {
        if (in_array('sender_id', $cols, true) && in_array('receiver_id', $cols, true)) {
            $statusSql = in_array('status', $cols, true) ? "AND status='accepted'" : "";
            $stmt = $pdo->prepare("
                SELECT CASE WHEN sender_id=? THEN receiver_id ELSE sender_id END AS friend_id
                FROM friendships WHERE (sender_id=? OR receiver_id=?) {$statusSql}
            ");
            $stmt->execute([$userId, $userId, $userId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }
        if (in_array('user1_id', $cols, true) && in_array('user2_id', $cols, true)) {
            $statusSql = in_array('confirmed', $cols, true) ? "AND confirmed=1" : "";
            $stmt = $pdo->prepare("
                SELECT CASE WHEN user1_id=? THEN user2_id ELSE user1_id END AS friend_id
                FROM friendships WHERE (user1_id=? OR user2_id=?) {$statusSql}
            ");
            $stmt->execute([$userId, $userId, $userId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }
    } catch (Throwable $e) {}
    return [];
}

function normalizeEvent(array $event): array {
    $event['id']                  = (int)$event['id'];
    $event['creator_id']          = (int)$event['creator_id'];
    $event['max_participants']    = $event['max_participants'] !== null ? (int)$event['max_participants'] : null;
    $event['participants_count']  = (int)($event['participants_count'] ?? 0);
    $event['interested_count']    = (int)($event['interested_count']   ?? 0);
    $event['friend_score']        = (int)($event['friend_score']       ?? 0);
    $event['avg_rating']          = $event['avg_rating'] ? round((float)$event['avg_rating'], 1) : null;
    $event['review_count']        = (int)($event['review_count']       ?? 0);
    $event['tags'] = !empty($event['tags_csv'])
        ? array_values(array_filter(array_map('trim', explode(',', $event['tags_csv']))))
        : [];
    unset($event['tags_csv']);
    $event['start_iso'] = $event['start_datetime'] ?? null;
    $event['end_iso']   = $event['end_datetime']   ?? null;
    // Is event full?
    $event['is_full'] = $event['max_participants']
        && $event['participants_count'] >= $event['max_participants'];
    return $event;
}

function loadEventList(PDO $pdo, int $userId, array $filters): array {
    $where  = ["e.visibility='public'", "e.status='active'", "e.start_datetime >= DATE_SUB(NOW(), INTERVAL 1 DAY)"];
    $params = [];

    if (!empty($filters['format']) && in_array($filters['format'], ['online','offline'], true)) {
        $where[] = 'e.event_format = ?';
        $params[] = $filters['format'];
    }
    if (!empty($filters['category'])) {
        $where[] = 'e.category = ?';
        $params[] = $filters['category'];
    }
    if (!empty($filters['city'])) {
        $where[] = 'e.city LIKE ?';
        $params[] = '%' . $filters['city'] . '%';
    }
    if (!empty($filters['q'])) {
        $where[] = '(e.title LIKE ? OR e.description LIKE ? OR e.city LIKE ? OR e.category LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        array_push($params, $like, $like, $like, $like);
    }

    $friendIds  = currentFriendIds($pdo, $userId);
    $friendSql  = '0';
    if ($friendIds) {
        $safeIds   = implode(',', array_map('intval', $friendIds));
        $friendSql = "(
            CASE WHEN e.creator_id IN ({$safeIds}) THEN 1 ELSE 0 END +
            (SELECT COUNT(*) FROM event_participants epf WHERE epf.event_id=e.id AND epf.user_id IN ({$safeIds}))
        )";
    }

    $myStatusSql = $userId
        ? "(SELECT ep3.status FROM event_participants ep3 WHERE ep3.event_id=e.id AND ep3.user_id={$userId} LIMIT 1)"
        : "NULL";

    $limit    = max(1, min(60, (int)($filters['limit'] ?? 30)));
    $whereSql = implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT
            e.*,
            u.first_name, u.last_name, u.avatar AS creator_avatar,
            GROUP_CONCAT(DISTINCT et.tag_name ORDER BY et.tag_name SEPARATOR ',') AS tags_csv,
            (SELECT COUNT(*) FROM event_participants ep  WHERE ep.event_id=e.id  AND ep.status='going')      AS participants_count,
            (SELECT COUNT(*) FROM event_participants ep2 WHERE ep2.event_id=e.id AND ep2.status='interested') AS interested_count,
            {$myStatusSql} AS my_status,
            {$friendSql}   AS friend_score,
            (SELECT ROUND(AVG(er.rating),1) FROM event_reviews er WHERE er.event_id=e.id) AS avg_rating,
            (SELECT COUNT(*) FROM event_reviews er2 WHERE er2.event_id=e.id)              AS review_count
        FROM events e
        JOIN users u ON u.id=e.creator_id
        LEFT JOIN event_tags et ON et.event_id=e.id
        WHERE {$whereSql}
        GROUP BY e.id
        ORDER BY e.start_datetime ASC
        LIMIT {$limit}
    ");
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $userCity  = '';
    $interests = currentUserInterests($pdo, $userId);
    if ($userId && in_array('city', eventsColumns($pdo, 'users'), true)) {
        $u = $pdo->prepare("SELECT city FROM users WHERE id=?");
        $u->execute([$userId]);
        $userCity = trim((string)$u->fetchColumn());
    }
    $interestLookup = array_flip(array_map('mb_strtolower', $interests));

    foreach ($events as &$event) {
        $fallback              = trim(($event['first_name'] ?? '') . ' ' . ($event['last_name'] ?? '')) ?: 'Mendflow';
        $event['creator_name'] = eventCreatorName($pdo, (int)$event['creator_id'], $event['creator_type'], $fallback);
        $event                 = normalizeEvent($event);

        $score = 0;
        if ($userCity && mb_strtolower((string)$event['city']) === mb_strtolower($userCity)) $score += 300;
        $haystack = array_map('mb_strtolower', array_merge([$event['category']], $event['tags']));
        foreach ($haystack as $tag) {
            if (isset($interestLookup[$tag])) { $score += 120; break; }
        }
        $score += min(90, $event['friend_score'] * 30);
        $event['recommendation_score'] = $score;
    }
    unset($event);

    if (!empty($filters['recommended'])) {
        usort($events, function ($a, $b) {
            if ($a['recommendation_score'] === $b['recommendation_score'])
                return strcmp($a['start_datetime'], $b['start_datetime']);
            return $b['recommendation_score'] <=> $a['recommendation_score'];
        });
    }
    return $events;
}

/* ── ROUTER ──────────────────────────────────────────────────── */
try {
    ensureEventsSchema($pdo);
    if (!eventsTableExists($pdo, 'events')) {
        eventsJson(['error' => 'Модуль мероприятий не установлен. Запусти events_migration.sql в phpMyAdmin'], 500);
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    /* ════ GET ═══════════════════════════════════════════════════ */
    if ($method === 'GET') {
        $userId = verifyTokenSoft();

        // Preferences
        if ($action === 'preferences') {
            $userId = verifyToken();
            $city   = '';
            if (in_array('city', eventsColumns($pdo, 'users'), true)) {
                $stmt = $pdo->prepare("SELECT city FROM users WHERE id=?");
                $stmt->execute([$userId]);
                $city = (string)$stmt->fetchColumn();
            }
            eventsJson(['city' => $city, 'interests' => currentUserInterests($pdo, $userId)]);
        }

        // Detail
        if ($action === 'detail') {
            $eventId = (int)($_GET['id'] ?? 0);
            if (!$eventId) eventsJson(['error' => 'ID мероприятия не указан'], 400);

            $myStatusSql = $userId
                ? "(SELECT ep3.status FROM event_participants ep3 WHERE ep3.event_id=e.id AND ep3.user_id={$userId} LIMIT 1)"
                : "NULL";

            $stmt = $pdo->prepare("
                SELECT e.*, u.first_name, u.last_name, u.avatar AS creator_avatar,
                    GROUP_CONCAT(DISTINCT et.tag_name ORDER BY et.tag_name SEPARATOR ',') AS tags_csv,
                    (SELECT COUNT(*) FROM event_participants ep  WHERE ep.event_id=e.id  AND ep.status='going')      AS participants_count,
                    (SELECT COUNT(*) FROM event_participants ep2 WHERE ep2.event_id=e.id AND ep2.status='interested') AS interested_count,
                    {$myStatusSql} AS my_status,
                    0 AS friend_score,
                    (SELECT ROUND(AVG(er.rating),1) FROM event_reviews er WHERE er.event_id=e.id) AS avg_rating,
                    (SELECT COUNT(*) FROM event_reviews er2 WHERE er2.event_id=e.id)              AS review_count
                FROM events e
                JOIN users u ON u.id=e.creator_id
                LEFT JOIN event_tags et ON et.event_id=e.id
                WHERE e.id=?
                GROUP BY e.id
                LIMIT 1
            ");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$event) eventsJson(['error' => 'Мероприятие не найдено'], 404);
            if ($event['visibility'] !== 'public' && (int)$event['creator_id'] !== (int)$userId)
                eventsJson(['error' => 'Нет доступа'], 403);

            $fallback              = trim(($event['first_name'] ?? '') . ' ' . ($event['last_name'] ?? '')) ?: 'Mendflow';
            $event['creator_name'] = eventCreatorName($pdo, (int)$event['creator_id'], $event['creator_type'], $fallback);
            $event                 = normalizeEvent($event);

            // Participants split by status
            $p = $pdo->prepare("
                SELECT ep.status, ep.created_at, u.id, u.first_name, u.last_name, u.avatar
                FROM event_participants ep
                JOIN users u ON u.id=ep.user_id
                WHERE ep.event_id=?
                ORDER BY ep.status='going' DESC, ep.created_at ASC
                LIMIT 100
            ");
            $p->execute([$eventId]);
            $allParts = $p->fetchAll(PDO::FETCH_ASSOC);

            // Reviews
            $r = $pdo->prepare("
                SELECT er.rating, er.comment, er.created_at,
                       u.first_name, u.last_name, u.avatar
                FROM event_reviews er
                JOIN users u ON u.id=er.user_id
                WHERE er.event_id=?
                ORDER BY er.created_at DESC
                LIMIT 20
            ");
            $r->execute([$eventId]);
            $reviews = $r->fetchAll(PDO::FETCH_ASSOC);

            eventsJson([
                'event'        => $event,
                'participants' => $allParts,
                'reviews'      => $reviews,
            ]);
        }

        // List
        $events = loadEventList($pdo, $userId ?: 0, [
            'q'           => trim($_GET['q']        ?? ''),
            'city'        => trim($_GET['city']      ?? ''),
            'category'    => trim($_GET['category']  ?? ''),
            'format'      => trim($_GET['format']    ?? ''),
            'recommended' => !empty($_GET['recommended']),
            'limit'       => (int)($_GET['limit']    ?? 30),
        ]);
        eventsJson(['events' => $events]);
    }

    /* ════ POST ══════════════════════════════════════════════════ */
    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? $action;

        // Save preferences
        if ($action === 'preferences') {
            $city      = trim((string)($data['city'] ?? ''));
            $interests = array_values(array_unique(array_filter(array_map('trim', (array)($data['interests'] ?? [])))));
            if (mb_strlen($city) > 120) eventsJson(['error' => 'Город слишком длинный'], 400);
            if (in_array('city', eventsColumns($pdo, 'users'), true)) {
                $pdo->prepare("UPDATE users SET city=? WHERE id=?")->execute([$city ?: null, $userId]);
            }
            $pdo->prepare("DELETE FROM user_interests WHERE user_id=?")->execute([$userId]);
            $ins = $pdo->prepare("INSERT INTO user_interests (user_id, interest_name) VALUES (?, ?)");
            foreach (array_slice($interests, 0, 10) as $interest) {
                if (mb_strlen($interest) <= 80) $ins->execute([$userId, $interest]);
            }
            eventsJson(['success' => true, 'city' => $city, 'interests' => $interests]);
        }

        // ── PARTICIPATE (FIXED) ───────────────────────────────────
        if ($action === 'participate') {
            $eventId   = (int)($data['event_id'] ?? 0);
            $newStatus = $data['status'] ?? '';

            if (!$eventId) eventsJson(['error' => 'ID мероприятия не указан'], 400);

            // Validate status: going, interested, or cancel
            if (!in_array($newStatus, ['going', 'interested', 'cancel'], true))
                eventsJson(['error' => 'Некорректный статус'], 400);

            // Check event exists
            $stmt = $pdo->prepare("SELECT id, max_participants, status FROM events WHERE id=? AND status='active' LIMIT 1");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$event) eventsJson(['error' => 'Мероприятие не найдено'], 404);

            // Get current user status
            $curStmt = $pdo->prepare("SELECT status FROM event_participants WHERE event_id=? AND user_id=? LIMIT 1");
            $curStmt->execute([$eventId, $userId]);
            $currentStatus = $curStmt->fetchColumn() ?: null;

            // TOGGLE: if clicking same status — cancel
            if ($newStatus !== 'cancel' && $currentStatus === $newStatus) {
                $newStatus = 'cancel';
            }

            if ($newStatus === 'cancel') {
                $pdo->prepare("DELETE FROM event_participants WHERE event_id=? AND user_id=?")
                    ->execute([$eventId, $userId]);
                // Return fresh counts
                $going = (int)$pdo->prepare("SELECT COUNT(*) FROM event_participants WHERE event_id=? AND status='going'")->execute([$eventId]) ? 0 : 0;
                $cntG = $pdo->prepare("SELECT COUNT(*) FROM event_participants WHERE event_id=? AND status='going'");
                $cntG->execute([$eventId]);
                $cntI = $pdo->prepare("SELECT COUNT(*) FROM event_participants WHERE event_id=? AND status='interested'");
                $cntI->execute([$eventId]);
                eventsJson(['success' => true, 'status' => null, 'participants_count' => (int)$cntG->fetchColumn(), 'interested_count' => (int)$cntI->fetchColumn()]);
            }

            // Check capacity for 'going'
            if ($newStatus === 'going' && $event['max_participants']) {
                $cnt = $pdo->prepare("SELECT COUNT(*) FROM event_participants WHERE event_id=? AND status='going'");
                $cnt->execute([$eventId]);
                if ((int)$cnt->fetchColumn() >= (int)$event['max_participants'])
                    eventsJson(['error' => 'Мест больше нет. Мероприятие заполнено.'], 400);
            }

            // Upsert — this REPLACES any previous status (going↔interested are mutually exclusive)
            $pdo->prepare("
                INSERT INTO event_participants (event_id, user_id, status)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE status=VALUES(status), created_at=CURRENT_TIMESTAMP
            ")->execute([$eventId, $userId, $newStatus]);

            // Return fresh counts
            $cntG = $pdo->prepare("SELECT COUNT(*) FROM event_participants WHERE event_id=? AND status='going'");
            $cntG->execute([$eventId]);
            $cntI = $pdo->prepare("SELECT COUNT(*) FROM event_participants WHERE event_id=? AND status='interested'");
            $cntI->execute([$eventId]);

            eventsJson([
                'success'           => true,
                'status'            => $newStatus,
                'participants_count'=> (int)$cntG->fetchColumn(),
                'interested_count'  => (int)$cntI->fetchColumn(),
            ]);
        }

        // Review
        if ($action === 'review') {
            $eventId = (int)($data['event_id'] ?? 0);
            $rating  = max(1, min(5, (int)($data['rating']  ?? 0)));
            $comment = trim((string)($data['comment'] ?? ''));
            if (!$eventId || !$rating) eventsJson(['error' => 'Оценка обязательна'], 400);

            // Only allow reviews for past events
            $stmt = $pdo->prepare("SELECT start_datetime FROM events WHERE id=? LIMIT 1");
            $stmt->execute([$eventId]);
            $ev = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$ev) eventsJson(['error' => 'Мероприятие не найдено'], 404);
            if (strtotime($ev['start_datetime']) > time()) eventsJson(['error' => 'Нельзя оставить отзыв до начала мероприятия'], 400);

            $pdo->prepare("
                INSERT INTO event_reviews (event_id, user_id, rating, comment)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment), created_at=CURRENT_TIMESTAMP
            ")->execute([$eventId, $userId, $rating, $comment]);
            eventsJson(['success' => true]);
        }

        // Create event
        $title       = trim((string)($data['title']        ?? ''));
        $description = trim((string)($data['description']  ?? ''));
        $format      = $data['event_format']               ?? 'offline';
        $category    = trim((string)($data['category']     ?? 'Другое'));
        $city        = trim((string)($data['city']         ?? ''));
        $location    = trim((string)($data['location']     ?? ''));
        $meetingLink = trim((string)($data['meeting_link'] ?? ''));
        $start       = trim((string)($data['start_datetime'] ?? ''));
        $end         = trim((string)($data['end_datetime']   ?? ''));
        $max         = isset($data['max_participants']) && $data['max_participants'] !== '' ? (int)$data['max_participants'] : null;
        $rawVisibility = $data['visibility'] ?? 'public';
        $visibility    = in_array($rawVisibility, ['public', 'private'], true) ? $rawVisibility : 'public';
        $cover       = trim((string)($data['cover_image']  ?? ''));
        $tags        = array_values(array_unique(array_filter(array_map('trim', (array)($data['tags'] ?? [])))));

        if (!$title || !$description || !$start || !in_array($format, ['online','offline'], true))
            eventsJson(['error' => 'Заполни название, описание, формат и дату начала'], 400);
        if ($format === 'offline' && (!$city || !$location))
            eventsJson(['error' => 'Для офлайн события нужны город и адрес'], 400);
        if ($format === 'online' && !$meetingLink)
            eventsJson(['error' => 'Для онлайн события нужна ссылка'], 400);
        if ($max !== null && $max < 1)
            eventsJson(['error' => 'Максимум участников должен быть больше 0'], 400);
        if (strtotime($start) === false)
            eventsJson(['error' => 'Некорректная дата начала'], 400);
        if ($end && strtotime($end) !== false && strtotime($end) < strtotime($start))
            eventsJson(['error' => 'Дата окончания не может быть раньше начала'], 400);

        $user        = getUser($userId);
        $creatorType = eventCreatorType($user ?: []);

        $stmt = $pdo->prepare("
            INSERT INTO events
                (title, description, cover_image, creator_id, creator_type, event_format,
                 city, location, meeting_link, category, max_participants,
                 start_datetime, end_datetime, visibility)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            mb_substr($title, 0, 180),
            $description,
            $cover ?: null,
            $userId,
            $creatorType,
            $format,
            $city        ?: null,
            $location    ?: null,
            $meetingLink ?: null,
            mb_substr($category ?: 'Другое', 0, 80),
            $max,
            date('Y-m-d H:i:s', strtotime($start)),
            $end ? date('Y-m-d H:i:s', strtotime($end)) : null,
            $visibility,
        ]);
        $eventId = (int)$pdo->lastInsertId();

        $tagInsert = $pdo->prepare("INSERT INTO event_tags (event_id, tag_name) VALUES (?, ?)");
        foreach (array_slice($tags ?: [$category], 0, 12) as $tag) {
            if ($tag !== '' && mb_strlen($tag) <= 80) $tagInsert->execute([$eventId, $tag]);
        }

        // Creator auto-joins as 'going'
        $pdo->prepare("INSERT INTO event_participants (event_id, user_id, status) VALUES (?, ?, 'going')")
            ->execute([$eventId, $userId]);

        eventsJson(['success' => true, 'event_id' => $eventId]);
    }

    eventsJson(['error' => 'Метод не разрешён'], 405);

} catch (PDOException $e) {
    eventsJson(['error' => 'Ошибка БД: ' . $e->getMessage()], 500);
} catch (Throwable $e) {
    eventsJson(['error' => $e->getMessage()], 500);
}
?>