<?php
require_once '../includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chatbot – AI Study Hub</title>
    <meta name="description" content="Trợ lý AI thông minh hỗ trợ học tập – AI Study Hub">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ── RESET & TOKENS ────────────────────────────────── */
        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dk: #4f46e5;
            --secondary: #ec4899;
            --dark: #111827;
            --gray-900: #111827;
            --gray-700: #374151;
            --gray-500: #6b7280;
            --gray-300: #d1d5db;
            --gray-100: #f3f4f6;
            --white: #ffffff;
            --border: #e5e7eb;
            --shadow-sm: 0 1px 4px rgba(0, 0, 0, .06);
            --shadow: 0 4px 20px rgba(0, 0, 0, .08);
            --shadow-lg: 0 10px 40px rgba(0, 0, 0, .12);
            --radius: 14px;
            --sidebar-w: 280px;
            --header-h: 58px;
            --transition: .25s ease;

            /* Sidebar dark theme */
            --sb-bg: #0f1117;
            --sb-hover: #1e2130;
            --sb-active: #252a3a;
            --sb-text: #c9d1d9;
            --sb-muted: #6e7681;
            --sb-border: rgba(255, 255, 255, .07);
        }

        html,
        body {
            height: 100%;
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--gray-100);
            color: var(--dark);
            overflow: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button {
            cursor: pointer;
            font-family: inherit;
            border: none;
            background: none;
        }

        textarea {
            font-family: inherit;
        }

        /* ── LAYOUT ─────────────────────────────────────────── */
        .chat-app {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ── SIDEBAR ────────────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            min-width: var(--sidebar-w);
            background: var(--sb-bg);
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: relative;
            z-index: 10;
            transition: transform var(--transition);
        }

        .sidebar-header {
            padding: 16px 16px 12px;
            border-bottom: 1px solid var(--sb-border);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--white);
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 12px;
        }

        .sidebar-logo .logo-icon {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            color: white;
            flex-shrink: 0;
        }

        .btn-new-chat {
            width: 100%;
            padding: 10px 14px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 10px;
            font-size: .875rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-new-chat:hover {
            opacity: .9;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(99, 102, 241, .4);
        }

        /* Conversation list */
        .sidebar-body {
            flex: 1;
            overflow-y: auto;
            padding: 8px 8px;
        }

        .sidebar-body::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-body::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }

        .conv-section-label {
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .08em;
            color: var(--sb-muted);
            text-transform: uppercase;
            padding: 10px 8px 4px;
        }

        .conv-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 10px;
            border-radius: 9px;
            cursor: pointer;
            transition: background var(--transition);
            color: var(--sb-text);
            font-size: .875rem;
            position: relative;
            overflow: hidden;
        }

        .conv-item:hover {
            background: var(--sb-hover);
        }

        .conv-item.active {
            background: var(--sb-active);
            color: var(--white);
        }

        .conv-item i {
            font-size: .8rem;
            color: var(--sb-muted);
            flex-shrink: 0;
        }

        .conv-item.active i {
            color: var(--primary);
        }

        .conv-title {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: .855rem;
        }

        .conv-actions {
            display: none;
            gap: 4px;
            flex-shrink: 0;
        }

        .conv-item:hover .conv-actions {
            display: flex;
        }

        .conv-action-btn {
            width: 24px;
            height: 24px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--sb-muted);
            font-size: .7rem;
            transition: var(--transition);
        }

        .conv-action-btn:hover {
            background: rgba(255, 255, 255, .1);
            color: var(--white);
        }

        .conv-action-btn.del:hover {
            background: rgba(239, 68, 68, .2);
            color: #f87171;
        }

        /* Sidebar footer */
        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid var(--sb-border);
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-radius: 9px;
            transition: background var(--transition);
            cursor: pointer;
            color: var(--sb-text);
        }

        .user-pill:hover {
            background: var(--sb-hover);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            color: white;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-name {
            font-size: .855rem;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
        }

        .footer-links {
            display: flex;
            gap: 6px;
            margin-top: 6px;
        }

        .footer-link {
            flex: 1;
            padding: 7px;
            border-radius: 7px;
            color: var(--sb-muted);
            font-size: .75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: var(--transition);
        }

        .footer-link:hover {
            background: var(--sb-hover);
            color: var(--sb-text);
        }

        /* ── MAIN CHAT AREA ─────────────────────────────────── */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: var(--white);
        }

        /* Top bar */
        .chat-topbar {
            height: var(--header-h);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 12px;
            background: rgba(255, 255, 255, .98);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 5;
        }

        .topbar-toggle {
            display: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            color: var(--gray-500);
            font-size: 1rem;
            transition: var(--transition);
        }

        .topbar-toggle:hover {
            background: var(--gray-100);
        }

        .topbar-title {
            flex: 1;
            font-weight: 600;
            font-size: .95rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--gray-700);
        }

        .topbar-actions {
            display: flex;
            gap: 6px;
        }

        .topbar-btn {
            padding: 7px 14px;
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 500;
            color: var(--gray-500);
            border: 1px solid var(--border);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .topbar-btn:hover {
            background: var(--gray-100);
            color: var(--dark);
        }

        /* Messages area */
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 24px 16px 8px;
            display: flex;
            flex-direction: column;
            gap: 0;
            scroll-behavior: smooth;
        }

        .messages-area::-webkit-scrollbar {
            width: 5px;
        }

        .messages-area::-webkit-scrollbar-track {
            background: transparent;
        }

        .messages-area::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 4px;
        }

        /* Empty state */
        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
            animation: fadeIn .4s ease;
        }

        .empty-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(99, 102, 241, .3);
        }

        .empty-state h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--gray-500);
            font-size: .95rem;
            max-width: 420px;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .suggestion-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            max-width: 560px;
        }

        .chip {
            padding: 9px 16px;
            background: var(--gray-100);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: .85rem;
            color: var(--gray-700);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 7px;
            font-weight: 500;
        }

        .chip:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, .1), rgba(236, 72, 153, .07));
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* Message bubbles */
        .message-row {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            max-width: 820px;
            margin: 0 auto;
            width: 100%;
            animation: fadeSlideUp .3s ease;
        }

        .message-row.user {
            flex-direction: row-reverse;
        }

        .msg-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            font-weight: 700;
        }

        .msg-avatar.ai {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .msg-avatar.user-av {
            background: var(--gray-200, #e5e7eb);
            color: var(--gray-700);
        }

        .msg-content {
            max-width: 72%;
        }

        .msg-bubble {
            padding: 12px 16px;
            border-radius: 16px;
            font-size: .9rem;
            line-height: 1.65;
            word-break: break-word;
        }

        .message-row.ai .msg-bubble {
            background: var(--gray-100);
            color: var(--dark);
            border-bottom-left-radius: 4px;
        }

        .message-row.user .msg-bubble {
            background: linear-gradient(135deg, var(--primary), var(--primary-dk));
            color: white;
            border-bottom-right-radius: 4px;
        }

        /* Markdown-ish formatting in AI bubbles */
        .msg-bubble strong {
            font-weight: 700;
        }

        .msg-bubble em {
            font-style: italic;
        }

        .msg-bubble code {
            background: rgba(99, 102, 241, .1);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: .85em;
        }

        .msg-bubble pre {
            background: var(--gray-900);
            color: #e2e8f0;
            padding: 14px;
            border-radius: 10px;
            margin: 10px 0;
            overflow-x: auto;
            font-size: .82rem;
            font-family: 'Courier New', monospace;
        }

        .msg-bubble ul,
        .msg-bubble ol {
            padding-left: 1.4em;
            margin: 6px 0;
        }

        .msg-bubble li {
            margin: 3px 0;
        }

        .msg-time {
            font-size: .72rem;
            color: var(--gray-500);
            margin-top: 5px;
            padding: 0 4px;
        }

        .message-row.user .msg-time {
            text-align: right;
        }

        /* Typing indicator */
        .typing-indicator {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            max-width: 820px;
            margin: 0 auto;
            width: 100%;
            animation: fadeSlideUp .3s ease;
        }

        .typing-bubble {
            background: var(--gray-100);
            padding: 14px 18px;
            border-radius: 16px;
            border-bottom-left-radius: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .dot {
            width: 7px;
            height: 7px;
            background: var(--gray-500);
            border-radius: 50%;
            animation: bounce 1.2s infinite;
        }

        .dot:nth-child(2) {
            animation-delay: .2s;
        }

        .dot:nth-child(3) {
            animation-delay: .4s;
        }

        @keyframes bounce {

            0%,
            60%,
            100% {
                transform: translateY(0);
            }

            30% {
                transform: translateY(-6px);
            }
        }

        /* ── INPUT AREA (fixed bottom) ─────────────────────── */
        .input-area {
            border-top: 1px solid var(--border);
            padding: 14px 20px 16px;
            background: var(--white);
            position: relative;
        }

        .input-wrapper {
            max-width: 820px;
            margin: 0 auto;
            background: var(--gray-100);
            border: 1.5px solid var(--border);
            border-radius: 16px;
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding: 10px 12px;
            transition: border-color var(--transition), box-shadow var(--transition);
        }

        .input-wrapper:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
            background: white;
        }

        #msgInput {
            flex: 1;
            border: none;
            background: none;
            outline: none;
            resize: none;
            font-size: .93rem;
            color: var(--dark);
            line-height: 1.55;
            max-height: 160px;
            min-height: 26px;
            scrollbar-width: thin;
        }

        #msgInput::placeholder {
            color: var(--gray-500);
        }

        #sendBtn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .9rem;
            flex-shrink: 0;
            transition: var(--transition);
            opacity: .5;
            pointer-events: none;
        }

        #sendBtn.ready {
            opacity: 1;
            pointer-events: all;
        }

        #sendBtn.ready:hover {
            transform: scale(1.08);
            box-shadow: 0 4px 14px rgba(99, 102, 241, .4);
        }

        #sendBtn.loading {
            opacity: 1;
            pointer-events: none;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .6;
            }
        }

        .input-hint {
            max-width: 820px;
            margin: 6px auto 0;
            font-size: .73rem;
            color: var(--gray-500);
            text-align: center;
        }

        /* ── TOAST ───────────────────────────────────────────── */
        .toast {
            position: fixed;
            bottom: 90px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: var(--dark);
            color: white;
            padding: 9px 18px;
            border-radius: 20px;
            font-size: .83rem;
            opacity: 0;
            pointer-events: none;
            transition: .3s ease;
            z-index: 9999;
            white-space: nowrap;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* ── MODAL (rename) ─────────────────────────────────── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s;
        }

        .modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .modal-card {
            background: white;
            border-radius: 18px;
            padding: 28px;
            width: 340px;
            max-width: 90vw;
            box-shadow: var(--shadow-lg);
            transform: scale(.95);
            transition: transform .25s;
        }

        .modal-overlay.open .modal-card {
            transform: scale(1);
        }

        .modal-card h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .modal-input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: .9rem;
            outline: none;
            transition: border-color var(--transition);
        }

        .modal-input:focus {
            border-color: var(--primary);
        }

        .modal-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 8px 18px;
            border-radius: 9px;
            font-size: .875rem;
            font-weight: 600;
            transition: var(--transition);
        }

        .modal-btn.cancel {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .modal-btn.cancel:hover {
            background: var(--gray-200, #e5e7eb);
        }

        .modal-btn.confirm {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .modal-btn.confirm:hover {
            opacity: .9;
        }

        /* ── ANIMATIONS ─────────────────────────────────────── */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── RESPONSIVE ─────────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                transform: translateX(-100%);
                z-index: 200;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, .5);
                z-index: 199;
                opacity: 0;
                pointer-events: none;
                transition: opacity .3s;
            }

            .sidebar-overlay.visible {
                opacity: 1;
                pointer-events: all;
            }

            .topbar-toggle {
                display: flex;
            }

            .msg-content {
                max-width: 88%;
            }
        }
    </style>
</head>

<body>
    <div class="chat-app">

        <!-- ── SIDEBAR ──────────────────────────────────────────────── -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon"><i class="fas fa-brain"></i></div>
                    <span>AI Study Hub</span>
                </div>
                <button class="btn-new-chat" id="btnNewChat" title="Tạo hội thoại mới">
                    <i class="fas fa-plus"></i> Hội thoại mới
                </button>
            </div>

            <div class="sidebar-body" id="convList">
                <div class="conv-section-label">Lịch sử</div>
                <!-- Conversations loaded by JS -->
                <div id="convItems"></div>
            </div>

            <div class="sidebar-footer">
                <div class="user-pill" onclick="window.location.href='profile.php'">
                    <div class="user-avatar" id="userAvatar">
                        <?php echo strtoupper(mb_substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <span
                        class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                    <i class="fas fa-chevron-right" style="color:var(--sb-muted);font-size:.7rem"></i>
                </div>
                <div class="footer-links">
                    <a class="footer-link" href="dashboard.php" title="Dashboard">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="footer-link" href="../backend/logout.php" title="Đăng xuất">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </aside>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- ── MAIN CHAT ─────────────────────────────────────────────── -->
        <main class="chat-main">

            <!-- Top bar -->
            <div class="chat-topbar">
                <button class="topbar-toggle" id="sidebarToggle" title="Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="topbar-title" id="topbarTitle">AI Chatbot</span>
                <div class="topbar-actions">
                    <button class="topbar-btn" id="btnTopRename" title="Đổi tên hội thoại" style="display:none">
                        <i class="fas fa-edit"></i> Đổi tên
                    </button>
                    <button class="topbar-btn" id="btnTopNew" title="Hội thoại mới">
                        <i class="fas fa-plus"></i><span class="d-none-mobile"> Mới</span>
                    </button>
                </div>
            </div>

            <!-- Messages -->
            <div class="messages-area" id="messagesArea">
                <!-- Empty state -->
                <div class="empty-state" id="emptyState">
                    <div class="empty-icon"><i class="fas fa-robot"></i></div>
                    <h2>Xin chào! Tôi có thể giúp gì?</h2>
                    <p>Tôi là trợ lý AI của AI Study Hub. Hãy đặt câu hỏi về học tập, giải thích khái niệm hoặc nhờ tôi
                        hỗ trợ bài tập.</p>
                    <div class="suggestion-chips">
                        <button class="chip" onclick="fillAndSend('Giải thích cho tôi về đạo hàm trong giải tích')">
                            <i class="fas fa-square-root-alt"></i> Đạo hàm là gì?
                        </button>
                        <button class="chip" onclick="fillAndSend('Viết code Python sắp xếp mảng bằng Bubble Sort')">
                            <i class="fas fa-code"></i> Bubble Sort Python
                        </button>
                        <button class="chip" onclick="fillAndSend('Phương trình hóa học cân bằng Fe + O2 → Fe2O3')">
                            <i class="fas fa-flask"></i> Cân bằng phương trình
                        </button>
                        <button class="chip" onclick="fillAndSend('Nguyên lý Newton thứ 2 là gì và ứng dụng thực tế?')">
                            <i class="fas fa-atom"></i> Định luật Newton 2
                        </button>
                        <button class="chip" onclick="fillAndSend('Cách học hiệu quả với phương pháp Pomodoro')">
                            <i class="fas fa-clock"></i> Phương pháp Pomodoro
                        </button>
                        <button class="chip" onclick="fillAndSend('Giải thích khái niệm đa hình trong lập trình OOP')">
                            <i class="fas fa-layer-group"></i> Đa hình OOP
                        </button>
                    </div>
                </div>
            </div>

            <!-- Input area -->
            <div class="input-area">
                <div class="input-wrapper">
                    <textarea id="msgInput" rows="1" placeholder="Nhắn tin với AI… (Enter gửi, Shift+Enter xuống dòng)"
                        maxlength="4000"></textarea>
                    <button id="sendBtn" title="Gửi">
                        <i class="fas fa-paper-plane" id="sendIcon"></i>
                    </button>
                </div>
                <div class="input-hint">
                    AI Study Hub · Powered by Gemini · Thông tin có thể không chính xác, hãy kiểm chứng lại
                </div>
            </div>
        </main>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <!-- Rename modal -->
    <div class="modal-overlay" id="renameModal">
        <div class="modal-card">
            <h3><i class="fas fa-edit" style="color:var(--primary);margin-right:8px"></i>Đổi tên hội thoại</h3>
            <input type="text" class="modal-input" id="renameInput" maxlength="120" placeholder="Nhập tên mới…">
            <div class="modal-actions">
                <button class="modal-btn cancel" id="renameCancelBtn">Hủy</button>
                <button class="modal-btn confirm" id="renameConfirmBtn">Lưu</button>
            </div>
        </div>
    </div>

    <script>
        /* ── STATE ────────────────────────────────────────────────── */
        const API = '../backend/chat_api.php';
        let currentSessionId = null;
        let renameTargetId = null;
        let isSending = false;

        /* ── DOM REFS ─────────────────────────────────────────────── */
        const messagesArea = document.getElementById('messagesArea');
        const msgInput = document.getElementById('msgInput');
        const sendBtn = document.getElementById('sendBtn');
        const sendIcon = document.getElementById('sendIcon');
        const convItems = document.getElementById('convItems');
        const emptyState = document.getElementById('emptyState');
        const topbarTitle = document.getElementById('topbarTitle');
        const toast = document.getElementById('toast');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const renameModal = document.getElementById('renameModal');
        const renameInput = document.getElementById('renameInput');
        const btnTopRename = document.getElementById('btnTopRename');

        /* ── INIT ─────────────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', () => {
            loadSessions();
            setupInput();
            setupSidebar();
            setupModal();
            document.getElementById('btnNewChat').addEventListener('click', newSession);
            document.getElementById('btnTopNew').addEventListener('click', newSession);
            btnTopRename.addEventListener('click', () => openRenameModal(currentSessionId));
        });

        /* ── SESSIONS ─────────────────────────────────────────────── */
        async function loadSessions() {
            try {
                const data = await apiFetch(`${API}?action=get_sessions`);
                if (!data.success) return;
                renderSessions(data.sessions || []);
            } catch (e) { console.error(e); }
        }

        function renderSessions(sessions) {
            convItems.innerHTML = '';
            if (!sessions.length) {
                convItems.innerHTML = '<div style="padding:12px 8px;color:var(--sb-muted);font-size:.8rem;text-align:center">Chưa có hội thoại nào</div>';
                return;
            }
            sessions.forEach(s => convItems.appendChild(buildConvItem(s)));
        }

        function buildConvItem(s) {
            const el = document.createElement('div');
            el.className = 'conv-item' + (s.id == currentSessionId ? ' active' : '');
            el.dataset.id = s.id;
            el.innerHTML = `
        <i class="fas fa-comment-dots"></i>
        <span class="conv-title" title="${esc(s.title)}">${esc(s.title)}</span>
        <div class="conv-actions">
            <button class="conv-action-btn" title="Đổi tên" onclick="openRenameModal(${s.id});event.stopPropagation()">
                <i class="fas fa-pen"></i>
            </button>
            <button class="conv-action-btn del" title="Xóa" onclick="deleteSession(${s.id});event.stopPropagation()">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
            el.addEventListener('click', () => loadSession(s.id, s.title));
            return el;
        }

        function setActiveConv(id) {
            document.querySelectorAll('.conv-item').forEach(el => {
                el.classList.toggle('active', el.dataset.id == id);
            });
        }

        /* ── LOAD SESSION ─────────────────────────────────────────── */
        async function loadSession(sessionId, title) {
            currentSessionId = sessionId;
            setActiveConv(sessionId);
            topbarTitle.textContent = title || 'Hội thoại';
            btnTopRename.style.display = 'flex';
            closeSidebarMobile();

            // Clear + show spinner
            clearMessages();
            showTyping();

            try {
                const data = await apiFetch(`${API}?action=get_messages&session_id=${sessionId}`);
                hideTyping();
                if (!data.success) { showToast('Không tải được tin nhắn'); return; }

                if (data.messages && data.messages.length) {
                    emptyState.style.display = 'none';
                    data.messages.forEach(m => appendMessage(m.role, m.message, m.created_at, false));
                    scrollBottom();
                } else {
                    emptyState.style.display = 'flex';
                }
            } catch (e) {
                hideTyping();
                showToast('Lỗi kết nối');
            }
        }

        /* ── NEW SESSION ──────────────────────────────────────────── */
        async function newSession() {
            try {
                const data = await apiFetch(API, 'POST', { action: 'new_session' });
                if (!data.success) return;

                // Prepend to list
                const item = buildConvItem({ id: data.session_id, title: data.title });
                convItems.insertBefore(item, convItems.firstChild);

                // Remove "no sessions" placeholder
                const placeholder = convItems.querySelector('[style*="text-align:center"]');
                if (placeholder) placeholder.remove();

                loadSession(data.session_id, data.title);
            } catch (e) { showToast('Không tạo được hội thoại'); }
        }

        /* ── DELETE SESSION ───────────────────────────────────────── */
        async function deleteSession(id) {
            if (!confirm('Xóa hội thoại này?')) return;
            try {
                const data = await apiFetch(API, 'POST', { action: 'delete_session', session_id: id });
                if (!data.success) return;
                const el = convItems.querySelector(`[data-id="${id}"]`);
                if (el) el.remove();

                if (id == currentSessionId) {
                    currentSessionId = null;
                    clearMessages();
                    topbarTitle.textContent = 'AI Chatbot';
                    btnTopRename.style.display = 'none';
                    emptyState.style.display = 'flex';
                }

                showToast('Đã xóa hội thoại');
            } catch (e) { showToast('Không xóa được'); }
        }

        /* ── RENAME SESSION ───────────────────────────────────────── */
        function openRenameModal(id) {
            if (!id) return;
            renameTargetId = id;
            const el = convItems.querySelector(`[data-id="${id}"] .conv-title`);
            renameInput.value = el ? el.textContent : '';
            renameModal.classList.add('open');
            setTimeout(() => { renameInput.focus(); renameInput.select(); }, 100);
        }

        async function confirmRename() {
            const newTitle = renameInput.value.trim();
            if (!newTitle || !renameTargetId) return;
            renameModal.classList.remove('open');

            try {
                const data = await apiFetch(API, 'POST', {
                    action: 'rename_session', session_id: renameTargetId, title: newTitle
                });
                if (!data.success) return;

                // Update sidebar item
                const el = convItems.querySelector(`[data-id="${renameTargetId}"] .conv-title`);
                if (el) el.textContent = newTitle;

                // Update topbar if active
                if (renameTargetId == currentSessionId) topbarTitle.textContent = newTitle;

                showToast('Đã đổi tên hội thoại');
            } catch (e) { showToast('Không đổi tên được'); }
        }

        /* ── SEND MESSAGE ─────────────────────────────────────────── */
        async function sendMessage() {
            if (isSending) return;
            const text = msgInput.value.trim();
            if (!text) return;

            // Create session if none
            if (!currentSessionId) {
                await newSession();
                if (!currentSessionId) return;
            }

            isSending = true;
            msgInput.value = '';
            autoResize();
            setSendState('loading');

            emptyState.style.display = 'none';
            appendMessage('user', text);
            scrollBottom();
            showTyping();

            try {
                const data = await apiFetch(API, 'POST', {
                    action: 'send_message',
                    session_id: currentSessionId,
                    message: text
                });

                hideTyping();
                setSendState('idle');
                isSending = false;

                if (!data.success) {
                    showToast(data.error || 'Lỗi gửi tin nhắn');
                    return;
                }

                appendMessage('assistant', data.reply);
                scrollBottom();

                // Update title if changed
                if (data.title) {
                    const titleEl = convItems.querySelector(`[data-id="${data.session_id}"] .conv-title`);
                    if (titleEl && titleEl.textContent === 'Hội thoại mới') {
                        titleEl.textContent = data.title;
                        topbarTitle.textContent = data.title;
                    }
                }

            } catch (e) {
                hideTyping();
                setSendState('idle');
                isSending = false;
                showToast('Lỗi kết nối server');
            }
        }

        function fillAndSend(text) {
            msgInput.value = text;
            autoResize();
            updateSendBtn();
            sendMessage();
        }

        /* ── MESSAGE RENDERING ────────────────────────────────────── */
        function appendMessage(role, text, time, animate = true) {
            const isUser = role === 'user';
            const row = document.createElement('div');
            row.className = `message-row ${isUser ? 'user' : 'ai'}`;
            if (!animate) row.style.animation = 'none';

            const avatar = isUser
                ? `<div class="msg-avatar user-av"><?php echo strtoupper(mb_substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?></div>`
                : `<div class="msg-avatar ai"><i class="fas fa-robot"></i></div>`;

            const timeStr = time
                ? new Date(time).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
                : new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });

            const formatted = isUser ? esc(text) : markdownToHtml(text);

            row.innerHTML = `
        ${avatar}
        <div class="msg-content">
            <div class="msg-bubble">${formatted}</div>
            <div class="msg-time">${timeStr}</div>
        </div>
    `;
            messagesArea.appendChild(row);
        }

        function markdownToHtml(text) {
            // Escape HTML first (except known patterns)
            let html = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Code blocks
            html = html.replace(/```([\s\S]*?)```/g, (_, code) =>
                `<pre><code>${code.trim()}</code></pre>`
            );

            // Inline code
            html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

            // Bold **text**
            html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

            // Italic *text*
            html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');

            // Bullet lists
            html = html.replace(/^[•\-\*] (.+)$/gm, '<li>$1</li>');
            html = html.replace(/(<li>[\s\S]*?<\/li>\n?)+/g, m => `<ul>${m}</ul>`);

            // Numbered lists
            html = html.replace(/^\d+\. (.+)$/gm, '<li>$1</li>');

            // Paragraphs (newlines → <br>)
            html = html.replace(/\n/g, '<br>');

            // Remove double <br> in lists
            html = html.replace(/<\/ul><br>/g, '</ul>');
            html = html.replace(/<br><ul>/g, '<ul>');

            return html;
        }

        /* ── TYPING INDICATOR ─────────────────────────────────────── */
        function showTyping() {
            if (document.getElementById('typingIndicator')) return;
            const el = document.createElement('div');
            el.className = 'typing-indicator';
            el.id = 'typingIndicator';
            el.innerHTML = `
        <div class="msg-avatar ai"><i class="fas fa-robot"></i></div>
        <div class="typing-bubble">
            <div class="dot"></div><div class="dot"></div><div class="dot"></div>
        </div>
    `;
            messagesArea.appendChild(el);
            scrollBottom();
        }

        function hideTyping() {
            const el = document.getElementById('typingIndicator');
            if (el) el.remove();
        }

        /* ── HELPERS ──────────────────────────────────────────────── */
        function clearMessages() {
            // Remove all message rows
            messagesArea.querySelectorAll('.message-row, .typing-indicator').forEach(el => el.remove());
        }

        function scrollBottom() {
            setTimeout(() => { messagesArea.scrollTop = messagesArea.scrollHeight; }, 50);
        }

        function setSendState(state) {
            sendBtn.className = '';
            if (state === 'loading') {
                sendBtn.classList.add('loading');
                sendIcon.className = 'fas fa-spinner fa-spin';
            } else {
                sendIcon.className = 'fas fa-paper-plane';
                updateSendBtn();
            }
        }

        function updateSendBtn() {
            sendBtn.classList.toggle('ready', msgInput.value.trim().length > 0);
        }

        function autoResize() {
            msgInput.style.height = 'auto';
            msgInput.style.height = Math.min(msgInput.scrollHeight, 160) + 'px';
        }

        function esc(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function showToast(msg, duration = 2800) {
            toast.textContent = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), duration);
        }

        /* ── INPUT SETUP ──────────────────────────────────────────── */
        function setupInput() {
            msgInput.addEventListener('input', () => { autoResize(); updateSendBtn(); });

            msgInput.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (!isSending) sendMessage();
                }
            });

            sendBtn.addEventListener('click', () => { if (!isSending) sendMessage(); });
        }

        /* ── SIDEBAR MOBILE ───────────────────────────────────────── */
        function setupSidebar() {
            document.getElementById('sidebarToggle').addEventListener('click', () => {
                sidebar.classList.toggle('open');
                sidebarOverlay.classList.toggle('visible', sidebar.classList.contains('open'));
            });

            sidebarOverlay.addEventListener('click', closeSidebarMobile);
        }

        function closeSidebarMobile() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('visible');
        }

        /* ── MODAL SETUP ──────────────────────────────────────────── */
        function setupModal() {
            document.getElementById('renameCancelBtn').addEventListener('click', () => {
                renameModal.classList.remove('open');
            });
            document.getElementById('renameConfirmBtn').addEventListener('click', confirmRename);
            renameInput.addEventListener('keydown', e => {
                if (e.key === 'Enter') confirmRename();
                if (e.key === 'Escape') renameModal.classList.remove('open');
            });
            renameModal.addEventListener('click', e => {
                if (e.target === renameModal) renameModal.classList.remove('open');
            });
        }

        /* ── API FETCH ────────────────────────────────────────────── */
        async function apiFetch(url, method = 'GET', body = null) {
            const opts = { method };
            if (method === 'POST' && body) {
                const fd = new FormData();
                Object.entries(body).forEach(([k, v]) => fd.append(k, v));
                opts.body = fd;
            }
            const res = await fetch(url, opts);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        }
    </script>
</body>

</html>