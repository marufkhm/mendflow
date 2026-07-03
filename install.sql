-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'company', 'university', 'admin', 'backoffice', 'moderator') NOT NULL DEFAULT 'student',
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    last_seen DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица организаций для раздела "Сеть"
CREATE TABLE IF NOT EXISTS organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL UNIQUE,
    type ENUM('universities', 'companies') NOT NULL,
    title VARCHAR(180) NOT NULL,
    subtitle VARCHAR(255) NOT NULL,
    meta VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    tags_text TEXT DEFAULT NULL,
    action_label VARCHAR(120) NOT NULL DEFAULT 'Открыть',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица постов
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    text TEXT NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Таблица лайков
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Таблица комментариев
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Таблица сессий
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(512) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Таблица запросов дружбы
CREATE TABLE IF NOT EXISTS friend_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_id INT NOT NULL,
    to_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_request (from_id, to_id),
    FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Таблица дружб (схема, которую ожидает код: sender_id/receiver_id/status)
CREATE TABLE IF NOT EXISTS friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'accepted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_friendship (sender_id, receiver_id),
    INDEX idx_friendship_sender (sender_id),
    INDEX idx_friendship_receiver (receiver_id),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Миграция friendships: если таблица создана по старой схеме (user1_id/user2_id),
-- добавляем sender_id/receiver_id/status до создания индексов ниже.
DROP PROCEDURE IF EXISTS mf_migrate_friendships;

DELIMITER $$

CREATE PROCEDURE mf_migrate_friendships()
BEGIN
  DECLARE has_table     INT DEFAULT 0;
  DECLARE has_sender    INT DEFAULT 0;
  DECLARE has_receiver  INT DEFAULT 0;
  DECLARE has_user1     INT DEFAULT 0;
  DECLARE has_status    INT DEFAULT 0;
  DECLARE has_updated   INT DEFAULT 0;
  DECLARE has_confirmed INT DEFAULT 0;
  DECLARE has_uq        INT DEFAULT 0;

  SELECT COUNT(*) INTO has_table
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships';

  IF has_table = 0 THEN
    CREATE TABLE friendships (
      id          INT AUTO_INCREMENT PRIMARY KEY,
      sender_id   INT NOT NULL,
      receiver_id INT NOT NULL,
      status      ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'accepted',
      created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uq_friendship (sender_id, receiver_id),
      INDEX idx_friendship_sender (sender_id),
      INDEX idx_friendship_receiver (receiver_id),
      FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    );
  ELSE
    SELECT COUNT(*) INTO has_sender FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships' AND COLUMN_NAME = 'sender_id';
    SELECT COUNT(*) INTO has_receiver FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships' AND COLUMN_NAME = 'receiver_id';
    SELECT COUNT(*) INTO has_user1 FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships' AND COLUMN_NAME = 'user1_id';
    SELECT COUNT(*) INTO has_status FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships' AND COLUMN_NAME = 'status';
    SELECT COUNT(*) INTO has_updated FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships' AND COLUMN_NAME = 'updated_at';

    IF has_sender = 0 THEN
      ALTER TABLE friendships ADD COLUMN sender_id INT NULL;
    END IF;
    IF has_receiver = 0 THEN
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

    IF has_user1 > 0 THEN
      UPDATE friendships
        SET sender_id   = COALESCE(sender_id, user1_id),
            receiver_id = COALESCE(receiver_id, user2_id)
        WHERE sender_id IS NULL OR receiver_id IS NULL;
      SELECT COUNT(*) INTO has_confirmed FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships' AND COLUMN_NAME = 'confirmed';
      IF has_confirmed > 0 THEN
        UPDATE friendships SET status = IF(confirmed = 1, 'accepted', 'pending');
      END IF;
    END IF;

    DELETE FROM friendships WHERE sender_id IS NULL OR receiver_id IS NULL;

    SELECT COUNT(*) INTO has_uq FROM INFORMATION_SCHEMA.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'friendships' AND INDEX_NAME = 'uq_friendship';
    IF has_uq = 0 THEN
      DELETE f1 FROM friendships f1
        INNER JOIN friendships f2
        WHERE f1.id > f2.id
          AND f1.sender_id = f2.sender_id
          AND f1.receiver_id = f2.receiver_id;
      ALTER TABLE friendships ADD UNIQUE KEY uq_friendship (sender_id, receiver_id);
    END IF;
  END IF;
END$$

DELIMITER ;

CALL mf_migrate_friendships();
DROP PROCEDURE IF EXISTS mf_migrate_friendships;

-- Индексы для производительности
DROP INDEX IF EXISTS idx_posts_created_at ON posts;
DROP INDEX IF EXISTS idx_posts_user_id ON posts;
DROP INDEX IF EXISTS idx_likes_post_id ON likes;
DROP INDEX IF EXISTS idx_comments_post_id ON comments;
DROP INDEX IF EXISTS idx_sessions_token ON sessions;
DROP INDEX IF EXISTS idx_sessions_expires_at ON sessions;
DROP INDEX IF EXISTS idx_friend_requests_from ON friend_requests;
DROP INDEX IF EXISTS idx_friend_requests_to ON friend_requests;
DROP INDEX IF EXISTS idx_friendship_sender ON friendships;
DROP INDEX IF EXISTS idx_friendship_receiver ON friendships;
DROP INDEX IF EXISTS idx_friendships_user1 ON friendships;
DROP INDEX IF EXISTS idx_friendships_user2 ON friendships;

CREATE INDEX idx_posts_created_at ON posts(created_at DESC);
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_likes_post_id ON likes(post_id);
CREATE INDEX idx_comments_post_id ON comments(post_id);
CREATE INDEX idx_sessions_token ON sessions(token);
CREATE INDEX idx_sessions_expires_at ON sessions(expires_at);
CREATE INDEX idx_friend_requests_from ON friend_requests(from_id);
CREATE INDEX idx_friend_requests_to ON friend_requests(to_id);
CREATE INDEX idx_friendship_sender ON friendships(sender_id);
CREATE INDEX idx_friendship_receiver ON friendships(receiver_id);
