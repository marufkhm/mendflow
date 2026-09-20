-- Дополнительные таблицы для профилей университетов
-- Запусти в phpMyAdmin после migration.sql (опционально — API создаст таблицы автоматически)

ALTER TABLE university_profiles ADD COLUMN cover VARCHAR(255) NULL;
ALTER TABLE university_profiles ADD COLUMN logo VARCHAR(255) NULL;
ALTER TABLE university_profiles ADD COLUMN instagram VARCHAR(120) NULL;
ALTER TABLE university_profiles ADD COLUMN founded_year INT NULL;
ALTER TABLE university_profiles ADD COLUMN students_count INT NULL;

CREATE TABLE IF NOT EXISTS uni_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    direction VARCHAR(50) NOT NULL DEFAULT 'other',
    duration VARCHAR(50) NULL,
    language VARCHAR(50) NULL,
    credits INT NULL,
    tuition_kzt INT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES university_profiles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS uni_clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    name VARCHAR(190) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'other',
    description TEXT NULL,
    logo VARCHAR(255) NULL,
    instagram VARCHAR(120) NULL,
    telegram VARCHAR(120) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES university_profiles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS uni_club_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_club_member (club_id, user_id),
    FOREIGN KEY (club_id) REFERENCES uni_clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS uni_exchange (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    partner_name VARCHAR(190) NOT NULL,
    partner_country VARCHAR(120) NULL,
    partner_flag VARCHAR(10) NULL,
    directions VARCHAR(255) NULL,
    duration VARCHAR(50) NULL,
    language VARCHAR(50) NULL,
    deadline DATE NULL,
    spots INT NULL,
    scholarship TINYINT(1) NOT NULL DEFAULT 0,
    url VARCHAR(255) NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES university_profiles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS uni_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    user_id INT NULL,
    author_type ENUM('university', 'club') NOT NULL DEFAULT 'university',
    entity_id INT NULL,
    text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES university_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS uni_follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_uni_follow (university_id, user_id),
    FOREIGN KEY (university_id) REFERENCES university_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
