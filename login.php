<?php
session_start();
require 'koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['password'] === $password) {
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            if ($user['role'] === 'admin' || $user['role'] === 'superadmin') {
                header('Location: dashboard_admin.php');
            } else {
                header('Location: dashboard_user.php');
            }
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        $error = 'Harap isi username dan password!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Manajemen Data Alumni</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/index.css">
</head>

<body>
    <div class="login-wrapper">
        <div class="login-deco"></div>
        <div class="login-card">
            <div class="login-brand">
                <div class="brand-icon"><svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 24 24">
                        <path fill="#1c4e82" d="M5 13.18v4L12 21l7-3.82v-4L12 17zM12 3L1 9l11 6l9-4.91V17h2V9z" stroke-width="0.5" stroke="#1c4e82" />
                    </svg></div>
                <h1>Alumni Portal</h1>
                <p>Sistem Manajemen Data Alumni</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                        placeholder="Masukkan username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                        placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn-login">Masuk →</button>
            </form>

            <p class="login-hint">
                Demo: <code>admin</code> / <code>admin</code> &nbsp;|&nbsp;
                <code>user</code> / <code>user</code>
            </p>
            <br>
            <code>Ingin membuat akun baru? <a href="https://wa.me/+6282294451306">Hubungi Superadmin</a></code>
        </div>
    </div>
</body>

</html>