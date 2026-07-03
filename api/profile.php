<?php
/**
 * profile.php — public profile of any user
 * GET /profile.php?user_id=42
 */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
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

require_once 'db.php';
require_once __DIR__ . '/badges_lib.php';

/* ── Optional auth ── */
function getAuthUser($pdo) {
    $token = function_exists('getBearerToken') ? getBearerToken() : null;
    if (!$token) return null;
    try {
        $st = $pdo->prepare("
            SELECT u.id
            FROM sessions s
            JOIN users u ON u.id = s.user_id
            WHERE s.token = ? AND s.expires_at > NOW()
            LIMIT 1
        ");
        $st->execute([$token]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        return $u ?: null;
    } catch (Exception $e) { return null; }
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'PUT'], true)) {
    jsonOut(['error' => 'Method not allowed'], 405);
}

$me = getAuthUser($pdo);
$myId = $me ? (int)$me['id'] : 0;

if (function_exists('ensureFriendshipsSchema')) {
    ensureFriendshipsSchema();
}

/* ──────────────────────────────────────────────────────────────
   PUT /profile.php — обновление своего профиля
   Body: { "university_id": 12|null, "organization": "..."|null }
   ────────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (!$myId) jsonOut(['error' => 'Unauthorized'], 401);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) jsonOut(['error' => 'Invalid JSON body'], 400);

    $hasUniversityId = array_key_exists('university_id', $input);
    $hasOrganization = array_key_exists('organization', $input);
    $hasCoverImage   = array_key_exists('cover_image', $input);
    $hasSpecialty    = array_key_exists('specialty',   $input);
    $hasEducation    = array_key_exists('education',   $input);
    $hasCountry      = array_key_exists('country',     $input);
    $hasCity         = array_key_exists('city',        $input);
    $hasBio          = array_key_exists('bio',         $input);
    $hasInterest1    = array_key_exists('interest1',   $input);
    $hasInterest2    = array_key_exists('interest2',   $input);
    $hasInterest3    = array_key_exists('interest3',   $input);

    // Safe migration: add extended profile columns if missing
    $extCols = [
        'cover_image' => "VARCHAR(500) NULL",
        'specialty'   => "VARCHAR(200) NULL",
        'education'   => "VARCHAR(100) NULL",
        'country'     => "VARCHAR(100) NULL",
        'city'        => "VARCHAR(120) NULL",
        'bio'         => "TEXT NULL",
        'interest1'   => "VARCHAR(100) NULL",
        'interest2'   => "VARCHAR(100) NULL",
        'interest3'   => "VARCHAR(100) NULL",
    ];
    try {
        $existingCols = $pdo->query("
            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
        ")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($extCols as $col => $def) {
            if (!in_array($col, $existingCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN `{$col}` {$def}");
            }
        }
    } catch (Throwable $e) {}

    $universityId = null;
    if ($hasUniversityId && $input['university_id'] !== null && $input['university_id'] !== '') {
        $universityId = (int)$input['university_id'];
        try {
            $check = $pdo->prepare("SELECT id FROM university_profiles WHERE id = ? LIMIT 1");
            $check->execute([$universityId]);
            if (!$check->fetch()) {
                $check2 = $pdo->prepare("SELECT id FROM universities WHERE id = ? LIMIT 1");
                $check2->execute([$universityId]);
                if (!$check2->fetch()) jsonOut(['error' => 'university_id не найден'], 422);
            }
        } catch (Exception $e) {
            jsonOut(['error' => 'DB error: ' . $e->getMessage()], 500);
        }
    }

    $organization = null;
    if ($hasOrganization) {
        $organization = trim((string)$input['organization']);
        if ($organization === '') $organization = null;
    }

    $coverImage = null;
    if ($hasCoverImage) {
        $coverImage = trim((string)$input['cover_image']);
        if ($coverImage === '') $coverImage = null;
    }

    $strOrNull = function($v) { $s = trim((string)$v); return $s === '' ? null : $s; };

    $sets = [];
    $params = [];
    if ($hasUniversityId) { $sets[] = 'university_id = ?'; $params[] = $universityId; }
    if ($hasOrganization) { $sets[] = 'organization = ?';  $params[] = $organization; }
    if ($hasCoverImage)   { $sets[] = 'cover_image = ?';   $params[] = $coverImage; }
    if ($hasSpecialty)    { $sets[] = 'specialty = ?';     $params[] = $strOrNull($input['specialty']); }
    if ($hasEducation)    { $sets[] = 'education = ?';     $params[] = $strOrNull($input['education']); }
    if ($hasCountry)      { $sets[] = 'country = ?';       $params[] = $strOrNull($input['country']); }
    if ($hasCity)         { $sets[] = 'city = ?';          $params[] = $strOrNull($input['city']); }
    if ($hasBio)          { $sets[] = 'bio = ?';           $params[] = $strOrNull($input['bio']); }
    if ($hasInterest1)    { $sets[] = 'interest1 = ?';     $params[] = $strOrNull($input['interest1']); }
    if ($hasInterest2)    { $sets[] = 'interest2 = ?';     $params[] = $strOrNull($input['interest2']); }
    if ($hasInterest3)    { $sets[] = 'interest3 = ?';     $params[] = $strOrNull($input['interest3']); }

    try {
        if ($sets) {
            $params[] = $myId;
            $sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        // ── Уведомление вузу о новом студенте ──────────────────────────
        // Срабатывает только если university_id реально изменился (привязка).
        if ($hasUniversityId && $universityId !== null) {
            try {
                // Проверяем предыдущий university_id студента
                $stPrev = $pdo->prepare("SELECT university_id FROM users WHERE id = ? LIMIT 1");
                // Уже обновили, поэтому берём из входных данных сравнение
                // — уведомляем если это новая привязка (не тот же вуз)
                $notifCheck = $pdo->query("
                    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' LIMIT 1
                ")->fetchColumn();

                if ($notifCheck) {
                    // Находим user_id владельца вуза через university_profiles
                    $stOwner = $pdo->prepare("
                        SELECT user_id FROM university_profiles WHERE id = ? LIMIT 1
                    ");
                    $stOwner->execute([$universityId]);
                    $ownerId = (int)$stOwner->fetchColumn();

                    if ($ownerId && $ownerId !== $myId) {
                        // Имя студента для превью
                        $stName = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ? LIMIT 1");
                        $stName->execute([$myId]);
                        $studentRow = $stName->fetch(PDO::FETCH_ASSOC);
                        $preview = trim(($studentRow['first_name'] ?? '') . ' ' . ($studentRow['last_name'] ?? ''));

                        $pdo->prepare("
                            INSERT INTO notifications
                                (user_id, from_user_id, type, post_id, post_preview, post_type, created_at)
                            VALUES (?, ?, 'university_join', NULL, ?, 'user', NOW())
                        ")->execute([$ownerId, $myId, $preview]);
                    }
                }
            } catch (Throwable $e) {
                // Уведомления не критичны
            }
        }

        // Detect which extended columns exist for safe SELECT
        $putAllCols = [];
        try {
            $putAllCols = $pdo->query("
                SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
            ")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $e) {}

        $safeCol = function($col) use ($putAllCols) {
            return in_array($col, $putAllCols) ? ", u.`{$col}`" : '';
        };
        $extSel = $safeCol('cover_image') . $safeCol('specialty') . $safeCol('education')
                . $safeCol('country')     . $safeCol('city')      . $safeCol('bio')
                . $safeCol('interest1')   . $safeCol('interest2') . $safeCol('interest3');

        $st = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.avatar, u.is_verified,
                   u.university_id, u.organization, un.university_name{$extSel}
            FROM users u
            LEFT JOIN universities un ON un.id = u.university_id
            WHERE u.id = ? LIMIT 1
        ");
        $st->execute([$myId]);
        $user = $st->fetch(PDO::FETCH_ASSOC);
        if (!$user) jsonOut(['error' => 'User not found'], 404);

        jsonOut(['user' => [
            'id'              => (int)$user['id'],
            'first_name'      => $user['first_name'],
            'last_name'       => $user['last_name'],
            'email'           => $user['email'],
            'avatar'          => normalizeMediaUrl($user['avatar'] ?? null),
            'cover_image'     => normalizeMediaUrl($user['cover_image'] ?? null),
            'is_verified'     => (bool)$user['is_verified'],
            'university_id'   => $user['university_id'] !== null ? (int)$user['university_id'] : null,
            'organization'    => $user['organization'],
            'university_name' => $user['university_name'],
            'specialty'       => $user['specialty']   ?? null,
            'education'       => $user['education']   ?? null,
            'country'         => $user['country']     ?? null,
            'city'            => $user['city']        ?? null,
            'bio'             => $user['bio']         ?? null,
            'interest1'       => $user['interest1']   ?? null,
            'interest2'       => $user['interest2']   ?? null,
            'interest3'       => $user['interest3']   ?? null,
        ]]);
    } catch (Exception $e) {
        jsonOut(['error' => 'DB error: ' . $e->getMessage()], 500);
    }
}

/* ──────────────────────────────────────────────────────────────
   GET /profile.php?user_id=42 — публичный профиль (как раньше)
   ────────────────────────────────────────────────────────────── */
$targetId = (int)($_GET['user_id'] ?? 0);
if (!$targetId) jsonOut(['error' => 'user_id required'], 400);

$part = trim($_GET['part'] ?? '');

/* ── GET part=subscriptions — подписки пользователя ── */
if ($part === 'subscriptions') {
    try {
        $st = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        $st->execute([$targetId]);
        if (!$st->fetchColumn()) jsonOut(['error' => 'User not found'], 404);

        $universities = [];
        $projects     = [];

        $hasUniFollow = false;
        try {
            $hasUniFollow = (bool)$pdo->query("
                SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'uni_follows' LIMIT 1
            ")->fetchColumn();
        } catch (Exception $e) {}

        if ($hasUniFollow) {
            $hasUniProf = (bool)$pdo->query("
                SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'university_profiles' LIMIT 1
            ")->fetchColumn();

            if ($hasUniProf) {
                $stUni = $pdo->prepare("
                    SELECT
                        uf.university_id AS id,
                        up.university_name AS name,
                        up.logo,
                        up.city,
                        uf.created_at,
                        (SELECT COUNT(*) FROM uni_follows uf2 WHERE uf2.university_id = uf.university_id) AS followers_count
                    FROM uni_follows uf
                    JOIN university_profiles up ON up.id = uf.university_id
                    WHERE uf.user_id = ?
                    ORDER BY uf.created_at DESC
                    LIMIT 50
                ");
                $stUni->execute([$targetId]);
                $universities = $stUni->fetchAll(PDO::FETCH_ASSOC);
                foreach ($universities as &$u) {
                    $u['type']       = 'university';
                    $u['created_at'] = utcDate($u['created_at']);
                    $u['time_ago']   = timeAgo($u['created_at']);
                }
                unset($u);
            }
        }

        $hasProjFollow = false;
        try {
            $hasProjFollow = (bool)$pdo->query("
                SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'project_followers' LIMIT 1
            ")->fetchColumn();
        } catch (Exception $e) {}

        if ($hasProjFollow) {
            $stPrj = $pdo->prepare("
                SELECT
                    p.id,
                    p.title AS name,
                    p.description,
                    p.cover_url AS logo,
                    p.category,
                    p.stage,
                    pf.created_at,
                    (SELECT COUNT(*) FROM project_followers pf2 WHERE pf2.project_id = p.id) AS followers_count
                FROM project_followers pf
                JOIN projects p ON p.id = pf.project_id
                WHERE pf.user_id = ? AND p.is_public = 1
                ORDER BY pf.created_at DESC
                LIMIT 50
            ");
            $stPrj->execute([$targetId]);
            $projects = $stPrj->fetchAll(PDO::FETCH_ASSOC);
            foreach ($projects as &$p) {
                $p['type']       = 'project';
                $p['created_at'] = utcDate($p['created_at']);
                $p['time_ago']   = timeAgo($p['created_at']);
            }
            unset($p);
        }

        jsonOut([
            'subscriptions' => [
                'universities' => $universities,
                'projects'     => $projects,
                'total'        => count($universities) + count($projects),
            ],
        ]);
    } catch (Exception $e) {
        jsonOut(['error' => 'DB error: ' . $e->getMessage()], 500);
    }
}

try {
    /* ── 1. Basic user info ── */
    // Detect which extended columns exist for safe SELECT
    $getAllCols = [];
    try {
        $getAllCols = $pdo->query("
            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
        ")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {}

    $gSafeCol = function($col) use ($getAllCols) {
        return in_array($col, $getAllCols) ? ", `{$col}`" : '';
    };
    $gExtSel = $gSafeCol('cover_image') . $gSafeCol('specialty') . $gSafeCol('education')
             . $gSafeCol('country')     . $gSafeCol('city')      . $gSafeCol('bio')
             . $gSafeCol('interest1')   . $gSafeCol('interest2') . $gSafeCol('interest3')
             . $gSafeCol('organization');

    $st = $pdo->prepare("
        SELECT id, first_name, last_name, email, avatar, created_at, is_verified{$gExtSel}
        FROM users WHERE id = ? LIMIT 1
    ");
    $st->execute([$targetId]);
    $user = $st->fetch(PDO::FETCH_ASSOC);
    if (!$user) jsonOut(['error' => 'User not found'], 404);

    /* ── 2. Posts count ── */
    $postsCount = 0;
    try {
        $st2 = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
        $st2->execute([$targetId]);
        $postsCount = (int)$st2->fetchColumn();
    } catch(Exception $e) {}

    /* ── 3. Friends count + friendship status ── */
    $friendsCount = 0;
    $friendshipStatus = 'none';

    try {
        $st3 = $pdo->prepare("
            SELECT COUNT(*) FROM friendships
            WHERE status = 'accepted' 
              AND (sender_id = ? OR receiver_id = ?)
        ");
        $st3->execute([$targetId, $targetId]);
        $friendsCount = (int)$st3->fetchColumn();

        // Статус дружбы между текущим пользователем и профилем
        if ($myId && $myId !== $targetId) {
            $st4 = $pdo->prepare("
                SELECT status, sender_id 
                FROM friendships 
                WHERE (sender_id = ? AND receiver_id = ?) 
                   OR (sender_id = ? AND receiver_id = ?)
                LIMIT 1
            ");
            $st4->execute([$myId, $targetId, $targetId, $myId]);
            $row = $st4->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                if ($row['status'] === 'accepted') {
                    $friendshipStatus = 'friends';
                } elseif ($row['status'] === 'pending') {
                    $friendshipStatus = ($row['sender_id'] == $myId) ? 'pending_sent' : 'pending_received';
                }
            }
        }
    } catch(Exception $e) {}

    /* ── 4. Recent posts ── */
    $recentPosts = [];
    try {
        $postCols = $pdo->query("SHOW COLUMNS FROM posts")->fetchAll(PDO::FETCH_COLUMN);
        $postTextCol = in_array('text', $postCols) ? 'text' : 'content';
        $st6 = $pdo->prepare("
            SELECT id, {$postTextCol} AS content, created_at,
                   (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) AS likes_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) AS comments_count
            FROM posts WHERE user_id = ?
            ORDER BY created_at DESC LIMIT 5
        ");
        $st6->execute([$targetId]);
        $recentPosts = $st6->fetchAll(PDO::FETCH_ASSOC);
        foreach ($recentPosts as &$post) {
            $post['created_at'] = utcDate($post['created_at']);
        }
    } catch(Exception $e) {}

    /* ── 5. Mutual friends ── */
    $mutualCount = 0;
    if ($myId && $myId !== $targetId && $friendsCount > 0) {
        try {
            $stM1 = $pdo->prepare("
                SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS fid
                FROM friendships WHERE (sender_id = ? OR receiver_id = ?) AND status = 'accepted'
            ");
            $stM1->execute([$myId, $myId, $myId]);
            $myFriendIds = $stM1->fetchAll(PDO::FETCH_COLUMN);

            if ($myFriendIds) {
                $placeholders = implode(',', array_fill(0, count($myFriendIds), '?'));
                $stM2 = $pdo->prepare("
                    SELECT COUNT(*) FROM friendships
                    WHERE status = 'accepted'
                      AND (
                          (sender_id = ? AND receiver_id IN ($placeholders)) OR
                          (receiver_id = ? AND sender_id IN ($placeholders))
                      )
                ");
                $params = array_merge([$targetId], $myFriendIds, [$targetId], $myFriendIds);
                $stM2->execute($params);
                $mutualCount = (int)$stM2->fetchColumn();
            }
        } catch(Exception $e) {}
    }

    $badges = mfComputeUserBadges($pdo, $targetId);

    jsonOut([
        'user' => [
            'id'           => (int)$user['id'],
            'first_name'   => $user['first_name'],
            'last_name'    => $user['last_name'],
            'email'        => null,
            'avatar'       => normalizeMediaUrl($user['avatar'] ?? null),
            'cover_image'  => normalizeMediaUrl($user['cover_image'] ?? null),
            'is_verified'  => (bool)$user['is_verified'],
            'member_since' => utcDate($user['created_at']),
            'organization' => $user['organization'] ?? null,
            'specialty'    => $user['specialty']    ?? null,
            'education'    => $user['education']    ?? null,
            'country'      => $user['country']      ?? null,
            'city'         => $user['city']         ?? null,
            'bio'          => $user['bio']          ?? null,
            'interest1'    => $user['interest1']    ?? null,
            'interest2'    => $user['interest2']    ?? null,
            'interest3'    => $user['interest3']    ?? null,
        ],
        'stats' => [
            'posts'   => $postsCount,
            'friends' => $friendsCount,
            'mutual'  => $mutualCount,
        ],
        'badges'            => $badges,
        'friendship_status' => $friendshipStatus,
        'is_me'             => ($myId === $targetId),
        'posts'             => $recentPosts,
    ]);

} catch (Exception $e) {
    jsonOut(['error' => 'DB error: ' . $e->getMessage()], 500);
}
?>