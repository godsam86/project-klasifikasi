<?php
ob_start();
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
register_shutdown_function(function (): void {
    $error = error_get_last();
    $output = trim(ob_get_contents() ?: '');
    $isFatal = $error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true);
    if (!$isFatal && $output === '') return;
    if (!$isFatal && is_array(json_decode($output, true))) return;
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $pesan = $isFatal ? $error['message'] : strip_tags($output);
    echo json_encode(['sukses' => false, 'error' => 'Kesalahan server: ' . trim($pesan)], JSON_UNESCAPED_UNICODE);
});

header('Content-Type: application/json; charset=utf-8');

try {
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['pengguna'])) { http_response_code(401); echo json_encode(['sukses' => false, 'error' => 'Sesi berakhir, silakan login kembali.']); exit; }

$kolomMapel = get_kolom_mapel($pdo);
$data = ambil_semua_siswa($pdo, $kolomMapel);

if (count($data) < 10) {
    echo json_encode(['sukses' => false, 'error' => 'Data siswa terlalu sedikit untuk melatih model (minimal 10 siswa).']);
    exit;
}

$payload = json_encode([
    'kkm' => KKM,
    'mp_keys' => mp_keys($kolomMapel),
    'mp_labels' => mp_labels($kolomMapel),
    'siswa' => $data,
]);

$scriptPath = __DIR__ . '/python/train_model.py';
$descriptorspec = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];
$pythonCandidates = PHP_OS_FAMILY === 'Windows' ? ['python', 'py', 'python3'] : ['python3', 'python'];
$proc = false;
$pythonUsed = '';
foreach ($pythonCandidates as $pythonBin) {
    $command = escapeshellarg($pythonBin) . ' ' . escapeshellarg($scriptPath);
    $proc = @proc_open($command, $descriptorspec, $pipes);
    if (is_resource($proc)) {
        $pythonUsed = $pythonBin;
        break;
    }
}

if (!is_resource($proc)) {
    echo json_encode(['sukses' => false, 'error' => 'Python tidak dapat dijalankan. Pastikan Python tersedia di PATH Apache dan jalankan: pip install -r python/requirements.txt']);
    exit;
}

fwrite($pipes[0], $payload);
fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
fclose($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[2]);
$exitCode = proc_close($proc);

$hasil = json_decode($stdout, true);
if (!is_array($hasil)) {
    echo json_encode(['sukses' => false, 'error' => 'Gagal memproses output Python (' . $pythonUsed . '). ' . ($stderr ? 'Detail: ' . trim($stderr) : 'Output tidak valid: ' . substr(trim($stdout), 0, 500))]);
    exit;
}

if (!empty($hasil['sukses'])) {
    // Simpan hasil model ke database
    $stmt = $pdo->prepare(
        'INSERT INTO hasil_model (akurasi, presisi, recall_score, f1_score, cm_json, bobot_json) VALUES (?,?,?,?,?,?)'
    );
    $stmt->execute([
        $hasil['akurasi'], $hasil['presisi'], $hasil['recall'], $hasil['f1'],
        json_encode($hasil['cm']), json_encode($hasil['bobot_fitur']),
    ]);
    $idModel = $pdo->lastInsertId();

    // Simpan hasil prediksi per siswa ke session (untuk halaman Hasil Prediksi)
    $_SESSION['hasil_prediksi'] = $hasil['prediksi'];
    $_SESSION['id_model_terakhir'] = $idModel;
}

echo json_encode($hasil, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    echo json_encode(['sukses' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
