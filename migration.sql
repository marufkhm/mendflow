-- ============================================================
-- Запусти в phpMyAdmin → база if0_41616496_mend2
-- ============================================================

-- 1. Добавляем роль в таблицу users
--    Все существующие строки останутся 'student' (DEFAULT)
ALTER TABLE users
    ADD COLUMN role ENUM('student', 'company', 'university') NOT NULL DEFAULT 'student'
    AFTER last_name;

-- 2. Профили компаний
--    verified = 0 → компания не может постить стажировки
--    Чтобы открыть доступ: UPDATE company_profiles SET verified = 1 WHERE id = X
CREATE TABLE company_profiles (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL UNIQUE,
    company_name VARCHAR(190) NOT NULL,
    industry     VARCHAR(120) NULL,
    website      VARCHAR(255) NULL,
    city         VARCHAR(120) NULL,
    country_name VARCHAR(120) NULL,
    description  TEXT         NULL,
    verified     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Профили университетов
--    verified = 0 → не может подтверждать студентов
CREATE TABLE university_profiles (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT          NOT NULL UNIQUE,
    university_name VARCHAR(190) NOT NULL,
    city            VARCHAR(120) NULL,
    country_name    VARCHAR(120) NULL,
    website         VARCHAR(255) NULL,
    description     TEXT         NULL,
    verified        TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
