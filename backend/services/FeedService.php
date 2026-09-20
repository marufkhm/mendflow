<?php

declare(strict_types=1);

final class FeedService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function list(array $filters): array
    {
        $currentEmail = SessionGuard::requireAuthenticated();
        $currentUser = $this->findUserByEmail($currentEmail);
        $currentUserId = (int) $currentUser['id'];

        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(12, max(1, (int) ($filters['limit'] ?? 5)));
        $offset = ($page - 1) * $limit;
        $feedMode = (string) ($filters['mode'] ?? 'recommendations');
        $postType = (string) ($filters['type'] ?? 'all');
        $query = trim((string) ($filters['query'] ?? ''));

        $allowedModes = ['recommendations', 'subscriptions'];
        $allowedTypes = ['all', 'post', 'project'];
        if (!in_array($feedMode, $allowedModes, true)) {
            $feedMode = 'recommendations';
        }
        if (!in_array($postType, $allowedTypes, true)) {
            $postType = 'all';
        }

        $conditions = [];
        $params = [
            'current_user_id_like' => $currentUserId,
            'limit_plus_one' => $limit + 1,
            'offset_value' => $offset,
        ];

        if ($feedMode === 'subscriptions') {
            $conditions[] = '(p.user_id = :current_user_id OR EXISTS (
                SELECT 1
                FROM follows f
                WHERE f.follower_user_id = :current_user_id_follow
                  AND f.followed_user_id = p.user_id
            ))';
            $params['current_user_id'] = $currentUserId;
            $params['current_user_id_follow'] = $currentUserId;
        }

        if ($postType !== 'all') {
            $conditions[] = 'p.post_type = :post_type';
            $params['post_type'] = $postType;
        }

        if ($query !== '') {
            $conditions[] = '(
                p.body LIKE :query_body
                OR CONCAT(u.first_name, " ", u.last_name) LIKE :query_author
                OR EXISTS (
                    SELECT 1
                    FROM post_tags ptq
                    WHERE ptq.post_id = p.id
                      AND ptq.tag_slug LIKE :query_tag
                )
            )';
            $params['query_body'] = '%' . $query . '%';
            $params['query_author'] = '%' . $query . '%';
            $params['query_tag'] = '%' . $query . '%';
        }

        $whereSql = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $stmt = $this->pdo->prepare("
            SELECT
                p.id,
                p.body,
                p.post_type,
                p.feed_mode,
                p.attachment_path,
                p.attachment_name,
                p.attachment_kind,
                p.created_at,
                p.updated_at,
                u.id AS author_id,
                u.email AS author_email,
                u.first_name AS author_first_name,
                u.last_name AS author_last_name,
                pr.organization AS author_organization,
                pr.specialty AS author_specialty,
                pr.education_level AS author_education,
                pr.country_code AS author_country_code,
                pr.country_name AS author_country_name,
                pr.avatar_path AS author_avatar_path,
                pr.style_font AS author_style_font,
                pr.style_accent_color AS author_style_accent_color,
                pr.style_card_color AS author_style_card_color,
                (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) AS likes_count,
                (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id) AS comments_count,
                EXISTS(
                    SELECT 1
                    FROM post_likes pll
                    WHERE pll.post_id = p.id
                      AND pll.user_id = :current_user_id_like
                ) AS liked_by_me
            FROM posts p
            INNER JOIN users u ON u.id = p.user_id
            LEFT JOIN profiles pr ON pr.user_id = u.id
            $whereSql
            ORDER BY p.created_at DESC, p.id DESC
            LIMIT :limit_plus_one OFFSET :offset_value
        ");

        foreach ($params as $key => $value) {
            $type = in_array($key, ['current_user_id', 'limit_plus_one', 'offset_value'], true)
                ? PDO::PARAM_INT
                : PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $type);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $hasMore = count($rows) > $limit;
        $rows = array_slice($rows, 0, $limit);
        $postIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $tagsByPostId = $this->loadTagsByPostIds($postIds);

        $items = array_map(function (array $row) use ($currentEmail, $tagsByPostId): array {
            $postId = (int) $row['id'];
            $authorEmail = (string) ($row['author_email'] ?? '');
            $tags = $tagsByPostId[$postId] ?? [];

            return [
                'id' => $postId,
                'text' => (string) ($row['body'] ?? ''),
                'type' => (string) ($row['post_type'] ?? 'post'),
                'feedMode' => (string) ($row['feed_mode'] ?? 'recommendations'),
                'interestMode' => $tags[0] ?? 'design',
                'tags' => $tags,
                'likes' => (int) ($row['likes_count'] ?? 0),
                'likedByMe' => ((int) ($row['liked_by_me'] ?? 0)) === 1,
                'commentsCount' => (int) ($row['comments_count'] ?? 0),
                'createdAt' => (string) ($row['created_at'] ?? ''),
                'updatedAt' => (string) ($row['updated_at'] ?? ''),
                'ownPost' => $authorEmail === $currentEmail,
                'authorKey' => $authorEmail === $currentEmail ? 'me' : ('account:' . strtolower($authorEmail)),
                'author' => [
                    'firstName' => (string) ($row['author_first_name'] ?? ''),
                    'lastName' => (string) ($row['author_last_name'] ?? ''),
                    'email' => $authorEmail,
                    'organization' => (string) ($row['author_organization'] ?? ''),
                    'specialty' => (string) ($row['author_specialty'] ?? ''),
                    'education' => (string) ($row['author_education'] ?? ''),
                    'countryCode' => (string) ($row['author_country_code'] ?? ''),
                    'countryName' => (string) ($row['author_country_name'] ?? ''),
                    'photo' => (string) ($row['author_avatar_path'] ?? ''),
                    'style' => [
                        'fontTheme' => (string) ($row['author_style_font'] ?? 'manrope') ?: 'manrope',
                        'accentColor' => (string) ($row['author_style_accent_color'] ?? '#e78479') ?: '#e78479',
                        'cardColor' => (string) ($row['author_style_card_color'] ?? '#f1e4d0') ?: '#f1e4d0',
                    ],
                ],
                'attachment' => ((string) ($row['attachment_path'] ?? '')) !== '' ? [
                    'path' => (string) $row['attachment_path'],
                    'name' => (string) ($row['attachment_name'] ?? ''),
                    'kind' => (string) ($row['attachment_kind'] ?? 'document'),
                ] : null,
            ];
        }, $rows);

        return [
            'ok' => true,
            'items' => $items,
            'page' => $page,
            'limit' => $limit,
            'hasMore' => $hasMore,
        ];
    }

    public function create(array $payload, array $files): array
    {
        $currentEmail = SessionGuard::requireAuthenticated();
        $user = $this->findUserByEmail($currentEmail);
        $userId = (int) $user['id'];

        $body = trim((string) ($payload['text'] ?? ''));
        $type = (string) ($payload['type'] ?? 'post');
        $feedMode = (string) ($payload['feedMode'] ?? 'recommendations');
        $tags = $this->sanitizeTags($payload['tags'] ?? []);

        if (!in_array($type, ['post', 'project'], true)) {
            throw new RuntimeException('Недопустимый тип публикации.', 422);
        }
        if (!in_array($feedMode, ['recommendations', 'subscriptions'], true)) {
            $feedMode = 'recommendations';
        }

        $attachment = $this->storeAttachment($files['attachment'] ?? null);
        if ($body === '' && $attachment === null) {
            throw new RuntimeException('Добавь текст или вложение.', 422);
        }

        if ($tags === []) {
            $tags = $this->loadUserInterests($userId);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO posts (
                user_id,
                body,
                post_type,
                feed_mode,
                attachment_path,
                attachment_name,
                attachment_kind
            ) VALUES (
                :user_id,
                :body,
                :post_type,
                :feed_mode,
                :attachment_path,
                :attachment_name,
                :attachment_kind
            )
        ');
        $stmt->execute([
            'user_id' => $userId,
            'body' => $body !== '' ? $body : null,
            'post_type' => $type,
            'feed_mode' => $feedMode,
            'attachment_path' => $attachment['path'] ?? null,
            'attachment_name' => $attachment['name'] ?? null,
            'attachment_kind' => $attachment['kind'] ?? null,
        ]);

        $postId = (int) $this->pdo->lastInsertId();
        $this->syncTags($postId, $tags);

        return [
            'ok' => true,
            'message' => 'Пост опубликован.',
            'post' => $this->getPostById($postId, $currentEmail),
        ];
    }

    public function like(int $postId): array
    {
        $currentEmail = SessionGuard::requireAuthenticated();
        $user = $this->findUserByEmail($currentEmail);
        $this->ensurePostExists($postId);

        $existsStmt = $this->pdo->prepare('
            SELECT id
            FROM post_likes
            WHERE post_id = :post_id AND user_id = :user_id
            LIMIT 1
        ');
        $existsStmt->execute([
            'post_id' => $postId,
            'user_id' => (int) $user['id'],
        ]);
        $liked = (bool) $existsStmt->fetch();

        if ($liked) {
            $deleteStmt = $this->pdo->prepare('
                DELETE FROM post_likes
                WHERE post_id = :post_id AND user_id = :user_id
            ');
            $deleteStmt->execute([
                'post_id' => $postId,
                'user_id' => (int) $user['id'],
            ]);
            $liked = false;
        } else {
            $insertStmt = $this->pdo->prepare('
                INSERT INTO post_likes (post_id, user_id)
                VALUES (:post_id, :user_id)
            ');
            $insertStmt->execute([
                'post_id' => $postId,
                'user_id' => (int) $user['id'],
            ]);
            $liked = true;
        }

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM post_likes WHERE post_id = :post_id');
        $countStmt->execute(['post_id' => $postId]);
        $row = $countStmt->fetch();

        return [
            'ok' => true,
            'likedByMe' => $liked,
            'likes' => (int) ($row['total'] ?? 0),
        ];
    }

    public function addComment(int $postId, string $body): array
    {
        $currentEmail = SessionGuard::requireAuthenticated();
        $user = $this->findUserByEmail($currentEmail);
        $text = trim($body);

        if ($text === '') {
            throw new RuntimeException('Комментарий не может быть пустым.', 422);
        }

        $this->ensurePostExists($postId);

        $stmt = $this->pdo->prepare('
            INSERT INTO post_comments (post_id, user_id, body)
            VALUES (:post_id, :user_id, :body)
        ');
        $stmt->execute([
            'post_id' => $postId,
            'user_id' => (int) $user['id'],
            'body' => $text,
        ]);

        $commentId = (int) $this->pdo->lastInsertId();

        return [
            'ok' => true,
            'message' => 'Комментарий добавлен.',
            'comment' => $this->getCommentById($commentId),
        ];
    }

    public function listComments(int $postId): array
    {
        SessionGuard::requireAuthenticated();
        $this->ensurePostExists($postId);

        $stmt = $this->pdo->prepare('
            SELECT
                pc.id,
                pc.body,
                pc.created_at,
                u.first_name,
                u.last_name,
                pr.avatar_path
            FROM post_comments pc
            INNER JOIN users u ON u.id = pc.user_id
            LEFT JOIN profiles pr ON pr.user_id = u.id
            WHERE pc.post_id = :post_id
            ORDER BY pc.created_at ASC, pc.id ASC
        ');
        $stmt->execute(['post_id' => $postId]);

        $comments = array_map([$this, 'mapCommentRow'], $stmt->fetchAll());

        return [
            'ok' => true,
            'comments' => $comments,
        ];
    }

    public function update(int $postId, array $payload): array
    {
        $currentEmail = SessionGuard::requireAuthenticated();
        $user = $this->findUserByEmail($currentEmail);
        $post = $this->findPostRowById($postId);

        if ((int) $post['user_id'] !== (int) $user['id']) {
            throw new RuntimeException('Нельзя редактировать чужой пост.', 403);
        }

        $body = trim((string) ($payload['text'] ?? ''));
        if ($body === '' && (string) ($post['attachment_path'] ?? '') === '') {
            throw new RuntimeException('Добавь текст или вложение.', 422);
        }

        $stmt = $this->pdo->prepare('
            UPDATE posts
            SET body = :body, updated_at = NOW()
            WHERE id = :post_id
        ');
        $stmt->execute([
            'body' => $body !== '' ? $body : null,
            'post_id' => $postId,
        ]);

        return [
            'ok' => true,
            'message' => 'Пост обновлен.',
            'post' => $this->getPostById($postId, $currentEmail),
        ];
    }

    public function delete(int $postId): array
    {
        $currentEmail = SessionGuard::requireAuthenticated();
        $user = $this->findUserByEmail($currentEmail);
        $post = $this->findPostRowById($postId);

        if ((int) $post['user_id'] !== (int) $user['id']) {
            throw new RuntimeException('Нельзя удалить чужой пост.', 403);
        }

        $stmt = $this->pdo->prepare('DELETE FROM posts WHERE id = :post_id');
        $stmt->execute(['post_id' => $postId]);

        return [
            'ok' => true,
            'message' => 'Пост удален.',
        ];
    }

    private function getPostById(int $postId, string $currentEmail): array
    {
        $result = $this->list([
            'page' => 1,
            'limit' => 100,
        ]);

        foreach ($result['items'] as $item) {
            if ((int) $item['id'] === $postId) {
                return $item;
            }
        }

        $post = $this->findPostRowById($postId);
        $authorEmail = (string) ($post['author_email'] ?? $currentEmail);
        return [
            'id' => $postId,
            'text' => (string) ($post['body'] ?? ''),
            'type' => (string) ($post['post_type'] ?? 'post'),
            'feedMode' => (string) ($post['feed_mode'] ?? 'recommendations'),
            'interestMode' => 'design',
            'tags' => [],
            'likes' => 0,
            'likedByMe' => false,
            'commentsCount' => 0,
            'createdAt' => (string) ($post['created_at'] ?? ''),
            'updatedAt' => (string) ($post['updated_at'] ?? ''),
            'ownPost' => $authorEmail === $currentEmail,
            'authorKey' => $authorEmail === $currentEmail ? 'me' : ('account:' . strtolower($authorEmail)),
            'author' => [
                'firstName' => (string) ($post['author_first_name'] ?? ''),
                'lastName' => (string) ($post['author_last_name'] ?? ''),
                'email' => $authorEmail,
                'organization' => (string) ($post['author_organization'] ?? ''),
                'specialty' => (string) ($post['author_specialty'] ?? ''),
                'education' => (string) ($post['author_education'] ?? ''),
                'countryCode' => (string) ($post['author_country_code'] ?? ''),
                'countryName' => (string) ($post['author_country_name'] ?? ''),
                'photo' => (string) ($post['author_avatar_path'] ?? ''),
                'style' => [
                    'fontTheme' => (string) ($post['author_style_font'] ?? 'manrope') ?: 'manrope',
                    'accentColor' => (string) ($post['author_style_accent_color'] ?? '#e78479') ?: '#e78479',
                    'cardColor' => (string) ($post['author_style_card_color'] ?? '#f1e4d0') ?: '#f1e4d0',
                ],
            ],
            'attachment' => ((string) ($post['attachment_path'] ?? '')) !== '' ? [
                'path' => (string) $post['attachment_path'],
                'name' => (string) ($post['attachment_name'] ?? ''),
                'kind' => (string) ($post['attachment_kind'] ?? 'document'),
            ] : null,
        ];
    }

    private function ensurePostExists(int $postId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM posts WHERE id = :post_id LIMIT 1');
        $stmt->execute(['post_id' => $postId]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('Пост не найден.', 404);
        }
    }

    private function findPostRowById(int $postId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                p.*,
                u.email AS author_email,
                u.first_name AS author_first_name,
                u.last_name AS author_last_name,
                pr.organization AS author_organization,
                pr.specialty AS author_specialty,
                pr.education_level AS author_education,
                pr.country_code AS author_country_code,
                pr.country_name AS author_country_name,
                pr.avatar_path AS author_avatar_path,
                pr.style_font AS author_style_font,
                pr.style_accent_color AS author_style_accent_color,
                pr.style_card_color AS author_style_card_color
            FROM posts p
            INNER JOIN users u ON u.id = p.user_id
            LEFT JOIN profiles pr ON pr.user_id = u.id
            WHERE p.id = :post_id
            LIMIT 1
        ');
        $stmt->execute(['post_id' => $postId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Пост не найден.', 404);
        }

        return $row;
    }

    private function loadTagsByPostIds(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($postIds), '?'));
        $stmt = $this->pdo->prepare("
            SELECT post_id, tag_slug
            FROM post_tags
            WHERE post_id IN ($placeholders)
            ORDER BY id ASC
        ");
        $stmt->execute($postIds);

        $tagsByPostId = [];
        foreach ($stmt->fetchAll() as $row) {
            $postId = (int) ($row['post_id'] ?? 0);
            $tag = (string) ($row['tag_slug'] ?? '');
            if ($postId <= 0 || $tag === '') {
                continue;
            }
            $tagsByPostId[$postId] ??= [];
            $tagsByPostId[$postId][] = $tag;
        }

        return $tagsByPostId;
    }

    private function loadUserInterests(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT interest_slug
            FROM profile_interests
            WHERE user_id = :user_id
            ORDER BY id ASC
            LIMIT 3
        ');
        $stmt->execute(['user_id' => $userId]);

        return array_map(
            static fn (array $row): string => (string) $row['interest_slug'],
            $stmt->fetchAll()
        );
    }

    private function sanitizeTags(mixed $tags): array
    {
        if (!is_array($tags)) {
            return [];
        }

        $allowed = ['design', 'frontend', 'ai', 'product', 'data'];
        $result = [];
        foreach ($tags as $tag) {
            $value = strtolower(trim((string) $tag));
            if ($value === '' || !in_array($value, $allowed, true) || in_array($value, $result, true)) {
                continue;
            }
            $result[] = $value;
            if (count($result) >= 3) {
                break;
            }
        }
        return $result;
    }

    private function syncTags(int $postId, array $tags): void
    {
        $deleteStmt = $this->pdo->prepare('DELETE FROM post_tags WHERE post_id = :post_id');
        $deleteStmt->execute(['post_id' => $postId]);

        if ($tags === []) {
            return;
        }

        $insertStmt = $this->pdo->prepare('
            INSERT INTO post_tags (post_id, tag_slug)
            VALUES (:post_id, :tag_slug)
        ');
        foreach ($tags as $tag) {
            $insertStmt->execute([
                'post_id' => $postId,
                'tag_slug' => $tag,
            ]);
        }
    }

    private function storeAttachment(?array $file): ?array
    {
        if ($file === null) {
            return null;
        }

        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $path = UploadStorage::saveUploadedFile($file, 'posts');
        $mime = (string) ($file['type'] ?? '');

        return [
            'path' => $path,
            'name' => (string) ($file['name'] ?? ''),
            'kind' => str_starts_with($mime, 'image/')
                ? 'image'
                : (str_starts_with($mime, 'video/') ? 'video' : 'document'),
        ];
    }

    private function getCommentById(int $commentId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                pc.id,
                pc.body,
                pc.created_at,
                u.first_name,
                u.last_name,
                pr.avatar_path
            FROM post_comments pc
            INNER JOIN users u ON u.id = pc.user_id
            LEFT JOIN profiles pr ON pr.user_id = u.id
            WHERE pc.id = :comment_id
            LIMIT 1
        ');
        $stmt->execute(['comment_id' => $commentId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Комментарий не найден.', 404);
        }

        return $this->mapCommentRow($row);
    }

    private function mapCommentRow(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'author' => trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')),
            'text' => (string) ($row['body'] ?? ''),
            'createdAt' => (string) ($row['created_at'] ?? ''),
            'photo' => (string) ($row['avatar_path'] ?? ''),
        ];
    }

    private function findUserByEmail(string $email): array
    {
        $stmt = $this->pdo->prepare('SELECT id, email FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new RuntimeException('Пользователь не найден.', 404);
        }

        return $user;
    }
}
