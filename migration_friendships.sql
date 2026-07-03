-- ============================================================================
-- migration_friendships.sql
-- Приводит таблицу `friendships` к схеме, которую ожидает код приложения:
--   sender_id / receiver_id / status / created_at / updated_at
--
-- Причина: старые схемы (install.sql, mendflow_schema.sql, migration_v2.sql)
-- создавали friendships с колонками user1_id / user2_id / confirmed, а весь
-- PHP-код (friends.php, profile.php, friend_recommendations.php, companies.php,
-- courses.php) обращается к sender_id / receiver_id / status.
-- Симптом: SQLSTATE[42S22] Unknown column 'status' in 'WHERE'.
--
-- БЕЗОПАСНО запускать повторно (idempotent). Данные переносятся.
-- Сервер:  mysql -u mendflow -p mendflow < migration_friendships.sql
-- DBeaver: открыть файл → Alt+X
-- ============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS mf_migrate_friendships;

DELIMITER $$

CREATE PROCEDURE mf_migrate_friendships()
BEGIN
  DECLARE has_table   INT DEFAULT 0;
  DECLARE has_sender  INT DEFAULT 0;
  DECLARE has_user1   INT DEFAULT 0;
  DECLARE has_status  INT DEFAULT 0;
  DECLARE has_updated INT DEFAULT 0;

  SELECT COUNT(*) INTO has_table
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships';

  -- Таблицы нет — создаём сразу правильную
  IF has_table = 0 THEN
    CREATE TABLE friendships (
      id          INT AUTO_INCREMENT PRIMARY KEY,
      sender_id   INT NOT NULL,
      receiver_id INT NOT NULL,
      status      ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'accepted',
      created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uq_friendship (sender_id, receiver_id),
      INDEX idx_friendship_sender (sender_id),
      INDEX idx_friendship_receiver (receiver_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    SELECT 'OK — таблица friendships создана заново (sender_id/receiver_id/status)' AS status;
  ELSE
    SELECT COUNT(*) INTO has_sender  FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships' AND COLUMN_NAME = 'sender_id';
    SELECT COUNT(*) INTO has_user1   FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships' AND COLUMN_NAME = 'user1_id';
    SELECT COUNT(*) INTO has_status  FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships' AND COLUMN_NAME = 'status';
    SELECT COUNT(*) INTO has_updated FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships' AND COLUMN_NAME = 'updated_at';

    -- Добавляем недостающие колонки нового формата
    IF has_sender = 0 THEN
      ALTER TABLE friendships ADD COLUMN sender_id INT NULL;
    END IF;
    IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'
                 AND COLUMN_NAME = 'receiver_id') = 0 THEN
      ALTER TABLE friendships ADD COLUMN receiver_id INT NULL;
    END IF;
    IF has_status = 0 THEN
      ALTER TABLE friendships
        ADD COLUMN status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'accepted';
    END IF;
    IF has_updated = 0 THEN
      ALTER TABLE friendships
        ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
    END IF;

    -- Переносим данные из старого формата (user1_id/user2_id/confirmed)
    IF has_user1 = 1 THEN
      UPDATE friendships
        SET sender_id   = COALESCE(sender_id, user1_id),
            receiver_id = COALESCE(receiver_id, user2_id)
        WHERE sender_id IS NULL OR receiver_id IS NULL;

      -- confirmed=1 → accepted, иначе pending
      IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'
                   AND COLUMN_NAME = 'confirmed') = 1 THEN
        UPDATE friendships SET status = IF(confirmed = 1, 'accepted', 'pending');
      END IF;
    END IF;

    -- Чистим строки без обоих участников (не должны существовать)
    DELETE FROM friendships WHERE sender_id IS NULL OR receiver_id IS NULL;

    -- Уникальный ключ для ON DUPLICATE KEY UPDATE в коде
    IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'
                 AND INDEX_NAME = 'uq_friendship') = 0 THEN
      -- Удаляем дубликаты пар перед созданием уникального ключа
      DELETE f1 FROM friendships f1
        INNER JOIN friendships f2
        WHERE f1.id > f2.id
          AND f1.sender_id = f2.sender_id
          AND f1.receiver_id = f2.receiver_id;
      ALTER TABLE friendships ADD UNIQUE KEY uq_friendship (sender_id, receiver_id);
    END IF;

    -- Удаляем устаревшие колонки и индексы (после переноса данных)
    IF has_user1 = 1 AND has_sender > 0 THEN
      BEGIN
        DECLARE done INT DEFAULT 0;
        DECLARE fk_name VARCHAR(64);
        DECLARE fk_cur CURSOR FOR
          SELECT CONSTRAINT_NAME
          FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'friendships'
            AND COLUMN_NAME IN ('user1_id', 'user2_id')
            AND REFERENCED_TABLE_NAME IS NOT NULL;
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

        OPEN fk_cur;
        fk_loop: LOOP
          FETCH fk_cur INTO fk_name;
          IF done THEN LEAVE fk_loop; END IF;
          SET @drop_fk = CONCAT('ALTER TABLE friendships DROP FOREIGN KEY `', fk_name, '`');
          PREPARE stmt FROM @drop_fk;
          EXECUTE stmt;
          DEALLOCATE PREPARE stmt;
        END LOOP;
        CLOSE fk_cur;
      END;

      IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'
                   AND INDEX_NAME = 'unique_friendship') THEN
        ALTER TABLE friendships DROP INDEX unique_friendship;
      END IF;
      IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'
                   AND INDEX_NAME = 'idx_friendships_user1') THEN
        ALTER TABLE friendships DROP INDEX idx_friendships_user1;
      END IF;
      IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'
                   AND INDEX_NAME = 'idx_friendships_user2') THEN
        ALTER TABLE friendships DROP INDEX idx_friendships_user2;
      END IF;
      IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'
                   AND COLUMN_NAME = 'user1_id') THEN
        ALTER TABLE friendships DROP COLUMN user1_id;
      END IF;
      IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'
                   AND COLUMN_NAME = 'user2_id') THEN
        ALTER TABLE friendships DROP COLUMN user2_id;
      END IF;
      IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships'
                   AND COLUMN_NAME = 'confirmed') THEN
        ALTER TABLE friendships DROP COLUMN confirmed;
      END IF;
    END IF;

    SELECT 'OK — таблица friendships приведена к sender_id/receiver_id/status' AS status;
  END IF;
END$$

DELIMITER ;

CALL mf_migrate_friendships();

DROP PROCEDURE IF EXISTS mf_migrate_friendships;
