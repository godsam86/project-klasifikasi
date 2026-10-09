<?php
$judulHalaman = 'Import Data';
$subHalaman = 'Import data siswa dari file Excel (.xlsx) atau CSV';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/lib/XlsxReader.php';

$kolomMapel = get_kolom_mapel($pdo);
$pesan = '';
$pesanTipe = '';
$previewHeader = [];
$previewRows = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['hapus_data'])) {
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
    } elseif (isset($_FILES['file_import'])) {
        try {
            $file = $_FILES['file_import'];
            if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Gagal mengunggah file.');
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $header = [];
            $rowsAsoc = [];

            if ($ext === 'xlsx') {
                $allRows = XlsxReader::baca($file['tmp_name']);
                [$header, $rowsAsoc] = XlsxReader::keAsosiatif($allRows);
            } elseif ($ext === 'csv') {
                $fh = fopen($file['tmp_name'], 'r');
                if ($fh === false) throw new RuntimeException('File CSV tidak dapat dibaca.');
                $sample = fgets($fh);
                if ($sample === false) throw new RuntimeException('File CSV kosong.');
                $delimiters = [';' => substr_count($sample, ';'), ',' => substr_count($sample, ','), "\t" => substr_count($sample, "\t")];
                $delimiter = array_search(max($delimiters), $delimiters, true);
                rewind($fh);
                $allRowsCsv = [];
                while (($row = fgetcsv($fh, 0, $delimiter)) !== false) $allRowsCsv[] = $row;
                [$header, $rowsAsoc] = XlsxReader::keAsosiatif($allRowsCsv);
                fclose($fh);
            } else {
                throw new RuntimeException('Format file tidak didukung. Gunakan .xlsx atau .csv. '
                    . '(Import .docx/.xls tidak tersedia pada versi web ini — silakan konversi ke Excel/CSV terlebih dahulu.)');
            }

            [$dataSiap, $kolomMapelBaru, $err] = siapkan_data_import($header, $rowsAsoc);
            if ($err) throw new RuntimeException($err);

            $tipeKedisiplinan = deteksi_kedisiplinan_kolom($header, $rowsAsoc);
            if ($tipeKedisiplinan === 'campuran') {
                $pesan = 'Peringatan: kolom Kedisiplinan pada file memiliki campuran data numerik dan teks. Sistem akan mempertahankan nilai asli tiap baris sesuai Excel.';
                $pesanTipe = 'warning';
            }

            $jumlah = impor_data_siswa($pdo, $dataSiap, $kolomMapelBaru);
            catat_import($pdo, $jumlah, $file['name']);

            $pesan = $pesan !== '' ? $pesan : "Berhasil mengimpor $jumlah data siswa dari berkas \"{$file['name']}\".";
            $pesanTipe = $pesanTipe !== '' ? $pesanTipe : 'sukses';
            $kolomMapel = get_kolom_mapel($pdo);
        } catch (Throwable $e) {
            $pesan = 'Gagal impor: ' . $e->getMessage();
            $pesanTipe = 'gagal';
        }
    }
}

// Preview 10 baris data saat ini
$dataSekarang = ambil_semua_siswa($pdo, $kolomMapel);
$previewData = array_slice($dataSekarang, 0, 10);
?>

<?php if ($pesan): ?>
  <div class="alert alert-<?= $pesanTipe ?>"><?= htmlspecialchars($pesan) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-title">📁 Import Data Siswa</div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <div class="form-group">
        <label>Pilih File (.xlsx atau .csv)</label>
        <input type="file" name="file_import" accept=".xlsx,.csv" required>
        <div class="hint">
          Kolom wajib: <b>Nama Siswa</b>, <b>Kehadiran</b> (%), <b>Kedisiplinan</b> (Baik/Cukup/Kurang).
          Kolom nilai mata pelajaran bebas — nama & jumlahnya otomatis menyesuaikan file yang diimpor.
        </div>
      </div>
      <div class="hint">Import akan mengganti seluruh dataset lama agar nama, jumlah siswa, mata pelajaran, dan nilai sepenuhnya mengikuti file baru.</div>
      <button type="submit" class="btn btn-hijau">⬆ Import Sekarang</button>
      <a href="download_template.php" class="btn btn-outline">⬇ Unduh Template Excel</a>
    </form>

    <form method="post" style="margin-top:16px;">
      <button type="submit" name="hapus_data" value="1" class="btn btn-merah" onclick="return confirm('Hapus semua data siswa dan hasil model yang tersimpan?')">🗑 Hapus Data</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-title">👁 Preview Data Saat Ini (10 baris pertama dari <?= count($dataSekarang) ?> total)</div>
  <div class="card-body table-wrap">
    <table class="tabel">
      <thead>
        <tr>
          <th>Nama</th>
          <?php foreach (mp_labels($kolomMapel) as $l): ?><th><?= htmlspecialchars($l) ?></th><?php endforeach; ?>
          <th>Kehadiran</th><th>Kedisiplinan</th><th>Rata-rata</th><th>Kategori</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($previewData as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['nama']) ?></td>
          <?php foreach (mp_keys($kolomMapel) as $k): ?><td><?= $d[$k] ?></td><?php endforeach; ?>
          <td><?= $d['kehadiran'] ?>%</td>
          <td><?= htmlspecialchars($d['kedisiplinan']) ?></td>
          <td><?= $d['rata_rata'] ?></td>
          <td><span class="badge <?= $d['kategori'] === 'Berprestasi' ? 'badge-hijau' : 'badge-oranye' ?>"><?= $d['kategori'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$previewData): ?><tr><td colspan="20">Belum ada data.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
