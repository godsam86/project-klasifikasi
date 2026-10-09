-- =============================================================
-- Skema database Sistem Analisis & Prediksi Prestasi Belajar Siswa
-- Dataset dan daftar mata pelajaran dibuat melalui proses import.
-- =============================================================

CREATE DATABASE IF NOT EXISTS db_prestasi_siswa
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE db_prestasi_siswa;

CREATE TABLE IF NOT EXISTS pengguna (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_lengkap VARCHAR(150) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  waktu_daftar DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS konfigurasi (
  kunci VARCHAR(50) PRIMARY KEY,
  nilai TEXT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS siswa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150),
  nilai_json TEXT,
  kehadiran INT,
  kedisiplinan VARCHAR(20),
  rata_rata DECIMAL(6,2),
  kategori VARCHAR(30)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS hasil_model (
  id INT AUTO_INCREMENT PRIMARY KEY,
  akurasi DECIMAL(8,6),
  presisi DECIMAL(8,6),
  recall_score DECIMAL(8,6),
  f1_score DECIMAL(8,6),
  cm_json TEXT,
  bobot_json TEXT,
  waktu DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS log_import (
  id INT AUTO_INCREMENT PRIMARY KEY,
  waktu DATETIME DEFAULT CURRENT_TIMESTAMP,
  jumlah_siswa INT,
  nama_file VARCHAR(255)
) ENGINE=InnoDB;
