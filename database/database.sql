CREATE DATABASE ai_study_hub;
USE ai_study_hub;

-- =====================================
-- USERS
-- =====================================

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default.png',
    role ENUM('admin','user') DEFAULT 'user',
    status ENUM('active','blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================
-- CATEGORIES
-- =====================================

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

-- =====================================
-- DOCUMENTS
-- =====================================

CREATE TABLE documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,

    title VARCHAR(255) NOT NULL,
    description TEXT,

    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),

    file_size BIGINT,

    download_count INT DEFAULT 0,

    status ENUM('pending','approved','rejected')
    DEFAULT 'approved',

    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE
);

-- =====================================
-- DOCUMENT CATEGORY
-- =====================================

CREATE TABLE document_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,

    document_id INT NOT NULL,
    category_id INT NOT NULL,

    FOREIGN KEY (document_id)
    REFERENCES documents(document_id)
    ON DELETE CASCADE,

    FOREIGN KEY (category_id)
    REFERENCES categories(category_id)
    ON DELETE CASCADE
);

-- =====================================
-- CHAT HISTORY
-- =====================================

CREATE TABLE chat_history (
    chat_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    question TEXT NOT NULL,
    answer LONGTEXT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE
);

-- =====================================
-- DOWNLOAD HISTORY
-- =====================================

CREATE TABLE download_history (
    download_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,
    document_id INT NOT NULL,

    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE,

    FOREIGN KEY (document_id)
    REFERENCES documents(document_id)
    ON DELETE CASCADE
);

-- =====================================
-- ACTIVITY LOG
-- =====================================

CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,

    action VARCHAR(255),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE SET NULL
);

-- =====================================
-- PASSWORD RESET
-- =====================================

CREATE TABLE password_resets (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    token VARCHAR(255) NOT NULL,

    expires_at DATETIME,

    FOREIGN KEY (user_id)
    REFERENCES users(user_id)
    ON DELETE CASCADE
);