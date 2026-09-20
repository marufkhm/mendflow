-- ═══════════════════════════════════════════════════════════════
-- MENDFLOW — Projects System Migration
-- Sprint 0: Database Foundation
-- Safe to run multiple times (IF NOT EXISTS everywhere)
-- ═══════════════════════════════════════════════════════════════

-- 1. PROJECTS ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS projects (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    owner_id      INT NOT NULL,
    title         VARCHAR(200) NOT NULL,
    description   TEXT,
    cover_url     VARCHAR(500) DEFAULT NULL,
    category      ENUM('product','research','design','ai_ml','mobile','web','other') DEFAULT 'other',
    stage         ENUM('idea','prototype','mvp','beta','launched','completed') DEFAULT 'idea',
    tags          VARCHAR(500) DEFAULT NULL,
    is_public     TINYINT(1) DEFAULT 1,
    looking_for   VARCHAR(500) DEFAULT NULL,
    website_url   VARCHAR(300) DEFAULT NULL,
    github_url    VARCHAR(300) DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. PROJECT MEMBERS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_members (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    project_id          INT NOT NULL,
    user_id             INT NOT NULL,
    permission_role     ENUM('owner','admin','member','viewer') DEFAULT 'member',
    specialization_role ENUM('product_manager','frontend','backend','designer','ml_engineer','analyst','qa','marketing','fullstack','other') DEFAULT 'other',
    joined_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_member (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
);

-- 3. PROJECT JOIN REQUESTS ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_join_requests (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    user_id     INT NOT NULL,
    message     TEXT,
    status      ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_request (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
);

-- 4. PROJECT FOLLOWERS ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_followers (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    user_id     INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
);

-- 5. PROJECT POSTS (Feed) ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_posts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    author_id   INT NOT NULL,
    type        ENUM('update','milestone','release','hiring','general') DEFAULT 'general',
    title       VARCHAR(300) DEFAULT NULL,
    content     TEXT NOT NULL,
    image_url   VARCHAR(500) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id)  REFERENCES users(id)    ON DELETE CASCADE
);

-- 6. PROJECT POST LIKES ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_post_likes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL,
    user_id     INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_plike (post_id, user_id),
    FOREIGN KEY (post_id)  REFERENCES project_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)          ON DELETE CASCADE
);

-- 7. PROJECT POST COMMENTS ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_post_comments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL,
    author_id   INT NOT NULL,
    content     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id)   REFERENCES project_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id)          ON DELETE CASCADE
);

-- 8. PROJECT TASKS (Kanban) ────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_tasks (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    project_id   INT NOT NULL,
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
    FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assignee_id) REFERENCES users(id)    ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id)    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS project_task_comments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    task_id     INT NOT NULL,
    author_id   INT NOT NULL,
    content     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id)   REFERENCES project_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id)          ON DELETE CASCADE
);

-- 9. PROJECT DOCS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS project_docs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    author_id   INT NOT NULL,
    parent_id   INT DEFAULT NULL,
    title       VARCHAR(300) NOT NULL DEFAULT 'Без названия',
    content     LONGTEXT,
    position    INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id)      ON DELETE CASCADE,
    FOREIGN KEY (author_id)  REFERENCES users(id)          ON DELETE CASCADE,
    FOREIGN KEY (parent_id)  REFERENCES project_docs(id)   ON DELETE SET NULL
);

-- 10. INDEXES ──────────────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_projects_owner      ON projects(owner_id);
CREATE INDEX IF NOT EXISTS idx_projects_category   ON projects(category);
CREATE INDEX IF NOT EXISTS idx_projects_stage      ON projects(stage);
CREATE INDEX IF NOT EXISTS idx_projects_created    ON projects(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_pmembers_project    ON project_members(project_id);
CREATE INDEX IF NOT EXISTS idx_pmembers_user       ON project_members(user_id);
CREATE INDEX IF NOT EXISTS idx_pjoin_project       ON project_join_requests(project_id);
CREATE INDEX IF NOT EXISTS idx_pfollowers_project  ON project_followers(project_id);
CREATE INDEX IF NOT EXISTS idx_pposts_project      ON project_posts(project_id);
CREATE INDEX IF NOT EXISTS idx_pposts_created      ON project_posts(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_ptasks_project      ON project_tasks(project_id);
CREATE INDEX IF NOT EXISTS idx_ptasks_status       ON project_tasks(status);
CREATE INDEX IF NOT EXISTS idx_ptask_comments_task ON project_task_comments(task_id);
CREATE INDEX IF NOT EXISTS idx_pdocs_project       ON project_docs(project_id);

-- ═══════════════════════════════════════════════════════════════
-- SPRINT 2 additions
-- ═══════════════════════════════════════════════════════════════

-- Emoji reactions on project posts
CREATE TABLE IF NOT EXISTS project_post_reactions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    post_id    INT NOT NULL,
    user_id    INT NOT NULL,
    emoji      VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (post_id, user_id, emoji),
    FOREIGN KEY (post_id)  REFERENCES project_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)         ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_preactions_post ON project_post_reactions(post_id);

-- Project activity log (auto-generated events)
CREATE TABLE IF NOT EXISTS project_activity (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id    INT DEFAULT NULL,
    type       ENUM('joined','left','post','task_done','milestone','release','role_changed') DEFAULT 'post',
    meta       VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_pactivity_project ON project_activity(project_id);
CREATE INDEX IF NOT EXISTS idx_pactivity_created ON project_activity(created_at DESC);
