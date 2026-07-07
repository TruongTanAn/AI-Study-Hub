<?php
require_once '../includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chatbot — AI Study Hub</title>
    <meta name="description" content="Hỏi đáp tài liệu thông minh với AI. Tải lên hoặc chọn tài liệu và đặt câu hỏi trực tiếp.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/chatbot.css">
</head>
<body>

    <!-- ══ HEADER / NAVBAR (identical to document_detail.php) ══ -->
    <header class="page-header">
        <a href="dashboard.php" class="logo">
            <i class="fas fa-brain"></i> AI Study Hub
        </a>
        <div class="header-actions">
            <span class="user-greeting">Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Bạn', ENT_QUOTES, 'UTF-8'); ?></strong></span>
            <a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
            <a href="documents.php"><i class="fas fa-folder-open"></i> <span>Tài liệu</span></a>
            <a href="upload.php" class="btn-upload-nav"><i class="fas fa-upload"></i> <span>Upload</span></a>
            <a href="../backend/logout.php" class="btn-logout-nav"><i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span></a>
        </div>
    </header>

    <!-- ══ APP SHELL ══ -->
    <div class="chat-app">

        <!-- ── SIDEBAR LEFT ── -->
        <aside class="chat-sidebar">
            <div class="sidebar-header">
                <button class="btn-new-chat" id="btn-new-chat">
                    <i class="fas fa-plus"></i> Chat mới
                </button>
            </div>

            <div class="sidebar-section-label">Hội thoại gần đây</div>

            <div class="history-list" id="history-list">
                <!-- Filled by chatbot.js → loadHistoryList() -->
                <div class="history-empty">
                    <i class="fas fa-comment-slash"></i>
                    Chưa có hội thoại nào
                </div>
            </div>
        </aside>

        <!-- ── MAIN CHAT AREA ── -->
        <main class="chat-main">

            <!-- ── PREVIEW PANEL (hidden by default) ── -->
            <div class="preview-panel" id="preview-panel">
                <div class="preview-titlebar">
                    <div class="preview-filename" id="preview-filename">
                        <i class="fas fa-file"></i>
                        <span>Tên tệp</span>
                    </div>
                    <button class="btn-close-preview" id="btn-close-preview" title="Đóng xem trước">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="preview-content-area" id="preview-content-area">
                    <!-- Filled by preview.js -->
                </div>
            </div>

            <!-- ── MESSAGES AREA ── -->
            <div class="messages-wrapper" id="messages-wrapper">

                <!-- Welcome / empty state -->
                <div class="welcome-screen" id="welcome-screen">
                    <div class="welcome-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h2>Xin chào! Tôi là AI Study Hub</h2>
                    <p>Tôi có thể giúp bạn giải thích tài liệu, trả lời câu hỏi học thuật, và hỗ trợ bạn học tập hiệu quả hơn. Hãy bắt đầu bằng cách đặt một câu hỏi!</p>

                    <div class="suggestion-chips">
                        <button class="chip" data-text="Tóm tắt nội dung chính của tài liệu này">
                            <i class="fas fa-list-ul"></i> Tóm tắt tài liệu
                        </button>
                        <button class="chip" data-text="Giải thích các khái niệm khó trong tài liệu">
                            <i class="fas fa-lightbulb"></i> Giải thích khái niệm
                        </button>
                        <button class="chip" data-text="Tạo 5 câu hỏi ôn tập từ tài liệu này">
                            <i class="fas fa-question-circle"></i> Câu hỏi ôn tập
                        </button>
                    </div>
                </div>

                <!-- Messages rendered here -->
                <div class="messages-list" id="messages-list" style="display:none;">
                    <!-- Filled by chatbot.js -->
                </div>

            </div><!-- /.messages-wrapper -->

            <!-- ── FILE ATTACHMENT STRIP ── -->
            <div class="attachment-strip" id="attachment-strip">
                <i class="fas fa-paperclip"></i>
                <span class="attach-name" id="attach-name">tên_tệp.pdf</span>
                <button class="btn-remove-attach" id="btn-remove-attach" title="Gỡ tệp đính kèm">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- ── INPUT BAR ── -->
            <div class="input-bar-wrapper">
                <div class="input-bar">
                    <!-- Attach button -->
                    <button class="btn-attach" id="btn-attach" title="Đính kèm tệp (PDF, DOCX, PPTX)">
                        <i class="fas fa-paperclip"></i>
                    </button>

                    <!-- Hidden file input -->
                    <input type="file" id="file-input" accept=".pdf,.doc,.docx,.ppt,.pptx">

                    <!-- Textarea -->
                    <textarea
                        id="chat-textarea"
                        rows="1"
                        placeholder="Nhập câu hỏi của bạn… (Enter để gửi, Shift+Enter để xuống dòng)"
                        aria-label="Ô nhập câu hỏi"
                    ></textarea>

                    <!-- Send button -->
                    <button class="btn-send" id="btn-send" title="Gửi (Enter)">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <p class="input-hint">
                    <i class="fas fa-shield-alt" style="color:var(--primary);margin-right:4px;"></i>
                    AI Study Hub · Câu trả lời được tạo bởi AI, hãy kiểm tra lại thông tin quan trọng.
                </p>
            </div>

        </main><!-- /.chat-main -->

    </div><!-- /.chat-app -->

    <!-- Scripts -->
    <script src="../assets/js/preview.js"></script>
    <script src="../assets/js/chatbot.js"></script>

</body>
</html>
