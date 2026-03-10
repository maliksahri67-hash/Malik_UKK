<?php
define('BASE_URL', '..');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
requireLoginMulti(['Admin','Petugas']);

$role = getRole();
$db = getDB();

// Stats
$stats = [];
$stats['buku'] = $db->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c'];
$stats['anggota'] = $db->query("SELECT COUNT(*) c FROM anggota")->fetch_assoc()['c'];
$stats['transaksi'] = $db->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman'")->fetch_assoc()['c'];
$stats['kembali'] = $db->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Pengembalian'")->fetch_assoc()['c'];
$stats['tersedia'] = $db->query("SELECT COUNT(*) c FROM buku WHERE status='tersedia'")->fetch_assoc()['c'];
$stats['denda'] = $db->query("SELECT COUNT(*) c FROM transaksi WHERE status_transaksi='Peminjaman' AND tgl_kembali < NOW()")->fetch_assoc()['c'];

// Recent transactions
$recent = $db->query("SELECT t.*, a.nama_anggota, b.judul_buku FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku ORDER BY t.id_transaksi DESC LIMIT 8");

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
$baseRole = strtolower($role);
include __DIR__ . '/../includes/layout.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon green">📚</div>
    <div class="stat-info">
      <div class="value"><?= $stats['buku'] ?></div>
      <div class="label">Total Buku</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon gold">✅</div>
    <div class="stat-info">
      <div class="value"><?= $stats['tersedia'] ?></div>
      <div class="label">Buku Tersedia</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">🎓</div>
    <div class="stat-info">
      <div class="value"><?= $stats['anggota'] ?></div>
      <div class="label">Total Anggota</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple">🔄</div>
    <div class="stat-info">
      <div class="value"><?= $stats['transaksi'] ?></div>
      <div class="label">Dipinjam</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">📥</div>
    <div class="stat-info">
      <div class="value"><?= $stats['kembali'] ?></div>
      <div class="label">Dikembalikan</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red">💰</div>
    <div class="stat-info">
      <div class="value"><?= $stats['denda'] ?></div>
      <div class="label">Terlambat</div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>📋 Transaksi Terbaru</h3>
    <a href="<?= BASE_URL ?>/<?= $baseRole ?>/transaksi.php" class="btn btn-outline btn-sm">Lihat Semua</a>
  </div>
  <div class="table-wrap">
    <table id="dataTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Anggota</th>
          <th>Buku</th>
          <th>Tgl Pinjam</th>
          <th>Tgl Kembali</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php 
            $nomor = 1;
            while ($r = $recent->fetch_assoc()): ?>
        <tr>
          <td><?= $nomor++; $nomor; ?></td>
          <td><?= htmlspecialchars($r['nama_anggota']) ?></td>
          <td><?= htmlspecialchars($r['judul_buku']) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($r['tgl_pinjam'])) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($r['tgl_kembali'])) ?></td>
          <td>
            <?php if ($r['status_transaksi'] === 'Peminjaman'): ?>
              <?php if (strtotime($r['tgl_kembali']) < time()): ?>
                <span class="badge badge-danger">Terlambat</span>
              <?php else: ?>
                <span class="badge badge-warning">Dipinjam</span>
              <?php endif ?>
            <?php else: ?>
              <span class="badge badge-success">Dikembalikan</span>
            <?php endif ?>
          </td>
        </tr>
        <?php endwhile ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/layout_footer.php' ?>
