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
    <title>Tài liệu của tôi - AI Study Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/home.css">
    <style>
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

        .breadcrumb-bar a:hover {
            text-decoration: underline;
        }

        .documents-page-wrapper {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px 60px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 32px;
        }

        .page-title h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .page-title p {
            color: var(--gray);
            font-size: 0.9375rem;
        }

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        .document-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow);
            transition: .3s ease;
        }

        .document-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, .1);
        }

        .document-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 16px;
        }

        .document-icon.pdf {
            background: #fee2e2;
            color: #dc2626;
        }

        .document-icon.docx {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .document-icon.pptx {
            background: #fef3c7;
            color: #b45309;
        }

        .document-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .document-meta {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .document-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .document-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .document-status.uploaded {
            background: #dcfce7;
            color: #166534;
        }

        .document-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .document-status.failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .document-actions {
            display: flex;
            gap: 8px;
        }

        .document-actions a,
        .document-actions button {
            flex: 1;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: .3s ease;
            border: none;
        }

        .btn-view {
            background: var(--primary);
            color: white;
        }

        .btn-view:hover {
            background: var(--primary-hover);
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fecaca;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 24px;
        }

        .empty-state a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .documents-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <header class="page-header">
        <div class="logo">
            <i class="fas fa-brain"></i> AI Study Hub
        </div>
        <div class="header-actions">
            <span>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
            <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="upload.php"><i class="fas fa-upload"></i> Upload</a>
            <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </header>

    <nav class="breadcrumb-bar">
        <a href="dashboard.php">Dashboard</a>
        <i class="fas fa-chevron-right"></i>
        <span>Tài liệu của tôi</span>
    </nav>

    <main class="documents-page-wrapper">
        <div class="page-title">
            <h1><i class="fas fa-folder-open" style="color: var(--primary);"></i> Tài liệu của tôi</h1>
            <p>Quản lý và xem lại các tài liệu bạn đã tải lên</p>
        </div>

        <div id="documents-container">
            <div class="empty-state" id="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <h3>Đang tải...</h3>
            </div>
        </div>
    </main>

    <script>
        const API_ENDPOINT = '../backend/upload_status.php';
        const DELETE_ENDPOINT = '../backend/delete_document.php';

        async function loadDocuments() {
            try {
                const response = await fetch(API_ENDPOINT, {
                    method: 'GET',
                    credentials: 'same-origin'
                });

                const data = await response.json();
                const container = document.getElementById('documents-container');

                if (!data.success || !data.documents || data.documents.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <h3>Chưa có tài liệu nào</h3>
                            <p>Bạn chưa tải lên tài liệu nào. Hãy bắt đầu chia sẻ tài liệu học tập ngay!</p>
                            <a href="upload.php"><i class="fas fa-upload"></i> Tải lên tài liệu</a>
                        </div>
                    `;
                    return;
                }

                const userId = <?php echo $_SESSION['user_id']; ?>;
                const userRole = '<?php echo $_SESSION['role'] ?? 'user'; ?>';

                let html = '<div class="documents-grid">';

                data.documents.forEach(doc => {
                    const iconClass = doc.file_type.toLowerCase();
                    const icon = iconClass === 'PDF' ? 'fa-file-pdf' : (iconClass === 'DOCX' ? 'fa-file-word' : 'fa-file-powerpoint');
                    const statusClass = doc.upload_status;
                    const statusText = doc.status_text;

                    html += `
                        <div class="document-card">
                            <div class="document-icon ${iconClass.toLowerCase()}">
                                <i class="fas ${icon}"></i>
                            </div>
                            <h3 class="document-title">${escapeHtml(doc.title)}</h3>
                            <div class="document-meta">
                                <span><i class="fas fa-file"></i> ${doc.file_size_formatted}</span>
                                <span><i class="fas fa-calendar"></i> ${formatDate(doc.upload_date)}</span>
                            </div>
                            <span class="document-status ${statusClass}">${statusText}</span>
                            <div class="document-actions">
                                <a href="${doc.cloud_url || doc.file_path}" class="btn-view" target="_blank">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                                ${(doc.user_id === userId || userRole === 'admin') ? `
                                    <button onclick="deleteDocument(${doc.id}, '${escapeHtml(doc.title)}')" class="btn-delete">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                container.innerHTML = html;

            } catch (error) {
                console.error('Error loading documents:', error);
                document.getElementById('documents-container').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Đã xảy ra lỗi</h3>
                        <p>Không thể tải danh sách tài liệu. Vui lòng thử lại sau.</p>
                    </div>
                `;
            }
        }

        async function deleteDocument(id, title) {
            if (!confirm(`Bạn có chắc chắn muốn xóa tài liệu "${title}"?`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('document_id', id);

                const response = await fetch(DELETE_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await response.json();

                if (data.success) {
                    alert('Xóa tài liệu thành công!');
                    loadDocuments();
                } else {
                    alert('Lỗi: ' + data.error);
                }
            } catch (error) {
                console.error('Error deleting document:', error);
                alert('Đã xảy ra lỗi khi xóa tài liệu.');
            }
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', loadDocuments);
    </script>

</body>
</html>
