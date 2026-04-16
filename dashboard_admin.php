<?php
session_start();
require 'koneksi.php';

// Auth check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: login.php');
    exit;
}

// Search & filter
$search   = trim($_GET['search'] ?? '');
$jurusan  = trim($_GET['jurusan'] ?? '');
$angkatan = trim($_GET['angkatan'] ?? '');

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(nama LIKE ? OR nim LIKE ? OR email LIKE ?)';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}
if ($jurusan) {
    $where[]  = 'jurusan = ?';
    $params[] = $jurusan;
}
if ($angkatan) {
    $where[]  = 'angkatan = ?';
    $params[] = $angkatan;
}

$sql = "SELECT * FROM alumni";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY angkatan DESC, nama ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alumni = $stmt->fetchAll();

// Lists for filter dropdowns
$jurusans  = $pdo->query("SELECT DISTINCT jurusan FROM alumni ORDER BY jurusan")->fetchAll(PDO::FETCH_COLUMN);
$angkatans = $pdo->query("SELECT DISTINCT angkatan FROM alumni ORDER BY angkatan DESC")->fetchAll(PDO::FETCH_COLUMN);

$total = count($alumni);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin – Manajemen Data Alumni</title>
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
            <a href="dashboard_admin.php" class="nav-item active">
                <span class="nav-icon">-></span> Data Alumni
            </a>
            <a href="tambah.php" class="nav-item">
                <span class="nav-icon">-></span> Tambah Alumni
            </a>
            <?php if ($_SESSION['role'] === 'superadmin'): ?>
                <a href="/users.php" class="nav-item">
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
                <h1 class="page-title">Data Alumni</h1>
                <p class="page-subtitle">Total <strong><?= $total ?></strong> alumni terdaftar</p>
            </div>
            <a href="tambah.php" class="btn-primary">+ Tambah Alumni</a>
        </div>

        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- Filter & Search -->
        <form method="GET" class="filter-bar">
            <input type="text" name="search" placeholder="Cari nama, NIS, email..."
                value="<?= htmlspecialchars($search) ?>" class="filter-input">
            <select name="jurusan" class="filter-select">
                <option value="">Semua Jurusan</option>
                <?php foreach ($jurusans as $j): ?>
                    <option value="<?= htmlspecialchars($j) ?>" <?= $jurusan === $j ? 'selected' : '' ?>>
                        <?= htmlspecialchars($j) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="angkatan" class="filter-select">
                <option value="">Semua Angkatan</option>
                <?php foreach ($angkatans as $a): ?>
                    <option value="<?= $a ?>" <?= $angkatan == $a ? 'selected' : '' ?>>
                        <?= $a ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-filter">Filter</button>
            <?php if ($search || $jurusan || $angkatan): ?>
                <a href="dashboard_admin.php" class="btn-reset">Reset</a>
            <?php endif; ?>
        </form>

        <!-- Table -->
        <div class="table-wrapper">
            <?php if (empty($alumni)): ?>
                <div class="empty-state">
                    <h3>Belum ada data alumni</h3>
                    <p>Mulai tambahkan data alumni menggunakan tombol "Tambah Alumni".</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Angkatan</th>
                            <th>Jurusan</th>
                            <th>Email</th>
                            <th>No. HP</th>
                            <th>Pekerjaan</th>
                            <th>Perusahaan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumni as $i => $a): ?>
                            <tr>
                                <td class="td-center"><?= $i + 1 ?></td>
                                <td><code><?= htmlspecialchars($a['nim']) ?></code></td>
                                <td class="td-nama"><?= htmlspecialchars($a['nama']) ?></td>
                                <td class="td-center">
                                    <span class="badge-angkatan"><?= $a['angkatan'] ?></span>
                                </td>
                                <td><?= htmlspecialchars($a['jurusan']) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($a['email']) ?>"><?= htmlspecialchars($a['email']) ?></a></td>
                                <td><?= htmlspecialchars($a['no_hp']) ?></td>
                                <td><?= htmlspecialchars($a['pekerjaan'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($a['perusahaan'] ?? '-') ?></td>
                                <td class="td-aksi">
                                    <a href="edit.php?id=<?= $a['id_alumni'] ?>" class="btn-edit">Edit</a>
                                    <form method="POST" action="delete.php" onsubmit="return confirmDelete('<?= htmlspecialchars($a['nama']) ?>')">
                                        <input type="hidden" name="id" value="<?= $a['id_alumni'] ?>">
                                        <button type="submit" class="btn-delete">Hapus</button>
                                    </form>
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
        function confirmDelete(nama) {
            return confirm('Yakin ingin menghapus data alumni "' + nama + '"?\nTindakan ini tidak dapat dibatalkan.');
        }
    </script>
</body>

</html>