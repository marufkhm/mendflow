-- ============================================================
-- MENDFLOW — Обмен / Exchange (uni_exchange)
-- Запусти в phpMyAdmin → твоя база Mendflow (InfinityFree)
--
-- Требуется: таблица university_profiles (migration.sql)
--
-- Если таблица уже есть без колонки university_id — она будет
-- пересоздана. Старые записи в uni_exchange будут удалены.
-- ============================================================

DROP TABLE IF EXISTS uni_exchange;

CREATE TABLE uni_exchange (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    university_id   INT NOT NULL,
    partner_name    VARCHAR(190) NOT NULL,
    partner_country VARCHAR(120) NULL,
    partner_flag    VARCHAR(10)  NULL,
    directions      VARCHAR(255) NULL,
    duration        VARCHAR(50)  NULL,
    language        VARCHAR(50)  NULL,
    deadline        DATE         NULL,
    spots           INT          NULL,
    scholarship     TINYINT(1)   NOT NULL DEFAULT 0,
    url             VARCHAR(255) NULL,
    description     TEXT         NULL,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uni_exchange_uni (university_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
