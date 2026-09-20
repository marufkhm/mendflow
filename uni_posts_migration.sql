-- ============================================================
-- MENDFLOW — Лента университета (uni_posts)
-- Запусти в phpMyAdmin → твоя база Mendflow (InfinityFree)
--
-- Требуется: таблица university_profiles (migration.sql)
--
-- Если таблица уже есть без колонки university_id — она будет
-- пересоздана. Старые записи в uni_posts будут удалены.
-- ============================================================

DROP TABLE IF EXISTS uni_posts;

CREATE TABLE uni_posts (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    user_id       INT NULL,
    author_type   ENUM('university', 'club') NOT NULL DEFAULT 'university',
    entity_id     INT NULL,
    text          TEXT NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uni_posts_uni (university_id),
    INDEX idx_uni_posts_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
