-- ================================================================
-- AI Study Hub - Complete Database Schema
-- Run Step 1 first, then Step 2.
-- ================================================================

-- ================================================================
-- STEP 1: Fix orphaned FK in downloads table
-- (downloads references documents.id but documents table was missing)
-- Run this BEFORE recreating documents
-- ================================================================
USE ai_study_hub;
SET FOREIGN_KEY_CHECKS = 0;
ALTER TABLE downloads DROP FOREIGN KEY IF EXISTS downloads_ibfk_1;
SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- STEP 2: Full schema (run after Step 1)
-- ================================================================
USE ai_study_hub;

SET FOREIGN_KEY_CHECKS = 0;

-- --- subjects ---
DROP TABLE IF EXISTS subjects;
CREATE TABLE subjects (
    subject_id   INT(11)       NOT NULL AUTO_INCREMENT,
    subject_name VARCHAR(100)  NOT NULL,
    PRIMARY KEY (subject_id),
    UNIQUE KEY  uk_subjects_name (subject_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- categories ---
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    category_id   INT(11)      NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    PRIMARY KEY (category_id),
    UNIQUE KEY  uk_categories_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- documents ---
DROP TABLE IF EXISTS documents;
CREATE TABLE documents (
    document_id     INT(11)                                      NOT NULL AUTO_INCREMENT,
    user_id         INT(11)                                      NOT NULL,
    subject_id      INT(11)                                      DEFAULT NULL,
    category_id     INT(11)                                      DEFAULT NULL,
    title           VARCHAR(255)                                 NOT NULL,
    description     TEXT                                         DEFAULT NULL,
    file_name       VARCHAR(255)                                 NOT NULL,
    original_name   VARCHAR(255)                                 NOT NULL,
    file_path       VARCHAR(500)                                 NOT NULL,
    file_type       VARCHAR(50)                                  NOT NULL,
    file_size       BIGINT                                      NOT NULL,
    visibility      ENUM('public','private','shared')            NOT NULL DEFAULT 'public',
    downloads_count INT(11)                                      NOT NULL DEFAULT 0,
    status          ENUM('pending','approved','rejected')        NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP                                   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP                                   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (document_id),
    KEY             idx_documents_user     (user_id),
    KEY             idx_documents_subject  (subject_id),
    KEY             idx_documents_category (category_id),
    CONSTRAINT fk_documents_user_id
        FOREIGN KEY (user_id)     REFERENCES users(user_id)              ON DELETE CASCADE,
    CONSTRAINT fk_documents_subject_id
        FOREIGN KEY (subject_id)  REFERENCES subjects(subject_id)       ON DELETE SET NULL,
    CONSTRAINT fk_documents_category_id
        FOREIGN KEY (category_id) REFERENCES categories(category_id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- document_categories ---
DROP TABLE IF EXISTS document_categories;
CREATE TABLE document_categories (
    id           INT(11) NOT NULL AUTO_INCREMENT,
    document_id  INT(11) NOT NULL,
    category_id  INT(11) NOT NULL,
    PRIMARY KEY (id),
    KEY          idx_doccat_document (document_id),
    KEY          idx_doccat_category (category_id),
    CONSTRAINT fk_doccat_document
        FOREIGN KEY (document_id)  REFERENCES documents(document_id)     ON DELETE CASCADE,
    CONSTRAINT fk_doccat_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- download_history ---
DROP TABLE IF EXISTS download_history;
CREATE TABLE download_history (
    download_id   INT(11)    NOT NULL AUTO_INCREMENT,
    user_id       INT(11)    NOT NULL,
    document_id   INT(11)    NOT NULL,
    downloaded_at TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (download_id),
    KEY           idx_dl_user     (user_id),
    KEY           idx_dl_document (document_id),
    CONSTRAINT fk_dl_user_id
        FOREIGN KEY (user_id)     REFERENCES users(user_id)           ON DELETE CASCADE,
    CONSTRAINT fk_dl_document_id
        FOREIGN KEY (document_id) REFERENCES documents(document_id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- password_resets ---
DROP TABLE IF EXISTS password_resets;
CREATE TABLE password_resets (
    reset_id    INT(11)       NOT NULL AUTO_INCREMENT,
    user_id     INT(11)       NOT NULL,
    token       VARCHAR(255)  NOT NULL,
    expires_at  DATETIME      DEFAULT NULL,
    PRIMARY KEY (reset_id),
    KEY         idx_pr_user (user_id),
    CONSTRAINT fk_pr_user_id
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- activity_logs ---
DROP TABLE IF EXISTS activity_logs;
CREATE TABLE activity_logs (
    log_id     INT(11)       NOT NULL AUTO_INCREMENT,
    user_id    INT(11)       DEFAULT NULL,
    action     VARCHAR(255)  DEFAULT NULL,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (log_id),
    KEY        idx_al_user (user_id),
    CONSTRAINT fk_al_user_id
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- chat_sessions ---
DROP TABLE IF EXISTS chat_sessions;
CREATE TABLE chat_sessions (
    id         INT(11)       NOT NULL AUTO_INCREMENT,
    user_id    INT(11)       NOT NULL,
    title      VARCHAR(255)  DEFAULT 'New Chat',
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY        idx_cs_user (user_id),
    CONSTRAINT fk_cs_user_id
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- chat_messages ---
DROP TABLE IF EXISTS chat_messages;
CREATE TABLE chat_messages (
    id           INT(11)                        NOT NULL AUTO_INCREMENT,
    session_id   INT(11)                        NOT NULL,
    role         ENUM('user','assistant')        NOT NULL,
    message      TEXT                           NOT NULL,
    document_id  INT(11)                        DEFAULT NULL,
    created_at   TIMESTAMP                     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY          idx_cm_session  (session_id),
    KEY          idx_cm_document (document_id),
    CONSTRAINT fk_cm_session_id
        FOREIGN KEY (session_id)  REFERENCES chat_sessions(id)          ON DELETE CASCADE,
    CONSTRAINT fk_cm_document_id
        FOREIGN KEY (document_id) REFERENCES documents(document_id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- Rebuild downloads FK with correct column (documents.document_id) ---
ALTER TABLE downloads
    ADD CONSTRAINT fk_downloads_document_id
        FOREIGN KEY (document_id) REFERENCES documents(document_id) ON DELETE CASCADE;

-- --- Seed data ---
INSERT IGNORE INTO categories (category_name) VALUES
    ('Toán học'), ('Vật lý'), ('Hóa học'),
    ('Lập trình'), ('Ngoại ngữ'), ('Kinh tế'), ('Khác');

INSERT IGNORE INTO subjects (subject_name) VALUES
    ('Khoa học tự nhiên'), ('Khoa học xã hội'),
    ('Công nghệ thông tin'), ('Kinh doanh'),
    ('Nghệ thuật'), ('Sức khỏe'), ('Khác');

SET FOREIGN_KEY_CHECKS = 1;
