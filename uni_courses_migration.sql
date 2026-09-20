-- ============================================================
-- MENDFLOW — Курсы университета (uni_courses)
-- Запусти в phpMyAdmin → твоя база Mendflow (InfinityFree)
--
-- Требуется: таблица university_profiles (migration.sql)
--
-- Если таблица уже есть без колонки university_id — она будет
-- пересоздана. Старые записи в uni_courses будут удалены.
-- ============================================================

DROP TABLE IF EXISTS uni_courses;

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
