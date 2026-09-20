<?php
/**
 * project_templates.php — шаблоны проектов (Sprint 14)
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';
require_once __DIR__ . '/project_roles.php';
require_once __DIR__ . '/project_resource_plans.php';

const TEMPLATE_IDS = ['startup', 'coursework', 'hackathon', 'research'];

function ensureSprint14Schema(PDO $pdo): void
{
    ensureProjectRolesSchema($pdo);

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_resources (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        project_id  INT NOT NULL,
        kind        ENUM('github','figma','notion','drive','website','training','other') NOT NULL DEFAULT 'other',
        title       VARCHAR(120) NOT NULL,
        url         VARCHAR(500) NOT NULL DEFAULT '',
        position    INT NOT NULL DEFAULT 0,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_pres_project (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $col = $pdo->query("SHOW COLUMNS FROM project_docs LIKE 'kind'")->fetch();
    if (!$col) {
        try {
            $pdo->exec("ALTER TABLE project_docs ADD COLUMN kind ENUM('doc','wiki') NOT NULL DEFAULT 'doc' AFTER title");
        } catch (Throwable $e) {}
    }

    try {
        $resKind = $pdo->query("SHOW COLUMNS FROM project_resources LIKE 'kind'")->fetch(PDO::FETCH_ASSOC);
        if ($resKind && strpos((string)($resKind['Type'] ?? ''), 'training') === false) {
            $pdo->exec("ALTER TABLE project_resources MODIFY kind ENUM('github','figma','notion','drive','website','training','other') NOT NULL DEFAULT 'other'");
        }
    } catch (Throwable $e) {}
}

function getProjectTemplates(): array
{
    return [
        'startup' => [
            'id'          => 'startup',
            'label'       => 'Стартап',
            'icon'        => '🚀',
            'category'    => 'product',
            'stage'       => 'idea',
            'tags'        => ['startup', 'mvp'],
            'looking_for' => 'Product Manager, Fullstack Developer, UI/UX Designer',
            'description' => 'Стартап: опишите проблему, решение и целевую аудиторию.',
            'my_specialization' => 'product_manager',
            'tasks' => [
                ['title' => 'Сформулировать проблему и гипотезу', 'status' => 'backlog', 'priority' => 'high'],
                ['title' => 'Описать MVP и метрики успеха', 'status' => 'backlog', 'priority' => 'high'],
                ['title' => 'Собрать landing / прототип', 'status' => 'todo', 'priority' => 'medium'],
                ['title' => 'Найти первых пользователей', 'status' => 'backlog', 'priority' => 'medium'],
            ],
            'roles' => [
                ['title' => 'Product Manager', 'specialization' => 'product_manager', 'description' => 'Видение продукта, приоритеты, метрики'],
                ['title' => 'Fullstack Developer', 'specialization' => 'fullstack', 'description' => 'MVP и интеграции'],
                ['title' => 'UI/UX Designer', 'specialization' => 'designer', 'description' => 'Интерфейс и UX-исследования'],
            ],
            'wiki' => [
                ['title' => 'Product Vision', 'content' => "## Видение продукта\n\n**Проблема:** \n\n**Решение:** \n\n**Целевая аудитория:** \n\n**Ключевые метрики:** "],
                ['title' => 'Pitch notes', 'content' => "## Заметки для питча\n\n- Elevator pitch (30 сек)\n- Конкуренты\n- Монетизация\n- Следующие шаги"],
            ],
        ],
        'coursework' => [
            'id'          => 'coursework',
            'label'       => 'Курсовая',
            'icon'        => '📚',
            'category'    => 'research',
            'stage'       => 'idea',
            'tags'        => ['курсовая', 'учёба'],
            'looking_for' => 'Analyst, Backend Developer',
            'description' => 'Курсовой проект: тема, цель, задачи и план работы.',
            'my_specialization' => 'analyst',
            'tasks' => [
                ['title' => 'Литературный обзор', 'status' => 'todo', 'priority' => 'high'],
                ['title' => 'Методология и план работы', 'status' => 'backlog', 'priority' => 'high'],
                ['title' => 'Черновик основной части', 'status' => 'backlog', 'priority' => 'medium'],
                ['title' => 'Оформление и подготовка к защите', 'status' => 'backlog', 'priority' => 'medium'],
            ],
            'roles' => [
                ['title' => 'Analyst', 'specialization' => 'analyst', 'description' => 'Исследование, анализ литературы'],
                ['title' => 'Backend Developer', 'specialization' => 'backend', 'description' => 'Реализация практической части'],
            ],
            'wiki' => [
                ['title' => 'Требования', 'content' => "## Требования к работе\n\n- Тема:\n- Объём:\n- Срок сдачи:\n- Критерии оценки:"],
                ['title' => 'Структура работы', 'content' => "## Структура\n\n1. Введение\n2. Обзор литературы\n3. Методология\n4. Результаты\n5. Заключение\n6. Список литературы"],
            ],
        ],
        'hackathon' => [
            'id'          => 'hackathon',
            'label'       => 'Хакатон',
            'icon'        => '⚡',
            'category'    => 'product',
            'stage'       => 'prototype',
            'tags'        => ['hackathon', 'demo'],
            'looking_for' => 'Fullstack Developer, UI/UX Designer, Pitch speaker',
            'description' => 'Хакатон: идея, прототип за 24–48 часов и демо.',
            'my_specialization' => 'fullstack',
            'tasks' => [
                ['title' => 'Выбрать идею и стек', 'status' => 'done', 'priority' => 'high'],
                ['title' => 'Прототип за 24 часа', 'status' => 'in_progress', 'priority' => 'high'],
                ['title' => 'Демо и презентация', 'status' => 'backlog', 'priority' => 'high'],
            ],
            'roles' => [
                ['title' => 'Fullstack Developer', 'specialization' => 'fullstack', 'description' => 'Быстрый прототип end-to-end'],
                ['title' => 'UI/UX Designer', 'specialization' => 'designer', 'description' => 'UI за ночь'],
                ['title' => 'Pitch speaker', 'specialization' => 'marketing', 'description' => 'Презентация жюри'],
            ],
            'wiki' => [
                ['title' => 'Идея и стек', 'content' => "## Идея\n\n**Проблема:** \n\n**Решение:** \n\n## Стек\n\n- Frontend:\n- Backend:\n- Инфра:"],
                ['title' => 'Demo script', 'content' => "## Сценарий демо (3 мин)\n\n1. Проблема (30 сек)\n2. Решение (1 мин)\n3. Live demo (1 мин)\n4. Итог (30 сек)"],
            ],
        ],
        'research' => [
            'id'          => 'research',
            'label'       => 'Исследование',
            'icon'        => '🔬',
            'category'    => 'research',
            'stage'       => 'mvp',
            'tags'        => ['research', 'эксперимент'],
            'looking_for' => 'Analyst, ML Engineer',
            'description' => 'Исследовательский проект: гипотеза, данные, анализ, выводы.',
            'my_specialization' => 'analyst',
            'tasks' => [
                ['title' => 'Сформулировать гипотезу и метрики', 'status' => 'todo', 'priority' => 'high'],
                ['title' => 'Сбор и подготовка данных', 'status' => 'backlog', 'priority' => 'high'],
                ['title' => 'Анализ результатов', 'status' => 'backlog', 'priority' => 'medium'],
                ['title' => 'Отчёт и выводы', 'status' => 'backlog', 'priority' => 'medium'],
            ],
            'roles' => [
                ['title' => 'Analyst', 'specialization' => 'analyst', 'description' => 'Дизайн эксперимента, интерпретация'],
                ['title' => 'ML Engineer', 'specialization' => 'ml_engineer', 'description' => 'Модели и пайплайны данных'],
            ],
            'wiki' => [
                ['title' => 'Гипотеза', 'content' => "## Гипотеза\n\n**H0:** \n\n**H1:** \n\n**Метрики:** \n\n**Критерий успеха:** "],
                ['title' => 'Датасеты и источники', 'content' => "## Источники данных\n\n| Источник | Описание | Ссылка |\n|----------|----------|--------|\n| | | |"],
            ],
        ],
    ];
}

function seedProjectTemplate(PDO $pdo, int $projectId, int $userId, string $templateId): array
{
    ensureSprint14Schema($pdo);
    $templates = getProjectTemplates();
    if (!isset($templates[$templateId])) {
        return ['seeded' => false];
    }
    $tpl = $templates[$templateId];
    $result = ['tasks' => 0, 'roles' => 0, 'wiki' => 0];

    // Tasks
    foreach ($tpl['tasks'] as $task) {
        $status = $task['status'] ?? 'backlog';
        $maxPos = $pdo->prepare('SELECT COALESCE(MAX(position),0)+1 FROM project_tasks WHERE project_id=? AND status=?');
        $maxPos->execute([$projectId, $status]);
        $pos = (int)$maxPos->fetchColumn();
        $pdo->prepare('
            INSERT INTO project_tasks (project_id, created_by, title, description, status, priority, position)
            VALUES (?,?,?,?,?,?,?)
        ')->execute([
            $projectId, $userId,
            mb_substr($task['title'], 0, 200),
            trim($task['description'] ?? ''),
            $status,
            $task['priority'] ?? 'medium',
            $pos,
        ]);
        $result['tasks']++;
    }

    // Roles
    $rolePos = (int)$pdo->query('SELECT COALESCE(MAX(position),0) FROM project_roles WHERE project_id=' . (int)$projectId)->fetchColumn();
    foreach ($tpl['roles'] as $role) {
        $rolePos++;
        $pdo->prepare('
            INSERT INTO project_roles (project_id, title, description, specialization, status, slots, position)
            VALUES (?,?,?,?, \'open\', 1, ?)
        ')->execute([
            $projectId,
            mb_substr($role['title'], 0, 120),
            trim($role['description'] ?? ''),
            normalizeSpec($role['specialization'] ?? null),
            $rolePos,
        ]);
        $result['roles']++;
    }

    // Wiki pages
    $wikiPos = 0;
    foreach ($tpl['wiki'] as $page) {
        $wikiPos++;
        $pdo->prepare('
            INSERT INTO project_docs (project_id, author_id, parent_id, title, kind, content, position)
            VALUES (?,?,?,?, \'wiki\',?,?)
        ')->execute([
            $projectId, $userId, null,
            mb_substr($page['title'], 0, 300),
            $page['content'] ?? '',
            $wikiPos,
        ]);
        $result['wiki']++;
    }

    return ['seeded' => true, 'template' => $templateId, 'counts' => $result];
}

function fetchProjectResources(PDO $pdo, int $projectId): array
{
    ensureSprint14Schema($pdo);
    $stmt = $pdo->prepare('SELECT * FROM project_resources WHERE project_id=? ORDER BY position ASC, id ASC');
    $stmt->execute([$projectId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buildProjectReadme(PDO $pdo, int $projectId): string
{
    ensureSprint14Schema($pdo);

    $stmt = $pdo->prepare('
        SELECT p.*, u.first_name, u.last_name
        FROM projects p JOIN users u ON p.owner_id = u.id
        WHERE p.id = ?
    ');
    $stmt->execute([$projectId]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$p) return '';

    $categoryLabels = [
        'product' => 'Продукт', 'research' => 'Исследование', 'ai_ml' => 'AI / ML',
        'design' => 'Дизайн', 'web' => 'Web', 'mobile' => 'Mobile', 'other' => 'Другое',
    ];
    $stageLabels = [
        'idea' => 'Идея', 'prototype' => 'Прототип', 'mvp' => 'MVP',
        'beta' => 'Бета', 'launched' => 'Запущен', 'completed' => 'Завершён',
    ];

    $lines = [];
    $lines[] = '# ' . $p['title'];
    $lines[] = '';
    $lines[] = '> ' . ($categoryLabels[$p['category']] ?? $p['category']) . ' · ' . ($stageLabels[$p['stage']] ?? $p['stage']);
    $lines[] = '';
    if (trim($p['description'] ?? '')) {
        $lines[] = trim($p['description']);
        $lines[] = '';
    }
    if ($p['tags']) {
        $tags = array_map('trim', explode(',', $p['tags']));
        $lines[] = '**Теги:** ' . implode(', ', array_map(fn($t) => "`$t`", $tags));
        $lines[] = '';
    }

    // Team
    $mem = $pdo->prepare('
        SELECT u.first_name, u.last_name, pm.permission_role, pm.specialization_role
        FROM project_members pm JOIN users u ON pm.user_id = u.id
        WHERE pm.project_id = ? ORDER BY FIELD(pm.permission_role, \'owner\',\'admin\',\'member\',\'viewer\')
    ');
    $mem->execute([$projectId]);
    $members = $mem->fetchAll(PDO::FETCH_ASSOC);
    if ($members) {
        $lines[] = '## Команда';
        $lines[] = '';
        foreach ($members as $m) {
            $lines[] = '- **' . $m['first_name'] . ' ' . $m['last_name'] . '** — ' . ($m['specialization_role'] ?? '') . ' (' . $m['permission_role'] . ')';
        }
        $lines[] = '';
    }

    // Open roles
    $roles = fetchProjectRoles($pdo, $projectId, null);
    $openRoles = array_filter($roles, fn($r) => $r['status'] === 'open');
    if ($openRoles) {
        $lines[] = '## Открытые роли';
        $lines[] = '';
        foreach ($openRoles as $r) {
            $lines[] = '- **' . $r['title'] . '**' . ($r['description'] ? ': ' . $r['description'] : '');
        }
        $lines[] = '';
    } elseif ($p['looking_for']) {
        $lines[] = '## Ищем в команду';
        $lines[] = '';
        foreach (array_map('trim', explode(',', $p['looking_for'])) as $r) {
            if ($r) $lines[] = '- ' . $r;
        }
        $lines[] = '';
    }

    // Resource planning
    $plans = fetchProjectResourcePlans($pdo, $projectId);
    if ($plans) {
        $lines[] = '## Планирование ресурсов';
        $lines[] = '';
        foreach ($plans as $pl) {
            $line = '- **' . $pl['title'] . '** [' . $pl['category'] . ']: '
                . $pl['allocated_amount'] . '/' . $pl['planned_amount'] . ' ' . $pl['unit']
                . ' (' . $pl['status'] . ')';
            if (!empty($pl['due_date'])) {
                $line .= ' — до ' . $pl['due_date'];
            }
            $lines[] = $line;
        }
        $lines[] = '';
    }

    // Legacy link resources + project URLs
    $resources = fetchProjectResources($pdo, $projectId);
    $resWithUrl = array_filter($resources, fn($r) => trim($r['url'] ?? '') !== '');
    $links = [];
    if ($p['website_url']) $links[] = ['Сайт', $p['website_url']];
    if ($p['github_url'])  $links[] = ['GitHub', $p['github_url']];
    foreach ($resWithUrl as $r) {
        $links[] = [$r['title'], $r['url']];
    }
    if ($links) {
        $lines[] = '## Ресурсы';
        $lines[] = '';
        foreach ($links as [$label, $url]) {
            $lines[] = '- [' . $label . '](' . $url . ')';
        }
        $lines[] = '';
    }

    // Milestones
    try {
        $ms = $pdo->prepare('SELECT title, status, target_date, description FROM project_milestones WHERE project_id=? ORDER BY position ASC');
        $ms->execute([$projectId]);
        $milestones = $ms->fetchAll(PDO::FETCH_ASSOC);
        if ($milestones) {
            $lines[] = '## Дорожная карта';
            $lines[] = '';
            foreach ($milestones as $m) {
                $date = $m['target_date'] ? ' (' . $m['target_date'] . ')' : '';
                $lines[] = '- **' . $m['title'] . '** [' . $m['status'] . ']' . $date;
            }
            $lines[] = '';
        }
    } catch (Throwable $e) {}

    // Tasks summary
    $ts = $pdo->prepare('SELECT status, COUNT(*) AS cnt FROM project_tasks WHERE project_id=? GROUP BY status');
    $ts->execute([$projectId]);
    $taskStats = $ts->fetchAll(PDO::FETCH_ASSOC);
    if ($taskStats) {
        $lines[] = '## Задачи';
        $lines[] = '';
        foreach ($taskStats as $t) {
            $lines[] = '- ' . $t['status'] . ': ' . $t['cnt'];
        }
        $lines[] = '';
    }

    // Wiki
    try {
        $wiki = $pdo->prepare("SELECT title FROM project_docs WHERE project_id=? AND kind='wiki' ORDER BY position ASC");
        $wiki->execute([$projectId]);
        $wikiPages = $wiki->fetchAll(PDO::FETCH_COLUMN);
        if ($wikiPages) {
            $lines[] = '## Wiki';
            $lines[] = '';
            foreach ($wikiPages as $title) {
                $lines[] = '- ' . $title;
            }
            $lines[] = '';
        }
    } catch (Throwable $e) {}

    $lines[] = '---';
    $lines[] = '*Экспортировано из [Mendflow](https://mendflow)* · ' . date('Y-m-d H:i');

    return implode("\n", $lines);
}

// ── Router (direct calls only) ───────────────────────────────
if (mfIsDirectScript(__FILE__)) {
    try {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'GET') {
            verifyTokenSoft();
            $action = $_GET['action'] ?? 'list';
            if ($action === 'list') {
                $all = getProjectTemplates();
                $list = array_values(array_map(function ($t) {
                    return [
                        'id'          => $t['id'],
                        'label'       => $t['label'],
                        'icon'        => $t['icon'],
                        'category'    => $t['category'],
                        'stage'       => $t['stage'],
                        'tags'        => $t['tags'],
                        'looking_for' => $t['looking_for'],
                        'description' => $t['description'],
                        'my_specialization' => $t['my_specialization'],
                    ];
                }, $all));
                echo json_encode(['templates' => $list]);
                exit;
            }
            http_response_code(400);
            echo json_encode(['error' => 'Неизвестный action']);
            exit;
        }
        http_response_code(405);
        echo json_encode(['error' => 'Метод не разрешён']);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
