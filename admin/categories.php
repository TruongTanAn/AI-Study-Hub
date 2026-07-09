<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_layout.php';

$flash = ['type' => '', 'msg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['category_name'] ?? '');
        if ($name === '') {
            $flash = ['type' => 'error', 'msg' => 'Tên danh mục không được rỗng.'];
        } else {
            $stmt = $conn->prepare('INSERT INTO categories (category_name) VALUES (?)');
            $stmt->bind_param('s', $name);
            if ($stmt->execute()) {
                $flash = ['type' => 'success', 'msg' => 'Đã thêm danh mục.'];
            } else {
                $flash = ['type' => 'error', 'msg' => 'Không thể thêm: ' . $stmt->error];
            }
            $stmt->close();
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['category_id'] ?? 0);
        $name = trim($_POST['category_name'] ?? '');
        if ($id > 0 && $name !== '') {
            $stmt = $conn->prepare('UPDATE categories SET category_name = ? WHERE category_id = ?');
            $stmt->bind_param('si', $name, $id);
            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                $flash = ['type' => 'success', 'msg' => "Đã cập nhật danh mục #$id."];
            } else {
                $flash = ['type' => 'error', 'msg' => 'Không thể cập nhật.'];
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['category_id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM categories WHERE category_id = ?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $flash = ['type' => 'success', 'msg' => "Đã xóa danh mục #$id."];
            } else {
                $flash = ['type' => 'error', 'msg' => 'Không thể xóa (có thể đang có tài liệu dùng danh mục này).'];
            }
            $stmt->close();
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $stmt = $conn->prepare('SELECT * FROM categories WHERE category_id = ?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$result = $conn->query('SELECT c.category_id, c.category_name, COUNT(d.document_id) AS doc_count
                         FROM categories c
                         LEFT JOIN documents d ON d.category_id = c.category_id
                         GROUP BY c.category_id, c.category_name
                         ORDER BY c.category_id ASC');
$categories = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

admin_render_header('Quản lý Danh mục', 'categories.php');
?>

<?php if ($flash['msg'] !== ''): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['msg']); ?></div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-bottom:14px"><i class="fas fa-plus-circle"></i> <?php echo $editRow ? 'Sửa danh mục' : 'Thêm danh mục'; ?></h3>
    <form method="post">
        <input type="hidden" name="action" value="<?php echo $editRow ? 'update' : 'create'; ?>">
        <?php if ($editRow): ?>
            <input type="hidden" name="category_id" value="<?php echo (int) $editRow['category_id']; ?>">
        <?php endif; ?>
        <div class="form-row">
            <label>Tên danh mục</label>
            <input type="text" name="category_name" required maxlength="100"
                   value="<?php echo htmlspecialchars($editRow['category_name'] ?? ''); ?>">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $editRow ? 'Cập nhật' : 'Thêm'; ?></button>
        <?php if ($editRow): ?>
            <a href="categories.php" class="btn btn-sm" style="background:#e2e8f0;color:#0f172a">Hủy</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
<table>
    <thead>
        <tr>
            <th style="width:80px">ID</th>
            <th>Tên danh mục</th>
            <th style="width:120px">Số tài liệu</th>
            <th style="width:200px">Hành động</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($categories)): ?>
        <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:24px">Chưa có danh mục nào</td></tr>
    <?php else: foreach ($categories as $c): ?>
        <tr>
            <td>#<?php echo (int) $c['category_id']; ?></td>
            <td><?php echo htmlspecialchars($c['category_name']); ?></td>
            <td><?php echo (int) $c['doc_count']; ?></td>
            <td>
                <a href="categories.php?edit=<?php echo (int) $c['category_id']; ?>" class="btn btn-edit btn-sm"><i class="fas fa-edit"></i> Sửa</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Xóa danh mục #<?php echo (int) $c['category_id']; ?>?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" value="<?php echo (int) $c['category_id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<?php admin_render_footer(); ?>
