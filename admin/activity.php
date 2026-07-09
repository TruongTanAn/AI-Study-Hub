<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_layout.php';

$hasLogs = (bool) ($conn->query("SHOW TABLES LIKE 'activity_logs'")->num_rows);

// Filter
$filterUser = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$filterAction = trim($_GET['action'] ?? '');
$q = trim($_GET['q'] ?? '');

$logs = [];
$totalLogs = 0;
if ($hasLogs) {
    $where = ['1=1'];
    $params = [];
    $types = '';
    if ($filterUser > 0) {
        $where[] = 'l.user_id = ?';
        $params[] = $filterUser;
        $types .= 'i';
    }
    if ($filterAction !== '') {
        $where[] = 'l.action LIKE ?';
        $params[] = '%' . $filterAction . '%';
        $types .= 's';
    }
    if ($q !== '') {
        $where[] = '(u.full_name LIKE ? OR u.email LIKE ? OR l.action LIKE ?)';
        array_push($params, "%$q%", "%$q%", "%$q%");
        $types .= 'sss';
    }
    $whereSql = implode(' AND ', $where);

    $sqlCount = "SELECT COUNT(*) AS c FROM activity_logs l LEFT JOIN users u ON l.user_id = u.user_id WHERE $whereSql";
    $stmt = $conn->prepare($sqlCount);
    if ($stmt) {
        if ($types) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $totalLogs = (int) $stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
    }

    $sql = "SELECT l.log_id, l.user_id, l.action, l.created_at, u.full_name, u.email
            FROM activity_logs l
            LEFT JOIN users u ON l.user_id = u.user_id
            WHERE $whereSql
            ORDER BY l.log_id DESC
            LIMIT 100";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($types) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Top actions
$topActions = [];
if ($hasLogs) {
    $res = $conn->query(
        "SELECT action, COUNT(*) AS c FROM activity_logs GROUP BY action ORDER BY c DESC LIMIT 8"
    );
    if ($res) $topActions = $res->fetch_all(MYSQLI_ASSOC);
}

// Users list for filter
$usersList = [];
$res = $conn->query("SELECT user_id, full_name, email FROM users ORDER BY full_name");
if ($res) $usersList = $res->fetch_all(MYSQLI_ASSOC);

admin_render_header('Activity Logs', 'activity.php');
?>

<?php if (!$hasLogs): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        Bảng <strong>activity_logs</strong> không tồn tại. Hãy tạo bảng trước khi sử dụng chức năng này.
        <pre style="margin-top:10px;background:#fff;padding:10px;border-radius:6px;font-size:.8rem">CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(user_id),
    INDEX(created_at)
);</pre>
    </div>
<?php else: ?>

<div class="stats">
    <div class="stat" style="border-left:4px solid #6366f1">
        <div class="label"><i class="fas fa-history"></i> Tổng log</div>
        <div class="num"><?php echo number_format($totalLogs); ?></div>
    </div>
    <div class="stat" style="border-left:4px solid #10b981">
        <div class="label"><i class="fas fa-list"></i> Hiển thị</div>
        <div class="num"><?php echo count($logs); ?><small>/100</small></div>
    </div>
    <div class="stat" style="border-left:4px solid #f59e0b">
        <div class="label"><i class="fas fa-stream"></i> Loại hành động</div>
        <div class="num"><?php echo count($topActions); ?></div>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:14px"><i class="fas fa-filter" style="color:#6366f1"></i> Bộ lọc</h3>
    <form method="get" class="search-bar">
        <input type="text" name="q" placeholder="Tìm theo tên, email, action..." value="<?php echo htmlspecialchars($q); ?>">
        <select name="user_id">
            <option value="0">-- Tất cả users --</option>
            <?php foreach ($usersList as $u): ?>
                <option value="<?php echo (int) $u['user_id']; ?>" <?php echo $filterUser === (int) $u['user_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($u['full_name'] . ' (' . $u['email'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="action" placeholder="Action keyword..." value="<?php echo htmlspecialchars($filterAction); ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Lọc</button>
        <a href="activity.php" class="btn btn-danger"><i class="fas fa-times"></i> Reset</a>
    </form>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">
    <div class="card">
        <h3 style="margin-bottom:14px"><i class="fas fa-list" style="color:#6366f1"></i> Logs (<?php echo count($logs); ?>)</h3>
        <?php if (empty($logs)): ?>
            <p style="text-align:center;color:#94a3b8;padding:30px">Không có log nào phù hợp</p>
        <?php else: ?>
            <table>
                <thead><tr><th>ID</th><th>User</th><th>Hành động</th><th>Thời gian</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>#<?php echo (int) $log['log_id']; ?></td>
                        <td>
                            <?php if ($log['full_name']): ?>
                                <strong><?php echo htmlspecialchars($log['full_name']); ?></strong><br>
                                <small style="color:#64748b"><?php echo htmlspecialchars($log['email']); ?></small>
                            <?php else: ?>
                                <em style="color:#94a3b8">User đã xóa (#<?php echo (int) $log['user_id']; ?>)</em>
                            <?php endif; ?>
                        </td>
                        <td><small style="font-family:monospace;background:#f1f5f9;padding:4px 8px;border-radius:4px"><?php echo htmlspecialchars($log['action']); ?></small></td>
                        <td style="font-size:.85rem;color:#64748b"><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3 style="margin-bottom:14px"><i class="fas fa-chart-bar" style="color:#f59e0b"></i> Top hành động</h3>
        <?php if (empty($topActions)): ?>
            <p style="text-align:center;color:#94a3b8">Chưa có dữ liệu</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Hành động</th><th>Số lần</th></tr></thead>
                <tbody>
                <?php $max = max(array_column($topActions, 'c')); foreach ($topActions as $ta): ?>
                    <tr>
                        <td><small style="font-family:monospace"><?php echo htmlspecialchars(mb_strimwidth($ta['action'], 0, 25, '...')); ?></small></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div style="flex:1;background:#e2e8f0;border-radius:4px;overflow:hidden;height:8px">
                                    <div style="height:100%;background:linear-gradient(90deg,#6366f1,#8b5cf6);width:<?php echo min(100, round($ta['c'] / max($max, 1) * 100)); ?>%"></div>
                                </div>
                                <span style="font-weight:700;min-width:32px;text-align:right"><?php echo (int) $ta['c']; ?></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
endif;

admin_render_footer();
?>
