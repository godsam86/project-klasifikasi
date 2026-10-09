<?php
$judulHalaman = 'Klasifikasi Siswa';
$subHalaman = 'Daftar lengkap siswa berdasarkan kategori prestasi';
require_once __DIR__ . '/includes/header.php';

$pesan = '';
$pesanTipe = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_data'])) {
  try {
    $jumlah = hapus_data_siswa($pdo);
    $pesan = $jumlah > 0
      ? "Berhasil menghapus semua $jumlah data siswa dan hasil model yang tersimpan."
      : 'Data siswa sudah kosong.';
    $pesanTipe = 'sukses';
  } catch (Throwable $e) {
    $pesan = 'Gagal menghapus data: ' . $e->getMessage();
    $pesanTipe = 'gagal';
  }
}

$kolomMapel = get_kolom_mapel($pdo);
$data = ambil_semua_siswa($pdo, $kolomMapel);
$berprestasi = count(array_filter($data, fn($d) => $d['kategori'] === 'Berprestasi'));
$pembinaan = count($data) - $berprestasi;
?>

<?php if ($pesan): ?>
  <div class="alert alert-<?= $pesanTipe ?>"><?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
  <div class="stat-card"><div class="ikon" style="background:#059669;">🏆</div><div><div class="nilai"><?= $berprestasi ?></div><div class="judul">Berprestasi</div></div></div>
  <div class="stat-card"><div class="ikon" style="background:#f59e0b;">📖</div><div><div class="nilai"><?= $pembinaan ?></div><div class="judul">Butuh Pembinaan</div></div></div>
  <div class="stat-card"><div class="ikon" style="background:#2563eb;">👥</div><div><div class="nilai"><?= count($data) ?></div><div class="judul">Total Siswa</div></div></div>
</div>

<div class="toolbar">
  <div class="left">
    <input type="text" id="cariInput" placeholder="🔍 Cari nama siswa..." style="padding:8px 12px; border:1px solid var(--border); border-radius:5px; min-width:220px;">
    <select id="filterKategori" style="padding:8px 12px; border:1px solid var(--border); border-radius:5px;">
      <option value="">Semua</option>
      <option value="Berprestasi">Berprestasi</option>
      <option value="Butuh Pembinaan">Butuh Pembinaan</option>
    </select>
  </div>
  <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
    <a href="export.php?jenis=klasifikasi_word" class="btn btn-ungu">📄 Word (.docx)</a>
    <a href="export.php?jenis=klasifikasi_pdf" class="btn btn-merah">📕 PDF (.pdf)</a>
    <a href="export.php?jenis=klasifikasi" class="btn btn-hijau">📊 Excel (.xlsx)</a>
    <form method="post" style="margin:0;">
      <button type="submit" name="hapus_data" value="1" class="btn btn-merah" onclick="return confirm('Hapus semua data siswa dan hasil model yang tersimpan?')">🗑 Hapus Data</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-title">🎓 Daftar Klasifikasi Siswa (<span id="jmlBaris"><?= count($data) ?></span> siswa)</div>
  <div class="card-body table-wrap">
    <table class="tabel">
      <thead><tr><th>Nama</th><th>Rata-rata</th><th>Kehadiran</th><th>Kedisiplinan</th><th>Kategori</th></tr></thead>
      <tbody>
      <?php foreach ($data as $d): ?>
        <tr data-nama="<?= htmlspecialchars(mb_strtolower($d['nama'])) ?>" data-kategori="<?= htmlspecialchars($d['kategori']) ?>">
          <td><?= htmlspecialchars($d['nama']) ?></td>
          <td><b><?= $d['rata_rata'] ?></b></td>
          <td><?= $d['kehadiran'] ?>%</td>
          <td><?= htmlspecialchars($d['kedisiplinan']) ?></td>
          <td><span class="badge <?= $d['kategori'] === 'Berprestasi' ? 'badge-hijau' : 'badge-oranye' ?>"><?= $d['kategori'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const cariInput = document.getElementById('cariInput');
const filterKategori = document.getElementById('filterKategori');
const tbody = document.querySelector('.tabel tbody');
const jmlBaris = document.getElementById('jmlBaris');
function terapkanFilter(){
  const q = cariInput.value.trim().toLowerCase();
  const kat = filterKategori.value;
  let tampil = 0;
  tbody.querySelectorAll('tr[data-nama]').forEach(tr => {
    const show = (!q || tr.dataset.nama.includes(q)) && (!kat || tr.dataset.kategori === kat);
    tr.style.display = show ? '' : 'none';
    if (show) tampil++;
  });
  jmlBaris.textContent = tampil;
}
cariInput.addEventListener('input', terapkanFilter);
filterKategori.addEventListener('change', terapkanFilter);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
