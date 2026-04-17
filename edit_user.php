<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: users.php');
    exit;
}

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Akun tidak ditemukan.'];
    header('Location: users.php');
    exit;
}

// Proteksi: tidak boleh edit diri sendiri
if ($user['username'] === $_SESSION['username']) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Tidak dapat mengedit akun Anda sendiri.'];
    header('Location: users.php');
    exit;
}

$errors = [];
$data   = $user; // pre-fill dengan data lama

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['username'] = trim($_POST['username'] ?? '');
    $data['role']     = trim($_POST['role']     ?? 'user');
    $password         = trim($_POST['password'] ?? '');
    $confirm          = trim($_POST['confirm']  ?? '');

    // Validasi username
    if (!$data['username']) {
        $errors[] = 'Username wajib diisi.';
    } elseif (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
        $errors[] = 'Username harus antara 3–50 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
        $errors[] = 'Username hanya boleh huruf, angka, dan underscore.';
    } else {
        // Cek duplikat (kecuali milik sendiri)
        $chk = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $chk->execute([$data['username'], $id]);
        if ($chk->fetch()) {
            $errors[] = 'Username sudah digunakan akun lain.';
        }
    }

    // Validasi password (opsional — kosong = tidak diganti)
    if ($password !== '') {
        if (strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter.';
        }
        if ($confirm === '') {
            $errors[] = 'Konfirmasi password wajib diisi.';
        } elseif ($confirm !== $password) {
            $errors[] = 'Konfirmasi password tidak cocok.';
        }
    }

    // Validasi role
    if (!in_array($data['role'], ['user', 'admin', 'superadmin'])) {
        $errors[] = 'Role tidak valid.';
    }

    if (empty($errors)) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE user_id = ?");
            $stmt->execute([$data['username'], $hash, $data['role'], $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE user_id = ?");
            $stmt->execute([$data['username'], $data['role'], $id]);
        }

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Data akun berhasil diperbarui!'];
        header('Location: users.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User – <?= htmlspecialchars($user['username']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/edit.css">
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

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Edit Akun</h1>
                <p class="page-subtitle">Mengedit akun: <strong><?= htmlspecialchars($user['username']) ?></strong></p>
            </div>
            <a href="users.php" class="btn-back">← Kembali</a>
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

                <!-- Informasi Akun -->
                <div class="form-section">
                    <h2 class="section-title">Informasi Akun</h2>
                    <div class="form-grid">

                        <!-- ID (readonly) -->
                        <div class="form-group">
                            <label>ID Akun</label>
                            <input type="text" value="<?= $user['user_id'] ?>" disabled class="input-disabled">
                        </div>

                        <!-- Username -->
                        <div class="form-group">
                            <label for="username">Username <span class="required">*</span></label>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="<?= htmlspecialchars($data['username']) ?>"
                                placeholder="Contoh: john_doe"
                                maxlength="50"
                                required>
                            <small class="form-hint">3–50 karakter, huruf/angka/underscore saja.</small>
                        </div>

                        <!-- Role -->
                        <div class="form-group form-group-full">
                            <label class="form-label">Role <span class="required">*</span></label>
                            <div class="role-options">
                                <?php foreach (
                                    [
                                        'user' => ['label' => 'User', 'desc' => 'Akses hanya lihat data alumni'],
                                        'admin' => ['label' => 'Admin', 'desc' => 'Bisa tambah & edit data alumni'],
                                        'superadmin' => ['label' => 'Superadmin', 'desc' => 'Akses penuh termasuk kelola akun']
                                    ]
                                    as $val => $info
                                ): ?>
                                    <label class="role-card <?= $data['role'] === $val ? 'role-card--active' : '' ?>">
                                        <input
                                            type="radio"
                                            name="role"
                                            value="<?= $val ?>"
                                            <?= $data['role'] === $val ? 'checked' : '' ?>
                                            onchange="updateRoleCards(this)">
                                        <span class="role-card-label"><?= $info['label'] ?></span>
                                        <span class="role-card-desc"><?= $info['desc'] ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Ganti Password -->
                <div class="form-section">
                    <h2 class="section-title">Ganti Password</h2>
                    <div class="form-grid">

                        <div class="form-group">
                            <label for="password">Password Baru</label>
                            <div class="input-password-wrap" style="height: 30px;">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="  Kosongkan jika tidak ingin ganti"
                                    maxlength="255">
                                <button type="button" class="btn-toggle-pw" onclick="togglePw('password', this)">👁</button>
                            </div>
                            <small class="form-hint">Minimal 6 karakter.</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm">Konfirmasi Password Baru</label>
                            <div class="input-password-wrap" style="height: 30px;">
                                <input
                                    type="password"
                                    id="confirm"
                                    name="confirm"
                                    placeholder="  Ulangi password baru"
                                    maxlength="255">
                                <button type="button" class="btn-toggle-pw" onclick="togglePw('confirm', this)">👁</button>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="form-actions">
                    <a href="users.php" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </div>

            </form>
        </div>
    </main>

    <script>
        function togglePw(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.textContent = isHidden ? '🙈' : '👁';
        }

        function updateRoleCards(radio) {
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('role-card--active');
            });
            radio.closest('.role-card').classList.add('role-card--active');
        }
    </script>

</body>

</html>