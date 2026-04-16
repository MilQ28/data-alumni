<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect admin/superadmin ke dashboard admin
if (in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: dashboard_admin.php');
    exit;
}

$search   = trim($_GET['search'] ?? '');
$jurusan  = trim($_GET['jurusan'] ?? '');
$angkatan = trim($_GET['angkatan'] ?? '');

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(nama LIKE ? OR nim LIKE ?)';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like]);
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
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY angkatan DESC, nama ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alumni = $stmt->fetchAll();

$jurusans  = $pdo->query("SELECT DISTINCT jurusan FROM alumni ORDER BY jurusan")->fetchAll(PDO::FETCH_COLUMN);
$angkatans = $pdo->query("SELECT DISTINCT angkatan FROM alumni ORDER BY angkatan DESC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direktori Alumni</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/dashboard.css">
</head>

<body>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-name">Alumni Portal</span>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard_user.php" class="nav-item active">
                <span class="nav-icon">-></span> Direktori Alumni
            </a>
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

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Direktori Alumni</h1>
                <p class="page-subtitle">Temukan sesama alumni di sini</p>
            </div>
        </div>

        <form method="GET" class="filter-bar">
            <input type="text" name="search" placeholder="Cari nama atau NIS..."
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
                    <option value="<?= $a ?>" <?= $angkatan == $a ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-filter">Filter</button>
            <?php if ($search || $jurusan || $angkatan): ?>
                <a href="dashboard_user.php" class="btn-reset">Reset</a>
            <?php endif; ?>
        </form>

        <!-- Cards View -->
        <div class="cards-grid">
            <?php if (empty($alumni)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎓</div>
                    <h3>Tidak ada data ditemukan</h3>
                    <p>Coba ubah kata kunci pencarian atau filter Anda.</p>
                </div>
            <?php else: ?>
                <?php foreach ($alumni as $a): ?>
                    <div class="alumni-card">
                        <div class="card-avatar"><?= strtoupper(mb_substr($a['nama'], 0, 1)) ?></div>
                        <div class="card-body">
                            <h3 class="card-name"><?= htmlspecialchars($a['nama']) ?></h3>
                            <p class="card-nim"><?= htmlspecialchars($a['nim']) ?></p>
                            <div class="card-tags">
                                <span class="badge-angkatan"><?= $a['angkatan'] ?></span>
                                <span class="badge-jurusan"><?= htmlspecialchars($a['jurusan']) ?></span>
                            </div>
                            <div class="card-info">
                                <?php if ($a['pekerjaan']): ?>
                                    <p>💼 <?= htmlspecialchars($a['pekerjaan']) ?><?= $a['perusahaan'] ? ' di ' . htmlspecialchars($a['perusahaan']) : '' ?></p>
                                <?php endif; ?>
                                <p>📧 <?= htmlspecialchars($a['email']) ?></p>
                                <p>📱 <?= htmlspecialchars($a['no_hp']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <footer>
            <div>
                <div>
                    <span>&copy; | Syamil Cholid Atsani - XI RPL 1</span>
                </div>
            </div>
        </footer>
    </main>

</body>

</html>