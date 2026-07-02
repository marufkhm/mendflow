-- Sprint 13: Вакансии / роли в проекте
-- phpMyAdmin: выполняйте по порядку. Ошибки дубликатов (#1060, #1061, #1050) — пропускайте.

CREATE TABLE IF NOT EXISTS project_roles (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    project_id      INT NOT NULL,
    title           VARCHAR(120) NOT NULL,
    description     TEXT,
    specialization  VARCHAR(50) DEFAULT NULL,
    status          ENUM('open','filled','closed') DEFAULT 'open',
    slots           INT NOT NULL DEFAULT 1,
    filled_count    INT NOT NULL DEFAULT 0,
    position        INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_proles_project (project_id),
    KEY idx_proles_status (project_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_role_applications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    role_id     INT NOT NULL,
    project_id  INT NOT NULL,
    user_id     INT NOT NULL,
    message     TEXT,
    status      ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_user (role_id, user_id),
    KEY idx_pra_project (project_id),
    KEY idx_pra_status (project_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Опционально: привязка общей заявки к роли
ALTER TABLE project_join_requests
    ADD COLUMN role_id INT DEFAULT NULL AFTER message;
