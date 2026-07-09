<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

$success_msg = isset($_GET['success']) ? htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8') : '';
$error_msg = isset($_GET['error']) ? htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') : '';

// Fetch categories for filtering pills
$categories = [];
$catResult = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_id ASC");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}
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

        .page-header .header-actions a.active {
            color: var(--primary);
            background: rgba(99, 102, 241, 0.08);
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

        /* Wrapper */
        .documents-page-wrapper {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 24px 60px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 35px;
        }

        .page-title h1 {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .page-title p {
            color: var(--gray);
            font-size: 0.95rem;
        }

        /* Search and Filter Section */
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .search-box-wrapper {
            position: relative;
            width: 100%;
        }

        .search-box-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 1.1rem;
        }

        .search-input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 0.95rem;
            outline: none;
            transition: var(--transition);
            color: var(--dark);
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .categories-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .filter-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dark);
            margin-right: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .category-pill {
            padding: 8px 18px;
            border-radius: 20px;
            background: var(--light);
            border: 1.5px solid var(--border);
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray);
            cursor: pointer;
            transition: var(--transition);
        }

        .category-pill:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(99, 102, 241, 0.04);
        }

        .category-pill.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-color: transparent;
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        /* Grid */
        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 28px;
        }

        .document-card {
            background: white;
            border-radius: 18px;
            padding: 24px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .document-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: transparent;
            transition: var(--transition);
        }

        .document-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.06);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .document-card:hover::after {
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
        }

        .card-header-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .document-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: var(--transition);
        }

        .document-card:hover .document-icon {
            transform: scale(1.05);
        }

        .document-icon.pdf { background: #fee2e2; color: #dc2626; }
        .document-icon.docx { background: #dbeafe; color: #1d4ed8; }
        .document-icon.pptx { background: #fef3c7; color: #b45309; }

        .card-badges {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }

        .document-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .document-status.pending { background: #fffbeb; color: #b45309; border-color: #fef3c7; }
        .document-status.approved { background: #f0fdf4; color: #15803d; border-color: #dcfce7; }
        .document-status.rejected { background: #fdf2f2; color: #9b1c1c; border-color: #fde8e8; }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge.public { background: #eef2ff; color: #4338ca; }
        .badge.private { background: #f1f5f9; color: #475569; }
        .badge.shared { background: #fae8ff; color: #a21caf; }

        .document-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.8em;
        }

        .document-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }

        .tag-item {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            background: #f8fafc;
            color: var(--gray);
            border: 1px solid var(--border);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .tag-item i {
            font-size: 0.75rem;
            color: var(--primary);
        }

        .document-description {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.8em;
            line-height: 1.4;
        }

        .document-meta {
            font-size: 0.8rem;
            color: var(--gray);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid var(--border);
            padding-top: 12px;
            margin-top: auto;
        }

        .document-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .document-actions {
            display: flex;
            gap: 8px;
        }

        .document-actions a,
        .document-actions button {
            flex: 1;
            padding: 10px 8px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-view {
            background: var(--primary);
            color: white;
        }

        .btn-view:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2);
        }

        .btn-edit {
            background: #f0fdfa;
            color: #0d9488;
            border: 1px solid #ccfbf1;
        }

        .btn-edit:hover {
            background: #ccfbf1;
        }

        .btn-delete {
            background: #fff5f5;
            color: #e11d48;
            border: 1px solid #ffe4e6;
        }

        .btn-delete:hover {
            background: #ffe4e6;
        }

        /* Empty / Alert States */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .empty-state i {
            font-size: 3.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            color: var(--dark);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 24px;
            font-size: 0.95rem;
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
            font-weight: 700;
            transition: var(--transition);
        }

        .empty-state a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, .25);
        }

        .alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
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

        @media (max-width: 768px) {
            .documents-grid {
                grid-template-columns: 1fr;
            }
            .filter-section {
                padding: 18px;
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
            <span class="user-greeting">Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
            <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="documents.php" class="active"><i class="fas fa-folder-open"></i> Tài liệu của tôi</a>
            <a href="upload.php" class="btn-upload-nav"><i class="fas fa-upload"></i> Upload</a>
            <a href="../backend/logout.php" class="btn-logout-nav"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
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
            <p>Quản lý và chỉnh sửa các tài liệu học tập bạn đã chia sẻ</p>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Filter and Search controls -->
        <div class="filter-section">
            <div class="search-box-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" class="search-input" placeholder="Tìm kiếm tài liệu theo tiêu đề hoặc mô tả...">
            </div>
            <div class="categories-filter">
                <span class="filter-label"><i class="fas fa-filter"></i> Lọc:</span>
                <span class="category-pill active" data-category="all">Tất cả</span>
                <?php foreach ($categories as $cat): ?>
                    <span class="category-pill" data-category="<?php echo $cat['category_id']; ?>">
                        <?php echo htmlspecialchars($cat['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="documents-container">
            <div class="empty-state" id="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <h3>Đang tải danh sách tài liệu...</h3>
            </div>
        </div>
    </main>

    <script>
        const API_ENDPOINT = '../backend/upload_status.php';
        const DELETE_ENDPOINT = '../backend/delete_document.php';
        
        let allDocuments = []; // Cache all documents for client-side filtering
        let activeCategory = 'all';
        let searchQuery = '';

        async function loadDocuments() {
            try {
                const response = await fetch(API_ENDPOINT, {
                    method: 'GET',
                    credentials: 'same-origin'
                });

                const data = await response.json();
                
                if (!data.success || !data.documents) {
                    showEmptyState('Đã xảy ra lỗi', 'Không thể tải danh sách tài liệu từ server.');
                    return;
                }

                allDocuments = data.documents;
                renderDocuments();

            } catch (error) {
                console.error('Error loading documents:', error);
                showEmptyState('Đã xảy ra lỗi', 'Kết nối mạng thất bại. Vui lòng thử lại.');
            }
        }

        function renderDocuments() {
            const container = document.getElementById('documents-container');
            
            // Filter documents based on active category and search query
            const filtered = allDocuments.filter(doc => {
                const matchesCategory = activeCategory === 'all' || doc.category_id.toString() === activeCategory;
                
                const normalizedTitle = (doc.title || '').toLowerCase();
                const normalizedDesc = (doc.description || '').toLowerCase();
                const matchesSearch = searchQuery === '' || 
                                      normalizedTitle.includes(searchQuery) || 
                                      normalizedDesc.includes(searchQuery);
                                      
                return matchesCategory && matchesSearch;
            });

            if (filtered.length === 0) {
                if (allDocuments.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <h3>Chưa có tài liệu nào</h3>
                            <p>Bạn chưa tải lên tài liệu nào. Hãy bắt đầu chia sẻ tài liệu học tập ngay!</p>
                            <a href="upload.php"><i class="fas fa-upload"></i> Tải lên tài liệu</a>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>Không tìm thấy tài liệu</h3>
                            <p>Không có kết quả khớp với bộ lọc hoặc từ khóa tìm kiếm của bạn.</p>
                        </div>
                    `;
                }
                return;
            }

            const userId = <?php echo $_SESSION['user_id']; ?>;
            const userRole = '<?php echo $_SESSION['role'] ?? 'user'; ?>';

            let html = '<div class="documents-grid">';

            filtered.forEach(doc => {
                const fileTypeLower = doc.file_type.toLowerCase();
                let iconClass = 'docx';
                let icon = 'fa-file-word';

                if (fileTypeLower === 'pdf') {
                    iconClass = 'pdf';
                    icon = 'fa-file-pdf';
                } else if (fileTypeLower === 'pptx') {
                    iconClass = 'pptx';
                    icon = 'fa-file-powerpoint';
                }

                const statusClass = doc.status || 'pending';
                const statusText = doc.status_text || 'Đang chờ duyệt';
                const descriptionText = doc.description ? escapeHtml(doc.description) : 'Không có mô tả nào.';
                const visibilityText = doc.visibility === 'public' ? 'Công khai' : (doc.visibility === 'private' ? 'Riêng tư' : 'Chia sẻ');
                const visibilityClass = doc.visibility || 'public';
                const categoryName = doc.category_name ? escapeHtml(doc.category_name) : 'Chưa phân loại';
                const subjectName = doc.subject_name ? escapeHtml(doc.subject_name) : 'Chưa chọn';

                html += `
                    <div class="document-card">
                        <div class="card-header-info">
                            <div class="document-icon ${iconClass}">
                                <i class="fas ${icon}"></i>
                            </div>
                            <div class="card-badges">
                                <span class="document-status ${statusClass}">${statusText}</span>
                                <span class="badge ${visibilityClass}">${visibilityText}</span>
                            </div>
                        </div>
                        <h3 class="document-title" title="${escapeHtml(doc.title)}">${escapeHtml(doc.title)}</h3>
                        
                        <div class="document-tags">
                            <span class="tag-item tag-category" title="Danh mục"><i class="fas fa-folder"></i> ${categoryName}</span>
                            <span class="tag-item tag-subject" title="Môn học"><i class="fas fa-book"></i> ${subjectName}</span>
                        </div>

                        <p class="document-description">${descriptionText}</p>
                        
                        <div class="document-meta">
                            <span><i class="fas fa-hdd"></i> ${doc.file_size_formatted}</span>
                            <span><i class="fas fa-calendar-alt"></i> ${formatDate(doc.created_at)}</span>
                            <span><i class="fas fa-download"></i> ${doc.downloads_count} lượt</span>
                        </div>
                        
                        <div class="document-actions">
                            <a href="document_detail.php?id=${doc.id}" class="btn-view">
                                <i class="fas fa-eye"></i> Chi tiết
                            </a>
                            ${(doc.user_id === userId || userRole === 'admin') ? `
                                <a href="edit_document.php?id=${doc.id}" class="btn-edit" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                                <button onclick="deleteDocument(${doc.id}, '${escapeHtml(doc.title)}')" class="btn-delete" title="Xóa tài liệu">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        function showEmptyState(title, message) {
            document.getElementById('documents-container').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>${title}</h3>
                    <p>${message}</p>
                </div>
            `;
        }

        async function deleteDocument(id, title) {
            if (!confirm(`Bạn có chắc chắn muốn xóa tài liệu "${title}"?\nHành động này không thể hoàn tác.`)) {
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
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            return date.toLocaleDateString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
            loadDocuments();

            // Search filtering
            const searchInput = document.getElementById('search-input');
            searchInput.addEventListener('input', (e) => {
                searchQuery = e.target.value.toLowerCase().trim();
                renderDocuments();
            });

            // Category filtering
            const pills = document.querySelectorAll('.category-pill');
            pills.forEach(pill => {
                pill.addEventListener('click', (e) => {
                    pills.forEach(p => p.classList.remove('active'));
                    e.target.classList.add('active');
                    activeCategory = e.target.getAttribute('data-category');
                    renderDocuments();
                });
            });
        });
    </script>
</body>
</html>
