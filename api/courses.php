<?php
/**
 * courses.php — платформенные курсы (создание, прохождение, рейтинг)
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/badges_lib.php';

if (!function_exists('normalizeMediaUrl') && is_readable(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

function crsMediaUrl(?string $url): ?string
{
    if (function_exists('normalizeMediaUrl')) {
        return normalizeMediaUrl($url);
    }
    if ($url === null) {
        return null;
    }
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    if (preg_match('#^(data:|blob:|https?://)#i', $url)) {
        return $url;
    }
    $path = $url[0] === '/' ? $url : '/' . $url;
    $path = preg_replace('#^/api/uploads/#', '/uploads/', $path);
    if (function_exists('mfAppOrigin')) {
        return rtrim(mfAppOrigin(), '/') . $path;
    }
    return $path;
}

function crsJson($data, $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function crsTableExists(PDO $pdo, string $table): bool
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

function ensureCoursesSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!crsTableExists($pdo, 'mf_courses')) {
        try {
            $pdo->exec("CREATE TABLE mf_courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                author_id INT NOT NULL,
                title VARCHAR(200) NOT NULL,
                description TEXT,
                cover_image VARCHAR(512) DEFAULT NULL,
                category VARCHAR(80) NOT NULL DEFAULT 'Другое',
                level ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
                status ENUM('draft','published') NOT NULL DEFAULT 'draft',
                estimated_minutes INT NOT NULL DEFAULT 30,
                rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0,
                rating_count INT NOT NULL DEFAULT 0,
                enrollments_count INT NOT NULL DEFAULT 0,
                completions_count INT NOT NULL DEFAULT 0,
                published_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_mf_courses_author (author_id),
                INDEX idx_mf_courses_status (status),
                INDEX idx_mf_courses_category (category),
                INDEX idx_mf_courses_published (published_at),
                INDEX idx_mf_courses_rating (rating_avg, enrollments_count)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }

    if (!crsTableExists($pdo, 'mf_course_tags')) {
        try {
            $pdo->exec("CREATE TABLE mf_course_tags (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                tag_name VARCHAR(60) NOT NULL,
                INDEX idx_mf_ct_course (course_id),
                INDEX idx_mf_ct_tag (tag_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }

    if (!crsTableExists($pdo, 'mf_course_lessons')) {
        try {
            $pdo->exec("CREATE TABLE mf_course_lessons (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                title VARCHAR(200) NOT NULL,
                content_json LONGTEXT,
                INDEX idx_mf_cl_course (course_id, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }

    if (!crsTableExists($pdo, 'mf_course_enrollments')) {
        try {
            $pdo->exec("CREATE TABLE mf_course_enrollments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                user_id INT NOT NULL,
                progress_pct TINYINT NOT NULL DEFAULT 0,
                completed_at TIMESTAMP NULL DEFAULT NULL,
                enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_mf_enroll (course_id, user_id),
                INDEX idx_mf_enroll_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }

    if (!crsTableExists($pdo, 'mf_course_lesson_progress')) {
        try {
            $pdo->exec("CREATE TABLE mf_course_lesson_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                user_id INT NOT NULL,
                lesson_id INT NOT NULL,
                quiz_passed TINYINT(1) NOT NULL DEFAULT 0,
                completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_mf_lprog (course_id, user_id, lesson_id),
                INDEX idx_mf_lprog_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }

    if (!crsTableExists($pdo, 'mf_course_reviews')) {
        try {
            $pdo->exec("CREATE TABLE mf_course_reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                user_id INT NOT NULL,
                rating TINYINT NOT NULL,
                review_text VARCHAR(200) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_mf_review (course_id, user_id),
                INDEX idx_mf_rev_course (course_id),
                INDEX idx_mf_rev_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }
}

const CRS_CATEGORIES = ['Дизайн', 'Код', 'Бизнес', 'Маркетинг', 'AI', 'Образование', 'Другое'];
const CRS_LEVELS = ['beginner', 'intermediate', 'advanced'];

function crsLevelLabel(string $level): string
{
    return [
        'beginner'     => 'Начинающий',
        'intermediate' => 'Средний',
        'advanced'     => 'Продвинутый',
    ][$level] ?? $level;
}

function crsFetchTags(PDO $pdo, int $courseId): array
{
    if (!crsTableExists($pdo, 'mf_course_tags')) {
        return [];
    }
    $st = $pdo->prepare('SELECT tag_name FROM mf_course_tags WHERE course_id = ? ORDER BY id');
    $st->execute([$courseId]);
    return array_map('strval', $st->fetchAll(PDO::FETCH_COLUMN));
}

function crsSaveTags(PDO $pdo, int $courseId, array $tags): void
{
    $pdo->prepare('DELETE FROM mf_course_tags WHERE course_id = ?')->execute([$courseId]);
    $ins = $pdo->prepare('INSERT INTO mf_course_tags (course_id, tag_name) VALUES (?, ?)');
    foreach ($tags as $tag) {
        $tag = mb_substr(trim((string)$tag), 0, 60);
        if ($tag !== '') {
            $ins->execute([$courseId, $tag]);
        }
    }
}

function crsLessonCount(PDO $pdo, int $courseId): int
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM mf_course_lessons WHERE course_id = ?');
    $st->execute([$courseId]);
    return (int)$st->fetchColumn();
}

function crsCompletedLessons(PDO $pdo, int $courseId, int $userId): int
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM mf_course_lesson_progress
         WHERE course_id = ? AND user_id = ? AND quiz_passed = 1'
    );
    $st->execute([$courseId, $userId]);
    return (int)$st->fetchColumn();
}

function crsRecalcProgress(PDO $pdo, int $courseId, int $userId): int
{
    $total = crsLessonCount($pdo, $courseId);
    if ($total <= 0) {
        return 0;
    }
    $done = crsCompletedLessons($pdo, $courseId, $userId);
    $pct  = (int)round(($done / $total) * 100);

    $pdo->prepare(
        'UPDATE mf_course_enrollments SET progress_pct = ? WHERE course_id = ? AND user_id = ?'
    )->execute([$pct, $courseId, $userId]);

    if ($pct >= 100) {
        $pdo->prepare(
            'UPDATE mf_course_enrollments SET completed_at = COALESCE(completed_at, NOW())
             WHERE course_id = ? AND user_id = ?'
        )->execute([$courseId, $userId]);

        $st = $pdo->prepare(
            'SELECT completed_at FROM mf_course_enrollments WHERE course_id = ? AND user_id = ? LIMIT 1'
        );
        $st->execute([$courseId, $userId]);
        $wasNull = !$st->fetchColumn();
        if ($wasNull) {
            $pdo->prepare('UPDATE mf_courses SET completions_count = completions_count + 1 WHERE id = ?')
                ->execute([$courseId]);
        }
    }

    return $pct;
}

function crsRecalcRating(PDO $pdo, int $courseId): void
{
    $st = $pdo->prepare(
        'SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt
         FROM mf_course_reviews WHERE course_id = ?'
    );
    $st->execute([$courseId]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
    $avg = round((float)($row['avg_r'] ?? 0), 2);
    $cnt = (int)($row['cnt'] ?? 0);
    $pdo->prepare('UPDATE mf_courses SET rating_avg = ?, rating_count = ? WHERE id = ?')
        ->execute([$avg, $cnt, $courseId]);
    crsCheckAuthorBadges($pdo, $courseId);
}

function crsCheckAuthorBadges(PDO $pdo, int $courseId): void
{
    try {
        ensureUserBadgesSchema($pdo);
        $st = $pdo->prepare('SELECT author_id, status, rating_avg FROM mf_courses WHERE id = ? LIMIT 1');
        $st->execute([$courseId]);
        $course = $st->fetch(PDO::FETCH_ASSOC);
        if (!$course) {
            return;
        }
        $authorId = (int)$course['author_id'];

        if ($course['status'] === 'published') {
            $cnt = $pdo->prepare(
                "SELECT COUNT(*) FROM mf_courses WHERE author_id = ? AND status = 'published'"
            );
            $cnt->execute([$authorId]);
            if ((int)$cnt->fetchColumn() === 1) {
                $pdo->prepare('INSERT IGNORE INTO user_badges (user_id, badge_key) VALUES (?, ?)')
                    ->execute([$authorId, 'first_course']);
            }
        }

        if ((float)$course['rating_avg'] >= 4.5) {
            $revCnt = $pdo->prepare('SELECT rating_count FROM mf_courses WHERE id = ?');
            $revCnt->execute([$courseId]);
            if ((int)$revCnt->fetchColumn() >= 3) {
                $pdo->prepare('INSERT IGNORE INTO user_badges (user_id, badge_key, meta_json) VALUES (?, ?, ?)')
                    ->execute([$authorId, 'top_course', json_encode(['course_id' => $courseId])]);
            }
        }
    } catch (Throwable $e) {
    }
}

function crsFetchAuthor(PDO $pdo, int $userId): array
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
        'avatar'      => crsMediaUrl($u['avatar'] ?? null),
        'is_verified' => !empty($u['is_verified']),
        'name'        => trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')),
    ];
}

function crsShapeCourse(PDO $pdo, array $row, ?int $viewerId = null, bool $withLessons = false): array
{
    $id = (int)$row['id'];
    $author = crsFetchAuthor($pdo, (int)$row['author_id']);
    $tags = crsFetchTags($pdo, $id);
    $lessonCount = crsLessonCount($pdo, $id);

    $enrolled = false;
    $progress = 0;
    $completed = false;
    $canRate = false;
    $myRating = null;

    if ($viewerId) {
        $st = $pdo->prepare(
            'SELECT progress_pct, completed_at FROM mf_course_enrollments WHERE course_id = ? AND user_id = ? LIMIT 1'
        );
        $st->execute([$id, $viewerId]);
        $enr = $st->fetch(PDO::FETCH_ASSOC);
        if ($enr) {
            $enrolled = true;
            $progress = (int)$enr['progress_pct'];
            $completed = !empty($enr['completed_at']);
        }
        if ($progress >= 50) {
            $canRate = true;
            $rv = $pdo->prepare('SELECT rating, review_text FROM mf_course_reviews WHERE course_id = ? AND user_id = ? LIMIT 1');
            $rv->execute([$id, $viewerId]);
            $myRating = $rv->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    $course = [
        'id'                  => $id,
        'title'               => $row['title'],
        'description'         => $row['description'] ?? '',
        'cover_image'         => crsMediaUrl($row['cover_image'] ?? null),
        'category'            => $row['category'] ?? 'Другое',
        'level'               => $row['level'] ?? 'beginner',
        'level_label'         => crsLevelLabel($row['level'] ?? 'beginner'),
        'status'              => $row['status'] ?? 'draft',
        'estimated_minutes'   => (int)($row['estimated_minutes'] ?? 30),
        'rating_avg'          => round((float)($row['rating_avg'] ?? 0), 1),
        'rating_count'        => (int)($row['rating_count'] ?? 0),
        'enrollments_count'   => (int)($row['enrollments_count'] ?? 0),
        'completions_count'   => (int)($row['completions_count'] ?? 0),
        'lesson_count'        => $lessonCount,
        'tags'                => $tags,
        'author'              => $author,
        'author_id'           => (int)$row['author_id'],
        'is_author'           => (bool)($viewerId && (int)$row['author_id'] === (int)$viewerId),
        'enrolled'            => $enrolled,
        'progress_pct'        => $progress,
        'completed'           => $completed,
        'can_rate'            => $canRate,
        'my_rating'           => $myRating,
        'published_at'        => !empty($row['published_at']) ? utcDate($row['published_at']) : null,
        'created_at'          => utcDate($row['created_at'] ?? date('Y-m-d H:i:s')),
    ];

    if ($withLessons) {
        $st = $pdo->prepare(
            'SELECT id, sort_order, title, content_json FROM mf_course_lessons
             WHERE course_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $st->execute([$id]);
        $lessons = [];
        $completedIds = [];
        if ($viewerId) {
            $cp = $pdo->prepare(
                'SELECT lesson_id FROM mf_course_lesson_progress
                 WHERE course_id = ? AND user_id = ? AND quiz_passed = 1'
            );
            $cp->execute([$id, $viewerId]);
            foreach ($cp->fetchAll(PDO::FETCH_COLUMN) as $lid) {
                $completedIds[(int)$lid] = true;
            }
        }
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $l) {
            $content = json_decode($l['content_json'] ?? '{}', true) ?: [];
            $quiz = $content['quiz'] ?? null;
            if ($quiz && !$course['is_author']) {
                unset($quiz['correct']);
            }
            $lessons[] = [
                'id'          => (int)$l['id'],
                'sort_order'  => (int)$l['sort_order'],
                'title'       => $l['title'],
                'blocks'      => $content['blocks'] ?? [],
                'quiz'        => $quiz,
                'completed'   => isset($completedIds[(int)$l['id']]),
            ];
        }
        $course['lessons'] = $lessons;
    }

    return $course;
}

function crsAssertAuthor(PDO $pdo, int $courseId, int $userId): array
{
    $st = $pdo->prepare('SELECT * FROM mf_courses WHERE id = ? LIMIT 1');
    $st->execute([$courseId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        crsJson(['error' => 'Курс не найден'], 404);
    }
    if ((int)$row['author_id'] !== $userId) {
        crsJson(['error' => 'Нет прав'], 403);
    }
    return $row;
}

function crsFriendIds(PDO $pdo, int $userId): array
{
    if (!crsTableExists($pdo, 'friendships')) {
        return [];
    }
    try {
        $st = $pdo->prepare(
            'SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS uid
             FROM friendships
             WHERE status = "accepted" AND (sender_id = ? OR receiver_id = ?)'
        );
        $st->execute([$userId, $userId, $userId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
        return [];
    }
}

function crsEstimateMinutes(array $lessons): int
{
    $mins = 0;
    foreach ($lessons as $l) {
        $blocks = $l['blocks'] ?? [];
        foreach ($blocks as $b) {
            if (($b['type'] ?? '') === 'text') {
                $mins += max(1, (int)ceil(mb_strlen($b['text'] ?? '') / 800));
            }
        }
        if (!empty($l['quiz'])) {
            $mins += 2;
        }
        $mins += 3;
    }
    return max(10, $mins);
}

function crsSaveLessons(PDO $pdo, int $courseId, array $lessons): void
{
    $pdo->prepare('DELETE FROM mf_course_lessons WHERE course_id = ?')->execute([$courseId]);
    $ins = $pdo->prepare(
        'INSERT INTO mf_course_lessons (course_id, sort_order, title, content_json) VALUES (?,?,?,?)'
    );
    $order = 0;
    foreach ($lessons as $l) {
        $title = trim($l['title'] ?? '');
        if ($title === '') {
            continue;
        }
        $content = [
            'blocks' => is_array($l['blocks'] ?? null) ? $l['blocks'] : [],
            'quiz'   => $l['quiz'] ?? null,
        ];
        if (!empty($content['quiz'])) {
            $opts = array_values(array_slice((array)($content['quiz']['options'] ?? []), 0, 4));
            while (count($opts) < 4) {
                $opts[] = '';
            }
            $content['quiz'] = [
                'question' => trim($content['quiz']['question'] ?? ''),
                'options'  => $opts,
                'correct'  => max(0, min(3, (int)($content['quiz']['correct'] ?? 0))),
            ];
        } else {
            $content['quiz'] = null;
        }
        $ins->execute([$courseId, $order++, $title, json_encode($content, JSON_UNESCAPED_UNICODE)]);
    }
}

function crsNormalizeQuiz(?array $quiz): ?array
{
    if (!$quiz || trim($quiz['question'] ?? '') === '') {
        return null;
    }
    $opts = array_values(array_filter(
        array_map('trim', array_slice((array)($quiz['options'] ?? []), 0, 4)),
        fn($o) => $o !== ''
    ));
    if (count($opts) < 2) {
        return null;
    }
    while (count($opts) < 4) {
        $opts[] = '';
    }
    return [
        'question' => trim($quiz['question']),
        'options'  => $opts,
        'correct'  => max(0, min(count($opts) - 1, (int)($quiz['correct'] ?? 0))),
    ];
}

function crsRecalcCourseMinutes(PDO $pdo, int $courseId): void
{
    $st = $pdo->prepare('SELECT content_json FROM mf_course_lessons WHERE course_id = ? ORDER BY sort_order ASC, id ASC');
    $st->execute([$courseId]);
    $lessons = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $content = json_decode($row['content_json'] ?? '{}', true) ?: [];
        $lessons[] = [
            'blocks' => $content['blocks'] ?? [],
            'quiz'   => $content['quiz'] ?? null,
        ];
    }
    $mins = crsEstimateMinutes($lessons);
    $pdo->prepare('UPDATE mf_courses SET estimated_minutes = ? WHERE id = ?')->execute([$mins, $courseId]);
}

function crsSyncLessonTitles(PDO $pdo, int $courseId, array $lessons): array
{
    $existing = [];
    $st = $pdo->prepare('SELECT id, title, content_json, sort_order FROM mf_course_lessons WHERE course_id = ? ORDER BY sort_order ASC, id ASC');
    $st->execute([$courseId]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $existing[(int)$row['id']] = $row;
    }

    $keptIds = [];
    $result  = [];
    $order   = 0;

    foreach ($lessons as $l) {
        $title = trim($l['title'] ?? '');
        if ($title === '') {
            continue;
        }
        $lessonId = (int)($l['id'] ?? 0);
        if ($lessonId && isset($existing[$lessonId])) {
            $contentJson = $existing[$lessonId]['content_json'] ?? '{}';
            $pdo->prepare('UPDATE mf_course_lessons SET title = ?, sort_order = ? WHERE id = ? AND course_id = ?')
                ->execute([$title, $order, $lessonId, $courseId]);
            $keptIds[] = $lessonId;
            $result[] = ['id' => $lessonId, 'title' => $title, 'sort_order' => $order];
        } else {
            $empty = json_encode(['blocks' => [], 'quiz' => null], JSON_UNESCAPED_UNICODE);
            $pdo->prepare('INSERT INTO mf_course_lessons (course_id, sort_order, title, content_json) VALUES (?,?,?,?)')
                ->execute([$courseId, $order, $title, $empty]);
            $newId = (int)$pdo->lastInsertId();
            $keptIds[] = $newId;
            $result[] = ['id' => $newId, 'title' => $title, 'sort_order' => $order];
        }
        $order++;
    }

    foreach (array_keys($existing) as $oldId) {
        if (!in_array($oldId, $keptIds, true)) {
            $pdo->prepare('DELETE FROM mf_course_lesson_progress WHERE lesson_id = ?')->execute([$oldId]);
            $pdo->prepare('DELETE FROM mf_course_lessons WHERE id = ? AND course_id = ?')->execute([$oldId, $courseId]);
        }
    }

    return $result;
}

try {
    ensureCoursesSchema($pdo);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $action = trim($_GET['action'] ?? 'list');
        $viewerId = verifyTokenSoft();

        if ($action === 'meta') {
            crsJson([
                'categories' => CRS_CATEGORIES,
                'levels'     => array_map(fn($l) => ['key' => $l, 'label' => crsLevelLabel($l)], CRS_LEVELS),
            ]);
        }

        if ($action === 'detail') {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                crsJson(['error' => 'id required'], 400);
            }
            $st = $pdo->prepare('SELECT * FROM mf_courses WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                crsJson(['error' => 'Курс не найден'], 404);
            }
            if ($row['status'] !== 'published' && (!$viewerId || (int)$row['author_id'] !== $viewerId)) {
                crsJson(['error' => 'Курс недоступен'], 403);
            }
            $course = crsShapeCourse($pdo, $row, $viewerId, true);

            $revs = $pdo->prepare("
                SELECT r.rating, r.review_text, r.created_at,
                       u.first_name, u.last_name, u.avatar
                FROM mf_course_reviews r
                JOIN users u ON u.id = r.user_id
                WHERE r.course_id = ?
                ORDER BY r.created_at DESC LIMIT 20
            ");
            $revs->execute([$id]);
            $reviews = [];
            foreach ($revs->fetchAll(PDO::FETCH_ASSOC) as $rv) {
                $reviews[] = [
                    'rating'      => (int)$rv['rating'],
                    'review_text' => $rv['review_text'] ?? '',
                    'created_at'  => utcDate($rv['created_at']),
                    'author_name' => trim(($rv['first_name'] ?? '') . ' ' . ($rv['last_name'] ?? '')),
                    'avatar'      => $rv['avatar'] ?? null,
                ];
            }
            $course['reviews'] = $reviews;

            crsJson(['course' => $course]);
        }

        if ($action === 'my_created' || $action === 'my_enrolled' || $action === 'my_completed') {
            if (!$viewerId) {
                crsJson(['error' => 'Требуется авторизация'], 401);
            }
            if ($action === 'my_created') {
                $st = $pdo->prepare('SELECT * FROM mf_courses WHERE author_id = ? ORDER BY updated_at DESC');
                $st->execute([$viewerId]);
            } elseif ($action === 'my_completed') {
                $st = $pdo->prepare("
                    SELECT c.* FROM mf_courses c
                    JOIN mf_course_enrollments e ON e.course_id = c.id AND e.user_id = ?
                    WHERE e.completed_at IS NOT NULL
                    ORDER BY e.completed_at DESC
                ");
                $st->execute([$viewerId]);
            } else {
                $st = $pdo->prepare("
                    SELECT c.* FROM mf_courses c
                    JOIN mf_course_enrollments e ON e.course_id = c.id AND e.user_id = ?
                    ORDER BY e.enrolled_at DESC
                ");
                $st->execute([$viewerId]);
            }
            $courses = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $courses[] = crsShapeCourse($pdo, $row, $viewerId);
            }
            crsJson(['courses' => $courses]);
        }

        if ($action === 'author_reviews') {
            $authorId = (int)($_GET['user_id'] ?? 0);
            if (!$authorId) {
                crsJson(['error' => 'user_id required'], 400);
            }
            $st = $pdo->prepare("
                SELECT r.rating, r.review_text, r.created_at,
                       c.title AS course_title, c.id AS course_id,
                       u.first_name, u.last_name, u.avatar
                FROM mf_course_reviews r
                JOIN mf_courses c ON c.id = r.course_id AND c.author_id = ?
                JOIN users u ON u.id = r.user_id
                ORDER BY r.created_at DESC LIMIT 50
            ");
            $st->execute([$authorId]);
            $reviews = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $rv) {
                $reviews[] = [
                    'course_id'    => (int)$rv['course_id'],
                    'course_title' => $rv['course_title'],
                    'rating'       => (int)$rv['rating'],
                    'review_text'  => $rv['review_text'] ?? '',
                    'created_at'   => utcDate($rv['created_at']),
                    'author_name'  => trim(($rv['first_name'] ?? '') . ' ' . ($rv['last_name'] ?? '')),
                    'avatar'       => $rv['avatar'] ?? null,
                ];
            }
            crsJson(['reviews' => $reviews]);
        }

        // list (default)
        $tab      = trim($_GET['tab'] ?? 'popular');
        $q        = trim($_GET['q'] ?? '');
        $tag      = trim($_GET['tag'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $level    = trim($_GET['level'] ?? '');

        if ($tab === 'drafts') {
            if (!$viewerId) {
                crsJson(['error' => 'Требуется авторизация'], 401);
            }
            $sql = "SELECT c.* FROM mf_courses c WHERE c.author_id = ? AND c.status = 'draft'";
            $params = [$viewerId];
            if ($category !== '') {
                $sql .= ' AND c.category = ?';
                $params[] = $category;
            }
            if ($level !== '' && in_array($level, CRS_LEVELS, true)) {
                $sql .= ' AND c.level = ?';
                $params[] = $level;
            }
            if ($q !== '') {
                $sql .= ' AND (c.title LIKE ? OR c.description LIKE ?)';
                $like = '%' . $q . '%';
                $params[] = $like;
                $params[] = $like;
            }
            if ($tag !== '') {
                $sql .= ' AND EXISTS (SELECT 1 FROM mf_course_tags t WHERE t.course_id = c.id AND t.tag_name = ?)';
                $params[] = $tag;
            }
            $sql .= ' ORDER BY c.updated_at DESC, c.created_at DESC LIMIT 40';
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $courses = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $courses[] = crsShapeCourse($pdo, $row, $viewerId);
            }
            crsJson(['courses' => $courses, 'tab' => 'drafts']);
        }

        if ($tab === 'my') {
            if (!$viewerId) {
                crsJson(['error' => 'Требуется авторизация'], 401);
            }
            $sql = 'SELECT c.* FROM mf_courses c WHERE c.author_id = ?';
            $params = [$viewerId];
            if ($category !== '') {
                $sql .= ' AND c.category = ?';
                $params[] = $category;
            }
            if ($level !== '' && in_array($level, CRS_LEVELS, true)) {
                $sql .= ' AND c.level = ?';
                $params[] = $level;
            }
            if ($q !== '') {
                $sql .= ' AND (c.title LIKE ? OR c.description LIKE ?)';
                $like = '%' . $q . '%';
                $params[] = $like;
                $params[] = $like;
            }
            if ($tag !== '') {
                $sql .= ' AND EXISTS (SELECT 1 FROM mf_course_tags t WHERE t.course_id = c.id AND t.tag_name = ?)';
                $params[] = $tag;
            }
            $sql .= ' ORDER BY c.updated_at DESC, c.created_at DESC LIMIT 40';
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $courses = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $courses[] = crsShapeCourse($pdo, $row, $viewerId);
            }
            crsJson(['courses' => $courses, 'tab' => 'my']);
        }

        $sql = "SELECT c.* FROM mf_courses c WHERE c.status = 'published'";
        $params = [];

        if ($category !== '') {
            $sql .= ' AND c.category = ?';
            $params[] = $category;
        }
        if ($level !== '' && in_array($level, CRS_LEVELS, true)) {
            $sql .= ' AND c.level = ?';
            $params[] = $level;
        }
        if ($q !== '') {
            $sql .= ' AND (c.title LIKE ? OR c.description LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if ($tag !== '') {
            $sql .= ' AND EXISTS (SELECT 1 FROM mf_course_tags t WHERE t.course_id = c.id AND t.tag_name = ?)';
            $params[] = $tag;
        }
        if ($tab === 'friends' && $viewerId) {
            $friendIds = crsFriendIds($pdo, $viewerId);
            if (!$friendIds) {
                crsJson(['courses' => []]);
            }
            $ph = implode(',', array_fill(0, count($friendIds), '?'));
            $sql .= " AND c.author_id IN ($ph)";
            $params = array_merge($params, $friendIds);
            $sql .= ' ORDER BY c.published_at DESC LIMIT 40';
        } elseif ($tab === 'new') {
            $sql .= ' ORDER BY c.published_at DESC, c.created_at DESC LIMIT 40';
        } else {
            $sql .= ' ORDER BY (c.rating_avg * GREATEST(c.completions_count, 1)) DESC, c.enrollments_count DESC LIMIT 40';
        }

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $courses = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $courses[] = crsShapeCourse($pdo, $row, $viewerId);
        }
        crsJson(['courses' => $courses, 'tab' => $tab]);
    }

    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = trim($data['action'] ?? '');

        if ($action === 'create' || $action === 'update') {
            $title = trim($data['title'] ?? '');
            if ($title === '') {
                crsJson(['error' => 'Название обязательно'], 400);
            }
            $description = trim($data['description'] ?? '');
            $coverProvided = array_key_exists('cover_image', $data);
            $cover = $coverProvided ? (trim((string)($data['cover_image'] ?? '')) ?: null) : null;
            $category    = trim($data['category'] ?? 'Другое');
            if (!in_array($category, CRS_CATEGORIES, true)) {
                $category = 'Другое';
            }
            $level = trim($data['level'] ?? 'beginner');
            if (!in_array($level, CRS_LEVELS, true)) {
                $level = 'beginner';
            }
            $tags    = is_array($data['tags'] ?? null) ? $data['tags'] : [];
            $lessons = is_array($data['lessons'] ?? null) ? $data['lessons'] : null;
            $estMins = $lessons !== null ? crsEstimateMinutes($lessons) : (int)($data['estimated_minutes'] ?? 30);

            if ($action === 'create') {
                $pdo->prepare("
                    INSERT INTO mf_courses (author_id, title, description, cover_image, category, level, estimated_minutes, status)
                    VALUES (?,?,?,?,?,?,?, 'draft')
                ")->execute([$userId, $title, $description, $cover, $category, $level, max(10, $estMins)]);
                $courseId = (int)$pdo->lastInsertId();
            } else {
                $courseId = (int)($data['id'] ?? 0);
                $existing = crsAssertAuthor($pdo, $courseId, $userId);
                if (!$coverProvided) {
                    $cover = $existing['cover_image'] ?? null;
                }
                $pdo->prepare("
                    UPDATE mf_courses SET title=?, description=?, cover_image=?, category=?, level=?, estimated_minutes=?
                    WHERE id=?
                ")->execute([$title, $description, $cover, $category, $level, max(10, $estMins), $courseId]);
            }

            crsSaveTags($pdo, $courseId, $tags);
            if ($lessons !== null) {
                crsSaveLessons($pdo, $courseId, $lessons);
            }

            $st = $pdo->prepare('SELECT * FROM mf_courses WHERE id = ? LIMIT 1');
            $st->execute([$courseId]);
            crsJson(['success' => true, 'course' => crsShapeCourse($pdo, $st->fetch(PDO::FETCH_ASSOC), $userId, true)]);
        }

        if ($action === 'create_lesson') {
            $courseId = (int)($data['course_id'] ?? 0);
            crsAssertAuthor($pdo, $courseId, $userId);
            $items = is_array($data['lessons'] ?? null) ? $data['lessons'] : [];
            if (!$items) {
                crsJson(['error' => 'Добавьте хотя бы один урок'], 400);
            }
            $synced = crsSyncLessonTitles($pdo, $courseId, $items);
            crsRecalcCourseMinutes($pdo, $courseId);
            crsJson(['success' => true, 'lessons' => $synced]);
        }

        if ($action === 'update_lesson') {
            $courseId = (int)($data['course_id'] ?? 0);
            $lessonId = (int)($data['lesson_id'] ?? 0);
            crsAssertAuthor($pdo, $courseId, $userId);

            $st = $pdo->prepare('SELECT id, content_json FROM mf_course_lessons WHERE id = ? AND course_id = ? LIMIT 1');
            $st->execute([$lessonId, $courseId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                crsJson(['error' => 'Урок не найден'], 404);
            }

            $content = json_decode($row['content_json'] ?? '{}', true) ?: [];

            if (array_key_exists('blocks', $data)) {
                $blocks = [];
                foreach ((array)$data['blocks'] as $b) {
                    if (!is_array($b)) {
                        continue;
                    }
                    $type = $b['type'] ?? '';
                    if ($type === 'heading') {
                        $blocks[] = ['type' => 'heading', 'text' => trim($b['text'] ?? '')];
                    } elseif ($type === 'text') {
                        $blocks[] = ['type' => 'text', 'text' => trim($b['text'] ?? '')];
                    } elseif ($type === 'image' && trim($b['url'] ?? '') !== '') {
                        $blocks[] = ['type' => 'image', 'url' => trim($b['url'])];
                    } elseif ($type === 'divider') {
                        $blocks[] = ['type' => 'divider'];
                    }
                }
                $content['blocks'] = $blocks;
            }

            if (array_key_exists('quiz', $data) || array_key_exists('quiz_data', $data)) {
                $quizRaw = $data['quiz'] ?? $data['quiz_data'] ?? null;
                $content['quiz'] = crsNormalizeQuiz(is_array($quizRaw) ? $quizRaw : null);
            }

            $pdo->prepare('UPDATE mf_course_lessons SET content_json = ? WHERE id = ? AND course_id = ?')
                ->execute([json_encode($content, JSON_UNESCAPED_UNICODE), $lessonId, $courseId]);
            crsRecalcCourseMinutes($pdo, $courseId);
            crsJson(['success' => true, 'lesson_id' => $lessonId]);
        }

        if ($action === 'publish') {
            $courseId = (int)($data['id'] ?? 0);
            crsAssertAuthor($pdo, $courseId, $userId);
            if (crsLessonCount($pdo, $courseId) < 1) {
                crsJson(['error' => 'Добавьте хотя бы один урок'], 400);
            }
            $pdo->prepare("
                UPDATE mf_courses SET status='published', published_at=COALESCE(published_at, NOW()) WHERE id=?
            ")->execute([$courseId]);
            crsCheckAuthorBadges($pdo, $courseId);
            $st = $pdo->prepare('SELECT * FROM mf_courses WHERE id = ? LIMIT 1');
            $st->execute([$courseId]);
            crsJson(['success' => true, 'course' => crsShapeCourse($pdo, $st->fetch(PDO::FETCH_ASSOC), $userId, true)]);
        }

        if ($action === 'delete') {
            $courseId = (int)($data['id'] ?? 0);
            crsAssertAuthor($pdo, $courseId, $userId);
            $pdo->prepare('DELETE FROM mf_course_tags WHERE course_id = ?')->execute([$courseId]);
            $pdo->prepare('DELETE FROM mf_course_lesson_progress WHERE course_id = ?')->execute([$courseId]);
            $pdo->prepare('DELETE FROM mf_course_enrollments WHERE course_id = ?')->execute([$courseId]);
            $pdo->prepare('DELETE FROM mf_course_reviews WHERE course_id = ?')->execute([$courseId]);
            $pdo->prepare('DELETE FROM mf_course_lessons WHERE course_id = ?')->execute([$courseId]);
            $pdo->prepare('DELETE FROM mf_courses WHERE id = ?')->execute([$courseId]);
            crsJson(['success' => true]);
        }

        if ($action === 'enroll') {
            $courseId = (int)($data['course_id'] ?? 0);
            $st = $pdo->prepare("SELECT * FROM mf_courses WHERE id = ? AND status = 'published' LIMIT 1");
            $st->execute([$courseId]);
            if (!$st->fetch()) {
                crsJson(['error' => 'Курс недоступен'], 404);
            }
            try {
                $pdo->prepare('INSERT INTO mf_course_enrollments (course_id, user_id) VALUES (?, ?)')
                    ->execute([$courseId, $userId]);
                $pdo->prepare('UPDATE mf_courses SET enrollments_count = enrollments_count + 1 WHERE id = ?')
                    ->execute([$courseId]);
            } catch (Throwable $e) {
            }
            crsJson(['success' => true, 'progress_pct' => crsRecalcProgress($pdo, $courseId, $userId)]);
        }

        if ($action === 'complete_lesson') {
            $courseId  = (int)($data['course_id'] ?? 0);
            $lessonId  = (int)($data['lesson_id'] ?? 0);
            $answer    = isset($data['quiz_answer']) ? (int)$data['quiz_answer'] : null;

            $st = $pdo->prepare(
                'SELECT content_json FROM mf_course_lessons WHERE id = ? AND course_id = ? LIMIT 1'
            );
            $st->execute([$lessonId, $courseId]);
            $lessonRow = $st->fetch(PDO::FETCH_ASSOC);
            if (!$lessonRow) {
                crsJson(['error' => 'Урок не найден'], 404);
            }
            $content = json_decode($lessonRow['content_json'] ?? '{}', true) ?: [];
            $quiz = $content['quiz'] ?? null;
            if ($quiz) {
                if ($answer === null) {
                    crsJson(['error' => 'Ответ на квиз обязателен'], 400);
                }
                $correct = (int)($quiz['correct'] ?? 0);
                if ($answer !== $correct) {
                    crsJson(['error' => 'Неверный ответ', 'correct' => false], 400);
                }
            }

            $chk = $pdo->prepare(
                'SELECT id FROM mf_course_enrollments WHERE course_id = ? AND user_id = ? LIMIT 1'
            );
            $chk->execute([$courseId, $userId]);
            if (!$chk->fetch()) {
                crsJson(['error' => 'Сначала запишитесь на курс'], 403);
            }

            $pdo->prepare("
                INSERT INTO mf_course_lesson_progress (course_id, user_id, lesson_id, quiz_passed)
                VALUES (?,?,?,1)
                ON DUPLICATE KEY UPDATE quiz_passed=1, completed_at=NOW()
            ")->execute([$courseId, $userId, $lessonId]);

            $pct = crsRecalcProgress($pdo, $courseId, $userId);
            crsJson(['success' => true, 'progress_pct' => $pct, 'completed' => $pct >= 100]);
        }

        if ($action === 'review') {
            $courseId = (int)($data['course_id'] ?? 0);
            $rating   = max(1, min(5, (int)($data['rating'] ?? 0)));
            $text     = mb_substr(trim($data['review_text'] ?? ''), 0, 200);

            $st = $pdo->prepare(
                'SELECT progress_pct FROM mf_course_enrollments WHERE course_id = ? AND user_id = ? LIMIT 1'
            );
            $st->execute([$courseId, $userId]);
            $enr = $st->fetch(PDO::FETCH_ASSOC);
            if (!$enr || (int)$enr['progress_pct'] < 50) {
                crsJson(['error' => 'Оценку можно поставить после 50% прохождения'], 403);
            }

            $pdo->prepare("
                INSERT INTO mf_course_reviews (course_id, user_id, rating, review_text)
                VALUES (?,?,?,?)
                ON DUPLICATE KEY UPDATE rating=VALUES(rating), review_text=VALUES(review_text)
            ")->execute([$courseId, $userId, $rating, $text ?: null]);
            crsRecalcRating($pdo, $courseId);
            crsJson(['success' => true]);
        }

        if ($action === 'certificate_post') {
            $courseId = (int)($data['course_id'] ?? 0);
            $message  = trim($data['message'] ?? '');

            $st = $pdo->prepare("
                SELECT e.completed_at, c.title, c.cover_image
                FROM mf_course_enrollments e
                JOIN mf_courses c ON c.id = e.course_id
                WHERE e.course_id = ? AND e.user_id = ? AND e.completed_at IS NOT NULL LIMIT 1
            ");
            $st->execute([$courseId, $userId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                crsJson(['error' => 'Сначала завершите курс'], 403);
            }

            $cols     = $pdo->query('SHOW COLUMNS FROM posts')->fetchAll(PDO::FETCH_COLUMN);
            $textCol  = in_array('text', $cols) ? 'text' : 'content';
            $imageCol = in_array('image_url', $cols) ? 'image_url' : 'image';

            $content = $message !== '' ? $message : ('🎓 Завершил(а) курс «' . $row['title'] . '»');
            $content .= "\n\n%%CERTIFICATE%%" . json_encode([
                'course_id'    => $courseId,
                'course_title' => $row['title'],
                'cover_image'  => $row['cover_image'] ?? null,
            ], JSON_UNESCAPED_UNICODE) . '%%CERTIFICATE_END%%';

            $pdo->prepare("INSERT INTO posts (user_id, {$textCol}, {$imageCol}) VALUES (?, ?, ?)")
                ->execute([$userId, $content, $row['cover_image'] ?? null]);
            crsJson(['success' => true, 'post_id' => (int)$pdo->lastInsertId()]);
        }

        crsJson(['error' => 'Неизвестный action'], 400);
    }

    crsJson(['error' => 'Метод не разрешён'], 405);
} catch (PDOException $e) {
    crsJson(['error' => 'DB: ' . $e->getMessage()], 500);
} catch (Throwable $e) {
    crsJson(['error' => $e->getMessage()], 500);
}
