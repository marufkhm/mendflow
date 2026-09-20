<?php
/**
 * ai.php — AI-помощник для проектов (Groq API proxy)
 * POST { action: "chat"|"search", message, history?, context? }
 */
error_reporting(0);
ini_set('display_errors', 0);
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешён']);
    exit;
}

$userId = verifyToken();

if (!checkRateLimit('ai_chat_' . $userId, 30, 3600)) {
    http_response_code(429);
    echo json_encode(['error' => 'Лимит AI-запросов исчерпан. Попробуйте через час.']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? 'chat';

try {
    if ($action === 'search') {
        handleAiSearch($input);
    } elseif ($action === 'chat') {
        handleAiChat($input, $userId);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Неизвестное действие']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка AI: ' . $e->getMessage()]);
}

function groqApiKey(): string {
    $key = env('GROQ_API_KEY', '');
    if (!$key) {
        http_response_code(503);
        echo json_encode(['error' => 'AI не настроен. Добавьте GROQ_API_KEY в .env']);
        exit;
    }
    return $key;
}

function groqModel(): string {
    return env('GROQ_MODEL', 'llama-3.3-70b-versatile');
}

function callGroq(array $messages, float $temperature = 0.4, int $maxTokens = 1200): string {
    $payload = json_encode([
        'model'       => groqModel(),
        'messages'    => $messages,
        'temperature' => $temperature,
        'max_tokens'  => $maxTokens,
    ], JSON_UNESCAPED_UNICODE);

    if (function_exists('curl_init')) {
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . groqApiKey(),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 45,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false || $code < 200 || $code >= 300) {
            throw new RuntimeException('Groq API: HTTP ' . $code . ' ' . substr((string)$resp, 0, 200));
        }
        $data = json_decode($resp, true);
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nAuthorization: Bearer " . groqApiKey() . "\r\n",
                'content' => $payload,
                'timeout' => 45,
            ],
        ]);
        $resp = @file_get_contents('https://api.groq.com/openai/v1/chat/completions', false, $ctx);
        if ($resp === false) throw new RuntimeException('Groq API недоступен');
        $data = json_decode($resp, true);
    }

    return trim($data['choices'][0]['message']['content'] ?? '');
}

function parseJsonFromAi(string $text) {
    $clean = preg_replace('/```json\s*|```/i', '', $text);
    $clean = trim($clean);
    $decoded = json_decode($clean, true);
    if (json_last_error() === JSON_ERROR_NONE) return $decoded;
    if (preg_match('/\[[\s\S]*\]/', $clean, $m)) {
        $decoded = json_decode($m[0], true);
        if (json_last_error() === JSON_ERROR_NONE) return $decoded;
    }
    if (preg_match('/\{[\s\S]*\}/', $clean, $m)) {
        $decoded = json_decode($m[0], true);
        if (json_last_error() === JSON_ERROR_NONE) return $decoded;
    }
    return null;
}

function handleAiSearch(array $input): void {
    $query    = trim($input['message'] ?? $input['query'] ?? '');
    $projects = $input['projects'] ?? [];
    if (!$query) {
        http_response_code(400);
        echo json_encode(['error' => 'Укажите запрос']);
        exit;
    }
    if (!is_array($projects) || !count($projects)) {
        echo json_encode(['matches' => []]);
        exit;
    }

    $projectsJson = json_encode(array_map(function ($p) {
        return [
            'id'          => (int)($p['id'] ?? 0),
            'title'       => $p['title'] ?? '',
            'description' => mb_substr($p['description'] ?? '', 0, 300),
            'category'    => $p['category'] ?? '',
            'stage'       => $p['stage'] ?? '',
            'tags'        => is_array($p['tags'] ?? null) ? implode(', ', $p['tags']) : ($p['tags'] ?? ''),
            'looking_for' => $p['looking_for'] ?? '',
        ];
    }, array_slice($projects, 0, 80)), JSON_UNESCAPED_UNICODE);

    $prompt = "Ты помогаешь найти подходящие проекты на платформе Mendflow для студентов.\n\n"
        . "Запрос: \"{$query}\"\n\nПроекты:\n{$projectsJson}\n\n"
        . "Верни ТОЛЬКО JSON массив (max 5): [{\"id\":number,\"score\":1-100,\"reason\":\"фраза на русском\"}]. "
        . "Сортировка по score. Если ничего не подходит — [].";

    $text    = callGroq([['role' => 'user', 'content' => $prompt]], 0.2, 600);
    $matches = parseJsonFromAi($text);
    if (!is_array($matches)) $matches = [];

    echo json_encode(['matches' => $matches], JSON_UNESCAPED_UNICODE);
}

function isSchemaPlaceholder(string $raw): bool {
    return (bool)preg_match('/product\|research|\.\.\.|idea\|prototype|"title"\s*:\s*"\.\.\."/i', $raw);
}

function isValidProjectDraft(?array $draft): bool {
    if (!is_array($draft)) return false;
    $title = trim((string)($draft['title'] ?? ''));
    if ($title === '' || $title === '...' || mb_strlen($title) < 2) return false;
    if (strpos($title, '|') !== false) return false;
    return true;
}

function isValidTasksDraft($draft): bool {
    if (!is_array($draft) || !$draft) return false;
    if (!isset($draft[0]) || !is_array($draft[0])) return false;
    $title = trim((string)($draft[0]['title'] ?? ''));
    return $title !== '' && $title !== '...';
}

function isValidRolesDraft($draft): bool {
    if (!is_array($draft) || !$draft) return false;
    if (!isset($draft[0]) || !is_array($draft[0])) return false;
    $title = trim((string)($draft[0]['title'] ?? ''));
    return $title !== '' && $title !== '...';
}

function extractStructuredFromReply(string $reply): array {
    $projectDraft = null;
    $tasksDraft   = null;
    $rolesDraft   = null;
    $cleanReply   = $reply;

    if (preg_match_all('/```(\w+)?\s*([\s\S]*?)```/i', $reply, $blocks, PREG_SET_ORDER)) {
        foreach ($blocks as $block) {
            $tag  = strtolower(trim($block[1] ?? 'json'));
            $raw  = trim($block[2]);
            if (!$raw || isSchemaPlaceholder($raw)) {
                $cleanReply = str_replace($block[0], '', $cleanReply);
                continue;
            }
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $cleanReply = str_replace($block[0], '', $cleanReply);
                continue;
            }
            if (!$projectDraft && ($tag === 'project' || isset($decoded['title'])) && isValidProjectDraft($decoded)) {
                $projectDraft = $decoded;
            } elseif (!$tasksDraft && ($tag === 'tasks' || (isset($decoded[0]['title']))) && isValidTasksDraft($decoded)) {
                $tasksDraft = $decoded;
            } elseif (!$rolesDraft && ($tag === 'roles' || (isset($decoded[0]['title']) && isset($decoded[0]['specialization']))) && isValidRolesDraft($decoded)) {
                $rolesDraft = $decoded;
            }
            $cleanReply = str_replace($block[0], '', $cleanReply);
        }
    }

    $cleanReply = preg_replace('/\n{3,}/', "\n\n", trim($cleanReply));
    return [$cleanReply, $projectDraft, $tasksDraft, $rolesDraft];
}

function buildSystemPrompt(array $ctx): string {
    $tab     = $ctx['tab'] ?? 'my';
    $project = $ctx['project'] ?? null;
    $my      = $ctx['my_projects'] ?? [];

    $lines = [
        'Ты AI-помощник Mendflow — платформы для студенческих проектов, стартапов и команд.',
        'Отвечай на русском, кратко и по делу. Используй markdown: **жирный**, списки.',
        'Ты помогаешь: придумать идею проекта, описание, задачи, роли в команде, посты в ленту, найти подходящие проекты.',
        '',
        'ВАЖНО — формат ответа:',
        '- Пользователь видит только обычный текст: советы, идеи, списки, шаги.',
        '- НИКОГДА не показывай JSON, схемы полей, шаблоны, блоки кода или технические инструкции.',
        '- Не пиши «заполни форму» с примером JSON — просто опиши идею словами.',
        '- Если предлагаешь конкретный новый проект, в самом конце (после текста) добавь один скрытый блок ```project с реальными заполненными полями: title, description, category, stage, tags, looking_for.',
        '- category — одно из: product, research, ai_ml, design, web, mobile, other.',
        '- stage — одно из: idea, prototype, mvp, beta, launched.',
        '- Если предлагаешь задачи — скрытый блок ```tasks с массивом [{title, status}].',
        '- Если анализируешь команду / кого не хватает — скрытый блок ```roles с массивом [{title, description, specialization}].',
        '- specialization — одно из: product_manager, frontend, backend, designer, ml_engineer, fullstack, analyst, qa, marketing, other.',
    ];

    if ($tab === 'my') {
        $lines[] = 'Пользователь на вкладке «Мои проекты».';
        if (count($my)) {
            $names = array_map(fn($p) => ($p['title'] ?? 'Без названия') . ' (' . ($p['stage'] ?? 'idea') . ')', array_slice($my, 0, 8));
            $lines[] = 'Его проекты: ' . implode('; ', $names);
            $lines[] = 'Если у пользователя уже есть проекты — советуй по ним, не предлагай создавать новый без запроса.';
        } else {
            $lines[] = 'У пользователя пока нет проектов — помоги начать первый.';
        }
    } elseif ($tab === 'explore') {
        $lines[] = 'Пользователь ищет проекты в каталоге.';
    } elseif ($tab === 'detail' && $project) {
        $lines[] = 'Открыт проект: «' . ($project['title'] ?? '') . '».';
        $lines[] = 'Описание: ' . mb_substr($project['description'] ?? '', 0, 400);
        $lines[] = 'Категория: ' . ($project['category'] ?? '') . ', стадия: ' . ($project['stage'] ?? '');
        if (!empty($project['looking_for'])) $lines[] = 'Ищут: ' . $project['looking_for'];
        if (!empty($project['open_roles'])) {
            $rtitles = array_map(fn($r) => $r['title'] ?? '', $project['open_roles']);
            $lines[] = 'Открытые роли: ' . implode(', ', array_filter($rtitles));
        }
        if (!empty($project['members'])) {
            $lines[] = 'Команда: ' . implode(', ', array_map(fn($m) => ($m['specialization_role'] ?? 'участник'), array_slice($project['members'], 0, 12)));
        }
        if (!empty($project['tags'])) {
            $tags = is_array($project['tags']) ? implode(', ', $project['tags']) : $project['tags'];
            $lines[] = 'Теги: ' . $tags;
        }
    }

    return implode("\n", $lines);
}

function handleAiChat(array $input, int $userId): void {
    $message = trim($input['message'] ?? '');
    if (!$message) {
        http_response_code(400);
        echo json_encode(['error' => 'Пустое сообщение']);
        exit;
    }

    $context = is_array($input['context'] ?? null) ? $input['context'] : [];
    $history = is_array($input['history'] ?? null) ? $input['history'] : [];

    $messages = [['role' => 'system', 'content' => buildSystemPrompt($context)]];

    foreach (array_slice($history, -10) as $h) {
        if (!is_array($h)) continue;
        $role = ($h['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
        $content = trim($h['content'] ?? '');
        if ($content) $messages[] = ['role' => $role, 'content' => $content];
    }

    $messages[] = ['role' => 'user', 'content' => $message];

    $reply = callGroq($messages, 0.5, 1500);

    [$reply, $projectDraft, $tasksDraft, $rolesDraft] = extractStructuredFromReply($reply);

    echo json_encode([
        'reply'         => $reply,
        'project_draft' => $projectDraft,
        'tasks_draft'   => $tasksDraft,
        'roles_draft'   => $rolesDraft,
    ], JSON_UNESCAPED_UNICODE);
}
