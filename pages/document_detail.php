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

// Query document details with joins
$stmt = $conn->prepare("
    SELECT d.*, u.full_name as uploader_name, c.category_name, s.subject_name
    FROM documents d
    JOIN users u ON d.user_id = u.user_id
    LEFT JOIN categories c ON d.category_id = c.category_id
    LEFT JOIN subjects s ON d.subject_id = s.subject_id
    WHERE d.document_id = ?
");

if (!$stmt) {
    die("Lỗi hệ thống: Prepare failed");
}

$stmt->bind_param("i", $documentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header('Location: documents.php?error=' . urlencode('Tài liệu không tồn tại'));
    exit;
}

$doc = $result->fetch_assoc();
$stmt->close();

// Permissions Check
if ($doc['visibility'] === 'private' && $doc['user_id'] !== $userId && $userRole !== 'admin') {
    header('Location: documents.php?error=' . urlencode('Bạn không có quyền xem tài liệu này'));
    exit;
}

if ($doc['status'] !== 'approved' && $doc['user_id'] !== $userId && $userRole !== 'admin') {
    header('Location: documents.php?error=' . urlencode('Tài liệu này chưa được phê duyệt công khai'));
    exit;
}

// Helpers
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

$fileType = strtoupper($doc['file_type']);
$fileSizeFormatted = formatBytes($doc['file_size']);
$joinDate = date('d/m/Y', strtotime($doc['created_at']));
$isOwnerOrAdmin = ($doc['user_id'] === $userId || $userRole === 'admin');

// Map visibility label and class
$visibilityLabels = [
    'public' => 'Công khai',
    'private' => 'Riêng tư',
    'shared' => 'Chia sẻ'
];
$visibilityClass = $doc['visibility'];

// Map status label and class
$statusLabels = [
    'pending' => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Từ chối'
];
$statusClass = $doc['status'];

// Construct file URL
$fileUrl = '../uploads/documents/' . htmlspecialchars($doc['file_name']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($doc['title']); ?> - AI Study Hub</title>
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

        /* Detail Container */
        .detail-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 24px 60px;
            display: grid;
            grid-template-columns: 8fr 4fr;
            gap: 30px;
        }

        .main-content-column {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .sidebar-column {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        /* Document Header Card */
        .doc-header-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .doc-icon-large {
            width: 68px;
            height: 68px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }

        .doc-icon-large.pdf { background: #fee2e2; color: #dc2626; }
        .doc-icon-large.docx { background: #dbeafe; color: #1d4ed8; }
        .doc-icon-large.pptx { background: #fef3c7; color: #b45309; }

        .doc-header-info h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .doc-description-text {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .doc-description-text strong {
            color: var(--dark);
        }

        /* Preview Area */
        .doc-preview-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            min-height: 450px;
            display: flex;
            flex-direction: column;
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .preview-header h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .preview-header h3 i {
            color: var(--primary);
        }

        .pdf-frame-wrapper {
            flex-grow: 1;
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--border);
            height: 600px;
        }

        .pdf-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .placeholder-preview {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 60px 24px;
            background: var(--light);
            border-radius: 10px;
            border: 1px dashed var(--border);
        }

        .placeholder-preview i {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .placeholder-preview.docx i { color: #1d4ed8; }
        .placeholder-preview.pptx i { color: #b45309; }

        .placeholder-preview h4 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .placeholder-preview p {
            font-size: 0.9rem;
            color: var(--gray);
            max-width: 440px;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        /* Sidebar Cards */
        .sidebar-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .sidebar-card h3 {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light);
        }

        .meta-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
        }

        .meta-label {
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .meta-label i {
            color: var(--primary);
            width: 16px;
            text-align: center;
        }

        .meta-value {
            font-weight: 600;
            color: var(--dark);
            text-align: right;
            max-width: 60%;
            word-break: break-word;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge.public { background: #eef2ff; color: #4338ca; }
        .badge.private { background: #f1f5f9; color: #475569; }
        .badge.shared { background: #fae8ff; color: #a21caf; }

        .badge.pending { background: #fffbeb; color: #b45309; border: 1px solid #fef3c7; }
        .badge.approved { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
        .badge.rejected { background: #fdf2f2; color: #9b1c1c; border: 1px solid #fde8e8; }

        /* Actions Card */
        .actions-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-action {
            width: 100%;
            padding: 13px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-download {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(99, 102, 241, .3);
        }

        .btn-ai-chat {
            background: #eef2ff;
            color: var(--primary);
            border: 1.5px solid #c7d2fe;
        }

        .btn-ai-chat:hover {
            background: #e0e7ff;
            transform: translateY(-1px);
        }

        .btn-sidebar-edit {
            background: #f0fdfa;
            color: #0d9488;
            border: 1px solid #ccfbf1;
        }

        .btn-sidebar-edit:hover {
            background: #ccfbf1;
        }

        .btn-sidebar-delete {
            background: #fff5f5;
            color: #e11d48;
            border: 1px solid #ffe4e6;
        }

        .btn-sidebar-delete:hover {
            background: #ffe4e6;
        }

        .btn-back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 0.9rem;
            color: var(--gray);
            text-decoration: none;
            font-weight: 600;
            margin-top: 8px;
            transition: var(--transition);
        }

        .btn-back-link:hover {
            color: var(--primary);
        }

        @media (max-width: 992px) {
            .detail-container {
                grid-template-columns: 1fr;
            }
            .sidebar-column {
                order: -1;
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
        <span>Chi tiết tài liệu</span>
    </nav>

    <main class="detail-container">
        <!-- Main Content Column -->
        <div class="main-content-column">
            <!-- Header Card -->
            <div class="doc-header-card">
                <div class="doc-icon-large <?php echo strtolower($fileType); ?>">
                    <i class="fas <?php echo $fileType === 'PDF' ? 'fa-file-pdf' : ($fileType === 'DOCX' ? 'fa-file-word' : 'fa-file-powerpoint'); ?>"></i>
                </div>
                <div class="doc-header-info">
                    <h1><?php echo htmlspecialchars($doc['title']); ?></h1>
                    <div class="doc-description-text">
                        <strong>Mô tả:</strong> <?php echo !empty($doc['description']) ? nl2br(htmlspecialchars($doc['description'])) : 'Không có mô tả nào cho tài liệu này.'; ?>
                    </div>
                </div>
            </div>

            <!-- Preview Card -->
            <div class="doc-preview-card">
                <div class="preview-header">
                    <h3><i class="fas fa-image"></i> Xem trước tài liệu</h3>
                    <span style="font-size: 0.85rem; color: var(--gray); font-weight: 600;"><?php echo $fileType; ?> Preview</span>
                </div>
                
                <?php if ($fileType === 'PDF'): ?>
                    <div class="pdf-frame-wrapper">
                        <!-- Use relative path to uploads directory for client access -->
                        <iframe src="<?php echo $fileUrl; ?>#toolbar=0" class="pdf-iframe"></iframe>
                    </div>
                <?php else: ?>
                    <div class="placeholder-preview <?php echo strtolower($fileType); ?>">
                        <i class="fas <?php echo $fileType === 'DOCX' ? 'fa-file-word' : 'fa-file-powerpoint'; ?>"></i>
                        <h4>Không hỗ trợ xem trước cho tệp <?php echo $fileType; ?></h4>
                        <p>Trình duyệt không thể nhúng trực tiếp tài liệu Word hoặc PowerPoint. Hãy tải xuống để xem nội dung đầy đủ một cách tốt nhất.</p>
                        <a href="../backend/download.php?id=<?php echo $doc['document_id']; ?>" class="btn-action btn-download" style="width: auto; padding: 12px 24px;">
                            <i class="fas fa-download"></i> Tải xuống ngay
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="sidebar-column">
            <!-- Action Cards -->
            <div class="actions-card">
                <a href="../backend/download.php?id=<?php echo $doc['document_id']; ?>" class="btn-action btn-download">
                    <i class="fas fa-download"></i> Tải xuống (<?php echo $fileSizeFormatted; ?>)
                </a>
                
                <!-- AI Chat Button -->
                <button onclick="askAIChatbot(<?php echo $doc['document_id']; ?>)" class="btn-action btn-ai-chat">
                    <i class="fas fa-robot"></i> Hỏi đáp AI về tài liệu
                </button>
                
                <?php if ($isOwnerOrAdmin): ?>
                    <a href="edit_document.php?id=<?php echo $doc['document_id']; ?>" class="btn-action btn-sidebar-edit">
                        <i class="fas fa-edit"></i> Chỉnh sửa thông tin
                    </a>
                    <button onclick="confirmDelete(<?php echo $doc['document_id']; ?>, '<?php echo addslashes($doc['title']); ?>')" class="btn-action btn-sidebar-delete">
                        <i class="fas fa-trash"></i> Xóa tài liệu
                    </button>
                <?php endif; ?>
                
                <a href="documents.php" class="btn-back-link">
                    <i class="fas fa-arrow-left"></i> Quay lại danh sách
                </a>
            </div>

            <!-- Meta Data Card -->
            <div class="sidebar-card">
                <h3>Thông tin chi tiết</h3>
                <ul class="meta-list">
                    <li class="meta-item">
                        <span class="meta-label"><i class="fas fa-user"></i> Người đăng:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($doc['uploader_name']); ?></span>
                    </li>
                    <li class="meta-item">
                        <span class="meta-label"><i class="fas fa-calendar-alt"></i> Ngày tải lên:</span>
                        <span class="meta-value"><?php echo $joinDate; ?></span>
                    </li>
                    <li class="meta-item">
                        <span class="meta-label"><i class="fas fa-folder"></i> Danh mục:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($doc['category_name'] ?? 'Chưa phân loại'); ?></span>
                    </li>
                    <li class="meta-item">
                        <span class="meta-label"><i class="fas fa-book"></i> Môn học:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($doc['subject_name'] ?? 'Chưa chọn'); ?></span>
                    </li>
                    <li class="meta-item">
                        <span class="meta-label"><i class="fas fa-download"></i> Lượt tải:</span>
                        <span class="meta-value"><?php echo $doc['downloads_count']; ?> lần</span>
                    </li>
                    <li class="meta-item">
                        <span class="meta-label"><i class="fas fa-eye"></i> Quyền truy cập:</span>
                        <span class="meta-value">
                            <span class="badge <?php echo $visibilityClass; ?>"><?php echo $visibilityLabels[$doc['visibility']]; ?></span>
                        </span>
                    </li>
                    <li class="meta-item">
                        <span class="meta-label"><i class="fas fa-check-circle"></i> Trạng thái:</span>
                        <span class="meta-value">
                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusLabels[$doc['status']]; ?></span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </main>

    <script>
        function confirmDelete(id, title) {
            if (confirm(`Bạn có chắc chắn muốn xóa tài liệu "${title}"?\nHành động này không thể hoàn tác.`)) {
                const formData = new FormData();
                formData.append('document_id', id);

                fetch('../backend/delete_document.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Xóa tài liệu thành công!');
                        window.location.href = 'documents.php?success=' + encodeURIComponent('Đã xóa tài liệu thành công.');
                    } else {
                        alert('Lỗi: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Đã xảy ra lỗi hệ thống khi xóa tài liệu.');
                });
            }
        }

        function askAIChatbot(id) {
            alert('Tính năng Hỏi đáp AI về tài liệu đang được tích hợp. Bạn sẽ được chuyển tới Chatbot!');
            window.location.href = 'chatbot.php?doc_id=' + id;
        }
    </script>
</body>
</html>
