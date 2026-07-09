<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_layout.php';

$flash = ['type' => '', 'msg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['subject_name'] ?? '');
        if ($name === '') {
            $flash = ['type' => 'error', 'msg' => 'Tên môn học không được rỗng.'];
        } else {
            $stmt = $conn->prepare('INSERT INTO subjects (subject_name) VALUES (?)');
            $stmt->bind_param('s', $name);
            if ($stmt->execute()) {
                $flash = ['type' => 'success', 'msg' => 'Đã thêm môn học.'];
            } else {
                $flash = ['type' => 'error', 'msg' => 'Không thể thêm: ' . $stmt->error];
            }
            $stmt->close();
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['subject_id'] ?? 0);
        $name = trim($_POST['subject_name'] ?? '');
        if ($id > 0 && $name !== '') {
            $stmt = $conn->prepare('UPDATE subjects SET subject_name = ? WHERE subject_id = ?');
            $stmt->bind_param('si', $name, $id);
            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                $flash = ['type' => 'success', 'msg' => "Đã cập nhật môn học #$id."];
            } else {
                $flash = ['type' => 'error', 'msg' => 'Không thể cập nhật.'];
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['subject_id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM subjects WHERE subject_id = ?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $flash = ['type' => 'success', 'msg' => "Đã xóa môn học #$id."];
            } else {
                $flash = ['type' => 'error', 'msg' => 'Không thể xóa (có thể đang có tài liệu dùng môn học này).'];
            }
            $stmt->close();
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $stmt = $conn->prepare('SELECT * FROM subjects WHERE subject_id = ?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$result = $conn->query('SELECT s.subject_id, s.subject_name, COUNT(d.document_id) AS doc_count
                         FROM subjects s
                         LEFT JOIN documents d ON d.subject_id = s.subject_id
                         GROUP BY s.subject_id, s.subject_name
                         ORDER BY s.subject_id ASC');
$subjects = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

admin_render_header('Quản lý Môn học', 'subjects.php');
?>

<?php if ($flash['msg'] !== ''): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['msg']); ?></div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-bottom:14px"><i class="fas fa-plus-circle"></i> <?php echo $editRow ? 'Sửa môn học' : 'Thêm môn học'; ?></h3>
    <form method="post">
        <input type="hidden" name="action" value="<?php echo $editRow ? 'update' : 'create'; ?>">
        <?php if ($editRow): ?>
            <input type="hidden" name="subject_id" value="<?php echo (int) $editRow['subject_id']; ?>">
        <?php endif; ?>
        <div class="form-row">
            <label>Tên môn học</label>
            <input type="text" name="subject_name" required maxlength="100"
                   value="<?php echo htmlspecialchars($editRow['subject_name'] ?? ''); ?>">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $editRow ? 'Cập nhật' : 'Thêm'; ?></button>
        <?php if ($editRow): ?>
            <a href="subjects.php" class="btn btn-sm" style="background:#e2e8f0;color:#0f172a">Hủy</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
<table>
    <thead>
        <tr>
            <th style="width:80px">ID</th>
            <th>Tên môn học</th>
            <th style="width:120px">Số tài liệu</th>
            <th style="width:200px">Hành động</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($subjects)): ?>
        <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:24px">Chưa có môn học nào</td></tr>
    <?php else: foreach ($subjects as $s): ?>
        <tr>
            <td>#<?php echo (int) $s['subject_id']; ?></td>
            <td><?php echo htmlspecialchars($s['subject_name']); ?></td>
            <td><?php echo (int) $s['doc_count']; ?></td>
            <td>
                <a href="subjects.php?edit=<?php echo (int) $s['subject_id']; ?>" class="btn btn-edit btn-sm"><i class="fas fa-edit"></i> Sửa</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Xóa môn học #<?php echo (int) $s['subject_id']; ?>?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="subject_id" value="<?php echo (int) $s['subject_id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<?php admin_render_footer(); ?>
