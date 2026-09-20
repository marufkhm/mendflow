-- Resource planning tools for projects

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
    KEY idx_prp_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
