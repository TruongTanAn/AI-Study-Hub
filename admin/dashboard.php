<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_layout.php';

$stats = admin_get_stats($conn);

// Top 5 users upload nhieu nhat
$topUploaders = [];
$res = $conn->query(
    "SELECT u.user_id, u.full_name, u.email, COUNT(d.document_id) AS doc_count
     FROM users u
     LEFT JOIN documents d ON d.user_id = u.user_id
     GROUP BY u.user_id, u.full_name, u.email
     ORDER BY doc_count DESC
     LIMIT 5"
);
if ($res) $topUploaders = $res->fetch_all(MYSQLI_ASSOC);

// Top 5 documents duoc tai nhieu nhat
$topDownloads = [];
$res = $conn->query(
    "SELECT d.document_id, d.title, d.downloads_count, u.full_name AS owner_name
     FROM documents d
     LEFT JOIN users u ON d.user_id = u.user_id
     ORDER BY d.downloads_count DESC, d.document_id DESC
     LIMIT 5"
);
if ($res) $topDownloads = $res->fetch_all(MYSQLI_ASSOC);

// Activity logs gan day
$recentLogs = [];
$hasLogs = false;
$res = $conn->query("SHOW TABLES LIKE 'activity_logs'");
if ($res && $res->num_rows > 0) {
    $hasLogs = true;
    $stmt = $conn->prepare(
        "SELECT l.log_id, l.user_id, l.action, l.created_at, u.full_name, u.email
         FROM activity_logs l
         LEFT JOIN users u ON l.user_id = u.user_id
         ORDER BY l.log_id DESC
         LIMIT 8"
    );
    $stmt->execute();
    $recentLogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Docs moi nhat
$recentDocs = [];
$res = $conn->query(
    "SELECT d.document_id, d.title, d.file_type, d.created_at, u.full_name AS owner_name
     FROM documents d
     LEFT JOIN users u ON d.user_id = u.user_id
     ORDER BY d.document_id DESC
     LIMIT 5"
);
if ($res) $recentDocs = $res->fetch_all(MYSQLI_ASSOC);

// Thong ke users theo role
$usersByRole = ['admin' => 0, 'user' => 0, 'guest' => 0];
$res = $conn->query("SELECT role, COUNT(*) AS c FROM users GROUP BY role");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $usersByRole[$r['role']] = (int) $r['c'];
    }
}

// Thong ke docs theo status
$docsByStatus = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
$res = $conn->query("SELECT status, COUNT(*) AS c FROM documents GROUP BY status");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $docsByStatus[$r['status']] = (int) $r['c'];
    }
}

admin_render_header('Dashboard', 'dashboard.php');
?>

<div class="stats">
    <div class="stat" style="border-left:4px solid #6366f1">
        <div class="label"><i class="fas fa-users"></i> Tổng Users</div>
        <div class="num"><?php echo number_format($stats['users']); ?></div>
        <small style="color:#64748b">
            <?php echo $usersByRole['admin']; ?> admin / <?php echo $usersByRole['user']; ?> user
        </small>
    </div>
    <div class="stat" style="border-left:4px solid #10b981">
        <div class="label"><i class="fas fa-folder-open"></i> Tổng Tài liệu</div>
        <div class="num"><?php echo number_format($stats['documents']); ?></div>
        <small style="color:#64748b">
            <?php echo $docsByStatus['approved']; ?> duyệt / <?php echo $docsByStatus['pending']; ?> chờ
        </small>
    </div>
    <div class="stat" style="border-left:4px solid #f59e0b">
        <div class="label"><i class="fas fa-tags"></i> Tổng Danh mục</div>
        <div class="num"><?php echo number_format($stats['categories']); ?></div>
    </div>
    <div class="stat" style="border-left:4px solid #ec4899">
        <div class="label"><i class="fas fa-book"></i> Tổng Môn học</div>
        <div class="num"><?php echo number_format($stats['subjects']); ?></div>
    </div>
    <div class="stat" style="border-left:4px solid #06b6d4">
        <div class="label"><i class="fas fa-comments"></i> Cuộc trò chuyện</div>
        <div class="num"><?php echo number_format($stats['conversations']); ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:20px">
    <div class="card">
        <h3 style="margin-bottom:14px"><i class="fas fa-trophy" style="color:#f59e0b"></i> Top 5 người upload</h3>
        <table>
            <thead><tr><th>#</th><th>Người dùng</th><th>Email</th><th>Docs</th></tr></thead>
            <tbody>
            <?php if (empty($topUploaders)): ?>
                <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px">Chưa có dữ liệu</td></tr>
            <?php else: $i = 1; foreach ($topUploaders as $tu): if ((int) $tu['doc_count'] === 0) continue; ?>
                <tr>
                    <td><strong>#<?php echo $i++; ?></strong></td>
                    <td><?php echo htmlspecialchars($tu['full_name']); ?></td>
                    <td style="color:#64748b;font-size:.85rem"><?php echo htmlspecialchars($tu['email']); ?></td>
                    <td><span style="background:#e0e7ff;color:#4338ca;padding:4px 10px;border-radius:6px;font-weight:700"><?php echo (int) $tu['doc_count']; ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="margin-bottom:14px"><i class="fas fa-download" style="color:#10b981"></i> Top 5 tài liệu tải nhiều</h3>
        <table>
            <thead><tr><th>#</th><th>Tài liệu</th><th>Chủ sở hữu</th><th>Lượt tải</th></tr></thead>
            <tbody>
            <?php if (empty($topDownloads)): ?>
                <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px">Chưa có dữ liệu</td></tr>
            <?php else: $i = 1; foreach ($topDownloads as $td): if ((int) $td['downloads_count'] === 0) continue; ?>
                <tr>
                    <td><strong>#<?php echo $i++; ?></strong></td>
                    <td><?php echo htmlspecialchars(mb_strimwidth($td['title'], 0, 40, '...')); ?></td>
                    <td style="color:#64748b;font-size:.85rem"><?php echo htmlspecialchars($td['owner_name'] ?? '?'); ?></td>
                    <td><span style="background:#d1fae5;color:#047857;padding:4px 10px;border-radius:6px;font-weight:700"><?php echo (int) $td['downloads_count']; ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:20px">
    <div class="card">
        <h3 style="margin-bottom:14px"><i class="fas fa-clock" style="color:#6366f1"></i> Tài liệu mới nhất</h3>
        <table>
            <thead><tr><th>ID</th><th>Tiêu đề</th><th>Loại</th><th>Người up</th><th>Ngày</th></tr></thead>
            <tbody>
            <?php if (empty($recentDocs)): ?>
                <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px">Chưa có</td></tr>
            <?php else: foreach ($recentDocs as $rd): ?>
                <tr>
                    <td>#<?php echo (int) $rd['document_id']; ?></td>
                    <td><?php echo htmlspecialchars(mb_strimwidth($rd['title'], 0, 30, '...')); ?></td>
                    <td><small><?php echo htmlspecialchars($rd['file_type']); ?></small></td>
                    <td style="font-size:.85rem"><?php echo htmlspecialchars($rd['owner_name'] ?? '?'); ?></td>
                    <td style="font-size:.85rem;color:#64748b"><?php echo $rd['created_at'] ? date('d/m H:i', strtotime($rd['created_at'])) : '-'; ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="margin-bottom:14px"><i class="fas fa-history" style="color:#ec4899"></i> Hoạt động gần đây
            <?php if (!$hasLogs): ?><small style="color:#94a3b8;font-weight:400">(bảng activity_logs không tồn tại)</small><?php endif; ?>
        </h3>
        <?php if (!$hasLogs): ?>
            <p style="color:#94a3b8;text-align:center;padding:30px">
                <i class="fas fa-info-circle"></i> Bảng activity_logs chưa có.<br>
                <small>Xem trang <a href="activity.php">Activity Logs</a> để biết chi tiết.</small>
            </p>
        <?php elseif (empty($recentLogs)): ?>
            <p style="color:#94a3b8;text-align:center;padding:20px">Chưa có log nào</p>
        <?php else: ?>
            <table>
                <thead><tr><th>User</th><th>Hành động</th><th>Thời gian</th></tr></thead>
                <tbody>
                <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td style="font-size:.85rem"><?php echo htmlspecialchars($log['full_name'] ?? 'User #' . $log['user_id']); ?></td>
                        <td><small><?php echo htmlspecialchars(mb_strimwidth($log['action'], 0, 40, '...')); ?></small></td>
                        <td style="font-size:.8rem;color:#64748b"><?php echo date('d/m H:i', strtotime($log['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="text-align:right;margin-top:10px">
                <a href="activity.php" class="btn btn-sm btn-primary">Xem tất cả →</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php admin_render_footer(); ?>
