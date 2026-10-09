<?php
$judulHalaman = 'Rekomendasi Prestasi Siswa';
$subHalaman = 'Ringkasan, pembinaan, dan rekomendasi berdasarkan KKM sekolah';
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
$ringkasan = hitung_rekomendasi_prestasi($pdo, $kolomMapel);
$memenuhi = $ringkasan['memenuhi'];
$butuhPembinaan = $ringkasan['butuh_pembinaan'];
$mapelDiperhatikan = $ringkasan['mapel_diperhatikan'];
$kesimpulan = $ringkasan['kesimpulan'];
?>

<?php if ($pesan): ?>
  <div class="alert alert-<?= $pesanTipe ?>"><?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
  <div class="stat-card"><div class="ikon" style="background:#2563eb;">👥</div><div><div class="nilai"><?= $ringkasan['total_siswa'] ?></div><div class="judul">Jumlah Seluruh Siswa</div></div></div>
  <div class="stat-card"><div class="ikon" style="background:#7c3aed;">🎯</div><div><div class="nilai"><?= number_format($ringkasan['kkm'], 0, ',', '.') ?></div><div class="judul">KKM Sekolah</div></div></div>
  <div class="stat-card"><div class="ikon" style="background:#059669;">✅</div><div><div class="nilai"><?= $ringkasan['jumlah_memenuhi'] ?></div><div class="judul">Siswa Memenuhi KKM</div><div class="sub"><?= $ringkasan['persen_memenuhi'] ?>%</div></div></div>
  <div class="stat-card"><div class="ikon" style="background:#f59e0b;">⚠️</div><div><div class="nilai"><?= $ringkasan['jumlah_belum'] ?></div><div class="judul">Siswa Belum Memenuhi KKM</div><div class="sub"><?= $ringkasan['persen_belum'] ?>%</div></div></div>
</div>

<div class="toolbar" style="justify-content:space-between;">
  <div class="left"><strong>Data aktif:</strong> <?= count($kolomMapel) ?> mata pelajaran terdeteksi</div>
  <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
    <a href="export.php?jenis=rekomendasi_word" class="btn btn-ungu">📄 Word (.docx)</a>
    <a href="export.php?jenis=rekomendasi_pdf" class="btn btn-merah">📕 PDF (.pdf)</a>
    <a href="export.php?jenis=rekomendasi_excel" class="btn btn-hijau">📊 Excel (.xlsx)</a>
    <form method="post" style="margin:0;">
      <button type="submit" name="hapus_data" value="1" class="btn btn-merah" onclick="return confirm('Hapus semua data siswa dan hasil model yang tersimpan?')">🗑 Hapus Data</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-title">📌 RINGKASAN</div>
  <div class="card-body">
    <div class="table-wrap">
      <table class="tabel">
        <thead>
          <tr>
            <th>Indikator</th>
            <th>Jumlah</th>
            <th>Persentase</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>Jumlah seluruh siswa</td><td><?= $ringkasan['total_siswa'] ?></td><td>100%</td></tr>
          <tr><td>KKM sekolah</td><td><?= number_format($ringkasan['kkm'], 0, ',', '.') ?></td><td>-</td></tr>
          <tr><td>Jumlah siswa yang memenuhi KKM</td><td><?= $ringkasan['jumlah_memenuhi'] ?></td><td><?= $ringkasan['persen_memenuhi'] ?>%</td></tr>
          <tr><td>Jumlah siswa yang belum memenuhi KKM</td><td><?= $ringkasan['jumlah_belum'] ?></td><td><?= $ringkasan['persen_belum'] ?>%</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-title">✅ SISWA MEMENUHI KKM</div>
  <div class="card-body table-wrap">
    <table class="tabel">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Siswa</th>
          <th>Nilai/Rata-rata</th>
          <th>KKM</th>
          <th>Selisih Nilai</th>
          <th>Status</th>
          <th>Kesimpulan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($memenuhi as $s): ?>
          <tr>
            <td><?= $s['no'] ?></td>
            <td><?= htmlspecialchars($s['nama']) ?></td>
            <td><?= $s['nilai'] ?></td>
            <td><?= $s['kkm'] ?></td>
            <td><?= $s['selisih'] ?></td>
            <td><span class="badge badge-hijau"><?= htmlspecialchars($s['status']) ?></span></td>
            <td><?= htmlspecialchars($s['kesimpulan']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$memenuhi): ?><tr><td colspan="7">Belum ada siswa yang memenuhi KKM.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-title">⚠️ SISWA MEMBUTUHKAN PEMBINAAN</div>
  <div class="card-body table-wrap">
    <table class="tabel">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Siswa</th>
          <th>Nilai/Rata-rata</th>
          <th>KKM</th>
          <th>Selisih Nilai</th>
          <th>Status</th>
          <th>Mata Pelajaran yang Belum Mencapai KKM</th>
          <th>Rekomendasi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($butuhPembinaan as $s): ?>
          <tr>
            <td><?= $s['no'] ?></td>
            <td><?= htmlspecialchars($s['nama']) ?></td>
            <td><?= $s['nilai'] ?></td>
            <td><?= $s['kkm'] ?></td>
            <td><?= $s['selisih'] ?></td>
            <td><span class="badge badge-oranye"><?= htmlspecialchars($s['status']) ?></span></td>
            <td><?= htmlspecialchars(implode(', ', $s['mapel_belum'])) ?: '-' ?></td>
            <td><?= htmlspecialchars($s['rekomendasi']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$butuhPembinaan): ?><tr><td colspan="8">Tidak ada siswa yang membutuhkan pembinaan.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-title">🧠 KESIMPULAN OTOMATIS</div>
  <div class="card-body">
    <p style="margin:0; font-size:14px; line-height:1.7;"><?= htmlspecialchars($kesimpulan) ?></p>
  </div>
</div>

<div class="card">
  <div class="card-title">💡 REKOMENDASI</div>
  <div class="card-body">
    <ul style="margin:0; padding-left:18px; line-height:1.8;">
      <li>Untuk siswa yang memenuhi KKM: <strong>Mempertahankan dan meningkatkan pencapaian belajar.</strong></li>
      <li>Untuk siswa yang belum memenuhi KKM: <strong>Melakukan pembinaan, pendampingan, atau remedial pada mata pelajaran yang belum mencapai KKM.</strong></li>
      <?php if ($mapelDiperhatikan): ?>
        <li>Mata pelajaran yang paling sering belum mencapai KKM: <strong><?= htmlspecialchars(implode(', ', array_keys(array_slice($mapelDiperhatikan, 0, 5, true)))) ?></strong>.</li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<div class="card">
  <div class="card-title">⬇ EXPORT HASIL</div>
  <div class="card-body" style="display:flex; gap:10px; flex-wrap:wrap;">
    <a href="export.php?jenis=rekomendasi_word" class="btn btn-ungu">📄 Word (.docx)</a>
    <a href="export.php?jenis=rekomendasi_pdf" class="btn btn-merah">📕 PDF (.pdf)</a>
    <a href="export.php?jenis=rekomendasi_excel" class="btn btn-hijau">📊 Excel (.xlsx)</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
