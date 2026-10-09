================================================================
  SISTEM ANALISIS & PREDIKSI PRESTASI BELAJAR SISWA (VERSI WEB)
  SD Negeri 060921 Medan - Kelas V
  Metode : Binary Logistic Regression
  Stack  : PHP + MySQL (XAMPP) + Python (untuk modul Machine Learning)
  Versi  : 1.0.0 (Web)
================================================================

DESKRIPSI
---------
Ini adalah versi web dari aplikasi desktop "Sistem Analisis & Prediksi
Prestasi Belajar Siswa". Seluruh fitur aplikasi desktop asli sudah
dipindahkan ke web berbasis PHP + MySQL yang berjalan di XAMPP:
Dashboard, Import Data, Analisis, Hasil Analisis, Prediksi (Machine
Learning), Hasil Prediksi, Klasifikasi Siswa, Evaluasi Model,
Pengujian Sistem, dan Tentang Aplikasi.

Karena scikit-learn (library Machine Learning) tidak tersedia di PHP,
fitur pelatihan model Logistic Regression tetap memakai Python:
PHP memanggil script python/train_model.py di background, lalu
menampilkan hasilnya di halaman web. Jadi komputer/server XAMPP Anda
butuh Python 3 terpasang juga (lihat langkah instalasi di bawah).


PERSYARATAN SISTEM
-------------------
1. XAMPP (Apache + MySQL/MariaDB + PHP 8.x)
   Unduh di: https://www.apachefriends.org/
   - Ekstensi PHP yang dibutuhkan (sudah aktif secara default di XAMPP,
     tidak perlu diaktifkan manual): pdo_mysql, mbstring, zip, json, simplexml/xml
   - Jika memakai PHP di luar XAMPP (mis. paket PHP minimal di Linux) dan
     muncul error "Call to undefined function simplexml_load_string()"
     saat Import Data, aktifkan ekstensi tsb (contoh Ubuntu: apt install php-xml)
2. Python 3.9 atau lebih baru (untuk fitur Prediksi)
   Unduh di: https://www.python.org/downloads/
   - Saat instalasi di Windows, centang "Add Python to PATH"


CARA INSTALASI
---------------
1. INSTAL XAMPP
   - Instal XAMPP seperti biasa, lalu buka XAMPP Control Panel.
   - Jalankan (Start) modul "Apache" dan "MySQL".

2. SALIN FOLDER APLIKASI
   - Salin folder "prestasi_siswa" (folder ini) ke dalam folder htdocs XAMPP:
     Windows : C:\xampp\htdocs\prestasi_siswa
     macOS   : /Applications/XAMPP/htdocs/prestasi_siswa
     Linux   : /opt/lampp/htdocs/prestasi_siswa

3. BUAT & IMPOR DATABASE
   - Buka browser, akses phpMyAdmin: http://localhost/phpmyadmin
   - Klik menu "Import" (atau "SQL" lalu tempel isi file database.sql)
   - Pilih file "database.sql" yang ada di folder aplikasi ini, lalu klik "Go"/"Kirim".
   - Database "db_prestasi_siswa" akan dibuat kosong dan diisi melalui halaman Import Data.

   Catatan: Jika MySQL XAMPP Anda memakai username/password berbeda dari
   default ("root" tanpa password), sesuaikan di file config/db.php.

4. INSTAL LIBRARY PYTHON (untuk fitur Prediksi)
   - Buka Command Prompt / Terminal, masuk ke folder aplikasi, lalu jalankan:

     Windows :  cd C:\xampp\htdocs\prestasi_siswa\python
                pip install -r requirements.txt

     macOS/Linux :  cd /opt/lampp/htdocs/prestasi_siswa/python
                    pip3 install -r requirements.txt

   - Library yang terpasang: numpy, pandas, scikit-learn

5. SESUAIKAN PATH PYTHON (jika perlu)
   - Buka file config/db.php, cari baris:
        define('PYTHON_BIN', 'python3');
   - Windows umumnya memakai perintah "python" (bukan "python3").
     Jika fitur Prediksi gagal dijalankan, ganti nilainya menjadi 'python',
     atau isi dengan path lengkap, contoh:
        define('PYTHON_BIN', 'C:\\Python311\\python.exe');
   - Cek versi Python yang aktif dengan mengetik: python --version  atau  python3 --version

6. AKSES APLIKASI
   - Buka browser, akses: http://localhost/prestasi_siswa/
   - Anda akan diarahkan ke halaman Login.


LOGIN PERTAMA KALI
--------------------
1. Di halaman Login, klik "Daftar di sini".
2. Isi Nama Lengkap, Username (min. 4 karakter), dan Password (min. 6 karakter).
3. Klik tombol "Daftar", lalu login dengan akun yang baru dibuat.


FITUR APLIKASI
---------------
1. Dashboard
   - Statistik ringkas jumlah siswa, mata pelajaran, dll.
   - Grafik distribusi prestasi (pie chart) & rata-rata nilai per mapel (bar chart)
   - Tabel 5 siswa nilai tertinggi & terendah

2. Import Data
   - Import dari file Excel (.xlsx) atau CSV
   - Preview data setelah import
   - Unduh template Excel
   - Catatan: import file Word (.docx) tidak tersedia pada versi web ini.
     Silakan konversi data Word ke Excel/CSV terlebih dahulu.

3. Analisis
   - Tabel data lengkap semua siswa dengan pencarian, filter, dan sorting
   - Download hasil analisis (Excel)

4. Hasil Analisis
   - Statistik per mata pelajaran, grafik rata-rata nilai,
     grafik hubungan kehadiran & kedisiplinan dengan prestasi
   - Download hasil analisis (Excel)

5. Prediksi
   - Latih model Binary Logistic Regression (memanggil Python di background)
   - Log proses pelatihan ditampilkan langsung di halaman web
   - Tampil akurasi setelah pelatihan

6. Hasil Prediksi
   - Tabel prediksi per siswa: Nama, Rata-rata, Kategori Aktual, Hasil Prediksi, Probabilitas
   - Filter & pencarian, download hasil prediksi (Excel)
   - Catatan: hasil prediksi disimpan di session, jadi akan hilang saat logout.
     Latih ulang model dari halaman Prediksi setelah login kembali jika dibutuhkan.

7. Klasifikasi Siswa
   - Daftar lengkap siswa dengan kategori, filter Semua/Berprestasi/Butuh Pembinaan
   - Download klasifikasi (Excel)

8. Evaluasi Model
   - Kartu metrik: Akurasi, Presisi, Recall, F1-Score
   - Confusion Matrix, bobot fitur (feature importance)
   - Download evaluasi model (Excel)

9. Pengujian Sistem
   - Tabel hasil pengujian 13 fitur aplikasi (BERHASIL/GAGAL otomatis)
   - Download hasil pengujian (Excel)

10. Tentang Aplikasi
    - Informasi sistem, metode, dan alur kerja aplikasi


FORMAT FILE IMPORT (Excel/CSV)
--------------------------------
File yang diimpor harus memiliki kolom minimal:
- Nama Siswa   (wajib)
- Kehadiran    (wajib, dalam %)
- Kedisiplinan (wajib: Baik / Cukup / Kurang)
- Minimal 1 kolom nilai mata pelajaran (bebas nama & jumlahnya)

Nama kolom nilai mata pelajaran BEBAS - sistem otomatis menyesuaikan
jumlah dan nama variabel sesuai file yang diimpor.

Gunakan tombol "Unduh Template Excel" di halaman Import Data untuk
mendapatkan format yang sudah benar.

Untuk file CSV: gunakan delimiter titik-koma (;) atau koma (,).


DATABASE
---------
Data disimpan di MySQL/MariaDB, database "db_prestasi_siswa", terdiri dari
tabel: pengguna, siswa, konfigurasi, hasil_model, log_import.
Kelola langsung lewat phpMyAdmin bila diperlukan (backup, reset, dll).


CATATAN PENELITIAN
--------------------
Penelitian Skripsi:
"Analisis dan Prediksi Prestasi Belajar Siswa Kelas V
Menggunakan Logistic Regression di SD Negeri 060921 Medan"

Metode        : Binary Logistic Regression (scikit-learn)
Dataset       : Dibuat dinamis dari file import Excel/CSV terbaru
Variabel Input: Nilai mata pelajaran + Kehadiran + Kedisiplinan
KKM           : 80
Pembagian Data: 80% Data Latih - 20% Data Uji (Stratified)
Preprocessing : Label Encoding + Min-Max Normalization


TROUBLESHOOTING
-----------------
"Koneksi database gagal":
  - Pastikan modul MySQL di XAMPP Control Panel sudah berstatus "Running".
  - Pastikan database "db_prestasi_siswa" sudah diimpor dari database.sql.
  - Cek ulang DB_USER/DB_PASS di config/db.php sesuai pengaturan MySQL Anda.

"Tidak dapat menjalankan Python" / fitur Prediksi gagal:
  - Pastikan Python 3 sudah terinstall: ketik `python --version` atau
    `python3 --version` di Command Prompt/Terminal.
  - Pastikan library sudah terpasang: `pip install -r python/requirements.txt`
  - Sesuaikan PYTHON_BIN di config/db.php (lihat langkah instalasi no. 5).
  - Di Windows, beberapa hosting/PHP membatasi proc_open(); pastikan fungsi
    proc_open tidak dinonaktifkan di php.ini (cari "disable_functions").

Error saat Import file .xlsx:
  - Pastikan file benar-benar format Excel modern (.xlsx), bukan .xls lama.
  - Untuk .xls lama, buka dengan Excel/Google Sheets lalu simpan ulang
    sebagai .xlsx, atau export sebagai .csv.

Halaman blank / error 500:
  - Aktifkan tampilan error PHP sementara untuk debug (php.ini:
    display_errors = On), atau cek file log Apache di folder logs XAMPP.


================================================================
© 2025 - Sistem Prestasi Siswa | SD Negeri 060921 Medan
================================================================
