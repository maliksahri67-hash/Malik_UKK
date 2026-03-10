<?php
$db = getDB();
$DENDA_PER_HARI = 1000;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'kembalikan') {
    $id_transaksi = (int)$_POST['id_transaksi'];
    $t = $db->query("SELECT * FROM transaksi WHERE id_transaksi=$id_transaksi")->fetch_assoc();
    if ($t) {
        $db->query("UPDATE transaksi SET status_transaksi='Pengembalian' WHERE id_transaksi=$id_transaksi");
        $db->query("UPDATE buku SET status='tersedia' WHERE id_buku={$t['id_buku']}");
        $success = 'Pengembalian dicatat & denda diselesaikan.';
    }
}

$terlambat = $db->query("
    SELECT t.*, a.nama_anggota, a.kelas, b.judul_buku,
           DATEDIFF(NOW(), t.tgl_kembali) AS hari_telat
    FROM transaksi t
    JOIN anggota a ON t.id_anggota = a.id_anggota
    JOIN buku b ON t.id_buku = b.id_buku
    WHERE t.status_transaksi = 'Peminjaman' AND t.tgl_kembali < NOW()
    ORDER BY hari_telat DESC
");
?>

<?php if (!empty($success)): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif ?>

<div class="page-header">
  <h2>Monitoring Denda</h2>
  <div class="search-bar"><input type="text" id="searchInput" placeholder="Cari anggota..."></div>
</div>

<?php $rows = $terlambat->fetch_all(MYSQLI_ASSOC); ?>

<?php if (empty($rows)): ?>
<div class="card">
  <div class="card-body empty-state">
    <div class="icon">🎉</div>
    <h3>Tidak ada keterlambatan</h3>
    <p>Semua peminjaman masih dalam batas waktu.</p>
  </div>
</div>
<?php else: ?>
<div class="card mb-3" style="padding:20px 24px;background:var(--danger-light);border-color:var(--danger);">
  <strong style="color:var(--danger);">⚠️ <?= count($rows) ?> peminjaman terlambat — Total denda:
  Rp <?= number_format(array_sum(array_map(fn($r) => max(0,$r['hari_telat']) * $DENDA_PER_HARI, $rows)),0,',','.') ?></strong>
</div>

<div class="card">
  <div class="table-wrap">
    <table id="dataTable">
      <thead><tr><th>#</th><th>Anggota</th><th>Kelas</th><th>Buku</th><th>Tgl Kembali</th><th>Hari Telat</th><th>Denda</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $t): $denda = max(0,$t['hari_telat']) * $DENDA_PER_HARI; ?>
        <tr>
          <td><?= $t['id_transaksi'] ?></td>
          <td><strong><?= htmlspecialchars($t['nama_anggota']) ?></strong></td>
          <td><?= htmlspecialchars($t['kelas']) ?></td>
          <td><?= htmlspecialchars($t['judul_buku']) ?></td>
          <td><?= date('d/m/Y', strtotime($t['tgl_kembali'])) ?></td>
          <td><span class="badge badge-danger"><?= $t['hari_telat'] ?> hari</span></td>
          <td><strong class="denda-amount">Rp <?= number_format($denda,0,',','.') ?></strong></td>
          <td>
            <form method="POST" onsubmit="return confirm('Tandai sebagai dikembalikan & denda lunas?')">
              <input type="hidden" name="act" value="kembalikan">
              <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
              <button type="submit" class="btn btn-success btn-sm">✅ Lunas & Kembali</button>
            </form>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif ?>
