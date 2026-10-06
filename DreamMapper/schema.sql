CREATE DATABASE IF NOT EXISTS dreammapper CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dreammapper;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE dreams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    prompt TEXT NOT NULL,
    target_date DATE NULL,
    status ENUM('active','completed','paused','archived') DEFAULT 'active',
    success_probability DECIMAL(5,2) DEFAULT 50.00,
    dream_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dream_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_dream_user (user_id),
    INDEX idx_dream_status (status)
) ENGINE=InnoDB;

CREATE TABLE nodes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dream_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    node_type ENUM('dream','goal','milestone','task','risk','backup') DEFAULT 'task',
    status ENUM('pending','in_progress','completed') DEFAULT 'pending',
    priority ENUM('low','medium','high') DEFAULT 'medium',
    estimated_days INT DEFAULT 1,
    reward_xp INT DEFAULT 10,
    risk_level ENUM('low','medium','high') DEFAULT 'low',
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_node_dream FOREIGN KEY (dream_id) REFERENCES dreams(id) ON DELETE CASCADE,
    CONSTRAINT fk_node_parent FOREIGN KEY (parent_id) REFERENCES nodes(id) ON DELETE SET NULL,
    INDEX idx_nodes_dream (dream_id),
    INDEX idx_nodes_parent (parent_id),
    INDEX idx_nodes_status (status)
) ENGINE=InnoDB;

CREATE TABLE todos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    dream_id INT UNSIGNED NOT NULL,
    node_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    due_date DATE NULL,
    completed TINYINT(1) DEFAULT 0,
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_todo_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_todo_dream FOREIGN KEY (dream_id) REFERENCES dreams(id) ON DELETE CASCADE,
    CONSTRAINT fk_todo_node FOREIGN KEY (node_id) REFERENCES nodes(id) ON DELETE SET NULL,
    INDEX idx_todos_dream (dream_id),
    INDEX idx_todos_due (due_date),
    INDEX idx_todos_completed (completed)
) ENGINE=InnoDB;

CREATE TABLE motivation_media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    media_type ENUM('youtube','audio','quote') DEFAULT 'quote',
    url VARCHAR(500) NULL,
    quote_text TEXT NULL,
    author VARCHAR(150) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE user_analytics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    dream_id INT UNSIGNED NULL,
    completion_rate DECIMAL(5,2) DEFAULT 0,
    success_probability DECIMAL(5,2) DEFAULT 0,
    risk_index DECIMAL(5,2) DEFAULT 0,
    xp_earned INT DEFAULT 0,
    streak_days INT DEFAULT 0,
    time_spent_minutes INT DEFAULT 0,
    snapshot_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_analytics_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_analytics_dream FOREIGN KEY (dream_id) REFERENCES dreams(id) ON DELETE CASCADE,
    UNIQUE KEY uq_analytics_snapshot (user_id, dream_id, snapshot_date),
    INDEX idx_analytics_date (snapshot_date)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    dream_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('reminder','milestone','motivation','system') DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_dream FOREIGN KEY (dream_id) REFERENCES dreams(id) ON DELETE CASCADE,
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_read (is_read)
) ENGINE=InnoDB;

-- Demo user. Password is: password
INSERT INTO users (name, email, password_hash)
VALUES ('Demo User', 'demo@dreammapper.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC2jX1jK2Q4v6e4hYy1u');

INSERT INTO motivation_media (title, media_type, quote_text, author) VALUES
('Start Small', 'quote', 'A small step every day becomes a very long journey.', 'DreamMapper'),
('Progress Over Perfection', 'quote', 'Do not wait for perfect conditions. Start with what you can control today.', 'DreamMapper'),
('Consistency Wins', 'quote', 'Your future is built by the actions you repeat.', 'DreamMapper');
