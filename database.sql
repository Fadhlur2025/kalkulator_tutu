-- Project: Kalkulator Tutu
-- Jalankan lewat phpMyAdmin (tab Import / SQL) atau: mysql -u root < database.sql
CREATE DATABASE IF NOT EXISTS kalkulator_tutu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kalkulator_tutu;

CREATE TABLE IF NOT EXISTS jenis_kalkulator (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  nama VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS riwayat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jenis_id INT NOT NULL,
  ekspresi VARCHAR(255) NOT NULL,
  hasil VARCHAR(255) NOT NULL,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (jenis_id) REFERENCES jenis_kalkulator(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO jenis_kalkulator (kode, nama) VALUES
 ('dasar','Dasar'),('persen','Persentase'),('diskon','Diskon'),
 ('bmi','BMI'),('suhu','Suhu'),('lingkaran','Lingkaran');
