<?php
$judulHalaman = 'Prediksi';
$subHalaman = 'Latih model Binary Logistic Regression';
require_once __DIR__ . '/includes/header.php';

$kolomMapel = get_kolom_mapel($pdo);
$jumlahSiswa = (int)$pdo->query('SELECT COUNT(*) FROM siswa')->fetchColumn();

$stmt = $pdo->query('SELECT * FROM hasil_model ORDER BY id DESC LIMIT 1');
$modelTerakhir = $stmt->fetch();
?>

<div class="card">
  <div class="card-title">🔮 Pelatihan Model Binary Logistic Regression</div>
  <div class="card-body">
    <p style="margin-top:0; color:var(--subteks); font-size:13px;">
      Model akan dilatih menggunakan seluruh data siswa (<b><?= $jumlahSiswa ?> siswa</b>) dengan variabel input:
      nilai <?= count($kolomMapel) ?> mata pelajaran, Kehadiran, dan Kedisiplinan.
      Data akan dibagi 80% data latih dan 20% data uji secara stratified random sampling (KKM = <?= KKM ?>).
    </p>
    <?php if ($modelTerakhir): ?>
      <div class="alert alert-info">
        Model terakhir dilatih pada <b><?= format_tanggal($modelTerakhir['waktu']) ?></b> —
        Akurasi: <b><?= round($modelTerakhir['akurasi'] * 100, 2) ?>%</b>
      </div>
    <?php endif; ?>
    <button id="btnLatih" class="btn btn-ungu" <?= $jumlahSiswa < 10 ? 'disabled' : '' ?>>🚀 Mulai Pelatihan Model</button>
    <?php if ($jumlahSiswa < 10): ?>
      <div class="hint" style="color:var(--merah);">Minimal 10 siswa dibutuhkan untuk melatih model. Silakan import data terlebih dahulu.</div>
    <?php endif; ?>
  </div>
</div>

<div class="card" id="cardLog" style="display:none;">
  <div class="card-title">📜 Log Proses Pelatihan</div>
  <div class="card-body">
    <div class="log-box" id="logBox">Menunggu proses dimulai...</div>
  </div>
</div>

<div class="card" id="cardHasil" style="display:none;">
  <div class="card-title">✅ Ringkasan Hasil Pelatihan</div>
  <div class="card-body">
    <div class="metric-grid" id="ringkasanMetrik"></div>
    <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
      <a href="hasil_prediksi.php" class="btn">📋 Lihat Hasil Prediksi</a>
      <a href="evaluasi_model.php" class="btn btn-oranye">🎯 Lihat Evaluasi Model</a>
    </div>
  </div>
</div>

<script>
document.getElementById('btnLatih').addEventListener('click', async function () {
  const btn = this;
  btn.disabled = true;
  btn.textContent = '⏳ Melatih model...';
  document.getElementById('cardLog').style.display = 'block';
  document.getElementById('logBox').textContent = 'Mengirim data ke Python & memulai pelatihan...';
  document.getElementById('cardHasil').style.display = 'none';

  try {
    const resp = await fetch('train_model.php', { method: 'POST' });
    const isi = await resp.text();
    let hasil;
    try {
      hasil = JSON.parse(isi);
    } catch (parseError) {
      throw new Error(`Server mengirim respons bukan JSON (${resp.status}). ${isi.replace(/<[^>]*>/g, ' ').trim().slice(0, 300)}`);
    }
    if (!hasil.sukses) {
      document.getElementById('logBox').textContent = '❌ GAGAL: ' + hasil.error;
      btn.textContent = '🚀 Mulai Pelatihan Model';
      btn.disabled = false;
      return;
    }
    document.getElementById('logBox').textContent = hasil.log.join('\n');
    const m = document.getElementById('ringkasanMetrik');
    m.innerHTML = `
      <div class="metric-card"><div class="angka" style="color:#2563eb;">${(hasil.akurasi*100).toFixed(2)}%</div><div class="label">Akurasi</div></div>
      <div class="metric-card"><div class="angka" style="color:#059669;">${(hasil.presisi*100).toFixed(2)}%</div><div class="label">Presisi</div></div>
      <div class="metric-card"><div class="angka" style="color:#f59e0b;">${(hasil.recall*100).toFixed(2)}%</div><div class="label">Recall</div></div>
      <div class="metric-card"><div class="angka" style="color:#7c3aed;">${(hasil.f1*100).toFixed(2)}%</div><div class="label">F1-Score</div></div>
    `;
    document.getElementById('cardHasil').style.display = 'block';
    btn.textContent = '🚀 Latih Ulang Model';
    btn.disabled = false;
  } catch (e) {
    document.getElementById('logBox').textContent = '❌ Terjadi kesalahan: ' + e;
    btn.textContent = '🚀 Mulai Pelatihan Model';
    btn.disabled = false;
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
