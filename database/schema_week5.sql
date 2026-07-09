-- ================================================================
-- AI Study Hub - Week 5 schema (AN - AI Integration)
-- Run this AFTER database.sql / schema_final.sql
-- ================================================================
USE ai_study_hub;

SET FOREIGN_KEY_CHECKS = 0;

-- ================================================================
-- conversations: moi hoi thoai AI cua mot user
-- ================================================================
DROP TABLE IF EXISTS conversations;
CREATE TABLE conversations (
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
    KEY idx_conversations_updated_at (updated_at),
    CONSTRAINT fk_conversations_user_id
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_conversations_document_id
        FOREIGN KEY (document_id) REFERENCES documents(document_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- chat_messages: tung tin nhan trong cuoc tro chuyen
-- role: user | assistant | system
-- ================================================================
DROP TABLE IF EXISTS chat_messages;
CREATE TABLE chat_messages (
    message_id      INT(11)                                  NOT NULL AUTO_INCREMENT,
    conversation_id INT(11)                                  NOT NULL,
    role            ENUM('user','assistant','system')        NOT NULL,
    message         LONGTEXT                                 NOT NULL,
    document_id     INT(11)                                  DEFAULT NULL,
    prompt_tokens   INT(11)                                  NOT NULL DEFAULT 0,
    completion_tokens INT(11)                                NOT NULL DEFAULT 0,
    created_at      TIMESTAMP                                NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id),
    KEY idx_chat_messages_conversation (conversation_id),
    KEY idx_chat_messages_document     (document_id),
    KEY idx_chat_messages_created_at   (created_at),
    CONSTRAINT fk_chat_messages_conversation_id
        FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_messages_document_id
        FOREIGN KEY (document_id) REFERENCES documents(document_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
