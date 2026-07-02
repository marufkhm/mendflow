-- ============================================================
-- Sprint 5 — Realtime + Collaboration
-- Database Migration (FIXED — no FK constraints)
-- InfinityFree / shared hosting compatible
-- ============================================================

-- ── 1. Realtime Events (SSE event bus) ───────────────────────
CREATE TABLE IF NOT EXISTS `realtime_events` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_type`  VARCHAR(64)  NOT NULL,
    `project_id`  INT          NULL,
    `payload`     TEXT         NOT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_project_type`  (`project_id`, `event_type`, `id`),
    INDEX `idx_global_type`   (`event_type`, `id`),
    INDEX `idx_created_at`    (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Realtime Presence ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `realtime_presence` (
    `user_id`          INT          NOT NULL,
    `project_id`       INT          NULL,
    `status`           VARCHAR(20)  NOT NULL DEFAULT 'connected',
    `editing_context`  VARCHAR(120) NULL,
    `last_seen`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`user_id`, `project_id`),
    INDEX `idx_project_last_seen` (`project_id`, `last_seen`),
    INDEX `idx_user_last_seen`    (`user_id`,    `last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Typing Indicators ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `typing_indicators` (
    `user_id`       INT          NOT NULL,
    `context_type`  VARCHAR(40)  NOT NULL,
    `context_id`    INT          NOT NULL,
    `started_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`user_id`, `context_type`, `context_id`),
    INDEX `idx_context` (`context_type`, `context_id`, `started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Done ─────────────────────────────────────────────────────
SELECT 'Sprint 5 migration complete' AS status;
