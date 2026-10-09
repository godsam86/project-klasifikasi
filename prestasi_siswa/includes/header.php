<?php
/**
 * Dipanggil di awal tiap halaman terproteksi.
 * Membutuhkan variabel $judulHalaman dan $subHalaman (opsional) sudah didefinisikan,
 * serta $halamanAktif = kunci menu (lihat MENU_ITEMS).
 */
require_once __DIR__ . '/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_login();
$pengguna = $_SESSION['pengguna'];

$MENU_ITEMS = [
    ['dashboard.php', '🏠', 'Dashboard'],
    ['import_data.php', '📁', 'Import Data'],
    ['analisis.php', '📊', 'Analisis'],
    ['hasil_analisis.php', '📈', 'Hasil Analisis'],
    ['prediksi.php', '🔮', 'Prediksi'],
    ['hasil_prediksi.php', '📋', 'Hasil Prediksi'],
    ['klasifikasi.php', '🎓', 'Klasifikasi Siswa'],
    ['evaluasi_model.php', '🎯', 'Evaluasi Model'],
    ['rekomendasi_prestasi.php', '🎓', 'Rekomendasi Prestasi Siswa'],
    ['pengujian_sistem.php', '🧪', 'Pengujian Sistem'],
    ['tentang.php', 'ℹ️', 'Tentang Aplikasi'],
];
$halamanSekarang = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($judulHalaman ?? 'Sistem Prestasi Siswa') ?> · SD Negeri 060921 Medan</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-wrap">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="badge">🎓</div>
      <strong>SISTEM PRESTASI SISWA</strong>
      <small>Logistic Regression</small>
      <small>SD Negeri 060921 Medan</small>
    </div>
    <div class="menu-label">MENU UTAMA</div>
    <nav>
      <?php foreach ($MENU_ITEMS as [$file, $ikon, $label]): ?>
        <a class="menu-item <?= $halamanSekarang === $file ? 'aktif' : '' ?>" href="<?= $file ?>">
          <span><?= $ikon ?></span><span><?= $label ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="user-box">
      <div class="row">
        <div class="avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($pengguna['nama_lengkap'], 0, 1))) ?></div>
        <div>
          <div class="nama"><?= htmlspecialchars($pengguna['nama_lengkap']) ?></div>
          <div class="username">@<?= htmlspecialchars($pengguna['username']) ?></div>
        </div>
      </div>
      <a class="logout-link" href="logout.php">⎋ Keluar</a>
      <div class="copyright">© 2025 - Semua Hak Dilindungi</div>
    </div>
  </aside>

  <div class="main">
    <div class="topbar">
      <div>
        <h1><?= htmlspecialchars($judulHalaman ?? '') ?></h1>
        <?php if (!empty($subHalaman)): ?><div class="sub"><?= htmlspecialchars($subHalaman) ?></div><?php endif; ?>
      </div>
      <div class="waktu" id="jamSekarang"></div>
    </div>
    <div class="content">
