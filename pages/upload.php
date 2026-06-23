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
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/upload.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary, #4f46e5), var(--secondary, #3b82f6));
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dashboard-header .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .dashboard-header a {
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(255,255,255,0.2);
            transition: 0.3s;
            text-decoration: none;
        }
        .dashboard-header a:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <div class="logo">
            <a href="dashboard.php" style="color:white; text-decoration:none; background:transparent; padding:0;">
                <i class="fas fa-brain"></i> AI Study Hub
            </a>
        </div>
        <div class="user-info">
            <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
            <a href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
            <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </header>

    <div class="upload-wrapper">
        <div class="container">
            <div class="upload-container">
                <div class="upload-header">
                    <h2>Tải lên tài liệu mới</h2>
                    <p>Chia sẻ và lưu trữ tài liệu học tập của bạn an toàn trên hệ thống.</p>
                </div>

                <?php if ($success_msg): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_msg): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="upload-tabs">
                    <button class="tab-btn active" onclick="switchTab('dragdrop')">
                        <i class="fas fa-cloud-upload-alt"></i> Kéo & Thả (Drag & Drop)
                    </button>
                    <button class="tab-btn" onclick="switchTab('form')">
                        <i class="fas fa-file-alt"></i> Form Truyền thống
                    </button>
                </div>

                <!-- DRAG AND DROP SECTION -->
                <div id="dragdrop-section" class="upload-section active">
                    <form action="../backend/upload_document.php" method="POST" enctype="multipart/form-data" id="dragDropForm">
                        <input type="hidden" name="upload_type" value="dragdrop">
                        
                        <div class="drop-zone" id="dropZone">
                            <i class="fas fa-cloud-upload-alt drop-zone-icon"></i>
                            <h3 class="drop-zone-text">Kéo và thả file của bạn vào đây</h3>
                            <p class="drop-zone-hint">hoặc click để chọn file từ máy tính</p>
                            <p class="drop-zone-hint" style="margin-top: 10px;">(Hỗ trợ: PDF, DOCX, TXT - Tối đa 10MB)</p>
                            <input type="file" name="document" class="file-input-hidden" id="fileInputHidden" required>
                        </div>

                        <div class="selected-file" id="selectedFilePreview">
                            <div class="file-info">
                                <i class="fas fa-file-pdf file-icon" id="previewIcon"></i>
                                <div class="file-details">
                                    <h4 id="previewFileName">filename.pdf</h4>
                                    <p id="previewFileSize">2.5 MB</p>
                                </div>
                            </div>
                            <button type="button" class="remove-file" onclick="removeFile()" title="Xóa file">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <button type="submit" class="btn-upload" id="btnUploadSubmit" style="margin-top: 20px; display: none;">
                            <i class="fas fa-upload"></i> Tải tài liệu lên
                        </button>
                    </form>
                </div>

                <!-- FORM UPLOAD SECTION -->
                <div id="form-section" class="upload-section">
                    <div class="form-upload-area">
                        <form action="../backend/upload_document.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="upload_type" value="form">
                            
                            <div class="form-group">
                                <label class="form-label" for="doc_title">Tên tài liệu (Tùy chọn)</label>
                                <input type="text" id="doc_title" name="doc_title" class="file-input-classic" placeholder="Nhập tên tài liệu...">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="document_file">Chọn file tài liệu</label>
                                <input type="file" id="document_file" name="document" class="file-input-classic" required>
                                <p class="drop-zone-hint" style="margin-top: 8px;">Định dạng: PDF, DOCX, TXT. Dung lượng tối đa: 10MB.</p>
                            </div>

                            <button type="submit" class="btn-upload">
                                <i class="fas fa-paper-plane"></i> Tải lên ngay
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Tab Switching Logic
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

        // Drag and Drop Logic
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInputHidden');
        const selectedFilePreview = document.getElementById('selectedFilePreview');
        const previewFileName = document.getElementById('previewFileName');
        const previewFileSize = document.getElementById('previewFileSize');
        const previewIcon = document.getElementById('previewIcon');
        const btnUploadSubmit = document.getElementById('btnUploadSubmit');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight drop zone when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('dragover');
        }

        function unhighlight(e) {
            dropZone.classList.remove('dragover');
        }

        // Handle dropped files
        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        // Handle selected files via click
        fileInput.addEventListener('change', function(e) {
            handleFiles(this.files);
        });

        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0]; // Only handle first file
                
                // Set file to input if it came from drag drop
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;

                // Update UI
                previewFileName.textContent = file.name;
                previewFileSize.textContent = formatBytes(file.size);
                
                // Update icon based on extension
                const ext = file.name.split('.').pop().toLowerCase();
                previewIcon.className = 'fas file-icon';
                if (ext === 'pdf') {
                    previewIcon.classList.add('fa-file-pdf');
                    previewIcon.style.color = '#ef4444';
                } else if (['doc', 'docx'].includes(ext)) {
                    previewIcon.classList.add('fa-file-word');
                    previewIcon.style.color = '#3b82f6';
                } else if (ext === 'txt') {
                    previewIcon.classList.add('fa-file-alt');
                    previewIcon.style.color = '#64748b';
                } else {
                    previewIcon.classList.add('fa-file');
                    previewIcon.style.color = '#8b5cf6';
                }

                selectedFilePreview.classList.add('active');
                btnUploadSubmit.style.display = 'inline-flex';
                dropZone.style.display = 'none';
            }
        }

        function removeFile() {
            fileInput.value = '';
            selectedFilePreview.classList.remove('active');
            btnUploadSubmit.style.display = 'none';
            dropZone.style.display = 'block';
        }

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>
