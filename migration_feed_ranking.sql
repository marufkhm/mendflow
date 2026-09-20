-- Smart feed ranking: engagement signals, DM shares, affinity, distribution tiers

CREATE TABLE IF NOT EXISTS post_engagement (
    post_id         INT NOT NULL PRIMARY KEY,
    impressions     INT NOT NULL DEFAULT 0,
    clicks          INT NOT NULL DEFAULT 0,
    read_time_ms    BIGINT NOT NULL DEFAULT 0,
    watch_time_ms   BIGINT NOT NULL DEFAULT 0,
    dm_shares       INT NOT NULL DEFAULT 0,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_dm_shares (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    post_id      INT NOT NULL,
    from_user_id INT NOT NULL,
    to_user_id   INT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pds_post (post_id),
    KEY idx_pds_from (from_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_affinity (
    user_low    INT NOT NULL,
    user_high   INT NOT NULL,
    score       FLOAT NOT NULL DEFAULT 0,
    likes       INT NOT NULL DEFAULT 0,
    comments    INT NOT NULL DEFAULT 0,
    dm_shares   INT NOT NULL DEFAULT 0,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_low, user_high)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_distribution (
    post_id          INT NOT NULL PRIMARY KEY,
    tier             ENUM('test','expanded','full') NOT NULL DEFAULT 'test',
    test_impressions INT NOT NULL DEFAULT 0,
    test_clicks      INT NOT NULL DEFAULT 0,
    test_started_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expanded_at      TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_signal_events (
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL,
    user_id     INT NOT NULL,
    signal_type ENUM('impression','click','read_ms','watch_ms') NOT NULL,
    value       INT NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pse_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
