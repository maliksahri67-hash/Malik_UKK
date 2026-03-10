<?php
define('BASE_URL', '..');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
requireLogin('Anggota');

$db = getDB();
$error = $success = '';
$id_anggota = $_SESSION['user_id'];
$DENDA_PER_HARI = 1000;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_transaksi = (int)$_POST['id_transaksi'];
    $t = $db->query("SELECT * FROM transaksi WHERE id_transaksi=$id_transaksi AND id_anggota=$id_anggota AND status_transaksi='Peminjaman'")->fetch_assoc();
    if ($t) {
        $s = $db->prepare("UPDATE transaksi SET status_transaksi='Pengembalian' WHERE id_transaksi=?");
        $s->bind_param('i', $id_transaksi);
        if ($s->execute()) {
            $db->query("UPDATE buku SET status='tersedia' WHERE id_buku={$t['id_buku']}");
            $hari_telat = max(0, floor((time() - strtotime($t['tgl_kembali'])) / 86400));
            $denda = $hari_telat * $DENDA_PER_HARI;
            $success = 'Pengembalian berhasil!' . ($denda > 0 ? " Denda: Rp ".number_format($denda,0,',','.') : ' Tidak ada denda.');
        }
    } else {
        $error = 'Transaksi tidak ditemukan.';
    }
}

$aktif = $db->query("SELECT t.*, b.judul_buku, b.pengarang, DATEDIFF(t.tgl_kembali, NOW()) AS sisa, DATEDIFF(NOW(), t.tgl_kembali) AS telat FROM transaksi t JOIN buku b ON t.id_buku=b.id_buku WHERE t.id_anggota=$id_anggota AND t.status_transaksi='Peminjaman' ORDER BY t.tgl_kembali ASC");

$pageTitle = 'Kembalikan Buku';
$activeNav = 'kembali';
$role = 'Anggota';
include __DIR__ . '/../includes/layout.php';
?>

<?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif ?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif ?>

<?php if ($aktif->num_rows === 0): ?>
<div class="card">
  <div class="card-body empty-state">
    <div class="icon">🎉</div>
    <h3>Tidak ada buku yang dipinjam</h3>
    <p>Kamu tidak sedang meminjam buku apapun.</p>
    <a href="katalog.php" class="btn btn-primary mt-2">Lihat Katalog</a>
  </div>
</div>
<?php else: ?>
<div style="display:grid;gap:16px;">
  <?php while ($t = $aktif->fetch_assoc()):
    $hari_telat = max(0, (int)$t['telat']);
    $denda = $hari_telat * $DENDA_PER_HARI;
    $terlambat = $t['sisa'] < 0;
  ?>
  <div class="card" style="border-left:4px solid <?= $terlambat?'var(--danger)':'var(--success)' ?>;">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <div style="font-size:18px;font-weight:700;"><?= htmlspecialchars($t['judul_buku']) ?></div>
        <div style="color:var(--text-muted);font-size:14px;">oleh <?= htmlspecialchars($t['pengarang']) ?></div>
        <div class="mt-1" style="font-size:13px;">
          📅 Pinjam: <?= date('d/m/Y', strtotime($t['tgl_pinjam'])) ?> &nbsp;|&nbsp;
          📅 Kembali: <?= date('d/m/Y', strtotime($t['tgl_kembali'])) ?>
        </div>
        <?php if ($terlambat): ?>
        <div class="mt-1"><span class="badge badge-danger">⚠️ Terlambat <?= $hari_telat ?> hari — Denda Rp <?= number_format($denda,0,',','.') ?></span></div>
        <?php else: ?>
        <div class="mt-1"><span class="badge badge-success">✅ <?= abs((int)$t['sisa']) ?> hari tersisa</span></div>
        <?php endif ?>
      </div>
      <form method="POST" onsubmit="return confirm('Konfirmasi pengembalian buku ini?<?= $denda>0?' Denda: Rp '.number_format($denda,0,',','.').' akan dikenakan.':'' ?>')">
        <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
        <button type="submit" class="btn btn-success">📥 Kembalikan</button>
      </form>
    </div>
  </div>
  <?php endwhile ?>
</div>
<?php endif ?>

<?php include __DIR__ . '/../includes/layout_footer.php' ?>
