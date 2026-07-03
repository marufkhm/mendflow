-- ============================================================================
-- Полное удаление пользователя Mendflow (пропускает несуществующие таблицы)
--
-- DBeaver: замените email в CALL в конце, Ctrl+A → Alt+X
-- Сервер:  mysql -u mendflow -p mendflow < delete_user_full.sql
-- ============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS mf_delete_user_full;
DROP PROCEDURE IF EXISTS mf_safe_delete;
DROP PROCEDURE IF EXISTS mf_delete_company_posts;
DROP PROCEDURE IF EXISTS mf_delete_child_by_author;

DELIMITER $$

CREATE PROCEDURE mf_safe_delete(IN p_table VARCHAR(64), IN p_where VARCHAR(2000))
BEGIN
  IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table
  ) THEN
    SET @sql = CONCAT('DELETE FROM `', p_table, '` WHERE ', p_where);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE PROCEDURE mf_delete_company_posts(IN v_uid INT)
BEGIN
  IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_posts'
  ) THEN
    IF EXISTS (
      SELECT 1 FROM INFORMATION_SCHEMA.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_profiles'
    ) THEN
      SET @sql = CONCAT(
        'DELETE cp FROM company_posts cp ',
        'LEFT JOIN company_profiles c ON c.id = cp.company_id ',
        'WHERE cp.user_id = ', v_uid, ' OR c.user_id = ', v_uid
      );
    ELSE
      SET @sql = CONCAT('DELETE FROM company_posts WHERE user_id = ', v_uid);
    END IF;

    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

-- Удалить из child WHERE parent.author_id = v_uid (articles → bookmarks и т.п.)
CREATE PROCEDURE mf_delete_child_by_author(
  IN p_child VARCHAR(64),
  IN p_parent VARCHAR(64),
  IN p_fk_col VARCHAR(64),
  IN p_author_col VARCHAR(64),
  IN v_uid INT
)
BEGIN
  IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_child
  ) AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_parent
  ) THEN
    SET @sql = CONCAT(
      'DELETE FROM `', p_child, '` WHERE `', p_fk_col, '` IN (',
      'SELECT id FROM `', p_parent, '` WHERE `', p_author_col, '` = ', v_uid, ')'
    );
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

CREATE PROCEDURE mf_delete_user_full(IN p_email VARCHAR(255))
BEGIN
  DECLARE v_uid INT DEFAULT NULL;
  DECLARE v_email VARCHAR(255);

  SET v_email = LOWER(TRIM(p_email));
  SELECT id INTO v_uid FROM users WHERE email = v_email LIMIT 1;

  IF v_uid IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Пользователь с таким email не найден';
  END IF;

  SELECT v_uid AS user_id, v_email AS email;

  START TRANSACTION;

  -- Посты, лайки, репосты
  CALL mf_safe_delete('comments', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('likes', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('reposts', CONCAT('user_id = ', v_uid));

  -- Страница компании
  CALL mf_safe_delete('company_comments', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('company_likes', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('company_reposts', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('company_follows', CONCAT('user_id = ', v_uid));
  CALL mf_delete_company_posts(v_uid);

  -- Вуз / uni
  CALL mf_safe_delete('uni_likes', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('uni_posts', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('uni_follows', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('uni_club_members', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('club_members', CONCAT('user_id = ', v_uid));

  -- Статьи
  CALL mf_safe_delete('article_bookmarks', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('article_reactions', CONCAT('user_id = ', v_uid));
  CALL mf_delete_child_by_author('article_bookmarks', 'articles', 'article_id', 'author_id', v_uid);
  CALL mf_delete_child_by_author('article_reactions', 'articles', 'article_id', 'author_id', v_uid);
  CALL mf_safe_delete('articles', CONCAT('author_id = ', v_uid));

  -- Обсуждения
  CALL mf_safe_delete('discussion_votes', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('discussion_answers', CONCAT('author_id = ', v_uid));
  CALL mf_delete_child_by_author('discussion_answers', 'discussions', 'discussion_id', 'author_id', v_uid);
  CALL mf_safe_delete('discussions', CONCAT('author_id = ', v_uid));

  -- Курсы
  CALL mf_safe_delete('mf_course_reviews', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('mf_course_lesson_progress', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('mf_course_enrollments', CONCAT('user_id = ', v_uid));
  CALL mf_delete_child_by_author('mf_course_lessons', 'mf_courses', 'course_id', 'author_id', v_uid);
  CALL mf_delete_child_by_author('mf_course_tags', 'mf_courses', 'course_id', 'author_id', v_uid);
  CALL mf_safe_delete('mf_courses', CONCAT('author_id = ', v_uid));

  -- Проекты
  CALL mf_safe_delete('project_activity', CONCAT('user_id = ', v_uid));

  -- Соцграф
  CALL mf_safe_delete('messages', CONCAT('from_id = ', v_uid, ' OR to_id = ', v_uid));
  CALL mf_safe_delete('friend_requests', CONCAT('from_id = ', v_uid, ' OR to_id = ', v_uid));

  -- События
  CALL mf_safe_delete('event_reviews', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('event_participants', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('events', CONCAT('creator_id = ', v_uid));

  -- Уведомления, бейджи, auth
  CALL mf_safe_delete('notification_reads', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('notifications', CONCAT('user_id = ', v_uid, ' OR from_user_id = ', v_uid));
  CALL mf_safe_delete('user_badges', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('user_affinity', CONCAT('user_low = ', v_uid, ' OR user_high = ', v_uid));
  CALL mf_safe_delete('push_subscriptions', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('email_verifications', CONCAT('user_id = ', v_uid, ' OR email = ', QUOTE(v_email)));
  CALL mf_safe_delete('password_resets', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('sessions', CONCAT('user_id = ', v_uid));

  -- Realtime / feed
  CALL mf_safe_delete('realtime_typing', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('realtime_presence', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('post_dm_shares', CONCAT('from_user_id = ', v_uid, ' OR to_user_id = ', v_uid));
  CALL mf_safe_delete('post_signal_events', CONCAT('user_id = ', v_uid));

  -- Feature requests / jobs
  CALL mf_safe_delete('feature_request_votes', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('feature_requests', CONCAT('author_id = ', v_uid));
  CALL mf_safe_delete('job_applications', CONCAT('user_id = ', v_uid));

  -- Посты и профили
  CALL mf_safe_delete('posts', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('company_profiles', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('university_profiles', CONCAT('user_id = ', v_uid));
  CALL mf_safe_delete('user_interests', CONCAT('user_id = ', v_uid));

  -- Пользователь (CASCADE удалит project_members, project_posts и т.д.)
  DELETE FROM users WHERE id = v_uid;

  COMMIT;

  SELECT v_uid AS deleted_user_id, v_email AS email, 'OK' AS status;
END$$

DELIMITER ;

-- ▼▼▼ ЗАМЕНИТЕ EMAIL ▼▼▼
CALL mf_delete_user_full('user@example.com');

-- Уборка процедур (можно закомментировать, если нужны повторно)
DROP PROCEDURE IF EXISTS mf_delete_user_full;
DROP PROCEDURE IF EXISTS mf_safe_delete;
DROP PROCEDURE IF EXISTS mf_delete_company_posts;
DROP PROCEDURE IF EXISTS mf_delete_child_by_author;

-- ============================================================================
-- Файлы на диске (на сервере, UID — из вывода user_id выше):
--
--   cd /var/www/html/mendflow/uploads
--   rm -f img_UID_* video_UID_* avatar_UID_* cover_UID_*
-- ============================================================================
