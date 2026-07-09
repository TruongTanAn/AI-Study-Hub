<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_layout.php';

$hasDownloads = (bool) ($conn->query("SHOW TABLES LIKE 'downloads'")->num_rows);

// Filter
$filterDoc = isset($_GET['doc_id']) ? (int) $_GET['doc_id'] : 0;
$filterUser = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$range = $_GET['range'] ?? 'all';

$now = time();
$start = null;
switch ($range) {
    case '7d':  $start = date('Y-m-d', strtotime('-7 days'));  break;
    case '30d': $start = date('Y-m-d', strtotime('-30 days')); break;
    case '90d': $start = date('Y-m-d', strtotime('-90 days')); break;
    default:    $start = null;
}

// Latest downloads
$rows = [];
if ($hasDownloads) {
    $where = ['1=1'];
    $params = [];
    $types = '';
    if ($filterDoc > 0) {
        $where[] = 'dl.document_id = ?';
        $params[] = $filterDoc;
        $types .= 'i';
    }
    if ($filterUser > 0) {
        $where[] = 'dl.user_id = ?';
        $params[] = $filterUser;
        $types .= 'i';
    }
    if ($start) {
        $where[] = 'dl.downloaded_at >= ?';
        $params[] = $start . ' 00:00:00';
        $types .= 's';
    }
    $whereSql = implode(' AND ', $where);

    $sql = "SELECT dl.id, dl.downloaded_at, dl.document_id, dl.user_id,
                   d.title AS doc_title, d.file_type,
                   u.full_name, u.email
            FROM downloads dl
            LEFT JOIN documents d ON dl.document_id = d.document_id
            LEFT JOIN users u ON dl.user_id = u.user_id
            WHERE $whereSql
            ORDER BY dl.id DESC
            LIMIT 100";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($types) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Stats
$stats = ['total' => 0, 'today' => 0, 'week' => 0, 'uniqueUsers' => 0];
if ($hasDownloads) {
    $r = $conn->query("SELECT COUNT(*) AS c FROM downloads");
    if ($r) $stats['total'] = (int) $r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(*) AS c FROM downloads WHERE downloaded_at >= CURDATE()");
    if ($r) $stats['today'] = (int) $r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(*) AS c FROM downloads WHERE downloaded_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    if ($r) $stats['week'] = (int) $r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(DISTINCT user_id) AS c FROM downloads");
    if ($r) $stats['uniqueUsers'] = (int) $r->fetch_assoc()['c'];
}

// Daily chart (last 14 days)
$dailyData = [];
if ($hasDownloads) {
    $r = $conn->query(
        "SELECT DATE(downloaded_at) AS d, COUNT(*) AS c
         FROM downloads
         WHERE downloaded_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
         GROUP BY DATE(downloaded_at)
         ORDER BY d"
    );
    $days = [];
    for ($i = 13; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i days"))] = 0;
    if ($r) {
        while ($rr = $r->fetch_assoc()) $days[$rr['d']] = (int) $rr['c'];
    }
    $dailyData = $days;
    $maxDaily = max($dailyData) ?: 1;
}

// Top downloaders
$topDownloaders = [];
if ($hasDownloads) {
    $r = $conn->query(
        "SELECT u.user_id, u.full_name, u.email, COUNT(dl.id) AS cnt
         FROM downloads dl
         LEFT JOIN users u ON dl.user_id = u.user_id
         GROUP BY u.user_id, u.full_name, u.email
         HAVING cnt > 0
         ORDER BY cnt DESC
         LIMIT 5"
    );
    if ($r) $topDownloaders = $r->fetch_all(MYSQLI_ASSOC);
}

// All users for filter
$usersList = [];
$r = $conn->query("SELECT user_id, full_name, email FROM users ORDER BY full_name");
if ($r) $usersList = $r->fetch_all(MYSQLI_ASSOC);

admin_render_header('Downloads Report', 'downloads.php');
?>

<?php if (!$hasDownloads): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        Bảng <strong>downloads</strong> không tồn tại.
    </div>
<?php endif; ?>

<div class="stats">
    <div class="stat" style="border-left:4px solid #6366f1">
        <div class="label"><i class="fas fa-download"></i> Tổng lượt tải</div>
        <div class="num"><?php echo number_format($stats['total']); ?></div>
    </div>
    <div class="stat" style="border-left:4px solid #10b981">
        <div class="label"><i class="fas fa-calendar-day"></i> Hôm nay</div>
        <div class="num"><?php echo number_format($stats['today']); ?></div>
    </div>
    <div class="stat" style="border-left:4px solid #f59e0b">
        <div class="label"><i class="fas fa-calendar-week"></i> 7 ngày qua</div>
        <div class="num"><?php echo number_format($stats['week']); ?></div>
    </div>
    <div class="stat" style="border-left:4px solid #ec4899">
        <div class="label"><i class="fas fa-user"></i> User đã tải</div>
        <div class="num"><?php echo number_format($stats['uniqueUsers']); ?></div>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:14px"><i class="fas fa-chart-line" style="color:#6366f1"></i> Lượt tải 14 ngày qua</h3>
    <?php if (empty($dailyData) || max($dailyData) === 0): ?>
        <p style="text-align:center;color:#94a3b8;padding:30px">Chưa có dữ liệu</p>
    <?php else: ?>
        <div style="display:flex;align-items:flex-end;gap:6px;height:180px;padding:10px 0;border-bottom:1px solid #e2e8f0;border-left:1px solid #e2e8f0">
            <?php $maxDaily = max($dailyData) ?: 1; foreach ($dailyData as $d => $c):
                $h = ($c / $maxDaily) * 150;
                $h = max(2, $h);
            ?>
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px" title="<?php echo $d; ?>: <?php echo $c; ?> lượt">
                    <div style="font-size:.65rem;color:#64748b;font-weight:700;min-height:14px"><?php echo $c ?: ''; ?></div>
                    <div style="width:100%;background:linear-gradient(180deg,#6366f1,#8b5cf6);height:<?php echo $h; ?>px;border-radius:6px 6px 0 0;transition:.2s" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'"></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:6px;margin-top:6px">
            <?php foreach ($dailyData as $d => $c): ?>
                <div style="flex:1;text-align:center;font-size:.65rem;color:#94a3b8"><?php echo date('d/m', strtotime($d)); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">
    <div class="card">
        <h3 style="margin-bottom:14px"><i class="fas fa-list" style="color:#10b981"></i> Lượt tải mới nhất (<?php echo count($rows); ?>)</h3>
        <form method="get" class="search-bar" style="margin-bottom:14px">
            <select name="range" onchange="this.form.submit()">
                <option value="all" <?php echo $range === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                <option value="7d" <?php echo $range === '7d' ? 'selected' : ''; ?>>7 ngày</option>
                <option value="30d" <?php echo $range === '30d' ? 'selected' : ''; ?>>30 ngày</option>
                <option value="90d" <?php echo $range === '90d' ? 'selected' : ''; ?>>90 ngày</option>
            </select>
            <select name="user_id">
                <option value="0">-- Tất cả users --</option>
                <?php foreach ($usersList as $u): ?>
                    <option value="<?php echo (int) $u['user_id']; ?>" <?php echo $filterUser === (int) $u['user_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($u['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
        </form>

        <?php if (empty($rows)): ?>
            <p style="text-align:center;color:#94a3b8;padding:30px">Chưa có lượt tải nào</p>
        <?php else: ?>
            <table>
                <thead><tr><th>ID</th><th>Tài liệu</th><th>Người tải</th><th>Loại</th><th>Thời gian</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>#<?php echo (int) $r['id']; ?></td>
                        <td><?php echo htmlspecialchars(mb_strimwidth($r['doc_title'] ?? '[#' . $r['document_id'] . ' đã xóa]', 0, 35, '...')); ?></td>
                        <td>
                            <?php if ($r['full_name']): ?>
                                <strong><?php echo htmlspecialchars($r['full_name']); ?></strong><br>
                                <small style="color:#64748b"><?php echo htmlspecialchars($r['email']); ?></small>
                            <?php else: ?>
                                <em style="color:#94a3b8">User #<?php echo (int) $r['user_id']; ?></em>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars($r['file_type'] ?? '-'); ?></small></td>
                        <td style="font-size:.85rem;color:#64748b"><?php echo date('d/m H:i', strtotime($r['downloaded_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3 style="margin-bottom:14px"><i class="fas fa-medal" style="color:#f59e0b"></i> Top người tải</h3>
        <?php if (empty($topDownloaders)): ?>
            <p style="text-align:center;color:#94a3b8">Chưa có</p>
        <?php else: ?>
            <table>
                <thead><tr><th>#</th><th>User</th><th>Lượt</th></tr></thead>
                <tbody>
                <?php $i = 1; $mx = max(array_column($topDownloaders, 'cnt')); foreach ($topDownloaders as $td): ?>
                    <tr>
                        <td>#<?php echo $i++; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($td['full_name'] ?? '[#' . $td['user_id'] . ']'); ?></strong><br>
                            <small style="color:#64748b"><?php echo htmlspecialchars($td['email'] ?? ''); ?></small>
                        </td>
                        <td>
                            <span style="background:#e0e7ff;color:#4338ca;padding:4px 10px;border-radius:6px;font-weight:700">
                                <?php echo (int) $td['cnt']; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php admin_render_footer(); ?>
