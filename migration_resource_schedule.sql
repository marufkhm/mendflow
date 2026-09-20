-- Float-style resource schedule (weekly allocations per team member)

CREATE TABLE IF NOT EXISTS project_resource_allocations (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    user_id     INT NOT NULL,
    week_start  DATE NOT NULL,
    hours       DECIMAL(6,2) NOT NULL DEFAULT 0,
    label       VARCHAR(120) NOT NULL DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_alloc (project_id, user_id, week_start),
    KEY idx_alloc_project_week (project_id, week_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- weekly_capacity добавляется автоматически через api/project_resource_schedule.php
