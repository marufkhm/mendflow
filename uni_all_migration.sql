-- ============================================================
-- MENDFLOW — Все таблицы университета (одним файлом)
-- Запусти в phpMyAdmin → база Mendflow (InfinityFree)
--
-- Пересоздаёт: uni_courses, uni_clubs, uni_club_members,
--              uni_exchange, uni_posts, uni_follows
-- Старые данные в этих таблицах будут удалены.
-- ============================================================

DROP TABLE IF EXISTS uni_club_members;
DROP TABLE IF EXISTS uni_clubs;
DROP TABLE IF EXISTS uni_courses;
DROP TABLE IF EXISTS uni_exchange;
DROP TABLE IF EXISTS uni_posts;
DROP TABLE IF EXISTS uni_follows;

CREATE TABLE uni_courses (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    title         VARCHAR(255) NOT NULL,
    direction     VARCHAR(50)  NOT NULL DEFAULT 'other',
    duration      VARCHAR(50)  NULL,
    language      VARCHAR(50)  NULL,
    credits       INT          NULL,
    tuition_kzt   INT          NULL,
    description   TEXT         NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uni_courses_uni (university_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE uni_exchange (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    university_id   INT NOT NULL,
    partner_name    VARCHAR(190) NOT NULL,
    partner_country VARCHAR(120) NULL,
    partner_flag    VARCHAR(10)  NULL,
    directions      VARCHAR(255) NULL,
    duration        VARCHAR(50)  NULL,
    language        VARCHAR(50)  NULL,
    deadline        DATE         NULL,
    spots           INT          NULL,
    scholarship     TINYINT(1)   NOT NULL DEFAULT 0,
    url             VARCHAR(255) NULL,
    description     TEXT         NULL,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uni_exchange_uni (university_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE uni_follows (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    user_id       INT NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_uni_follow (university_id, user_id),
    INDEX idx_uni_follows_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
