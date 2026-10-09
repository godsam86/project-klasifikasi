<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/lib/XlsxWriter.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_login();

function pdf_escape(string $text): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function buat_file_docx_rekomendasi(array $ringkasan): string
{
    $lines = [
        'LAPORAN REKOMENDASI PRESTASI SISWA',
        'Identitas Aplikasi/Sekolah: Sistem Prestasi Siswa SD Negeri 060921 Medan',
        'KKM Sekolah: ' . number_format($ringkasan['kkm'], 0, ',', '.'),
        'Jumlah seluruh siswa: ' . $ringkasan['total_siswa'],
        'Siswa memenuhi KKM: ' . $ringkasan['jumlah_memenuhi'] . ' (' . $ringkasan['persen_memenuhi'] . '%)',
        'Siswa belum memenuhi KKM: ' . $ringkasan['jumlah_belum'] . ' (' . $ringkasan['persen_belum'] . '%)',
        '',
        'KESIMPULAN',
        $ringkasan['kesimpulan'],
        '',
        'DAFTAR SISWA MEMENUHI KKM',
    ];

    foreach ($ringkasan['memenuhi'] as $s) {
        $lines[] = '- ' . $s['nama'] . ' | Nilai/Rata-rata: ' . $s['nilai'] . ' | KKM: ' . $s['kkm'] . ' | Selisih: ' . $s['selisih'] . ' | Status: ' . $s['status'];
    }

    $lines[] = '';
    $lines[] = 'DAFTAR SISWA MEMBUTUHKAN PEMBINAAN';
    foreach ($ringkasan['butuh_pembinaan'] as $s) {
        $mapel = $s['mapel_belum'] ? implode(', ', $s['mapel_belum']) : '-';
        $lines[] = '- ' . $s['nama'] . ' | Nilai/Rata-rata: ' . $s['nilai'] . ' | KKM: ' . $s['kkm'] . ' | Selisih: ' . $s['selisih'] . ' | Mata Pelajaran: ' . $mapel . ' | Rekomendasi: ' . $s['rekomendasi'];
    }

    $lines[] = '';
    $lines[] = 'MATA PELAJARAN YANG PERLU DIPERHATIKAN';
    if ($ringkasan['mapel_diperhatikan']) {
        foreach ($ringkasan['mapel_diperhatikan'] as $mapel => $jml) {
            $lines[] = '- ' . $mapel . ' (' . $jml . ' siswa belum mencapai KKM)';
        }
    } else {
        $lines[] = '- Tidak ada data mata pelajaran yang belum mencapai KKM.';
    }

    $lines[] = '';
    $lines[] = 'REKOMENDASI';
    $lines[] = '1. Untuk siswa yang memenuhi KKM: Mempertahankan dan meningkatkan pencapaian belajar.';
    $lines[] = '2. Untuk siswa yang belum memenuhi KKM: Melakukan pembinaan, pendampingan, atau remedial pada mata pelajaran yang belum mencapai KKM.';

    $tmp = tempnam(sys_get_temp_dir(), 'docx');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
    $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Rekomendasi Prestasi Siswa</dc:title><dc:creator>Sistem Prestasi Siswa</dc:creator><cp:lastModifiedBy>Sistem Prestasi Siswa</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:modified></cp:coreProperties>');
    $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Sistem Prestasi Siswa</Application></Properties>');

    $paragraphXml = '';
    foreach ($lines as $line) {
        $safe = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
        $paragraphXml .= '<w:p><w:r><w:t xml:space="preserve">' . $safe . '</w:t></w:r></w:p>';
    }
    $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $paragraphXml . '</w:body></w:document>');
    $zip->close();
    return $tmp;
}

function buat_file_pdf_rekomendasi(array $ringkasan): string
{
    $lines = [
        'LAPORAN REKOMENDASI PRESTASI SISWA',
        'Identitas Aplikasi/Sekolah: Sistem Prestasi Siswa SD Negeri 060921 Medan',
        'KKM Sekolah: ' . number_format($ringkasan['kkm'], 0, ',', '.'),
        'Jumlah Seluruh Siswa: ' . $ringkasan['total_siswa'],
        'Siswa Memenuhi KKM: ' . $ringkasan['jumlah_memenuhi'] . ' (' . $ringkasan['persen_memenuhi'] . '%)',
        'Siswa Belum Memenuhi KKM: ' . $ringkasan['jumlah_belum'] . ' (' . $ringkasan['persen_belum'] . '%)',
        '',
        'Kesimpulan: ' . $ringkasan['kesimpulan'],
        '',
        'Daftar siswa memenuhi KKM:',
    ];
    foreach ($ringkasan['memenuhi'] as $s) {
        $lines[] = '- ' . $s['nama'] . ' | Nilai ' . $s['nilai'] . ' | Status ' . $s['status'];
    }
    $lines[] = '';
    $lines[] = 'Daftar siswa membutuhkan pembinaan:';
    foreach ($ringkasan['butuh_pembinaan'] as $s) {
        $mapel = $s['mapel_belum'] ? implode(', ', $s['mapel_belum']) : '-';
        $lines[] = '- ' . $s['nama'] . ' | Nilai ' . $s['nilai'] . ' | Mata Pelajaran: ' . $mapel;
    }
    $lines[] = '';
    $lines[] = 'Rekomendasi: ' . implode(' ', $ringkasan['rekomendasi_umum']);

    $stream = '';
    foreach ($lines as $index => $line) {
        $y = 790 - ($index * 18);
        $stream .= "BT /F1 11 Tf 50 {$y} Td (" . pdf_escape($line) . ") Tj ET\n";
    }
    $tmp = tempnam(sys_get_temp_dir(), 'pdf');
    $pdf = "%PDF-1.4\n";
    $objects = [
        "<< /Type /Catalog /Pages 2 0 R >>",
        "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
        "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
        "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
        "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream"
    ];
    $offsets = [0];
    foreach ($objects as $i => $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
    }
    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
    file_put_contents($tmp, $pdf);
    return $tmp;
}

$jenis = $_GET['jenis'] ?? '';
$kolomMapel = get_kolom_mapel($pdo);
$mpKeys = mp_keys($kolomMapel);
$mpLabels = mp_labels($kolomMapel);

switch ($jenis) {
    case 'analisis':
    case 'analisis_excel': {
        $data = ambil_semua_siswa($pdo, $kolomMapel);
        $header = array_merge(['Nama Siswa'], $mpLabels, ['Kehadiran', 'Kedisiplinan', 'Rata-rata', 'Kategori']);
        $rows = [];
        foreach ($data as $d) {
            $r = [$d['nama']];
            foreach ($mpKeys as $k) $r[] = $d[$k];
            $r[] = $d['kehadiran']; $r[] = $d['kedisiplinan']; $r[] = $d['rata_rata']; $r[] = $d['kategori'];
            $rows[] = $r;
        }
        XlsxWriter::download('Data_Analisis_Siswa.xlsx', 'Data Siswa', $header, $rows);
        break;
    }

    case 'analisis_word': {
        $data = ambil_semua_siswa($pdo, $kolomMapel);
        $lines = ['LAPORAN ANALISIS SISWA', 'Jumlah siswa: ' . count($data), 'Mata pelajaran: ' . implode(', ', $mpLabels)];
        foreach ($data as $i => $d) {
            $nilaiMapel = [];
            foreach ($mpKeys as $k) $nilaiMapel[] = $k . ': ' . $d[$k];
            $lines[] = ($i + 1) . '. ' . $d['nama'] . ' | Rata-rata: ' . $d['rata_rata'] . ' | Kategori: ' . $d['kategori'] . ' | ' . implode(' | ', $nilaiMapel);
        }
        $tmp = buat_file_docx_rekomendasi(['kkm' => KKM, 'total_siswa' => count($data), 'jumlah_memenuhi' => count(array_filter($data, fn($d) => $d['kategori'] === 'Berprestasi')), 'persen_memenuhi' => 0, 'jumlah_belum' => 0, 'persen_belum' => 0, 'kesimpulan' => 'Laporan analisis siswa.', 'memenuhi' => [], 'butuh_pembinaan' => [], 'mapel_diperhatikan' => [], 'rekomendasi_umum' => []]);
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Laporan Analisis Siswa</dc:title><dc:creator>Sistem Prestasi Siswa</dc:creator><cp:lastModifiedBy>Sistem Prestasi Siswa</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:modified></cp:coreProperties>');
        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Sistem Prestasi Siswa</Application></Properties>');
        $paragraphXml = '';
        foreach ($lines as $line) {
            $safe = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            $paragraphXml .= '<w:p><w:r><w:t xml:space="preserve">' . $safe . '</w:t></w:r></w:p>';
        }
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $paragraphXml . '</w:body></w:document>');
        $zip->close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="Laporan_Analisis_Siswa.docx"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'analisis_pdf': {
        $data = ambil_semua_siswa($pdo, $kolomMapel);
        $lines = ['LAPORAN ANALISIS SISWA', 'Jumlah siswa: ' . count($data), 'Mata pelajaran: ' . implode(', ', $mpLabels)];
        foreach ($data as $i => $d) {
            $lines[] = ($i + 1) . '. ' . $d['nama'] . ' | Rata-rata: ' . $d['rata_rata'] . ' | Kategori: ' . $d['kategori'];
        }
        $stream = '';
        foreach ($lines as $index => $line) {
            $y = 790 - ($index * 18);
            $stream .= "BT /F1 11 Tf 50 {$y} Td (" . pdf_escape($line) . ") Tj ET\n";
        }
        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        $pdf = "%PDF-1.4\n";
        $objects = [
            "<< /Type /Catalog /Pages 2 0 R >>",
            "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
            "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
            "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream"
        ];
        $offsets = [0];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
        file_put_contents($tmp, $pdf);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Laporan_Analisis_Siswa.pdf"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'hasil_analisis': {
        $data = ambil_semua_siswa($pdo, $kolomMapel);
        $stat = statistik_mapel($data, $kolomMapel);
        $writer = new XlsxWriter();
        $header1 = array_merge(['Nama Siswa'], $mpLabels, ['Kehadiran', 'Kedisiplinan', 'Rata-rata', 'Kategori']);
        $rows1 = [];
        foreach ($data as $d) {
            $r = [$d['nama']];
            foreach ($mpKeys as $k) $r[] = $d[$k];
            $r[] = $d['kehadiran']; $r[] = $d['kedisiplinan']; $r[] = $d['rata_rata']; $r[] = $d['kategori'];
            $rows1[] = $r;
        }
        $writer->tambahSheet('Data Siswa', $header1, $rows1);

        $header2 = ['Mata Pelajaran', 'Min', 'Maks', 'Rata-rata', 'Std Dev', 'Median', 'KKM', 'Status'];
        $rows2 = [];
        foreach ($stat as $s) $rows2[] = [$s['label'], $s['min'], $s['max'], $s['rata'], $s['std'], $s['median'], KKM, $s['status']];
        $writer->tambahSheet('Statistik per Mapel', $header2, $rows2);

        $writer->kirimDownload('Hasil_Analisis.xlsx');
        break;
    }

    case 'hasil_analisis_word': {
        $data = ambil_semua_siswa($pdo, $kolomMapel);
        $stat = statistik_mapel($data, $kolomMapel);
        $lines = ['LAPORAN HASIL ANALISIS', 'Jumlah siswa: ' . count($data), 'KKM: ' . KKM];
        foreach ($stat as $s) {
            $lines[] = '- ' . $s['label'] . ' | Rata-rata: ' . $s['rata'] . ' | Status: ' . $s['status'];
        }
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Laporan Hasil Analisis</dc:title><dc:creator>Sistem Prestasi Siswa</dc:creator><cp:lastModifiedBy>Sistem Prestasi Siswa</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:modified></cp:coreProperties>');
        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Sistem Prestasi Siswa</Application></Properties>');
        $paragraphXml = '';
        foreach ($lines as $line) {
            $safe = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            $paragraphXml .= '<w:p><w:r><w:t xml:space="preserve">' . $safe . '</w:t></w:r></w:p>';
        }
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $paragraphXml . '</w:body></w:document>');
        $zip->close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="Laporan_Hasil_Analisis.docx"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'hasil_analisis_pdf': {
        $data = ambil_semua_siswa($pdo, $kolomMapel);
        $stat = statistik_mapel($data, $kolomMapel);
        $lines = ['LAPORAN HASIL ANALISIS', 'Jumlah siswa: ' . count($data), 'KKM: ' . KKM];
        foreach ($stat as $s) {
            $lines[] = '- ' . $s['label'] . ' | Rata-rata: ' . $s['rata'] . ' | Status: ' . $s['status'];
        }
        $stream = '';
        foreach ($lines as $index => $line) {
            $y = 790 - ($index * 18);
            $stream .= "BT /F1 11 Tf 50 {$y} Td (" . pdf_escape($line) . ") Tj ET\n";
        }
        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        $pdf = "%PDF-1.4\n";
        $objects = [
            "<< /Type /Catalog /Pages 2 0 R >>",
            "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
            "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
            "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream"
        ];
        $offsets = [0];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
        file_put_contents($tmp, $pdf);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Laporan_Hasil_Analisis.pdf"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'klasifikasi': {
        $data = ambil_semua_siswa($pdo, $kolomMapel);
        $writer = new XlsxWriter();
        $header = array_merge(['Nama Siswa'], $mpLabels, ['Kehadiran', 'Kedisiplinan', 'Rata-rata']);
        foreach (['Berprestasi' => 'Berprestasi', 'Butuh Pembinaan' => 'Butuh Pembinaan'] as $kat => $sheetNm) {
            $rows = [];
            foreach ($data as $d) {
                if ($d['kategori'] !== $kat) continue;
                $r = [$d['nama']];
                foreach ($mpKeys as $k) $r[] = $d[$k];
                $r[] = $d['kehadiran']; $r[] = $d['kedisiplinan']; $r[] = $d['rata_rata'];
                $rows[] = $r;
            }
            $writer->tambahSheet($sheetNm, $header, $rows);
        }
        $writer->kirimDownload('Klasifikasi_Siswa.xlsx');
        break;
    }

    case 'klasifikasi_word': {
        $data = ambil_semua_siswa($pdo, $kolomMapel);
        $lines = ['LAPORAN KLASIFIKASI SISWA'];
        foreach (['Berprestasi', 'Butuh Pembinaan'] as $kategori) {
            $filtered = array_values(array_filter($data, fn($d) => $d['kategori'] === $kategori));
            $lines[] = 'Kategori: ' . $kategori . ' (' . count($filtered) . ' siswa)';
            foreach ($filtered as $d) {
                $lines[] = '- ' . $d['nama'] . ' | Rata-rata: ' . $d['rata_rata'] . ' | Kehadiran: ' . $d['kehadiran'] . '%';
            }
            $lines[] = '';
        }
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Laporan Klasifikasi Siswa</dc:title><dc:creator>Sistem Prestasi Siswa</dc:creator><cp:lastModifiedBy>Sistem Prestasi Siswa</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:modified></cp:coreProperties>');
        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Sistem Prestasi Siswa</Application></Properties>');
        $paragraphXml = '';
        foreach ($lines as $line) {
            $safe = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            $paragraphXml .= '<w:p><w:r><w:t xml:space="preserve">' . $safe . '</w:t></w:r></w:p>';
        }
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $paragraphXml . '</w:body></w:document>');
        $zip->close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="Laporan_Klasifikasi_Siswa.docx"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'klasifikasi_pdf': {
        $data = ambil_semua_siswa($pdo, $kolomMapel);
        $lines = ['LAPORAN KLASIFIKASI SISWA'];
        foreach (['Berprestasi', 'Butuh Pembinaan'] as $kategori) {
            $filtered = array_values(array_filter($data, fn($d) => $d['kategori'] === $kategori));
            $lines[] = 'Kategori: ' . $kategori . ' (' . count($filtered) . ' siswa)';
            foreach ($filtered as $d) {
                $lines[] = '- ' . $d['nama'] . ' | Rata-rata: ' . $d['rata_rata'] . ' | Kehadiran: ' . $d['kehadiran'] . '%';
            }
            $lines[] = '';
        }
        $stream = '';
        foreach ($lines as $index => $line) {
            $y = 790 - ($index * 18);
            $stream .= "BT /F1 11 Tf 50 {$y} Td (" . pdf_escape($line) . ") Tj ET\n";
        }
        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        $pdf = "%PDF-1.4\n";
        $objects = [
            "<< /Type /Catalog /Pages 2 0 R >>",
            "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
            "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
            "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream"
        ];
        $offsets = [0];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
        file_put_contents($tmp, $pdf);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Laporan_Klasifikasi_Siswa.pdf"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'evaluasi': {
        $stmt = $pdo->query('SELECT * FROM hasil_model ORDER BY id DESC LIMIT 1');
        $model = $stmt->fetch();
        if (!$model) { die('Model belum dilatih.'); }
        $cm = json_decode($model['cm_json'], true) ?: [[0,0],[0,0]];
        $bobot = json_decode($model['bobot_json'], true) ?: [];
        $writer = new XlsxWriter();
        $writer->tambahSheet('Evaluasi Model', ['Metrik', 'Nilai', 'Persen'], [
            ['Akurasi', round($model['akurasi'], 4), round($model['akurasi']*100, 2) . '%'],
            ['Presisi', round($model['presisi'], 4), round($model['presisi']*100, 2) . '%'],
            ['Recall', round($model['recall_score'], 4), round($model['recall_score']*100, 2) . '%'],
            ['F1-Score', round($model['f1_score'], 4), round($model['f1_score']*100, 2) . '%'],
        ]);
        $writer->tambahSheet('Confusion Matrix', ['', 'Prediksi: Butuh Pembinaan', 'Prediksi: Berprestasi'], [
            ['Aktual: Butuh Pembinaan', $cm[0][0] ?? 0, $cm[0][1] ?? 0],
            ['Aktual: Berprestasi', $cm[1][0] ?? 0, $cm[1][1] ?? 0],
        ]);
        $rowsBobot = [];
        foreach ($bobot as $k => $v) $rowsBobot[] = [$k, round($v, 4)];
        $writer->tambahSheet('Bobot Fitur', ['Fitur', 'Koefisien Absolut'], $rowsBobot);
        $writer->kirimDownload('Evaluasi_Model.xlsx');
        break;
    }

    case 'evaluasi_word': {
        $stmt = $pdo->query('SELECT * FROM hasil_model ORDER BY id DESC LIMIT 1');
        $model = $stmt->fetch();
        if (!$model) { die('Model belum dilatih.'); }
        $lines = ['LAPORAN EVALUASI MODEL', 'Akurasi: ' . round($model['akurasi'] * 100, 2) . '%', 'Presisi: ' . round($model['presisi'] * 100, 2) . '%', 'Recall: ' . round($model['recall_score'] * 100, 2) . '%', 'F1-Score: ' . round($model['f1_score'] * 100, 2) . '%', 'Dilatih pada: ' . format_tanggal($model['waktu'])];
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Laporan Evaluasi Model</dc:title><dc:creator>Sistem Prestasi Siswa</dc:creator><cp:lastModifiedBy>Sistem Prestasi Siswa</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:modified></cp:coreProperties>');
        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Sistem Prestasi Siswa</Application></Properties>');
        $paragraphXml = '';
        foreach ($lines as $line) {
            $safe = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            $paragraphXml .= '<w:p><w:r><w:t xml:space="preserve">' . $safe . '</w:t></w:r></w:p>';
        }
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $paragraphXml . '</w:body></w:document>');
        $zip->close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="Laporan_Evaluasi_Model.docx"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'evaluasi_pdf': {
        $stmt = $pdo->query('SELECT * FROM hasil_model ORDER BY id DESC LIMIT 1');
        $model = $stmt->fetch();
        if (!$model) { die('Model belum dilatih.'); }
        $lines = ['LAPORAN EVALUASI MODEL', 'Akurasi: ' . round($model['akurasi'] * 100, 2) . '%', 'Presisi: ' . round($model['presisi'] * 100, 2) . '%', 'Recall: ' . round($model['recall_score'] * 100, 2) . '%', 'F1-Score: ' . round($model['f1_score'] * 100, 2) . '%', 'Dilatih pada: ' . format_tanggal($model['waktu'])];
        $stream = '';
        foreach ($lines as $index => $line) {
            $y = 790 - ($index * 18);
            $stream .= "BT /F1 11 Tf 50 {$y} Td (" . pdf_escape($line) . ") Tj ET\n";
        }
        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        $pdf = "%PDF-1.4\n";
        $objects = [
            "<< /Type /Catalog /Pages 2 0 R >>",
            "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
            "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
            "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream"
        ];
        $offsets = [0];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
        file_put_contents($tmp, $pdf);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Laporan_Evaluasi_Model.pdf"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'hasil_prediksi': {
        $prediksi = $_SESSION['hasil_prediksi'] ?? null;
        if (!$prediksi) { die('Belum ada hasil prediksi pada sesi ini. Silakan latih model terlebih dahulu di halaman Prediksi.'); }
        $header = ['Nama Siswa', 'Rata-rata Aktual', 'Kategori Aktual', 'Hasil Prediksi', 'Probabilitas (%)'];
        $rows = [];
        foreach ($prediksi as $p) $rows[] = [$p['nama'], $p['rata_rata'], $p['kategori'], $p['prediksi'], $p['probabilitas']];
        XlsxWriter::download('Hasil_Prediksi.xlsx', 'Hasil Prediksi', $header, $rows);
        break;
    }

    case 'hasil_prediksi_word': {
        $prediksi = $_SESSION['hasil_prediksi'] ?? null;
        if (!$prediksi) { die('Belum ada hasil prediksi pada sesi ini. Silakan latih model terlebih dahulu di halaman Prediksi.'); }
        $lines = ['LAPORAN HASIL PREDIKSI'];
        foreach ($prediksi as $i => $p) {
            $lines[] = ($i + 1) . '. ' . $p['nama'] . ' | Kategori Aktual: ' . $p['kategori'] . ' | Prediksi: ' . $p['prediksi'] . ' | Probabilitas: ' . $p['probabilitas'] . '%';
        }
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Laporan Hasil Prediksi</dc:title><dc:creator>Sistem Prestasi Siswa</dc:creator><cp:lastModifiedBy>Sistem Prestasi Siswa</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:modified></cp:coreProperties>');
        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Sistem Prestasi Siswa</Application></Properties>');
        $paragraphXml = '';
        foreach ($lines as $line) {
            $safe = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            $paragraphXml .= '<w:p><w:r><w:t xml:space="preserve">' . $safe . '</w:t></w:r></w:p>';
        }
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $paragraphXml . '</w:body></w:document>');
        $zip->close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="Laporan_Hasil_Prediksi.docx"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'hasil_prediksi_pdf': {
        $prediksi = $_SESSION['hasil_prediksi'] ?? null;
        if (!$prediksi) { die('Belum ada hasil prediksi pada sesi ini. Silakan latih model terlebih dahulu di halaman Prediksi.'); }
        $lines = ['LAPORAN HASIL PREDIKSI'];
        foreach ($prediksi as $i => $p) {
            $lines[] = ($i + 1) . '. ' . $p['nama'] . ' | Kategori Aktual: ' . $p['kategori'] . ' | Prediksi: ' . $p['prediksi'] . ' | Probabilitas: ' . $p['probabilitas'] . '%';
        }
        $stream = '';
        foreach ($lines as $index => $line) {
            $y = 790 - ($index * 18);
            $stream .= "BT /F1 11 Tf 50 {$y} Td (" . pdf_escape($line) . ") Tj ET\n";
        }
        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        $pdf = "%PDF-1.4\n";
        $objects = [
            "<< /Type /Catalog /Pages 2 0 R >>",
            "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
            "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
            "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream"
        ];
        $offsets = [0];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
        file_put_contents($tmp, $pdf);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Laporan_Hasil_Prediksi.pdf"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'pengujian': {
        $baris = $_SESSION['baris_pengujian'] ?? null;
        if (!$baris) { die('Belum ada hasil pengujian. Silakan buka halaman Pengujian Sistem terlebih dahulu.'); }
        $header = ['No', 'Fitur', 'Skenario Pengujian', 'Hasil yang Diharapkan', 'Hasil Aktual', 'Status'];
        $rows = [];
        foreach ($baris as $b) $rows[] = [$b['No'], $b['Fitur'], $b['Skenario'], $b['Diharapkan'], $b['Aktual'], $b['Status']];
        XlsxWriter::download('Hasil_Pengujian_Sistem.xlsx', 'Hasil Pengujian', $header, $rows);
        break;
    }

    case 'pengujian_word': {
        $baris = $_SESSION['baris_pengujian'] ?? null;
        if (!$baris) { die('Belum ada hasil pengujian. Silakan buka halaman Pengujian Sistem terlebih dahulu.'); }
        $lines = ['LAPORAN PENGUJIAN SISTEM'];
        foreach ($baris as $b) {
            $lines[] = $b['No'] . '. ' . $b['Fitur'] . ' | Status: ' . $b['Status'] . ' | Hasil: ' . $b['Aktual'];
        }
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Laporan Pengujian Sistem</dc:title><dc:creator>Sistem Prestasi Siswa</dc:creator><cp:lastModifiedBy>Sistem Prestasi Siswa</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">2026-08-12T00:00:00Z</dcterms:modified></cp:coreProperties>');
        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Sistem Prestasi Siswa</Application></Properties>');
        $paragraphXml = '';
        foreach ($lines as $line) {
            $safe = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            $paragraphXml .= '<w:p><w:r><w:t xml:space="preserve">' . $safe . '</w:t></w:r></w:p>';
        }
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $paragraphXml . '</w:body></w:document>');
        $zip->close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="Laporan_Pengujian_Sistem.docx"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'pengujian_pdf': {
        $baris = $_SESSION['baris_pengujian'] ?? null;
        if (!$baris) { die('Belum ada hasil pengujian. Silakan buka halaman Pengujian Sistem terlebih dahulu.'); }
        $lines = ['LAPORAN PENGUJIAN SISTEM'];
        foreach ($baris as $b) {
            $lines[] = $b['No'] . '. ' . $b['Fitur'] . ' | Status: ' . $b['Status'] . ' | Hasil: ' . $b['Aktual'];
        }
        $stream = '';
        foreach ($lines as $index => $line) {
            $y = 790 - ($index * 18);
            $stream .= "BT /F1 11 Tf 50 {$y} Td (" . pdf_escape($line) . ") Tj ET\n";
        }
        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        $pdf = "%PDF-1.4\n";
        $objects = [
            "<< /Type /Catalog /Pages 2 0 R >>",
            "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
            "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
            "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream"
        ];
        $offsets = [0];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
        file_put_contents($tmp, $pdf);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Laporan_Pengujian_Sistem.pdf"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'rekomendasi_word':
    case 'word': {
        $ringkasan = hitung_rekomendasi_prestasi($pdo, $kolomMapel);
        $tmp = buat_file_docx_rekomendasi($ringkasan);
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="Rekomendasi_Prestasi_Siswa.docx"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'rekomendasi_pdf':
    case 'pdf': {
        $ringkasan = hitung_rekomendasi_prestasi($pdo, $kolomMapel);
        $tmp = buat_file_pdf_rekomendasi($ringkasan);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Rekomendasi_Prestasi_Siswa.pdf"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    case 'rekomendasi_excel':
    case 'excel': {
        $ringkasan = hitung_rekomendasi_prestasi($pdo, $kolomMapel);
        $writer = new XlsxWriter();

        $writer->tambahSheet('Ringkasan', ['Indikator', 'Jumlah', 'Persentase'], [
            ['Jumlah seluruh siswa', $ringkasan['total_siswa'], '100%'],
            ['KKM sekolah', $ringkasan['kkm'], '-'],
            ['Jumlah siswa yang memenuhi KKM', $ringkasan['jumlah_memenuhi'], $ringkasan['persen_memenuhi'] . '%'],
            ['Jumlah siswa yang belum memenuhi KKM', $ringkasan['jumlah_belum'], $ringkasan['persen_belum'] . '%'],
        ]);

        $writer->tambahSheet('Memenuhi KKM', ['No', 'Nama Siswa', 'Nilai/Rata-rata', 'KKM', 'Selisih', 'Status'], array_map(fn($s) => [$s['no'], $s['nama'], $s['nilai'], $s['kkm'], $s['selisih'], $s['status']], $ringkasan['memenuhi']));

        $writer->tambahSheet('Membutuhkan Pembinaan', ['No', 'Nama Siswa', 'Nilai/Rata-rata', 'KKM', 'Selisih', 'Status', 'Mata Pelajaran yang Belum Mencapai KKM', 'Rekomendasi'], array_map(fn($s) => [$s['no'], $s['nama'], $s['nilai'], $s['kkm'], $s['selisih'], $s['status'], implode(', ', $s['mapel_belum']), $s['rekomendasi']], $ringkasan['butuh_pembinaan']));

        $writer->kirimDownload('Rekomendasi_Prestasi_Siswa.xlsx');
        break;
    }

    default:
        http_response_code(400);
        die('Jenis export tidak dikenali.');
}
