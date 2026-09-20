-- ============================================================
-- MENDFLOW — Клубы университета (uni_clubs + uni_club_members)
-- Запусти в phpMyAdmin → твоя база Mendflow (InfinityFree)
--
-- Требуется: таблица university_profiles (migration.sql)
--
-- Если таблицы уже есть без нужных колонок — будут пересозданы.
-- Старые записи в uni_clubs и uni_club_members будут удалены.
-- ============================================================

DROP TABLE IF EXISTS uni_club_members;
DROP TABLE IF EXISTS uni_clubs;

CREATE TABLE uni_clubs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    name          VARCHAR(190) NOT NULL,
    category      VARCHAR(50)  NOT NULL DEFAULT 'other',
    description   TEXT         NULL,
    logo          VARCHAR(255) NULL,
    instagram     VARCHAR(120) NULL,
    telegram      VARCHAR(120) NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uni_clubs_uni (university_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE uni_club_members (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    club_id    INT NOT NULL,
    user_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_club_member (club_id, user_id),
    INDEX idx_club_members_user (user_id),
    INDEX idx_club_members_club (club_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
