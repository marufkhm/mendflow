<?php

declare(strict_types=1);

final class MessageService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function list(array $filters): array
    {
        $currentEmail = SessionGuard::requireAuthenticated();
        $peerEmail = strtolower(trim((string) ($filters['peer'] ?? '')));
        if ($peerEmail === '' || !filter_var($peerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Укажи корректный email собеседника.');
        }

        $currentUser = $this->findUserByEmail($currentEmail);
        $peerUser = $this->findUserByEmail($peerEmail);
        $threadId = $this->findThreadId((int) $currentUser['id'], (int) $peerUser['id']);

        $messages = [];
        if ($threadId !== null) {
            $stmt = $this->pdo->prepare('
                SELECT
                    m.id,
                    m.body,
                    m.attachment_path,
                    m.attachment_name,
                    m.attachment_kind,
                    m.created_at,
                    u.email AS sender_email
                FROM messages m
                LEFT JOIN users u ON u.id = m.sender_user_id
                WHERE m.thread_id = :thread_id
                ORDER BY m.created_at ASC, m.id ASC
            ');
            $stmt->execute(['thread_id' => $threadId]);
            $rows = $stmt->fetchAll();

            $messages = array_map(static function (array $row) use ($currentEmail): array {
                $senderEmail = (string) ($row['sender_email'] ?? '');
                $attachmentPath = (string) ($row['attachment_path'] ?? '');
                $attachmentName = (string) ($row['attachment_name'] ?? '');
                $attachmentKind = (string) ($row['attachment_kind'] ?? '');

                return [
                    'id' => (int) $row['id'],
                    'direction' => $senderEmail === $currentEmail ? 'outgoing' : 'incoming',
                    'text' => (string) ($row['body'] ?? ''),
                    'createdAt' => (string) ($row['created_at'] ?? ''),
                    'attachment' => $attachmentPath !== '' ? [
                        'path' => $attachmentPath,
                        'name' => $attachmentName,
                        'kind' => $attachmentKind,
                    ] : null,
                ];
            }, $rows);
        }

        return [
            'ok' => true,
            'messages' => $messages,
        ];
    }

    public function send(array $payload, array $files): array
    {
        $currentEmail = SessionGuard::requireAuthenticated();
        $peerEmail = strtolower(trim((string) ($payload['peer'] ?? '')));
        $text = trim((string) ($payload['text'] ?? ''));

        if ($peerEmail === '' || !filter_var($peerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Укажи корректный email собеседника.');
        }

        if ($peerEmail === $currentEmail) {
            throw new RuntimeException('Нельзя отправить сообщение самому себе.');
        }

        $currentUser = $this->findUserByEmail($currentEmail);
        $peerUser = $this->findUserByEmail($peerEmail);

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentKind = null;

        if (isset($files['attachment']) && ($files['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $attachmentPath = UploadStorage::saveUploadedFile($files['attachment'], 'messages');
            $attachmentName = (string) ($files['attachment']['name'] ?? '');
            $mime = (string) ($files['attachment']['type'] ?? '');
            $attachmentKind = str_starts_with($mime, 'image/')
                ? 'image'
                : (str_starts_with($mime, 'video/') ? 'video' : 'document');
        }

        if ($text === '' && $attachmentPath === null) {
            throw new RuntimeException('Добавь текст или вложение.');
        }

        $threadId = $this->getOrCreateThreadId((int) $currentUser['id'], (int) $peerUser['id']);

        $stmt = $this->pdo->prepare('
            INSERT INTO messages (
                thread_id,
                sender_user_id,
                body,
                attachment_path,
                attachment_name,
                attachment_kind
            ) VALUES (
                :thread_id,
                :sender_user_id,
                :body,
                :attachment_path,
                :attachment_name,
                :attachment_kind
            )
        ');
        $stmt->execute([
            'thread_id' => $threadId,
            'sender_user_id' => (int) $currentUser['id'],
            'body' => $text !== '' ? $text : null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_kind' => $attachmentKind,
        ]);

        return [
            'ok' => true,
            'message' => 'Сообщение отправлено.',
        ];
    }

    private function findUserByEmail(string $email): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, email, first_name, last_name
            FROM users
            WHERE email = :email
            LIMIT 1
        ');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new RuntimeException('Пользователь не найден.');
        }

        return $user;
    }

    private function findThreadId(int $firstUserId, int $secondUserId): ?int
    {
        $threadKey = $this->buildThreadKey($firstUserId, $secondUserId);
        $stmt = $this->pdo->prepare('SELECT id FROM message_threads WHERE thread_key = :thread_key LIMIT 1');
        $stmt->execute(['thread_key' => $threadKey]);
        $row = $stmt->fetch();

        return $row ? (int) $row['id'] : null;
    }

    private function getOrCreateThreadId(int $firstUserId, int $secondUserId): int
    {
        $existingId = $this->findThreadId($firstUserId, $secondUserId);
        if ($existingId !== null) {
            return $existingId;
        }

        $threadKey = $this->buildThreadKey($firstUserId, $secondUserId);
        $title = 'DM ' . $threadKey;

        $this->pdo->beginTransaction();

        try {
            $insertThread = $this->pdo->prepare('
                INSERT INTO message_threads (thread_key, title)
                VALUES (:thread_key, :title)
            ');
            $insertThread->execute([
                'thread_key' => $threadKey,
                'title' => $title,
            ]);

            $threadId = (int) $this->pdo->lastInsertId();

            $insertParticipant = $this->pdo->prepare('
                INSERT INTO message_thread_participants (thread_id, user_id)
                VALUES (:thread_id, :user_id)
            ');

            $insertParticipant->execute([
                'thread_id' => $threadId,
                'user_id' => $firstUserId,
            ]);
            $insertParticipant->execute([
                'thread_id' => $threadId,
                'user_id' => $secondUserId,
            ]);

            $this->pdo->commit();

            return $threadId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function buildThreadKey(int $firstUserId, int $secondUserId): string
    {
        $ids = [$firstUserId, $secondUserId];
        sort($ids, SORT_NUMERIC);
        return 'dm:' . $ids[0] . '|' . $ids[1];
    }
}
