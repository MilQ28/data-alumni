<?php
session_start();
require 'koneksi.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

// Search & filter
$search = trim($_GET['search'] ?? '');
$role   = trim($_GET['role']   ?? '');

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(username LIKE ?)';
    $params[] = "%$search%";
}
if ($role) {
    $where[]  = 'role = ?';
    $params[] = $role;
}

$sql = "SELECT * FROM users";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY user_id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$total = count($users);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin – Manajemen Akun Pengguna</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/dashboard.css">
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-name">Alumni Portal</span>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard_admin.php" class="nav-item">
                <span class="nav-icon">-></span> Data Alumni
            </a>
            <a href="tambah.php" class="nav-item">
                <span class="nav-icon">-></span> Tambah Alumni
            </a>
            <?php if ($_SESSION['role'] === 'superadmin'): ?>
                <a href="users.php" class="nav-item active">
                    <span class="nav-icon">-></span> Kelola User
                </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper($_SESSION['username'][0]) ?></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                    <div class="user-role"><?= ucfirst($_SESSION['role']) ?></div>
                </div>
            </div>
            <a href="logout.php" class="btn-logout">Keluar</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Kelola Akun</h1>
                <p class="page-subtitle">Total <strong><?= $total ?></strong> Pengguna</p>
            </div>
            <a href="tambah_user.php" class="btn-primary">+ Tambah User</a>
        </div>

        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- Filter & Search -->
        <form method="GET" class="filter-bar">
            <input type="text" name="search" placeholder="Cari username..."
                value="<?= htmlspecialchars($search) ?>" class="filter-input">
            <select name="role" class="filter-select">
                <option value="">Semua Role</option>
                <option value="user" <?= $role === 'user'       ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $role === 'admin'      ? 'selected' : '' ?>>Admin</option>
                <option value="superadmin" <?= $role === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
            </select>
            <button type="submit" class="btn-filter">Filter</button>
            <?php if ($search || $role): ?>
                <a href="users.php" class="btn-reset">Reset</a>
            <?php endif; ?>
        </form>

        <!-- Table -->
        <div class="table-wrapper">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <h3>Belum ada data pengguna</h3>
                    <p>Mulai tambahkan akun menggunakan tombol "Tambah User".</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $i => $u): ?>
                            <tr>
                                <td class="td-center"><?= $i + 1 ?></td>
                                <td class="td-center"><code><?= $u['user_id'] ?></code></td>
                                <td class="td-nama"><?= htmlspecialchars($u['username']) ?></td>
                                <td class="td-center">
                                    <?php
                                    $roleClass = match ($u['role']) {
                                        'superadmin' => 'badge-superadmin',
                                        'admin'      => 'badge-admin',
                                        default      => 'badge-user',
                                    };
                                    ?>
                                    <span class="<?= $roleClass ?>"><?= ucfirst($u['role']) ?></span>
                                </td>
                                <td class="td-aksi">
                                    <!-- Jangan izinkan superadmin mengedit/hapus dirinya sendiri -->
                                    <?php if ($u['username'] !== $_SESSION['username']): ?>
                                        <a href="edit_user.php?id=<?= $u['user_id'] ?>" class="btn-edit">Edit</a>
                                        <form method="POST" action="delete_user.php"
                                            onsubmit="return confirmDelete('<?= htmlspecialchars($u['username']) ?>')">
                                            <input type="hidden" name="id" value="<?= $u['user_id'] ?>">
                                            <button type="submit" class="btn-delete">Hapus</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="td-self-note">— Akun Anda</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <footer class="footer">
            <marquee behavior="" direction="left">
                <h3><span>&copy; </span>| Syamil Cholid Atsani - XI RPL 1</h3>
            </marquee>
            <marquee behavior="" direction="right">
                <h3><span>&copy; </span>| Syamil Cholid Atsani - XI RPL 1</h3>
            </marquee>
        </footer>
    </main>

    <script>
        function confirmDelete(username) {
            return confirm('Yakin ingin menghapus akun "' + username + '"?\nTindakan ini tidak dapat dibatalkan.');
        }
    </script>
</body>

</html>