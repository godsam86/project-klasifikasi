<?php
$judulHalaman = 'Dashboard';
$subHalaman = 'Ringkasan statistik prestasi belajar siswa';
require_once __DIR__ . '/includes/header.php';

$kolomMapel = get_kolom_mapel($pdo);
$data = ambil_semua_siswa($pdo, $kolomMapel);
$mpLabels = mp_labels($kolomMapel);
$mpKeys = mp_keys($kolomMapel);

$totalSiswa = count($data);
$berprestasi = count(array_filter($data, fn($d) => $d['kategori'] === 'Berprestasi'));
$pembinaan = $totalSiswa - $berprestasi;
$rataUmum = $totalSiswa ? round(array_sum(array_column($data, 'rata_rata')) / $totalSiswa, 2) : 0;
$rataHadir = $totalSiswa ? round(array_sum(array_column($data, 'kehadiran')) / $totalSiswa, 1) : 0;

// Rata-rata per mapel (untuk bar chart)
$rataMapel = [];
foreach ($kolomMapel as [$k, $label]) {
    $vals = array_column($data, $k);
    $rataMapel[] = $vals ? round(array_sum($vals) / count($vals), 2) : 0;
}

// Top 5 & Bottom 5
$urut = $data;
usort($urut, fn($a, $b) => $b['rata_rata'] <=> $a['rata_rata']);
$top5 = array_slice($urut, 0, 5);
$bottom5 = array_slice(array_reverse($urut), 0, 5);
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="ikon" style="background:#2563eb;">👥</div>
    <div><div class="nilai"><?= $totalSiswa ?></div><div class="judul">Total Siswa</div><div class="sub">Kelas V</div></div>
  </div>
  <div class="stat-card">
    <div class="ikon" style="background:#7c3aed;">📚</div>
    <div><div class="nilai"><?= count($mpLabels) ?></div><div class="judul">Mata Pelajaran</div><div class="sub">Variabel input</div></div>
  </div>
  <div class="stat-card">
    <div class="ikon" style="background:#059669;">🏆</div>
    <div><div class="nilai"><?= $berprestasi ?></div><div class="judul">Berprestasi</div><div class="sub">Rata-rata ≥ <?= KKM ?></div></div>
  </div>
  <div class="stat-card">
    <div class="ikon" style="background:#f59e0b;">📖</div>
    <div><div class="nilai"><?= $pembinaan ?></div><div class="judul">Butuh Pembinaan</div><div class="sub">Rata-rata &lt; <?= KKM ?></div></div>
  </div>
  <div class="stat-card">
    <div class="ikon" style="background:#0891b2;">📊</div>
    <div><div class="nilai"><?= $rataUmum ?></div><div class="judul">Rata-rata Umum</div><div class="sub">Kehadiran rata-rata <?= $rataHadir ?>%</div></div>
  </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;" class="dash-grid">
  <div class="card">
    <div class="card-title">🥧 Distribusi Kategori Prestasi</div>
    <div class="card-body"><canvas id="chartPie" height="220"></canvas></div>
  </div>
  <div class="card">
    <div class="card-title">📊 Rata-rata Nilai per Mata Pelajaran</div>
    <div class="card-body"><canvas id="chartBar" height="220"></canvas></div>
  </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;" class="dash-grid">
  <div class="card">
    <div class="card-title">🏅 5 Siswa Nilai Tertinggi</div>
    <div class="card-body table-wrap">
      <table class="tabel">
        <thead><tr><th>Nama</th><th>Rata-rata</th><th>Kategori</th></tr></thead>
        <tbody>
        <?php foreach ($top5 as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['nama']) ?></td>
            <td><?= $s['rata_rata'] ?></td>
            <td><span class="badge <?= $s['kategori'] === 'Berprestasi' ? 'badge-hijau' : 'badge-oranye' ?>"><?= $s['kategori'] ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$top5): ?><tr><td colspan="3">Belum ada data.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-title">📉 5 Siswa Nilai Terendah</div>
    <div class="card-body table-wrap">
      <table class="tabel">
        <thead><tr><th>Nama</th><th>Rata-rata</th><th>Kategori</th></tr></thead>
        <tbody>
        <?php foreach ($bottom5 as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['nama']) ?></td>
            <td><?= $s['rata_rata'] ?></td>
            <td><span class="badge <?= $s['kategori'] === 'Berprestasi' ? 'badge-hijau' : 'badge-oranye' ?>"><?= $s['kategori'] ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$bottom5): ?><tr><td colspan="3">Belum ada data.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-title">⚡ Menu Cepat</div>
  <div class="card-body" style="display:flex; gap:10px; flex-wrap:wrap;">
    <a class="btn" href="import_data.php">📁 Import Data</a>
    <a class="btn btn-ungu" href="prediksi.php">🔮 Latih Model Prediksi</a>
    <a class="btn btn-hijau" href="analisis.php">📊 Lihat Analisis</a>
    <a class="btn btn-oranye" href="klasifikasi.php">🎓 Klasifikasi Siswa</a>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartPie'), {
  type: 'pie',
  data: {
    labels: ['Berprestasi', 'Butuh Pembinaan'],
    datasets: [{ data: [<?= $berprestasi ?>, <?= $pembinaan ?>], backgroundColor: ['#059669', '#f59e0b'] }]
  },
  options: { plugins: { legend: { position: 'bottom' } } }
});
new Chart(document.getElementById('chartBar'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($mpLabels) ?>,
    datasets: [{ label: 'Rata-rata', data: <?= json_encode($rataMapel) ?>, backgroundColor: '#2563eb' }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
