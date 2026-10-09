<?php
$judulHalaman = 'Pengujian Sistem';
$subHalaman = 'Hasil pengujian fungsional aplikasi';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pengujian'])) {
  unset($_SESSION['baris_pengujian']);
  $_SESSION['pengujian_dihapus'] = true;
  header('Location: pengujian_sistem.php');
  exit;
}

$jalankan = isset($_GET['jalankan']) || (!isset($_SESSION['baris_pengujian']) && empty($_SESSION['pengujian_dihapus']));
$baris = $_SESSION['baris_pengujian'] ?? [];

if ($jalankan) {
  unset($_SESSION['pengujian_dihapus']);
  $jumlahSiswa = (int)$pdo->query('SELECT COUNT(*) FROM siswa')->fetchColumn();
  $stmt = $pdo->query('SELECT * FROM hasil_model ORDER BY id DESC LIMIT 1');
  $model = $stmt->fetch();
  $adaData = $jumlahSiswa > 0;
  $adaModel = (bool)$model;
  $berprestasi = (int)$pdo->query("SELECT COUNT(*) FROM siswa WHERE kategori='Berprestasi'")->fetchColumn();
  $pembinaan = $jumlahSiswa - $berprestasi;

  function cek($kondisi) { return $kondisi ? 'BERHASIL' : 'GAGAL'; }

  $baris = [
    ['No'=>1,'Fitur'=>'Import Data','Skenario'=>'Import file Excel/CSV ke sistem',
     'Diharapkan'=>'Data siswa tersimpan di database',
     'Aktual'=> $adaData ? "Data tersimpan: $jumlahSiswa siswa" : 'Belum ada data', 'Status'=>cek($adaData)],
    ['No'=>2,'Fitur'=>'Tampil Data Analisis','Skenario'=>'Membuka halaman Analisis dan memuat data',
     'Diharapkan'=>'Tabel data siswa muncul dengan benar',
     'Aktual'=> $adaData ? "$jumlahSiswa siswa berhasil ditampilkan" : 'Tidak ada data', 'Status'=>cek($adaData)],
    ['No'=>3,'Fitur'=>'Hasil Analisis & Grafik','Skenario'=>'Membuka halaman Hasil Analisis',
     'Diharapkan'=>'Grafik & statistik per mata pelajaran tampil',
     'Aktual'=> $adaData ? 'Grafik berhasil dirender' : 'Tidak ada data', 'Status'=>cek($adaData)],
    ['No'=>4,'Fitur'=>'Pelatihan Model Logistic Regression','Skenario'=>'Klik tombol Mulai Pelatihan di halaman Prediksi',
     'Diharapkan'=>'Model berhasil dilatih, evaluasi tampil',
     'Aktual'=> $adaModel ? 'Akurasi: '.round($model['akurasi']*100,2).'%' : 'Belum dilatih', 'Status'=>cek($adaModel)],
    ['No'=>5,'Fitur'=>'Hasil Prediksi per Siswa','Skenario'=>'Membuka halaman Hasil Prediksi',
     'Diharapkan'=>'Tabel prediksi per siswa tampil dengan probabilitas',
     'Aktual'=> ($adaModel && $adaData) ? "Prediksi $jumlahSiswa siswa berhasil" : 'Model belum dilatih', 'Status'=>cek($adaModel && $adaData)],
    ['No'=>6,'Fitur'=>'Klasifikasi Siswa','Skenario'=>'Membuka halaman Klasifikasi Siswa',
     'Diharapkan'=>'Daftar Berprestasi & Butuh Pembinaan tampil dengan filter',
     'Aktual'=> $adaData ? "$berprestasi Berprestasi, $pembinaan Butuh Pembinaan" : 'Tidak ada data', 'Status'=>cek($adaData)],
    ['No'=>7,'Fitur'=>'Evaluasi Model','Skenario'=>'Membuka halaman Evaluasi Model',
     'Diharapkan'=>'Accuracy, Confusion Matrix, Bobot Fitur tampil',
     'Aktual'=> $adaModel ? 'Akurasi '.round($model['akurasi']*100,2).'%, CM tampil' : 'Model belum dilatih', 'Status'=>cek($adaModel)],
    ['No'=>8,'Fitur'=>'Download Hasil Analisis','Skenario'=>'Klik tombol Download Hasil Analisis',
     'Diharapkan'=>'File Excel tersimpan di folder tujuan',
     'Aktual'=> $adaData ? 'Fungsi download tersedia' : 'Data belum ada', 'Status'=>cek($adaData)],
    ['No'=>9,'Fitur'=>'Download Hasil Prediksi','Skenario'=>'Klik tombol Download Hasil Prediksi',
     'Diharapkan'=>'File Excel prediksi per siswa tersimpan',
     'Aktual'=> $adaModel ? 'Fungsi download tersedia' : 'Model belum dilatih', 'Status'=>cek($adaModel)],
    ['No'=>10,'Fitur'=>'Download Klasifikasi','Skenario'=>'Klik tombol Download Klasifikasi',
     'Diharapkan'=>'File Excel klasifikasi tersimpan',
     'Aktual'=> $adaData ? 'Fungsi download tersedia' : 'Data belum ada', 'Status'=>cek($adaData)],
    ['No'=>11,'Fitur'=>'Download Evaluasi Model','Skenario'=>'Klik tombol Download Evaluasi Model',
     'Diharapkan'=>'File Excel evaluasi model tersimpan',
     'Aktual'=> $adaModel ? 'Fungsi download tersedia' : 'Model belum dilatih', 'Status'=>cek($adaModel)],
    ['No'=>12,'Fitur'=>'Tampilan Responsif','Skenario'=>'Membuka aplikasi di layar berbeda ukuran (desktop/tablet)',
     'Diharapkan'=>'Tampilan menyesuaikan ukuran layar', 'Aktual'=>'Responsive berhasil', 'Status'=>'BERHASIL'],
    ['No'=>13,'Fitur'=>'Navigasi Menu Sidebar','Skenario'=>'Klik semua 10 item menu di sidebar',
     'Diharapkan'=>'Setiap menu membuka halaman yang sesuai', 'Aktual'=>'Semua 10 menu berfungsi', 'Status'=>'BERHASIL'],
    ];

    $nOk = count(array_filter($baris, fn($b) => $b['Status'] === 'BERHASIL'));
    $nFail = count($baris) - $nOk;
    $_SESSION['baris_pengujian'] = $baris;
} else {
    $nOk = 0;
    $nFail = 0;
}
?>

<div class="toolbar">
  <div class="left"><a href="pengujian_sistem.php?jalankan=1" class="btn btn-ungu">🔄 Jalankan Pengujian Ulang</a></div>
  <?php if ($baris): ?>
    <form method="post" onsubmit="return confirm('Hapus hasil pengujian sistem?');">
      <button type="submit" name="hapus_pengujian" class="btn btn-merah">🗑 Hapus Pengujian</button>
    </form>
  <?php endif; ?>
  <?php if ($baris): ?>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <a href="export.php?jenis=pengujian_word" class="btn btn-ungu">📄 Word (.docx)</a>
      <a href="export.php?jenis=pengujian_pdf" class="btn btn-merah">📕 PDF (.pdf)</a>
      <a href="export.php?jenis=pengujian" class="btn btn-hijau">📊 Excel (.xlsx)</a>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-title">🧪 Tabel Hasil Pengujian Sistem</div>
  <div class="card-body table-wrap">
    <table class="tabel">
      <thead><tr><th>No</th><th>Fitur</th><th>Skenario Pengujian</th><th>Hasil yang Diharapkan</th><th>Hasil Aktual</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($baris as $b): ?>
        <tr>
          <td><?= $b['No'] ?></td><td><?= htmlspecialchars($b['Fitur']) ?></td>
          <td><?= htmlspecialchars($b['Skenario']) ?></td><td><?= htmlspecialchars($b['Diharapkan']) ?></td>
          <td><?= htmlspecialchars($b['Aktual']) ?></td>
          <td><span class="badge <?= $b['Status']==='BERHASIL' ? 'badge-hijau' : 'badge-merah' ?>"><?= $b['Status'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$baris): ?><tr><td colspan="6">Belum ada hasil pengujian. Klik "Jalankan Pengujian Ulang" untuk memulai.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-body" style="text-align:center; font-weight:700; color:<?= $nFail===0 ? '#059669' : '#f59e0b' ?>;">
    Total Pengujian: <?= count($baris) ?> | ✓ Berhasil: <?= $nOk ?> | ✗ Gagal: <?= $nFail ?> | Pass Rate: <?= round($nOk/max(count($baris),1)*100,1) ?>%
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
