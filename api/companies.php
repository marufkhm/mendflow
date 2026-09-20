<?php
/**
 * companies.php — публичные страницы компаний Mendflow
 * GET  ?action=detail|posts|jobs|projects|team|list|my&id=
 * POST { action: follow|update|create_post }
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

function coJson($data, $code = 200): void
{
    http_response_code($code);
    echo mfJsonEncode($data);
    exit;
}

function coFixUtf8(?string $value): ?string
{
    if ($value === null || $value === '') {
        return $value;
    }
    if (mb_check_encoding($value, 'UTF-8')) {
        return $value;
    }
    $encodings = ['UTF-8', 'CP1251', 'ISO-8859-1', 'Windows-1251'];
    foreach ($encodings as $from) {
        $converted = @mb_convert_encoding($value, 'UTF-8', $from);
        if ($converted && mb_check_encoding($converted, 'UTF-8') && !str_contains($converted, '�')) {
            return $converted;
        }
    }
    return $value;
}

function coCleanDisplay(?string $value): ?string
{
    $value = coFixUtf8($value);
    if ($value === null || $value === '') {
        return null;
    }
    $value = trim($value);
    if ($value === '' || preg_match('/^\?+$/u', $value)) {
        return null;
    }
    return $value;
}

function coUtf8Fields(array $row): array
{
    foreach (['company_name', 'tagline', 'industry', 'city', 'country_name', 'description'] as $field) {
        if (array_key_exists($field, $row)) {
            $row[$field] = coCleanDisplay($row[$field]);
        }
    }
    return $row;
}

function coTableExists(PDO $pdo, string $table): bool
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

function coColumns(PDO $pdo, string $table): array
{
    try {
        $st = $pdo->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION"
        );
        $st->execute([$table]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return [];
    }
}

function ensureCompanySchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    if (!coTableExists($pdo, 'company_profiles')) {
        $done = true;
        return;
    }

    $extra = [
        'tagline'      => "ALTER TABLE company_profiles ADD COLUMN tagline VARCHAR(255) NULL",
        'logo'         => "ALTER TABLE company_profiles ADD COLUMN logo VARCHAR(255) NULL",
        'cover'        => "ALTER TABLE company_profiles ADD COLUMN cover VARCHAR(255) NULL",
        'team_size'    => "ALTER TABLE company_profiles ADD COLUMN team_size INT NULL",
        'founded_year' => "ALTER TABLE company_profiles ADD COLUMN founded_year INT NULL",
    ];
    $existing = coColumns($pdo, 'company_profiles');
    foreach ($extra as $col => $sql) {
        if (!in_array($col, $existing, true)) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
            }
        }
    }

    if (!coTableExists($pdo, 'company_follows')) {
        try {
            $pdo->exec("CREATE TABLE company_follows (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_co_follow (company_id, user_id),
                KEY idx_co_follows_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }

    if (!coTableExists($pdo, 'company_posts')) {
        try {
            $pdo->exec("CREATE TABLE company_posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_id INT NOT NULL,
                user_id INT NOT NULL,
                text TEXT NOT NULL,
                attachment_url VARCHAR(512) NULL,
                attachment_kind VARCHAR(16) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_co_posts_company (company_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }

    if (!coTableExists($pdo, 'company_likes')) {
        try {
            $pdo->exec("CREATE TABLE company_likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_co_like (post_id, user_id),
                KEY idx_co_likes_post (post_id),
                KEY idx_co_likes_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }

    if (!coTableExists($pdo, 'company_comments')) {
        try {
            $pdo->exec("CREATE TABLE company_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                text TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_co_comments_post (post_id),
                KEY idx_co_comments_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }

    if (!coTableExists($pdo, 'company_reposts')) {
        try {
            $pdo->exec("CREATE TABLE company_reposts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_co_repost (post_id, user_id),
                KEY idx_co_reposts_post (post_id),
                KEY idx_co_reposts_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
        }
    }

    $postCols = coColumns($pdo, 'company_posts');
    $postExtra = [
        'attachment_url'  => 'ALTER TABLE company_posts ADD COLUMN attachment_url VARCHAR(512) NULL',
        'attachment_kind' => 'ALTER TABLE company_posts ADD COLUMN attachment_kind VARCHAR(16) NULL',
    ];
    foreach ($postExtra as $col => $sql) {
        if (coTableExists($pdo, 'company_posts') && !in_array($col, $postCols, true)) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
            }
        }
    }

    $userCols = coColumns($pdo, 'users');
    if (!in_array('employer_company_id', $userCols, true)) {
        try {
            $pdo->exec('ALTER TABLE users ADD COLUMN employer_company_id INT NULL');
        } catch (Throwable $e) {
        }
    }

    $done = true;

    try {
        $pdo->exec('ALTER TABLE company_profiles CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    } catch (Throwable $e) {
    }

    $charsetCols = [
        'company_name' => 'VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL',
        'tagline'      => 'VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL',
        'industry'     => 'VARCHAR(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL',
        'city'         => 'VARCHAR(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL',
        'country_name' => 'VARCHAR(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL',
        'description'  => 'TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL',
    ];
    $existing = coColumns($pdo, 'company_profiles');
    foreach ($charsetCols as $col => $def) {
        if (in_array($col, $existing, true)) {
            try {
                $pdo->exec("ALTER TABLE company_profiles MODIFY {$col} {$def}");
            } catch (Throwable $e) {
            }
        }
    }
}

function coFetchCompany(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT * FROM company_profiles WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? coUtf8Fields($row) : null;
}

function coFollowersCount(PDO $pdo, int $companyId): int
{
    if (!coTableExists($pdo, 'company_follows')) {
        return 0;
    }
    $st = $pdo->prepare('SELECT COUNT(*) FROM company_follows WHERE company_id = ?');
    $st->execute([$companyId]);
    return (int)$st->fetchColumn();
}

function coIsFollowing(PDO $pdo, int $companyId, ?int $viewerId): bool
{
    if (!$viewerId || !coTableExists($pdo, 'company_follows')) {
        return false;
    }
    $st = $pdo->prepare('SELECT 1 FROM company_follows WHERE company_id = ? AND user_id = ? LIMIT 1');
    $st->execute([$companyId, $viewerId]);
    return (bool)$st->fetchColumn();
}

function coTeamCount(PDO $pdo, array $company): int
{
    $cols = coColumns($pdo, 'users');
    $hasEmp = in_array('employer_company_id', $cols, true);
    $hasOrg = in_array('organization', $cols, true);
    if (!$hasEmp && !$hasOrg) {
        return (int)($company['team_size'] ?? 0);
    }

    $sql = "SELECT COUNT(*) FROM users u WHERE u.role NOT IN ('company','university') AND (";
    $params = [];
    $parts = [];
    if ($hasEmp) {
        $parts[] = 'u.employer_company_id = ?';
        $params[] = (int)$company['id'];
    }
    if ($hasOrg) {
        $parts[] = '(u.organization = ? AND (u.employer_company_id IS NULL OR u.employer_company_id = 0))';
        $params[] = $company['company_name'];
    }
    $sql .= implode(' OR ', $parts) . ')';
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) {
        return (int)($company['team_size'] ?? 0);
    }
}

function coFriendsAtCompany(PDO $pdo, int $companyId, string $companyName, ?int $viewerId): array
{
    if (!$viewerId || !coTableExists($pdo, 'friendships')) {
        return ['count' => 0, 'friends' => []];
    }

    $cols = coColumns($pdo, 'users');
    $hasEmp = in_array('employer_company_id', $cols, true);
    $hasOrg = in_array('organization', $cols, true);

    $teamCond = [];
    $teamParams = [];
    if ($hasEmp) {
        $teamCond[] = 'u.employer_company_id = ?';
        $teamParams[] = $companyId;
    }
    if ($hasOrg) {
        $teamCond[] = 'u.organization = ?';
        $teamParams[] = $companyName;
    }
    if (!$teamCond) {
        return ['count' => 0, 'friends' => []];
    }

    $sql = "
        SELECT u.id, u.first_name, u.last_name, u.avatar, u.specialty
        FROM friendships f
        JOIN users u ON (
            (f.sender_id = ? AND f.receiver_id = u.id) OR
            (f.receiver_id = ? AND f.sender_id = u.id)
        )
        WHERE f.status = 'accepted'
          AND u.id != ?
          AND u.role NOT IN ('company','university')
          AND (" . implode(' OR ', $teamCond) . ")
        ORDER BY u.first_name, u.last_name
        LIMIT 12
    ";
    $params = array_merge([$viewerId, $viewerId, $viewerId], $teamParams);
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        return [
            'count'   => count($rows),
            'friends' => array_map(static function ($r) {
                return [
                    'id'         => (int)$r['id'],
                    'first_name' => $r['first_name'],
                    'last_name'  => $r['last_name'],
                    'avatar'     => $r['avatar'],
                    'specialty'  => $r['specialty'] ?? null,
                    'name'       => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
                ];
            }, $rows),
        ];
    } catch (Throwable $e) {
        return ['count' => 0, 'friends' => []];
    }
}

function coActivity(PDO $pdo, array $company): array
{
    $out = [];
    $companyId = (int)$company['id'];
    $ownerId   = (int)$company['user_id'];

    if (coTableExists($pdo, 'job_vacancies')) {
        try {
            $st = $pdo->prepare("
                SELECT COUNT(*) FROM job_vacancies
                WHERE company_id = ? AND status = 'open'
                  AND published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $st->execute([$companyId]);
            $n = (int)$st->fetchColumn();
            if ($n > 0) {
                $out[] = [
                    'type'  => 'vacancies',
                    'text'  => $n === 1 ? '1 новая вакансия на этой неделе' : "{$n} новых вакансии на этой неделе",
                    'count' => $n,
                ];
            }
        } catch (Throwable $e) {
        }
    }

    if (coTableExists($pdo, 'projects')) {
        try {
            $st = $pdo->prepare("
                SELECT title FROM projects
                WHERE owner_id = ? AND is_public = 1
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY created_at DESC LIMIT 1
            ");
            $st->execute([$ownerId]);
            $title = $st->fetchColumn();
            if ($title) {
                $out[] = [
                    'type'  => 'project',
                    'text'  => 'Запустили проект «' . mb_substr((string)$title, 0, 50) . '»',
                    'count' => 1,
                ];
            }
        } catch (Throwable $e) {
        }
    }

    return $out;
}

function coShapeJob(array $r): array
{
    return [
        'id'                 => (int)$r['id'],
        'title'              => $r['title'],
        'description'        => $r['description'] ?? '',
        'work_format'        => $r['work_format'] ?? null,
        'schedule'           => $r['schedule'] ?? null,
        'specialization'     => $r['specialization'] ?? null,
        'vacancy_type'       => $r['vacancy_type'] ?? null,
        'city'               => $r['city'] ?? null,
        'status'             => $r['status'] ?? 'open',
        'applications_count' => (int)($r['applications_count'] ?? 0),
        'time_ago'           => timeAgo($r['published_at'] ?? $r['created_at']),
    ];
}

function coFetchJobs(PDO $pdo, int $companyId, array $filters = [], int $limit = 50): array
{
    if (!coTableExists($pdo, 'job_vacancies')) {
        return [];
    }
    $sql = "SELECT * FROM job_vacancies WHERE company_id = ? AND status = 'open'";
    $params = [$companyId];
    if (!empty($filters['role'])) {
        $sql .= ' AND (specialization = ? OR title LIKE ?)';
        $params[] = $filters['role'];
        $params[] = '%' . $filters['role'] . '%';
    }
    if (!empty($filters['work_format'])) {
        $sql .= ' AND work_format = ?';
        $params[] = $filters['work_format'];
    }
    $sql .= ' ORDER BY published_at DESC, created_at DESC LIMIT ' . (int)$limit;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return array_map('coShapeJob', $st->fetchAll(PDO::FETCH_ASSOC));
}

function coProjectProgress(PDO $pdo, int $projectId): int
{
    if (!coTableExists($pdo, 'project_tasks')) {
        return 0;
    }
    try {
        $st = $pdo->prepare("
            SELECT ROUND(100 * SUM(status = 'done') / NULLIF(COUNT(*), 0))
            FROM project_tasks WHERE project_id = ?
        ");
        $st->execute([$projectId]);
        return (int)($st->fetchColumn() ?: 0);
    } catch (Throwable $e) {
        return 0;
    }
}

function coFetchProjects(PDO $pdo, int $ownerId, int $limit = 50): array
{
    if (!coTableExists($pdo, 'projects')) {
        return [];
    }
    $st = $pdo->prepare("
        SELECT p.id, p.title, p.description, p.cover_url, p.category, p.stage, p.tags, p.created_at,
               (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) AS member_count
        FROM projects p
        WHERE p.owner_id = ? AND p.is_public = 1
        ORDER BY p.updated_at DESC, p.created_at DESC
        LIMIT ?
    ");
    $st->execute([$ownerId, $limit]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'            => (int)$r['id'],
            'title'         => $r['title'],
            'description'   => $r['description'] ?? '',
            'cover_url'     => $r['cover_url'],
            'category'      => $r['category'],
            'stage'         => $r['stage'],
            'tags'          => $r['tags'] ? explode(',', $r['tags']) : [],
            'member_count'  => (int)$r['member_count'],
            'progress'      => coProjectProgress($pdo, (int)$r['id']),
            'time_ago'      => timeAgo($r['created_at']),
        ];
    }
    return $out;
}

function coFetchPosts(PDO $pdo, int $companyId, int $limit = 50, ?int $viewerId = null): array
{
    if (!coTableExists($pdo, 'company_posts')) {
        return [];
    }
    $viewerSql = $viewerId ? (int)$viewerId : 0;
    $userLikedSub = $viewerSql
        ? "(SELECT COUNT(*) FROM company_likes cl WHERE cl.post_id = p.id AND cl.user_id = {$viewerSql})"
        : '0';
    $userRepostedSub = $viewerSql
        ? "(SELECT COUNT(*) FROM company_reposts cr WHERE cr.post_id = p.id AND cr.user_id = {$viewerSql})"
        : '0';
    $st = $pdo->prepare("
        SELECT p.id, p.text, p.attachment_url, p.attachment_kind, p.created_at, p.company_id,
               u.first_name, u.last_name, u.avatar,
               c.company_name, c.logo AS company_logo,
               (SELECT COUNT(*) FROM company_likes cl WHERE cl.post_id = p.id) AS likes_count,
               (SELECT COUNT(*) FROM company_comments cc WHERE cc.post_id = p.id) AS comments_count,
               (SELECT COUNT(*) FROM company_reposts cr WHERE cr.post_id = p.id) AS reposts_count,
               {$userLikedSub} AS user_liked,
               {$userRepostedSub} AS user_reposted
        FROM company_posts p
        JOIN users u ON u.id = p.user_id
        JOIN company_profiles c ON c.id = p.company_id
        WHERE p.company_id = ?
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $st->execute([$companyId, $limit]);
    return array_map(static function ($r) {
        return [
            'id'               => (int)$r['id'],
            'text'             => $r['text'],
            'attachment_url'   => $r['attachment_url'] ?: null,
            'attachment_kind'  => $r['attachment_kind'] ?: null,
            'company_id'       => (int)$r['company_id'],
            'entity_id'        => (int)$r['company_id'],
            'author_type'      => 'company',
            'author_name'      => coCleanDisplay($r['company_name']) ?: 'Компания',
            'author_logo'      => $r['company_logo'] ?: null,
            'avatar'           => $r['company_logo'] ?: null,
            'likes_count'      => (int)($r['likes_count'] ?? 0),
            'comments_count'   => (int)($r['comments_count'] ?? 0),
            'reposts_count'    => (int)($r['reposts_count'] ?? 0),
            'user_liked'       => !empty($r['user_liked']),
            'user_reposted'    => !empty($r['user_reposted']),
            'created_at'       => utcDate($r['created_at']),
            'time_ago'         => timeAgo($r['created_at']),
        ];
    }, $st->fetchAll(PDO::FETCH_ASSOC));
}

function coFetchTeam(PDO $pdo, array $company, int $limit = 100): array
{
    $cols = coColumns($pdo, 'users');
    $hasEmp = in_array('employer_company_id', $cols, true);
    $hasOrg = in_array('organization', $cols, true);
    if (!$hasEmp && !$hasOrg) {
        return [];
    }

    $extra = '';
    if (in_array('specialty', $cols, true)) {
        $extra .= ', u.specialty';
    }
    if (in_array('city', $cols, true)) {
        $extra .= ', u.city';
    }

    $parts = [];
    $params = [];
    if ($hasEmp) {
        $parts[] = 'u.employer_company_id = ?';
        $params[] = (int)$company['id'];
    }
    if ($hasOrg) {
        $parts[] = 'u.organization = ?';
        $params[] = $company['company_name'];
    }

    $sql = "
        SELECT u.id, u.first_name, u.last_name, u.avatar{$extra}
        FROM users u
        WHERE u.role NOT IN ('company','university')
          AND (" . implode(' OR ', $parts) . ")
        ORDER BY u.first_name, u.last_name
        LIMIT ?
    ";
    $params[] = $limit;
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return array_map(static function ($r) {
            return [
                'id'         => (int)$r['id'],
                'first_name' => $r['first_name'],
                'last_name'  => $r['last_name'],
                'avatar'     => $r['avatar'],
                'specialty'  => $r['specialty'] ?? null,
                'city'       => $r['city'] ?? null,
                'name'       => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
            ];
        }, $st->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        return [];
    }
}

function coNormalize(PDO $pdo, array $row, ?int $viewerId, bool $withPreview = false): array
{
    $id = (int)$row['id'];
    $friends = coFriendsAtCompany($pdo, $id, $row['company_name'], $viewerId);

    $jobsCount = 0;
    if (coTableExists($pdo, 'job_vacancies')) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM job_vacancies WHERE company_id = ? AND status = 'open'");
        $st->execute([$id]);
        $jobsCount = (int)$st->fetchColumn();
    }

    $postsCount = 0;
    if (coTableExists($pdo, 'company_posts')) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM company_posts WHERE company_id = ?');
        $st->execute([$id]);
        $postsCount = (int)$st->fetchColumn();
    }

    $projectsCount = 0;
    if (coTableExists($pdo, 'projects')) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM projects WHERE owner_id = ? AND is_public = 1');
        $st->execute([(int)$row['user_id']]);
        $projectsCount = (int)$st->fetchColumn();
    }

    $company = [
        'id'                 => $id,
        'user_id'            => (int)$row['user_id'],
        'company_name'       => $row['company_name'],
        'tagline'            => $row['tagline'] ?? null,
        'industry'           => $row['industry'] ?? null,
        'team_size'          => isset($row['team_size']) ? (int)$row['team_size'] : null,
        'city'               => $row['city'] ?? null,
        'country_name'       => $row['country_name'] ?? null,
        'website'            => $row['website'] ?? null,
        'founded_year'       => isset($row['founded_year']) ? (int)$row['founded_year'] : null,
        'description'        => $row['description'] ?? '',
        'logo'               => $row['logo'] ?? null,
        'cover'              => $row['cover'] ?? null,
        'verified'           => !empty($row['verified']),
        'followers'          => coFollowersCount($pdo, $id),
        'is_following'       => coIsFollowing($pdo, $id, $viewerId),
        'is_owner'           => $viewerId && (int)$row['user_id'] === $viewerId,
        'jobs_count'         => $jobsCount,
        'posts_count'        => $postsCount,
        'projects_count'     => $projectsCount,
        'team_count'         => coTeamCount($pdo, $row),
        'activity'           => coActivity($pdo, $row),
        'friends_here'       => $friends['friends'],
        'friends_here_count' => $friends['count'],
    ];

    if ($withPreview) {
        $company['preview'] = [
            'posts'    => coFetchPosts($pdo, $id, 3, $viewerId),
            'jobs'     => coFetchJobs($pdo, $id, [], 3),
            'projects' => coFetchProjects($pdo, (int)$row['user_id'], 3),
        ];
    }

    return $company;
}

try {
    ensureCompanySchema($pdo);
    $viewerId = verifyTokenSoft();
    $method   = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'detail';
        $id     = (int)($_GET['id'] ?? 0);

        if ($action === 'list') {
            $q = trim($_GET['q'] ?? '');
            $limit = min(max((int)($_GET['limit'] ?? 10), 1), 20);
            $sql = 'SELECT id, company_name, logo, city, industry, verified FROM company_profiles';
            $params = [];
            if ($q !== '') {
                $sql .= ' WHERE company_name LIKE ? OR industry LIKE ? OR city LIKE ?';
                $like = '%' . $q . '%';
                $params = [$like, $like, $like];
            }
            $sql .= ' ORDER BY verified DESC, company_name ASC LIMIT ?';
            $params[] = $limit;
            $st = $pdo->prepare($sql);
            $st->execute($params);
            coJson(['companies' => $st->fetchAll(PDO::FETCH_ASSOC)]);
        }

        if ($action === 'my') {
            if (!$viewerId) {
                coJson(['error' => 'Требуется авторизация'], 401);
            }
            $st = $pdo->prepare('SELECT id FROM company_profiles WHERE user_id = ? LIMIT 1');
            $st->execute([$viewerId]);
            $myId = (int)$st->fetchColumn();
            if (!$myId) {
                coJson(['error' => 'Профиль компании не найден'], 404);
            }
            $row = coFetchCompany($pdo, $myId);
            coJson(['company' => coNormalize($pdo, $row, $viewerId, true)]);
        }

        if (!$id) {
            coJson(['error' => 'ID компании обязателен'], 400);
        }

        $row = coFetchCompany($pdo, $id);
        if (!$row) {
            coJson(['error' => 'Компания не найдена'], 404);
        }

        if ($action === 'detail') {
            coJson(['company' => coNormalize($pdo, $row, $viewerId, true)]);
        }

        if ($action === 'posts') {
            coJson(['posts' => coFetchPosts($pdo, $id, 50, $viewerId ?: null)]);
        }

        if ($action === 'jobs') {
            coJson(['jobs' => coFetchJobs($pdo, $id, [
                'role'         => trim($_GET['role'] ?? ''),
                'work_format'  => trim($_GET['work_format'] ?? ''),
            ])]);
        }

        if ($action === 'projects') {
            coJson(['projects' => coFetchProjects($pdo, (int)$row['user_id'])]);
        }

        if ($action === 'team') {
            coJson(['team' => coFetchTeam($pdo, $row)]);
        }

        coJson(['error' => 'Unknown action'], 400);
    }

    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? '';

        if ($action === 'follow') {
            $companyId = (int)($data['company_id'] ?? $data['id'] ?? 0);
            $row = coFetchCompany($pdo, $companyId);
            if (!$row) {
                coJson(['error' => 'Компания не найдена'], 404);
            }
            if (!coTableExists($pdo, 'company_follows')) {
                coJson(['error' => 'Подписки недоступны'], 500);
            }
            $chk = $pdo->prepare('SELECT id FROM company_follows WHERE company_id = ? AND user_id = ?');
            $chk->execute([$companyId, $userId]);
            if ($chk->fetch()) {
                $pdo->prepare('DELETE FROM company_follows WHERE company_id = ? AND user_id = ?')
                    ->execute([$companyId, $userId]);
                $following = false;
            } else {
                $pdo->prepare('INSERT INTO company_follows (company_id, user_id) VALUES (?, ?)')
                    ->execute([$companyId, $userId]);
                $following = true;
            }
            coJson([
                'success'   => true,
                'following' => $following,
                'followers' => coFollowersCount($pdo, $companyId),
            ]);
        }

        if ($action === 'update') {
            $st = $pdo->prepare('SELECT * FROM company_profiles WHERE user_id = ? LIMIT 1');
            $st->execute([$userId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                coJson(['error' => 'Профиль компании не найден'], 404);
            }

            $fields = [
                'company_name', 'tagline', 'industry', 'website', 'city', 'country_name',
                'description', 'logo', 'cover', 'team_size', 'founded_year',
            ];
            $sets = [];
            $params = [];
            foreach ($fields as $f) {
                if (!array_key_exists($f, $data)) {
                    continue;
                }
                $val = $data[$f];
                if (in_array($f, ['team_size', 'founded_year'], true)) {
                    $val = ($val === '' || $val === null) ? null : (int)$val;
                } else {
                    $val = is_string($val) ? trim($val) : $val;
                    if ($val === '') {
                        $val = null;
                    }
                }
                $sets[] = "{$f} = ?";
                $params[] = $val;
            }
            if (!$sets) {
                coJson(['error' => 'Нет данных для обновления'], 400);
            }
            $params[] = (int)$row['id'];
            $pdo->prepare('UPDATE company_profiles SET ' . implode(', ', $sets) . ' WHERE id = ?')
                ->execute($params);
            $updated = coFetchCompany($pdo, (int)$row['id']);
            coJson(['success' => true, 'company' => coNormalize($pdo, $updated, $userId, true)]);
        }

        if ($action === 'create_post') {
            $st = $pdo->prepare('SELECT id FROM company_profiles WHERE user_id = ? LIMIT 1');
            $st->execute([$userId]);
            $companyId = (int)$st->fetchColumn();
            if (!$companyId) {
                coJson(['error' => 'Только для аккаунтов компаний'], 403);
            }
            $text = trim($data['text'] ?? '');
            $attachmentUrl = trim($data['attachment_url'] ?? '');
            $attachmentKind = in_array($data['attachment_kind'] ?? '', ['image', 'video'], true)
                ? $data['attachment_kind']
                : null;
            if (!$text && !$attachmentUrl) {
                coJson(['error' => 'Добавьте текст, фото или видео'], 400);
            }
            if (!coTableExists($pdo, 'company_posts')) {
                coJson(['error' => 'Посты недоступны'], 500);
            }
            $pdo->prepare('INSERT INTO company_posts (company_id, user_id, text, attachment_url, attachment_kind) VALUES (?,?,?,?,?)')
                ->execute([$companyId, $userId, $text, $attachmentUrl ?: null, $attachmentKind]);
            $postId = (int)$pdo->lastInsertId();
            $posts = coFetchPosts($pdo, $companyId, 1);
            $created = $posts[0] ?? null;
            coJson(['success' => true, 'post_id' => $postId, 'post' => $created]);
        }

        coJson(['error' => 'Unknown action'], 400);
    }

    coJson(['error' => 'Method not allowed'], 405);
} catch (PDOException $e) {
    coJson(['error' => 'DB: ' . $e->getMessage()], 500);
} catch (Throwable $e) {
    coJson(['error' => $e->getMessage()], 500);
}
