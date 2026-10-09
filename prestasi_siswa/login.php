<?php
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['pengguna'])) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    [$ok, $pesan, $data] = login_pengguna($pdo, $username, $password);
    if ($ok) {
        $_SESSION['pengguna'] = $data;
        header('Location: dashboard.php');
        exit;
    }
    $error = $pesan;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk · Sistem Prestasi Siswa</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <h2>🎓 Sistem Prestasi Siswa</h2>
    <p class="sub">SD Negeri 060921 Medan · Kelas V</p>
    <?php if ($error): ?><div class="alert alert-gagal"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if (!empty($_GET['daftar'])): ?><div class="alert alert-sukses">Registrasi berhasil! Silakan login.</div><?php endif; ?>
    <form method="post">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn" style="width:100%; justify-content:center; padding:11px;">Masuk</button>
    </form>
    <div class="auth-switch">Belum punya akun? <a href="register.php">Daftar di sini</a></div>
  </div>
</div>
</body>
</html>
