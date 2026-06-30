<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

$documentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($documentId <= 0) {
    header('Location: documents.php?error=' . urlencode('ID tài liệu không hợp lệ'));
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'user';

// Fetch current document details
$stmt = $conn->prepare("SELECT * FROM documents WHERE document_id = ?");
if (!$stmt) {
    die("Lỗi hệ thống: Prepare failed");
}
$stmt->bind_param("i", $documentId);
$stmt->execute();
$docResult = $stmt->get_result();

if ($docResult->num_rows === 0) {
    $stmt->close();
    header('Location: documents.php?error=' . urlencode('Tài liệu không tồn tại'));
    exit;
}

$doc = $docResult->fetch_assoc();
$stmt->close();

// Permission check: only owner or admin
if ($doc['user_id'] !== $userId && $userRole !== 'admin') {
    header('Location: documents.php?error=' . urlencode('Bạn không có quyền chỉnh sửa tài liệu này'));
    exit;
}

// Fetch categories for dropdown
$categories = [];
$catResult = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_id ASC");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch subjects for dropdown
$subjects = [];
$subResult = $conn->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_id ASC");
if ($subResult) {
    while ($row = $subResult->fetch_assoc()) {
        $subjects[] = $row;
    }
}

$fileType = strtoupper($doc['file_type']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa tài liệu - AI Study Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/home.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --secondary: #ec4899;
            --dark: #0f172a;
            --gray: #64748b;
            --light: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
            --shadow: 0 10px 30px rgba(0, 0, 0, .04);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--light);
            color: var(--dark);
            min-height: 100vh;
        }

        /* Glassmorphism Header */
        .page-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        }

        .page-header .logo {
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-header .logo i {
            color: var(--primary);
            -webkit-text-fill-color: initial;
        }

        .page-header .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header .header-actions .user-greeting {
            color: var(--dark);
            font-size: 0.9rem;
            margin-right: 12px;
            opacity: 0.9;
        }

        .page-header .header-actions a {
            color: var(--gray);
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .page-header .header-actions a:hover {
            color: var(--primary);
            background: rgba(99, 102, 241, 0.06);
        }

        .page-header .header-actions a.btn-upload-nav {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .page-header .header-actions a.btn-upload-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.25);
        }

        .page-header .header-actions a.btn-logout-nav {
            border: 1px solid var(--border);
        }

        .page-header .header-actions a.btn-logout-nav:hover {
            color: #ef4444;
            background: #fee2e2;
            border-color: #fecaca;
        }

        /* Breadcrumbs */
        .breadcrumb-bar {
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--gray);
        }

        .breadcrumb-bar a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .breadcrumb-bar a:hover {
            text-decoration: underline;
            color: var(--primary-hover);
        }

        /* Page Wrapper */
        .edit-page-wrapper {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 24px 60px;
        }

        .edit-page-title {
            text-align: center;
            margin-bottom: 32px;
        }

        .edit-page-title h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .edit-page-title p {
            color: var(--gray);
            font-size: 0.95rem;
        }

        .edit-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        /* File Info Static Box */
        .file-info-static {
            background: var(--light);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px 22px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .file-icon-badge {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .file-icon-badge.pdf { background: #fee2e2; color: #dc2626; }
        .file-icon-badge.docx { background: #dbeafe; color: #1d4ed8; }
        .file-icon-badge.pptx { background: #fef3c7; color: #b45309; }

        .file-details-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .file-details-text .filename {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--dark);
            word-break: break-all;
        }

        .file-details-text .meta {
            font-size: 0.8rem;
            color: var(--gray);
            font-weight: 500;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .form-group label .required {
            color: #ef4444;
            margin-left: 3px;
        }

        .form-control {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            color: var(--dark);
            font-family: inherit;
            background: white;
            transition: var(--transition);
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 44px;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 35px;
        }

        .btn-submit {
            flex: 2;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, .3);
        }

        .btn-cancel {
            flex: 1;
            padding: 14px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            background: white;
            color: var(--dark);
            font-size: 0.95rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-cancel:hover {
            background: var(--light);
            color: var(--primary);
            border-color: var(--primary);
        }

        /* Alert Boxes */
        .alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert.success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert.error {
            background-color: #fdf2f2;
            color: #9b1c1c;
            border: 1px solid #fde8e8;
        }

        @media (max-width: 600px) {
            .edit-card {
                padding: 24px 20px;
            }
            .form-actions {
                flex-direction: column;
            }
            .page-header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            .page-header .header-actions {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 8px;
            }
            .page-header .header-actions .user-greeting {
                width: 100%;
                margin-bottom: 5px;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <header class="page-header">
        <a href="dashboard.php" class="logo">
            <i class="fas fa-brain"></i> AI Study Hub
        </a>
        <div class="header-actions">
            <span class="user-greeting">Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
            <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="documents.php"><i class="fas fa-folder-open"></i> Tài liệu của tôi</a>
            <a href="upload.php" class="btn-upload-nav"><i class="fas fa-upload"></i> Upload</a>
            <a href="../backend/logout.php" class="btn-logout-nav"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </header>

    <nav class="breadcrumb-bar">
        <a href="dashboard.php">Dashboard</a>
        <i class="fas fa-chevron-right"></i>
        <a href="documents.php">Tài liệu của tôi</a>
        <i class="fas fa-chevron-right"></i>
        <a href="document_detail.php?id=<?php echo $doc['document_id']; ?>">Chi tiết tài liệu</a>
        <i class="fas fa-chevron-right"></i>
        <span>Chỉnh sửa</span>
    </nav>

    <main class="edit-page-wrapper">
        <div class="edit-page-title">
            <h1><i class="fas fa-edit" style="color: var(--primary);"></i> Chỉnh sửa thông tin tài liệu</h1>
            <p>Cập nhật tiêu đề, mô tả và cài đặt bảo mật cho tài liệu học tập của bạn</p>
        </div>

        <div class="edit-card">
            <!-- File Info (Read-only) -->
            <div class="file-info-static">
                <div class="file-icon-badge <?php echo strtolower($fileType); ?>">
                    <i class="fas <?php echo $fileType === 'PDF' ? 'fa-file-pdf' : ($fileType === 'DOCX' ? 'fa-file-word' : 'fa-file-powerpoint'); ?>"></i>
                </div>
                <div class="file-details-text">
                    <span class="filename"><?php echo htmlspecialchars($doc['original_name']); ?></span>
                    <span class="meta">Định dạng: <?php echo $fileType; ?> | Kích thước: <?php echo round($doc['file_size'] / (1024 * 1024), 2); ?> MB</span>
                </div>
            </div>

            <!-- Error/Success Alert Box -->
            <div id="alert-box" class="alert">
                <i id="alert-icon" class="fas"></i>
                <span id="alert-text"></span>
            </div>

            <form id="edit-form" novalidate>
                <input type="hidden" name="document_id" value="<?php echo $doc['document_id']; ?>">

                <!-- Title -->
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
                        value="<?php echo htmlspecialchars($doc['title']); ?>"
                        required
                    >
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="doc-description">Mô tả ngắn</label>
                    <textarea
                        id="doc-description"
                        name="description"
                        class="form-control"
                        placeholder="Mô tả tóm tắt nội dung tài liệu..."
                    ><?php echo htmlspecialchars($doc['description'] ?? ''); ?></textarea>
                </div>

                <!-- Category -->
                <div class="form-group">
                    <label for="doc-category">Danh mục</label>
                    <select id="doc-category" name="category_id" class="form-control">
                        <option value="">— Chọn danh mục —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo $doc['category_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Subject -->
                <div class="form-group">
                    <label for="doc-subject">Môn học</label>
                    <select id="doc-subject" name="subject_id" class="form-control">
                        <option value="">— Chọn môn học —</option>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?php echo $sub['subject_id']; ?>" <?php echo $doc['subject_id'] == $sub['subject_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sub['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Visibility -->
                <div class="form-group">
                    <label for="doc-visibility">Quyền riêng tư</label>
                    <select id="doc-visibility" name="visibility" class="form-control">
                        <option value="public" <?php echo $doc['visibility'] === 'public' ? 'selected' : ''; ?>>Công khai (Mọi người đều xem được)</option>
                        <option value="private" <?php echo $doc['visibility'] === 'private' ? 'selected' : ''; ?>>Riêng tư (Chỉ mình tôi xem được)</option>
                        <option value="shared" <?php echo $doc['visibility'] === 'shared' ? 'selected' : ''; ?>>Chia sẻ (Có liên kết mới xem được)</option>
                    </select>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="document_detail.php?id=<?php echo $doc['document_id']; ?>" class="btn-cancel">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                    <button type="submit" class="btn-submit" id="btn-submit">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('edit-form');
            const alertBox = document.getElementById('alert-box');
            const alertText = document.getElementById('alert-text');
            const alertIcon = document.getElementById('alert-icon');
            const submitBtn = document.getElementById('btn-submit');

            function showAlert(message, type = 'error') {
                alertBox.className = `alert ${type}`;
                alertText.textContent = message;
                
                if (type === 'success') {
                    alertIcon.className = 'fas fa-check-circle';
                } else {
                    alertIcon.className = 'fas fa-exclamation-circle';
                }
                
                alertBox.style.display = 'flex';
                // Scroll to alert
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                // Validate client side
                const titleInput = document.getElementById('doc-title');
                if (titleInput.value.trim() === '') {
                    showAlert('Tiêu đề tài liệu không được để trống.');
                    titleInput.focus();
                    return;
                }

                // Prepare data
                const formData = new FormData(form);

                // Disable submit button and show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
                alertBox.style.display = 'none';

                try {
                    const response = await fetch('../backend/edit_document_process.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const data = await response.json();

                    if (data.success) {
                        showAlert(data.message, 'success');
                        setTimeout(() => {
                            window.location.href = `document_detail.php?id=<?php echo $doc['document_id']; ?>`;
                        }, 1500);
                    } else {
                        showAlert(data.error || 'Đã xảy ra lỗi không xác định.');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi';
                    }
                } catch (error) {
                    console.error('Error updating document:', error);
                    showAlert('Lỗi kết nối đến máy chủ. Vui lòng thử lại sau.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi';
                }
            });
        });
    </script>
</body>
</html>
