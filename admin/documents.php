<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_layout.php';

$flash = ['type' => '', 'msg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $docId = (int) ($_POST['document_id'] ?? 0);
        if ($docId > 0) {
            $stmt = $conn->prepare('SELECT file_path FROM documents WHERE document_id = ?');
            $stmt->bind_param('i', $docId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                $del = $conn->prepare('DELETE FROM documents WHERE document_id = ?');
                $del->bind_param('i', $docId);
                if ($del->execute() && $del->affected_rows > 0) {
                    if (!empty($row['file_path']) && is_file($row['file_path'])) {
                        @unlink($row['file_path']);
                    }
                    $flash = ['type' => 'success', 'msg' => "Đã xóa tài liệu #$docId."];
                } else {
                    $flash = ['type' => 'error', 'msg' => 'Không thể xóa (có thể có dữ liệu liên quan).'];
                }
                $del->close();
            } else {
                $flash = ['type' => 'error', 'msg' => 'Tài liệu không tồn tại.'];
            }
        }
    }
}

$keyword = trim($_GET['q'] ?? '');
if ($keyword !== '') {
    $stmt = $conn->prepare(
        'SELECT d.document_id, d.title, d.file_type, d.status, d.visibility, d.created_at,
                u.full_name AS owner_name, u.email AS owner_email
         FROM documents d
         LEFT JOIN users u ON d.user_id = u.user_id
         WHERE d.title LIKE ?
         ORDER BY d.document_id DESC'
    );
    $like = '%' . $keyword . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $docs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query(
        'SELECT d.document_id, d.title, d.file_type, d.status, d.visibility, d.created_at,
                u.full_name AS owner_name, u.email AS owner_email
         FROM documents d
         LEFT JOIN users u ON d.user_id = u.user_id
         ORDER BY d.document_id DESC
         LIMIT 200'
    );
    $docs = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function admin_status_label(string $s): string {
    return ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'][$s] ?? $s;
}
function admin_vis_label(string $v): string {
    return ['public' => 'Công khai', 'private' => 'Riêng tư', 'shared' => 'Chia sẻ'][$v] ?? $v;
}

admin_render_header('Quản lý Tài liệu', 'documents.php');
?>

<?php if ($flash['msg'] !== ''): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['msg']); ?></div>
<?php endif; ?>

<form method="get" class="search-bar">
    <input type="text" name="q" placeholder="Tìm theo tiêu đề tài liệu..." value="<?php echo htmlspecialchars($keyword); ?>">
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tìm</button>
    <?php if ($keyword !== ''): ?>
        <a href="documents.php" class="btn btn-sm" style="background:#e2e8f0;color:#0f172a"><i class="fas fa-times"></i> Xóa lọc</a>
    <?php endif; ?>
    <span style="margin-left:auto;color:#64748b">Tổng: <?php echo count($docs); ?> tài liệu</span>
</form>

<div class="card">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Tiêu đề</th>
            <th>Loại</th>
            <th>Chủ sở hữu</th>
            <th>Trạng thái</th>
            <th>Quyền</th>
            <th>Ngày tạo</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($docs)): ?>
        <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:24px">Không có tài liệu</td></tr>
    <?php else: foreach ($docs as $d): ?>
        <tr>
            <td>#<?php echo (int) $d['document_id']; ?></td>
            <td><?php echo htmlspecialchars($d['title']); ?></td>
            <td><?php echo htmlspecialchars($d['file_type']); ?></td>
            <td><?php echo htmlspecialchars($d['owner_name'] ?? '?'); ?></td>
            <td><?php echo admin_status_label($d['status']); ?></td>
            <td><?php echo admin_vis_label($d['visibility']); ?></td>
            <td><?php echo $d['created_at'] ? date('d/m/Y', strtotime($d['created_at'])) : '-'; ?></td>
            <td>
                <form method="post" style="display:inline" onsubmit="return confirm('Xóa tài liệu #<?php echo (int) $d['document_id']; ?>?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="document_id" value="<?php echo (int) $d['document_id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<?php admin_render_footer(); ?>
