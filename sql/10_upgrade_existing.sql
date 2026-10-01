-- ============================================================================
-- 10 — Догнать живую базу до текущей схемы (MariaDB)
-- ----------------------------------------------------------------------------
-- Запускать, если таблица users УЖЕ есть. Новую базу ставь через 00_schema.sql.
-- Безопасно повторно: ADD COLUMN IF NOT EXISTS / CREATE TABLE IF NOT EXISTS.
-- Не содержит DROP TABLE (старые uni_*_migration.sql удаляли данные).
--
--   mysql -u mendflow -p mendflow < sql/10_upgrade_existing.sql
-- Затем при ошибке друзей: sql/20_friendships.sql
-- ============================================================================

SET NAMES utf8mb4;

-- ── users: профиль, auth, вуз ───────────────────────────────────────────────
ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('student','company','university','admin','backoffice','moderator') NOT NULL DEFAULT 'student';
ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(500) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS cover_image VARCHAR(500) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS university_id INT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS organization VARCHAR(190) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS specialty VARCHAR(200) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS education VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS country VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(120) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS interest1 VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS interest2 VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS interest3 VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS employer_company_id INT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified_at DATETIME DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS notify_inbox_email TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_seen DATETIME DEFAULT NULL;

-- ── Недостающие таблицы (если API ещё не создавал) ──────────────────────────
-- Полные определения — в 00_schema.sql. Здесь только CREATE IF NOT EXISTS
-- без DROP. Копируем критичные фичи.

CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(512) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sessions_token (token(191)),
    INDEX idx_sessions_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_id INT NOT NULL,
    to_id INT NOT NULL,
    content TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation (from_id, to_id),
    INDEX idx_to_unread (to_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS company_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(190) NOT NULL,
    tagline VARCHAR(255) NULL,
    industry VARCHAR(120) NULL,
    website VARCHAR(255) NULL,
    city VARCHAR(120) NULL,
    country_name VARCHAR(120) NULL,
    description TEXT NULL,
    logo VARCHAR(255) NULL,
    cover VARCHAR(255) NULL,
    team_size INT NULL,
    founded_year INT NULL,
    verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS university_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    university_name VARCHAR(190) NOT NULL,
    city VARCHAR(120) NULL,
    country_name VARCHAR(120) NULL,
    website VARCHAR(255) NULL,
    description TEXT NULL,
    cover VARCHAR(255) NULL,
    logo VARCHAR(255) NULL,
    instagram VARCHAR(120) NULL,
    founded_year INT NULL,
    students_count INT NULL,
    verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    INDEX idx_uni_courses_uni (university_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    INDEX idx_uni_clubs_uni (university_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS uni_club_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_club_member (club_id, user_id),
    INDEX idx_club_members_user (user_id),
    INDEX idx_club_members_club (club_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    INDEX idx_uni_exchange_uni (university_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS uni_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    user_id INT NULL,
    author_type ENUM('university','club') NOT NULL DEFAULT 'university',
    entity_id INT NULL,
    text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uni_posts_uni (university_id),
    INDEX idx_uni_posts_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS uni_follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_uni_follow (university_id, user_id),
    INDEX idx_uni_follows_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    interest_name VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_interest (user_id, interest_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    creator_id INT NOT NULL,
    creator_type ENUM('user','university','company') NOT NULL DEFAULT 'user',
    event_format ENUM('online','offline') NOT NULL DEFAULT 'offline',
    city VARCHAR(120) DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,
    meeting_link VARCHAR(255) DEFAULT NULL,
    category VARCHAR(80) NOT NULL DEFAULT 'Другое',
    max_participants INT DEFAULT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME DEFAULT NULL,
    visibility ENUM('public','private') NOT NULL DEFAULT 'public',
    status ENUM('active','cancelled','finished') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('going','interested') NOT NULL DEFAULT 'going',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_event_user (event_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    tag_name VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_event_review_user (event_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    cover_url VARCHAR(500) DEFAULT NULL,
    category ENUM('product','research','design','ai_ml','mobile','web','other') DEFAULT 'other',
    stage ENUM('idea','prototype','mvp','beta','launched','completed') DEFAULT 'idea',
    tags VARCHAR(500) DEFAULT NULL,
    is_public TINYINT(1) DEFAULT 1,
    looking_for VARCHAR(500) DEFAULT NULL,
    website_url VARCHAR(300) DEFAULT NULL,
    github_url VARCHAR(300) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    permission_role ENUM('owner','admin','member','viewer') DEFAULT 'member',
    specialization_role ENUM('product_manager','frontend','backend','designer','ml_engineer','analyst','qa','marketing','fullstack','other') DEFAULT 'other',
    weekly_capacity DECIMAL(5,2) NOT NULL DEFAULT 40,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_member (project_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_join_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT,
    role_id INT DEFAULT NULL,
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_request (project_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_followers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (project_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    author_id INT NOT NULL,
    type ENUM('update','milestone','release','hiring','general') DEFAULT 'general',
    title VARCHAR(300) DEFAULT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_plike (post_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_post_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_post_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    emoji VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (post_id, user_id, emoji)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    type ENUM('joined','left','post','task_done','milestone','release','role_changed') DEFAULT 'post',
    meta VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    milestone_id INT DEFAULT NULL,
    assignee_id INT DEFAULT NULL,
    created_by INT NOT NULL,
    title VARCHAR(300) NOT NULL,
    description TEXT,
    status ENUM('backlog','todo','in_progress','review','done') DEFAULT 'backlog',
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    due_date DATE DEFAULT NULL,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_task_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_docs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    author_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    title VARCHAR(300) NOT NULL DEFAULT 'Без названия',
    kind ENUM('doc','wiki') NOT NULL DEFAULT 'doc',
    content LONGTEXT,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    stage_key ENUM('idea','mvp','beta','launch') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    target_date DATE DEFAULT NULL,
    status ENUM('pending','in_progress','done') DEFAULT 'pending',
    position INT DEFAULT 0,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_project_stage (project_id, stage_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(120) NOT NULL,
    description TEXT,
    specialization VARCHAR(50) DEFAULT NULL,
    status ENUM('open','filled','closed') DEFAULT 'open',
    slots INT NOT NULL DEFAULT 1,
    filled_count INT NOT NULL DEFAULT 0,
    position INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_role_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT,
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_user (role_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    kind ENUM('github','figma','notion','drive','website','training','other') NOT NULL DEFAULT 'other',
    title VARCHAR(120) NOT NULL,
    url VARCHAR(500) NOT NULL DEFAULT '',
    position INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_resource_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    category ENUM('people','budget','tools','training','infrastructure') NOT NULL DEFAULT 'tools',
    title VARCHAR(120) NOT NULL,
    planned_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    allocated_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    unit VARCHAR(20) NOT NULL DEFAULT 'pcs',
    status ENUM('planned','partial','ready') NOT NULL DEFAULT 'planned',
    notes VARCHAR(500) NOT NULL DEFAULT '',
    due_date DATE NULL DEFAULT NULL,
    position INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_resource_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    week_start DATE NOT NULL,
    hours DECIMAL(6,2) NOT NULL DEFAULT 0,
    label VARCHAR(120) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_alloc (project_id, user_id, week_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS realtime_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(64) NOT NULL,
    project_id INT NULL,
    payload TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS realtime_presence (
    user_id INT NOT NULL,
    project_id INT NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'connected',
    editing_context VARCHAR(120) NULL,
    last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS typing_indicators (
    user_id INT NOT NULL,
    context_type VARCHAR(40) NOT NULL,
    context_id INT NOT NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, context_type, context_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_engagement (
    post_id INT NOT NULL PRIMARY KEY,
    impressions INT NOT NULL DEFAULT 0,
    clicks INT NOT NULL DEFAULT 0,
    read_time_ms BIGINT NOT NULL DEFAULT 0,
    watch_time_ms BIGINT NOT NULL DEFAULT 0,
    dm_shares INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_dm_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_affinity (
    user_low INT NOT NULL,
    user_high INT NOT NULL,
    score FLOAT NOT NULL DEFAULT 0,
    likes INT NOT NULL DEFAULT 0,
    comments INT NOT NULL DEFAULT 0,
    dm_shares INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_low, user_high)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_distribution (
    post_id INT NOT NULL PRIMARY KEY,
    tier ENUM('test','expanded','full') NOT NULL DEFAULT 'test',
    test_impressions INT NOT NULL DEFAULT 0,
    test_clicks INT NOT NULL DEFAULT 0,
    test_started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expanded_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_signal_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    signal_type ENUM('impression','click','read_ms','watch_ms') NOT NULL,
    value INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint VARCHAR(512) NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_push_endpoint (endpoint(191)),
    KEY idx_push_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS email_verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pr_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    action VARCHAR(64) NOT NULL,
    hit_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_user_id INT DEFAULT NULL,
    type VARCHAR(50) NOT NULL,
    post_id INT DEFAULT NULL,
    post_preview VARCHAR(255) DEFAULT NULL,
    post_type VARCHAR(32) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inbox_email_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    from_user_id INT DEFAULT NULL,
    ref_id INT NOT NULL DEFAULT 0,
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_iel_user_sent (user_id, sent_at),
    KEY idx_iel_dedup (user_id, type, ref_id, sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Колонки на уже существующих таблицах ────────────────────────────────────
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS banned_at DATETIME DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS ban_reason VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS deleted_at DATETIME DEFAULT NULL;
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_university_id (university_id);

ALTER TABLE friend_requests ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE posts ADD COLUMN IF NOT EXISTS post_type VARCHAR(32) NOT NULL DEFAULT 'post';
ALTER TABLE posts ADD COLUMN IF NOT EXISTS article_id INT DEFAULT NULL;

ALTER TABLE projects ADD COLUMN IF NOT EXISTS slug VARCHAR(100) DEFAULT NULL AFTER title;
ALTER TABLE projects ADD UNIQUE INDEX IF NOT EXISTS idx_projects_slug (slug);

ALTER TABLE notifications ADD COLUMN IF NOT EXISTS feature_request_id INT DEFAULT NULL AFTER post_id;
ALTER TABLE uni_club_members ADD COLUMN IF NOT EXISTS role ENUM('member','admin','owner') NOT NULL DEFAULT 'member';
ALTER TABLE project_milestones ADD COLUMN IF NOT EXISTS start_date DATE DEFAULT NULL AFTER target_date;

ALTER TABLE university_profiles ADD COLUMN IF NOT EXISTS cover VARCHAR(255) NULL;
ALTER TABLE university_profiles ADD COLUMN IF NOT EXISTS logo VARCHAR(255) NULL;
ALTER TABLE university_profiles ADD COLUMN IF NOT EXISTS instagram VARCHAR(120) NULL;
ALTER TABLE university_profiles ADD COLUMN IF NOT EXISTS founded_year INT NULL;
ALTER TABLE university_profiles ADD COLUMN IF NOT EXISTS students_count INT NULL;

ALTER TABLE events ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) DEFAULT NULL;
ALTER TABLE events ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) DEFAULT NULL;

ALTER TABLE project_tasks ADD COLUMN IF NOT EXISTS milestone_id INT DEFAULT NULL AFTER project_id;
ALTER TABLE project_tasks ADD COLUMN IF NOT EXISTS due_date DATE DEFAULT NULL;
ALTER TABLE project_tasks ADD COLUMN IF NOT EXISTS checklist JSON DEFAULT NULL AFTER description;
ALTER TABLE project_tasks ADD COLUMN IF NOT EXISTS depends_on JSON DEFAULT NULL AFTER checklist;
ALTER TABLE project_tasks MODIFY status VARCHAR(64) NOT NULL DEFAULT 'backlog';
ALTER TABLE project_activity MODIFY type VARCHAR(64) NOT NULL DEFAULT 'post';
ALTER TABLE project_activity MODIFY meta VARCHAR(1000) DEFAULT NULL;
ALTER TABLE project_join_requests ADD COLUMN IF NOT EXISTS role_id INT DEFAULT NULL AFTER message;
ALTER TABLE project_docs ADD COLUMN IF NOT EXISTS kind ENUM('doc','wiki') NOT NULL DEFAULT 'doc' AFTER title;
ALTER TABLE project_members ADD COLUMN IF NOT EXISTS weekly_capacity DECIMAL(5,2) NOT NULL DEFAULT 40 AFTER specialization_role;

ALTER TABLE project_resources
    MODIFY kind ENUM('github','figma','notion','drive','website','training','other') NOT NULL DEFAULT 'other';

SELECT 'OK — upgrade_existing выполнен. Проверка: sql/90_check.sql' AS status;
