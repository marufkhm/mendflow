-- Sprint 13: Roadmap / вехи проекта
-- phpMyAdmin: выполняйте по порядку.
--
-- Можно ПРОПУСКАТЬ строки с ошибками:
--   #1060 Duplicate column name     → колонка уже есть
--   #1061 Duplicate key name        → индекс уже есть
--   #1050 Table already exists      → таблица уже есть

-- 1. Таблица вех
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
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- 2. Колонка milestone_id в задачах
ALTER TABLE project_tasks
    ADD COLUMN milestone_id INT DEFAULT NULL AFTER project_id;

-- 3. Индексы (если #1061 — уже созданы, пропустите)
-- MariaDB / MySQL 8.0.29+:
-- CREATE INDEX IF NOT EXISTS idx_pmilestones_project ON project_milestones(project_id);
-- CREATE INDEX IF NOT EXISTS idx_ptasks_milestone ON project_tasks(milestone_id);

-- Универсальный вариант (ошибка дубликата = всё ок):
CREATE INDEX idx_pmilestones_project ON project_milestones(project_id);
CREATE INDEX idx_ptasks_milestone ON project_tasks(milestone_id);
