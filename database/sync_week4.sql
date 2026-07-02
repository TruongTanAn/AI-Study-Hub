-- =============================================
-- Week 4 schema sync for ai_study_hub
-- Run this in phpMyAdmin / MySQL CLI
-- =============================================
USE ai_study_hub;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Rebuild lookup tables first to satisfy foreign keys
DROP TABLE IF EXISTS subjects;
CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL UNIQUE
);

INSERT IGNORE INTO subjects (subject_name) VALUES
    ('Khoa học tự nhiên'),
    ('Khoa học xã hội'),
    ('Công nghệ thông tin'),
    ('Kinh doanh'),
    ('Nghệ thuật'),
    ('Sức khỏe'),
    ('Khác');

DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

INSERT IGNORE INTO categories (category_name) VALUES
    ('Toán học'),
    ('Vật lý'),
    ('Hóa học'),
    ('Lập trình'),
    ('Ngoại ngữ'),
    ('Kinh tế'),
    ('Khác');

-- 2. Rebuild documents table to match Week 4 code expectations
DROP TABLE IF EXISTS documents;
CREATE TABLE documents (
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
    visibility ENUM('public','private','shared') NOT NULL DEFAULT 'public',
    downloads_count INT NOT NULL DEFAULT 0,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Restore document_categories as a join table (if needed later)
DROP TABLE IF EXISTS document_categories;
CREATE TABLE document_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    category_id INT NOT NULL,
    FOREIGN KEY (document_id) REFERENCES documents(document_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

-- 4. Restore expected reference tables for code paths
DROP TABLE IF EXISTS download_history;
CREATE TABLE download_history (
    download_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_id INT NOT NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(document_id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS chat_history;
CREATE TABLE chat_history (
    chat_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question TEXT NOT NULL,
    answer LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 5. Recreate missing indexes safely
ALTER TABLE documents
    ADD KEY idx_documents_user (user_id),
    ADD KEY idx_documents_subject (subject_id),
    ADD KEY idx_documents_category (category_id);

ALTER TABLE downloads
    ADD KEY idx_downloads_document (document_id),
    ADD KEY idx_downloads_user (user_id);

ALTER TABLE chat_messages
    ADD KEY idx_chat_document (document_id),
    ADD KEY idx_chat_session (session_id);

SET FOREIGN_KEY_CHECKS = 1;
