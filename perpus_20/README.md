# 📚 Perpus Digital — Sistem Manajemen Perpustakaan

## Fitur
- **Admin**: Dashboard, CRUD Pengguna, Anggota, Kategori, Buku, Transaksi, Denda, Laporan (CSV export)
- **Petugas**: Sama dengan Admin tanpa manajemen pengguna
- **Anggota**: Katalog buku, Peminjaman, Pengembalian, Riwayat, Ulasan, Profil

## Instalasi

### 1. Taruh di server (XAMPP/Laragon)
Salin folder `perpus_20/` ke `htdocs/` (XAMPP) atau `www/` (Laragon).

### 2. Import Database
**Cara A — via setup.php:**
Buka `http://localhost/perpus_20/setup.php` dan ikuti instruksi.

**Cara B — via phpMyAdmin:**
1. Buat database `perpus_20`
2. Import file `perpus_db.sql`

**Cara C — import database asli:**
Import file `perpus_20_baru.sql` yang sudah ada.

### 3. Konfigurasi
Edit `config/database.php` sesuai konfigurasi MySQL kamu:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'perpus_20');
```

## Akun Default (dari database asli)
| Role | Username/NIS | Password |
|------|-------------|----------|
| Admin | malik | malik123 |
| Admin | malik2 | (hashed) |
| Petugas | petugas | petugas123 |
| Anggota | NIS: 3333 | someone123 |

## Struktur Folder
```
perpus_20/
├── index.php          — Login & Registrasi
├── setup.php          — Installer
├── perpus_db.sql      — Schema + data awal
├── config/database.php
├── includes/          — Layout, session, shared CRUD
├── assets/css/ js/
├── admin/             — Panel Admin
├── petugas/           — Panel Petugas
└── anggota/           — Panel Anggota
```

## Denda
Rp 1.000/hari keterlambatan (dapat diubah di `includes/transaksi_crud.php` dan `includes/denda_view.php`)
