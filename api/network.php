<?php
/**
 * network.php — people + organizations for the Connect view
 * InfinityFree-safe: ob_start() + all errors suppressed
 */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

function jsonOut($data, $code = 200) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonOut(['error' => 'Method not allowed'], 405);
}

require_once 'db.php';
require_once __DIR__ . '/badges_lib.php';

try {
    $people = [];
    $organizations = [];

    $userCols = [];
    try {
        $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}

    $roleFilter = in_array('role', $userCols, true)
        ? "WHERE (u.role IS NULL OR u.role NOT IN ('university', 'company'))"
        : '';

    $extraCols = '';
    foreach (['organization', 'specialty', 'city'] as $col) {
        if (in_array($col, $userCols, true)) {
            $extraCols .= ", u.{$col}";
        }
    }

    /* ── People (students only) ── */
    $peopleStmt = $pdo->query("
        SELECT
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.avatar,
            u.created_at{$extraCols},
            COUNT(DISTINCT p.id) AS posts_count
        FROM users u
        LEFT JOIN posts p ON p.user_id = u.id
        {$roleFilter}
        GROUP BY u.id, u.first_name, u.last_name, u.email, u.avatar, u.created_at{$extraCols}
        ORDER BY u.created_at DESC
        LIMIT 100
    ");

    $peopleRows = $peopleStmt->fetchAll(PDO::FETCH_ASSOC);
    $peopleIds  = array_map(static fn($r) => (int)$r['id'], $peopleRows);
    $badgeMap   = mfComputeUserBadgesBatch($pdo, $peopleIds);

    foreach ($peopleRows as $row) {
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $uid  = (int)$row['id'];
        $badges = $badgeMap[$uid] ?? mfBuildBadgePayload([]);
        $subtitle = $badges['primary_label'] ?? '';
        if ($subtitle === '') {
            $subtitle = trim((string)($row['organization'] ?? ''));
            if ($subtitle === '' && !empty($row['city'])) {
                $subtitle = (string)$row['city'];
            }
        }
        $earnedTags = array_map(static fn($b) => $b['title'], $badges['earned'] ?? []);
        $people[] = [
            'id'          => $uid,
            'type'        => 'people',
            'title'       => $name ?: 'Участник',
            'subtitle'    => $subtitle,
            'meta'        => $row['city'] ?? ($row['organization'] ?? ''),
            'description' => '',
            'avatar'      => $row['avatar'] ?? null,
            'tags'        => $earnedTags,
            'badges'      => $badges['earned'] ?? [],
            'action'      => 'Открыть профиль',
        ];
    }

    /* ── Registered university profiles ── */
    try {
        $uniTableExists = $pdo->query("SHOW TABLES LIKE 'university_profiles'")->fetchColumn();
        if ($uniTableExists) {
            $uniStmt = $pdo->query("
                SELECT id, university_name, city, country_name, website, description, verified
                FROM university_profiles
                ORDER BY created_at DESC
                LIMIT 100
            ");
            foreach ($uniStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $location = trim(implode(', ', array_filter([$row['city'] ?? '', $row['country_name'] ?? ''])));
                $organizations[] = [
                    'id'          => (int)$row['id'],
                    'db_id'       => (int)$row['id'],
                    'type'        => 'universities',
                    'title'       => $row['university_name'],
                    'subtitle'    => $location ?: 'Университет Mendflow',
                    'meta'        => $row['website'] ?: ($location ?: 'Профиль университета'),
                    'description' => $row['description'] ?: 'Страница университета с курсами, клубами и программами обмена.',
                    'tags'        => array_values(array_filter(['Universities', !empty($row['verified']) ? 'Verified' : null])),
                    'action'      => 'Открыть профиль',
                ];
            }
        }
    } catch (Exception $e) {}

    /* ── Registered company profiles ── */
    try {
        $coTableExists = $pdo->query("SHOW TABLES LIKE 'company_profiles'")->fetchColumn();
        if ($coTableExists) {
            $hasJobs = $pdo->query("SHOW TABLES LIKE 'job_vacancies'")->fetchColumn();
            $jobsSel = $hasJobs
                ? "(SELECT COUNT(*) FROM job_vacancies jv WHERE jv.company_id = cp.id AND jv.status = 'open')"
                : '0';
            $coStmt = $pdo->query("
                SELECT cp.id, cp.company_name, cp.tagline, cp.industry, cp.city, cp.country_name,
                       cp.website, cp.description, cp.verified, cp.logo, {$jobsSel} AS open_jobs
                FROM company_profiles cp
                ORDER BY cp.verified DESC, cp.created_at DESC
                LIMIT 100
            ");
            foreach ($coStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $location = trim(implode(', ', array_filter([$row['city'] ?? '', $row['country_name'] ?? ''])));
                $subtitle = $row['tagline'] ?: ($row['industry'] ?: ($location ?: 'Компания Mendflow'));
                $organizations[] = [
                    'id'          => (int)$row['id'],
                    'db_id'       => (int)$row['id'],
                    'type'        => 'companies',
                    'title'       => $row['company_name'],
                    'subtitle'    => $subtitle,
                    'meta'        => $location ?: ($row['website'] ?: 'Профиль компании'),
                    'description' => $row['description'] ?: 'Страница компании с вакансиями, проектами и командой.',
                    'tags'        => array_values(array_filter([
                        'Companies',
                        !empty($row['verified']) ? 'Verified' : null,
                        (int)$row['open_jobs'] > 0 ? ((int)$row['open_jobs'] . ' вакансий') : null,
                    ])),
                    'action'      => 'Открыть профиль',
                    'verified'    => !empty($row['verified']),
                    'open_jobs'   => (int)$row['open_jobs'],
                ];
            }
        }
    } catch (Exception $e) {}

    /* ── Seed organizations (optional table) ── */
    $skipSeedCompanies = !empty(array_filter($organizations, static fn($o) => ($o['type'] ?? '') === 'companies'));
    try {
        $orgTableExists = $pdo->query("SHOW TABLES LIKE 'organizations'")->fetchColumn();
        if ($orgTableExists) {
            $orgStmt = $pdo->query("
                SELECT id, type, title, subtitle, meta, description, tags_text, action_label
                FROM organizations
                ORDER BY type ASC, title ASC
                LIMIT 100
            ");
            foreach ($orgStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($skipSeedCompanies && ($row['type'] ?? '') === 'companies') {
                    continue;
                }
                $tags = array_values(array_filter(array_map('trim', explode(',', (string)($row['tags_text'] ?? '')))));
                $organizations[] = [
                    'id'          => (int)$row['id'],
                    'db_id'       => (int)$row['id'],
                    'type'        => $row['type'],
                    'title'       => $row['title'],
                    'subtitle'    => $row['subtitle'],
                    'meta'        => $row['meta'],
                    'description' => $row['description'],
                    'tags'        => $tags,
                    'action'      => $row['action_label'] ?: 'Открыть',
                ];
            }
        }
    } catch (Exception $e) {
        // organizations table doesn't exist — silently ignore
    }

    jsonOut([
        'people'        => $people,
        'organizations' => $organizations,
    ]);

} catch (PDOException $e) {
    jsonOut(['error' => 'Ошибка БД: ' . $e->getMessage()], 500);
}
