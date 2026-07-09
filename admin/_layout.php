<?php
/**
 * Layout helper cho admin.
 * Moi trang admin se:
 *  1. require '_guard.php'
 *  2. require '_layout.php'
 *  3. goi admin_render_header($title, $active)
 *  4. echo noi dung
 *  5. goi admin_render_footer()
 *
 * Ham tra ve HTML, khong lam thay doi file cu.
 */
if (!function_exists('admin_render_header')) {
    function admin_render_header(string $title, string $active = ''): void {
        $base = './';
        $navItems = [
            'dashboard.php'  => ['Dashboard',     'fa-th-large'],
            'users.php'      => ['Users',         'fa-users'],
            'documents.php'  => ['Documents',     'fa-folder-open'],
            'categories.php' => ['Categories',    'fa-tags'],
            'subjects.php'   => ['Subjects',      'fa-book'],
            'activity.php'   => ['Activity Logs', 'fa-history'],
            'downloads.php'  => ['Downloads',     'fa-download'],
        ];
        $name = htmlspecialchars($GLOBALS['currentAdminName'] ?? 'Admin');
        ?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($title); ?> - Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f1f5f9;color:#0f172a;min-height:100vh;display:flex}
.sidebar{width:240px;background:#0f172a;color:#e2e8f0;min-height:100vh;padding:20px 0;position:sticky;top:0}
.sidebar h2{color:#fff;text-align:center;padding:0 20px 20px;border-bottom:1px solid #1e293b;font-size:1.1rem}
.sidebar .logo{color:#fff;font-size:1.2rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:8px;justify-content:center;padding-bottom:20px}
.sidebar .logo i{color:#6366f1}
.sidebar nav a{display:flex;align-items:center;gap:10px;padding:12px 22px;color:#cbd5e1;text-decoration:none;font-weight:500;border-left:3px solid transparent;transition:.2s}
.sidebar nav a:hover{background:rgba(99,102,241,.1);color:#fff}
.sidebar nav a.active{background:rgba(99,102,241,.15);border-left-color:#6366f1;color:#fff}
.sidebar nav a i{width:20px;text-align:center}
.sidebar .logout{margin-top:30px;border-top:1px solid #1e293b;padding-top:20px}
.sidebar .logout a{color:#f87171}
.main{flex:1;padding:30px;overflow-x:auto}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;background:#fff;padding:16px 22px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.header h1{font-size:1.4rem;font-weight:700}
.header .user-info{color:#64748b;font-size:.9rem}
.header .user-info strong{color:#0f172a}
.card{background:#fff;border-radius:10px;padding:22px;box-shadow:0 2px 8px rgba(0,0,0,.04);margin-bottom:20px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:18px;margin-bottom:25px}
.stat{background:#fff;padding:22px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.stat .label{color:#64748b;font-size:.85rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.stat .num{font-size:2rem;font-weight:700;color:#0f172a;margin-top:8px}
.stat .num small{font-size:1rem;color:#94a3b8}
table{width:100%;border-collapse:collapse;background:#fff}
th,td{padding:12px 14px;text-align:left;border-bottom:1px solid #e2e8f0;font-size:.92rem}
th{background:#f8fafc;color:#475569;font-weight:700;font-size:.82rem;text-transform:uppercase;letter-spacing:.4px}
tr:hover{background:#f8fafc}
.btn{display:inline-block;padding:8px 16px;border-radius:8px;border:none;cursor:pointer;font-weight:600;font-size:.85rem;text-decoration:none;transition:.2s}
.btn-primary{background:#6366f1;color:#fff}
.btn-primary:hover{background:#4f46e5}
.btn-danger{background:#ef4444;color:#fff}
.btn-danger:hover{background:#dc2626}
.btn-edit{background:#f59e0b;color:#fff}
.btn-edit:hover{background:#d97706}
.btn-sm{padding:6px 12px;font-size:.8rem}
.form-row{margin-bottom:14px}
.form-row label{display:block;margin-bottom:6px;font-weight:600;color:#334155;font-size:.9rem}
.form-row input,.form-row select,.form-row textarea{width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:.9rem;font-family:inherit}
.form-row input:focus,.form-row select:focus,.form-row textarea:focus{outline:none;border-color:#6366f1}
.search-bar{display:flex;gap:10px;margin-bottom:18px;align-items:center}
.search-bar input{flex:1;max-width:380px;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px}
.alert{padding:12px 18px;border-radius:8px;margin-bottom:18px;font-weight:600}
.alert-success{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
.alert-error{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca}
.role-badge{padding:4px 10px;border-radius:6px;font-size:.75rem;font-weight:700;text-transform:uppercase;display:inline-block}
.role-badge.admin{background:#fef3c7;color:#b45309}
.role-badge.user{background:#e0e7ff;color:#4338ca}
@media (max-width:768px){body{flex-direction:column}.sidebar{width:100%;min-height:auto;position:relative}}
</style>
</head>
<body>
<aside class="sidebar">
  <a href="dashboard.php" class="logo"><i class="fas fa-shield-halved"></i> Admin Panel</a>
  <nav>
    <?php foreach ($navItems as $file => $info): ?>
        <a href="<?php echo $base . $file; ?>" class="<?php echo $active === $file ? 'active' : ''; ?>">
            <i class="fas <?php echo $info[1]; ?>"></i> <?php echo $info[0]; ?>
        </a>
    <?php endforeach; ?>
    <div class="logout">
        <a href="../backend/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </nav>
</aside>
<main class="main">
  <div class="header">
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <div class="user-info">Xin chào, <strong><?php echo $name; ?></strong></div>
  </div>
        <?php
    }

    function admin_render_footer(): void {
        ?>
</main>
</body>
</html>
        <?php
    }
}
