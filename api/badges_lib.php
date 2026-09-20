<?php
/**
 * Mendflow achievement badges — definitions + computation
 * Library file — do not open directly in browser.
 */
if (PHP_SAPI !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Forbidden');
}

function ensureUserBadgesSchema(PDO $pdo): void
{
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_badges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            badge_key VARCHAR(40) NOT NULL,
            meta_json TEXT NULL,
            verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verified_by INT NULL,
            UNIQUE KEY uq_user_badge (user_id, badge_key),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
        // table may already exist with different engine — ignore
    }
}

function mfBadgeDefinitions(): array
{
    return [
        'product_launch' => [
            'emoji' => '🚀',
            'title' => 'Запустил продукт',
            'hint'  => 'Команда подтверждает + ссылка на живой продукт',
            'order' => 1,
        ],
        'team_builder' => [
            'emoji' => '👥',
            'title' => 'Построил команду',
            'hint'  => '5+ участников в завершённом проекте',
            'order' => 2,
        ],
        'serial_founder' => [
            'emoji' => '💡',
            'title' => 'Серийный основатель',
            'hint'  => '3+ завершённых проекта',
            'order' => 3,
        ],
        'first_course' => [
            'emoji' => '📚',
            'title' => 'Первый курс',
            'hint'  => 'Опубликован первый курс на платформе',
            'order' => 4,
        ],
        'top_course' => [
            'emoji' => '⭐',
            'title' => 'Топ-курс',
            'hint'  => 'Курс с рейтингом 4.5+',
            'order' => 5,
        ],
    ];
}

function mfTableExists(PDO $pdo, string $table): bool
{
    try {
        $st = $pdo->prepare("
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
            LIMIT 1
        ");
        $st->execute([$table]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

/** @return string[] badge keys earned by user */
function mfFetchVerifiedBadgeKeys(PDO $pdo, int $userId): array
{
    if (!mfTableExists($pdo, 'user_badges')) {
        return [];
    }
    try {
        $st = $pdo->prepare('SELECT badge_key FROM user_badges WHERE user_id = ?');
        $st->execute([$userId]);
        return array_values(array_filter(array_map('strval', $st->fetchAll(PDO::FETCH_COLUMN))));
    } catch (Throwable $e) {
        return [];
    }
}

/** @return string[] */
function mfFetchVerifiedBadgeKeysBatch(PDO $pdo, array $userIds): array
{
    $map = [];
    foreach ($userIds as $id) {
        $map[(int)$id] = [];
    }
    if (!$userIds || !mfTableExists($pdo, 'user_badges')) {
        return $map;
    }
    $ids = array_values(array_unique(array_map('intval', $userIds)));
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    try {
        $st = $pdo->prepare("SELECT user_id, badge_key FROM user_badges WHERE user_id IN ($ph)");
        $st->execute($ids);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $uid = (int)$row['user_id'];
            $map[$uid][] = (string)$row['badge_key'];
        }
    } catch (Throwable $e) {
    }
    return $map;
}

function mfHasAutoProductLaunch(PDO $pdo, int $userId): bool
{
    if (!mfTableExists($pdo, 'projects')) {
        return false;
    }
    try {
        $st = $pdo->prepare("
            SELECT 1
            FROM projects p
            LEFT JOIN project_members pm ON pm.project_id = p.id
            WHERE p.stage IN ('launched', 'completed')
              AND p.website_url IS NOT NULL
              AND TRIM(p.website_url) <> ''
              AND (p.owner_id = ? OR pm.user_id = ?)
            LIMIT 1
        ");
        $st->execute([$userId, $userId]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

/** @return int[] userId => 1 */
function mfAutoProductLaunchBatch(PDO $pdo, array $userIds): array
{
    $set = [];
    if (!$userIds || !mfTableExists($pdo, 'projects')) {
        return $set;
    }
    $ids = array_values(array_unique(array_map('intval', $userIds)));
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    try {
        $st = $pdo->prepare("
            SELECT DISTINCT uid FROM (
                SELECT p.owner_id AS uid
                FROM projects p
                WHERE p.stage IN ('launched', 'completed')
                  AND p.website_url IS NOT NULL AND TRIM(p.website_url) <> ''
                  AND p.owner_id IN ($ph)
                UNION
                SELECT pm.user_id AS uid
                FROM project_members pm
                JOIN projects p ON p.id = pm.project_id
                WHERE p.stage IN ('launched', 'completed')
                  AND p.website_url IS NOT NULL AND TRIM(p.website_url) <> ''
                  AND pm.user_id IN ($ph)
            ) t
        ");
        $st->execute(array_merge($ids, $ids));
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            $set[(int)$uid] = 1;
        }
    } catch (Throwable $e) {
    }
    return $set;
}

function mfHasTeamBuilder(PDO $pdo, int $userId): bool
{
    if (!mfTableExists($pdo, 'projects') || !mfTableExists($pdo, 'project_members')) {
        return false;
    }
    try {
        $st = $pdo->prepare("
            SELECT 1
            FROM project_members pm
            JOIN projects p ON p.id = pm.project_id AND p.stage = 'completed'
            JOIN (
                SELECT project_id, COUNT(*) AS mc
                FROM project_members
                GROUP BY project_id
                HAVING mc >= 5
            ) big ON big.project_id = p.id
            WHERE pm.user_id = ?
            LIMIT 1
        ");
        $st->execute([$userId]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

/** @return int[] */
function mfTeamBuilderBatch(PDO $pdo, array $userIds): array
{
    $set = [];
    if (!$userIds || !mfTableExists($pdo, 'projects') || !mfTableExists($pdo, 'project_members')) {
        return $set;
    }
    $ids = array_values(array_unique(array_map('intval', $userIds)));
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    try {
        $st = $pdo->prepare("
            SELECT DISTINCT pm.user_id
            FROM project_members pm
            JOIN projects p ON p.id = pm.project_id AND p.stage = 'completed'
            JOIN (
                SELECT project_id, COUNT(*) AS mc
                FROM project_members
                GROUP BY project_id
                HAVING mc >= 5
            ) big ON big.project_id = p.id
            WHERE pm.user_id IN ($ph)
        ");
        $st->execute($ids);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            $set[(int)$uid] = 1;
        }
    } catch (Throwable $e) {
    }
    return $set;
}

function mfCompletedProjectsCount(PDO $pdo, int $userId): int
{
    if (!mfTableExists($pdo, 'projects')) {
        return 0;
    }
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE owner_id = ? AND stage = 'completed'");
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function mfHasFirstCourse(PDO $pdo, int $userId): bool
{
    if (!mfTableExists($pdo, 'mf_courses')) {
        return false;
    }
    try {
        $st = $pdo->prepare("SELECT 1 FROM mf_courses WHERE author_id = ? AND status = 'published' LIMIT 1");
        $st->execute([$userId]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function mfHasTopCourse(PDO $pdo, int $userId): bool
{
    if (!mfTableExists($pdo, 'mf_courses')) {
        return false;
    }
    try {
        $st = $pdo->prepare("
            SELECT 1 FROM mf_courses
            WHERE author_id = ? AND status = 'published' AND rating_avg >= 4.5 AND rating_count >= 3
            LIMIT 1
        ");
        $st->execute([$userId]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

/** @return int[] userId => 1 */
function mfFirstCourseBatch(PDO $pdo, array $userIds): array
{
    $set = [];
    if (!$userIds || !mfTableExists($pdo, 'mf_courses')) {
        return $set;
    }
    $ids = array_values(array_unique(array_map('intval', $userIds)));
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    try {
        $st = $pdo->prepare("SELECT DISTINCT author_id FROM mf_courses WHERE status='published' AND author_id IN ($ph)");
        $st->execute($ids);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            $set[(int)$uid] = 1;
        }
    } catch (Throwable $e) {
    }
    return $set;
}

/** @return int[] userId => 1 */
function mfTopCourseBatch(PDO $pdo, array $userIds): array
{
    $set = [];
    if (!$userIds || !mfTableExists($pdo, 'mf_courses')) {
        return $set;
    }
    $ids = array_values(array_unique(array_map('intval', $userIds)));
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    try {
        $st = $pdo->prepare("
            SELECT DISTINCT author_id FROM mf_courses
            WHERE status='published' AND rating_avg >= 4.5 AND rating_count >= 3 AND author_id IN ($ph)
        ");
        $st->execute($ids);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            $set[(int)$uid] = 1;
        }
    } catch (Throwable $e) {
    }
    return $set;
}

/** @return int[] userId => count */
function mfCompletedProjectsBatch(PDO $pdo, array $userIds): array
{
    $map = [];
    foreach ($userIds as $id) {
        $map[(int)$id] = 0;
    }
    if (!$userIds || !mfTableExists($pdo, 'projects')) {
        return $map;
    }
    $ids = array_values(array_unique(array_map('intval', $userIds)));
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    try {
        $st = $pdo->prepare("
            SELECT owner_id, COUNT(*) AS cnt
            FROM projects
            WHERE owner_id IN ($ph) AND stage = 'completed'
            GROUP BY owner_id
        ");
        $st->execute($ids);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int)$row['owner_id']] = (int)$row['cnt'];
        }
    } catch (Throwable $e) {
    }
    return $map;
}

/** Resolve earned badge keys for one user */
function mfResolveEarnedKeys(PDO $pdo, int $userId, ?array $verified = null): array
{
    $defs     = mfBadgeDefinitions();
    $verified = $verified ?? mfFetchVerifiedBadgeKeys($pdo, $userId);
    $earned   = [];

    if (in_array('product_launch', $verified, true) || mfHasAutoProductLaunch($pdo, $userId)) {
        $earned[] = 'product_launch';
    }
    if (mfHasTeamBuilder($pdo, $userId)) {
        $earned[] = 'team_builder';
    }
    if (mfCompletedProjectsCount($pdo, $userId) >= 3) {
        $earned[] = 'serial_founder';
    }
    if (in_array('first_course', $verified, true) || mfHasFirstCourse($pdo, $userId)) {
        $earned[] = 'first_course';
    }
    if (in_array('top_course', $verified, true) || mfHasTopCourse($pdo, $userId)) {
        $earned[] = 'top_course';
    }

    $earned = array_values(array_unique($earned));
    usort($earned, static function ($a, $b) use ($defs) {
        return ($defs[$a]['order'] ?? 99) <=> ($defs[$b]['order'] ?? 99);
    });

    return $earned;
}

function mfBuildBadgePayload(array $earnedKeys): array
{
    $defs   = mfBadgeDefinitions();
    $earned = [];
    $all    = [];

    foreach ($defs as $key => $def) {
        $isEarned = in_array($key, $earnedKeys, true);
        $item     = [
            'key'    => $key,
            'emoji'  => $def['emoji'],
            'title'  => $def['title'],
            'hint'   => $def['hint'],
            'earned' => $isEarned,
        ];
        $all[] = $item;
        if ($isEarned) {
            $earned[] = $item;
        }
    }

    return [
        'earned'          => $earned,
        'all'             => $all,
        'primary_label'   => mfFormatBadgeSubtitle($earned),
        'primary_emoji'   => $earned[0]['emoji'] ?? null,
        'primary_title'   => $earned[0]['title'] ?? null,
    ];
}

function mfComputeUserBadges(PDO $pdo, int $userId): array
{
    ensureUserBadgesSchema($pdo);
    $keys = mfResolveEarnedKeys($pdo, $userId);
    return mfBuildBadgePayload($keys);
}

/** @return array<int, array> */
function mfComputeUserBadgesBatch(PDO $pdo, array $userIds): array
{
    ensureUserBadgesSchema($pdo);
    $defs = mfBadgeDefinitions();
    $ids  = array_values(array_unique(array_map('intval', $userIds)));
    $out  = [];

    if (!$ids) {
        return $out;
    }

    $verifiedMap  = mfFetchVerifiedBadgeKeysBatch($pdo, $ids);
    $launchMap    = mfAutoProductLaunchBatch($pdo, $ids);
    $teamMap      = mfTeamBuilderBatch($pdo, $ids);
    $completedMap = mfCompletedProjectsBatch($pdo, $ids);
    $firstCourseMap = mfFirstCourseBatch($pdo, $ids);
    $topCourseMap   = mfTopCourseBatch($pdo, $ids);

    foreach ($ids as $uid) {
        $earned = [];
        $ver    = $verifiedMap[$uid] ?? [];

        if (in_array('product_launch', $ver, true) || isset($launchMap[$uid])) {
            $earned[] = 'product_launch';
        }
        if (isset($teamMap[$uid])) {
            $earned[] = 'team_builder';
        }
        if (($completedMap[$uid] ?? 0) >= 3) {
            $earned[] = 'serial_founder';
        }
        if (in_array('first_course', $ver, true) || isset($firstCourseMap[$uid])) {
            $earned[] = 'first_course';
        }
        if (in_array('top_course', $ver, true) || isset($topCourseMap[$uid])) {
            $earned[] = 'top_course';
        }

        $earned = array_values(array_unique($earned));
        usort($earned, static function ($a, $b) use ($defs) {
            return ($defs[$a]['order'] ?? 99) <=> ($defs[$b]['order'] ?? 99);
        });

        $out[$uid] = mfBuildBadgePayload($earned);
    }

    return $out;
}

function mfFormatBadgeSubtitle(array $earnedBadges, int $limit = 2): string
{
    if (!$earnedBadges) {
        return '';
    }
    $parts = [];
    foreach (array_slice($earnedBadges, 0, $limit) as $b) {
        $emoji = $b['emoji'] ?? '';
        $title = $b['title'] ?? '';
        $parts[] = trim("$emoji $title");
    }
    return implode(' · ', array_filter($parts));
}
