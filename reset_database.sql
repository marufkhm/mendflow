-- ============================================================================
-- ПОЛНЫЙ СБРОС БД Mendflow — удаляет ВСЕ данные (пользователи, посты, всё)
--
-- ⚠️  НЕОБРАТИМО. Сделайте бэкап:
--     mysqldump -u mendflow -p mendflow > backup_$(date +%F).sql
--
-- DBeaver: Ctrl+A → Alt+X
-- Сервер:  mysql -u mendflow -p mendflow < reset_database.sql
--
-- После SQL очистите файлы:
--   rm -f /var/www/html/mendflow/uploads/*
--   (оставьте uploads/.htaccess если нужен)
-- ============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS mf_reset_all_data;

DELIMITER $$

CREATE PROCEDURE mf_reset_all_data()
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE tbl VARCHAR(64);
  DECLARE cur CURSOR FOR
    SELECT TABLE_NAME
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_TYPE = 'BASE TABLE';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  SET FOREIGN_KEY_CHECKS = 0;

  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO tbl;
    IF done THEN
      LEAVE read_loop;
    END IF;
    SET @sql = CONCAT('TRUNCATE TABLE `', tbl, '`');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END LOOP;
  CLOSE cur;

  SET FOREIGN_KEY_CHECKS = 1;

  SELECT 'OK — все таблицы очищены (TRUNCATE)' AS status;
END$$

DELIMITER ;

CALL mf_reset_all_data();

DROP PROCEDURE IF EXISTS mf_reset_all_data;

-- ============================================================================
-- Вариант 2 (ещё чище — пересоздать БД с нуля, на сервере):
--
--   mysqldump -u mendflow -p mendflow > ~/mendflow_backup.sql
--   mysql -u root -p -e "
--     DROP DATABASE mendflow;
--     CREATE DATABASE mendflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--     GRANT ALL ON mendflow.* TO 'mendflow'@'localhost';
--   "
--   mysql -u mendflow -p mendflow < mendflow_schema.sql
--
-- Файлы uploads:
--   cd /var/www/html/mendflow/uploads && rm -f img_* video_* avatar_* cover_*
-- ============================================================================
