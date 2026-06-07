-- ============================================================
-- Klinik Sehat Bersama - Database Schema
-- MySQL 8.x
-- ============================================================

-- CREATE DATABASE IF NOT EXISTS klinik
--   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- USE klinik_sehat_bersama;

-- ============================================================
-- TABEL: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id_user         INT AUTO_INCREMENT PRIMARY KEY,
    nama            VARCHAR(100) NOT NULL,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    role            ENUM('pendaftaran','dokter','apoteker','kasir','manajer','admin') NOT NULL,
    is_active       TINYINT(1) DEFAULT 1,
    last_login      DATETIME NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: dokter
-- ============================================================
CREATE TABLE IF NOT EXISTS dokter (
    id_dokter       INT AUTO_INCREMENT PRIMARY KEY,
    nama_dokter     VARCHAR(100) NOT NULL,
    spesialisasi    VARCHAR(100) NOT NULL,
    no_sip          VARCHAR(50)  NOT NULL,
    jadwal_praktek  TEXT,
    id_user         INT NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: pasien
-- ============================================================
CREATE TABLE IF NOT EXISTS pasien (
    id_pasien       INT AUTO_INCREMENT PRIMARY KEY,
    no_rekam_medis  VARCHAR(20)  NOT NULL UNIQUE,
    nama_lengkap    VARCHAR(100) NOT NULL,
    tanggal_lahir   DATE         NOT NULL,
    jenis_kelamin   ENUM('L','P') NOT NULL,
    alamat          TEXT,
    no_telepon      VARCHAR(20),
    email           VARCHAR(100),
    golongan_darah  VARCHAR(5),
    alergi          TEXT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: antrian
-- ============================================================
CREATE TABLE IF NOT EXISTS antrian (
    id_antrian          INT AUTO_INCREMENT PRIMARY KEY,
    id_pasien           INT NOT NULL,
    id_dokter           INT NOT NULL,
    tanggal_kunjungan   DATE NOT NULL,
    no_antrian          INT NOT NULL,
    jam_kedatangan      TIME NOT NULL,
    status              ENUM('menunggu','dipanggil','selesai','batal') DEFAULT 'menunggu',
    id_petugas          INT NOT NULL,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pasien) REFERENCES pasien(id_pasien),
    FOREIGN KEY (id_dokter) REFERENCES dokter(id_dokter),
    FOREIGN KEY (id_petugas) REFERENCES users(id_user)
) ENGINE=InnoDB;

CREATE INDEX idx_antrian_tanggal ON antrian(tanggal_kunjungan);
CREATE INDEX idx_antrian_status  ON antrian(status);

-- ============================================================
-- TABEL: rekam_medis
-- ============================================================
CREATE TABLE IF NOT EXISTS rekam_medis (
    id_rekam_medis  INT AUTO_INCREMENT PRIMARY KEY,
    id_antrian      INT NOT NULL,
    id_pasien       INT NOT NULL,
    id_dokter       INT NOT NULL,
    tanggal_periksa DATETIME DEFAULT CURRENT_TIMESTAMP,
    anamnesis       TEXT,
    pemeriksaan_fisik TEXT,
    kode_icd        VARCHAR(20),
    diagnosis       VARCHAR(255),
    tindakan        TEXT,
    catatan_dokter  TEXT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_antrian) REFERENCES antrian(id_antrian),
    FOREIGN KEY (id_pasien)  REFERENCES pasien(id_pasien),
    FOREIGN KEY (id_dokter)  REFERENCES dokter(id_dokter)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: resep
-- ============================================================
CREATE TABLE IF NOT EXISTS resep (
    id_resep        INT AUTO_INCREMENT PRIMARY KEY,
    id_rekam_medis  INT NOT NULL,
    id_pasien       INT NOT NULL,
    id_dokter       INT NOT NULL,
    tanggal_resep   DATETIME DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('pending','diproses','siap','diambil') DEFAULT 'pending',
    id_apoteker     INT NULL,
    catatan         TEXT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rekam_medis) REFERENCES rekam_medis(id_rekam_medis),
    FOREIGN KEY (id_pasien)      REFERENCES pasien(id_pasien),
    FOREIGN KEY (id_dokter)      REFERENCES dokter(id_dokter),
    FOREIGN KEY (id_apoteker)    REFERENCES users(id_user)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: obat
-- ============================================================
CREATE TABLE IF NOT EXISTS obat (
    id_obat         INT AUTO_INCREMENT PRIMARY KEY,
    nama_obat       VARCHAR(150) NOT NULL,
    kategori        VARCHAR(100),
    satuan          VARCHAR(30)  NOT NULL,
    harga_beli      DECIMAL(10,2) DEFAULT 0,
    harga_jual      DECIMAL(10,2) DEFAULT 0,
    stok            INT DEFAULT 0,
    stok_minimum    INT DEFAULT 10,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: detail_resep
-- ============================================================
CREATE TABLE IF NOT EXISTS detail_resep (
    id_detail_resep INT AUTO_INCREMENT PRIMARY KEY,
    id_resep        INT NOT NULL,
    id_obat         INT NOT NULL,
    id_obat_substitusi INT NULL,
    jumlah          INT NOT NULL,
    dosis           VARCHAR(100),
    aturan_pakai    TEXT,
    harga_satuan    DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (id_resep) REFERENCES resep(id_resep) ON DELETE CASCADE,
    FOREIGN KEY (id_obat)  REFERENCES obat(id_obat),
    FOREIGN KEY (id_obat_substitusi) REFERENCES obat(id_obat)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: stok_obat_log
-- ============================================================
CREATE TABLE IF NOT EXISTS stok_obat_log (
    id_log          INT AUTO_INCREMENT PRIMARY KEY,
    id_obat         INT NOT NULL,
    tipe            ENUM('masuk','keluar') NOT NULL,
    jumlah          INT NOT NULL,
    stok_sesudah    INT NOT NULL,
    referensi_id    INT NULL,
    referensi_tipe  VARCHAR(50),
    keterangan      TEXT,
    id_user         INT NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_obat) REFERENCES obat(id_obat),
    FOREIGN KEY (id_user) REFERENCES users(id_user)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: pengadaan_obat
-- ============================================================
CREATE TABLE IF NOT EXISTS pengadaan_obat (
    id_pengadaan    INT AUTO_INCREMENT PRIMARY KEY,
    id_obat         INT NOT NULL,
    jumlah_pesan    INT NOT NULL,
    harga_beli      DECIMAL(10,2) DEFAULT 0,
    tgl_kadaluarsa  DATE NULL,
    supplier        VARCHAR(150),
    status          ENUM('draft','disetujui','diterima') DEFAULT 'draft',
    id_pengaju      INT NOT NULL,
    id_penyetuju    INT NULL,
    tgl_pengajuan   DATETIME DEFAULT CURRENT_TIMESTAMP,
    tgl_disetujui   DATETIME NULL,
    tgl_diterima    DATETIME NULL,
    FOREIGN KEY (id_obat)       REFERENCES obat(id_obat),
    FOREIGN KEY (id_pengaju)    REFERENCES users(id_user),
    FOREIGN KEY (id_penyetuju)  REFERENCES users(id_user)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: transaksi_pembayaran
-- ============================================================
CREATE TABLE IF NOT EXISTS transaksi_pembayaran (
    id_transaksi        INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi        VARCHAR(30) NOT NULL UNIQUE,
    id_antrian          INT NOT NULL,
    id_pasien           INT NOT NULL,
    biaya_konsultasi    DECIMAL(10,2) DEFAULT 0,
    biaya_obat          DECIMAL(10,2) DEFAULT 0,
    total_tagihan       DECIMAL(10,2) DEFAULT 0,
    metode_bayar        ENUM('tunai','transfer') DEFAULT 'tunai',
    jumlah_bayar        DECIMAL(10,2) DEFAULT 0,
    kembalian           DECIMAL(10,2) DEFAULT 0,
    status              ENUM('lunas','menunggu') DEFAULT 'menunggu',
    id_kasir            INT NOT NULL,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_antrian) REFERENCES antrian(id_antrian),
    FOREIGN KEY (id_pasien)  REFERENCES pasien(id_pasien),
    FOREIGN KEY (id_kasir)   REFERENCES users(id_user)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL: icd10_codes (autocomplete diagnosis)
-- ============================================================
CREATE TABLE IF NOT EXISTS icd10_codes (
    id_kode     INT AUTO_INCREMENT PRIMARY KEY,
    kode        VARCHAR(20) NOT NULL,
    nama_penyakit VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE INDEX idx_icd10_kode ON icd10_codes(kode);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Users (password di-hash dengan bcrypt)
-- admin123, dokter123, apoteker123, kasir123, petugas123, manajer123
INSERT INTO users (nama, username, password, role) VALUES
('Administrator', 'admin', '$2y$12$fmTgt/IcspZk2/qicJsLj.JJBz1d/t4EnbNmqU51PT8/xP6GyjNEK', 'admin'),
('Dr. Andi Pratama', 'dr_andi', '$2y$12$6PUwXqkePRTbUrog4kPoOuP7dUEI1V4SAMWn8uMByedwbj211xj.C', 'dokter'),
('Dr. Maya Sari', 'dr_maya', '$2y$12$6PUwXqkePRTbUrog4kPoOuP7dUEI1V4SAMWn8uMByedwbj211xj.C', 'dokter'),
('Sari Apoteker', 'apoteker', '$2y$12$6PUwXqkePRTbUrog4kPoOuP7dUEI1V4SAMWn8uMByedwbj211xj.C', 'apoteker'),
('Budi Kasir', 'kasir', '$2y$12$6PUwXqkePRTbUrog4kPoOuP7dUEI1V4SAMWn8uMByedwbj211xj.C', 'kasir'),
('Ani Petugas', 'petugas', '$2y$12$6PUwXqkePRTbUrog4kPoOuP7dUEI1V4SAMWn8uMByedwbj211xj.C', 'pendaftaran'),
('Eko Manajer', 'manajer', '$2y$12$6PUwXqkePRTbUrog4kPoOuP7dUEI1V4SAMWn8uMByedwbj211xj.C', 'manajer');

-- Dokter
INSERT INTO dokter (nama_dokter, spesialisasi, no_sip, jadwal_praktek, id_user) VALUES
('Dr. Andi Pratama', 'Umum', 'SIP-001-2026', 'Senin-Jumat 08:00-15:00', 2),
('Dr. Maya Sari', 'Gigi', 'SIP-002-2026', 'Senin-Kamis 09:00-14:00', 3);

-- Obat sample
INSERT INTO obat (nama_obat, kategori, satuan, harga_beli, harga_jual, stok, stok_minimum) VALUES
('Paracetamol 500mg', 'Analgesik', 'tablet', 500, 1500, 200, 50),
('Amoxicillin 500mg', 'Antibiotik', 'kapsul', 1200, 3500, 150, 30),
('Omeprazole 20mg', 'Antasida', 'kapsul', 800, 2500, 100, 20),
('Cetirizine 10mg', 'Antihistamin', 'tablet', 600, 2000, 180, 40),
('Metformin 500mg', 'Antidiabetes', 'tablet', 700, 2200, 120, 25),
('Amlodipine 5mg', 'Antihipertensi', 'tablet', 900, 3000, 90, 20),
('Ibuprofen 400mg', 'Analgesik', 'tablet', 550, 1800, 160, 35),
('Vitamin B Complex', 'Vitamin', 'tablet', 300, 1000, 250, 50),
('Dexamethasone 0.5mg', 'Kortikosteroid', 'tablet', 400, 1500, 100, 20),
('Antacida DOEN', 'Antasida', 'tablet', 350, 1200, 200, 40);

-- ICD-10 codes sample
INSERT INTO icd10_codes (kode, nama_penyakit) VALUES
('A09', 'Diare dan gastroenteritis'),
('B34.9', 'Infeksi virus, tidak spesifik'),
('E11', 'Diabetes mellitus tipe 2'),
('E78.0', 'Hiperkolesterolemia'),
('I10', 'Hipertensi esensial (primer)'),
('J00', 'Nasofaringitis akut (common cold)'),
('J02.9', 'Faringitis akut, tidak spesifik'),
('J06.9', 'Infeksi saluran pernapasan atas akut'),
('J18.9', 'Pneumonia, tidak spesifik'),
('J20.9', 'Bronkitis akut, tidak spesifik'),
('J30.1', 'Rinitis alergi (hay fever)'),
('K21.0', 'GERD - Gastroesophageal reflux disease'),
('K29.7', 'Gastritis, tidak spesifik'),
('K30', 'Dispepsia fungsional'),
('K59.0', 'Konstipasi'),
('L20.9', 'Dermatitis atopik, tidak spesifik'),
('L50.9', 'Urtikaria, tidak spesifik'),
('M54.5', 'Nyeri punggung bawah (low back pain)'),
('M79.3', 'Panniculitis, tidak spesifik'),
('N39.0', 'Infeksi saluran kemih'),
('R10.4', 'Nyeri perut, tidak spesifik'),
('R50.9', 'Demam, tidak spesifik'),
('R51', 'Sakit kepala'),
('T78.4', 'Alergi, tidak spesifik');

-- Pasien sample
INSERT INTO pasien (no_rekam_medis, nama_lengkap, tanggal_lahir, jenis_kelamin, alamat, no_telepon, golongan_darah) VALUES
('RM-20260601-0001', 'Ahmad Fauzi', '1985-03-15', 'L', 'Jl. Merdeka No. 10, Jakarta', '081234567890', 'A'),
('RM-20260601-0002', 'Siti Rahayu', '1990-07-22', 'P', 'Jl. Sudirman No. 25, Jakarta', '081234567891', 'B'),
('RM-20260601-0003', 'Budi Santoso', '1978-11-08', 'L', 'Jl. Gatot Subroto No. 5, Jakarta', '081234567892', 'O'),
('RM-20260602-0001', 'Dewi Lestari', '1995-01-30', 'P', 'Jl. Thamrin No. 15, Jakarta', '081234567893', 'AB'),
('RM-20260602-0002', 'Rizki Ramadhan', '2000-05-12', 'L', 'Jl. Kuningan No. 8, Jakarta', '081234567894', 'O');
