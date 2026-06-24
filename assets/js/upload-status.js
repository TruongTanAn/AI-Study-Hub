(function () {
    'use strict';

    var API_ENDPOINT = '../backend/upload_document.php';
    var ALLOWED_EXTENSIONS = ['pdf', 'docx', 'pptx'];

    var uploadForm           = null;
    var fileInput            = null;
    var progressContainer    = null;
    var statusTextEl         = null;
    var resultMessageEl      = null;
    var progressBarFill      = null;
    var progressPercentLabel = null;

    function init() {
        uploadForm        = document.getElementById('upload-form');
        fileInput         = document.getElementById('file-input');
        progressContainer = document.getElementById('upload-progress-container');
        statusTextEl      = document.getElementById('upload-status-text');
        resultMessageEl   = document.getElementById('upload-result-message');

        if (!uploadForm) return;

        renderProgressBar();
        uploadForm.addEventListener('submit', handleFormSubmit);
    }

    function renderProgressBar() {
        if (!progressContainer) return;
        if (progressContainer.querySelector('.upload-progress-bar')) return;

        progressContainer.innerHTML = [
            '<div class="upload-progress-label">',
            '    <span>Tiến trình upload</span>',
            '    <span class="upload-progress-percent" id="upload-progress-percent">0%</span>',
            '</div>',
            '<div class="upload-progress-bar">',
            '    <div class="upload-progress-fill" id="upload-progress-fill"></div>',
            '</div>'
        ].join('');

        progressBarFill      = document.getElementById('upload-progress-fill');
        progressPercentLabel = document.getElementById('upload-progress-percent');
    }

    function handleFormSubmit(event) {
        event.preventDefault();

        var validationError = validateFileClient();
        if (validationError) {
            showResultMessage(false, validationError);
            return;
        }

        resetUploadUI();

        var formData = new FormData(uploadForm);
        sendUploadRequest(formData);
    }

    function validateFileClient() {
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            return 'Vui lòng chọn file trước khi upload.';
        }

        var fileExtension = fileInput.files[0].name.split('.').pop().toLowerCase();

        if (ALLOWED_EXTENSIONS.indexOf(fileExtension) === -1) {
            return 'Chỉ chấp nhận file PDF, DOCX, PPTX. Vui lòng chọn lại.';
        }

        return null;
    }

    function sendUploadRequest(formData) {
        var xhr = new XMLHttpRequest();

        xhr.upload.onprogress = function (event) {
            if (!event.lengthComputable) return;

            var percent = Math.round((event.loaded / event.total) * 100);
            updateProgressBar(percent);
            updateStatusText(percent < 100 ? 'uploading' : 'processing');
        };

        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;

            if (xhr.status === 200) {
                handleServerResponse(xhr.responseText);
            } else {
                handleUploadError('Upload thất bại. Vui lòng thử lại. (Lỗi HTTP ' + xhr.status + ')');
            }
        };

        xhr.onerror = function () {
            handleUploadError('Không thể kết nối tới server. Vui lòng kiểm tra kết nối mạng.');
        };

        xhr.open('POST', API_ENDPOINT, true);
        xhr.send(formData);

        showProgressContainer();
        updateStatusText('uploading');
    }

    function handleServerResponse(responseText) {
        var response;

        try {
            response = JSON.parse(responseText);
        } catch (e) {
            handleUploadError('Phản hồi từ server không hợp lệ. Vui lòng thử lại.');
            return;
        }

        if (response.success === true) {
            updateProgressBar(100);
            updateStatusText('completed');

            setTimeout(function () {
                showResultMessage(true, 'Upload tài liệu thành công!');
                hideProgressContainerAfterDelay(1500);
                resetForm();
            }, 400);
        } else {
            var errorMessage = (response.message && response.message.trim())
                ? response.message
                : 'Upload thất bại. Vui lòng thử lại.';
            handleUploadError(errorMessage);
        }
    }

    function handleUploadError(message) {
        updateProgressBar(0);
        updateStatusText('idle');
        hideProgressContainerAfterDelay(0);
        showResultMessage(false, message);
    }

    function updateProgressBar(percent) {
        if (!progressBarFill || !progressPercentLabel) return;

        var safePercent = Math.min(Math.max(percent, 0), 100);

        progressBarFill.style.width      = safePercent + '%';
        progressPercentLabel.textContent = safePercent + '%';

        if (safePercent === 100) {
            progressBarFill.classList.add('completed');
        } else {
            progressBarFill.classList.remove('completed');
        }
    }

    function updateStatusText(state) {
        if (!statusTextEl) return;

        statusTextEl.className = 'upload-status-text';

        var iconHtml   = '<span class="upload-status-icon"></span>';
        var messageMap = {
            uploading:  'Đang upload file...',
            processing: 'Đang xử lý trên server...',
            completed:  'Hoàn tất',
            idle:       ''
        };

        if (state === 'idle') {
            statusTextEl.style.display = 'none';
            statusTextEl.innerHTML     = '';
            return;
        }

        if (state === 'completed') {
            statusTextEl.classList.add('completed');
        }

        statusTextEl.style.display = 'flex';
        statusTextEl.innerHTML     = iconHtml + messageMap[state];
    }

    function showResultMessage(isSuccess, message) {
        if (!resultMessageEl) return;

        resultMessageEl.className = 'upload-result-message';

        var iconSymbol = isSuccess ? '✔' : '✖';
        var alertClass = isSuccess ? 'alert-success' : 'alert-error';

        resultMessageEl.classList.add(alertClass);
        resultMessageEl.style.display = 'flex';
        resultMessageEl.innerHTML = [
            '<span class="alert-icon">' + iconSymbol + '</span>',
            '<span class="alert-text">' + escapeHtml(message) + '</span>'
        ].join('');
    }

    function showProgressContainer() {
        if (!progressContainer) return;
        progressContainer.classList.add('active');
        progressContainer.style.display = 'block';
    }

    function hideProgressContainerAfterDelay(delayMs) {
        if (!progressContainer) return;
        setTimeout(function () {
            progressContainer.classList.remove('active');
            progressContainer.style.display = 'none';
        }, delayMs);
    }

    function resetUploadUI() {
        if (resultMessageEl) {
            resultMessageEl.style.display = 'none';
            resultMessageEl.innerHTML     = '';
            resultMessageEl.className     = 'upload-result-message';
        }
        updateStatusText('idle');
        updateProgressBar(0);
    }

    function resetForm() {
        if (uploadForm) uploadForm.reset();
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
