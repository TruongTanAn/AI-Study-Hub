/**
 * chatbot.js — AI Study Hub Chatbot Frontend Logic
 * Handles: send/receive messages, typing effect, file attach,
 *          chat history load, suggestion chips, new chat, scroll.
 */

(function () {
    'use strict';

    /* ── STATE ──────────────────────────────────────────── */
    let conversationId = null;
    let attachedFile   = null;
    let isSending      = false;

    // Read optional document_id from URL query string (?doc_id=X)
    const urlParams     = new URLSearchParams(window.location.search);
    const urlDocumentId = urlParams.get('doc_id') || null;

    /* ── DOM REFERENCES ─────────────────────────────────── */
    const textarea        = document.getElementById('chat-textarea');
    const btnSend         = document.getElementById('btn-send');
    const fileInput       = document.getElementById('file-input');
    const btnAttach       = document.getElementById('btn-attach');
    const attachStrip     = document.getElementById('attachment-strip');
    const attachName      = document.getElementById('attach-name');
    const btnRemoveAttach = document.getElementById('btn-remove-attach');
    const messagesWrapper = document.getElementById('messages-wrapper');
    const welcomeScreen   = document.getElementById('welcome-screen');
    const messagesList    = document.getElementById('messages-list');
    const historyList     = document.getElementById('history-list');
    const btnNewChat      = document.getElementById('btn-new-chat');

    /* ── INIT ───────────────────────────────────────────── */
    function init() {
        bindEvents();
        loadHistoryList();

        // If a doc_id was passed in the URL, auto-focus textarea with a hint
        if (urlDocumentId) {
            textarea.placeholder = 'Hỏi AI về tài liệu này…';
        }
    }

    /* ── EVENT BINDINGS ─────────────────────────────────── */
    function bindEvents() {
        // Send via button
        btnSend.addEventListener('click', handleSend);

        // Keyboard shortcuts
        textarea.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSend();
            }
        });

        // Auto-resize textarea
        textarea.addEventListener('input', autoResize);

        // File attach
        btnAttach.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', handleFileChange);

        // Remove attached file
        btnRemoveAttach.addEventListener('click', removeAttachedFile);

        // New chat
        btnNewChat.addEventListener('click', startNewChat);

        // Suggestion chips
        document.querySelectorAll('.chip').forEach(chip => {
            chip.addEventListener('click', () => {
                textarea.value = chip.dataset.text;
                autoResize();
                textarea.focus();
            });
        });
    }

    /* ── AUTO-RESIZE TEXTAREA ────────────────────────────── */
    function autoResize() {
        textarea.style.height = 'auto';
        const newHeight = Math.min(textarea.scrollHeight, 120);
        textarea.style.height = newHeight + 'px';
    }

    /* ── FILE HANDLING ──────────────────────────────────── */
    function handleFileChange(e) {
        const file = e.target.files[0];
        if (!file) return;

        attachedFile = file;
        attachName.textContent = file.name;
        attachStrip.classList.add('visible');

        // Show preview using preview.js entry point
        if (typeof previewFile === 'function') {
            previewFile(file);
        }

        // Reset input so the same file can be re-selected
        fileInput.value = '';
    }

    function removeAttachedFile() {
        attachedFile = null;
        attachStrip.classList.remove('visible');
        fileInput.value = '';

        // Close preview panel
        if (typeof closePreview === 'function') {
            closePreview();
        }
    }

    /* ── SEND MESSAGE ───────────────────────────────────── */
    async function handleSend() {
        const text = textarea.value.trim();
        if (!text || isSending) return;

        isSending = true;
        btnSend.disabled = true;

        // Show messages section, hide welcome
        showMessagesSection();

        // Append user bubble
        appendMessage('user', text);

        // Clear textarea
        textarea.value = '';
        autoResize();

        // Show typing indicator
        const typingRow = appendTypingIndicator();

        try {
            const formData = new FormData();
            formData.append('message', text);

            if (conversationId) {
                formData.append('conversation_id', conversationId);
            }

            if (urlDocumentId) {
                formData.append('document_id', urlDocumentId);
            }

            if (attachedFile) {
                formData.append('file', attachedFile);
            }

            const response = await fetch('../backend/chat_api.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            // Remove typing indicator
            typingRow.remove();

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            // Save conversation_id for subsequent messages
            if (data.conversation_id) {
                conversationId = data.conversation_id;
            }

            // Append AI reply with typing effect
            const reply = data.reply || 'Xin lỗi, tôi không hiểu câu hỏi của bạn. Vui lòng thử lại.';
            appendMessageWithTypingEffect('ai', reply);

            // Refresh history list in sidebar
            loadHistoryList();

        } catch (err) {
            typingRow.remove();
            appendErrorMessage('Không thể kết nối với AI. Vui lòng thử lại sau.');
            console.error('[Chatbot] Error:', err);
        } finally {
            isSending = false;
            btnSend.disabled = false;
            textarea.focus();
        }
    }

    /* ── SHOW/HIDE SECTIONS ──────────────────────────────── */
    function showMessagesSection() {
        if (welcomeScreen) welcomeScreen.style.display = 'none';
        messagesList.style.display = 'flex';
    }

    function showWelcomeSection() {
        if (welcomeScreen) welcomeScreen.style.display = '';
        messagesList.style.display = 'none';
        messagesList.innerHTML = '';
    }

    /* ── APPEND MESSAGE ──────────────────────────────────── */
    function appendMessage(role, text) {
        const row = document.createElement('div');
        row.className = `message-row ${role}`;

        const avatarIcon = role === 'ai' ? 'fa-robot' : 'fa-user';
        const timeStr    = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });

        // Render markdown-ish line breaks
        const htmlText = escapeHtml(text).replace(/\n/g, '<br>');

        row.innerHTML = `
            <div class="avatar ${role}"><i class="fas ${avatarIcon}"></i></div>
            <div>
                <div class="bubble">${htmlText}</div>
                <div class="message-time">${timeStr}</div>
            </div>
        `;

        messagesList.appendChild(row);
        scrollToBottom();
        return row;
    }

    /* ── TYPING EFFECT ───────────────────────────────────── */
    function appendMessageWithTypingEffect(role, fullText) {
        const row = document.createElement('div');
        row.className = `message-row ${role}`;

        const avatarIcon = role === 'ai' ? 'fa-robot' : 'fa-user';
        const timeStr    = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });

        const bubble = document.createElement('div');
        bubble.className = 'bubble';

        const timeEl = document.createElement('div');
        timeEl.className = 'message-time';
        timeEl.textContent = timeStr;

        const avatarEl = document.createElement('div');
        avatarEl.className = `avatar ${role}`;
        avatarEl.innerHTML = `<i class="fas ${avatarIcon}"></i>`;

        const wrapper = document.createElement('div');
        wrapper.appendChild(bubble);
        wrapper.appendChild(timeEl);

        row.appendChild(avatarEl);
        row.appendChild(wrapper);
        messagesList.appendChild(row);
        scrollToBottom();

        // Type characters one by one
        let index = 0;
        const interval = setInterval(() => {
            if (index >= fullText.length) {
                clearInterval(interval);
                return;
            }
            const char = fullText[index];
            index++;

            // Build display: escape and convert \n to <br>
            const displayed = escapeHtml(fullText.slice(0, index)).replace(/\n/g, '<br>');
            bubble.innerHTML = displayed;
            scrollToBottom();
        }, 18);
    }

    /* ── TYPING INDICATOR ────────────────────────────────── */
    function appendTypingIndicator() {
        const row = document.createElement('div');
        row.className = 'message-row ai';
        row.id = 'typing-row';
        row.innerHTML = `
            <div class="avatar ai"><i class="fas fa-robot"></i></div>
            <div class="typing-indicator">
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
            </div>
        `;
        messagesList.appendChild(row);
        scrollToBottom();
        return row;
    }

    /* ── ERROR MESSAGE ───────────────────────────────────── */
    function appendErrorMessage(msg) {
        const el = document.createElement('div');
        el.className = 'inline-error';
        el.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${escapeHtml(msg)}`;
        messagesList.appendChild(el);
        scrollToBottom();
    }

    /* ── SCROLL TO BOTTOM ────────────────────────────────── */
    function scrollToBottom() {
        messagesWrapper.scrollTop = messagesWrapper.scrollHeight;
    }

    /* ── NEW CHAT ────────────────────────────────────────── */
    function startNewChat() {
        conversationId = null;
        removeAttachedFile();
        showWelcomeSection();
        textarea.value = '';
        autoResize();
        textarea.focus();

        // Remove active state from history items
        document.querySelectorAll('.history-item').forEach(el => el.classList.remove('active'));
    }

    /* ── LOAD HISTORY LIST (sidebar) ─────────────────────── */
    async function loadHistoryList() {
        try {
            const response = await fetch('../backend/load_chat_history.php?list=1', {
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();

            if (!data || !Array.isArray(data.conversations) || data.conversations.length === 0) {
                renderEmptyHistory();
                return;
            }

            renderHistoryList(data.conversations);

        } catch (err) {
            console.warn('[Chatbot] Could not load history:', err);
            renderEmptyHistory();
        }
    }

    function renderEmptyHistory() {
        historyList.innerHTML = `
            <div class="history-empty">
                <i class="fas fa-comment-slash"></i>
                Chưa có hội thoại nào
            </div>
        `;
    }

    function renderHistoryList(conversations) {
        historyList.innerHTML = '';

        conversations.forEach(conv => {
            const btn = document.createElement('button');
            btn.className = 'history-item';
            btn.dataset.id = conv.conversation_id;

            const title   = conv.title || 'Hội thoại không tên';
            const dateStr = conv.updated_at
                ? new Date(conv.updated_at).toLocaleDateString('vi-VN')
                : '';

            btn.innerHTML = `
                <i class="fas fa-comment-dots"></i>
                <span class="history-title" title="${escapeHtml(title)}">${escapeHtml(title)}</span>
                ${dateStr ? `<span class="history-date">${dateStr}</span>` : ''}
            `;

            btn.addEventListener('click', () => loadConversation(conv.conversation_id, btn));
            historyList.appendChild(btn);
        });
    }

    /* ── LOAD OLD CONVERSATION ────────────────────────────── */
    async function loadConversation(id, btnEl) {
        try {
            const response = await fetch(`../backend/load_chat_history.php?conversation_id=${encodeURIComponent(id)}`, {
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();

            // Update active state
            document.querySelectorAll('.history-item').forEach(el => el.classList.remove('active'));
            if (btnEl) btnEl.classList.add('active');

            // Set state
            conversationId = id;

            // Render messages
            showMessagesSection();
            messagesList.innerHTML = '';

            if (!data.messages || data.messages.length === 0) {
                showWelcomeSection();
                conversationId = null;
                return;
            }

            data.messages.forEach(msg => {
                const role = msg.role === 'user' ? 'user' : 'ai';
                appendMessage(role, msg.content || '');
            });

        } catch (err) {
            console.error('[Chatbot] Could not load conversation:', err);
            appendErrorMessage('Không thể tải lịch sử hội thoại. Vui lòng thử lại.');
        }
    }

    /* ── HELPERS ─────────────────────────────────────────── */
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /* ── START ───────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', init);

})();
