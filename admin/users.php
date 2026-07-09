<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_layout.php';

$flash = ['type' => '', 'msg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $deleteId = (int) ($_POST['user_id'] ?? 0);
        if ($deleteId === $currentAdminId) {
            $flash = ['type' => 'error', 'msg' => 'Khong the xoa chinh minh.'];
        } elseif ($deleteId > 0) {
            $stmt = $conn->prepare('DELETE FROM users WHERE user_id = ?');
            $stmt->bind_param('i', $deleteId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $flash = ['type' => 'success', 'msg' => "Da xoa user #$deleteId."];
            } else {
                $flash = ['type' => 'error', 'msg' => 'Khong the xoa user (co the dang co du lieu lien quan).'];
            }
            $stmt->close();
        }
    }
}

$keyword = trim($_GET['q'] ?? '');
if ($keyword !== '') {
    $stmt = $conn->prepare(
        'SELECT user_id, full_name, email, role, created_at
         FROM users
         WHERE full_name LIKE ? OR email LIKE ?
         ORDER BY user_id ASC'
    );
    $like = '%' . $keyword . '%';
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query(
        'SELECT user_id, full_name, email, role, created_at FROM users ORDER BY user_id ASC'
    );
    $users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

admin_render_header('Quản lý Users', 'users.php');
?>

<?php if ($flash['msg'] !== ''): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['msg']); ?></div>
<?php endif; ?>

<form method="get" class="search-bar">
    <input type="text" name="q" placeholder="Tìm theo tên hoặc email..." value="<?php echo htmlspecialchars($keyword); ?>">
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tìm</button>
    <?php if ($keyword !== ''): ?>
        <a href="users.php" class="btn btn-sm" style="background:#e2e8f0;color:#0f172a"><i class="fas fa-times"></i> Xóa lọc</a>
    <?php endif; ?>
</form>

<div class="card">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Họ tên</th>
            <th>Email</th>
            <th>Role</th>
            <th>Ngày tạo</th>
            <th style="width:120px">Hành động</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($users)): ?>
        <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:24px">Không có dữ liệu</td></tr>
    <?php else: foreach ($users as $u): ?>
        <tr>
            <td>#<?php echo (int) $u['user_id']; ?></td>
            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><span class="role-badge <?php echo htmlspecialchars($u['role']); ?>"><?php echo htmlspecialchars($u['role']); ?></span></td>
            <td><?php echo $u['created_at'] ? date('d/m/Y', strtotime($u['created_at'])) : '-'; ?></td>
            <td>
                <?php if ((int) $u['user_id'] === $currentAdminId): ?>
                    <span style="color:#94a3b8;font-size:.85rem"><i>Chính bạn</i></span>
                <?php else: ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Xóa user #<?php echo (int) $u['user_id']; ?>?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?php echo (int) $u['user_id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Xóa</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<?php admin_render_footer(); ?>
