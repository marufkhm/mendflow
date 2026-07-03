-- ⚠️  Только если база УЖЕ есть (таблица users существует).
-- Если users нет — сначала импортируй mendflow_schema.sql целиком!
--
--   mysql -u mendflow -p mendflow < mendflow_schema.sql

CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(512) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sessions_token (token),
    KEY idx_sessions_user (user_id),
    KEY idx_sessions_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- MariaDB 10.0.2+ / MySQL 8.0.12+
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified_at DATETIME NULL;

UPDATE users
SET email_verified_at = COALESCE(created_at, NOW())
WHERE email_verified_at IS NULL;
