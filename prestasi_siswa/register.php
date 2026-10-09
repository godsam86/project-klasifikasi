<?php
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['pengguna'])) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_lengkap'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';
    if ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        [$ok, $pesan] = daftar_pengguna($pdo, $nama, $username, $password);
        if ($ok) { header('Location: login.php?daftar=1'); exit; }
        $error = $pesan;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar · Sistem Prestasi Siswa</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <h2>🎓 Buat Akun Baru</h2>
    <p class="sub">SD Negeri 060921 Medan · Kelas V</p>
    <?php if ($error): ?><div class="alert alert-gagal"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <div class="form-group">
        <label>Nama Lengkap</label>
        <input type="text" name="nama_lengkap" required autofocus>
      </div>
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required>
        <div class="hint">Minimal 4 karakter: huruf, angka, titik, garis bawah.</div>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
        <div class="hint">Minimal 6 karakter.</div>
      </div>
      <div class="form-group">
        <label>Konfirmasi Password</label>
        <input type="password" name="konfirmasi" required>
      </div>
      <button type="submit" class="btn btn-hijau" style="width:100%; justify-content:center; padding:11px;">Daftar</button>
    </form>
    <div class="auth-switch">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
  </div>
</div>
</body>
</html>
