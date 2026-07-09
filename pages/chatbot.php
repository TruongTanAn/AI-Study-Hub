<?php
/**
 * AI Study Hub - Chatbot Page (Week 5 - AN)
 *
 * Features:
 *  - Sidebar: conversation history (load via load_chat_history.php)
 *  - Main: chat with OpenRouter (via chat_api.php)
 *  - RAG: chat theo document (qua document_qa.php)
 *  - Preview document inline (qua preview_document.php)
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$initialDocumentId = isset($_GET['doc_id']) ? (int) $_GET['doc_id'] : 0;
$initialConversationId = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : 0;

require_once __DIR__ . '/../backend/conversation_helpers.php';
if (!defined('AI_STUDY_HUB')) {
    define('AI_STUDY_HUB', true);
}

$initialDocument = null;
$docError = '';
if ($initialDocumentId > 0) {
    $initialDocument = ai_db_get_document_for_user($conn, $initialDocumentId, (int) $_SESSION['user_id']);
    if (!$initialDocument) {
        $docError = 'Tai lieu khong ton tai hoac ban khong co quyen truy cap (co the tai lieu dang cho duyet).';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chatbot - AI Study Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/chat.css">
</head>
<body>

<header class="page-header">
    <a href="dashboard.php" class="logo">
        <i class="fas fa-brain"></i> AI Study Hub
    </a>
    <div class="header-actions">
        <span class="user-greeting">Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></strong></span>
        <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="documents.php"><i class="fas fa-folder-open"></i> Tài liệu</a>
        <a href="../backend/logout.php" class="btn-logout-nav"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
    </div>
</header>

<main class="chatbot-shell" id="chatbotShell">
    <!-- Sidebar -->
    <aside class="chat-sidebar" id="chatSidebar">
        <div class="sidebar-header">
            <button id="newChatBtn" class="btn-new-chat">
                <i class="fas fa-plus"></i> Cuộc trò chuyện mới
            </button>
            <button id="toggleSidebarBtn" class="btn-toggle-sidebar" title="Thu gọn">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <div class="sidebar-section">
            <h4 class="sidebar-section-title"><i class="fas fa-comments"></i> Lịch sử</h4>
            <div id="conversationList" class="conversation-list">
                <div class="conversation-empty">Đang tải...</div>
            </div>
        </div>

        <div class="sidebar-footer">
            <a href="documents.php" class="sidebar-link"><i class="fas fa-folder-open"></i> Tài liệu của tôi</a>
            <a href="dashboard.php" class="sidebar-link"><i class="fas fa-th-large"></i> Dashboard</a>
        </div>
    </aside>

    <!-- Main chat -->
    <section class="chat-main">
        <div class="chat-toolbar">
            <div class="chat-toolbar-left">
                <span class="model-badge"><i class="fas fa-robot"></i> <span id="modelBadge">meta-llama/llama-3.1-8b-instruct</span></span>
                <span class="doc-badge hidden" id="docBadge">
                    <i class="fas fa-file-alt"></i>
                    <span id="docBadgeTitle">Tài liệu</span>
                    <button class="btn-clear-doc" id="clearDocBtn" title="Bỏ chọn tài liệu"><i class="fas fa-times"></i></button>
                </span>
            </div>
            <div class="chat-toolbar-right">
                <button id="openSidebarBtnMobile" class="btn-icon" title="Mở sidebar"><i class="fas fa-bars"></i></button>
            </div>
        </div>

        <div class="chat-doc-preview hidden" id="docPreview">
            <div class="chat-doc-preview-header">
                <h4><i class="fas fa-eye"></i> <span id="docPreviewTitle">Tài liệu</span></h4>
                <button class="btn-icon" id="closePreviewBtn" title="Đóng"><i class="fas fa-times"></i></button>
            </div>
            <div class="chat-doc-preview-body" id="docPreviewBody">
                <div class="preview-empty">Chưa có nội dung xem trước.</div>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="chat-welcome" id="chatWelcome">
                <div class="chat-welcome-icon"><i class="fas fa-robot"></i></div>
                <h2>Chào bạn! Mình là trợ lý AI của AI Study Hub.</h2>
                <p>Bạn có thể hỏi bất kỳ điều gì, hoặc chọn một tài liệu để mình trả lời dựa trên nội dung của nó.</p>
                <div class="chat-welcome-suggestions">
                    <button class="suggestion-btn" data-q="Giải thích giúp mình khái niệm học tập hiệu quả"><i class="fas fa-lightbulb"></i> Gợi ý phương pháp học</button>
                    <button class="suggestion-btn" data-q="Tóm tắt các bước lập kế hoạch học tập"><i class="fas fa-list-check"></i> Lập kế hoạch học tập</button>
                    <button class="suggestion-btn" data-q="Đưa ra lời khuyên để tăng cường tập trung khi học"><i class="fas fa-bolt"></i> Tăng tập trung</button>
                </div>
            </div>
        </div>

        <form class="chat-input-bar" id="chatForm" autocomplete="off">
            <textarea
                id="chatInput"
                class="chat-input"
                placeholder="Nhập câu hỏi của bạn... (Shift + Enter để xuống dòng)"
                rows="1"
                maxlength="2000"
            ></textarea>
            <button type="submit" id="chatSendBtn" class="btn-send">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </section>
</main>

<script>
    window.CHAT_CONFIG = {
        userId: <?php echo (int) $_SESSION['user_id']; ?>,
        conversationId: <?php echo $initialConversationId; ?>,
        documentId: <?php echo $initialDocumentId; ?>,
        document: <?php echo $initialDocument ? json_encode([
            'document_id'   => (int) $initialDocument['document_id'],
            'title'         => (string) $initialDocument['title'],
            'description'   => (string) ($initialDocument['description'] ?? ''),
            'file_name'     => (string) $initialDocument['file_name'],
            'original_name' => (string) $initialDocument['original_name'],
            'file_type'     => (string) $initialDocument['file_type'],
        ], JSON_UNESCAPED_UNICODE) : 'null'; ?>,
        apiChat: '../backend/chat_api.php',
        apiDocChat: '../backend/document_qa.php',
        apiHistory: '../backend/load_chat_history.php',
        apiPreview: '../backend/preview_document.php',
        apiSave: '../backend/save_chat.php',
        docError: <?php echo json_encode($docError, JSON_UNESCAPED_UNICODE); ?>,
    };
</script>
<script src="../assets/js/chat.js"></script>
</body>
</html>