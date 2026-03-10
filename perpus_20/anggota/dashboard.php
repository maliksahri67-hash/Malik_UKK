<?php
define('BASE_URL', '..');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
requireLogin('Anggota');

$db = getDB();
$id = $_SESSION['user_id'];

$total_pinjam = $db->query("SELECT COUNT(*) c FROM transaksi WHERE id_anggota=$id")->fetch_assoc()['c'];
$sedang_pinjam = $db->query("SELECT COUNT(*) c FROM transaksi WHERE id_anggota=$id AND status_transaksi='Peminjaman'")->fetch_assoc()['c'];
$telat = $db->query("SELECT COUNT(*) c FROM transaksi WHERE id_anggota=$id AND status_transaksi='Peminjaman' AND tgl_kembali < NOW()")->fetch_assoc()['c'];

$aktif = $db->query("SELECT t.*, b.judul_buku, DATEDIFF(t.tgl_kembali, NOW()) AS sisa FROM transaksi t JOIN buku b ON t.id_buku=b.id_buku WHERE t.id_anggota=$id AND t.status_transaksi='Peminjaman' ORDER BY t.tgl_kembali ASC LIMIT 5");

$pageTitle = 'Beranda';
$activeNav = 'dashboard';
$role = 'Anggota';
include __DIR__ . '/../includes/layout.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue">📚</div>
    <div class="stat-info">
      <div class="value"><?= $total_pinjam ?></div>
      <div class="label">Total Pernah Pinjam</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon gold">📤</div>
    <div class="stat-info">
      <div class="value"><?= $sedang_pinjam ?></div>
      <div class="label">Sedang Dipinjam</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red">⚠️</div>
    <div class="stat-info">
      <div class="value"><?= $telat ?></div>
      <div class="label">Terlambat</div>
    </div>
  </div>
</div>

<?php if ($sedang_pinjam > 0): ?>
<div class="card mb-3">
  <div class="card-header">
    <h3>📤 Buku Yang Sedang Dipinjam</h3>
    <a href="<?= BASE_URL ?>/anggota/kembali.php" class="btn btn-success btn-sm">Kembalikan</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Judul Buku</th><th>Tgl Pinjam</th><th>Tgl Kembali</th><th>Sisa Hari</th></tr></thead>
      <tbody>
        <?php while ($r = $aktif->fetch_assoc()): ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['judul_buku']) ?></strong></td>
          <td><?= date('d/m/Y', strtotime($r['tgl_pinjam'])) ?></td>
          <td><?= date('d/m/Y', strtotime($r['tgl_kembali'])) ?></td>
          <td>
            <?php if ($r['sisa'] < 0): ?>
              <span class="badge badge-danger">Terlambat <?= abs($r['sisa']) ?> hari — Denda Rp <?= number_format(abs($r['sisa'])*1000,0,',','.') ?></span>
            <?php elseif ($r['sisa'] <= 2): ?>
              <span class="badge badge-warning">⚠️ <?= $r['sisa'] ?> hari lagi</span>
            <?php else: ?>
              <span class="badge badge-success"><?= $r['sisa'] ?> hari</span>
            <?php endif ?>
          </td>
        </tr>
        <?php endwhile ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif ?>

<div class="card">
  <div class="card-header">
    <h3>📚 Jelajahi Buku</h3>
    <a href="<?= BASE_URL ?>/anggota/katalog.php" class="btn btn-outline btn-sm">Lihat Katalog</a>
  </div>
  <div class="card-body">
    <p class="text-muted">Temukan buku favorit kamu di katalog perpustakaan. Tersedia berbagai kategori: novel, fiksi, bisnis, komik, dan lainnya.</p>
    <div class="mt-2">
      <a href="<?= BASE_URL ?>/anggota/katalog.php" class="btn btn-primary">🔍 Cari Buku</a>
      <a href="<?= BASE_URL ?>/anggota/pinjam.php" class="btn btn-accent" style="margin-left:8px;">📤 Ajukan Pinjam</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/layout_footer.php' ?>
