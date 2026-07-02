-- ============================================================
-- MENDFLOW Events module migration
-- Run in phpMyAdmin for the Mendflow database.
-- ============================================================

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS city VARCHAR(120) NULL AFTER last_seen;

CREATE TABLE IF NOT EXISTS user_interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    interest_name VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_interest (user_id, interest_name),
    INDEX idx_user_interests_user (user_id),
    INDEX idx_user_interests_name (interest_name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    creator_id INT NOT NULL,
    creator_type ENUM('user', 'university', 'company') NOT NULL DEFAULT 'user',
    event_format ENUM('online', 'offline') NOT NULL DEFAULT 'offline',
    city VARCHAR(120) DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    meeting_link VARCHAR(255) DEFAULT NULL,
    category VARCHAR(80) NOT NULL DEFAULT 'Другое',
    max_participants INT DEFAULT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME DEFAULT NULL,
    visibility ENUM('public', 'private') NOT NULL DEFAULT 'public',
    status ENUM('active', 'cancelled', 'finished') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_events_start (start_datetime),
    INDEX idx_events_city (city),
    INDEX idx_events_category (category),
    INDEX idx_events_format (event_format),
    INDEX idx_events_creator (creator_id, creator_type),
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS event_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('going', 'interested') NOT NULL DEFAULT 'going',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_event_user (event_id, user_id),
    INDEX idx_event_participants_event (event_id),
    INDEX idx_event_participants_user (user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS event_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    tag_name VARCHAR(80) NOT NULL,
    INDEX idx_event_tags_event (event_id),
    INDEX idx_event_tags_name (tag_name),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS event_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_event_review_user (event_id, user_id),
    INDEX idx_event_reviews_event (event_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
