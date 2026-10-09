<?php
$judulHalaman = 'Analisis';
$subHalaman = 'Tabel data lengkap seluruh siswa';
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
$mpKeys = mp_keys($kolomMapel);
$mpLabels = mp_labels($kolomMapel);
?>

<?php if ($pesan): ?>
  <div class="alert alert-<?= $pesanTipe ?>"><?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<div class="toolbar">
  <div class="left">
    <input type="text" id="cariInput" placeholder="🔍 Cari nama siswa..." style="padding:8px 12px; border:1px solid var(--border); border-radius:5px; min-width:220px;">
    <select id="filterKategori" style="padding:8px 12px; border:1px solid var(--border); border-radius:5px;">
      <option value="">Semua Kategori</option>
      <option value="Berprestasi">Berprestasi</option>
      <option value="Butuh Pembinaan">Butuh Pembinaan</option>
    </select>
  </div>
  <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
    <a href="export.php?jenis=analisis_word" class="btn btn-ungu">📄 Word (.docx)</a>
    <a href="export.php?jenis=analisis_pdf" class="btn btn-merah">📕 PDF (.pdf)</a>
    <a href="export.php?jenis=analisis" class="btn btn-hijau">📊 Excel (.xlsx)</a>
    <form method="post" style="margin:0;">
      <button type="submit" name="hapus_data" value="1" class="btn btn-merah" onclick="return confirm('Hapus semua data siswa dan hasil model yang tersimpan?')">🗑 Hapus Data</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-title">📊 Data Lengkap Siswa (<span id="jmlBaris"><?= count($data) ?></span> siswa) — klik judul kolom untuk urutkan</div>
  <div class="card-body table-wrap">
    <table class="tabel" id="tabelAnalisis">
      <thead>
        <tr>
          <th data-tipe="teks">Nama</th>
          <?php foreach ($mpLabels as $l): ?><th data-tipe="angka"><?= htmlspecialchars($l) ?></th><?php endforeach; ?>
          <th data-tipe="angka">Kehadiran</th>
          <th data-tipe="teks">Kedisiplinan</th>
          <th data-tipe="angka">Rata-rata</th>
          <th data-tipe="teks">Kategori</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($data as $d): ?>
        <tr data-nama="<?= htmlspecialchars(mb_strtolower($d['nama'])) ?>" data-kategori="<?= htmlspecialchars($d['kategori']) ?>">
          <td><?= htmlspecialchars($d['nama']) ?></td>
          <?php foreach ($mpKeys as $k): ?><td><?= $d[$k] ?></td><?php endforeach; ?>
          <td><?= $d['kehadiran'] ?>%</td>
          <td><?= htmlspecialchars($d['kedisiplinan']) ?></td>
          <td><b><?= $d['rata_rata'] ?></b></td>
          <td><span class="badge <?= $d['kategori'] === 'Berprestasi' ? 'badge-hijau' : 'badge-oranye' ?>"><?= $d['kategori'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$data): ?><tr><td colspan="20">Belum ada data. Silakan import data terlebih dahulu.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const cariInput = document.getElementById('cariInput');
const filterKategori = document.getElementById('filterKategori');
const tabel = document.getElementById('tabelAnalisis');
const tbody = tabel.querySelector('tbody');
const jmlBaris = document.getElementById('jmlBaris');

function terapkanFilter(){
  const q = cariInput.value.trim().toLowerCase();
  const kat = filterKategori.value;
  let tampil = 0;
  tbody.querySelectorAll('tr[data-nama]').forEach(tr => {
    const cocokNama = !q || tr.dataset.nama.includes(q);
    const cocokKat = !kat || tr.dataset.kategori === kat;
    const show = cocokNama && cocokKat;
    tr.style.display = show ? '' : 'none';
    if (show) tampil++;
  });
  jmlBaris.textContent = tampil;
}
cariInput.addEventListener('input', terapkanFilter);
filterKategori.addEventListener('change', terapkanFilter);

// Sorting sederhana per kolom
let sortState = {};
tabel.querySelectorAll('th').forEach((th, idx) => {
  th.addEventListener('click', () => {
    const rows = Array.from(tbody.querySelectorAll('tr[data-nama]'));
    const tipe = th.dataset.tipe;
    const asc = !(sortState[idx] === 'asc');
    sortState = { [idx]: asc ? 'asc' : 'desc' };
    rows.sort((a, b) => {
      let va = a.children[idx].textContent.trim();
      let vb = b.children[idx].textContent.trim();
      if (tipe === 'angka') { va = parseFloat(va) || 0; vb = parseFloat(vb) || 0; return asc ? va - vb : vb - va; }
      return asc ? va.localeCompare(vb) : vb.localeCompare(va);
    });
    rows.forEach(r => tbody.appendChild(r));
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
