<?php
/**
 * jobs.php — Карьера: вакансии компаний, отклики студентов, статистика вузов
 * GET  ?action=list|detail|my_vacancies|my_applications|company_applications|university_stats|partners
 * POST { action: create|update|close|apply|handle_application|add_partner|remove_partner }
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

const JOB_SPECS = [
    'product_manager', 'frontend', 'backend', 'designer', 'ml_engineer',
    'fullstack', 'analyst', 'qa', 'marketing', 'other',
];

try {
    ensureJobsSchema($pdo);
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    if ($method === 'GET') {
        $userId = verifyTokenSoft();
        if ($action === 'list' || $action === '') {
            echo json_encode(['vacancies' => jobsListVacancies($pdo, $userId, $_GET)], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'detail') {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'id required']);
                exit;
            }
            echo json_encode(['vacancy' => jobsFetchVacancy($pdo, $id, $userId)], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'my_vacancies') {
            $uid = verifyToken();
            echo json_encode(['vacancies' => jobsMyVacancies($pdo, $uid)], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'my_applications') {
            $uid = verifyToken();
            echo json_encode(['applications' => jobsMyApplications($pdo, $uid)], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'company_applications') {
            $uid = verifyToken();
            $vacancyId = (int)($_GET['vacancy_id'] ?? 0);
            $scope = in_array($_GET['scope'] ?? 'active', ['active', 'all', 'archive'], true)
                ? ($_GET['scope'] ?? 'active')
                : 'active';
            echo json_encode(['applications' => jobsCompanyApplications($pdo, $uid, $vacancyId, $scope)], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'university_stats') {
            $uid = verifyToken();
            echo json_encode(jobsUniversityStats($pdo, $uid), JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'partners') {
            $uid = verifyToken();
            echo json_encode(['partners' => jobsListPartners($pdo, $uid)], JSON_UNESCAPED_UNICODE);
            exit;
        }
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
        exit;
    }

    if ($method === 'POST') {
        $userId = verifyToken();
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $data['action'] ?? '';

        if ($action === 'create') {
            $company = jobsRequireCompany($pdo, $userId);
            $title = trim($data['title'] ?? '');
            if (!$title) {
                http_response_code(400);
                echo json_encode(['error' => 'title обязателен']);
                exit;
            }
            $status = !empty($company['verified']) ? 'open' : 'draft';
            if (!empty($data['publish']) && empty($company['verified'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Компания не верифицирована — вакансия сохранена как черновик']);
                exit;
            }
            if (!empty($data['publish']) && !empty($company['verified'])) {
                $status = 'open';
            }

            $pdo->prepare("
                INSERT INTO job_vacancies (
                    company_id, created_by, title, description, requirements,
                    work_format, schedule, education, specialization,
                    target_skills, target_university_ids,
                    contact_email, contact_name, city, country_name,
                    vacancy_type, status, deadline, published_at
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                (int)$company['id'],
                $userId,
                $title,
                trim($data['description'] ?? ''),
                trim($data['requirements'] ?? ''),
                jobsNormWorkFormat($data['work_format'] ?? 'hybrid'),
                jobsNormSchedule($data['schedule'] ?? 'full-time'),
                jobsNormEducation($data['education'] ?? 'any'),
                jobsNormSpec($data['specialization'] ?? null),
                jobsNormSkills($data['target_skills'] ?? null),
                jobsNormUniIds($data['target_university_ids'] ?? null),
                trim($data['contact_email'] ?? '') ?: null,
                trim($data['contact_name'] ?? '') ?: null,
                trim($data['city'] ?? '') ?: null,
                trim($data['country_name'] ?? '') ?: null,
                jobsNormType($data['vacancy_type'] ?? 'internship'),
                $status,
                !empty($data['deadline']) ? $data['deadline'] : null,
                $status === 'open' ? date('Y-m-d H:i:s') : null,
            ]);
            $id = (int)$pdo->lastInsertId();
            echo json_encode(['success' => true, 'vacancy' => jobsFetchVacancy($pdo, $id, $userId)]);
            exit;
        }

        if ($action === 'update') {
            $company = jobsRequireCompany($pdo, $userId);
            $id = (int)($data['id'] ?? 0);
            $v = jobsFetchVacancyRow($pdo, $id);
            if ((int)$v['company_id'] !== (int)$company['id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Нет доступа']);
                exit;
            }
            $status = $v['status'];
            if (!empty($data['publish']) && !empty($company['verified'])) {
                $status = 'open';
            } elseif (!empty($data['publish']) && empty($company['verified'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Компания не верифицирована']);
                exit;
            }
            if (($data['status'] ?? '') === 'closed') {
                $status = 'closed';
            }

            $pdo->prepare("
                UPDATE job_vacancies SET
                    title=?, description=?, requirements=?,
                    work_format=?, schedule=?, education=?, specialization=?,
                    target_skills=?, target_university_ids=?,
                    contact_email=?, contact_name=?, city=?, country_name=?,
                    vacancy_type=?, status=?, deadline=?,
                    published_at = IF(?='open' AND published_at IS NULL, NOW(), published_at)
                WHERE id=?
            ")->execute([
                trim($data['title'] ?? $v['title']),
                trim($data['description'] ?? $v['description'] ?? ''),
                trim($data['requirements'] ?? $v['requirements'] ?? ''),
                jobsNormWorkFormat($data['work_format'] ?? $v['work_format']),
                jobsNormSchedule($data['schedule'] ?? $v['schedule']),
                jobsNormEducation($data['education'] ?? $v['education']),
                jobsNormSpec($data['specialization'] ?? $v['specialization']),
                jobsNormSkills($data['target_skills'] ?? $v['target_skills']),
                jobsNormUniIds($data['target_university_ids'] ?? $v['target_university_ids']),
                trim($data['contact_email'] ?? $v['contact_email'] ?? '') ?: null,
                trim($data['contact_name'] ?? $v['contact_name'] ?? '') ?: null,
                trim($data['city'] ?? $v['city'] ?? '') ?: null,
                trim($data['country_name'] ?? $v['country_name'] ?? '') ?: null,
                jobsNormType($data['vacancy_type'] ?? $v['vacancy_type']),
                $status,
                !empty($data['deadline']) ? $data['deadline'] : ($v['deadline'] ?: null),
                $status,
                $id,
            ]);
            echo json_encode(['success' => true, 'vacancy' => jobsFetchVacancy($pdo, $id, $userId)]);
            exit;
        }

        if ($action === 'close') {
            $company = jobsRequireCompany($pdo, $userId);
            $id = (int)($data['id'] ?? 0);
            $v = jobsFetchVacancyRow($pdo, $id);
            if ((int)$v['company_id'] !== (int)$company['id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Нет доступа']);
                exit;
            }
            $pdo->prepare("UPDATE job_vacancies SET status='closed' WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'apply') {
            jobsRequireStudent($pdo, $userId);
            $vacancyId = (int)($data['id'] ?? 0);
            if (!$vacancyId) {
                http_response_code(400);
                echo json_encode(['error' => 'id required']);
                exit;
            }
            $v = jobsFetchVacancyRow($pdo, $vacancyId);
            if ($v['status'] !== 'open') {
                http_response_code(400);
                echo json_encode(['error' => 'Вакансия закрыта']);
                exit;
            }
            if (!jobsStudentCanSee($pdo, $userId, $v)) {
                http_response_code(403);
                echo json_encode(['error' => 'Вакансия недоступна для вашего профиля']);
                exit;
            }

            $chk = $pdo->prepare('SELECT id FROM job_applications WHERE vacancy_id=? AND user_id=?');
            $chk->execute([$vacancyId, $userId]);
            if ($chk->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Вы уже откликнулись']);
                exit;
            }

            $resume = jobsBuildResumeSnapshot($pdo, $userId);
            $uniId  = jobsUserUniversityId($pdo, $userId);

            $pdo->prepare("
                INSERT INTO job_applications (vacancy_id, company_id, user_id, university_id, message, resume_snapshot, status)
                VALUES (?,?,?,?,?,?,'pending')
            ")->execute([
                $vacancyId,
                (int)$v['company_id'],
                $userId,
                $uniId,
                trim($data['message'] ?? ''),
                json_encode($resume, JSON_UNESCAPED_UNICODE),
            ]);
            $pdo->prepare('UPDATE job_vacancies SET applications_count = applications_count + 1 WHERE id=?')
                ->execute([$vacancyId]);

            jobsNotifyCompany($pdo, (int)$v['company_id'], $userId, $vacancyId, $v['title']);

            echo json_encode(['success' => true, 'application_id' => (int)$pdo->lastInsertId()]);
            exit;
        }

        if ($action === 'handle_application') {
            $company = jobsRequireCompany($pdo, $userId);
            $appId = (int)($data['id'] ?? 0);
            $status = trim($data['status'] ?? '');
            if (!$appId || !in_array($status, ['reviewing', 'offer', 'accepted', 'rejected'], true)) {
                http_response_code(400);
                echo json_encode(['error' => 'id и status обязательны']);
                exit;
            }
            $app = jobsFetchApplication($pdo, $appId);
            if ((int)$app['company_id'] !== (int)$company['id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Нет доступа']);
                exit;
            }
            $wasOffer = in_array($app['status'], ['offer', 'accepted'], true);
            $pdo->prepare('UPDATE job_applications SET status=? WHERE id=?')->execute([$status, $appId]);
            if (in_array($status, ['offer', 'accepted'], true) && !$wasOffer) {
                $pdo->prepare('UPDATE job_vacancies SET offers_count = offers_count + 1 WHERE id=?')
                    ->execute([(int)$app['vacancy_id']]);
            }
            $addedToTeam = false;
            if ($status === 'accepted') {
                $addedToTeam = jobsAddApplicantToTeam($pdo, $company, (int)$app['user_id']);
            }
            jobsNotifyApplicant($pdo, (int)$app['user_id'], $userId, $appId, $status, $app['vacancy_title']);
            echo json_encode([
                'success'       => true,
                'status'        => $status,
                'added_to_team' => $addedToTeam,
            ]);
            exit;
        }

        if ($action === 'add_partner') {
            $uni = jobsRequireUniversity($pdo, $userId);
            $companyId = (int)($data['company_id'] ?? 0);
            if (!$companyId) {
                http_response_code(400);
                echo json_encode(['error' => 'company_id required']);
                exit;
            }
            $pdo->prepare("
                INSERT IGNORE INTO job_university_partners (university_id, company_id, status)
                VALUES (?,?,'active')
            ")->execute([(int)$uni['id'], $companyId]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'remove_partner') {
            $uni = jobsRequireUniversity($pdo, $userId);
            $companyId = (int)($data['company_id'] ?? 0);
            $pdo->prepare('DELETE FROM job_university_partners WHERE university_id=? AND company_id=?')
                ->execute([(int)$uni['id'], $companyId]);
            echo json_encode(['success' => true]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/* ── Schema ─────────────────────────────────────────────────── */

function ensureJobsSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS job_vacancies (
        id                    INT AUTO_INCREMENT PRIMARY KEY,
        company_id            INT NOT NULL,
        created_by            INT NOT NULL,
        title                 VARCHAR(180) NOT NULL,
        description           TEXT,
        requirements          TEXT,
        work_format           ENUM('office','remote','hybrid') DEFAULT 'hybrid',
        schedule              ENUM('full-time','part-time') DEFAULT 'full-time',
        education             ENUM('bachelor','master','any') DEFAULT 'any',
        specialization        VARCHAR(50) DEFAULT NULL,
        target_skills         VARCHAR(500) DEFAULT NULL,
        target_university_ids VARCHAR(500) DEFAULT NULL,
        contact_email         VARCHAR(255) DEFAULT NULL,
        contact_name          VARCHAR(120) DEFAULT NULL,
        city                  VARCHAR(120) DEFAULT NULL,
        country_name          VARCHAR(120) DEFAULT NULL,
        vacancy_type          ENUM('internship','job','trainee') DEFAULT 'internship',
        status                ENUM('draft','open','closed') DEFAULT 'draft',
        applications_count    INT NOT NULL DEFAULT 0,
        offers_count          INT NOT NULL DEFAULT 0,
        deadline              DATE DEFAULT NULL,
        published_at          DATETIME DEFAULT NULL,
        created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_jv_company (company_id),
        KEY idx_jv_status (status),
        KEY idx_jv_published (published_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS job_applications (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        vacancy_id      INT NOT NULL,
        company_id      INT NOT NULL,
        user_id         INT NOT NULL,
        university_id   INT DEFAULT NULL,
        message         TEXT,
        resume_snapshot TEXT,
        status          ENUM('pending','reviewing','offer','accepted','rejected','withdrawn') DEFAULT 'pending',
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_job_app (vacancy_id, user_id),
        KEY idx_ja_company (company_id, status),
        KEY idx_ja_user (user_id),
        KEY idx_ja_uni (university_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS job_university_partners (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        university_id  INT NOT NULL,
        company_id     INT NOT NULL,
        status         ENUM('active','pending') DEFAULT 'active',
        created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_jup (university_id, company_id),
        KEY idx_jup_company (company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $done = true;
}

/* ── Helpers ────────────────────────────────────────────────── */

function jobsNormWorkFormat(?string $v): string
{
    return in_array($v, ['office', 'remote', 'hybrid'], true) ? $v : 'hybrid';
}

function jobsNormSchedule(?string $v): string
{
    return in_array($v, ['full-time', 'part-time'], true) ? $v : 'full-time';
}

function jobsNormEducation(?string $v): string
{
    return in_array($v, ['bachelor', 'master', 'any'], true) ? $v : 'any';
}

function jobsNormType(?string $v): string
{
    return in_array($v, ['internship', 'job', 'trainee'], true) ? $v : 'internship';
}

function jobsNormSpec($v): ?string
{
    if (!$v) {
        return null;
    }
    $v = strtolower(trim((string)$v));
    return in_array($v, JOB_SPECS, true) ? $v : 'other';
}

function jobsNormSkills($v): ?string
{
    if (!$v) {
        return null;
    }
    if (is_array($v)) {
        $v = implode(',', $v);
    }
    $parts = array_filter(array_map('trim', explode(',', (string)$v)));
    return $parts ? implode(',', array_slice($parts, 0, 20)) : null;
}

function jobsNormUniIds($v): ?string
{
    if (!$v) {
        return null;
    }
    if (is_array($v)) {
        $v = implode(',', array_map('intval', $v));
    }
    $ids = array_filter(array_map('intval', explode(',', (string)$v)));
    return $ids ? implode(',', $ids) : null;
}

function jobsFetchCompany(PDO $pdo, int $userId): ?array
{
    if (!jobsTableExists($pdo, 'company_profiles')) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM company_profiles WHERE user_id=? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function jobsFetchUniversity(PDO $pdo, int $userId): ?array
{
    if (!jobsTableExists($pdo, 'university_profiles')) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM university_profiles WHERE user_id=? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function jobsRequireCompany(PDO $pdo, int $userId): array
{
    $user = getUser($userId);
    if (($user['role'] ?? '') !== 'company') {
        http_response_code(403);
        echo json_encode(['error' => 'Только для аккаунтов компаний']);
        exit;
    }
    $c = jobsFetchCompany($pdo, $userId);
    if (!$c) {
        http_response_code(404);
        echo json_encode(['error' => 'Профиль компании не найден']);
        exit;
    }
    return $c;
}

function jobsEnsureEmployerColumn(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $cols = tableColumns('users');
    if (!in_array('employer_company_id', $cols, true)) {
        try {
            $pdo->exec('ALTER TABLE users ADD COLUMN employer_company_id INT NULL');
        } catch (Throwable $e) {
        }
    }
}

function jobsAddApplicantToTeam(PDO $pdo, array $company, int $applicantUserId): bool
{
    if (!$applicantUserId) {
        return false;
    }
    jobsEnsureEmployerColumn($pdo);
    $cols = tableColumns('users');
    $sets = [];
    $params = [];
    if (in_array('employer_company_id', $cols, true)) {
        $sets[] = 'employer_company_id = ?';
        $params[] = (int)$company['id'];
    }
    $companyName = trim((string)($company['company_name'] ?? ''));
    if ($companyName !== '' && in_array('organization', $cols, true)) {
        $sets[] = 'organization = ?';
        $params[] = $companyName;
    }
    if (!$sets) {
        return false;
    }
    $params[] = $applicantUserId;
    $stmt = $pdo->prepare(
        'UPDATE users SET ' . implode(', ', $sets)
        . " WHERE id = ? AND role NOT IN ('company', 'university')"
    );
    $stmt->execute($params);
    return true;
}

function jobsRequireUniversity(PDO $pdo, int $userId): array
{
    $user = getUser($userId);
    if (($user['role'] ?? '') !== 'university') {
        http_response_code(403);
        echo json_encode(['error' => 'Только для университетов']);
        exit;
    }
    $u = jobsFetchUniversity($pdo, $userId);
    if (!$u) {
        http_response_code(404);
        echo json_encode(['error' => 'Профиль университета не найден']);
        exit;
    }
    return $u;
}

function jobsRequireStudent(PDO $pdo, int $userId): void
{
    $user = getUser($userId);
    $role = $user['role'] ?? 'student';
    if (in_array($role, ['company', 'university'], true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Отклик доступен студентам и пользователям']);
        exit;
    }
}

function jobsTableExists(PDO $pdo, string $table): bool
{
    try {
        return (bool)$pdo->query("
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $pdo->quote($table) . " LIMIT 1
        ")->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function jobsUserUniversityId(PDO $pdo, int $userId): ?int
{
    try {
        $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'university_id'")->fetch();
        if (!$col) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT university_id FROM users WHERE id=?');
        $stmt->execute([$userId]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    } catch (Throwable $e) {
        return null;
    }
}

function jobsBuildResumeSnapshot(PDO $pdo, int $userId): array
{
    $user = getUser($userId) ?: [];
    $cols = ['specialty', 'education', 'country', 'city', 'bio', 'interest1', 'interest2', 'interest3', 'organization'];
    $extra = [];
    foreach ($cols as $c) {
        try {
            $has = $pdo->query("SHOW COLUMNS FROM users LIKE " . $pdo->quote($c))->fetch();
            if ($has) {
                $extra[$c] = $user[$c] ?? null;
            }
        } catch (Throwable $e) {
        }
    }
    $uniName = null;
    $uniId = jobsUserUniversityId($pdo, $userId);
    if ($uniId && jobsTableExists($pdo, 'university_profiles')) {
        $st = $pdo->prepare('SELECT university_name FROM university_profiles WHERE id=?');
        $st->execute([$uniId]);
        $uniName = $st->fetchColumn() ?: null;
    }
    return [
        'name'       => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
        'email'      => $user['email'] ?? null,
        'avatar'     => $user['avatar'] ?? null,
        'university' => $uniName,
        'badges'     => [],
        'profile'    => $extra,
        'generated_at' => date('c'),
    ];
}

function jobsStudentCanSee(PDO $pdo, int $userId, array $v): bool
{
    $uniId = jobsUserUniversityId($pdo, $userId);
    $targetUnis = trim($v['target_university_ids'] ?? '');
    if ($targetUnis !== '') {
        $allowed = array_map('intval', explode(',', $targetUnis));
        if ($uniId && in_array($uniId, $allowed, true)) {
            return true;
        }
        if ($uniId && jobsTableExists($pdo, 'job_university_partners')) {
            $st = $pdo->prepare("
                SELECT 1 FROM job_university_partners
                WHERE university_id=? AND company_id=? AND status='active' LIMIT 1
            ");
            $st->execute([$uniId, (int)$v['company_id']]);
            if ($st->fetch()) {
                return true;
            }
        }
        return false;
    }
    return true;
}

function jobsRelevanceScore(array $v, ?array $user): int
{
    if (!$user) {
        return 0;
    }
    $score = 0;
    $spec = $user['specialty'] ?? '';
    if ($v['specialization'] && $spec && stripos($spec, $v['specialization']) !== false) {
        $score += 3;
    }
    $skills = strtolower($v['target_skills'] ?? '');
    foreach (['interest1', 'interest2', 'interest3'] as $k) {
        $val = strtolower(trim($user[$k] ?? ''));
        if ($val && $skills && strpos($skills, $val) !== false) {
            $score += 2;
        }
    }
    if ($v['education'] !== 'any' && ($user['education'] ?? '') === $v['education']) {
        $score += 2;
    }
    if ($v['city'] && ($user['city'] ?? '') && strcasecmp($v['city'], $user['city']) === 0) {
        $score += 1;
    }
    return $score;
}

function jobsListVacancies(PDO $pdo, ?int $userId, array $filters): array
{
    $sql = "
        SELECT v.*, c.company_name, c.industry, c.logo AS company_logo, c.verified AS company_verified,
               EXISTS(SELECT 1 FROM job_applications a WHERE a.vacancy_id=v.id AND a.user_id=?) AS i_applied
        FROM job_vacancies v
        JOIN company_profiles c ON c.id = v.company_id
        WHERE v.status = 'open'
    ";
    $params = [$userId ?: 0];

    if (!empty($filters['q'])) {
        $sql .= ' AND (v.title LIKE ? OR c.company_name LIKE ? OR v.description LIKE ?)';
        $q = '%' . trim($filters['q']) . '%';
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
    }
    if (!empty($filters['schedule'])) {
        $sql .= ' AND v.schedule = ?';
        $params[] = jobsNormSchedule($filters['schedule']);
    }
    if (!empty($filters['education'])) {
        $sql .= ' AND (v.education = ? OR v.education = \'any\')';
        $params[] = jobsNormEducation($filters['education']);
    }
    if (!empty($filters['specialization'])) {
        $sql .= ' AND (v.specialization = ? OR v.specialization IS NULL)';
        $params[] = jobsNormSpec($filters['specialization']);
    }
    if (!empty($filters['work_format'])) {
        $sql .= ' AND v.work_format = ?';
        $params[] = jobsNormWorkFormat($filters['work_format']);
    }

    $sql .= ' ORDER BY v.published_at DESC, v.created_at DESC LIMIT 100';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $user = $userId ? getUser($userId) : null;
    $out  = [];
    foreach ($rows as $r) {
        if ($userId && !jobsStudentCanSee($pdo, $userId, $r)) {
            continue;
        }
        $shaped = jobsShapeVacancy($r, $userId);
        $shaped['relevance'] = jobsRelevanceScore($r, $user);
        $out[] = $shaped;
    }
    usort($out, static fn($a, $b) => ($b['relevance'] <=> $a['relevance']) ?: strcmp($b['published_at'] ?? '', $a['published_at'] ?? ''));
    return $out;
}

function jobsFetchVacancyRow(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT * FROM job_vacancies WHERE id=?');
    $stmt->execute([$id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) {
        http_response_code(404);
        echo json_encode(['error' => 'Вакансия не найдена']);
        exit;
    }
    return $r;
}

function jobsFetchVacancy(PDO $pdo, int $id, ?int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT v.*, c.company_name, c.industry, c.website, c.logo AS company_logo, c.city AS company_city, c.verified AS company_verified,
               EXISTS(SELECT 1 FROM job_applications a WHERE a.vacancy_id=v.id AND a.user_id=?) AS i_applied
        FROM job_vacancies v
        JOIN company_profiles c ON c.id = v.company_id
        WHERE v.id=?
    ");
    $stmt->execute([$userId ?: 0, $id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) {
        http_response_code(404);
        echo json_encode(['error' => 'Вакансия не найдена']);
        exit;
    }
    return jobsShapeVacancy($r, $userId);
}

function jobsShapeVacancy(array $r, ?int $userId): array
{
    return [
        'id'                    => (int)$r['id'],
        'company_id'            => (int)$r['company_id'],
        'title'                 => $r['title'],
        'description'           => $r['description'] ?? '',
        'requirements'          => $r['requirements'] ?? '',
        'work_format'           => $r['work_format'],
        'schedule'              => $r['schedule'],
        'education'             => $r['education'],
        'specialization'        => $r['specialization'],
        'target_skills'         => $r['target_skills'] ? explode(',', $r['target_skills']) : [],
        'target_university_ids' => $r['target_university_ids'] ? array_map('intval', explode(',', $r['target_university_ids'])) : [],
        'contact_email'         => $r['contact_email'],
        'contact_name'          => $r['contact_name'],
        'city'                  => $r['city'],
        'country_name'          => $r['country_name'],
        'vacancy_type'          => $r['vacancy_type'],
        'status'                => $r['status'],
        'active_applications_count' => (int)($r['active_applications_count'] ?? 0),
        'applications_count'    => (int)($r['active_applications_count'] ?? $r['applications_count'] ?? 0),
        'total_applications_count' => (int)($r['applications_count'] ?? 0),
        'offers_count'          => (int)($r['offers_count'] ?? 0),
        'deadline'              => $r['deadline'],
        'company_name'          => $r['company_name'] ?? null,
        'company_logo'          => $r['company_logo'] ?? null,
        'company_industry'      => $r['industry'] ?? null,
        'company_verified'      => !empty($r['company_verified']),
        'i_applied'             => !empty($r['i_applied']),
        'published_at'          => $r['published_at'] ? utcDate($r['published_at']) : null,
        'time_ago'              => timeAgo($r['created_at']),
    ];
}

function jobsMyVacancies(PDO $pdo, int $userId): array
{
    $c = jobsFetchCompany($pdo, $userId);
    if (!$c) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT v.*, c.company_name, c.industry, c.logo AS company_logo, c.verified AS company_verified, 0 AS i_applied,
               (SELECT COUNT(*) FROM job_applications ja
                WHERE ja.vacancy_id = v.id AND ja.status IN ('pending','reviewing','offer')) AS active_applications_count
        FROM job_vacancies v
        JOIN company_profiles c ON c.id = v.company_id
        WHERE v.company_id=?
        ORDER BY v.updated_at DESC
    ");
    $stmt->execute([(int)$c['id']]);
    return array_map(static fn($r) => jobsShapeVacancy($r, $userId), $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function jobsMyApplications(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT a.*, v.title AS vacancy_title, v.work_format, v.schedule, c.company_name
        FROM job_applications a
        JOIN job_vacancies v ON v.id = a.vacancy_id
        JOIN company_profiles c ON c.id = a.company_id
        WHERE a.user_id=?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$userId]);
    return array_map(static function ($r) {
        return [
            'id'            => (int)$r['id'],
            'vacancy_id'    => (int)$r['vacancy_id'],
            'vacancy_title' => $r['vacancy_title'],
            'company_name'  => $r['company_name'],
            'status'        => $r['status'],
            'work_format'   => $r['work_format'],
            'schedule'      => $r['schedule'],
            'created_at'    => utcDate($r['created_at']),
            'time_ago'      => timeAgo($r['created_at']),
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function jobsCompanyApplications(PDO $pdo, int $userId, int $vacancyId, string $scope = 'active'): array
{
    $c = jobsRequireCompany($pdo, $userId);
    $sql = "
        SELECT a.*, v.title AS vacancy_title, u.first_name, u.last_name, u.email, u.avatar
        FROM job_applications a
        JOIN job_vacancies v ON v.id = a.vacancy_id
        JOIN users u ON u.id = a.user_id
        WHERE a.company_id=?
    ";
    $params = [(int)$c['id']];
    if ($vacancyId) {
        $sql .= ' AND a.vacancy_id=?';
        $params[] = $vacancyId;
    }
    if ($scope === 'active') {
        $sql .= " AND a.status IN ('pending', 'reviewing', 'offer')";
    } elseif ($scope === 'archive') {
        $sql .= " AND a.status IN ('accepted', 'rejected', 'withdrawn')";
    }
    $sql .= ' ORDER BY a.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return array_map(static function ($r) {
        $resume = json_decode($r['resume_snapshot'] ?? '{}', true) ?: [];
        return [
            'id'            => (int)$r['id'],
            'vacancy_id'    => (int)$r['vacancy_id'],
            'vacancy_title' => $r['vacancy_title'],
            'user_id'       => (int)$r['user_id'],
            'name'          => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
            'email'         => $r['email'],
            'avatar'        => $r['avatar'],
            'status'        => $r['status'],
            'message'       => $r['message'],
            'resume'        => $resume,
            'university_id' => $r['university_id'] ? (int)$r['university_id'] : null,
            'created_at'    => utcDate($r['created_at']),
            'time_ago'      => timeAgo($r['created_at']),
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function jobsFetchApplication(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare("
        SELECT a.*, v.title AS vacancy_title
        FROM job_applications a
        JOIN job_vacancies v ON v.id = a.vacancy_id
        WHERE a.id=?
    ");
    $stmt->execute([$id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) {
        http_response_code(404);
        echo json_encode(['error' => 'Отклик не найден']);
        exit;
    }
    return $r;
}

function jobsUniversityStats(PDO $pdo, int $userId): array
{
    $uni = jobsRequireUniversity($pdo, $userId);
    $uniId = (int)$uni['id'];

    $apps = $pdo->prepare("
        SELECT COUNT(*) FROM job_applications WHERE university_id=?
    ");
    $apps->execute([$uniId]);
    $totalApps = (int)$apps->fetchColumn();

    $offers = $pdo->prepare("
        SELECT COUNT(*) FROM job_applications
        WHERE university_id=? AND status IN ('offer','accepted')
    ");
    $offers->execute([$uniId]);
    $totalOffers = (int)$offers->fetchColumn();

    $students = 0;
    try {
        $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE university_id=?');
        $st->execute([$uniId]);
        $students = (int)$st->fetchColumn();
    } catch (Throwable $e) {
    }

    $partners = 0;
    if (jobsTableExists($pdo, 'job_university_partners')) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM job_university_partners WHERE university_id=? AND status='active'");
        $st->execute([$uniId]);
        $partners = (int)$st->fetchColumn();
    }

    $recent = $pdo->prepare("
        SELECT a.id, a.status, a.created_at, u.first_name, u.last_name, v.title AS vacancy_title, c.company_name
        FROM job_applications a
        JOIN users u ON u.id = a.user_id
        JOIN job_vacancies v ON v.id = a.vacancy_id
        JOIN company_profiles c ON c.id = a.company_id
        WHERE a.university_id=?
        ORDER BY a.created_at DESC LIMIT 15
    ");
    $recent->execute([$uniId]);

    return [
        'students_count'      => $students,
        'applications_count'  => $totalApps,
        'offers_count'        => $totalOffers,
        'partners_count'      => $partners,
        'recent_applications' => array_map(static function ($r) {
            return [
                'id'            => (int)$r['id'],
                'student'       => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
                'vacancy_title' => $r['vacancy_title'],
                'company_name'  => $r['company_name'],
                'status'        => $r['status'],
                'time_ago'      => timeAgo($r['created_at']),
            ];
        }, $recent->fetchAll(PDO::FETCH_ASSOC)),
    ];
}

function jobsListPartners(PDO $pdo, int $userId): array
{
    $uni = jobsRequireUniversity($pdo, $userId);
    if (!jobsTableExists($pdo, 'job_university_partners')) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT p.company_id, p.status, p.created_at, c.company_name, c.industry, c.city
        FROM job_university_partners p
        JOIN company_profiles c ON c.id = p.company_id
        WHERE p.university_id=? AND p.status='active'
        ORDER BY c.company_name
    ");
    $stmt->execute([(int)$uni['id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function jobsNotifyCompany(PDO $pdo, int $companyId, int $fromUserId, int $vacancyId, string $title): void
{
    try {
        ensureNotificationsSchema($pdo);
        if (!jobsTableExists($pdo, 'company_profiles')) {
            return;
        }
        $st = $pdo->prepare('SELECT user_id FROM company_profiles WHERE id=?');
        $st->execute([$companyId]);
        $ownerId = (int)$st->fetchColumn();
        if (!$ownerId || $ownerId === $fromUserId) {
            return;
        }
        $preview = 'Отклик на «' . mb_substr($title, 0, 60) . '»';
        $pdo->prepare("
            INSERT INTO notifications (user_id, from_user_id, type, post_id, post_preview, post_type, created_at)
            VALUES (?, ?, 'job_application', ?, ?, 'company', NOW())
        ")->execute([$ownerId, $fromUserId, $vacancyId, $preview]);
    } catch (Throwable $e) {
    }
}

function jobsNotifyApplicant(PDO $pdo, int $userId, int $fromUserId, int $appId, string $status, string $title): void
{
    try {
        ensureNotificationsSchema($pdo);
        $labels = [
            'reviewing' => 'на рассмотрении',
            'offer'     => 'оффер',
            'accepted'  => 'принят',
            'rejected'  => 'отклонён',
        ];
        $preview = 'Отклик «' . mb_substr($title, 0, 50) . '»: ' . ($labels[$status] ?? $status);
        $pdo->prepare("
            INSERT INTO notifications (user_id, from_user_id, type, post_id, post_preview, post_type, created_at)
            VALUES (?, ?, 'job_status', ?, ?, 'career', NOW())
        ")->execute([$userId, $fromUserId, $appId, $preview]);
    } catch (Throwable $e) {
    }
}
