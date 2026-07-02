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

-- Таблица дружб
CREATE TABLE IF NOT EXISTS friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    confirmed TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_friendship (user1_id, user2_id),
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Индексы для производительности
DROP INDEX IF EXISTS idx_posts_created_at ON posts;
DROP INDEX IF EXISTS idx_posts_user_id ON posts;
DROP INDEX IF EXISTS idx_likes_post_id ON likes;
DROP INDEX IF EXISTS idx_comments_post_id ON comments;
DROP INDEX IF EXISTS idx_sessions_token ON sessions;
DROP INDEX IF EXISTS idx_sessions_expires_at ON sessions;
DROP INDEX IF EXISTS idx_friend_requests_from ON friend_requests;
DROP INDEX IF EXISTS idx_friend_requests_to ON friend_requests;
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
CREATE INDEX idx_friendships_user1 ON friendships(user1_id);
CREATE INDEX idx_friendships_user2 ON friendships(user2_id);
