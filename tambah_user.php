<?php
session_start();
require 'koneksi.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

$errors = [];
$old    = ['username' => '', 'role' => 'user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $role     = trim($_POST['role']     ?? 'user');

    // Validasi
    if ($username === '') {
        $errors['username'] = 'Username wajib diisi.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors['username'] = 'Username harus antara 3–50 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Username hanya boleh huruf, angka, dan underscore.';
    } else {
        // Cek duplikat
        $chk = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $chk->execute([$username]);
        if ($chk->fetch()) {
            $errors['username'] = 'Username sudah digunakan, pilih yang lain.';
        }
    }

    if ($password === '') {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password minimal 6 karakter.';
    }

    if ($confirm === '') {
        $errors['confirm'] = 'Konfirmasi password wajib diisi.';
    } elseif ($confirm !== $password) {
        $errors['confirm'] = 'Konfirmasi password tidak cocok.';
    }

    if (!in_array($role, ['user', 'admin', 'superadmin'])) {
        $errors['role'] = 'Role tidak valid.';
    }

    $old = compact('username', 'role');

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hash, $role]);

        $_SESSION['flash'] = [
            'type' => 'success',
            'msg'  => "Akun \"$username\" berhasil ditambahkan."
        ];
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
    <title>Tambah User – Alumni Portal</title>
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
                <h1 class="page-title">Tambah Akun</h1>
                <p class="page-subtitle">Buat akun pengguna baru</p>
            </div>
            <a href="users.php" class="btn-secondary">← Kembali</a>
        </div>

        <div class="form-wrapper">
            <form method="POST" action="tambah_user.php" class="form-card" novalidate>

                <!-- Username -->
                <div class="form-group <?= isset($errors['username']) ? 'has-error' : '' ?>">
                    <label for="username" class="form-label">Username <span class="required">*</span></label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        value="<?= htmlspecialchars($old['username']) ?>"
                        placeholder="Contoh: john_doe"
                        maxlength="50"
                        autofocus>
                    <?php if (isset($errors['username'])): ?>
                        <span class="form-error"><?= $errors['username'] ?></span>
                    <?php else: ?>
                        <span class="form-hint">3–50 karakter, hanya huruf, angka, dan underscore.</span>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div class="form-group <?= isset($errors['password']) ? 'has-error' : '' ?>">
                    <label for="password" class="form-label">Password <span class="required">*</span></label>
                    <div class="input-password-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Minimal 6 karakter"
                            maxlength="255">
                        <button type="button" class="btn-toggle-pw" onclick="togglePw('password', this)" title="Tampilkan password">
                            👁
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <span class="form-error"><?= $errors['password'] ?></span>
                    <?php else: ?>
                        <span class="form-hint">Minimal 6 karakter.</span>
                    <?php endif; ?>
                </div>

                <!-- Konfirmasi Password -->
                <div class="form-group <?= isset($errors['confirm']) ? 'has-error' : '' ?>">
                    <label for="confirm" class="form-label">Konfirmasi Password <span class="required">*</span></label>
                    <div class="input-password-wrap">
                        <input
                            type="password"
                            id="confirm"
                            name="confirm"
                            class="form-input"
                            placeholder="Ulangi password"
                            maxlength="255">
                        <button type="button" class="btn-toggle-pw" onclick="togglePw('confirm', this)" title="Tampilkan password">
                            👁
                        </button>
                    </div>
                    <?php if (isset($errors['confirm'])): ?>
                        <span class="form-error"><?= $errors['confirm'] ?></span>
                    <?php endif; ?>
                </div>

                <!-- Role -->
                <div class="form-group <?= isset($errors['role']) ? 'has-error' : '' ?>">
                    <label class="form-label">Role <span class="required">*</span></label>
                    <div class="role-options">
                        <?php foreach (['user' => 'User', 'admin' => 'Admin', 'superadmin' => 'Superadmin'] as $val => $label): ?>
                            <label class="role-card <?= $old['role'] === $val ? 'role-card--active' : '' ?>">
                                <input
                                    type="radio"
                                    name="role"
                                    value="<?= $val ?>"
                                    <?= $old['role'] === $val ? 'checked' : '' ?>
                                    onchange="updateRoleCards(this)">
                                <span class="role-card-label"><?= $label ?></span>
                                <span class="role-card-desc">
                                    <?php if ($val === 'user'): ?>
                                        Akses hanya lihat data alumni
                                    <?php elseif ($val === 'admin'): ?>
                                        Bisa tambah & edit data alumni
                                    <?php else: ?>
                                        Akses penuh termasuk kelola akun
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($errors['role'])): ?>
                        <span class="form-error"><?= $errors['role'] ?></span>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <a href="users.php" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary">Simpan Akun</button>
                </div>

            </form>
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
        // Toggle show/hide password
        function togglePw(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.textContent = isHidden ? '🙈' : '👁';
        }

        // Highlight role card saat dipilih
        function updateRoleCards(radio) {
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('role-card--active');
            });
            radio.closest('.role-card').classList.add('role-card--active');
        }
    </script>

</body>

</html>