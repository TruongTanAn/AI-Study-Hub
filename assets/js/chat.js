/* AI Study Hub - Chatbot Client (Week 5 - AN)
 *
 * Luong hoat dong:
 *  - Khoi tao: load conversation sidebar + (neu co conversation_id) load messages
 *  - Gui cau hoi: POST den chat_api.php (hoac document_qa.php neu co document)
 *  - Typing effect cho bot reply
 *  - Sidebar cap nhat sau moi luot gui
 *  - Preview document: GET preview_document.php?document_id=...
 */

(function () {
    'use strict';

    const CFG = window.CHAT_CONFIG || {};

    const $ = (sel, ctx) => (ctx || document).querySelector(sel);
    const $$ = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));

    const els = {
        shell: $('#chatbotShell'),
        sidebar: $('#chatSidebar'),
        toggleBtn: $('#toggleSidebarBtn'),
        openSidebarMobileBtn: $('#openSidebarBtnMobile'),
        newChatBtn: $('#newChatBtn'),
        convList: $('#conversationList'),
        messages: $('#chatMessages'),
        welcome: $('#chatWelcome'),
        form: $('#chatForm'),
        input: $('#chatInput'),
        sendBtn: $('#chatSendBtn'),
        modelBadge: $('#modelBadge'),
        docBadge: $('#docBadge'),
        docBadgeTitle: $('#docBadgeTitle'),
        clearDocBtn: $('#clearDocBtn'),
        docPreview: $('#docPreview'),
        docPreviewTitle: $('#docPreviewTitle'),
        docPreviewBody: $('#docPreviewBody'),
        closePreviewBtn: $('#closePreviewBtn'),
    };

    const state = {
        conversationId: CFG.conversationId || 0,
        documentId: CFG.documentId || 0,
        document: CFG.document || null,
        sending: false,
        convCache: [],
        previewOpen: false,
    };

    // ====================== Toast ======================
    let toastBox = null;
    function ensureToastBox() {
        if (!toastBox) {
            toastBox = document.createElement('div');
            toastBox.className = 'toast-container';
            document.body.appendChild(toastBox);
        }
        return toastBox;
    }

    function toast(message, type) {
        type = type || 'info';
        const box = ensureToastBox();
        const node = document.createElement('div');
        node.className = 'toast ' + (type === 'error' ? 'error' : (type === 'success' ? 'success' : ''));
        node.textContent = String(message);
        box.appendChild(node);
        setTimeout(() => {
            node.style.opacity = '0';
            node.style.transition = 'opacity 0.3s';
            setTimeout(() => node.remove(), 320);
        }, 3500);
    }

    // ====================== Helpers ======================
    function scrollToBottom(smooth) {
        const node = els.messages;
        if (!node) return;
        if (smooth === false) {
            node.scrollTop = node.scrollHeight;
        } else {
            node.scrollTo({ top: node.scrollHeight, behavior: 'smooth' });
        }
    }

    function fmtTime(iso) {
        if (!iso) return '';
        const d = new Date(iso.replace(' ', 'T'));
        if (isNaN(d.getTime())) return '';
        const pad = (n) => String(n).padStart(2, '0');
        return pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function nl2br(str) {
        return escapeHtml(str).replace(/\r?\n/g, '<br>');
    }

    function setSending(b) {
        state.sending = !!b;
        if (els.sendBtn) els.sendBtn.disabled = state.sending;
        if (els.input) els.input.disabled = state.sending;
    }

    // ====================== Render messages ======================
    function hideWelcome() {
        if (els.welcome) els.welcome.style.display = 'none';
    }

    function appendMessage(role, text, opts) {
        opts = opts || {};
        hideWelcome();
        const wrap = document.createElement('div');
        wrap.className = 'message ' + (role === 'user' ? 'user' : (role === 'error' ? 'error bot' : (role === 'system' ? 'system bot' : 'bot')));
        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';
        avatar.innerHTML = role === 'user' ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        bubble.innerHTML = nl2br(text);
        const meta = document.createElement('div');
        meta.className = 'message-meta';
        meta.textContent = opts.time ? fmtTime(opts.time) : fmtTime(new Date().toISOString().slice(0, 19).replace('T', ' '));

        wrap.appendChild(bubble);
        wrap.appendChild(avatar);
        wrap.appendChild(meta);
        els.messages.appendChild(wrap);
        scrollToBottom();
        return wrap;
    }

    function appendTyping() {
        hideWelcome();
        const wrap = document.createElement('div');
        wrap.className = 'message bot typing';
        wrap.innerHTML = `
            <div class="message-bubble">
                <span class="typing-indicator"><span></span><span></span><span></span></span>
            </div>
            <div class="message-avatar"><i class="fas fa-robot"></i></div>
            <div class="message-meta">Đang trả lời...</div>
        `;
        els.messages.appendChild(wrap);
        scrollToBottom();
        return wrap;
    }

    function typingEffect(targetNode, fullText) {
        // fullText may contain plain text; show progressively
        fullText = String(fullText || '');
        const speed = fullText.length > 800 ? 4 : 14;
        let i = 0;
        return new Promise((resolve) => {
            function step() {
                i = Math.min(fullText.length, i + 4);
                targetNode.innerHTML = nl2br(fullText.slice(0, i));
                scrollToBottom();
                if (i < fullText.length) {
                    setTimeout(step, speed);
                } else {
                    resolve();
                }
            }
            step();
        });
    }

    function clearMessages() {
        const keep = [];
        $$('.message', els.messages).forEach(n => n.remove());
        if (els.welcome) els.welcome.style.display = '';
    }

    // ====================== Sidebar ======================
    async function loadConversations() {
        try {
            const res = await fetch(CFG.apiHistory + '?scope=conversations&t=' + Date.now(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (!data || !data.success) {
                if (els.convList) els.convList.innerHTML = '<div class="conversation-empty">Chưa có cuộc trò chuyện nào.</div>';
                return;
            }
            state.convCache = data.conversations || [];
            renderConversations();
        } catch (err) {
            console.error('loadConversations', err);
            if (els.convList) els.convList.innerHTML = '<div class="conversation-empty">Không thể tải lịch sử.</div>';
        }
    }

    function renderConversations() {
        if (!els.convList) return;
        const list = state.convCache;
        if (!list.length) {
            els.convList.innerHTML = '<div class="conversation-empty">Chưa có cuộc trò chuyện nào.<br>Hãy bắt đầu trò chuyện mới!</div>';
            return;
        }
        const html = list.map(c => {
            const title = (c.title || 'Cuộc trò chuyện').toString().slice(0, 50);
            const docIcon = c.document_id ? '<i class="fas fa-file-alt" title="RAG theo tài liệu"></i>' : '<i class="fas fa-comment"></i>';
            const active = (c.conversation_id === state.conversationId) ? ' active' : '';
            return `
                <div class="conversation-item${active}" data-cid="${c.conversation_id}">
                    <div class="conversation-title">${docIcon} ${escapeHtml(title)}</div>
                    <div class="conversation-actions">
                        <button class="btn-conv-action btn-delete-conv" data-cid="${c.conversation_id}" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
        els.convList.innerHTML = html;

        $$('.conversation-item', els.convList).forEach(node => {
            node.addEventListener('click', (ev) => {
                if (ev.target.closest('.btn-delete-conv')) return;
                const cid = parseInt(node.getAttribute('data-cid'), 10) || 0;
                if (cid > 0) openConversation(cid);
            });
        });

        $$('.btn-delete-conv', els.convList).forEach(btn => {
            btn.addEventListener('click', async (ev) => {
                ev.stopPropagation();
                const cid = parseInt(btn.getAttribute('data-cid'), 10) || 0;
                if (!confirm('Xóa cuộc trò chuyện này?')) return;
                await deleteConversation(cid);
            });
        });
    }

    async function openConversation(cid) {
        state.conversationId = cid;
        // Update URL
        const url = new URL(window.location.href);
        url.searchParams.set('conversation_id', cid);
        url.searchParams.delete('doc_id');
        window.history.replaceState({}, '', url.toString());

        $$('.conversation-item', els.convList).forEach(n => n.classList.remove('active'));
        const node = els.convList.querySelector(`.conversation-item[data-cid="${cid}"]`);
        if (node) node.classList.add('active');

        try {
            const res = await fetch(CFG.apiHistory + '?scope=messages&conversation_id=' + cid, {
                method: 'GET',
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!data || !data.success) {
                toast(data && data.error ? data.error : 'Không tải được lịch sử', 'error');
                return;
            }
            clearMessages();
            const msgs = data.messages || [];
            msgs.forEach(m => {
                if (m.role === 'system') return; // hide system errors from UI
                appendMessage(m.role === 'assistant' ? 'bot' : m.role, m.message, { time: m.created_at });
            });
            scrollToBottom(false);
        } catch (err) {
            console.error('openConversation', err);
            toast('Lỗi mạng khi tải lịch sử', 'error');
        }
    }

    async function deleteConversation(cid) {
        try {
            const res = await fetch('../backend/delete_conversation.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ conversation_id: cid }),
            });
            const rawText = await res.text();
            let data;
            try {
                data = rawText ? JSON.parse(rawText) : {};
            } catch (err) {
                throw new Error('Phan hoi khong phai JSON: ' + rawText.slice(0, 120));
            }
            if (!data || !data.success) {
                toast((data && data.error) || ('HTTP ' + res.status), 'error');
                return;
            }
            // Neu dang xoa conversation dang mo -> reset ve chat moi
            if (state.conversationId === cid) {
                state.conversationId = 0;
                state.document = null;
                state.documentId = 0;
                clearMessages();
                els.docBadge.classList.add('hidden');
                hidePreview();
                const url = new URL(window.location.href);
                url.searchParams.delete('conversation_id');
                url.searchParams.delete('doc_id');
                window.history.replaceState({}, '', url.toString());
            }
            // Reload sidebar de bo item vua xoa
            await loadConversations();
            toast('Da xoa cuoc tro chuyen', 'success');
        } catch (err) {
            console.error('deleteConversation', err);
            toast('Loi khi xoa cuoc tro chuyen: ' + (err && err.message ? err.message : 'unknown'), 'error');
        }
    }

    function startNewConversation() {
        state.conversationId = 0;
        clearMessages();
        const url = new URL(window.location.href);
        url.searchParams.delete('conversation_id');
        url.searchParams.delete('doc_id');
        window.history.replaceState({}, '', url);
        $$('.conversation-item', els.convList).forEach(n => n.classList.remove('active'));
        setDocument(null);
        if (els.input) els.input.focus();
    }

    // ====================== Document preview ======================
    async function loadDocumentPreview(documentId) {
        if (!documentId) return;
        try {
            const res = await fetch(CFG.apiPreview + '?document_id=' + documentId + '&t=' + Date.now(), {
                method: 'GET',
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!data || !data.success) {
                toast((data && data.error) || 'Khong the tai preview', 'error');
                return;
            }
            const f = data.file || {};
            els.docPreviewTitle.textContent = (data.document && data.document.title) || 'Tài liệu';
            let body = '';
            if (f.preview === 'text' && f.text) {
                body = '<pre style="white-space:pre-wrap; font-family:inherit;">' + escapeHtml(f.text) + '</pre>';
            } else if (f.preview === 'pdf' && f.url) {
                body = '<iframe src="' + escapeHtml(f.url) + '" style="width:100%;height:360px;border:none;border-radius:8px;"></iframe>';
            } else if (f.preview === 'image' && f.url) {
                body = '<img src="' + escapeHtml(f.url) + '" style="max-width:100%;max-height:360px;display:block;margin:0 auto;border-radius:8px;" />';
            } else if (f.preview === 'office_online' && f.url) {
                // Microsoft Office Online viewer - ho tro DOCX, PPTX, XLSX,...
                var officeSrc = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(f.url);
                body = '<iframe src="' + officeSrc + '" style="width:100%;height:360px;border:none;border-radius:8px;"></iframe>';
                body += '<p style="font-size:0.78rem;color:#94a3b8;margin-top:6px;text-align:center;">Preview qua Microsoft Office Online. Cần kết nối Internet.</p>';
            } else if (!f.exists) {
                body = '<div class="preview-empty">File không tồn tại trên hệ thống lưu trữ (Supabase Storage).</div>';
            } else {
                body = '<div class="preview-empty">Định dạng file này chưa hỗ trợ xem nhanh. Bạn vẫn có thể hỏi AI về tài liệu.</div>';
            }
            els.docPreviewBody.innerHTML = body;
            els.docPreview.classList.remove('hidden');
            state.previewOpen = true;
        } catch (err) {
            console.error('preview', err);
            toast('Lỗi khi tai preview', 'error');
        }
    }

    function hidePreview() {
        els.docPreview.classList.add('hidden');
        els.docPreviewBody.innerHTML = '';
        state.previewOpen = false;
    }

    function setDocument(doc) {
        if (!doc) {
            state.document = null;
            state.documentId = 0;
            els.docBadge.classList.add('hidden');
            hidePreview();
            const url = new URL(window.location.href);
            url.searchParams.delete('doc_id');
            window.history.replaceState({}, '', url.toString());
            return;
        }
        state.document = doc;
        state.documentId = parseInt(doc.document_id, 10) || 0;
        els.docBadge.classList.remove('hidden');
        els.docBadgeTitle.textContent = (doc.title || 'Tài liệu').toString().slice(0, 32);
        const url = new URL(window.location.href);
        url.searchParams.set('doc_id', String(state.documentId));
        window.history.replaceState({}, '', url.toString());
        loadDocumentPreview(state.documentId);
    }

    function initFromConfig() {
        if (CFG.docError) {
            toast(CFG.docError, 'error');
        }
        if (state.document && state.document.document_id) {
            els.docBadge.classList.remove('hidden');
            els.docBadgeTitle.textContent = (state.document.title || 'Tài liệu').toString().slice(0, 32);
            loadDocumentPreview(state.documentId);
        }
        if (state.conversationId > 0) {
            openConversation(state.conversationId);
        }
    }

    // ====================== Send message ======================
    async function sendMessage(text) {
        text = (text || '').trim();
        if (!text) return;
        if (state.sending) return;

        appendMessage('user', text);
        setSending(true);

        const typing = appendTyping();

        const payload = {
            message: text,
            conversation_id: state.conversationId,
            document_id: state.documentId,
        };

        const endpoint = state.documentId > 0 ? CFG.apiDocChat : CFG.apiChat;

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });

            const rawText = await res.text();
            let data;
            try {
                data = rawText ? JSON.parse(rawText) : {};
            } catch (err) {
                throw new Error('Phan hoi khong phai JSON: ' + rawText.slice(0, 120));
            }

            typing.remove();

            if (data && data.success) {
                if (data.conversation_id) {
                    state.conversationId = parseInt(data.conversation_id, 10) || state.conversationId;
                    const url = new URL(window.location.href);
                    url.searchParams.set('conversation_id', String(state.conversationId));
                    window.history.replaceState({}, '', url.toString());
                }

                const wrap = appendMessage('bot', data.message || '', { time: new Date().toISOString().slice(0, 19).replace('T', ' ') });
                const bubbleNode = wrap.querySelector('.message-bubble');
                if (bubbleNode) {
                    // small typing effect for natural feel; skip for very long replies to be safe
                    const reply = (data.message || '').toString();
                    if (reply.length < 5000) {
                        await typingEffect(bubbleNode, reply);
                    }
                }
                await loadConversations();
                $$('.conversation-item', els.convList).forEach(n => n.classList.remove('active'));
                const node = els.convList.querySelector(`.conversation-item[data-cid="${state.conversationId}"]`);
                if (node) node.classList.add('active');
            } else {
                const errMsg = (data && (data.error || data.message)) || ('HTTP ' + res.status);
                appendMessage('error', 'Lỗi: ' + errMsg);
                toast(errMsg, 'error');
            }
        } catch (err) {
            typing.remove();
            appendMessage('error', 'Lỗi mạng: ' + (err && err.message ? err.message : 'unknown'));
            toast('Lỗi mạng khi goi AI', 'error');
            console.error(err);
        } finally {
            setSending(false);
            if (els.input) els.input.focus();
        }
    }

    // ====================== Form ======================
    function autoResize() {
        const el = els.input;
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(180, el.scrollHeight) + 'px';
    }

    function bindForm() {
        if (!els.form) return;
        els.form.addEventListener('submit', (ev) => {
            ev.preventDefault();
            const text = els.input.value;
            els.input.value = '';
            autoResize();
            sendMessage(text);
        });
        if (els.input) {
            els.input.addEventListener('keydown', (ev) => {
                if (ev.key === 'Enter' && !ev.shiftKey) {
                    ev.preventDefault();
                    const text = els.input.value;
                    els.input.value = '';
                    autoResize();
                    sendMessage(text);
                }
            });
            els.input.addEventListener('input', autoResize);
        }
        $$('.suggestion-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const q = btn.getAttribute('data-q');
                if (q) sendMessage(q);
            });
        });
    }

    // ====================== Sidebar toggling ======================
    function bindSidebar() {
        if (els.toggleBtn) {
            els.toggleBtn.addEventListener('click', () => {
                els.sidebar.classList.toggle('collapsed');
            });
        }
        if (els.openSidebarMobileBtn) {
            els.openSidebarMobileBtn.addEventListener('click', () => {
                els.sidebar.classList.toggle('open');
            });
        }
        if (els.newChatBtn) {
            els.newChatBtn.addEventListener('click', () => {
                startNewConversation();
                if (els.sidebar.classList.contains('open')) els.sidebar.classList.remove('open');
            });
        }
    }

    function bindDocActions() {
        if (els.clearDocBtn) {
            els.clearDocBtn.addEventListener('click', () => setDocument(null));
        }
        if (els.closePreviewBtn) {
            els.closePreviewBtn.addEventListener('click', hidePreview);
        }
    }

    // ====================== Init ======================
    function init() {
        if (!CFG.apiChat) {
            console.error('CHAT_CONFIG missing');
            return;
        }
        bindForm();
        bindSidebar();
        bindDocActions();
        initFromConfig();
        loadConversations();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
