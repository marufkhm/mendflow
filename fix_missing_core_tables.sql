-- ⚠️  Только если база УЖЕ есть (таблица users существует).
-- Если users нет — сначала импортируй mendflow_schema.sql целиком!

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

-- НЕ запускай массовый UPDATE email_verified_at — отключит верификацию!
-- Для теста используй reset_email_verification.sql
