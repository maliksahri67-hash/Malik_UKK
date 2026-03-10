<?php
// includes/layout.php
// Usage: include at top of pages after setting $pageTitle, $activeNav
// Also needs $role = 'admin' | 'petugas' | 'anggota'

$role = $role ?? 'admin';
$baseRole = strtolower($role);
$userName = $_SESSION['nama'] ?? 'User';
$userLevel = $_SESSION['role'] ?? $role;

function navItem($href, $icon, $label, $activeNav, $current) {
    $active = ($activeNav === $current) ? ' active' : '';
    echo "<a href=\"{$href}\" class=\"nav-link{$active}\"><span class=\"nav-icon\">{$icon}</span>{$label}</a>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Perpustakaan') ?> — Perpus Digital</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<style>
/* Tambahan style untuk tombol home dengan panah < */
.sidebar-home {
  padding: 16px 20px 8px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 8px;
}
.sidebar-home a {
  color: rgba(255,255,255,0.8);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.2s;
}
.sidebar-home a:hover {
  color: white;
  transform: translateX(-3px); /* efek geser ke kiri saat hover */
}
.sidebar-home .home-icon {
  font-size: 18px;
  font-weight: 600;
  line-height: 1;
}
</style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <!-- TOMBOL KEMBALI KE BERANDA - POJOK KIRI ATAS SIDEBAR -->
    <div class="sidebar-home">
      <a href="<?= BASE_URL ?>/index.php">
        <span class="home-icon"><</span>
        <span>Kembali ke Beranda</span>
      </a>
    </div>
    
    <div class="sidebar-brand">
      <div class="brand-icon">📚</div>
      <h2>Perpus Digital_20</h2>
      <p>Sistem Manajemen Perpustakaan</p>
    </div>
    <div class="sidebar-user">
      <div class="user-avatar"><?= strtoupper(substr($userName,0,1)) ?></div>
      <div class="user-info">
        <div class="name"><?= htmlspecialchars($userName) ?></div>
        <div class="role"><?= htmlspecialchars($userLevel) ?></div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <?php if ($baseRole === 'admin' || $baseRole === 'petugas'): ?>
        <div class="nav-section-title">Menu Utama</div>
        <?php navItem(BASE_URL."/{$baseRole}/dashboard.php", '🏠', 'Dashboard', $activeNav, 'dashboard') ?>
        <?php if ($baseRole === 'admin'): ?>
          <?php navItem(BASE_URL."/{$baseRole}/pengguna.php", '👤', 'Pengguna', $activeNav, 'pengguna') ?>
        <?php endif ?>
        <?php navItem(BASE_URL."/{$baseRole}/anggota.php", '🎓', 'Anggota', $activeNav, 'anggota') ?>
        <div class="nav-section-title">Koleksi</div>
        <?php navItem(BASE_URL."/{$baseRole}/kategori.php", '🏷️', 'Kategori', $activeNav, 'kategori') ?>
        <?php navItem(BASE_URL."/{$baseRole}/buku.php", '📖', 'Buku', $activeNav, 'buku') ?>
        <div class="nav-section-title">Sirkulasi</div>
        <?php navItem(BASE_URL."/{$baseRole}/transaksi.php", '🔄', 'Transaksi', $activeNav, 'transaksi') ?>
        <?php navItem(BASE_URL."/{$baseRole}/denda.php", '💰', 'Denda', $activeNav, 'denda') ?>
        <?php navItem(BASE_URL."/{$baseRole}/laporan.php", '📊', 'Laporan', $activeNav, 'laporan') ?>
        <div class="nav-section-title">Akun</div>
        <?php navItem(BASE_URL."/{$baseRole}/profil.php", '⚙️', 'Profil Saya', $activeNav, 'profil') ?>
      <?php else: ?>
        <div class="nav-section-title">Menu</div>
        <?php navItem(BASE_URL."/anggota/dashboard.php", '🏠', 'Beranda', $activeNav, 'dashboard') ?>
        <?php navItem(BASE_URL."/anggota/katalog.php", '📚', 'Katalog Buku', $activeNav, 'katalog') ?>
        <?php navItem(BASE_URL."/anggota/pinjam.php", '📤', 'Pinjam Buku', $activeNav, 'pinjam') ?>
        <?php navItem(BASE_URL."/anggota/kembali.php", '📥', 'Kembalikan Buku', $activeNav, 'kembali') ?>
        <?php navItem(BASE_URL."/anggota/riwayat.php", '🕓', 'Riwayat', $activeNav, 'riwayat') ?>
        <?php navItem(BASE_URL."/anggota/ulasan.php", '💬', 'Ulasan', $activeNav, 'ulasan') ?>
        <div class="nav-section-title">Akun</div>
        <?php navItem(BASE_URL."/anggota/profil.php", '⚙️', 'Profil', $activeNav, 'profil') ?>
      <?php endif ?>
    </nav>
    <div class="sidebar-footer">
      <a href="<?= BASE_URL ?>/<?= $baseRole ?>/logout.php" class="nav-link">
        <span class="nav-icon">🚪</span>Logout
      </a>
    </div>
  </aside>

  <div class="main">
    <div class="topbar">
      <h1><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
      <div class="topbar-actions">
        <button onclick="toggleSidebar()" style="display:none;background:none;border:none;font-size:22px;cursor:pointer;" class="mobile-toggle">☰</button>
        <a href="<?= BASE_URL ?>/<?= $baseRole ?>/profil.php" class="btn btn-outline btn-sm">⚙️ Profil</a>
      </div>
    </div>
    <div class="content">