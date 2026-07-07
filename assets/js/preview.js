/**
 * preview.js — AI Study Hub File Preview Handler
 * Entry point: previewFile(file)
 * Supports: PDF (iframe + blob URL), DOCX (mammoth.js), PPTX (unsupported notice)
 * Also exports: closePreview()
 */

(function () {
    'use strict';

    /* ── DOM REFS ────────────────────────────────────────── */
    const previewPanel       = document.getElementById('preview-panel');
    const previewFilename    = document.getElementById('preview-filename');
    const previewContentArea = document.getElementById('preview-content-area');
    const btnClosePreview    = document.getElementById('btn-close-preview');

    let activeBlobUrl = null;
    let revokeTimer   = null;

    /* ── INIT ───────────────────────────────────────────── */
    if (btnClosePreview) {
        btnClosePreview.addEventListener('click', closePreview);
    }

    /* ── PUBLIC: previewFile ─────────────────────────────── */
    window.previewFile = function previewFile(file) {
        if (!file || !previewPanel) return;

        const ext = getExtension(file.name);

        // Update titlebar filename
        if (previewFilename) {
            previewFilename.innerHTML = `
                <i class="fas ${getFileIcon(ext)}"></i>
                <span>${escapeHtml(file.name)}</span>
            `;
        }

        // Clear previous content
        clearPreviewContent();

        // Show the panel
        previewPanel.classList.add('visible');

        switch (ext) {
            case 'pdf':
                renderPdf(file);
                break;
            case 'docx':
            case 'doc':
                renderDocx(file);
                break;
            case 'pptx':
            case 'ppt':
                renderUnsupported();
                break;
            default:
                renderUnsupported('Định dạng tệp này không được hỗ trợ xem trước.');
                break;
        }
    };

    /* ── PUBLIC: closePreview ────────────────────────────── */
    window.closePreview = function closePreview() {
        if (!previewPanel) return;
        previewPanel.classList.remove('visible');
        clearPreviewContent();
    };

    /* ── PDF ─────────────────────────────────────────────── */
    function renderPdf(file) {
        // Revoke any previous blob URL
        revokePreviousBlobUrl();

        const blobUrl = URL.createObjectURL(file);
        activeBlobUrl = blobUrl;

        const iframe = document.createElement('iframe');
        iframe.src   = blobUrl + '#toolbar=0';
        iframe.style.cssText = 'width:100%;height:100%;border:none;border-radius:6px;';
        iframe.title = 'PDF Preview';

        previewContentArea.appendChild(iframe);

        // Revoke blob URL after 60 seconds to free memory
        revokeTimer = setTimeout(() => {
            URL.revokeObjectURL(blobUrl);
            activeBlobUrl = null;
        }, 60000);
    }

    /* ── DOCX ────────────────────────────────────────────── */
    async function renderDocx(file) {
        const loadingEl = createLoadingEl('Đang tải mammoth.js để xem trước…');
        previewContentArea.appendChild(loadingEl);

        try {
            // Load mammoth.js from CDN if not already loaded
            await ensureMammoth();

            const arrayBuffer = await file.arrayBuffer();
            const result      = await mammoth.convertToHtml({ arrayBuffer });

            loadingEl.remove();

            const container = document.createElement('div');
            container.className = 'docx-rendered';
            container.innerHTML = result.value || '<p><em>Tài liệu trống hoặc không đọc được.</em></p>';

            previewContentArea.appendChild(container);

        } catch (err) {
            loadingEl.remove();
            console.error('[Preview] DOCX error:', err);
            renderUnsupported('Không thể xem trước tệp DOCX. AI sẽ đọc nội dung của tệp.');
        }
    }

    /* ── PPTX / UNSUPPORTED ──────────────────────────────── */
    function renderUnsupported(message) {
        const msg = message || 'Xem trước PPTX không được hỗ trợ trên trình duyệt. AI sẽ đọc và phân tích nội dung tài liệu cho bạn.';
        const el  = document.createElement('div');
        el.className = 'preview-unsupported';
        el.innerHTML = `
            <i class="fas fa-file-powerpoint"></i>
            <p>${escapeHtml(msg)}</p>
        `;
        previewContentArea.appendChild(el);
    }

    /* ── ENSURE MAMMOTH.JS ───────────────────────────────── */
    function ensureMammoth() {
        return new Promise((resolve, reject) => {
            if (typeof mammoth !== 'undefined') {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src   = 'https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.8.0/mammoth.browser.min.js';
            script.onload  = resolve;
            script.onerror = () => reject(new Error('Failed to load mammoth.js from CDN'));
            document.head.appendChild(script);
        });
    }

    /* ── HELPERS ─────────────────────────────────────────── */
    function clearPreviewContent() {
        if (previewContentArea) {
            previewContentArea.innerHTML = '';
        }
        revokePreviousBlobUrl();
    }

    function revokePreviousBlobUrl() {
        if (revokeTimer) {
            clearTimeout(revokeTimer);
            revokeTimer = null;
        }
        if (activeBlobUrl) {
            URL.revokeObjectURL(activeBlobUrl);
            activeBlobUrl = null;
        }
    }

    function getExtension(filename) {
        return (filename.split('.').pop() || '').toLowerCase();
    }

    function getFileIcon(ext) {
        const map = {
            pdf:  'fa-file-pdf',
            docx: 'fa-file-word',
            doc:  'fa-file-word',
            pptx: 'fa-file-powerpoint',
            ppt:  'fa-file-powerpoint',
        };
        return map[ext] || 'fa-file';
    }

    function createLoadingEl(text) {
        const el = document.createElement('div');
        el.className = 'preview-unsupported';
        el.style.gap = '12px';
        el.innerHTML = `
            <i class="fas fa-spinner fa-spin" style="color:var(--primary);font-size:1.8rem;"></i>
            <p style="color:var(--gray);">${escapeHtml(text)}</p>
        `;
        return el;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

})();
