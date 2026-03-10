<?php
define('BASE_URL', '..');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
requireLogin('Anggota');

$db = getDB();
$id_anggota = $_SESSION['user_id'];
$DENDA_PER_HARI = 1000;

$riwayat = $db->query("SELECT t.*, b.judul_buku, b.pengarang, k.nama_kategori FROM transaksi t JOIN buku b ON t.id_buku=b.id_buku JOIN kategori k ON b.id_kategori=k.id_kategori WHERE t.id_anggota=$id_anggota ORDER BY t.id_transaksi DESC");

$pageTitle = 'Riwayat Peminjaman';
$activeNav = 'riwayat';
$role = 'Anggota';
include __DIR__ . '/../includes/layout.php';
?>

<div class="page-header">
  <h2>Riwayat Peminjaman</h2>
  <div class="search-bar"><input type="text" id="searchInput" placeholder="Cari buku..."></div>
</div>

<?php if ($riwayat->num_rows === 0): ?>
<div class="card">
  <div class="card-body empty-state">
    <div class="icon">📋</div>
    <h3>Belum ada riwayat</h3>
    <p>Kamu belum pernah meminjam buku.</p>
    <a href="katalog.php" class="btn btn-primary mt-2">Mulai Meminjam</a>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table id="dataTable">
      <thead>
        <tr><th>#</th><th>Judul Buku</th><th>Kategori</th><th>Tgl Pinjam</th><th>Tgl Kembali</th><th>Status</th><th>Denda</th></tr>
      </thead>
      <tbody>
        <?php while ($r = $riwayat->fetch_assoc()):
          $terlambat = $r['status_transaksi'] === 'Peminjaman' && strtotime($r['tgl_kembali']) < time();
          $hari_telat = $terlambat ? max(0, floor((time() - strtotime($r['tgl_kembali']))/86400)) : 0;
          $denda = $hari_telat * $DENDA_PER_HARI;
        ?>
        <tr>
          <td><?= $r['id_transaksi'] ?></td>
          <td>
            <strong><?= htmlspecialchars($r['judul_buku']) ?></strong><br>
            <span style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($r['pengarang']) ?></span>
          </td>
          <td><span class="badge badge-gold"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
          <td><?= date('d/m/Y', strtotime($r['tgl_pinjam'])) ?></td>
          <td><?= date('d/m/Y', strtotime($r['tgl_kembali'])) ?></td>
          <td>
            <?php if ($r['status_transaksi'] === 'Pengembalian'): ?>
              <span class="badge badge-success">✅ Dikembalikan</span>
            <?php elseif ($terlambat): ?>
              <span class="badge badge-danger">⚠️ Terlambat</span>
            <?php else: ?>
              <span class="badge badge-warning">📤 Dipinjam</span>
            <?php endif ?>
          </td>
          <td><?= $denda > 0 ? '<span class="badge badge-danger">Rp '.number_format($denda,0,',','.').'</span>' : '<span style="color:var(--text-muted)">-</span>' ?></td>
        </tr>
        <?php endwhile ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif ?>

<?php include __DIR__ . '/../includes/layout_footer.php' ?>
