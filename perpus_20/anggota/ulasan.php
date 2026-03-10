<?php
define('BASE_URL', '..');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
requireLogin('Anggota');

$db = getDB();
$error = $success = '';
$id_anggota = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'add') {
        $id_buku = (int)$_POST['id_buku'];
        $text = trim($_POST['text'] ?? '');
        if (!$text) { $error = 'Ulasan tidak boleh kosong.'; }
        else {
            // Check if already reviewed this book
            $chk = $db->query("SELECT id_ulasan FROM ulasan_buku WHERE id_anggota=$id_anggota AND id_buku=$id_buku")->fetch_assoc();
            if ($chk) { $error = 'Kamu sudah memberikan ulasan untuk buku ini.'; }
            else {
                $s = $db->prepare("INSERT INTO ulasan_buku (id_anggota,id_buku,text) VALUES (?,?,?)");
                $s->bind_param('iis', $id_anggota, $id_buku, $text);
                if ($s->execute()) $success = 'Ulasan berhasil ditambahkan.';
                else $error = 'Gagal menambahkan ulasan.';
            }
        }
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM ulasan_buku WHERE id_ulasan=$id AND id_anggota=$id_anggota");
        $success = 'Ulasan dihapus.';
    }
}

// Books the user has borrowed (eligible to review)
$pernah_pinjam = $db->query("SELECT DISTINCT b.id_buku, b.judul_buku FROM transaksi t JOIN buku b ON t.id_buku=b.id_buku WHERE t.id_anggota=$id_anggota ORDER BY b.judul_buku");

// All reviews for display
$ulasan = $db->query("SELECT u.*, b.judul_buku, a.nama_anggota FROM ulasan_buku u JOIN buku b ON u.id_buku=b.id_buku JOIN anggota a ON u.id_anggota=a.id_anggota ORDER BY u.id_ulasan DESC");

$pageTitle = 'Ulasan Buku';
$activeNav = 'ulasan';
$role = 'Anggota';
include __DIR__ . '/../includes/layout.php';
?>

<?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif ?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif ?>

<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:24px;align-items:start;">
  <!-- Form Ulasan -->
  <div class="card">
    <div class="card-header"><h3>✍️ Tulis Ulasan</h3></div>
    <div class="card-body">
      <?php if ($pernah_pinjam->num_rows === 0): ?>
      <div class="empty-state" style="padding:20px 0;">
        <p>Kamu belum pernah meminjam buku. Pinjam buku dulu untuk bisa memberi ulasan.</p>
        <a href="katalog.php" class="btn btn-primary mt-2 btn-sm">Lihat Katalog</a>
      </div>
      <?php else: ?>
      <form method="POST">
        <input type="hidden" name="act" value="add">
        <div class="form-group">
          <label>Pilih Buku</label>
          <select name="id_buku" class="form-control" required>
            <option value="">-- Pilih Buku --</option>
            <?php $pernah_pinjam->data_seek(0); while ($b = $pernah_pinjam->fetch_assoc()): ?>
            <option value="<?= $b['id_buku'] ?>"><?= htmlspecialchars($b['judul_buku']) ?></option>
            <?php endwhile ?>
          </select>
        </div>
        <div class="form-group">
          <label>Ulasan</label>
          <textarea name="text" class="form-control" rows="5" required placeholder="Bagikan pendapatmu tentang buku ini..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Kirim Ulasan</button>
      </form>
      <?php endif ?>
    </div>
  </div>

  <!-- Daftar Ulasan -->
  <div>
    <h2 class="mb-2" style="font-family:'Playfair Display',serif;font-size:18px;color:var(--primary);">💬 Semua Ulasan</h2>
    <?php if ($ulasan->num_rows === 0): ?>
    <div class="card"><div class="card-body empty-state"><p>Belum ada ulasan.</p></div></div>
    <?php else: ?>
    <div style="display:grid;gap:12px;">
      <?php while ($u = $ulasan->fetch_assoc()): ?>
      <div class="card" style="<?= $u['id_anggota']==$id_anggota?'border-left:3px solid var(--accent);':'' ?>">
        <div class="card-body">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
            <div>
              <div style="font-weight:700;font-size:14px;"><?= htmlspecialchars($u['judul_buku']) ?></div>
              <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">oleh <?= htmlspecialchars($u['nama_anggota']) ?> <?= $u['id_anggota']==$id_anggota?'<span class="badge badge-gold">Saya</span>':'' ?></div>
              <div style="font-size:14px;line-height:1.5;"><?= nl2br(htmlspecialchars($u['text'])) ?></div>
            </div>
            <?php if ($u['id_anggota'] == $id_anggota): ?>
            <form method="POST" onsubmit="return confirmDelete('Hapus ulasan ini?')">
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" value="<?= $u['id_ulasan'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
            </form>
            <?php endif ?>
          </div>
        </div>
      </div>
      <?php endwhile ?>
    </div>
    <?php endif ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/layout_footer.php' ?>
