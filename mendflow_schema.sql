-- ============================================================================
-- MENDFLOW — полная схема БД (единый файл)
-- ----------------------------------------------------------------------------
-- Совместимо с MySQL 8 и MariaDB (используются только CREATE TABLE IF NOT EXISTS
-- с полными определениями колонок — без ADD COLUMN IF NOT EXISTS / CREATE INDEX
-- IF NOT EXISTS, которых нет в обычном MySQL).
--
-- Безопасно запускать несколько раз. Остальные «фичевые» таблицы (courses,
-- articles, discussions, jobs, badges, reposts, notifications, rate_limits,
-- email_verifications, password_resets, company_follows, project_calendar и др.)
-- приложение создаёт автоматически при первом обращении к соответствующему API.
--
-- Импорт на сервере:
--   mysql -u mendflow -p mendflow < mendflow_schema.sql
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Пользователи (все колонки сразу) ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    first_name          VARCHAR(100) NOT NULL,
    last_name           VARCHAR(100) NOT NULL,
    role                ENUM('student','company','university','admin','backoffice','moderator') NOT NULL DEFAULT 'student',
    email               VARCHAR(255) UNIQUE NOT NULL,
    password_hash       VARCHAR(255) NOT NULL,
    avatar              VARCHAR(500) DEFAULT NULL,
    cover_image         VARCHAR(500) DEFAULT NULL,
    organization        VARCHAR(190) DEFAULT NULL,
    specialty           VARCHAR(200) DEFAULT NULL,
    education           VARCHAR(100) DEFAULT NULL,
    country             VARCHAR(100) DEFAULT NULL,
    city                VARCHAR(120) DEFAULT NULL,
    bio                 TEXT         DEFAULT NULL,
    interest1           VARCHAR(100) DEFAULT NULL,
    interest2           VARCHAR(100) DEFAULT NULL,
    interest3           VARCHAR(100) DEFAULT NULL,
    employer_company_id INT          DEFAULT NULL,
    is_verified         TINYINT(1)   NOT NULL DEFAULT 0,
    is_admin            TINYINT(1)   NOT NULL DEFAULT 0,
    email_verified_at   DATETIME     DEFAULT NULL,
    last_seen           DATETIME     DEFAULT NULL,
    created_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Организации (каталог «Сеть») ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS organizations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(120) NOT NULL UNIQUE,
    type         ENUM('universities','companies') NOT NULL,
    title        VARCHAR(180) NOT NULL,
    subtitle     VARCHAR(255) NOT NULL,
    meta         VARCHAR(180) NOT NULL,
    description  TEXT NOT NULL,
    tags_text    TEXT DEFAULT NULL,
    action_label VARCHAR(120) NOT NULL DEFAULT 'Открыть',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Сессии ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sessions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token      VARCHAR(512) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sessions_token (token(191)),
    INDEX idx_sessions_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Посты / лайки / комментарии ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS posts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    text       TEXT NOT NULL,
    image_url  VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_posts_created (created_at),
    INDEX idx_posts_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS likes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    post_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, post_id),
    INDEX idx_likes_post (post_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    post_id    INT NOT NULL,
    text       TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_comments_post (post_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Друзья / сообщения ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS friend_requests (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    from_id    INT NOT NULL,
    to_id      INT NOT NULL,
    status     ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pair (from_id, to_id),
    INDEX idx_fr_from (from_id),
    INDEX idx_fr_to (to_id),
    FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_id)   REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS friendships (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user1_id   INT NOT NULL,
    user2_id   INT NOT NULL,
    confirmed  TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_friendship (user1_id, user2_id),
    INDEX idx_friendships_u1 (user1_id),
    INDEX idx_friendships_u2 (user2_id),
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    from_id    INT NOT NULL,
    to_id      INT NOT NULL,
    content    TEXT NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation (from_id, to_id),
    INDEX idx_to_unread (to_id, is_read),
    FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_id)   REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Интересы пользователя ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_interests (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    interest_name VARCHAR(80) NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_interest (user_id, interest_name),
    INDEX idx_user_interests_user (user_id),
    INDEX idx_user_interests_name (interest_name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Профили компаний / университетов ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS company_profiles (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL UNIQUE,
    company_name VARCHAR(190) NOT NULL,
    tagline      VARCHAR(255) NULL,
    industry     VARCHAR(120) NULL,
    website      VARCHAR(255) NULL,
    city         VARCHAR(120) NULL,
    country_name VARCHAR(120) NULL,
    description  TEXT NULL,
    logo         VARCHAR(255) NULL,
    cover        VARCHAR(255) NULL,
    team_size    INT NULL,
    founded_year INT NULL,
    verified     TINYINT(1) NOT NULL DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS university_profiles (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    university_name VARCHAR(190) NOT NULL,
    city            VARCHAR(120) NULL,
    country_name    VARCHAR(120) NULL,
    website         VARCHAR(255) NULL,
    description     TEXT NULL,
    cover           VARCHAR(255) NULL,
    logo            VARCHAR(255) NULL,
    instagram       VARCHAR(120) NULL,
    founded_year    INT NULL,
    students_count  INT NULL,
    verified        TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Университет: курсы, клубы, обмен, посты, подписки ───────────────────────
CREATE TABLE IF NOT EXISTS uni_courses (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    title         VARCHAR(255) NOT NULL,
    direction     VARCHAR(50)  NOT NULL DEFAULT 'other',
    duration      VARCHAR(50)  NULL,
    language      VARCHAR(50)  NULL,
    credits       INT NULL,
    tuition_kzt   INT NULL,
    description   TEXT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uni_courses_uni (university_id),
    FOREIGN KEY (university_id) REFERENCES university_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS uni_clubs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    name          VARCHAR(190) NOT NULL,
    category      VARCHAR(50)  NOT NULL DEFAULT 'other',
    description   TEXT NULL,
    logo          VARCHAR(255) NULL,
    instagram     VARCHAR(120) NULL,
    telegram      VARCHAR(120) NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uni_clubs_uni (university_id),
    FOREIGN KEY (university_id) REFERENCES university_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS uni_club_members (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    club_id    INT NOT NULL,
    user_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_club_member (club_id, user_id),
    INDEX idx_club_members_user (user_id),
    INDEX idx_club_members_club (club_id),
    FOREIGN KEY (club_id) REFERENCES uni_clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS uni_exchange (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    university_id   INT NOT NULL,
    partner_name    VARCHAR(190) NOT NULL,
    partner_country VARCHAR(120) NULL,
    partner_flag    VARCHAR(10)  NULL,
    directions      VARCHAR(255) NULL,
    duration        VARCHAR(50)  NULL,
    language        VARCHAR(50)  NULL,
    deadline        DATE NULL,
    spots           INT NULL,
    scholarship     TINYINT(1) NOT NULL DEFAULT 0,
    url             VARCHAR(255) NULL,
    description     TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uni_exchange_uni (university_id),
    FOREIGN KEY (university_id) REFERENCES university_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS uni_posts (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    user_id       INT NULL,
    author_type   ENUM('university','club') NOT NULL DEFAULT 'university',
    entity_id     INT NULL,
    text          TEXT NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uni_posts_uni (university_id),
    INDEX idx_uni_posts_user (user_id),
    FOREIGN KEY (university_id) REFERENCES university_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS uni_follows (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    user_id       INT NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_uni_follow (university_id, user_id),
    INDEX idx_uni_follows_user (user_id),
    FOREIGN KEY (university_id) REFERENCES university_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Мероприятия ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS events (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(180) NOT NULL,
    description      TEXT NOT NULL,
    cover_image      VARCHAR(255) DEFAULT NULL,
    creator_id       INT NOT NULL,
    creator_type     ENUM('user','university','company') NOT NULL DEFAULT 'user',
    event_format     ENUM('online','offline') NOT NULL DEFAULT 'offline',
    city             VARCHAR(120) DEFAULT NULL,
    location         VARCHAR(255) DEFAULT NULL,
    meeting_link     VARCHAR(255) DEFAULT NULL,
    category         VARCHAR(80) NOT NULL DEFAULT 'Другое',
    max_participants INT DEFAULT NULL,
    start_datetime   DATETIME NOT NULL,
    end_datetime     DATETIME DEFAULT NULL,
    visibility       ENUM('public','private') NOT NULL DEFAULT 'public',
    status           ENUM('active','cancelled','finished') NOT NULL DEFAULT 'active',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_events_start (start_datetime),
    INDEX idx_events_city (city),
    INDEX idx_events_category (category),
    INDEX idx_events_format (event_format),
    INDEX idx_events_creator (creator_id, creator_type),
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_participants (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    event_id   INT NOT NULL,
    user_id    INT NOT NULL,
    status     ENUM('going','interested') NOT NULL DEFAULT 'going',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_event_user (event_id, user_id),
    INDEX idx_ep_event (event_id),
    INDEX idx_ep_user (user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_tags (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    tag_name VARCHAR(80) NOT NULL,
    INDEX idx_event_tags_event (event_id),
    INDEX idx_event_tags_name (tag_name),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    event_id   INT NOT NULL,
    user_id    INT NOT NULL,
    rating     TINYINT NOT NULL,
    comment    TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_event_review_user (event_id, user_id),
    INDEX idx_event_reviews_event (event_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Проекты ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS projects (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    owner_id    INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT,
    cover_url   VARCHAR(500) DEFAULT NULL,
    category    ENUM('product','research','design','ai_ml','mobile','web','other') DEFAULT 'other',
    stage       ENUM('idea','prototype','mvp','beta','launched','completed') DEFAULT 'idea',
    tags        VARCHAR(500) DEFAULT NULL,
    is_public   TINYINT(1) DEFAULT 1,
    looking_for VARCHAR(500) DEFAULT NULL,
    website_url VARCHAR(300) DEFAULT NULL,
    github_url  VARCHAR(300) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_projects_owner (owner_id),
    INDEX idx_projects_category (category),
    INDEX idx_projects_stage (stage),
    INDEX idx_projects_created (created_at),
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_members (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    project_id          INT NOT NULL,
    user_id             INT NOT NULL,
    permission_role     ENUM('owner','admin','member','viewer') DEFAULT 'member',
    specialization_role ENUM('product_manager','frontend','backend','designer','ml_engineer','analyst','qa','marketing','fullstack','other') DEFAULT 'other',
    joined_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_member (project_id, user_id),
    INDEX idx_pmembers_project (project_id),
    INDEX idx_pmembers_user (user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_join_requests (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id    INT NOT NULL,
    message    TEXT,
    role_id    INT DEFAULT NULL,
    status     ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_request (project_id, user_id),
    INDEX idx_pjoin_project (project_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_followers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (project_id, user_id),
    INDEX idx_pfollowers_project (project_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_posts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    author_id  INT NOT NULL,
    type       ENUM('update','milestone','release','hiring','general') DEFAULT 'general',
    title      VARCHAR(300) DEFAULT NULL,
    content    TEXT NOT NULL,
    image_url  VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pposts_project (project_id),
    INDEX idx_pposts_created (created_at),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_post_likes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    post_id    INT NOT NULL,
    user_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_plike (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES project_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_post_comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    post_id    INT NOT NULL,
    author_id  INT NOT NULL,
    content    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES project_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_post_reactions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    post_id    INT NOT NULL,
    user_id    INT NOT NULL,
    emoji      VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (post_id, user_id, emoji),
    INDEX idx_preactions_post (post_id),
    FOREIGN KEY (post_id) REFERENCES project_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_activity (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id    INT DEFAULT NULL,
    type       ENUM('joined','left','post','task_done','milestone','release','role_changed') DEFAULT 'post',
    meta       VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pactivity_project (project_id),
    INDEX idx_pactivity_created (created_at),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_tasks (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    milestone_id INT DEFAULT NULL,
    assignee_id  INT DEFAULT NULL,
    created_by   INT NOT NULL,
    title        VARCHAR(300) NOT NULL,
    description  TEXT,
    status       ENUM('backlog','todo','in_progress','review','done') DEFAULT 'backlog',
    priority     ENUM('low','medium','high','urgent') DEFAULT 'medium',
    due_date     DATE DEFAULT NULL,
    position     INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ptasks_project (project_id),
    INDEX idx_ptasks_status (status),
    INDEX idx_ptasks_milestone (milestone_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_task_comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    task_id    INT NOT NULL,
    author_id  INT NOT NULL,
    content    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ptask_comments_task (task_id),
    FOREIGN KEY (task_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_docs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    author_id  INT NOT NULL,
    parent_id  INT DEFAULT NULL,
    title      VARCHAR(300) NOT NULL DEFAULT 'Без названия',
    kind       ENUM('doc','wiki') NOT NULL DEFAULT 'doc',
    content    LONGTEXT,
    position   INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pdocs_project (project_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES project_docs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_milestones (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
    stage_key    ENUM('idea','mvp','beta','launch') NOT NULL,
    title        VARCHAR(200) NOT NULL,
    description  TEXT,
    target_date  DATE DEFAULT NULL,
    status       ENUM('pending','in_progress','done') DEFAULT 'pending',
    position     INT DEFAULT 0,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_project_stage (project_id, stage_key),
    INDEX idx_pmilestones_project (project_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_roles (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    project_id     INT NOT NULL,
    title          VARCHAR(120) NOT NULL,
    description    TEXT,
    specialization VARCHAR(50) DEFAULT NULL,
    status         ENUM('open','filled','closed') DEFAULT 'open',
    slots          INT NOT NULL DEFAULT 1,
    filled_count   INT NOT NULL DEFAULT 0,
    position       INT NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_proles_project (project_id),
    KEY idx_proles_status (project_id, status),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_role_applications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    role_id    INT NOT NULL,
    project_id INT NOT NULL,
    user_id    INT NOT NULL,
    message    TEXT,
    status     ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_user (role_id, user_id),
    KEY idx_pra_project (project_id),
    KEY idx_pra_status (project_id, status),
    FOREIGN KEY (role_id) REFERENCES project_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_resources (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    kind       ENUM('github','figma','notion','drive','website','training','other') NOT NULL DEFAULT 'other',
    title      VARCHAR(120) NOT NULL,
    url        VARCHAR(500) NOT NULL DEFAULT '',
    position   INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_pres_project (project_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_resource_plans (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    project_id       INT NOT NULL,
    category         ENUM('people','budget','tools','training','infrastructure') NOT NULL DEFAULT 'tools',
    title            VARCHAR(120) NOT NULL,
    planned_amount   DECIMAL(12,2) NOT NULL DEFAULT 0,
    allocated_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    unit             VARCHAR(20) NOT NULL DEFAULT 'pcs',
    status           ENUM('planned','partial','ready') NOT NULL DEFAULT 'planned',
    notes            VARCHAR(500) NOT NULL DEFAULT '',
    due_date         DATE NULL DEFAULT NULL,
    position         INT NOT NULL DEFAULT 0,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_prp_project (project_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_resource_allocations (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id    INT NOT NULL,
    week_start DATE NOT NULL,
    hours      DECIMAL(6,2) NOT NULL DEFAULT 0,
    label      VARCHAR(120) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_alloc (project_id, user_id, week_start),
    KEY idx_alloc_project_week (project_id, week_start),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Realtime (SSE) ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS realtime_events (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(64) NOT NULL,
    project_id INT NULL,
    payload    TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project_type (project_id, event_type, id),
    INDEX idx_global_type (event_type, id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS realtime_presence (
    user_id         INT NOT NULL,
    project_id      INT NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'connected',
    editing_context VARCHAR(120) NULL,
    last_seen       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, project_id),
    INDEX idx_project_last_seen (project_id, last_seen),
    INDEX idx_user_last_seen (user_id, last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS typing_indicators (
    user_id      INT NOT NULL,
    context_type VARCHAR(40) NOT NULL,
    context_id   INT NOT NULL,
    started_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, context_type, context_id),
    INDEX idx_context (context_type, context_id, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Умная лента (feed ranking) ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS post_engagement (
    post_id       INT NOT NULL PRIMARY KEY,
    impressions   INT NOT NULL DEFAULT 0,
    clicks        INT NOT NULL DEFAULT 0,
    read_time_ms  BIGINT NOT NULL DEFAULT 0,
    watch_time_ms BIGINT NOT NULL DEFAULT 0,
    dm_shares     INT NOT NULL DEFAULT 0,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_dm_shares (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    post_id      INT NOT NULL,
    from_user_id INT NOT NULL,
    to_user_id   INT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pds_post (post_id),
    KEY idx_pds_from (from_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_affinity (
    user_low   INT NOT NULL,
    user_high  INT NOT NULL,
    score      FLOAT NOT NULL DEFAULT 0,
    likes      INT NOT NULL DEFAULT 0,
    comments   INT NOT NULL DEFAULT 0,
    dm_shares  INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_low, user_high)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_distribution (
    post_id          INT NOT NULL PRIMARY KEY,
    tier             ENUM('test','expanded','full') NOT NULL DEFAULT 'test',
    test_impressions INT NOT NULL DEFAULT 0,
    test_clicks      INT NOT NULL DEFAULT 0,
    test_started_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expanded_at      TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_signal_events (
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL,
    user_id     INT NOT NULL,
    signal_type ENUM('impression','click','read_ms','watch_ms') NOT NULL,
    value       INT NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pse_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Web Push (Sprint 19) ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    endpoint   VARCHAR(512) NOT NULL,
    p256dh     VARCHAR(255) NOT NULL,
    auth       VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_push_endpoint (endpoint(191)),
    KEY idx_push_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Email-верификация и сброс пароля (Sprint 10) ────────────────────────────
CREATE TABLE IF NOT EXISTS email_verifications (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    email      VARCHAR(255) NOT NULL,
    code       VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ev_email (email),
    INDEX idx_ev_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token      VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at    DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pr_token (token),
    INDEX idx_pr_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Rate limiting (Sprint 9) ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rate_limits (
    id     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip     VARCHAR(45) NOT NULL,
    action VARCHAR(64) NOT NULL,
    hit_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rl_ip_action (ip, action, hit_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Уведомления ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    from_user_id INT DEFAULT NULL,
    type         VARCHAR(50) NOT NULL,
    post_id      INT DEFAULT NULL,
    post_preview VARCHAR(255) DEFAULT NULL,
    post_type    VARCHAR(32) DEFAULT NULL,
    is_read      TINYINT(1) DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notif_user (user_id),
    KEY idx_notif_type (type),
    KEY idx_notif_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Готово. Остальные таблицы (courses, articles, discussions, jobs, badges,
-- reposts, feature_requests, company_follows, project_calendar, post_signals
-- и пр.) создаются автоматически при первом обращении к соответствующему API.
-- ============================================================================
