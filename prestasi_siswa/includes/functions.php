<?php
/**
 * Kumpulan fungsi inti aplikasi:
 * - Pengelolaan kolom mata pelajaran dinamis
 * - Pengambilan & perhitungan ulang data siswa
 * - Normalisasi data import
 * - Autentikasi
 */

require_once __DIR__ . '/../config/db.php';

const ALIAS_KOLOM = [
    'nama' => 'nama', 'nama siswa' => 'nama', 'nama lengkap' => 'nama',
    'name' => 'nama', 'student name' => 'nama', 'full name' => 'nama',
    'kehadiran' => 'kehadiran', 'kehadiran %' => 'kehadiran',
    'presentase kehadiran' => 'kehadiran', 'persentase kehadiran' => 'kehadiran',
    'attendance' => 'kehadiran', 'attendance %' => 'kehadiran', 'presence' => 'kehadiran',
    'kedisiplinan' => 'kedisiplinan', 'disiplin' => 'kedisiplinan',
    'skor kedisiplinan' => 'kedisiplinan',
    'discipline' => 'kedisiplinan', 'discipline score' => 'kedisiplinan',
    'no' => 'abaikan', 'no.' => 'abaikan', 'nomor' => 'abaikan',
    'nomor urut' => 'abaikan', 'no urut' => 'abaikan',
];

/** Ambil daftar kolom mata pelajaran aktif: [[key,label], ...] */
function get_kolom_mapel(PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT nilai FROM konfigurasi WHERE kunci = 'kolom_mapel'");
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row && $row['nilai']) {
        $decoded = json_decode($row['nilai'], true);
        if (is_array($decoded)) return $decoded;
    }
    return [];
}

function set_kolom_mapel(PDO $pdo, array $pasangan): void
{
    $json = json_encode(array_values($pasangan));
    $stmt = $pdo->prepare(
        "INSERT INTO konfigurasi (kunci, nilai) VALUES ('kolom_mapel', ?)
         ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)"
    );
    $stmt->execute([$json]);
}

function mp_keys(array $kolomMapel): array { return array_column($kolomMapel, 0); }
function mp_labels(array $kolomMapel): array { return array_column($kolomMapel, 1); }

/**
 * Ambil semua data siswa, dengan nilai_json dipecah menjadi kolom per mata pelajaran.
 * Mengembalikan array asosiatif per baris.
 */
function ambil_semua_siswa(PDO $pdo, array $kolomMapel): array
{
    $keys = mp_keys($kolomMapel);
    $stmt = $pdo->query('SELECT * FROM siswa ORDER BY id');
    $rows = $stmt->fetchAll();
    $hasil = [];
    foreach ($rows as $r) {
        $nilai = json_decode($r['nilai_json'] ?? '{}', true) ?: [];
        $baris = [
            'id' => $r['id'],
            'nama' => $r['nama'],
            'kehadiran' => (int)$r['kehadiran'],
            'kedisiplinan' => $r['kedisiplinan'],
            'rata_rata' => (float)$r['rata_rata'],
            'kategori' => $r['kategori'],
        ];
        foreach ($keys as $k) {
            $baris[$k] = isset($nilai[$k]) ? (float)$nilai[$k] : 0;
        }
        $hasil[] = $baris;
    }
    return $hasil;
}

function hitung_ulang_rata_semua(PDO $pdo, array $kolomMapel): void
{
    $keys = mp_keys($kolomMapel);
    if (empty($keys)) return;
    $stmt = $pdo->query('SELECT id, nilai_json FROM siswa');
    $rows = $stmt->fetchAll();
    $upd = $pdo->prepare('UPDATE siswa SET rata_rata = ?, kategori = ? WHERE id = ?');
    foreach ($rows as $r) {
        $nilai = json_decode($r['nilai_json'] ?? '{}', true) ?: [];
        $vals = [];
        foreach ($keys as $k) {
            if (isset($nilai[$k]) && $nilai[$k] !== null) $vals[] = (float)$nilai[$k];
        }
        $rata = count($vals) ? round(array_sum($vals) / count($vals), 2) : 0.0;
        $kat = $rata >= KKM ? 'Berprestasi' : 'Butuh Pembinaan';
        $upd->execute([$rata, $kat, $r['id']]);
    }
}

/** Normalisasi nama kolom: lowercase, hapus tanda kurung, rapikan spasi */
function norm_kolom(string $t): string
{
    $s = mb_strtolower(trim($t));
    $s = preg_replace('/\(.*?\)/', '', $s);
    $s = str_replace(['.', '_', '-'], ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

function norm_kunci_mapel(string $t): string
{
    $s = norm_kolom($t);
    $s = preg_replace('/[^a-z0-9]+/', '_', $s);
    $s = trim($s, '_');
    return $s !== '' ? $s : 'variabel';
}

function deteksi_tipe_kedisiplinan(array $nilai): string
{
    $lihatNumerik = false;
    $lihatTeks = false;

    foreach ($nilai as $v) {
        $raw = trim((string)($v ?? ''));
        if ($raw === '') continue;
        $norm = str_replace(',', '.', $raw);
        if (preg_match('/^-?\d+(?:\.\d+)?$/', $norm)) {
            $lihatNumerik = true;
        } else {
            $lihatTeks = true;
        }
    }

    if ($lihatNumerik && $lihatTeks) return 'campuran';
    if ($lihatNumerik) return 'numerik';
    if ($lihatTeks) return 'teks';
    return 'kosong';
}

function deteksi_kedisiplinan_kolom(array $header, array $rows): string
{
    $namaKolom = null;
    foreach ($header as $kol) {
        if (norm_kolom((string)$kol) === 'kedisiplinan') {
            $namaKolom = $kol;
            break;
        }
    }
    if ($namaKolom === null) return 'kosong';

    $nilai = [];
    foreach ($rows as $row) {
        $nilai[] = $row[$namaKolom] ?? '';
    }
    return deteksi_tipe_kedisiplinan($nilai);
}

function konversi_nilai_kedisiplinan($v)
{
    if ($v === null) return '';
    $raw = trim((string)$v);
    if ($raw === '') return '';
    $norm = str_replace(',', '.', $raw);
    if (preg_match('/^-?\d+(?:\.\d+)?$/', $norm)) {
        $num = (float)$norm;
        return floor($num) == $num ? (int)$num : $num;
    }
    return preg_replace('/\s+/', ' ', $raw);
}

function kategori_kedisiplinan($v): string
{
    $raw = trim((string)($v ?? ''));
    if ($raw === '') return 'Cukup';
    $norm = str_replace(',', '.', $raw);
    if (preg_match('/^-?\d+(?:\.\d+)?$/', $norm)) {
        $num = (float)$norm;
        if ($num >= 80) return 'Baik';
        if ($num >= 60) return 'Cukup';
        return 'Kurang';
    }

    $s = mb_strtolower($raw);
    if (preg_match('/\b(sangat\s+baik|baik|good|excellent)\b/', $s)) return 'Baik';
    if (preg_match('/\b(cukup|cukup\s+baik|sedang|average)\b/', $s)) return 'Cukup';
    if (preg_match('/\b(kurang|rendah|poor)\b/', $s)) return 'Kurang';
    return 'Cukup';
}

function norm_kedisiplinan($v): string
{
    return kategori_kedisiplinan($v);
}

/**
 * Siapkan data import: deteksi kolom nama/kehadiran/kedisiplinan (baku)
 * dan kolom mata pelajaran (bebas, otomatis menyesuaikan).
 *
 * $rows: array asosiatif per baris, hasil parsing file (header => value)
 * Return: [dataSiap[], kolomMapel[[key,label],...], error|null]
 */
function siapkan_data_import(array $header, array $rows): array
{
    $kolomTetap = [];      // header_asli => target ('nama'/'kehadiran'/'kedisiplinan')
    $kolomMapelAsli = [];  // header_asli (kolom nilai mapel)
    $targetTerpakai = [];

    foreach ($header as $kol) {
        $norm = norm_kolom($kol);
        $target = ALIAS_KOLOM[$norm] ?? null;
        if ($target === 'abaikan') continue;
        if ($target && !in_array($target, $targetTerpakai, true)) {
            $kolomTetap[$kol] = $target;
            $targetTerpakai[] = $target;
        } elseif ($target === null) {
            $kolomMapelAsli[] = $kol;
        }
    }

    $wajib = ['nama' => 'Nama Siswa', 'kehadiran' => 'Kehadiran', 'kedisiplinan' => 'Kedisiplinan'];
    $hilang = [];
    foreach ($wajib as $k => $label) {
        if (!in_array($k, $targetTerpakai, true)) $hilang[] = $label;
    }
    if ($hilang) {
        return [null, null, 'Kolom tidak ditemukan: ' . implode(', ', $hilang)];
    }
    if (!$kolomMapelAsli) {
        return [null, null, 'Tidak ada kolom nilai mata pelajaran/variabel.'];
    }

    // Buat pemetaan kolom mapel -> key unik
    $petaMapel = [];
    $kunciTerpakai = [];
    $kolomMapel = [];
    foreach ($kolomMapelAsli as $kol) {
        $label = trim((string)$kol);
        $dasar = norm_kunci_mapel($kol);
        $kunci = $dasar; $n = 2;
        while (in_array($kunci, $kunciTerpakai, true)) { $kunci = $dasar . '_' . $n; $n++; }
        $kunciTerpakai[] = $kunci;
        $petaMapel[$kol] = $kunci;
        $kolomMapel[] = [$kunci, $label];
    }
    $mapelKeys = array_column($kolomMapel, 0);

    // Petakan ulang tiap baris berdasar kolomTetap & petaMapel
    $hasil = [];
    foreach ($rows as $row) {
        $baris = [];
        foreach ($kolomTetap as $asli => $target) {
            $baris[$target] = $row[$asli] ?? '';
        }
        foreach ($petaMapel as $asli => $kunci) {
            $baris[$kunci] = $row[$asli] ?? '';
        }
        $namaBaris = trim((string)($baris['nama'] ?? ''));
        if ($namaBaris === '') continue;
        $baris['nama'] = $namaBaris;

        foreach (array_merge($mapelKeys, ['kehadiran']) as $col) {
            $v = $baris[$col] ?? null;
            $v = is_numeric(str_replace(',', '.', (string)$v)) ? (float)str_replace(',', '.', (string)$v) : null;
            if ($v !== null) $v = max(0, min(100, $v));
            $baris[$col] = $v;
        }
        $baris['kedisiplinan'] = konversi_nilai_kedisiplinan($baris['kedisiplinan'] ?? '');
        $hasil[] = $baris;
    }

    // Isi nilai kosong dengan rata-rata kolom (seperti aplikasi asli)
    foreach (array_merge($mapelKeys, ['kehadiran']) as $col) {
        $vals = array_filter(array_column($hasil, $col), fn($v) => $v !== null);
        $rata = count($vals) ? array_sum($vals) / count($vals) : 0;
        foreach ($hasil as &$b) {
            if ($b[$col] === null) $b[$col] = round($rata);
            $b[$col] = (int)round($b[$col]);
        }
        unset($b);
    }

    // Pertahankan seluruh baris agar jumlah siswa sama persis dengan file.
    $final = $hasil;

    // Hitung rata-rata & kategori
    foreach ($final as &$b) {
        $vals = [];
        foreach ($mapelKeys as $k) $vals[] = $b[$k];
        $rata = count($vals) ? round(array_sum($vals) / count($vals), 2) : 0;
        $b['rata_rata'] = $rata;
        $b['kategori'] = $rata >= KKM ? 'Berprestasi' : 'Butuh Pembinaan';
        $nilaiJson = [];
        foreach ($mapelKeys as $k) $nilaiJson[$k] = (int)$b[$k];
        $b['nilai_json'] = json_encode($nilaiJson);
    }
    unset($b);

    if (!$final) {
        return [null, null, 'Tidak ada data siswa yang valid untuk diimpor.'];
    }

    return [$final, $kolomMapel, null];
}

function catat_import(PDO $pdo, int $jumlah, string $namaFile): void
{
    $stmt = $pdo->prepare('INSERT INTO log_import (jumlah_siswa, nama_file) VALUES (?, ?)');
    $stmt->execute([$jumlah, $namaFile]);
}

function hapus_data_siswa(PDO $pdo): int
{
    $jumlah = (int)$pdo->query('SELECT COUNT(*) FROM siswa')->fetchColumn();

    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM siswa');
        $pdo->exec('DELETE FROM hasil_model');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['hasil_prediksi'], $_SESSION['id_model_terakhir']);
    }

    return $jumlah;
}

function impor_data_siswa(PDO $pdo, array $dataBaris, array $kolomMapelBaru, string $mode = 'ganti'): int
{
    if (!$dataBaris || !$kolomMapelBaru) {
        throw new RuntimeException('Dataset baru tidak berisi data yang dapat diimpor.');
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM siswa');
        $pdo->exec('DELETE FROM hasil_model');
        set_kolom_mapel($pdo, $kolomMapelBaru);
        $stmt = $pdo->prepare(
            'INSERT INTO siswa (nama, nilai_json, kehadiran, kedisiplinan, rata_rata, kategori) VALUES (?,?,?,?,?,?)'
        );
        foreach ($dataBaris as $b) {
            $stmt->execute([$b['nama'], $b['nilai_json'], (int)$b['kehadiran'], $b['kedisiplinan'], $b['rata_rata'], $b['kategori']]);
        }
        $kolomMapelFinal = get_kolom_mapel($pdo);
        hitung_ulang_rata_semua($pdo, $kolomMapelFinal);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['hasil_prediksi'], $_SESSION['id_model_terakhir']);
    }
    return count($dataBaris);
}

/* ------------------------- AUTENTIKASI ------------------------- */

function cek_username_ada(PDO $pdo, string $username): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM pengguna WHERE username = ?');
    $stmt->execute([mb_strtolower(trim($username))]);
    return (int)$stmt->fetchColumn() > 0;
}

function daftar_pengguna(PDO $pdo, string $namaLengkap, string $username, string $password): array
{
    $namaLengkap = trim($namaLengkap);
    $username = mb_strtolower(trim($username));
    if ($namaLengkap === '' || $username === '' || $password === '') {
        return [false, 'Semua kolom wajib diisi.'];
    }
    if (mb_strlen($username) < 4) return [false, 'Username minimal 4 karakter.'];
    if (!preg_match('/^[a-zA-Z0-9_.]+$/', $username)) {
        return [false, 'Username hanya huruf, angka, titik, dan garis bawah.'];
    }
    if (mb_strlen($password) < 6) return [false, 'Password minimal 6 karakter.'];
    if (cek_username_ada($pdo, $username)) return [false, 'Username sudah terdaftar.'];

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO pengguna (nama_lengkap, username, password_hash) VALUES (?,?,?)');
    $stmt->execute([$namaLengkap, $username, $hash]);
    return [true, 'Registrasi berhasil! Silakan login.'];
}

function login_pengguna(PDO $pdo, string $username, string $password): array
{
    $username = mb_strtolower(trim($username));
    $stmt = $pdo->prepare('SELECT id, nama_lengkap, username, password_hash FROM pengguna WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if (!$row) return [false, 'Username tidak ditemukan.', null];
    if (!password_verify($password, $row['password_hash'])) return [false, 'Password salah.', null];
    return [true, 'Login berhasil.', ['id' => $row['id'], 'nama_lengkap' => $row['nama_lengkap'], 'username' => $row['username']]];
}

function require_login(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['pengguna'])) {
        header('Location: login.php');
        exit;
    }
}

function statistik_mapel(array $data, array $kolomMapel): array
{
    $rows = [];
    foreach ($kolomMapel as [$k, $label]) {
        $vals = array_column($data, $k);
        if (!$vals) { $rows[] = ['label' => $label, 'min' => 0, 'max' => 0, 'rata' => 0, 'std' => 0, 'median' => 0, 'status' => '-']; continue; }
        $n = count($vals);
        $mean = array_sum($vals) / $n;
        $variance = 0;
        foreach ($vals as $v) $variance += ($v - $mean) ** 2;
        $std = $n > 1 ? sqrt($variance / ($n - 1)) : 0;
        sort($vals);
        $mid = (int)floor($n / 2);
        $median = ($n % 2 === 0) ? ($vals[$mid - 1] + $vals[$mid]) / 2 : $vals[$mid];
        $rows[] = [
            'label' => $label, 'min' => min($vals), 'max' => max($vals),
            'rata' => round($mean, 2), 'std' => round($std, 2), 'median' => $median,
            'status' => $mean >= KKM ? 'Di atas KKM' : 'Di bawah KKM',
        ];
    }
    return $rows;
}

function hitung_rekomendasi_prestasi(PDO $pdo, array $kolomMapel): array
{
    $data = ambil_semua_siswa($pdo, $kolomMapel);
    $totalSiswa = count($data);
    $kkm = (float)KKM;
    $memenuhi = [];
    $butuhPembinaan = [];
    $mapelDiperhatikan = [];

    foreach ($data as $s) {
        $nilai = (float)($s['rata_rata'] ?? 0);
        $mapelBelum = [];

        foreach ($kolomMapel as [$k, $label]) {
            $nilaiMapel = (float)($s[$k] ?? 0);
            if ($nilaiMapel < $kkm) {
                $mapelBelum[] = $label;
                $mapelDiperhatikan[$label] = ($mapelDiperhatikan[$label] ?? 0) + 1;
            }
        }

        if ($nilai >= $kkm) {
            $memenuhi[] = [
                'no' => count($memenuhi) + 1,
                'nama' => $s['nama'],
                'nilai' => round($nilai, 2),
                'kkm' => $kkm,
                'selisih' => round($nilai - $kkm, 2),
                'status' => 'Memenuhi KKM',
                'kesimpulan' => 'Nilai siswa telah memenuhi KKM pada rata-rata keseluruhan.',
                'rekomendasi' => 'Mempertahankan dan meningkatkan pencapaian belajar.',
            ];
            continue;
        }

        $selisih = round($kkm - $nilai, 2);
        $rekomendasiMapel = count($mapelBelum)
            ? 'Disarankan mendapatkan pembinaan atau remedial pada mata pelajaran yang belum mencapai KKM: ' . implode(', ', $mapelBelum) . '.'
            : 'Disarankan melakukan pembinaan, pendampingan, atau remedial agar rata-rata dapat mencapai KKM.';

        $butuhPembinaan[] = [
            'no' => count($butuhPembinaan) + 1,
            'nama' => $s['nama'],
            'nilai' => round($nilai, 2),
            'kkm' => $kkm,
            'selisih' => $selisih,
            'status' => 'Belum Memenuhi KKM',
            'mapel_belum' => $mapelBelum,
            'rekomendasi' => $rekomendasiMapel,
        ];
    }

    $jumlahMemenuhi = count($memenuhi);
    $jumlahBelum = count($butuhPembinaan);
    $persenMemenuhi = $totalSiswa ? round(($jumlahMemenuhi / $totalSiswa) * 100, 2) : 0;
    $persenBelum = $totalSiswa ? round(($jumlahBelum / $totalSiswa) * 100, 2) : 0;

    $mapelDiperhatikanTerurut = $mapelDiperhatikan;
    arsort($mapelDiperhatikanTerurut);

    $kesimpulan = sprintf(
        'Berdasarkan hasil perbandingan nilai siswa dengan KKM sebesar %s, sebanyak %d siswa telah memenuhi KKM, sedangkan %d siswa belum mencapai KKM dan memerlukan pembinaan lebih lanjut.',
        number_format($kkm, 0, ',', '.'),
        $jumlahMemenuhi,
        $jumlahBelum
    );

    return [
        'total_siswa' => $totalSiswa,
        'kkm' => $kkm,
        'jumlah_memenuhi' => $jumlahMemenuhi,
        'jumlah_belum' => $jumlahBelum,
        'persen_memenuhi' => $persenMemenuhi,
        'persen_belum' => $persenBelum,
        'memenuhi' => $memenuhi,
        'butuh_pembinaan' => $butuhPembinaan,
        'mapel_diperhatikan' => $mapelDiperhatikanTerurut,
        'kesimpulan' => $kesimpulan,
        'rekomendasi_umum' => [
            'Mempertahankan dan meningkatkan pencapaian belajar.',
            'Melakukan pembinaan, pendampingan, atau remedial pada mata pelajaran yang belum mencapai KKM.',
        ],
    ];
}

function format_tanggal(?string $mysqlDatetime): string
{
    if (!$mysqlDatetime) return '-';
    $ts = strtotime($mysqlDatetime);
    return $ts ? date('d/m/Y H:i:s', $ts) : $mysqlDatetime;
}
