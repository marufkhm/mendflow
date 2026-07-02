-- ============================================================
-- migration_v2.sql — Друзья + Сообщения
-- Запусти в phpMyAdmin → база if0_41616496_mend2
-- ============================================================

-- 1. Таблица заявок в друзья
CREATE TABLE IF NOT EXISTS friend_requests (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    from_id     INT NOT NULL,
    to_id       INT NOT NULL,
    status      ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pair (from_id, to_id),
    FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_id)   REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Таблица сообщений
CREATE TABLE IF NOT EXISTS messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    from_id     INT  NOT NULL,
    to_id       INT  NOT NULL,
    content     TEXT NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_id)   REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation (from_id, to_id),
    INDEX idx_to_unread (to_id, is_read)
);
