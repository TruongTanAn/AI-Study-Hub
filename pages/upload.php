<?php
require_once '../includes/auth_check.php';

$success_msg = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error_msg = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tải lên tài liệu - AI Study Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/upload.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <header class="upload-custom-header">
        <div class="logo">
            <a href="dashboard.php" class="logo-link">
                <i class="fas fa-brain"></i> AI Study Hub
            </a>
        </div>
        <div class="user-info">
            <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
            <a href="profile.php" class="header-btn"><i class="fas fa-user"></i> Hồ sơ</a>
            <a href="../backend/logout.php" class="header-btn logout"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </header>

    <div class="upload-wrapper">
        <div class="container">
            <div class="upload-container">
                <div class="upload-header">
                    <h2>Tải lên tài liệu mới</h2>
                    <p>Chia sẻ và lưu trữ tài liệu học tập của bạn an toàn trên hệ thống. Trải nghiệm tốc độ tải lên
                        mượt mà và giao diện hiện đại.</p>
                </div>

                <div id="alertContainer">
                    <?php if ($success_msg): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <span><?php echo $success_msg; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_msg): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <span><?php echo $error_msg; ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="upload-tabs">
                    <button class="tab-btn active" onclick="switchTab('dragdrop')">
                        <i class="fas fa-cloud-upload-alt"></i> Kéo & Thả
                    </button>
                    <button class="tab-btn" onclick="switchTab('form')">
                        <i class="fas fa-file-alt"></i> Chọn File
                    </button>
                </div>

                <!-- DRAG AND DROP SECTION -->
                <div id="dragdrop-section" class="upload-section active">
                    <form action="../backend/upload_document.php" method="POST" enctype="multipart/form-data"
                        id="dragDropForm">
                        <input type="hidden" name="upload_type" value="dragdrop">

                        <div class="drop-zone" id="dropZone">
                            <div class="drop-zone-content">
                                <div class="icon-circle">
                                    <i class="fas fa-cloud-upload-alt drop-zone-icon"></i>
                                </div>
                                <h3 class="drop-zone-text">Kéo và thả file của bạn vào đây</h3>
                                <p class="drop-zone-hint">hoặc click để duyệt file từ máy tính</p>
                                <div class="supported-formats">
                                    <span class="badge pdf">PDF</span>
                                    <span class="badge word">DOCX</span>
                                    <span class="badge txt">TXT</span>
                                    <span class="size-limit">Tối đa 10MB</span>
                                </div>
                            </div>
                            <input type="file" name="document" class="file-input-hidden" id="fileInputHidden" required
                                accept=".pdf,.doc,.docx,.txt">
                        </div>

                        <div class="selected-file" id="selectedFilePreview">
                            <div class="file-info-container">
                                <div class="file-icon-box" id="previewIconBox">
                                    <i class="fas fa-file-pdf file-icon" id="previewIcon"></i>
                                </div>
                                <div class="file-details">
                                    <h4 id="previewFileName">filename.pdf</h4>
                                    <div class="file-meta">
                                        <span id="previewFileSize">2.5 MB</span>
                                        <span class="status-badge pending" id="uploadStatusBadge">Sẵn sàng tải
                                            lên</span>
                                    </div>

                                    <div class="progress-container" id="ddProgressContainer">
                                        <div class="progress-bar-bg">
                                            <div class="progress-bar-fill" id="ddProgressBar"></div>
                                        </div>
                                        <span class="progress-text" id="ddProgressText">0%</span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="remove-file" onclick="removeFile('dragdrop')" title="Xóa file"
                                id="ddRemoveBtn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <button type="submit" class="btn-upload" id="btnUploadSubmit">
                            <i class="fas fa-rocket"></i> Bắt đầu tải lên
                        </button>
                    </form>
                </div>

                <!-- FORM UPLOAD SECTION -->
                <div id="form-section" class="upload-section">
                    <div class="form-upload-area">
                        <form action="../backend/upload_document.php" method="POST" enctype="multipart/form-data"
                            id="classicForm">
                            <input type="hidden" name="upload_type" value="form">

                            <div class="form-group">
                                <label class="form-label" for="doc_title">
                                    <i class="fas fa-heading"></i> Tên tài liệu <span class="optional">(Tùy chọn)</span>
                                </label>
                                <input type="text" id="doc_title" name="doc_title" class="input-modern"
                                    placeholder="Nhập tên tài liệu gợi nhớ...">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="document_file">
                                    <i class="fas fa-file-upload"></i> Chọn file tài liệu
                                </label>
                                <div class="file-input-wrapper">
                                    <button type="button" class="btn-choose-file"
                                        onclick="document.getElementById('document_file').click()">
                                        <i class="fas fa-folder-open"></i> Duyệt file
                                    </button>
                                    <span class="file-name-display" id="classicFileName">Chưa có file nào được
                                        chọn</span>
                                    <input type="file" id="document_file" name="document" class="file-input-classic"
                                        required accept=".pdf,.doc,.docx,.txt" style="display: none;">
                                </div>
                                <div class="form-hints">
                                    <span><i class="fas fa-info-circle"></i> Định dạng: PDF, DOCX, TXT</span>
                                    <span><i class="fas fa-hdd"></i> Tối đa: 10MB</span>
                                </div>
                            </div>

                            <div class="progress-container form-progress" id="formProgressContainer">
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" id="formProgressBar"></div>
                                </div>
                                <div class="progress-stats">
                                    <span class="status-text" id="formStatusText">Đang tải lên...</span>
                                    <span class="progress-text" id="formProgressText">0%</span>
                                </div>
                            </div>

                            <button type="submit" class="btn-upload" id="btnClassicSubmit" disabled>
                                <i class="fas fa-paper-plane"></i> Tải lên ngay
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Set up tab switching
        function switchTab(tabId) {
            document.querySelectorAll('.upload-section').forEach(section => {
                section.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            document.getElementById(tabId + '-section').classList.add('active');
            event.currentTarget.classList.add('active');
        }

        // Configuration
        const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
        const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'txt'];

        // --- Drag & Drop Logic ---
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInputHidden');
        const selectedFilePreview = document.getElementById('selectedFilePreview');
        const previewFileName = document.getElementById('previewFileName');
        const previewFileSize = document.getElementById('previewFileSize');
        const previewIcon = document.getElementById('previewIcon');
        const previewIconBox = document.getElementById('previewIconBox');
        const btnUploadSubmit = document.getElementById('btnUploadSubmit');
        const dragDropForm = document.getElementById('dragDropForm');

        const ddProgressContainer = document.getElementById('ddProgressContainer');
        const ddProgressBar = document.getElementById('ddProgressBar');
        const ddProgressText = document.getElementById('ddProgressText');
        const uploadStatusBadge = document.getElementById('uploadStatusBadge');
        const ddRemoveBtn = document.getElementById('ddRemoveBtn');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight drop zone
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) { dropZone.classList.add('dragover'); }
        function unhighlight(e) { dropZone.classList.remove('dragover'); }

        dropZone.addEventListener('drop', handleDrop, false);
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files, 'dragdrop');
        }

        fileInput.addEventListener('change', function () { handleFiles(this.files, 'dragdrop'); });

        // --- Classic Form Logic ---
        const classicFileInput = document.getElementById('document_file');
        const classicFileNameDisplay = document.getElementById('classicFileName');
        const btnClassicSubmit = document.getElementById('btnClassicSubmit');
        const classicForm = document.getElementById('classicForm');

        const formProgressContainer = document.getElementById('formProgressContainer');
        const formProgressBar = document.getElementById('formProgressBar');
        const formProgressText = document.getElementById('formProgressText');
        const formStatusText = document.getElementById('formStatusText');

        classicFileInput.addEventListener('change', function () { handleFiles(this.files, 'form'); });

        // --- General File Handling ---
        function handleFiles(files, type) {
            if (files.length === 0) return;
            const file = files[0];

            // Validate File
            const ext = file.name.split('.').pop().toLowerCase();
            if (!ALLOWED_EXTENSIONS.includes(ext)) {
                showToast('Lỗi', 'Định dạng file không hợp lệ! Vui lòng chọn PDF, DOCX hoặc TXT.', 'error');
                removeFile(type);
                return;
            }
            if (file.size > MAX_FILE_SIZE) {
                showToast('Lỗi', 'Kích thước file vượt quá 10MB!', 'error');
                removeFile(type);
                return;
            }

            if (type === 'dragdrop') {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;

                previewFileName.textContent = file.name;
                previewFileSize.textContent = formatBytes(file.size);

                updateFileIcon(ext, previewIcon, previewIconBox);

                selectedFilePreview.classList.add('active');
                btnUploadSubmit.classList.add('show');
                dropZone.style.display = 'none';

                // Reset progress
                ddProgressContainer.style.display = 'none';
                uploadStatusBadge.textContent = 'Sẵn sàng tải lên';
                uploadStatusBadge.className = 'status-badge pending';
                ddRemoveBtn.style.display = 'flex';

            } else if (type === 'form') {
                classicFileNameDisplay.textContent = file.name;
                classicFileNameDisplay.classList.add('has-file');
                btnClassicSubmit.removeAttribute('disabled');

                // Reset progress
                formProgressContainer.style.display = 'none';
            }
        }

        function updateFileIcon(ext, iconEl, boxEl) {
            iconEl.className = 'fas file-icon';
            boxEl.className = 'file-icon-box';

            if (ext === 'pdf') {
                iconEl.classList.add('fa-file-pdf');
                boxEl.classList.add('pdf');
            } else if (['doc', 'docx'].includes(ext)) {
                iconEl.classList.add('fa-file-word');
                boxEl.classList.add('word');
            } else if (ext === 'txt') {
                iconEl.classList.add('fa-file-alt');
                boxEl.classList.add('txt');
            } else {
                iconEl.classList.add('fa-file');
            }
        }

        function removeFile(type) {
            if (type === 'dragdrop') {
                fileInput.value = '';
                selectedFilePreview.classList.remove('active');
                btnUploadSubmit.classList.remove('show');
                dropZone.style.display = 'block';
            } else if (type === 'form') {
                classicFileInput.value = '';
                classicFileNameDisplay.textContent = 'Chưa có file nào được chọn';
                classicFileNameDisplay.classList.remove('has-file');
                btnClassicSubmit.setAttribute('disabled', 'true');
            }
        }

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        // --- AJAX Upload Handling ---
        function handleAjaxUpload(e, type) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();

            let pContainer, pBar, pText, submitBtn;

            if (type === 'dragdrop') {
                pContainer = ddProgressContainer;
                pBar = ddProgressBar;
                pText = ddProgressText;
                submitBtn = btnUploadSubmit;
                uploadStatusBadge.textContent = 'Đang tải...';
                uploadStatusBadge.className = 'status-badge uploading';
                ddRemoveBtn.style.display = 'none';
            } else {
                pContainer = formProgressContainer;
                pBar = formProgressBar;
                pText = formProgressText;
                submitBtn = btnClassicSubmit;
                formStatusText.textContent = 'Đang tải lên...';
            }

            pContainer.style.display = 'block';
            submitBtn.setAttribute('disabled', 'true');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    pBar.style.width = percentComplete + '%';
                    pText.textContent = percentComplete + '%';
                }
            }, false);

            xhr.onload = function () {
                if (xhr.status === 200) {
                    // Usually the backend redirects. If it's a standard PHP upload that doesn't return JSON but redirects, 
                    // AJAX might follow the redirect and load the whole HTML.
                    // Let's emulate a success response for smooth UX if it redirects to success URL
                    if (xhr.responseURL && xhr.responseURL.includes('success=')) {
                        const urlParams = new URLSearchParams(xhr.responseURL.split('?')[1]);
                        showToast('Thành công', urlParams.get('success') || 'Tải lên thành công!', 'success');
                        setTimeout(() => window.location.href = xhr.responseURL, 1500);
                    } else if (xhr.responseURL && xhr.responseURL.includes('error=')) {
                        const urlParams = new URLSearchParams(xhr.responseURL.split('?')[1]);
                        showToast('Lỗi', urlParams.get('error') || 'Có lỗi xảy ra!', 'error');
                        resetUploadUI(type, submitBtn);
                    } else {
                        // Fallback fallback if backend doesn't redirect as expected
                        showToast('Hoàn tất', 'Yêu cầu đã được xử lý.', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } else {
                    showToast('Lỗi', 'Không thể kết nối đến máy chủ.', 'error');
                    resetUploadUI(type, submitBtn);
                }
            };

            xhr.onerror = function () {
                showToast('Lỗi', 'Lỗi mạng, vui lòng thử lại sau.', 'error');
                resetUploadUI(type, submitBtn);
            };

            xhr.open('POST', form.action, true);
            xhr.send(formData);
        }

        function resetUploadUI(type, btn) {
            btn.removeAttribute('disabled');
            if (type === 'dragdrop') {
                btn.innerHTML = '<i class="fas fa-rocket"></i> Bắt đầu tải lên';
                uploadStatusBadge.textContent = 'Lỗi tải lên';
                uploadStatusBadge.className = 'status-badge error';
                ddRemoveBtn.style.display = 'flex';
            } else {
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Tải lên ngay';
                formStatusText.textContent = 'Thất bại';
                formStatusText.style.color = '#ef4444';
            }
        }

        dragDropForm.addEventListener('submit', (e) => handleAjaxUpload(e, 'dragdrop'));
        classicForm.addEventListener('submit', (e) => handleAjaxUpload(e, 'form'));

        // --- Toast Notification ---
        function showToast(title, message, type) {
            const container = document.getElementById('alertContainer');
            const alertHtml = `
                <div class="alert alert-${type}" style="animation: fadeInDown 0.5s ease forwards;">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> 
                    <span><strong>${title}:</strong> ${message}</span>
                </div>
            `;
            container.innerHTML = alertHtml;
        }
    </script>
</body>

</html>