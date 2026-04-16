<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['nim', 'nama', 'angkatan', 'jurusan', 'email', 'no_hp', 'pekerjaan', 'perusahaan', 'alamat'];
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }

    // Validasi
    if (!$data['nim'])      $errors[] = 'NIS wajib diisi.';
    if (!$data['nama'])     $errors[] = 'Nama wajib diisi.';
    if (!$data['angkatan']) $errors[] = 'Angkatan wajib diisi.';
    if (!$data['jurusan'])  $errors[] = 'Jurusan wajib diisi.';
    if (!$data['email'])    $errors[] = 'Email wajib diisi.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!$data['no_hp'])    $errors[] = 'No. HP wajib diisi.';

    // Cek duplikat NIM
    if (!$errors) {
        $chk = $pdo->prepare("SELECT id_alumni FROM alumni WHERE nim = ?");
        $chk->execute([$data['nim']]);
        if ($chk->fetch()) $errors[] = 'NIS sudah terdaftar.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            INSERT INTO alumni (nim, nama, angkatan, jurusan, email, no_hp, pekerjaan, perusahaan, alamat)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['nim'], $data['nama'], $data['angkatan'], $data['jurusan'],
            $data['email'], $data['no_hp'],
            $data['pekerjaan'] ?: null,
            $data['perusahaan'] ?: null,
            $data['alamat'] ?: null,
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Data alumni berhasil ditambahkan!'];
        header('Location: dashboard_admin.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Alumni</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/tambah.css">
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-name">Alumni Portal</span>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard_admin.php" class="nav-item">
            <span class="nav-icon">-></span> Data Alumni
        </a>
        <a href="tambah.php" class="nav-item active">
            <span class="nav-icon">-></span> Tambah Alumni
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
            <h1 class="page-title">Tambah Alumni</h1>
            <p class="page-subtitle">Isi data alumni baru di bawah ini</p>
        </div>
        <a href="dashboard_admin.php" class="btn-back">← Kembali</a>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-error">
        <strong>Terdapat kesalahan:</strong>
        <ul>
            <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <div class="form-section">
                <h2 class="section-title">Informasi Akademik</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nim">NIS <span class="required">*</span></label>
                        <input type="text" id="nim" name="nim"
                               value="<?= htmlspecialchars($data['nim'] ?? '') ?>"
                               placeholder="Contoh: 2019001" required>
                    </div>
                    <div class="form-group">
                        <label for="angkatan">Angkatan <span class="required">*</span></label>
                        <input type="number" id="angkatan" name="angkatan"
                               value="<?= htmlspecialchars($data['angkatan'] ?? date('Y')) ?>"
                               min="1990" max="<?= date('Y') ?>" required>
                    </div>
                    <div class="form-group form-group-full">
                        <label for="nama">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" id="nama" name="nama"
                               value="<?= htmlspecialchars($data['nama'] ?? '') ?>"
                               placeholder="Contoh: Budi Santoso" required>
                    </div>
                    <div class="form-group form-group-full">
                        <label for="jurusan">Jurusan / Program Studi <span class="required">*</span></label>
                        <select id="jurusan" name="jurusan" required>
                            <option value="">-- Pilih Jurusan --</option>
                            <?php
                            $jurusans = ['Teknik Informatika', 'Sistem Informasi', 'Manajemen Informatika', 'Teknik Komputer', 'Ilmu Komputer'];
                            foreach ($jurusans as $j):
                                $sel = (($data['jurusan'] ?? '') === $j) ? 'selected' : '';
                            ?>
                            <option value="<?= $j ?>" <?= $sel ?>><?= $j ?></option>
                            <?php endforeach; ?>
                            <option value="Lainnya" <?= (($data['jurusan'] ?? '') === 'Lainnya') ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Informasi Kontak</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email"
                               value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                               placeholder="contoh@email.com" required>
                    </div>
                    <div class="form-group">
                        <label for="no_hp">No. HP <span class="required">*</span></label>
                        <input type="text" id="no_hp" name="no_hp"
                               value="<?= htmlspecialchars($data['no_hp'] ?? '') ?>"
                               placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="form-group form-group-full">
                        <label for="alamat">Alamat</label>
                        <textarea id="alamat" name="alamat" rows="2"
                                  placeholder="Alamat lengkap saat ini"><?= htmlspecialchars($data['alamat'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">Informasi Karir (opsional)</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="pekerjaan">Pekerjaan / Jabatan</label>
                        <input type="text" id="pekerjaan" name="pekerjaan"
                               value="<?= htmlspecialchars($data['pekerjaan'] ?? '') ?>"
                               placeholder="Contoh: Software Engineer">
                    </div>
                    <div class="form-group">
                        <label for="perusahaan">Perusahaan / Instansi</label>
                        <input type="text" id="perusahaan" name="perusahaan"
                               value="<?= htmlspecialchars($data['perusahaan'] ?? '') ?>"
                               placeholder="Contoh: PT Telkom Indonesia">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="dashboard_admin.php" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>
</main>

</body>
</html>
