<?php
$judulHalaman = 'Evaluasi Model';
$subHalaman = 'Metrik performa model Binary Logistic Regression';
require_once __DIR__ . '/includes/header.php';

$stmt = $pdo->query('SELECT * FROM hasil_model ORDER BY id DESC LIMIT 1');
$model = $stmt->fetch();
?>

<?php if (!$model): ?>
  <div class="alert alert-info">
    Model belum pernah dilatih. Silakan buka halaman <a href="prediksi.php"><b>Prediksi</b></a> untuk melatih model terlebih dahulu.
  </div>
<?php else:
  $cm = json_decode($model['cm_json'], true) ?: [[0,0],[0,0]];
  $bobot = json_decode($model['bobot_json'], true) ?: [];
  $tn = $cm[0][0] ?? 0; $fp = $cm[0][1] ?? 0; $fn = $cm[1][0] ?? 0; $tp = $cm[1][1] ?? 0;
?>

<div class="toolbar">
  <div class="left">Dilatih pada: <b><?= format_tanggal($model['waktu']) ?></b></div>
  <div style="display:flex; gap:8px; flex-wrap:wrap;">
    <a href="export.php?jenis=evaluasi_word" class="btn btn-ungu">📄 Word (.docx)</a>
    <a href="export.php?jenis=evaluasi_pdf" class="btn btn-merah">📕 PDF (.pdf)</a>
    <a href="export.php?jenis=evaluasi" class="btn btn-hijau">📊 Excel (.xlsx)</a>
  </div>
</div>

<div class="card">
  <div class="card-title">🎯 Metrik Evaluasi</div>
  <div class="card-body">
    <div class="metric-grid">
      <div class="metric-card"><div class="angka" style="color:#2563eb;"><?= round($model['akurasi']*100,2) ?>%</div><div class="label">Akurasi</div></div>
      <div class="metric-card"><div class="angka" style="color:#059669;"><?= round($model['presisi']*100,2) ?>%</div><div class="label">Presisi</div></div>
      <div class="metric-card"><div class="angka" style="color:#f59e0b;"><?= round($model['recall_score']*100,2) ?>%</div><div class="label">Recall</div></div>
      <div class="metric-card"><div class="angka" style="color:#7c3aed;"><?= round($model['f1_score']*100,2) ?>%</div><div class="label">F1-Score</div></div>
    </div>
  </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;" class="dash-grid">
  <div class="card">
    <div class="card-title">🧩 Confusion Matrix</div>
    <div class="card-body" style="display:flex; justify-content:center;">
      <table class="cm-table">
        <tr><th></th><th>Prediksi:<br>Butuh Pembinaan</th><th>Prediksi:<br>Berprestasi</th></tr>
        <tr><th>Aktual:<br>Butuh Pembinaan</th><td class="cm-cell-hi"><?= $tn ?></td><td class="cm-cell-lo"><?= $fp ?></td></tr>
        <tr><th>Aktual:<br>Berprestasi</th><td class="cm-cell-lo"><?= $fn ?></td><td class="cm-cell-hi"><?= $tp ?></td></tr>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-title">⚖️ Bobot Fitur (Feature Importance)</div>
    <div class="card-body"><canvas id="chartBobot" height="240"></canvas></div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartBobot'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_keys($bobot)) ?>,
    datasets: [{ label: 'Koefisien Absolut', data: <?= json_encode(array_values($bobot)) ?>, backgroundColor: '#7c3aed' }]
  },
  options: { indexAxis: 'y', plugins: { legend: { display: false } } }
});
</script>

<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
