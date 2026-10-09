<?php
/**
 * Konfigurasi koneksi database MySQL (XAMPP).
 * Sesuaikan DB_HOST / DB_USER / DB_PASS jika berbeda dari default XAMPP.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_prestasi_siswa');
define('DB_USER', 'root');
define('DB_PASS', '');

define('KKM', 80);

// Path ke executable python (untuk fitur Prediksi Machine Learning).
// Windows biasanya: 'python'  |  macOS/Linux biasanya: 'python3'
define('PYTHON_BIN', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal. Pastikan MySQL di XAMPP sudah berjalan dan database "db_prestasi_siswa" '
        . 'sudah diimpor dari file database.sql.<br>Detail: ' . htmlspecialchars($e->getMessage()));
}
