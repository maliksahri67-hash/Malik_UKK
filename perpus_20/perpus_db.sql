-- Perpus Digital - Database Schema
-- Import ini ke phpMyAdmin atau gunakan setup.php

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `anggota` (
  `id_anggota` int(11) NOT NULL AUTO_INCREMENT,
  `nis` varchar(20) NOT NULL,
  `nama_anggota` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `kelas` varchar(10) NOT NULL,
  `foto` longblob NOT NULL,
  PRIMARY KEY (`id_anggota`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kategori` (
  `id_kategori` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `buku` (
  `id_buku` int(11) NOT NULL AUTO_INCREMENT,
  `judul_buku` varchar(100) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `pengarang` varchar(100) NOT NULL,
  `penerbit` varchar(100) NOT NULL,
  `tahun_terbit` year(4) NOT NULL,
  `gambar_buku` longblob NOT NULL,
  `deskripsi_buku` text NOT NULL,
  `status` enum('tersedia','tidak') NOT NULL DEFAULT 'tersedia',
  PRIMARY KEY (`id_buku`),
  FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pengguna` (
  `id_pengguna` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_pengguna` varchar(50) NOT NULL,
  `level` enum('Admin','Petugas') NOT NULL,
  PRIMARY KEY (`id_pengguna`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `transaksi` (
  `id_transaksi` int(11) NOT NULL AUTO_INCREMENT,
  `id_anggota` int(11) NOT NULL,
  `id_buku` int(11) NOT NULL,
  `tgl_pinjam` datetime NOT NULL,
  `tgl_kembali` datetime NOT NULL,
  `status_transaksi` enum('Peminjaman','Pengembalian') NOT NULL,
  PRIMARY KEY (`id_transaksi`),
  FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON UPDATE CASCADE,
  FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ulasan_buku` (
  `id_ulasan` int(11) NOT NULL AUTO_INCREMENT,
  `id_anggota` int(11) NOT NULL,
  `id_buku` int(11) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id_ulasan`),
  FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data awal
INSERT IGNORE INTO `kategori` (`id_kategori`, `nama_kategori`) VALUES
(1,'novel'),(2,'fiksi'),(3,'bisnis'),(5,'komik');

INSERT IGNORE INTO `pengguna` (`id_pengguna`,`username`,`password`,`nama_pengguna`,`level`) VALUES
(1,'malik','$2y$10$YKsWpMOy5TBqKIwjkS0IruTAFbdyGvBnA3uyv6EMdJPXbO0MxVALi','malik','Admin'),
(2,'petugas','$2y$10$YKsWpMOy5TBqKIwjkS0IruTAFbdyGvBnA3uyv6EMdJPXbO0MxVALi','petugas','Petugas');

-- Password untuk semua akun default: admin123
-- Admin: malik / admin123
-- Petugas: petugas / admin123

INSERT IGNORE INTO `anggota` (`id_anggota`,`nis`,`nama_anggota`,`email`,`password`,`kelas`,`foto`) VALUES
(1,'3333','Demo Anggota','demo@email.com','$2y$10$YKsWpMOy5TBqKIwjkS0IruTAFbdyGvBnA3uyv6EMdJPXbO0MxVALi','XI PPLG','');
-- Password anggota: admin123

INSERT IGNORE INTO `buku` (`id_buku`,`judul_buku`,`id_kategori`,`pengarang`,`penerbit`,`tahun_terbit`,`gambar_buku`,`deskripsi_buku`,`status`) VALUES
(1,'Investasi Saham untuk Pemula',3,'Robert Kiyosaki','Gramedia',2020,'','Panduan lengkap berinvestasi saham untuk pemula.','tersedia'),
(2,'Malin Kundang',1,'Anonim','Balai Pustaka',2015,'','Kisah seorang anak durhaka yang dikutuk menjadi batu.','tersedia'),
(3,'Doraemon Vol. 1',5,'Fujiko F. Fujio','Elex Media',2010,'','Petualangan robot kucing dari masa depan.','tersedia');

COMMIT;
