<?php
$db = getDB();
$error = $success = '';
$DENDA_PER_HARI = 1000;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'pinjam') {
        $id_anggota = (int)$_POST['id_anggota'];
        $id_buku = (int)$_POST['id_buku'];
        $durasi = max(1, (int)($_POST['durasi'] ?? 7));
        $tgl_pinjam = date('Y-m-d H:i:s');
        $tgl_kembali = date('Y-m-d H:i:s', strtotime("+{$durasi} days"));

        // Check buku tersedia
        $chk = $db->query("SELECT status FROM buku WHERE id_buku=$id_buku")->fetch_assoc();
        if (!$chk || $chk['status'] !== 'tersedia') {
            $error = 'Buku tidak tersedia untuk dipinjam.';
        } else {
            $s = $db->prepare("INSERT INTO transaksi (id_anggota,id_buku,tgl_pinjam,tgl_kembali,status_transaksi) VALUES (?,?,?,?,'Peminjaman')");
            $s->bind_param('iiss', $id_anggota, $id_buku, $tgl_pinjam, $tgl_kembali);
            if ($s->execute()) {
                $db->query("UPDATE buku SET status='tidak' WHERE id_buku=$id_buku");
                $success = 'Peminjaman berhasil dicatat.';
            } else {
                $error = 'Gagal: ' . $db->error;
            }
        }
    } elseif ($act === 'kembalikan') {
        $id_transaksi = (int)$_POST['id_transaksi'];
        $t = $db->query("SELECT * FROM transaksi WHERE id_transaksi=$id_transaksi")->fetch_assoc();
        if ($t) {
            $s = $db->prepare("UPDATE transaksi SET status_transaksi='Pengembalian' WHERE id_transaksi=?");
            $s->bind_param('i', $id_transaksi);
            if ($s->execute()) {
                $db->query("UPDATE buku SET status='tersedia' WHERE id_buku={$t['id_buku']}");
                $success = 'Pengembalian berhasil dicatat.';
            } else {
                $error = 'Gagal: ' . $db->error;
            }
        }
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        $t = $db->query("SELECT * FROM transaksi WHERE id_transaksi=$id")->fetch_assoc();
        if ($t && $t['status_transaksi'] === 'Peminjaman') {
            $db->query("UPDATE buku SET status='tersedia' WHERE id_buku={$t['id_buku']}");
        }
        $db->query("DELETE FROM transaksi WHERE id_transaksi=$id");
        $success = 'Transaksi dihapus.';
    }
}

$transaksi = $db->query("SELECT t.*, a.nama_anggota, a.kelas, b.judul_buku FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku ORDER BY t.id_transaksi DESC");
$anggota_list = $db->query("SELECT id_anggota, CONCAT(nis,' - ',nama_anggota) as label FROM anggota ORDER BY nama_anggota");
$buku_tersedia = $db->query("SELECT id_buku, judul_buku FROM buku WHERE status='tersedia' ORDER BY judul_buku");
?>

<?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif ?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif ?>

<div class="page-header">
  <h2>Manajemen Transaksi</h2>
  <div class="flex gap-2 items-center">
    <div class="search-bar"><input type="text" id="searchInput" placeholder="Cari transaksi..."></div>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Catat Peminjaman</button>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table id="dataTable">
      <thead><tr><th>#</th><th>Anggota</th><th>Kelas</th><th>Buku</th><th>Tgl Pinjam</th><th>Tgl Kembali</th><th>Status</th><th>Denda</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php 
        $nomor = 1; 
        while ($t = $transaksi->fetch_assoc()):
          $telat = strtotime($t['tgl_kembali']) < time() && $t['status_transaksi'] === 'Peminjaman';
          $hari_telat = $telat ? max(0, floor((time() - strtotime($t['tgl_kembali'])) / 86400)) : 0;
          $denda = $hari_telat * $DENDA_PER_HARI;
        ?>
        <tr>
          <td><?= $nomor++; $nomor; ?></td>
          <td><strong><?= htmlspecialchars($t['nama_anggota']) ?></strong></td>
          <td><?= htmlspecialchars($t['kelas']) ?></td>
          <td><?= htmlspecialchars($t['judul_buku']) ?></td>
          <td><?= date('d/m/Y', strtotime($t['tgl_pinjam'])) ?></td>
          <td><?= date('d/m/Y', strtotime($t['tgl_kembali'])) ?></td>
          <td>
            <?php if ($t['status_transaksi'] === 'Pengembalian'): ?>
              <span class="badge badge-success">✅ Kembali</span>
            <?php elseif ($telat): ?>
              <span class="badge badge-danger">⚠️ Terlambat</span>
            <?php else: ?>
              <span class="badge badge-warning">📤 Dipinjam</span>
            <?php endif ?>
          </td>
          <td><?= $denda > 0 ? '<span class="badge badge-danger">Rp '.number_format($denda,0,',','.').'</span>' : '-' ?></td>
          <td class="actions-cell">
            <?php if ($t['status_transaksi'] === 'Peminjaman'): ?>
            <form method="POST" onsubmit="return confirm('Konfirmasi pengembalian buku ini?')">
              <input type="hidden" name="act" value="kembalikan">
              <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
              <button type="submit" class="btn btn-success btn-sm">📥 Kembalikan</button>
            </form>
            <?php endif ?>
            <form method="POST" onsubmit="return confirmDelete()">
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" value="<?= $t['id_transaksi'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
            </form>
          </td>
        </tr>
        <?php endwhile ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header"><h3>Catat Peminjaman Buku</h3><button class="modal-close" onclick="closeModal('addModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="act" value="pinjam">
        <div class="form-group"><label>Anggota</label>
          <select name="id_anggota" class="form-control" required>
            <option value="">-- Pilih Anggota --</option>
            <?php $anggota_list->data_seek(0); while ($a = $anggota_list->fetch_assoc()): ?>
            <option value="<?= $a['id_anggota'] ?>"><?= htmlspecialchars($a['label']) ?></option>
            <?php endwhile ?>
          </select>
        </div>
        <div class="form-group"><label>Buku (Tersedia)</label>
          <select name="id_buku" class="form-control" required>
            <option value="">-- Pilih Buku --</option>
            <?php $buku_tersedia->data_seek(0); while ($b = $buku_tersedia->fetch_assoc()): ?>
            <option value="<?= $b['id_buku'] ?>"><?= htmlspecialchars($b['judul_buku']) ?></option>
            <?php endwhile ?>
          </select>
        </div>
        <div class="form-group"><label>Durasi Pinjam (hari)</label><input type="number" name="durasi" class="form-control" value="7" min="1" max="30"></div>
        <button type="submit" class="btn btn-primary btn-block">Catat Peminjaman</button>
      </form>
    </div>
  </div>
</div>
