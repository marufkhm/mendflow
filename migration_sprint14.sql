-- Sprint 14: шаблоны + ресурсы + wiki в docs

CREATE TABLE IF NOT EXISTS project_resources (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    kind        ENUM('github','figma','notion','drive','website','other') NOT NULL DEFAULT 'other',
    title       VARCHAR(120) NOT NULL,
    url         VARCHAR(500) NOT NULL DEFAULT '',
    position    INT NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_pres_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE project_docs
    ADD COLUMN kind ENUM('doc','wiki') NOT NULL DEFAULT 'doc' AFTER title;
