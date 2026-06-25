<?php
require_once '../includes/auth_check.php';
<<<<<<< HEAD

$success_msg = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error_msg = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="vi">

=======
?>
<!DOCTYPE html>
<html lang="vi">
>>>>>>> origin/van-fe
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tải lên tài liệu - AI Study Hub</title>
<<<<<<< HEAD
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
=======
    <meta name="description" content="Tải lên tài liệu học tập PDF, DOCX, PPTX lên hệ thống AI Study Hub.">
    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- CSS chung + CSS upload (của Vân) -->
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/upload.css">
    <style>
        /* =============================================
           LAYOUT TỔNG THỂ
           ============================================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--light);
            color: var(--dark);
            min-height: 100vh;
        }

        /* =============================================
           TOPBAR (đồng bộ với dashboard.php)
           ============================================= */
        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header .logo {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
        }

        .page-header .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header .header-actions span {
            opacity: .9;
            font-size: 0.9375rem;
        }

        .page-header .header-actions a {
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(255, 255, 255, .2);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: .3s ease;
        }

        .page-header .header-actions a:hover {
            background: rgba(255, 255, 255, .35);
        }

        /* =============================================
           BREADCRUMB
           ============================================= */
        .breadcrumb-bar {
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 12px 25px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            color: var(--gray);
        }

        .breadcrumb-bar a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb-bar a:hover {
            text-decoration: underline;
        }

        .breadcrumb-bar i {
            font-size: 0.75rem;
        }

        /* =============================================
           MAIN CONTENT
           ============================================= */
        .upload-page-wrapper {
            max-width: 760px;
            margin: 40px auto;
            padding: 0 20px 60px;
        }

        .upload-page-title {
            text-align: center;
            margin-bottom: 32px;
        }

        .upload-page-title h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .upload-page-title p {
            color: var(--gray);
            font-size: 0.9375rem;
        }

        /* =============================================
           UPLOAD CARD
           ============================================= */
        .upload-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
        }

        /* =============================================
           KHU VỰC DRAG & DROP
           ============================================= */
        .drop-zone {
            border: 2px dashed var(--border);
            border-radius: 14px;
            padding: 48px 24px;
            text-align: center;
            cursor: pointer;
            transition: .3s ease;
            background: var(--light);
            position: relative;
            margin-bottom: 28px;
        }

        .drop-zone:hover,
        .drop-zone.drag-over {
            border-color: var(--primary);
            background: #eef2ff;
        }

        .drop-zone.drag-over .drop-zone-icon {
            transform: scale(1.15);
        }

        .drop-zone-icon {
            font-size: 3rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 16px;
            display: block;
            transition: .3s ease;
        }

        .drop-zone h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .drop-zone p {
            color: var(--gray);
            font-size: 0.875rem;
            margin-bottom: 18px;
        }

        .drop-zone-btn {
            display: inline-block;
            padding: 10px 22px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: .3s ease;
            border: none;
        }

        .drop-zone-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(99, 102, 241, .35);
        }

        /* Badge hiển thị tên file đã chọn */
        .selected-file-badge {
            display: none;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            padding: 10px 16px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 10px;
            font-size: 0.875rem;
            color: var(--primary);
            font-weight: 500;
            text-align: left;
            word-break: break-all;
        }

        .selected-file-badge i {
            flex-shrink: 0;
            font-size: 1.1rem;
        }

        /* Input file ẩn — JS và label sẽ trigger */
        #file-input {
            display: none;
        }

        /* =============================================
           FORM FIELDS
           ============================================= */
        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .form-group label .required {
            color: var(--danger, #ef4444);
            margin-left: 3px;
        }

        .form-control {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 0.9375rem;
            color: var(--dark);
            font-family: inherit;
            background: white;
            transition: .3s ease;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 44px;
            cursor: pointer;
        }

        /* =============================================
           NÚT SUBMIT
           ============================================= */
        .btn-submit {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: .3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
            letter-spacing: .01em;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, .4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit i {
            font-size: 1.1rem;
        }

        /* =============================================
           DIVIDER
           ============================================= */
        .form-divider {
            height: 1px;
            background: var(--border);
            margin: 28px 0;
        }

        /* =============================================
           CHÚ THÍCH LOẠI FILE
           ============================================= */
        .file-hint {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .file-hint-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .file-hint-tag.pdf  { background: #fee2e2; color: #dc2626; }
        .file-hint-tag.docx { background: #dbeafe; color: #1d4ed8; }
        .file-hint-tag.pptx { background: #fef3c7; color: #b45309; }

        /* =============================================
           RESPONSIVE
           ============================================= */
        @media (max-width: 600px) {
            .upload-card {
                padding: 24px 18px;
            }

            .upload-page-title h1 {
                font-size: 1.5rem;
            }

            .drop-zone {
                padding: 32px 16px;
            }
        }
    </style>
</head>
<body>

    <!-- ================================================
         TOPBAR
         ================================================ -->
    <header class="page-header">
        <div class="logo">
            <i class="fas fa-brain"></i> AI Study Hub
        </div>
        <div class="header-actions">
            <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
            <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </header>

    <!-- ================================================
         BREADCRUMB
         ================================================ -->
    <nav class="breadcrumb-bar" aria-label="Breadcrumb">
        <a href="dashboard.php">Dashboard</a>
        <i class="fas fa-chevron-right"></i>
        <span>Tải lên tài liệu</span>
    </nav>

    <!-- ================================================
         NỘI DUNG CHÍNH
         ================================================ -->
    <main class="upload-page-wrapper">

        <div class="upload-page-title">
            <h1><i class="fas fa-cloud-upload-alt" style="background:linear-gradient(135deg,var(--primary),var(--secondary));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i> Tải lên tài liệu</h1>
            <p>Chia sẻ tài liệu học tập với cộng đồng AI Study Hub</p>
        </div>

        <div class="upload-card">

            <!-- ============================================
                 FORM UPLOAD
                 id="upload-form" — DOM contract với upload-status.js (Vân)
                 ============================================ -->
            <form id="upload-form" enctype="multipart/form-data" novalidate>

                <!-- KHU VỰC DRAG & DROP -->
                <div class="drop-zone" id="drop-zone">
                    <span class="drop-zone-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </span>
                    <h3>Kéo & thả file vào đây</h3>
                    <p>hoặc nhấn nút bên dưới để chọn file từ máy tính</p>

                    <!-- label đóng vai trò nút bấm, trigger #file-input -->
                    <label for="file-input" class="drop-zone-btn">
                        <i class="fas fa-folder-open"></i> Chọn file
                    </label>

                    <!-- input file ẩn — name="file" đúng API contract -->
                    <input
                        type="file"
                        id="file-input"
                        name="file"
                        accept=".pdf,.docx,.pptx"
                    >

                    <!-- Badge tên file sau khi chọn -->
                    <div class="selected-file-badge" id="selected-file-badge">
                        <i class="fas fa-file-alt"></i>
                        <span id="selected-file-name">—</span>
                    </div>
                </div>

                <!-- Gợi ý loại file cho phép -->
                <div class="file-hint">
                    <span class="file-hint-tag pdf"><i class="fas fa-file-pdf"></i> PDF</span>
                    <span class="file-hint-tag docx"><i class="fas fa-file-word"></i> DOCX</span>
                    <span class="file-hint-tag pptx"><i class="fas fa-file-powerpoint"></i> PPTX</span>
                    <small style="color:var(--gray);margin-left:4px;align-self:center;">Tối đa 50 MB</small>
                </div>

                <div class="form-divider"></div>

                <!-- TIÊU ĐỀ TÀI LIỆU -->
                <div class="form-group">
                    <label for="doc-title">
                        Tiêu đề tài liệu <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="doc-title"
                        name="title"
                        class="form-control"
                        placeholder="Nhập tiêu đề tài liệu..."
                        required
                    >
                </div>

                <!-- MÔ TẢ -->
                <div class="form-group">
                    <label for="doc-description">Mô tả</label>
                    <textarea
                        id="doc-description"
                        name="description"
                        class="form-control"
                        placeholder="Mô tả ngắn về nội dung tài liệu..."
                    ></textarea>
                </div>

                <!-- DANH MỤC -->
                <div class="form-group">
                    <label for="doc-category">
                        Danh mục <span class="required">*</span>
                    </label>
                    <select
                        id="doc-category"
                        name="category_id"
                        class="form-control"
                        required
                    >
                        <option value="">— Chọn danh mục —</option>
                        <option value="1">Toán học</option>
                        <option value="2">Vật lý</option>
                        <option value="3">Hóa học</option>
                        <option value="4">Lập trình</option>
                        <option value="5">Ngoại ngữ</option>
                        <option value="6">Kinh tế</option>
                        <option value="7">Khác</option>
                    </select>
                </div>

                <!-- NÚT SUBMIT -->
                <button type="submit" class="btn-submit" id="btn-submit">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Tải lên ngay
                </button>

                <!-- ============================================
                     VÙNG PROGRESS + STATUS + KẾT QUẢ
                     (DOM contract với upload-status.js của Vân)
                     JS sẽ tự render nội dung bên trong các div này
                     ============================================ -->

                <!-- Thanh tiến trình upload — JS render nội dung bên trong -->
                <div id="upload-progress-container"></div>

                <!-- Text trạng thái: "Đang upload...", "Đang xử lý...", "Hoàn tất" -->
                <div id="upload-status-text"></div>

                <!-- Thông báo kết quả cuối (thành công / thất bại) -->
                <div id="upload-result-message"></div>

            </form>
            <!-- /#upload-form -->

        </div>
        <!-- /.upload-card -->

    </main>

    <!-- ================================================
         JAVASCRIPT
         upload-status.js (của Vân) xử lý XHR, progress bar,
         status UI và thông báo kết quả
         ================================================ -->
    <script src="../assets/js/upload-status.js" defer></script>
    <script>
        /* ==============================================
           DRAG & DROP + PREVIEW TÊN FILE
           Logic UI cho drop-zone, tách riêng khỏi
           upload-status.js để không lẫn trách nhiệm
           ============================================== */
        (function () {
            var dropZone        = document.getElementById('drop-zone');
            var fileInput       = document.getElementById('file-input');
            var selectedBadge   = document.getElementById('selected-file-badge');
            var selectedName    = document.getElementById('selected-file-name');

            /* Hiện tên file sau khi chọn */
            function showSelectedFile(file) {
                if (!file) return;
                selectedName.textContent = file.name;
                selectedBadge.style.display = 'flex';
            }

            /* Lắng nghe sự kiện chọn file qua input */
            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    showSelectedFile(this.files[0]);
                }
            });

            /* Drag over — thêm class để hiện highlight */
            dropZone.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });

            /* Drag leave — bỏ highlight */
            dropZone.addEventListener('dragleave', function (e) {
                if (!this.contains(e.relatedTarget)) {
                    this.classList.remove('drag-over');
                }
            });

            /* Drop file vào zone */
            dropZone.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                var files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    /* Gán file vào input để upload-status.js đọc được */
                    var dataTransfer = new DataTransfer();
                    dataTransfer.items.add(files[0]);
                    fileInput.files = dataTransfer.files;

                    showSelectedFile(files[0]);
                }
            });
        })();
    </script>

</body>
</html>
>>>>>>> origin/van-fe
