<?php
require_once '../includes/auth_check.php';

$success_msg = isset($_GET['success']) ? htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8') : '';
$error_msg = isset($_GET['error']) ? htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tải lên tài liệu học tập PDF, DOCX, PPTX lên hệ thống AI Study Hub.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/upload.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--light);
            color: var(--dark);
            min-height: 100vh;
        }
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
        .breadcrumb-bar a:hover { text-decoration: underline; }
        .breadcrumb-bar i { font-size: 0.75rem; }
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
        .upload-page-title p { color: var(--gray); font-size: 0.9375rem; }
        .upload-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
        }
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
        .drop-zone:hover, .drop-zone.drag-over {
            border-color: var(--primary);
            background: #eef2ff;
        }
        .drop-zone.drag-over .drop-zone-icon { transform: scale(1.15); }
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
        .drop-zone p { color: var(--gray); font-size: 0.875rem; margin-bottom: 18px; }
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
        #file-input { display: none; }
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
        .selected-file-badge i { flex-shrink: 0; font-size: 1.1rem; }
        .form-group { margin-bottom: 22px; }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark);
            margin-bottom: 8px;
        }
        .form-group label .required { color: var(--danger, #ef4444); margin-left: 3px; }
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
        textarea.form-control { resize: vertical; min-height: 100px; }
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 44px;
            cursor: pointer;
        }
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
        .btn-submit i { font-size: 1.1rem; }
        .form-divider { height: 1px; background: var(--border); margin: 28px 0; }
        .file-hint { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
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
        @media (max-width: 600px) {
            .upload-card { padding: 24px 18px; }
            .upload-page-title h1 { font-size: 1.5rem; }
            .drop-zone { padding: 32px 16px; }
        }
    </style>
</head>
<body>

    <header class="page-header">
        <div class="logo">
            <i class="fas fa-brain"></i> AI Study Hub
        </div>
        <div class="header-actions">
            <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
            <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </header>

    <nav class="breadcrumb-bar" aria-label="Breadcrumb">
        <a href="dashboard.php">Dashboard</a>
        <i class="fas fa-chevron-right"></i>
        <span>Tải lên tài liệu</span>
    </nav>

    <main class="upload-page-wrapper">

        <div class="upload-page-title">
            <h1><i class="fas fa-cloud-upload-alt" style="background:linear-gradient(135deg,var(--primary),var(--secondary));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i> Tải lên tài liệu</h1>
            <p>Chia sẻ tài liệu học tập với cộng đồng AI Study Hub</p>
        </div>

        <div class="upload-card">

            <?php if ($success_msg): ?>
                <div class="alert success" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:14px 20px;border-radius:12px;margin-bottom:24px;">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert error" style="background:#fdf2f2;color:#9b1c1c;border:1px solid #fde8e8;padding:14px 20px;border-radius:12px;margin-bottom:24px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <form id="upload-form" enctype="multipart/form-data" novalidate>

                <div class="drop-zone" id="drop-zone">
                    <span class="drop-zone-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </span>
                    <h3>Kéo & thả file vào đây</h3>
                    <p>hoặc nhấn nút bên dưới để chọn file từ máy tính</p>

                    <label for="file-input" class="drop-zone-btn">
                        <i class="fas fa-folder-open"></i> Chọn file
                    </label>

                    <input
                        type="file"
                        id="file-input"
                        name="file"
                        accept=".pdf,.docx,.pptx"
                    >

                    <div class="selected-file-badge" id="selected-file-badge">
                        <i class="fas fa-file-alt"></i>
                        <span id="selected-file-name">—</span>
                    </div>
                </div>

                <div class="file-hint">
                    <span class="file-hint-tag pdf"><i class="fas fa-file-pdf"></i> PDF</span>
                    <span class="file-hint-tag docx"><i class="fas fa-file-word"></i> DOCX</span>
                    <span class="file-hint-tag pptx"><i class="fas fa-file-powerpoint"></i> PPTX</span>
                    <small style="color:var(--gray);margin-left:4px;align-self:center;">Tối đa 50 MB</small>
                </div>

                <div class="form-divider"></div>

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

                <div class="form-group">
                    <label for="doc-description">Mô tả</label>
                    <textarea
                        id="doc-description"
                        name="description"
                        class="form-control"
                        placeholder="Mô tả ngắn về nội dung tài liệu..."
                    ></textarea>
                </div>

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

                <button type="submit" class="btn-submit" id="btn-submit">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Tải lên ngay
                </button>

                <div id="upload-progress-container"></div>
                <div id="upload-status-text"></div>
                <div id="upload-result-message"></div>

            </form>

        </div>

    </main>

    <script src="../assets/js/upload-status.js" defer></script>
    <script>
        (function () {
            var dropZone        = document.getElementById('drop-zone');
            var fileInput       = document.getElementById('file-input');
            var selectedBadge   = document.getElementById('selected-file-badge');
            var selectedName    = document.getElementById('selected-file-name');

            function showSelectedFile(file) {
                if (!file) return;
                selectedName.textContent = file.name;
                selectedBadge.style.display = 'flex';
            }

            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    showSelectedFile(this.files[0]);
                }
            });

            dropZone.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });

            dropZone.addEventListener('dragleave', function (e) {
                if (!this.contains(e.relatedTarget)) {
                    this.classList.remove('drag-over');
                }
            });

            dropZone.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                var files = e.dataTransfer.files;
                if (files && files.length > 0) {
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
