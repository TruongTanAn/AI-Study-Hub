CREATE DATABASE IF NOT EXISTS ai_study_hub;
USE ai_study_hub;

-- Ensure ai_user (created by MYSQL_USER env) has full access on this DB
-- This fixes "permission denied" errors when PHP code connects via ai_user
CREATE USER IF NOT EXISTS 'ai_user'@'%' IDENTIFIED BY 'ai_password';
ALTER USER 'ai_user'@'%' IDENTIFIED WITH mysql_native_password BY 'ai_password';
GRANT ALL PRIVILEGES ON ai_study_hub.* TO 'ai_user'@'%';
FLUSH PRIVILEGES;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default.png',
    role ENUM('admin','user') DEFAULT 'user',
    status ENUM('active','blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_id INT,
    category_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size BIGINT NOT NULL,
    visibility ENUM('public','private','shared') DEFAULT 'public',
    downloads_count INT DEFAULT 0,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS document_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    category_id INT NOT NULL,
    FOREIGN KEY (document_id) REFERENCES documents(document_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_history (
    chat_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question TEXT NOT NULL,
    answer LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS download_history (
    download_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_id INT NOT NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(document_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS password_resets (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ================================================================
-- Week 5 (AN - AI Integration): conversations + chat_messages
-- These tables are required by backend/load_chat_history.php
-- and backend/document_qa.php
-- ================================================================
CREATE TABLE IF NOT EXISTS conversations (
    conversation_id INT(11)       NOT NULL AUTO_INCREMENT,
    user_id         INT(11)       NOT NULL,
    title           VARCHAR(255)  NOT NULL DEFAULT 'Cuoc tro chuyen moi',
    document_id     INT(11)       DEFAULT NULL,
    model           VARCHAR(120)  DEFAULT NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (conversation_id),
    KEY idx_conversations_user        (user_id),
    KEY idx_conversations_document    (document_id),
    KEY idx_conversations_updated_at  (updated_at),
    CONSTRAINT fk_conversations_user_id
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_conversations_document_id
        FOREIGN KEY (document_id) REFERENCES documents(document_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_messages (
    message_id        INT(11)                            NOT NULL AUTO_INCREMENT,
    conversation_id   INT(11)                            NOT NULL,
    role              ENUM('user','assistant','system')  NOT NULL,
    message           LONGTEXT                           NOT NULL,
    document_id       INT(11)                            DEFAULT NULL,
    prompt_tokens     INT(11)                            NOT NULL DEFAULT 0,
    completion_tokens INT(11)                            NOT NULL DEFAULT 0,
    created_at        TIMESTAMP                          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id),
    KEY idx_chat_messages_conversation (conversation_id),
    KEY idx_chat_messages_document     (document_id),
    KEY idx_chat_messages_created_at   (created_at),
    CONSTRAINT fk_chat_messages_conversation_id
        FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_messages_document_id
        FOREIGN KEY (document_id) REFERENCES documents(document_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO categories (category_name) VALUES 
    ('Toán học'),
    ('Vật lý'),
    ('Hóa học'),
    ('Lập trình'),
    ('Ngoại ngữ'),
    ('Kinh tế'),
    ('Khác');

INSERT IGNORE INTO subjects (subject_name) VALUES 
    ('Khoa học tự nhiên'),
    ('Khoa học xã hội'),
    ('Công nghệ thông tin'),
    ('Kinh doanh'),
    ('Nghệ thuật'),
    ('Sức khỏe'),
    ('Khác');
