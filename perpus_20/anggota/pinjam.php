<?php
define('BASE_URL', '..');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
requireLogin('Anggota');

$db = getDB();
$error = $success = '';
$id_anggota = $_SESSION['user_id'];

// Check existing active loans
$aktif = $db->query("SELECT COUNT(*) c FROM transaksi WHERE id_anggota=$id_anggota AND status_transaksi='Peminjaman'")->fetch_assoc()['c'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_buku = (int)$_POST['id_buku'];
    $durasi = max(1, min(30, (int)$_POST['durasi']));

    if ($aktif >= 3) {
        $error = 'Batas pinjam maksimal 3 buku sekaligus.';
    } else {
        $chk = $db->query("SELECT status FROM buku WHERE id_buku=$id_buku")->fetch_assoc();
        if (!$chk || $chk['status'] !== 'tersedia') {
            $error = 'Buku tidak tersedia saat ini.';
        } else {
            $tgl_pinjam = date('Y-m-d H:i:s');
            $tgl_kembali = date('Y-m-d H:i:s', strtotime("+{$durasi} days"));
            $s = $db->prepare("INSERT INTO transaksi (id_anggota,id_buku,tgl_pinjam,tgl_kembali,status_transaksi) VALUES (?,?,?,?,'Peminjaman')");
            $s->bind_param('iiss', $id_anggota, $id_buku, $tgl_pinjam, $tgl_kembali);
            if ($s->execute()) {
                $db->query("UPDATE buku SET status='tidak' WHERE id_buku=$id_buku");
                $success = 'Peminjaman berhasil! Batas kembali: ' . date('d/m/Y', strtotime($tgl_kembali));
                $aktif++;
            } else {
                $error = 'Gagal memproses peminjaman.';
            }
        }
    }
}

// Pre-select book from katalog
$preselect = (int)($_GET['id'] ?? 0);
$buku_tersedia = $db->query("SELECT b.*, k.nama_kategori FROM buku b JOIN kategori k ON b.id_kategori=k.id_kategori WHERE b.status='tersedia' ORDER BY b.judul_buku");

$pageTitle = 'Pinjam Buku';
$activeNav = 'pinjam';
$role = 'Anggota';
include __DIR__ . '/../includes/layout.php';
?>

<?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif ?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif ?>

<?php if ($aktif >= 3): ?>
<div class="alert alert-warning">⚠️ Kamu sudah meminjam 3 buku. Kembalikan salah satu dulu sebelum meminjam lagi.</div>
<?php else: ?>
<div class="card" style="max-width:560px;">
  <div class="card-header"><h3>📤 Form Peminjaman Buku</h3></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-group">
        <label>Pilih Buku</label>
        <select name="id_buku" class="form-control" required>
          <option value="">-- Pilih Buku yang Tersedia --</option>
          <?php $buku_tersedia->data_seek(0); while ($b = $buku_tersedia->fetch_assoc()): ?>
          <option value="<?= $b['id_buku'] ?>" <?= $preselect==$b['id_buku']?'selected':'' ?>>[<?= htmlspecialchars($b['nama_kategori']) ?>] <?= htmlspecialchars($b['judul_buku']) ?> — <?= htmlspecialchars($b['pengarang']) ?></option>
          <?php endwhile ?>
        </select>
      </div>
      <div class="form-group">
        <label>Durasi Pinjam (hari)</label>
        <input type="range" name="durasi" min="1" max="14" value="7" oninput="document.getElementById('durasi_val').textContent=this.value" style="width:100%;margin-bottom:4px;">
        <div style="text-align:center;font-size:20px;font-weight:700;color:var(--primary);"><span id="durasi_val">7</span> hari</div>
        <div style="text-align:center;font-size:13px;color:var(--text-muted);">Batas kembali: <strong id="tgl_kembali_preview"><?= date('d/m/Y', strtotime('+7 days')) ?></strong></div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">📤 Ajukan Peminjaman</button>
    </form>
  </div>
</div>
<?php endif ?>

<?php if ($buku_tersedia->num_rows === 0 && $aktif < 3): ?>
<div class="card mt-3">
  <div class="card-body empty-state">
    <div class="icon">📭</div>
    <h3>Tidak ada buku tersedia</h3>
    <p>Semua buku sedang dipinjam. Cek lagi nanti.</p>
  </div>
</div>
<?php endif ?>

<script>
const range = document.querySelector('input[name="durasi"]');
if (range) {
  range.addEventListener('input', function() {
    const days = parseInt(this.value);
    const d = new Date();
    d.setDate(d.getDate() + days);
    const tgl = d.getDate().toString().padStart(2,'0') + '/' + (d.getMonth()+1).toString().padStart(2,'0') + '/' + d.getFullYear();
    document.getElementById('tgl_kembali_preview').textContent = tgl;
  });
}
</script>

<?php include __DIR__ . '/../includes/layout_footer.php' ?>
