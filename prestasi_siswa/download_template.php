<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/lib/XlsxWriter.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_login();

$kolomMapel = get_kolom_mapel($pdo);
$header = array_merge(['Nama Siswa'], mp_labels($kolomMapel), ['Kehadiran', 'Kedisiplinan']);
$contoh = array_merge(
    ['Contoh Nama Siswa'],
    array_fill(0, count($kolomMapel), 85),
    [95, 'Baik']
);
XlsxWriter::download('Template_Import_Data_Siswa.xlsx', 'Template', $header, [$contoh]);
