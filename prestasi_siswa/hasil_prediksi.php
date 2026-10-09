<?php
$judulHalaman = 'Hasil Prediksi';
$subHalaman = 'Prediksi kategori prestasi per siswa hasil model';
require_once __DIR__ . '/includes/header.php';

$prediksi = $_SESSION['hasil_prediksi'] ?? null;
?>

<?php if (!$prediksi): ?>
  <div class="alert alert-info">
    Model belum dilatih pada sesi ini. Silakan buka halaman <a href="prediksi.php"><b>Prediksi</b></a> dan klik "Mulai Pelatihan Model" terlebih dahulu.
  </div>
<?php else: ?>

<div class="toolbar">
  <div class="left">
    <input type="text" id="cariInput" placeholder="🔍 Cari nama siswa..." style="padding:8px 12px; border:1px solid var(--border); border-radius:5px; min-width:220px;">
    <select id="filterHasil" style="padding:8px 12px; border:1px solid var(--border); border-radius:5px;">
      <option value="">Semua Hasil Prediksi</option>
      <option value="Berprestasi">Berprestasi</option>
      <option value="Butuh Pembinaan">Butuh Pembinaan</option>
    </select>
  </div>
  <div style="display:flex; gap:8px; flex-wrap:wrap;">
    <a href="export.php?jenis=hasil_prediksi_word" class="btn btn-ungu">📄 Word (.docx)</a>
    <a href="export.php?jenis=hasil_prediksi_pdf" class="btn btn-merah">📕 PDF (.pdf)</a>
    <a href="export.php?jenis=hasil_prediksi" class="btn btn-hijau">📊 Excel (.xlsx)</a>
  </div>
</div>

<div class="card">
  <div class="card-title">📋 Tabel Hasil Prediksi (<span id="jmlBaris"><?= count($prediksi) ?></span> siswa)</div>
  <div class="card-body table-wrap">
    <table class="tabel">
      <thead><tr><th>Nama</th><th>Rata-rata</th><th>Kategori Aktual</th><th>Hasil Prediksi</th><th>Probabilitas</th></tr></thead>
      <tbody>
      <?php foreach ($prediksi as $p): ?>
        <tr data-nama="<?= htmlspecialchars(mb_strtolower($p['nama'])) ?>" data-prediksi="<?= htmlspecialchars($p['prediksi']) ?>">
          <td><?= htmlspecialchars($p['nama']) ?></td>
          <td><?= $p['rata_rata'] ?></td>
          <td><span class="badge <?= $p['kategori'] === 'Berprestasi' ? 'badge-hijau' : 'badge-oranye' ?>"><?= $p['kategori'] ?></span></td>
          <td><span class="badge <?= $p['prediksi'] === 'Berprestasi' ? 'badge-biru' : 'badge-merah' ?>"><?= $p['prediksi'] ?></span></td>
          <td><?= $p['probabilitas'] ?>%</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const cariInput = document.getElementById('cariInput');
const filterHasil = document.getElementById('filterHasil');
const tbody = document.querySelector('.tabel tbody');
const jmlBaris = document.getElementById('jmlBaris');
function terapkanFilter(){
  const q = cariInput.value.trim().toLowerCase();
  const f = filterHasil.value;
  let tampil = 0;
  tbody.querySelectorAll('tr[data-nama]').forEach(tr => {
    const show = (!q || tr.dataset.nama.includes(q)) && (!f || tr.dataset.prediksi === f);
    tr.style.display = show ? '' : 'none';
    if (show) tampil++;
  });
  jmlBaris.textContent = tampil;
}
cariInput.addEventListener('input', terapkanFilter);
filterHasil.addEventListener('change', terapkanFilter);
</script>

<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
