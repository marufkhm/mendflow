-- ============================================================================
-- Mendflow — полная проверка БД (таблицы, колонки, строки)
--
-- DBeaver: открыть файл → Ctrl+A → Execute (Alt+X) — только SELECT, безопасно.
-- Сервер:  mysql -u mendflow -p mendflow < check_database.sql
-- Mac:     ./db-check.sh
--
-- Результат:
--   ✓ OK      — всё на месте
--   ! WARN    — не критично, но стоит знать
--   ✗ MISSING — нужно исправить (миграция / mendflow_schema.sql)
-- ============================================================================

SET NAMES utf8mb4;

SELECT '══════════════════════════════════════════════════════════════' AS '';
SELECT '  MENDFLOW — проверка базы данных' AS '';
SELECT CONCAT('  База: ', DATABASE(), '  |  ', NOW()) AS '';
SELECT '══════════════════════════════════════════════════════════════' AS '';

-- ── 1. Все таблицы и число строк ────────────────────────────────────────────
SELECT '── 1. Таблицы и количество строк ──' AS '';

SELECT
  t.TABLE_NAME AS table_name,
  t.TABLE_ROWS AS approx_rows,
  t.ENGINE,
  t.TABLE_COLLATION
FROM INFORMATION_SCHEMA.TABLES t
WHERE t.TABLE_SCHEMA = DATABASE()
  AND t.TABLE_TYPE = 'BASE TABLE'
ORDER BY t.TABLE_NAME;

-- ── 2. Обязательные таблицы (ядро приложения) ───────────────────────────────
SELECT '' AS '';
SELECT '── 2. Обязательные таблицы ──' AS '';

SELECT
  req.table_name,
  CASE WHEN t.TABLE_NAME IS NOT NULL THEN 'OK' ELSE 'MISSING' END AS status,
  req.purpose
FROM (
  SELECT 'users'              AS table_name, 'аккаунты, логин' AS purpose UNION ALL
  SELECT 'sessions',           'JWT / токены сессий' UNION ALL
  SELECT 'posts',              'лента, профиль' UNION ALL
  SELECT 'likes',              'лайки постов' UNION ALL
  SELECT 'comments',           'комментарии' UNION ALL
  SELECT 'friend_requests',    'заявки в друзья' UNION ALL
  SELECT 'friendships',        'список друзей' UNION ALL
  SELECT 'messages',           'личные чаты' UNION ALL
  SELECT 'email_verifications','подтверждение email' UNION ALL
  SELECT 'notifications',      'уведомления'
) req
LEFT JOIN INFORMATION_SCHEMA.TABLES t
  ON t.TABLE_SCHEMA = DATABASE() AND t.TABLE_NAME = req.table_name
ORDER BY status DESC, req.table_name;

-- ── 3. Рекомендуемые таблицы (фичи) ─────────────────────────────────────────
SELECT '' AS '';
SELECT '── 3. Таблицы фич (опционально, создаются API при первом использовании) ──' AS '';

SELECT
  req.table_name,
  CASE WHEN t.TABLE_NAME IS NOT NULL THEN 'OK' ELSE 'WARN (нет)' END AS status,
  req.feature
FROM (
  SELECT 'organizations'      AS table_name, 'раздел Сеть' AS feature UNION ALL
  SELECT 'projects',           'проекты' UNION ALL
  SELECT 'project_members',    'участники проектов' UNION ALL
  SELECT 'events',             'мероприятия' UNION ALL
  SELECT 'university_profiles','профили вузов' UNION ALL
  SELECT 'company_profiles',   'профили компаний' UNION ALL
  SELECT 'articles',           'статьи' UNION ALL
  SELECT 'discussions',        'обсуждения' UNION ALL
  SELECT 'courses',            'курсы' UNION ALL
  SELECT 'jobs',               'вакансии / карьера' UNION ALL
  SELECT 'rate_limits',        'защита от спама' UNION ALL
  SELECT 'password_resets',    'сброс пароля'
) req
LEFT JOIN INFORMATION_SCHEMA.TABLES t
  ON t.TABLE_SCHEMA = DATABASE() AND t.TABLE_NAME = req.table_name
ORDER BY status DESC, req.table_name;

-- ── 4. Критичные колонки ────────────────────────────────────────────────────
SELECT '' AS '';
SELECT '── 4. Критичные колонки ──' AS '';

SELECT
  chk.table_name,
  chk.column_name,
  CASE WHEN c.COLUMN_NAME IS NOT NULL THEN 'OK' ELSE 'MISSING' END AS status,
  chk.note
FROM (
  SELECT 'users' AS table_name, 'email_verified_at' AS column_name, 'блок входа без verify' AS note UNION ALL
  SELECT 'users', 'role', 'тип аккаунта student/company/university' UNION ALL
  SELECT 'users', 'password_hash', 'хеш пароля' UNION ALL
  SELECT 'sessions', 'token', 'авторизация API' UNION ALL
  SELECT 'sessions', 'expires_at', 'срок сессии' UNION ALL
  SELECT 'friendships', 'sender_id', 'НОВАЯ схема друзей (не user1_id!)' UNION ALL
  SELECT 'friendships', 'receiver_id', 'НОВАЯ схема друзей (не user2_id!)' UNION ALL
  SELECT 'friendships', 'status', 'accepted/pending — без неё ошибка 1054' UNION ALL
  SELECT 'friend_requests', 'status', 'статус заявки' UNION ALL
  SELECT 'posts', 'user_id', 'автор поста'
) chk
LEFT JOIN INFORMATION_SCHEMA.COLUMNS c
  ON c.TABLE_SCHEMA = DATABASE()
 AND c.TABLE_NAME = chk.table_name
 AND c.COLUMN_NAME = chk.column_name
ORDER BY status DESC, chk.table_name, chk.column_name;

-- posts: text ИЛИ content
SELECT
  'posts' AS table_name,
  'text OR content' AS column_name,
  CASE
    WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='posts' AND COLUMN_NAME='text')
      OR EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='posts' AND COLUMN_NAME='content')
    THEN 'OK'
    ELSE 'MISSING'
  END AS status,
  'текст поста (достаточно одной из колонок)' AS note;

-- Старые колонки friendships — если есть, нужна миграция
SELECT '' AS '';
SELECT '── 4b. Устаревшая схема friendships (должно быть пусто) ──' AS '';

SELECT
  c.COLUMN_NAME AS old_column,
  'WARN — запустите migration_friendships.sql' AS action
FROM INFORMATION_SCHEMA.COLUMNS c
WHERE c.TABLE_SCHEMA = DATABASE()
  AND c.TABLE_NAME = 'friendships'
  AND c.COLUMN_NAME IN ('user1_id', 'user2_id', 'confirmed');

-- ── 5. Сводка по данным ───────────────────────────────────────────────────────
SELECT '' AS '';
SELECT '── 5. Данные (строки) ──' AS '';

SELECT 'users' AS entity,
  (SELECT COUNT(*) FROM users) AS total,
  (SELECT COUNT(*) FROM users WHERE email_verified_at IS NOT NULL) AS verified,
  (SELECT COUNT(*) FROM users WHERE email_verified_at IS NULL) AS not_verified
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users');

SELECT 'sessions (активные)' AS entity,
  (SELECT COUNT(*) FROM sessions WHERE expires_at > NOW()) AS active,
  (SELECT COUNT(*) FROM sessions WHERE expires_at <= NOW()) AS expired
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sessions');

SELECT 'posts' AS entity, COUNT(*) AS total
FROM posts
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='posts');

-- friendships: не считаем строки, если нет status (см. блок 4)
SELECT 'friendships' AS entity,
  IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='friendships' AND COLUMN_NAME='status'),
    '(см. SELECT COUNT(*) FROM friendships WHERE status=''accepted'')',
    'SKIP — сначала migration_friendships.sql'
  ) AS total;

SELECT 'friend_requests (pending)' AS entity, COUNT(*) AS total
FROM friend_requests
WHERE status = 'pending'
  AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friend_requests');

-- ── 6. Целостность (сироты) ─────────────────────────────────────────────────
SELECT '' AS '';
SELECT '── 6. Целостность данных ──' AS '';

SELECT 'posts без пользователя' AS check_name,
  COUNT(*) AS problem_rows
FROM posts p
LEFT JOIN users u ON u.id = p.user_id
WHERE u.id IS NULL
  AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='posts');

SELECT 'sessions без пользователя' AS check_name,
  COUNT(*) AS problem_rows
FROM sessions s
LEFT JOIN users u ON u.id = s.user_id
WHERE u.id IS NULL
  AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sessions');

SELECT 'likes без поста' AS check_name,
  COUNT(*) AS problem_rows
FROM likes l
LEFT JOIN posts p ON p.id = l.post_id
WHERE p.id IS NULL
  AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='likes');

-- ── 7. Итог ─────────────────────────────────────────────────────────────────
SELECT '' AS '';
SELECT '── 7. ИТОГ ──' AS '';

SELECT
  CASE
    WHEN missing_tables > 0 THEN CONCAT('✗ Нет ', missing_tables, ' обязательных таблиц — импортируйте mendflow_schema.sql')
    WHEN missing_cols > 0 THEN CONCAT('✗ Нет ', missing_cols, ' критичных колонок — migration_friendships.sql + mendflow_schema.sql')
    WHEN old_friend_cols > 0 AND new_friend_cols < 3 THEN '✗ Старая схема friendships — запустите migration_friendships.sql'
    WHEN old_friend_cols > 0 THEN '! Гибрид: новые колонки есть, старые ещё не удалены — перезапустите migration_friendships.sql'
    ELSE '✓ Ядро БД в порядке'
  END AS verdict
FROM (
  SELECT
    (SELECT COUNT(*) FROM (
      SELECT req.table_name FROM (
        SELECT 'users' AS table_name UNION SELECT 'sessions' UNION SELECT 'posts'
        UNION SELECT 'likes' UNION SELECT 'comments' UNION SELECT 'friend_requests'
        UNION SELECT 'friendships' UNION SELECT 'messages' UNION SELECT 'email_verifications'
        UNION SELECT 'notifications'
      ) req
      LEFT JOIN INFORMATION_SCHEMA.TABLES t
        ON t.TABLE_SCHEMA = DATABASE() AND t.TABLE_NAME = req.table_name
      WHERE t.TABLE_NAME IS NULL
    ) x) AS missing_tables,
    (SELECT COUNT(*) FROM (
      SELECT chk.table_name, chk.column_name FROM (
        SELECT 'users' AS table_name, 'email_verified_at' AS column_name UNION ALL
        SELECT 'friendships', 'sender_id' UNION ALL
        SELECT 'friendships', 'receiver_id' UNION ALL
        SELECT 'friendships', 'status'
      ) chk
      LEFT JOIN INFORMATION_SCHEMA.COLUMNS c
        ON c.TABLE_SCHEMA = DATABASE() AND c.TABLE_NAME = chk.table_name AND c.COLUMN_NAME = chk.column_name
      WHERE c.COLUMN_NAME IS NULL
    ) y) AS missing_cols,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'
        AND COLUMN_NAME IN ('user1_id', 'user2_id')) AS old_friend_cols,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'
        AND COLUMN_NAME IN ('sender_id', 'receiver_id', 'status')) AS new_friend_cols
) summary;

SELECT '' AS '';
SELECT 'Если есть MISSING: mendflow_schema.sql → migration_friendships.sql → ./deploy.sh' AS hint;
