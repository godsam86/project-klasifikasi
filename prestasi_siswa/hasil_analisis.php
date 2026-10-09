<?php
$judulHalaman = 'Hasil Analisis';
$subHalaman = 'Statistik per mata pelajaran & hubungan variabel dengan prestasi';
require_once __DIR__ . '/includes/header.php';

$kolomMapel = get_kolom_mapel($pdo);
$data = ambil_semua_siswa($pdo, $kolomMapel);
$statMapel = statistik_mapel($data, $kolomMapel);

// Hubungan kehadiran dengan prestasi: rata-rata kehadiran per kategori
$hadirBerprestasi = array_column(array_filter($data, fn($d) => $d['kategori'] === 'Berprestasi'), 'kehadiran');
$hadirPembinaan = array_column(array_filter($data, fn($d) => $d['kategori'] !== 'Berprestasi'), 'kehadiran');
$avgHadirBerprestasi = $hadirBerprestasi ? round(array_sum($hadirBerprestasi) / count($hadirBerprestasi), 1) : 0;
$avgHadirPembinaan = $hadirPembinaan ? round(array_sum($hadirPembinaan) / count($hadirPembinaan), 1) : 0;

// Hubungan kedisiplinan dengan prestasi: jumlah siswa per (kedisiplinan x kategori)
$disiplinLevels = ['Baik', 'Cukup', 'Kurang'];
$disiplinBerprestasi = []; $disiplinPembinaan = [];
foreach ($disiplinLevels as $lvl) {
    $disiplinBerprestasi[] = count(array_filter($data, fn($d) => kategori_kedisiplinan($d['kedisiplinan']) === $lvl && $d['kategori'] === 'Berprestasi'));
    $disiplinPembinaan[] = count(array_filter($data, fn($d) => kategori_kedisiplinan($d['kedisiplinan']) === $lvl && $d['kategori'] !== 'Berprestasi'));
}
?>

<div class="toolbar">
  <div style="display:flex; gap:8px; flex-wrap:wrap;">
    <a href="export.php?jenis=hasil_analisis_word" class="btn btn-ungu">📄 Word (.docx)</a>
    <a href="export.php?jenis=hasil_analisis_pdf" class="btn btn-merah">📕 PDF (.pdf)</a>
    <a href="export.php?jenis=hasil_analisis" class="btn btn-hijau">📊 Excel (.xlsx)</a>
  </div>
</div>

<div class="card">
  <div class="card-title">📈 Statistik per Mata Pelajaran</div>
  <div class="card-body table-wrap">
    <table class="tabel">
      <thead><tr><th>Mata Pelajaran</th><th>Min</th><th>Maks</th><th>Rata-rata</th><th>Std Dev</th><th>Median</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($statMapel as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['label']) ?></td>
          <td><?= $s['min'] ?></td><td><?= $s['max'] ?></td><td><b><?= $s['rata'] ?></b></td>
          <td><?= $s['std'] ?></td><td><?= $s['median'] ?></td>
          <td><span class="badge <?= $s['status'] === 'Di atas KKM' ? 'badge-hijau' : 'badge-oranye' ?>"><?= $s['status'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$statMapel): ?><tr><td colspan="7">Belum ada data.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-title">📊 Grafik Rata-rata Nilai per Mata Pelajaran</div>
  <div class="card-body"><canvas id="chartBarMapel" height="220"></canvas></div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;" class="dash-grid">
  <div class="card">
    <div class="card-title">🎯 Hubungan Kehadiran dengan Prestasi</div>
    <div class="card-body"><canvas id="chartHadir" height="220"></canvas></div>
  </div>
  <div class="card">
    <div class="card-title">🧭 Hubungan Kedisiplinan dengan Prestasi</div>
    <div class="card-body"><canvas id="chartDisiplin" height="220"></canvas></div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartBarMapel'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($statMapel, 'label')) ?>,
    datasets: [{ label: 'Rata-rata', data: <?= json_encode(array_column($statMapel, 'rata')) ?>, backgroundColor: '#2563eb' }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } }
});
new Chart(document.getElementById('chartHadir'), {
  type: 'bar',
  data: {
    labels: ['Berprestasi', 'Butuh Pembinaan'],
    datasets: [{ label: 'Rata-rata Kehadiran (%)', data: [<?= $avgHadirBerprestasi ?>, <?= $avgHadirPembinaan ?>], backgroundColor: ['#059669', '#f59e0b'] }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } }
});
new Chart(document.getElementById('chartDisiplin'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($disiplinLevels) ?>,
    datasets: [
      { label: 'Berprestasi', data: <?= json_encode($disiplinBerprestasi) ?>, backgroundColor: '#059669' },
      { label: 'Butuh Pembinaan', data: <?= json_encode($disiplinPembinaan) ?>, backgroundColor: '#f59e0b' }
    ]
  },
  options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
