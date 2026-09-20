CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(120) NOT NULL,
    last_name VARCHAR(120) NOT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(64) NULL,
    token_expiry DATETIME NULL,
    verified_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    organization VARCHAR(190) NULL,
    specialty VARCHAR(120) NULL,
    education_level VARCHAR(50) NULL,
    country_code VARCHAR(12) NULL,
    country_name VARCHAR(120) NULL,
    avatar_path VARCHAR(255) NULL,
    style_font VARCHAR(50) NULL,
    style_accent_color VARCHAR(20) NULL,
    style_card_color VARCHAR(20) NULL,
    bio TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE profile_interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    interest_slug VARCHAR(80) NOT NULL,
    UNIQUE KEY uniq_user_interest (user_id, interest_slug),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    body TEXT NULL,
    post_type VARCHAR(50) NOT NULL DEFAULT 'post',
    feed_mode VARCHAR(50) NOT NULL DEFAULT 'recommendations',
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(255) NULL,
    attachment_kind VARCHAR(50) NULL,
    updated_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE post_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    tag_slug VARCHAR(80) NOT NULL,
    UNIQUE KEY uniq_post_tag (post_id, tag_slug),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

CREATE TABLE post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_post_like (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE post_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_user_id INT NOT NULL,
    followed_user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_follow (follower_user_id, followed_user_id),
    FOREIGN KEY (follower_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (followed_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    description TEXT NOT NULL,
    specialty VARCHAR(120) NULL,
    education_level VARCHAR(50) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE internships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(190) NOT NULL,
    title VARCHAR(190) NOT NULL,
    schedule_type VARCHAR(50) NOT NULL,
    specialty VARCHAR(120) NULL,
    education_level VARCHAR(50) NULL,
    description TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE message_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_key VARCHAR(255) NOT NULL UNIQUE,
    title VARCHAR(190) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE message_thread_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_thread_user (thread_id, user_id),
    FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    sender_user_id INT NULL,
    body TEXT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(255) NULL,
    attachment_kind VARCHAR(50) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE project_workspaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE project_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT NOT NULL,
    title VARCHAR(190) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'backlog',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workspace_id) REFERENCES project_workspaces(id) ON DELETE CASCADE
);
