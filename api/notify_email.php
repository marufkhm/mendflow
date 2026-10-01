<?php
/**
 * Письма о новых уведомлениях Inbox.
 * Вызывается из лайков, комментариев, заявок и insertAppNotification.
 * Не блокирует ответ API: отправка уходит в shutdown после fastcgi_finish_request.
 */
require_once __DIR__ . '/mail.php';

function ensureInboxEmailSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $cols = $pdo->query("
            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
        ")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('notify_inbox_email', $cols, true)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN notify_inbox_email TINYINT(1) NOT NULL DEFAULT 1');
        }
    } catch (Throwable $e) {
    }
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS inbox_email_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            from_user_id INT DEFAULT NULL,
            ref_id INT NOT NULL DEFAULT 0,
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_iel_user_sent (user_id, sent_at),
            KEY idx_iel_dedup (user_id, type, ref_id, sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {
    }
}

function mfUserWantsInboxEmail(PDO $pdo, int $userId): bool
{
    ensureInboxEmailSchema($pdo);
    try {
        $st = $pdo->prepare('SELECT notify_inbox_email FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $val = $st->fetchColumn();
        if ($val === false) {
            return false;
        }
        return (int)$val !== 0;
    } catch (Throwable $e) {
        return true;
    }
}

function notifyInboxByEmail(
    PDO $pdo,
    int $userId,
    string $type,
    ?int $fromUserId,
    string $preview,
    int $refId = 0
): void {
    if ($userId <= 0) {
        return;
    }
    if ($fromUserId !== null && $fromUserId > 0 && $fromUserId === $userId) {
        return;
    }

    $GLOBALS['_mf_inbox_mail_jobs'][] = [
        'user_id'      => $userId,
        'type'         => $type,
        'from_user_id' => ($fromUserId !== null && $fromUserId > 0) ? $fromUserId : null,
        'preview'      => $preview,
        'ref_id'       => $refId,
    ];

    static $registered = false;
    if ($registered) {
        return;
    }
    $registered = true;
    register_shutdown_function(static function () use ($pdo) {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        foreach ($GLOBALS['_mf_inbox_mail_jobs'] ?? [] as $job) {
            try {
                mfSendInboxEmailNow(
                    $pdo,
                    (int)$job['user_id'],
                    (string)$job['type'],
                    $job['from_user_id'] !== null ? (int)$job['from_user_id'] : null,
                    (string)$job['preview'],
                    (int)$job['ref_id']
                );
            } catch (Throwable $e) {
            }
        }
        $GLOBALS['_mf_inbox_mail_jobs'] = [];
    });
}

function mfInboxEmailTypeLabel(string $type): string
{
    return [
        'like'                 => 'лайк на ваш пост',
        'comment'              => 'комментарий к вашему посту',
        'repost'               => 'репост вашего поста',
        'friend_request'       => 'заявку в друзья',
        'job_application'      => 'отклик на вакансию',
        'job_status'           => 'обновление статуса отклика',
        'feature_status'       => 'обновление идеи',
        'task_assigned'        => 'назначение задачи',
        'task_due_soon'        => 'приближающийся срок задачи',
        'task_overdue'         => 'просроченную задачу',
        'task_commented'       => 'комментарий к задаче',
        'task_status_changed'  => 'смену статуса задачи',
        'task_mentioned'       => 'упоминание в задаче',
        'post_mentioned'       => 'упоминание в публикации',
        'university_join'      => 'нового студента вуза',
    ][$type] ?? 'новое уведомление';
}

function mfSendInboxEmailNow(
    PDO $pdo,
    int $userId,
    string $type,
    ?int $fromUserId,
    string $preview,
    int $refId
): void {
    ensureInboxEmailSchema($pdo);

    if (!mfUserWantsInboxEmail($pdo, $userId)) {
        return;
    }

    $userCols = $pdo->query("
        SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
    ")->fetchAll(PDO::FETCH_COLUMN);
    $select = ['id', 'email', 'first_name'];
    foreach (['last_seen', 'email_verified_at', 'notify_inbox_email'] as $col) {
        if (in_array($col, $userCols, true)) {
            $select[] = $col;
        }
    }
    $recipient = $pdo->prepare('SELECT ' . implode(', ', $select) . ' FROM users WHERE id = ? LIMIT 1');
    $recipient->execute([$userId]);
    $user = $recipient->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        return;
    }

    $email = trim((string)($user['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    if (in_array('email_verified_at', $select, true) && empty($user['email_verified_at'])) {
        return;
    }

    if (in_array('last_seen', $select, true) && !empty($user['last_seen'])) {
        $online = $pdo->prepare('
            SELECT 1 FROM users
            WHERE id = ? AND last_seen > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE)
            LIMIT 1
        ');
        $online->execute([$userId]);
        if ($online->fetchColumn()) {
            return;
        }
    }

    $fromId = $fromUserId ?: 0;
    $stDup = $pdo->prepare('
        SELECT 1 FROM inbox_email_log
        WHERE user_id = ? AND type = ? AND COALESCE(from_user_id, 0) = ? AND ref_id = ?
          AND sent_at > DATE_SUB(NOW(), INTERVAL 6 HOUR)
        LIMIT 1
    ');
    $stDup->execute([$userId, $type, $fromId, $refId]);
    if ($stDup->fetchColumn()) {
        return;
    }

    $stDay = $pdo->prepare('
        SELECT COUNT(*) FROM inbox_email_log
        WHERE user_id = ? AND sent_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
    ');
    $stDay->execute([$userId]);
    if ((int)$stDay->fetchColumn() >= 10) {
        return;
    }

    if (in_array($type, ['like', 'repost'], true)) {
        $stLike = $pdo->prepare("
            SELECT 1 FROM inbox_email_log
            WHERE user_id = ? AND type IN ('like', 'repost')
              AND sent_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)
            LIMIT 1
        ");
        $stLike->execute([$userId]);
        if ($stLike->fetchColumn()) {
            return;
        }
    }

    $actorName = 'Кто-то';
    if ($fromId > 0) {
        $stFrom = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id = ? LIMIT 1');
        $stFrom->execute([$fromId]);
        $from = $stFrom->fetch(PDO::FETCH_ASSOC);
        if ($from) {
            $actorName = trim((string)$from['first_name'] . ' ' . (string)$from['last_name']) ?: $actorName;
        }
    } elseif (in_array($type, ['task_due_soon', 'task_overdue'], true)) {
        $actorName = 'Mendflow';
    }

    $label   = mfInboxEmailTypeLabel($type);
    $preview = htmlspecialchars(mb_substr(trim($preview), 0, 160), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $safeActor = htmlspecialchars($actorName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $first   = htmlspecialchars((string)($user['first_name'] ?: 'друг'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $appUrl  = rtrim((string)(function_exists('mfAppOrigin') ? mfAppOrigin() : env('APP_URL', 'https://mendflow.us')), '/');
    if ($appUrl === '') {
        $appUrl = 'https://mendflow.us';
    }
    $inboxUrl = $appUrl . '/#/feed';

    $extra = $preview !== '' ? "<p style=\"margin:16px 0;padding:12px 14px;background:#f6f1f8;border-radius:10px\">{$preview}</p>" : '';
    $html = emailTemplate(
        'Новое уведомление',
        "<p>Здравствуйте, <strong>{$first}</strong>!</p>
         <p><strong>{$safeActor}</strong> — {$label}.</p>
         {$extra}
         <p style=\"margin:24px 0\"><a href=\"{$inboxUrl}\" style=\"display:inline-block;padding:12px 24px;background:#7c3aed;color:#fff;text-decoration:none;border-radius:10px;font-weight:600\">Открыть Inbox</a></p>
         <p style=\"font-size:12px;color:#9ca3af\">Письмо приходит, только если вас не было на сайте. Отключить: Настройки профиля → Уведомления.</p>"
    );

    $subject = $actorName . ' — ' . $label . ' в Mendflow';
    $result  = sendEmail($email, $subject, $html);
    if (empty($result['ok'])) {
        return;
    }

    $pdo->prepare('
        INSERT INTO inbox_email_log (user_id, type, from_user_id, ref_id, sent_at)
        VALUES (?, ?, ?, ?, NOW())
    ')->execute([$userId, $type, $fromId > 0 ? $fromId : null, $refId]);
}
