-- ==========================================================
-- DATABASE: `kost`
-- SISTEM INFORMASI MANAJEMEN KOST LENGKAP & TERINTEGRASI
-- Dibuat untuk: AppServ / XAMPP / phpMyAdmin / MySQL 5.7 - 8.x
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+07:00";

CREATE DATABASE IF NOT EXISTS `kost` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kost`;

-- --------------------------------------------------------
-- DROP SEMUA TABEL TERLEBIH DAHULU (BERSIH TOTAL 0 ERROR)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tbl_pengaduan_layanan`;
DROP TABLE IF EXISTS `tbl_tagihan_pembayaran`;
DROP TABLE IF EXISTS `tbl_booking`;
DROP TABLE IF EXISTS `tbl_foto_kamar`;
DROP TABLE IF EXISTS `tbl_kamar_fasilitas`;
DROP TABLE IF EXISTS `tbl_fasilitas`;
DROP TABLE IF EXISTS `tbl_kamar`;
DROP TABLE IF EXISTS `tbl_kategori_kamar`;
DROP TABLE IF EXISTS `tbl_password_resets`;
DROP TABLE IF EXISTS `tbl_users`;
DROP TABLE IF EXISTS `tbl_pengaturan`;

-- --------------------------------------------------------
-- 1. TABEL PENGATURAN & PROFIL KOST
-- --------------------------------------------------------
CREATE TABLE `tbl_pengaturan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kost` varchar(100) NOT NULL DEFAULT 'Griya Kost Harmoni',
  `slogan` varchar(255) DEFAULT 'Hunian Modern, Bersih, Nyaman, Aman, dan Strategis',
  `logo` varchar(255) DEFAULT 'logo.png',
  `banner` varchar(255) DEFAULT 'banner.jpg',
  `deskripsi` text DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT 'Jakarta Selatan',
  `no_telepon` varchar(20) DEFAULT '021-88997766',
  `no_wa` varchar(20) DEFAULT '081234567890',
  `email` varchar(100) DEFAULT 'kontak@griyakostharmoni.com',
  `nama_bank` varchar(50) DEFAULT 'Bank BCA',
  `no_rekening` varchar(50) DEFAULT '8830129841',
  `atas_nama` varchar(100) DEFAULT 'Griya Kost Harmoni',
  `qris_image` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tbl_pengaturan` (`id`, `nama_kost`, `slogan`, `logo`, `banner`, `deskripsi`, `alamat`, `kota`, `no_telepon`, `no_wa`, `email`, `nama_bank`, `no_rekening`, `atas_nama`, `qris_image`) VALUES
(1, 'Griya Kost Harmoni', 'Hunian Modern, Bersih, Nyaman, Aman, dan Strategis untuk Mahasiswa & Profesional', 'logo.png', 'banner.jpg', 'Griya Kost Harmoni menyediakan hunian sewa kamar kost berkualitas prima dengan fasilitas lengkap seperti AC sejuk hemat daya, WiFi Fiber Optic 100 Mbps, kamar mandi dalam dengan water heater, sistem keamanan CCTV 24 jam dengan access card, dapur bersama modern, area parkir luas, serta lingkungan yang asri, tenang, dan strategis dekat pusat kampus dan perkantoran.', 'Jl. Flamboyan No. 45, Kebayoran Baru', 'Jakarta Selatan', '021-88997766', '081234567890', 'kontak@griyakostharmoni.com', 'Bank BCA', '8830129841', 'Griya Kost Harmoni', 'qris.png');

-- --------------------------------------------------------
-- 2. TABEL PENGGUNA (USERS)
-- --------------------------------------------------------
CREATE TABLE `tbl_users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `no_hp` varchar(20) NOT NULL,
  `role` enum('admin','petugas','pengunjung') NOT NULL DEFAULT 'pengunjung',
  `foto` varchar(255) DEFAULT 'default_user.png',
  `alamat_asal` text DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT 'Mahasiswa / Karyawan',
  `no_ktp` varchar(30) DEFAULT NULL,
  `status_aktif` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tbl_users` (`id_user`, `username`, `password`, `nama_lengkap`, `email`, `no_hp`, `role`, `foto`, `alamat_asal`, `pekerjaan`, `no_ktp`, `status_aktif`) VALUES
(1, 'admin', MD5('admin123'), 'Administrator Utama', 'admin@griyakost.com', '08111222333', 'admin', 'default_user.png', 'Jakarta Selatan', 'Pengelola Kost Harmoni', '3171012345670001', 'aktif'),
(2, 'petugas', MD5('petugas123'), 'Budi Santoso, S.Kom', 'petugas@griyakost.com', '08222333444', 'petugas', 'default_user.png', 'Bandung', 'Staff Layanan & Operasional', '3271012345670002', 'aktif'),
(3, 'petugas2', MD5('petugas123'), 'Hendra Gunawan', 'hendra@griyakost.com', '08222333555', 'petugas', 'default_user.png', 'Bogor', 'Staff Maintenance & Teknisi', '3271012345670009', 'aktif'),
(4, 'petugas3', MD5('petugas123'), 'Siti Nurhalizah, S.E.', 'siti.admin@griyakost.com', '08222333666', 'petugas', 'default_user.png', 'Tangerang', 'Staff Administrasi & Keuangan', '3671012345670010', 'aktif'),
(5, 'user1', MD5('user123'), 'Rian Pratama', 'rian@gmail.com', '08333444555', 'pengunjung', 'default_user.png', 'Yogyakarta', 'Software Engineer di Startup Jakarta', '3471012345670003', 'aktif'),
(6, 'user2', MD5('user123'), 'dr. Maya Sartika', 'maya.sartika@gmail.com', '08555666777', 'pengunjung', 'default_user.png', 'Surabaya', 'Dokter Koas RSUP Fatmawati', '3571012345670004', 'aktif'),
(7, 'user3', MD5('user123'), 'Dimas Anggara, S.Kom', 'dimas.anggara@gmail.com', '08777888999', 'pengunjung', 'default_user.png', 'Semarang', 'Digital Marketing Specialist', '3371012345670005', 'aktif'),
(8, 'user4', MD5('user123'), 'Annisa Rahma, S.Pd', 'annisa.rahma@gmail.com', '08999000111', 'pengunjung', 'default_user.png', 'Malang', 'Mahasiswi Pascasarjana UI', '3571012345670006', 'aktif'),
(9, 'user5', MD5('user123'), 'Fajar Nugraha', 'fajar.nugraha@gmail.com', '081234567111', 'pengunjung', 'default_user.png', 'Solo', 'UI/UX Product Designer', '3371012345670007', 'aktif'),
(10, 'user6', MD5('user123'), 'Putri Maharani', 'putri.maharani@gmail.com', '081234567222', 'pengunjung', 'default_user.png', 'Denpasar', 'Senior Financial Analyst', '5171012345670008', 'aktif'),
(11, 'user7', MD5('user123'), 'Kevin Sanjaya Putra', 'kevin.sanjaya@gmail.com', '081298765432', 'pengunjung', 'default_user.png', 'Medan', 'Business Development Officer', '1271012345670011', 'aktif'),
(12, 'user8', MD5('user123'), 'Nadia Aurelia', 'nadia.aurelia@gmail.com', '081345678901', 'pengunjung', 'default_user.png', 'Bandung', 'Content Creator & Freelancer', '3273012345670012', 'aktif'),
(13, 'user9', MD5('user123'), 'Bagas Wicaksono', 'bagas.w@gmail.com', '081567890123', 'pengunjung', 'default_user.png', 'Cirebon', 'Mahasiswa Teknik Informatika', '3209012345670013', 'aktif'),
(14, 'user10', MD5('user123'), 'Tiara Andini Putri', 'tiara.andini@gmail.com', '081789012345', 'pengunjung', 'default_user.png', 'Palembang', 'Auditor Kantor Akuntan Publik', '1671012345670014', 'aktif');

-- --------------------------------------------------------
-- 2.1 TABEL RESET PASSWORD (LUPA PASSWORD)
-- --------------------------------------------------------
CREATE TABLE `tbl_password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(100) NOT NULL,
  `otp` varchar(10) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reset_token` (`token`),
  KEY `idx_reset_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. TABEL KATEGORI / TIPE KAMAR
-- --------------------------------------------------------
CREATE TABLE `tbl_kategori_kamar` (
  `id_kategori` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(50) NOT NULL,
  `jenis_penghuni` enum('Putra','Putri','Campur') NOT NULL DEFAULT 'Campur',
  `deskripsi` text DEFAULT NULL,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tbl_kategori_kamar` (`id_kategori`, `nama_kategori`, `jenis_penghuni`, `deskripsi`) VALUES
(1, 'Tipe Standar Hemat (Putra)', 'Putra', 'Kamar ekonomis nyaman khusus putra dengan sirkulasi udara baik, meja kerja, dan ventilasi alami yang segar.'),
(2, 'Tipe Standar Hemat (Putri)', 'Putri', 'Kamar ekonomis asri khusus putri dengan ventilasi jendela menghadap taman, lemari pakaian, dan meja belajar.'),
(3, 'Tipe Deluxe AC (Putra)', 'Putra', 'Kamar eksklusif putra dengan AC sejuk, kamar mandi dalam shower, meja belajar ergonomis, dan kasur springbed.'),
(4, 'Tipe Deluxe AC (Putri)', 'Putri', 'Kamar eksklusif ber-AC dan kamar mandi dalam water heater khusus putri yang aman, sejuk, tenang, dan higienis.'),
(5, 'Tipe VIP Eksekutif (Campur)', 'Campur', 'Kamar berukuran luas dengan perabotan mewah lengkap, Smart TV 32 Inch, Kulkas mini, Water Heater, dan sofa santai.'),
(6, 'Tipe Superior Balkon (Putri)', 'Putri', 'Kamar luas lantai 2 dengan balkon pemandangan asri, kamar mandi dalam air panas, dan pencahayaan alami maksimal.');

-- --------------------------------------------------------
-- 4. TABEL KAMAR KOST
-- --------------------------------------------------------
CREATE TABLE `tbl_kamar` (
  `id_kamar` int(11) NOT NULL AUTO_INCREMENT,
  `id_kategori` int(11) NOT NULL,
  `nomor_kamar` varchar(20) NOT NULL UNIQUE,
  `nama_kamar` varchar(100) NOT NULL,
  `lantai` int(11) NOT NULL DEFAULT 1,
  `ukuran` varchar(20) NOT NULL DEFAULT '3x4 meter',
  `harga_per_bulan` decimal(12,2) NOT NULL,
  `harga_per_tahun` decimal(12,2) DEFAULT NULL,
  `harga_per_hari` decimal(12,2) DEFAULT NULL,
  `status` enum('tersedia','terisi','renovasi') NOT NULL DEFAULT 'tersedia',
  `foto_utama` varchar(255) DEFAULT 'kamar_default.jpg',
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kamar`),
  KEY `fk_kamar_kategori` (`id_kategori`),
  CONSTRAINT `fk_kamar_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `tbl_kategori_kamar` (`id_kategori`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tbl_kamar` (`id_kamar`, `id_kategori`, `nomor_kamar`, `nama_kamar`, `lantai`, `ukuran`, `harga_per_bulan`, `harga_per_tahun`, `harga_per_hari`, `status`, `foto_utama`, `deskripsi`) VALUES
(1, 1, 'A-01', 'Kamar Standar Putra 01', 1, '3x3.5 m', 1200000.00, 13200000.00, 100000.00, 'terisi', 'kamar_1.jpg', 'Kamar nyaman lantai 1 dengan ranjang springbed single, lemari pakaian 2 pintu, meja belajar, dan ventilasi udara alami yang segar.'),
(2, 1, 'A-02', 'Kamar Standar Putra 02', 1, '3x3.5 m', 1200000.00, 13200000.00, 100000.00, 'tersedia', 'kamar_2.jpg', 'Kamar dekat dengan taman tengah, pencahayaan sangat baik, lingkungan tenang untuk belajar atau bekerja WFH.'),
(3, 1, 'A-03', 'Kamar Standar Putra 03', 1, '3x3.5 m', 1200000.00, 13200000.00, 100000.00, 'renovasi', 'kamar_8.jpg', 'Sedang dalam proses pengecatan ulang dinding dan peremajaan furnitur kamar.'),
(4, 2, 'A-04', 'Kamar Standar Putri 04', 1, '3x3.5 m', 1250000.00, 13800000.00, 100000.00, 'terisi', 'kamar_2.jpg', 'Kamar putri lantai 1 bernuansa hangat, dekat akses dapur bersama dan ruang cuci jemur.'),
(5, 3, 'B-01', 'Kamar Deluxe Putra 01', 1, '3.5x4 m', 1800000.00, 19800000.00, 150000.00, 'terisi', 'kamar_3.jpg', 'Kamar ber-AC sejuk khusus putra, dilengkapi kamar mandi dalam, meja kerja luas, dan koneksi WiFi cepat.'),
(6, 3, 'B-02', 'Kamar Deluxe Putra 02', 1, '3.5x4 m', 1800000.00, 19800000.00, 150000.00, 'tersedia', 'kamar_4.jpg', 'Kamar deluxe putra dengan kasur springbed ukuran 120x200, AC Daikin hemat daya, dan cermin dinding.'),
(7, 4, 'B-03', 'Kamar Deluxe Putri 01', 1, '3.5x4 m', 1850000.00, 20350000.00, 150000.00, 'terisi', 'kamar_3.jpg', 'Kamar eksklusif putri dilengkapi AC Daikin 0.5 PK, kamar mandi dalam dengan water heater, dan cermin full body.'),
(8, 4, 'B-04', 'Kamar Deluxe Putri 02', 1, '3.5x4 m', 1850000.00, 20350000.00, 150000.00, 'tersedia', 'kamar_4.jpg', 'Kamar sejuk dengan jendela menghadap timur, AC hemat daya, dan koneksi internet WiFi kecepatan tinggi 100 Mbps.'),
(9, 4, 'B-05', 'Kamar Deluxe Putri 03', 2, '3.5x4 m', 1850000.00, 20350000.00, 150000.00, 'tersedia', 'kamar_default.jpg', 'Kamar lantai 2 yang tenang dan aman, kamar mandi dalam shower hangat, AC dingin, dan lemari 3 pintu.'),
(10, 6, 'C-01', 'Kamar Superior Balkon Putri 01', 2, '3.5x4.5 m', 2100000.00, 23100000.00, 175000.00, 'terisi', 'kamar_7.jpg', 'Kamar lantai 2 dengan balkon pribadi pemandangan asri, AC dingin, kamar mandi dalam shower air panas, dan lemari pakaian geser.'),
(11, 6, 'C-02', 'Kamar Superior Balkon Putri 02', 2, '3.5x4.5 m', 2100000.00, 23100000.00, 175000.00, 'tersedia', 'kamar_7.jpg', 'Kamar superior balkon dengan pencahayaan maksimal, sirkulasi udara sejuk, dan meja kerja modern.'),
(12, 5, 'VIP-01', 'Kamar VIP Eksekutif Suite 01', 2, '4x5 m', 2500000.00, 27500000.00, 200000.00, 'terisi', 'kamar_5.jpg', 'Kamar paling luas dengan Smart TV 32 Inch, Kulkas mini pribadi, Water Heater, Kasur Queen Size, dan Meja Kerja Ergonomis.'),
(13, 5, 'VIP-02', 'Kamar VIP Eksekutif Suite 02', 2, '4x5 m', 2500000.00, 27500000.00, 200000.00, 'tersedia', 'kamar_6.jpg', 'Kamar sudut lantai 2 dengan pencahayaan panoramic, Smart TV, kulkas pribadi, ranjang empuk, dan sofa santai.'),
(14, 5, 'VIP-03', 'Kamar VIP Eksekutif Suite 03', 2, '4x5 m', 2500000.00, 27500000.00, 200000.00, 'terisi', 'kamar_5.jpg', 'Kamar VIP mewah dengan fasilitas komplit, WiFi berkecepatan tinggi, kulkas mini, dan kasur king coil.'),
(15, 5, 'VIP-04', 'Kamar VIP Eksekutif Suite 04', 3, '4.5x5.5 m', 2800000.00, 30800000.00, 225000.00, 'tersedia', 'kamar_6.jpg', 'Kamar Penthouse VIP Suite di lantai 3 dengan pemandangan kota, Smart TV 43 Inch, kulkas mini, dan balkon pribadi ekstra luas.'),
(16, 1, 'A-05', 'Kamar Standar Putra 05', 1, '3x3.5 m', 1200000.00, 13200000.00, 100000.00, 'tersedia', 'kamar_1.jpg', 'Kamar standar baru selesai renovasi, lantai granit bersih, kasur baru, dan lingkungan hening.');

-- --------------------------------------------------------
-- 5. TABEL MASTER FASILITAS
-- --------------------------------------------------------
CREATE TABLE `tbl_fasilitas` (
  `id_fasilitas` int(11) NOT NULL AUTO_INCREMENT,
  `nama_fasilitas` varchar(50) NOT NULL,
  `ikon` varchar(50) DEFAULT 'fa-check',
  `tipe` enum('kamar','bersama') NOT NULL DEFAULT 'kamar',
  `keterangan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_fasilitas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tbl_fasilitas` (`id_fasilitas`, `nama_fasilitas`, `ikon`, `tipe`, `keterangan`) VALUES
(1, 'Air Conditioner (AC)', 'fa-snowflake', 'kamar', 'AC hemat daya & sejuk di dalam kamar.'),
(2, 'Kamar Mandi Dalam', 'fa-bath', 'kamar', 'Kloset duduk, shower, wastafel & ventilasi bersih.'),
(3, 'WiFi Fiber 100 Mbps', 'fa-wifi', 'bersama', 'Akses internet stabil berkecepatan tinggi 24 jam nonstop.'),
(4, 'Kasur Spring Bed Premium', 'fa-bed', 'kamar', 'Kasur empuk kualitas hotel + sprei & bantal.'),
(5, 'Lemari & Meja Kerja Ergonomis', 'fa-chair', 'kamar', 'Lemari pakaian 2 pintu & meja kerja belajar.'),
(6, 'Water Heater (Air Panas)', 'fa-shower', 'kamar', 'Pemanas air mandi modern instan dan aman.'),
(7, 'Parkir Mobil & Motor Luas', 'fa-motorcycle', 'bersama', 'Area parkir beratap, aman, dan berpagar besi.'),
(8, 'CCTV & Keamanan 24 Jam', 'fa-shield-alt', 'bersama', 'Pengawasan kamera CCTV di seluruh lorong & gerbang masuk.'),
(9, 'Dapur Bersama & Gas Gratis', 'fa-utensils', 'bersama', 'Dapur lengkap dengan kompor gas, wastafel & dispenser air minum.'),
(10, 'Mesin Cuci Otomatis & Jemuran', 'fa-tshirt', 'bersama', 'Area jemur beratap luas dan mesin cuci otomatis gratis.'),
(11, 'Smart TV & Streaming', 'fa-tv', 'kamar', 'TV digital 32 Inch dengan Netflix & YouTube ready.'),
(12, 'Kulkas Mini Pribadi', 'fa-cube', 'kamar', 'Kulkas mini pendingin minuman & makanan di dalam kamar.'),
(13, 'Akses Kartu RFID / Smartlock', 'fa-key', 'bersama', 'Pintu gerbang & pintu utama dengan kartu akses digital.'),
(14, 'Air Minum Dispenser RO Gratis', 'fa-glass-water', 'bersama', 'Dispenser air panas, dingin, dan normal gratis 24 jam.'),
(15, 'Balkon Pribadi Santai', 'fa-sun', 'kamar', 'Balkon pribadi dengan kursi santai dan pemandangan asri.');

-- --------------------------------------------------------
-- 6. TABEL RELASI KAMAR & FASILITAS
-- --------------------------------------------------------
CREATE TABLE `tbl_kamar_fasilitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_kamar` int(11) NOT NULL,
  `id_fasilitas` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_kf_kamar` (`id_kamar`),
  KEY `fk_kf_fasilitas` (`id_fasilitas`),
  CONSTRAINT `fk_kf_kamar` FOREIGN KEY (`id_kamar`) REFERENCES `tbl_kamar` (`id_kamar`) ON DELETE CASCADE,
  CONSTRAINT `fk_kf_fasilitas` FOREIGN KEY (`id_fasilitas`) REFERENCES `tbl_fasilitas` (`id_fasilitas`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tbl_kamar_fasilitas` (`id_kamar`, `id_fasilitas`) VALUES
-- Kamar Standar (1, 2, 3, 4, 16)
(1, 3), (1, 4), (1, 5), (1, 7), (1, 8), (1, 9), (1, 10), (1, 13), (1, 14),
(2, 3), (2, 4), (2, 5), (2, 7), (2, 8), (2, 9), (2, 10), (2, 13), (2, 14),
(3, 3), (3, 4), (3, 5), (3, 7), (3, 8), (3, 9), (3, 10), (3, 13), (3, 14),
(4, 3), (4, 4), (4, 5), (4, 7), (4, 8), (4, 9), (4, 10), (4, 13), (4, 14),
(16, 3), (16, 4), (16, 5), (16, 7), (16, 8), (16, 9), (16, 10), (16, 13), (16, 14),
-- Kamar Deluxe (5, 6, 7, 8, 9)
(5, 1), (5, 2), (5, 3), (5, 4), (5, 5), (5, 6), (5, 7), (5, 8), (5, 9), (5, 10), (5, 13), (5, 14),
(6, 1), (6, 2), (6, 3), (6, 4), (6, 5), (6, 6), (6, 7), (6, 8), (6, 9), (6, 10), (6, 13), (6, 14),
(7, 1), (7, 2), (7, 3), (7, 4), (7, 5), (7, 6), (7, 7), (7, 8), (7, 9), (7, 10), (7, 13), (7, 14),
(8, 1), (8, 2), (8, 3), (8, 4), (8, 5), (8, 6), (8, 7), (8, 8), (8, 9), (8, 10), (8, 13), (8, 14),
(9, 1), (9, 2), (9, 3), (9, 4), (9, 5), (9, 6), (9, 7), (9, 8), (9, 9), (9, 10), (9, 13), (9, 14),
-- Kamar Superior Balkon (10, 11)
(10, 1), (10, 2), (10, 3), (10, 4), (10, 5), (10, 6), (10, 7), (10, 8), (10, 9), (10, 10), (10, 13), (10, 14), (10, 15),
(11, 1), (11, 2), (11, 3), (11, 4), (11, 5), (11, 6), (11, 7), (11, 8), (11, 9), (11, 10), (11, 13), (11, 14), (11, 15),
-- Kamar VIP Suite (12, 13, 14, 15)
(12, 1), (12, 2), (12, 3), (12, 4), (12, 5), (12, 6), (12, 7), (12, 8), (12, 9), (12, 10), (12, 11), (12, 12), (12, 13), (12, 14),
(13, 1), (13, 2), (13, 3), (13, 4), (13, 5), (13, 6), (13, 7), (13, 8), (13, 9), (13, 10), (13, 11), (13, 12), (13, 13), (13, 14),
(14, 1), (14, 2), (14, 3), (14, 4), (14, 5), (14, 6), (14, 7), (14, 8), (14, 9), (14, 10), (14, 11), (14, 12), (14, 13), (14, 14),
(15, 1), (15, 2), (15, 3), (15, 4), (15, 5), (15, 6), (15, 7), (15, 8), (15, 9), (15, 10), (15, 11), (15, 12), (15, 13), (15, 14), (15, 15);

-- --------------------------------------------------------
-- 7. TABEL FOTO / GALERI KAMAR
-- --------------------------------------------------------
CREATE TABLE `tbl_foto_kamar` (
  `id_foto` int(11) NOT NULL AUTO_INCREMENT,
  `id_kamar` int(11) NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `keterangan` varchar(100) DEFAULT 'Foto Kamar',
  PRIMARY KEY (`id_foto`),
  KEY `fk_foto_kamar` (`id_kamar`),
  CONSTRAINT `fk_foto_kamar` FOREIGN KEY (`id_kamar`) REFERENCES `tbl_kamar` (`id_kamar`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tbl_foto_kamar` (`id_kamar`, `nama_file`, `keterangan`) VALUES
(1, 'kamar_1_1.jpg', 'Tampilan Tempat Tidur Single Kamar A-01'),
(1, 'kamar_1_2.jpg', 'Meja Belajar & Lemari Pakaian'),
(2, 'kamar_2_1.jpg', 'Sudut Jendela & Meja Belajar A-02'),
(5, 'kamar_3_1.jpg', 'Interior Kamar Deluxe Putra B-01'),
(7, 'kamar_3_1.jpg', 'Interior Kamar Deluxe Putri B-03'),
(7, 'kamar_3_2.jpg', 'Kamar Mandi Dalam Shower Water Heater'),
(8, 'kamar_4_1.jpg', 'Interior Kamar Deluxe Putri B-04'),
(10, 'kamar_7_1.jpg', 'Balkon Pribadi Kamar Superior C-01'),
(12, 'kamar_5_1.jpg', 'Living Area & Bed Kamar VIP Suite 01'),
(12, 'kamar_5_2.jpg', 'Smart TV, Kulkas Mini & Meja Kerja'),
(13, 'kamar_6_1.jpg', 'View Jendela & Sofa Santai VIP Suite 02'),
(15, 'kamar_6_1.jpg', 'Balkon Ekstra Luas Penthouse VIP 04');

-- --------------------------------------------------------
-- 8. TABEL PEMESANAN / BOOKING KOST
-- --------------------------------------------------------
CREATE TABLE `tbl_booking` (
  `id_booking` int(11) NOT NULL AUTO_INCREMENT,
  `kode_booking` varchar(30) NOT NULL UNIQUE,
  `id_user` int(11) NOT NULL,
  `id_kamar` int(11) NOT NULL,
  `tgl_booking` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tgl_mulai_sewa` date NOT NULL,
  `durasi_bulan` int(11) NOT NULL DEFAULT 1,
  `tgl_selesai_sewa` date NOT NULL,
  `total_biaya` decimal(12,2) NOT NULL,
  `catatan` text DEFAULT NULL,
  `status_booking` enum('pending','disetujui','ditolak','aktif','selesai','dibatalkan') NOT NULL DEFAULT 'pending',
  `diverifikasi_oleh` int(11) DEFAULT NULL,
  `tgl_verifikasi` datetime DEFAULT NULL,
  PRIMARY KEY (`id_booking`),
  KEY `fk_booking_user` (`id_user`),
  KEY `fk_booking_kamar` (`id_kamar`),
  CONSTRAINT `fk_booking_user` FOREIGN KEY (`id_user`) REFERENCES `tbl_users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_kamar` FOREIGN KEY (`id_kamar`) REFERENCES `tbl_kamar` (`id_kamar`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tbl_booking` (`id_booking`, `kode_booking`, `id_user`, `id_kamar`, `tgl_mulai_sewa`, `durasi_bulan`, `tgl_selesai_sewa`, `total_biaya`, `catatan`, `status_booking`, `diverifikasi_oleh`, `tgl_verifikasi`) VALUES
(1, 'BK-20260801-001', 5, 1, '2026-08-01', 3, '2026-11-01', 3600000.00, 'Mohon disiapkan kunci cadangan dan info password WiFi.', 'aktif', 2, '2026-08-01 10:00:00'),
(2, 'BK-20260810-002', 6, 7, '2026-08-15', 6, '2027-02-15', 11100000.00, 'Penyewa dokter koas RS Fatmawati Jakarta.', 'aktif', 2, '2026-08-11 14:20:00'),
(3, 'BK-20260815-003', 7, 12, '2026-08-20', 12, '2027-08-20', 27500000.00, 'Sewa tahunan paket hemat VIP Suite 01.', 'aktif', 1, '2026-08-16 09:15:00'),
(4, 'BK-20260818-004', 9, 14, '2026-08-22', 6, '2027-02-22', 15000000.00, 'Sewa kamar VIP Suite 03 paket 6 bulan.', 'aktif', 1, '2026-08-22 11:00:00'),
(5, 'BK-20260820-005', 8, 10, '2026-09-01', 6, '2027-03-01', 12600000.00, 'Sewa kamar superior balkon lantai 2.', 'aktif', 2, '2026-08-21 16:30:00'),
(6, 'BK-20260822-006', 11, 5, '2026-08-25', 3, '2026-11-25', 5400000.00, 'Sewa kamar Deluxe Putra B-01.', 'aktif', 2, '2026-08-23 09:00:00'),
(7, 'BK-20260823-007', 12, 4, '2026-09-01', 1, '2026-10-01', 1250000.00, 'Sewa bulanan kamar Standar Putri A-04.', 'aktif', 3, '2026-08-23 15:45:00'),
(8, 'BK-20260824-008', 10, 8, '2026-09-01', 1, '2026-10-01', 1850000.00, 'Pemesanan baru menunggu konfirmasi verifikasi admin.', 'pending', NULL, NULL),
(9, 'BK-20260824-009', 13, 2, '2026-09-05', 3, '2026-12-05', 3600000.00, 'Pemesanan kamar Standar Putra A-02.', 'pending', NULL, NULL),
(10, 'BK-20260710-010', 14, 6, '2026-07-15', 1, '2026-08-15', 1800000.00, 'Masa sewa telah selesai pada 15 Agustus.', 'selesai', 1, '2026-07-11 10:00:00');

-- --------------------------------------------------------
-- 9. TABEL TAGIHAN & PEMBAYARAN
-- --------------------------------------------------------
CREATE TABLE `tbl_tagihan_pembayaran` (
  `id_tagihan` int(11) NOT NULL AUTO_INCREMENT,
  `kode_tagihan` varchar(30) NOT NULL UNIQUE,
  `id_booking` int(11) DEFAULT NULL,
  `id_user` int(11) NOT NULL,
  `id_kamar` int(11) NOT NULL,
  `judul_tagihan` varchar(150) NOT NULL,
  `jenis_biaya` enum('sewa_kost','listrik','kebersihan','denda','lainnya') NOT NULL DEFAULT 'sewa_kost',
  `periode_bulan` varchar(30) NOT NULL,
  `nominal` decimal(12,2) NOT NULL,
  `tgl_jatuh_tempo` date NOT NULL,
  `status_pembayaran` enum('belum_bayar','menunggu_verifikasi','lunas','ditolak') NOT NULL DEFAULT 'belum_bayar',
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `tgl_bayar` datetime DEFAULT NULL,
  `metode_pembayaran` varchar(50) DEFAULT 'Transfer Bank',
  `catatan_pembayar` text DEFAULT NULL,
  `catatan_petugas` text DEFAULT NULL,
  `diverifikasi_oleh` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tagihan`),
  KEY `fk_tagihan_user` (`id_user`),
  KEY `fk_tagihan_kamar` (`id_kamar`),
  KEY `fk_tagihan_booking` (`id_booking`),
  CONSTRAINT `fk_tagihan_user` FOREIGN KEY (`id_user`) REFERENCES `tbl_users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_tagihan_kamar` FOREIGN KEY (`id_kamar`) REFERENCES `tbl_kamar` (`id_kamar`) ON DELETE CASCADE,
  CONSTRAINT `fk_tagihan_booking` FOREIGN KEY (`id_booking`) REFERENCES `tbl_booking` (`id_booking`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tbl_tagihan_pembayaran` (`id_tagihan`, `kode_tagihan`, `id_booking`, `id_user`, `id_kamar`, `judul_tagihan`, `jenis_biaya`, `periode_bulan`, `nominal`, `tgl_jatuh_tempo`, `status_pembayaran`, `bukti_transfer`, `tgl_bayar`, `metode_pembayaran`, `catatan_pembayar`, `catatan_petugas`, `diverifikasi_oleh`) VALUES
(1, 'INV-20260801-001', 1, 5, 1, 'Pembayaran Sewa Kamar A-01 (3 Bulan)', 'sewa_kost', 'Agustus - Oktober 2026', 3600000.00, '2026-08-05', 'lunas', 'bukti_1.jpg', '2026-08-01 09:30:00', 'Transfer BCA', 'Sudah transfer full 3 bulan via m-BCA.', 'Pembayaran terverifikasi valid dan lunas.', 1),
(2, 'INV-20260810-002', 2, 6, 7, 'Pembayaran Sewa Kamar Deluxe Putri B-03 (6 Bulan)', 'sewa_kost', 'Agustus 2026 - Feb 2027', 11100000.00, '2026-08-15', 'lunas', 'bukti_2.jpg', '2026-08-11 11:15:00', 'Transfer BCA', 'Transfer sewa 6 bulan.', 'Bukti transfer valid dan dana masuk rekening.', 2),
(3, 'INV-20260815-003', 3, 7, 12, 'Pembayaran Sewa Tahunan VIP Suite 01', 'sewa_kost', 'Agustus 2026 - Agustus 2027', 27500000.00, '2026-08-20', 'lunas', 'bukti_3.jpg', '2026-08-16 08:45:00', 'Transfer BCA', 'Transfer 1 tahun paket hemat lunas.', 'Pembayaran paket tahunan valid dan kamar aktif.', 1),
(4, 'INV-20260818-004', 4, 9, 14, 'Pembayaran Sewa Kamar VIP Suite 03 (6 Bulan)', 'sewa_kost', 'Agustus 2026 - Feb 2027', 15000000.00, '2026-08-25', 'lunas', 'bukti_default.jpg', '2026-08-22 10:45:00', 'Transfer Mandiri', 'Lunas sewa 6 bulan.', 'Verifikasi berhasil.', 2),
(5, 'INV-20260820-005', 5, 8, 10, 'Pembayaran Sewa Superior Balkon C-01 (6 Bulan)', 'sewa_kost', 'September 2026 - Feb 2027', 12600000.00, '2026-08-25', 'lunas', 'bukti_default.jpg', '2026-08-21 15:20:00', 'Transfer BNI', 'Pembayaran via BNI Mobile Banking.', 'Verifikasi lunas.', 3),
(6, 'INV-20260822-006', 6, 11, 5, 'Pembayaran Sewa Deluxe Putra B-01 (3 Bulan)', 'sewa_kost', 'Agustus - November 2026', 5400000.00, '2026-08-26', 'lunas', 'bukti_default.jpg', '2026-08-23 08:30:00', 'Transfer BCA', 'Lunas 3 bulan.', 'Verifikasi valid.', 2),
(7, 'INV-20260823-007', 7, 12, 4, 'Pembayaran Sewa Standar Putri A-04', 'sewa_kost', 'September 2026', 1250000.00, '2026-08-28', 'lunas', 'bukti_default.jpg', '2026-08-23 14:10:00', 'QRIS BCA', 'Pembayaran via QRIS scan.', 'QRIS berhasil masuk.', 3),
(8, 'INV-20260824-008', NULL, 5, 1, 'Iuran Listrik & Pemakaian Alat Tambahan', 'listrik', 'Agustus 2026', 75000.00, '2026-08-30', 'belum_bayar', NULL, NULL, 'Transfer Bank', NULL, NULL, NULL),
(9, 'INV-20260824-009', NULL, 6, 7, 'Tagihan Listrik AC Bulan Agustus', 'listrik', 'Agustus 2026', 95000.00, '2026-08-30', 'belum_bayar', NULL, NULL, 'Transfer Bank', NULL, NULL, NULL),
(10, 'INV-20260824-010', 8, 10, 8, 'Pembayaran Sewa Awal Kamar Deluxe Putri B-04', 'sewa_kost', 'September 2026', 1850000.00, '2026-08-28', 'menunggu_verifikasi', 'bukti_4.jpg', '2026-08-24 07:10:00', 'Transfer BCA', 'Sudah transfer sewa awal bulan September.', NULL, NULL),
(11, 'INV-20260824-011', 9, 13, 2, 'Pembayaran Sewa Kamar Standar Putra A-02 (3 Bulan)', 'sewa_kost', 'September - November 2026', 3600000.00, '2026-08-30', 'belum_bayar', NULL, NULL, 'Transfer Bank', NULL, NULL, NULL),
(12, 'INV-20260824-012', NULL, 7, 12, 'Iuran Kebersihan & Pengelolaan Lingkungan', 'kebersihan', 'Agustus 2026', 50000.00, '2026-08-31', 'belum_bayar', NULL, NULL, 'Transfer Bank', NULL, NULL, NULL);

-- --------------------------------------------------------
-- 10. TABEL PENGADUAN & LAYANAN KAMAR KOST
-- --------------------------------------------------------
CREATE TABLE `tbl_pengaduan_layanan` (
  `id_pengaduan` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_kamar` int(11) NOT NULL,
  `judul_keluhan` varchar(150) NOT NULL,
  `deskripsi` text NOT NULL,
  `foto_bukti` varchar(255) DEFAULT NULL,
  `status` enum('menunggu','diproses','selesai','ditolak') NOT NULL DEFAULT 'menunggu',
  `respon_petugas` text DEFAULT NULL,
  `tgl_lapor` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tgl_selesai` datetime DEFAULT NULL,
  PRIMARY KEY (`id_pengaduan`),
  KEY `fk_pengaduan_user` (`id_user`),
  KEY `fk_pengaduan_kamar` (`id_kamar`),
  CONSTRAINT `fk_pengaduan_user` FOREIGN KEY (`id_user`) REFERENCES `tbl_users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_pengaduan_kamar` FOREIGN KEY (`id_kamar`) REFERENCES `tbl_kamar` (`id_kamar`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tbl_pengaduan_layanan` (`id_pengaduan`, `id_user`, `id_kamar`, `judul_keluhan`, `deskripsi`, `status`, `respon_petugas`, `tgl_lapor`, `tgl_selesai`) VALUES
(1, 5, 1, 'Lampu Kamar Mandi Redup & Berkedip', 'Lampu kamar mandi utama berkedip dan mulai redup, mohon diganti dengan bohlam LED baru.', 'selesai', 'Bohlam LED Phillips 9W sudah diganti baru oleh petugas teknisi Hendra pada 20 Agustus.', '2026-08-20 08:30:00', '2026-08-20 14:00:00'),
(2, 6, 7, 'Pembersihan Filter AC Berkala', 'Filter AC kamar B-03 mulai berdebu, mohon dibersihkan saat jadwal servis AC rutin.', 'diproses', 'Jadwal teknisi AC sudah dikonfirmasi untuk hari Selasa pk 10.00 WIB.', '2026-08-23 15:10:00', NULL),
(3, 7, 12, 'Pengecekan Tekanan Air Water Heater', 'Tekanan air hangat agak kecil saat pagi hari di kamar mandi dalam VIP Suite.', 'diproses', 'Pompa pendorong shower sedang dicek oleh teknisi.', '2026-08-24 07:45:00', NULL),
(4, 9, 14, 'Koneksi WiFi Lantai 2 Terkadang Lambat', 'Saat jam 8 malam koneksi WiFi sempat drop, mohon dicek access point lantai 2.', 'menunggu', NULL, '2026-08-24 08:15:00', NULL),
(5, 8, 10, 'Permintaan Duplikat Kunci Pintu Tambahan', 'Mohon dibuatkan 1 kunci cadangan untuk pintu kamar superior balkon.', 'selesai', 'Kunci cadangan telah diserahkan langsung kepada penghuni Annisa.', '2026-08-22 13:00:00', '2026-08-22 17:30:00'),
(6, 11, 5, 'Kran Wastafel Kamar Mandi Sedikit Menetes', 'Kran wastafel ada rembesan air kecil saat ditutup rapat.', 'menunggu', NULL, '2026-08-24 09:30:00', NULL);

-- ==========================================================
-- SELESAI IMPORT & AKTIFKAN KEMBALI FOREIGN KEY CHECKS
-- ==========================================================
COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
