<?php
/**
 * moderation_lib.php — shared moderation helpers (reports, bans, content removal, alerts)
 */
require_once __DIR__ . '/db.php';

const MF_REPORT_REASONS = ['spam', 'insult', 'fraud', 'inappropriate', 'other'];
const MF_REPORT_TARGETS = [
    'post', 'comment', 'message', 'user',
    'project_task', 'project_task_comment', 'project_post', 'project_post_comment',
];

function mfModeratorPanelPasswordConfigured(): bool
{
    return (string)env('MODERATOR_PANEL_PASSWORD', '') !== '';
}

function mfVerifyModeratorPanelPassword(?string $password): bool
{
    $expected = (string)env('MODERATOR_PANEL_PASSWORD', '');
    if ($expected === '') {
        return false;
    }
    if ($password === null || $password === '') {
        return false;
    }
    return hash_equals($expected, $password);
}

function mfModeratorPasswordFromRequest(): ?string
{
    $header = trim($_SERVER['HTTP_X_MODERATOR_PASSWORD'] ?? '');
    return $header !== '' ? $header : null;
}

/** Require X-Moderator-Password header (after platform admin check). */
function mfRequireModeratorPanelPassword(): void
{
    if (!mfModeratorPanelPasswordConfigured()) {
        http_response_code(503);
        echo json_encode([
            'error'                  => 'MODERATOR_PANEL_PASSWORD не настроен на сервере',
            'panel_password_required'  => true,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!mfVerifyModeratorPanelPassword(mfModeratorPasswordFromRequest())) {
        http_response_code(403);
        echo json_encode([
            'error'                  => 'Неверный пароль панели модерации',
            'panel_password_required'  => true,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function mfReportReasonLabel(string $reason): string
{
    $map = [
        'spam'           => 'Спам',
        'insult'         => 'Оскорбления',
        'fraud'          => 'Мошенничество',
        'inappropriate'  => 'Неприемлемый контент',
        'other'          => 'Другое',
    ];
    return $map[$reason] ?? $reason;
}

function mfReportTargetLabel(string $type): string
{
    $map = [
        'post'                  => 'Пост',
        'comment'               => 'Комментарий',
        'message'               => 'Сообщение',
        'user'                  => 'Профиль',
        'project_task'          => 'Задача',
        'project_task_comment'  => 'Комментарий к задаче',
        'project_post'          => 'Пост в проекте',
        'project_post_comment'  => 'Комментарий в проекте',
    ];
    return $map[$type] ?? $type;
}

/** Audit log for admin actions. */
function ensureModerationLogSchema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    global $pdo;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS moderation_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(64) NOT NULL,
            target_type VARCHAR(32) NULL,
            target_id INT NULL,
            details TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_modlog_admin (admin_id),
            KEY idx_modlog_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {}
}

function mfModerationLog(PDO $pdo, int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void
{
    ensureModerationLogSchema();
    try {
        $pdo->prepare(
            'INSERT INTO moderation_logs (admin_id, action, target_type, target_id, details, created_at)
             VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())'
        )->execute([$adminId, $action, $targetType, $targetId, $details]);
    } catch (Throwable $e) {}
}

function ensurePlatformBanSchema(): void
{
    global $pdo;
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        if (function_exists('ensureUserAccountSchema')) {
            ensureUserAccountSchema();
        }
        $cols = tableColumns('users');
        if (!in_array('is_banned', $cols, true)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN is_banned TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!in_array('banned_at', $cols, true)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN banned_at DATETIME NULL');
        }
        if (!in_array('ban_reason', $cols, true)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN ban_reason VARCHAR(255) NULL');
        }
    } catch (Throwable $e) {}
}

function userIsNotBannedSql(string $alias = 'u'): string
{
    $cols = tableColumns('users');
    if (!in_array('is_banned', $cols, true)) {
        return '1=1';
    }
    return "({$alias}.is_banned = 0 OR {$alias}.is_banned IS NULL)";
}

function mfPlatformBanUser(PDO $pdo, int $adminId, int $userId, ?string $reason = null): bool
{
    if ($userId <= 0 || $userId === $adminId) {
        return false;
    }
    ensurePlatformBanSchema();
    $stmt = $pdo->prepare(
        'UPDATE users SET is_banned = 1, banned_at = UTC_TIMESTAMP(), ban_reason = ? WHERE id = ?'
    );
    $stmt->execute([$reason ?: null, $userId]);
    if (!$stmt->rowCount()) {
        return false;
    }
    $pdo->prepare('DELETE FROM sessions WHERE user_id = ?')->execute([$userId]);
    mfModerationLog($pdo, $adminId, 'platform_ban', 'user', $userId, $reason);
    return true;
}

function mfPlatformUnbanUser(PDO $pdo, int $adminId, int $userId): bool
{
    ensurePlatformBanSchema();
    $stmt = $pdo->prepare(
        'UPDATE users SET is_banned = 0, banned_at = NULL, ban_reason = NULL WHERE id = ?'
    );
    $stmt->execute([$userId]);
    if (!$stmt->rowCount()) {
        return false;
    }
    mfModerationLog($pdo, $adminId, 'platform_unban', 'user', $userId, null);
    return true;
}

/** Admin-initiated social block (admin blocks user on behalf of platform visibility). */
function mfAdminBlockUser(PDO $pdo, int $adminId, int $targetUserId): bool
{
    if ($targetUserId <= 0 || $targetUserId === $adminId) {
        return false;
    }
    ensureBlocksSchema();
    mfClearFriendshipBetween($pdo, $adminId, $targetUserId);
    $pdo->prepare(
        'INSERT IGNORE INTO user_blocks (blocker_id, blocked_id, created_at) VALUES (?, ?, UTC_TIMESTAMP())'
    )->execute([$adminId, $targetUserId]);
    mfModerationLog($pdo, $adminId, 'admin_block', 'user', $targetUserId, null);
    return true;
}

/** Preview + author for reported content. */
function mfReportTargetInfo(PDO $pdo, string $type, int $id): array
{
    $info = [
        'target_type'   => $type,
        'target_id'     => $id,
        'author_id'     => null,
        'author_name'   => null,
        'preview'       => null,
        'exists'        => false,
    ];
    if ($id <= 0) {
        return $info;
    }

    try {
        switch ($type) {
            case 'post':
                $cols = $pdo->query('SHOW COLUMNS FROM posts')->fetchAll(PDO::FETCH_COLUMN);
                $textCol = in_array('text', $cols, true) ? 'text' : 'content';
                $st = $pdo->prepare("SELECT p.user_id, p.{$textCol} AS body, u.first_name, u.last_name FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ? LIMIT 1");
                $st->execute([$id]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $info['exists'] = true;
                    $info['author_id'] = (int)$row['user_id'];
                    $info['author_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $info['preview'] = mb_substr(trim($row['body'] ?? ''), 0, 200);
                }
                break;

            case 'comment':
                $cols = $pdo->query('SHOW COLUMNS FROM comments')->fetchAll(PDO::FETCH_COLUMN);
                $textCol = in_array('text', $cols, true) ? 'text' : 'content';
                $st = $pdo->prepare("SELECT c.user_id, c.{$textCol} AS body, u.first_name, u.last_name FROM comments c JOIN users u ON u.id = c.user_id WHERE c.id = ? LIMIT 1");
                $st->execute([$id]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $info['exists'] = true;
                    $info['author_id'] = (int)$row['user_id'];
                    $info['author_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $info['preview'] = mb_substr(trim($row['body'] ?? ''), 0, 200);
                }
                break;

            case 'message':
                $st = $pdo->prepare('SELECT m.from_id, m.content, u.first_name, u.last_name FROM messages m JOIN users u ON u.id = m.from_id WHERE m.id = ? LIMIT 1');
                $st->execute([$id]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $info['exists'] = true;
                    $info['author_id'] = (int)$row['from_id'];
                    $info['author_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $info['preview'] = mb_substr(trim($row['content'] ?? ''), 0, 200);
                }
                break;

            case 'user':
                $st = $pdo->prepare('SELECT id, first_name, last_name, email, bio FROM users WHERE id = ? LIMIT 1');
                $st->execute([$id]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $info['exists'] = true;
                    $info['author_id'] = (int)$row['id'];
                    $info['author_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $info['preview'] = mb_substr(trim($row['bio'] ?? $row['email'] ?? ''), 0, 200);
                }
                break;

            case 'project_task':
                $st = $pdo->prepare('SELECT t.title, t.description, t.created_by, u.first_name, u.last_name FROM project_tasks t LEFT JOIN users u ON u.id = t.created_by WHERE t.id = ? LIMIT 1');
                $st->execute([$id]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $info['exists'] = true;
                    $info['author_id'] = (int)($row['created_by'] ?? 0) ?: null;
                    $info['author_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $info['preview'] = mb_substr(trim(($row['title'] ?? '') . ' ' . ($row['description'] ?? '')), 0, 200);
                }
                break;

            case 'project_task_comment':
                $st = $pdo->prepare('SELECT tc.author_id, tc.content, u.first_name, u.last_name FROM task_comments tc JOIN users u ON u.id = tc.author_id WHERE tc.id = ? LIMIT 1');
                $st->execute([$id]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $info['exists'] = true;
                    $info['author_id'] = (int)$row['author_id'];
                    $info['author_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $info['preview'] = mb_substr(trim($row['content'] ?? ''), 0, 200);
                }
                break;

            case 'project_post':
                $st = $pdo->prepare('SELECT pp.author_id, pp.content, pp.title, u.first_name, u.last_name FROM project_posts pp JOIN users u ON u.id = pp.author_id WHERE pp.id = ? LIMIT 1');
                $st->execute([$id]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $info['exists'] = true;
                    $info['author_id'] = (int)$row['author_id'];
                    $info['author_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $info['preview'] = mb_substr(trim(($row['title'] ?? '') . ' ' . ($row['content'] ?? '')), 0, 200);
                }
                break;

            case 'project_post_comment':
                $st = $pdo->prepare('SELECT c.author_id, c.content, u.first_name, u.last_name FROM project_post_comments c JOIN users u ON u.id = c.author_id WHERE c.id = ? LIMIT 1');
                $st->execute([$id]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $info['exists'] = true;
                    $info['author_id'] = (int)$row['author_id'];
                    $info['author_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    $info['preview'] = mb_substr(trim($row['content'] ?? ''), 0, 200);
                }
                break;
        }
    } catch (Throwable $e) {}

    return $info;
}

/** Delete reported content (platform admin). Returns ['ok'=>bool, 'error'=>?]. */
function mfModerationDeleteContent(PDO $pdo, string $type, int $id): array
{
    if ($id <= 0) {
        return ['ok' => false, 'error' => 'Invalid id'];
    }

    try {
        switch ($type) {
            case 'post':
                $st = $pdo->prepare('DELETE FROM posts WHERE id = ?');
                $st->execute([$id]);
                return ['ok' => $st->rowCount() > 0, 'error' => $st->rowCount() ? null : 'Not found'];

            case 'comment':
                $cols = $pdo->query('SHOW COLUMNS FROM comments')->fetchAll(PDO::FETCH_COLUMN);
                $table = in_array('id', $cols, true) ? 'comments' : 'comments';
                $st = $pdo->prepare("DELETE FROM {$table} WHERE id = ?");
                $st->execute([$id]);
                return ['ok' => $st->rowCount() > 0, 'error' => $st->rowCount() ? null : 'Not found'];

            case 'message':
                $st = $pdo->prepare('DELETE FROM messages WHERE id = ?');
                $st->execute([$id]);
                return ['ok' => $st->rowCount() > 0, 'error' => $st->rowCount() ? null : 'Not found'];

            case 'user':
                return ['ok' => false, 'error' => 'Use platform ban for users'];

            case 'project_task':
                $st = $pdo->prepare('DELETE FROM project_tasks WHERE id = ?');
                $st->execute([$id]);
                return ['ok' => $st->rowCount() > 0, 'error' => $st->rowCount() ? null : 'Not found'];

            case 'project_task_comment':
                $st = $pdo->prepare('DELETE FROM task_comments WHERE id = ?');
                $st->execute([$id]);
                return ['ok' => $st->rowCount() > 0, 'error' => $st->rowCount() ? null : 'Not found'];

            case 'project_post':
                $st = $pdo->prepare('DELETE FROM project_posts WHERE id = ?');
                $st->execute([$id]);
                return ['ok' => $st->rowCount() > 0, 'error' => $st->rowCount() ? null : 'Not found'];

            case 'project_post_comment':
                $st = $pdo->prepare('DELETE FROM project_post_comments WHERE id = ?');
                $st->execute([$id]);
                return ['ok' => $st->rowCount() > 0, 'error' => $st->rowCount() ? null : 'Not found'];

            default:
                return ['ok' => false, 'error' => 'Unknown type'];
        }
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Delete failed'];
    }
}

/** Emails of platform admins/moderators. */
function mfModeratorEmails(PDO $pdo): array
{
    $emails = [];
    $envList = env('MODERATOR_EMAILS', '');
    if ($envList) {
        foreach (preg_split('/[\s,;]+/', $envList) as $e) {
            $e = trim($e);
            if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $e;
            }
        }
    }
    if ($emails) {
        return array_values(array_unique($emails));
    }

    $cols = tableColumns('users');
    $checks = [];
    if (in_array('is_admin', $cols, true)) $checks[] = 'is_admin = 1';
    if (in_array('role', $cols, true)) $checks[] = "role IN ('admin', 'backoffice', 'moderator')";
    if (in_array('is_platform_admin', $cols, true)) $checks[] = 'is_platform_admin = 1';
    if (!$checks) {
        return [];
    }

    try {
        $activeSql = function_exists('userIsActiveSql') ? userIsActiveSql('u') : '1=1';
        $st = $pdo->query(
            'SELECT DISTINCT email FROM users u WHERE email IS NOT NULL AND email != "" AND ' .
            $activeSql . ' AND (' . implode(' OR ', $checks) . ') LIMIT 20'
        );
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $emails[] = $row['email'];
            }
        }
    } catch (Throwable $e) {}

    return array_values(array_unique($emails));
}

function mfSendTelegramModeratorAlert(string $text): bool
{
    $token = env('TELEGRAM_BOT_TOKEN', '');
    $chatId = env('TELEGRAM_MODERATOR_CHAT_ID', '');
    if (!$token || !$chatId) {
        return false;
    }
    $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
    $payload = json_encode([
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ]);
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 8,
        ],
    ]);
    $res = @file_get_contents($url, false, $ctx);
    if ($res === false) {
        return false;
    }
    $data = json_decode($res, true);
    return !empty($data['ok']);
}

/** Notify moderators about a new report (email + optional Telegram). */
function mfNotifyModeratorsNewReport(PDO $pdo, int $reportId, string $targetType, int $targetId, string $reason, ?string $comment, int $reporterId): void
{
    $reporterName = 'Пользователь #' . $reporterId;
    try {
        $st = $pdo->prepare('SELECT first_name, last_name, email FROM users WHERE id = ? LIMIT 1');
        $st->execute([$reporterId]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            $reporterName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: ($u['email'] ?? $reporterName);
        }
    } catch (Throwable $e) {}

    $targetLabel = mfReportTargetLabel($targetType);
    $reasonLabel = mfReportReasonLabel($reason);
    $preview = mfReportTargetInfo($pdo, $targetType, $targetId);
    $appUrl = rtrim(env('APP_URL', 'https://mendflow.us'), '/');
    $adminLink = $appUrl . '/#/admin';

    $subject = "[Mendflow] Новая жалоба #{$reportId}";
    $bodyHtml = '<p><strong>Новая жалоба #' . (int)$reportId . '</strong></p>'
        . '<p><strong>Кто:</strong> ' . htmlspecialchars($reporterName, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><strong>Объект:</strong> ' . htmlspecialchars($targetLabel, ENT_QUOTES, 'UTF-8') . ' #' . (int)$targetId . '</p>'
        . '<p><strong>Причина:</strong> ' . htmlspecialchars($reasonLabel, ENT_QUOTES, 'UTF-8') . '</p>';
    if ($comment) {
        $bodyHtml .= '<p><strong>Комментарий:</strong> ' . nl2br(htmlspecialchars($comment, ENT_QUOTES, 'UTF-8')) . '</p>';
    }
    if (!empty($preview['preview'])) {
        $bodyHtml .= '<p><strong>Фрагмент:</strong> ' . htmlspecialchars($preview['preview'], ENT_QUOTES, 'UTF-8') . '</p>';
    }
    $bodyHtml .= '<p><a href="' . htmlspecialchars($adminLink, ENT_QUOTES, 'UTF-8') . '">Открыть панель модерации</a></p>';

    if (file_exists(__DIR__ . '/mail.php')) {
        require_once __DIR__ . '/mail.php';
        foreach (mfModeratorEmails($pdo) as $email) {
            try {
                sendEmail($email, $subject, emailTemplate('Новая жалоба', $bodyHtml));
            } catch (Throwable $e) {}
        }
    }

    $tgText = "🚨 <b>Жалоба #{$reportId}</b>\n"
        . "От: " . htmlspecialchars($reporterName, ENT_QUOTES, 'UTF-8') . "\n"
        . "Объект: {$targetLabel} #{$targetId}\n"
        . "Причина: {$reasonLabel}\n";
    if ($comment) {
        $tgText .= "Коммент: " . htmlspecialchars(mb_substr($comment, 0, 300), ENT_QUOTES, 'UTF-8') . "\n";
    }
    $tgText .= "\n<a href=\"{$adminLink}\">Панель модерации</a>";
    mfSendTelegramModeratorAlert($tgText);
}

function mfAdminStats(PDO $pdo): array
{
    ensureReportsSchema();
    ensureModerationLogSchema();
    ensurePlatformBanSchema();

    $stats = [
        'users_total'       => 0,
        'users_banned'      => 0,
        'reports_new'       => 0,
        'reports_reviewed'  => 0,
        'reports_resolved'  => 0,
        'blocks_total'      => 0,
        'posts_total'       => 0,
        'logs_24h'          => 0,
    ];

    try {
        $stats['users_total'] = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    } catch (Throwable $e) {}
    try {
        $cols = tableColumns('users');
        if (in_array('is_banned', $cols, true)) {
            $stats['users_banned'] = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_banned = 1')->fetchColumn();
        }
    } catch (Throwable $e) {}
    try {
        $stats['reports_new'] = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'new'")->fetchColumn();
        $stats['reports_reviewed'] = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'reviewed'")->fetchColumn();
        $stats['reports_resolved'] = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'resolved'")->fetchColumn();
    } catch (Throwable $e) {}
    try {
        ensureBlocksSchema();
        $stats['blocks_total'] = (int)$pdo->query('SELECT COUNT(*) FROM user_blocks')->fetchColumn();
    } catch (Throwable $e) {}
    try {
        $stats['posts_total'] = (int)$pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    } catch (Throwable $e) {}
    try {
        $stats['logs_24h'] = (int)$pdo->query(
            'SELECT COUNT(*) FROM moderation_logs WHERE created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR)'
        )->fetchColumn();
    } catch (Throwable $e) {}

    return $stats;
}
