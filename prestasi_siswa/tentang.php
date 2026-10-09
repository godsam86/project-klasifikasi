<?php
$judulHalaman = 'Tentang Aplikasi';
$subHalaman = 'Informasi sistem dan metode';
require_once __DIR__ . '/includes/header.php';
$kolomMapel = get_kolom_mapel($pdo);
$jumlahSiswa = (int)$pdo->query('SELECT COUNT(*) FROM siswa')->fetchColumn();
?>

<div class="card" style="background:linear-gradient(135deg,#1a2d4d,#0f1e33); color:#fff; text-align:center; padding:32px 16px;">
  <div style="font-size:40px;">🎓</div>
  <div style="font-size:18px; font-weight:800; margin-top:6px;">Sistem Analisis &amp; Prediksi Prestasi Belajar Siswa</div>
  <div style="color:#94a3b8; margin-top:4px;">SD Negeri 060921 Medan – Kelas V</div>
  <div style="color:#64748b; font-size:12px; margin-top:6px;">Versi Web 1.0.0 (PHP + MySQL / XAMPP)</div>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(190px,1fr));">
  <div class="card"><div class="card-body"><div style="font-size:11px; color:var(--subteks); font-weight:700;">METODE UTAMA</div><div style="font-weight:800; margin-top:4px;">Binary Logistic Regression<br><span style="font-weight:400; font-size:12px; color:var(--subteks);">(scikit-learn via Python)</span></div></div></div>
  <div class="card"><div class="card-body"><div style="font-size:11px; color:var(--subteks); font-weight:700;">SEKOLAH</div><div style="font-weight:800; margin-top:4px;">SD Negeri 060921 Medan<br><span style="font-weight:400; font-size:12px; color:var(--subteks);">Kelas V</span></div></div></div>
  <div class="card"><div class="card-body"><div style="font-size:11px; color:var(--subteks); font-weight:700;">DATASET</div><div style="font-weight:800; margin-top:4px;"><?= $jumlahSiswa ?> Siswa<br><span style="font-weight:400; font-size:12px; color:var(--subteks);"><?= count($kolomMapel) ?> Variabel Input</span></div></div></div>
  <div class="card"><div class="card-body"><div style="font-size:11px; color:var(--subteks); font-weight:700;">KKM</div><div style="font-weight:800; margin-top:4px;">Nilai ≥ <?= KKM ?> = Berprestasi<br><span style="font-weight:400; font-size:12px; color:var(--subteks);">Nilai &lt; <?= KKM ?> = Butuh Pembinaan</span></div></div></div>
</div>

<div class="card">
  <div class="card-title">ℹ Tentang Aplikasi</div>
  <div class="card-body" style="line-height:1.7;">
    Aplikasi ini dibuat untuk membantu guru dan pihak sekolah dalam melakukan analisis dan prediksi
    prestasi belajar siswa Kelas V SD Negeri 060921 Medan menggunakan metode Binary Logistic Regression.
    Versi ini merupakan hasil konversi dari aplikasi desktop (Python/Tkinter) menjadi aplikasi web
    berbasis PHP &amp; MySQL yang berjalan di atas XAMPP.
    <br><br>
    <b>Sistem ini mampu:</b>
    <ul>
      <li>Menganalisis nilai siswa dari berbagai mata pelajaran secara otomatis</li>
      <li>Memprediksi apakah siswa termasuk kategori Berprestasi atau Butuh Pembinaan</li>
      <li>Menampilkan evaluasi kinerja model secara detail (Akurasi, Presisi, Recall, F1)</li>
      <li>Mengklasifikasikan seluruh siswa berdasarkan hasil analisis</li>
      <li>Mengekspor seluruh hasil ke format Excel (.xlsx) untuk laporan resmi</li>
    </ul>
    Data yang digunakan dapat diimpor dari file Excel (.xlsx) atau CSV dengan format kolom yang
    fleksibel — sistem otomatis menyesuaikan jumlah dan nama variabel/mata pelajaran sesuai file
    yang diimpor.
  </div>
</div>

<div class="card">
  <div class="card-title">🔄 Alur Kerja Sistem</div>
  <div class="card-body" style="display:flex; flex-wrap:wrap; gap:10px; justify-content:space-between; text-align:center;">
    <?php
    $steps = [
        ['1', 'Import Data', 'Import file Excel/CSV siswa', '#2563eb'],
        ['2', 'Analisis', 'Analisis statistik nilai', '#7c3aed'],
        ['3', 'Prediksi', 'Latih model Logistic Regression', '#059669'],
        ['4', 'Hasil Prediksi', 'Lihat prediksi per siswa', '#0891b2'],
        ['5', 'Klasifikasi', 'Berprestasi / Butuh Pembinaan', '#f59e0b'],
        ['6', 'Evaluasi', 'Evaluasi akurasi model', '#ef4444'],
        ['7', 'Download', 'Export hasil ke Excel', '#2563eb'],
    ];
    foreach ($steps as $s): ?>
      <div style="flex:1; min-width:100px;">
        <div style="width:40px; height:40px; border-radius:50%; background:<?= $s[3] ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; margin:0 auto 6px;"><?= $s[0] ?></div>
        <div style="font-weight:700; font-size:12.5px;"><?= $s[1] ?></div>
        <div style="font-size:11px; color:var(--subteks);"><?= $s[2] ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="card-title">📌 Informasi Teknis</div>
  <div class="card-body">
    <?php
    $tech = [
        ['Bahasa Pemrograman', 'PHP 8.x + Python 3.x (untuk modul Machine Learning)'],
        ['Server', 'Apache (XAMPP)'],
        ['Machine Learning', 'scikit-learn (LogisticRegression) — dipanggil dari PHP via proc_open'],
        ['Data Processing', 'pandas, numpy (Python)'],
        ['Visualisasi', 'Chart.js'],
        ['Database', 'MySQL / MariaDB (XAMPP)'],
        ['Export', 'Penulis .xlsx native PHP (tanpa dependency eksternal)'],
        ['Versi Aplikasi', '1.0.0 (Web)'],
    ];
    foreach ($tech as [$k, $v]): ?>
      <div style="display:flex; padding:4px 0; border-bottom:1px solid #f1f5f9; font-size:13px;">
        <div style="width:220px; color:var(--subteks); flex-shrink:0;"><?= htmlspecialchars($k) ?>:</div>
        <div style="font-weight:600;"><?= htmlspecialchars($v) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
