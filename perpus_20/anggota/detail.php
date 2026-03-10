<?php
define('BASE_URL', '..');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
requireLogin('Anggota');

$db         = getDB();
$id         = (int)($_GET['id'] ?? 0);
$id_anggota = $_SESSION['user_id'];

if (!$id) { header("Location: katalog.php"); exit; }

$stmt = $db->prepare("SELECT b.*, k.nama_kategori FROM buku b JOIN kategori k ON b.id_kategori=k.id_kategori WHERE b.id_buku=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();
if (!$b) { header("Location: katalog.php"); exit; }

// Cek sedang meminjam (aktif)
$cek = $db->prepare("SELECT * FROM transaksi WHERE id_anggota=? AND id_buku=? AND status_transaksi='Peminjaman' LIMIT 1");
$cek->bind_param('ii', $id_anggota, $id);
$cek->execute();
$sudah_pinjam = $cek->get_result()->fetch_assoc();

// Cek pernah pinjam (riwayat — boleh beri ulasan)
$pernah = $db->prepare("SELECT * FROM transaksi WHERE id_anggota=? AND id_buku=? LIMIT 1");
$pernah->bind_param('ii', $id_anggota, $id);
$pernah->execute();
$pernah_pinjam = $pernah->get_result()->fetch_assoc();

// Cek sudah punya ulasan
$cek_ul = $db->prepare("SELECT * FROM ulasan_buku WHERE id_anggota=? AND id_buku=? LIMIT 1");
$cek_ul->bind_param('ii', $id_anggota, $id);
$cek_ul->execute();
$ulasan_saya = $cek_ul->get_result()->fetch_assoc();

// ── Handle POST ulasan ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act_ulasan'])) {
    $act    = $_POST['act_ulasan'];
    $teks   = trim($_POST['text'] ?? '');
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));

    // Pastikan kolom rating ada
    $col = $db->query("SHOW COLUMNS FROM ulasan_buku LIKE 'rating'");
    if ($col->num_rows === 0) {
        $db->query("ALTER TABLE ulasan_buku ADD COLUMN rating tinyint(1) NOT NULL DEFAULT 5 AFTER text");
    }

    if ($act === 'add' && $pernah_pinjam && !$ulasan_saya) {
        if ($teks) {
            $s = $db->prepare("INSERT INTO ulasan_buku (id_anggota, id_buku, text, rating) VALUES (?,?,?,?)");
            $s->bind_param('iisi', $id_anggota, $id, $teks, $rating);
            $s->execute();
        }
    } elseif ($act === 'edit' && $ulasan_saya) {
        if ($teks) {
            $s = $db->prepare("UPDATE ulasan_buku SET text=?, rating=? WHERE id_ulasan=?");
            $s->bind_param('sii', $teks, $rating, $ulasan_saya['id_ulasan']);
            $s->execute();
        }
    } elseif ($act === 'delete' && $ulasan_saya) {
        $s = $db->prepare("DELETE FROM ulasan_buku WHERE id_ulasan=?");
        $s->bind_param('i', $ulasan_saya['id_ulasan']);
        $s->execute();
    }

    header("Location: detail.php?id=$id");
    exit;
}

// Cek kolom rating ada atau tidak
$col_check  = $db->query("SHOW COLUMNS FROM ulasan_buku LIKE 'rating'");
$has_rating = $col_check->num_rows > 0;
$sel_rating = $has_rating ? "u.rating" : "5 as rating";

// Ambil semua ulasan
$ulasan_list = $db->query("
    SELECT u.id_ulasan, u.text, $sel_rating, u.id_anggota,
           a.nama_anggota, a.kelas
    FROM ulasan_buku u
    JOIN anggota a ON u.id_anggota = a.id_anggota
    WHERE u.id_buku = $id
    ORDER BY u.id_ulasan DESC
");

// Rata-rata rating
$avg_row = $has_rating
    ? $db->query("SELECT ROUND(AVG(rating),1) as avg_r, COUNT(*) as total FROM ulasan_buku WHERE id_buku=$id")->fetch_assoc()
    : ['avg_r' => 0, 'total' => 0];

$pageTitle = htmlspecialchars($b['judul_buku']);
$activeNav = 'katalog';
$role      = 'Anggota';
include __DIR__ . '/../includes/layout.php';
?>

<style>
.detail-wrap { max-width: 900px; margin: 0 auto; }

.back-link {
  display: inline-flex; align-items: center; gap: 6px;
  color: #6b7585; font-size: 13px; text-decoration: none;
  margin-bottom: 20px; transition: color .18s;
}
.back-link:hover { color: #0f1f3d; }

/* ── Detail Card ── */
.detail-card {
  background: #fff; border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.09);
  overflow: hidden; display: flex;
}
.detail-cover {
  flex: 0 0 380px; background: #f0f2f6;
  display: flex; align-items: center; justify-content: center;
  font-size: 72px; min-height: 380px; position: relative;
}
.detail-cover img { width:100%; height:100%; object-fit:cover; display:block; }
.status-badge {
  position:absolute; top:14px; left:14px;
  font-size:11px; font-weight:700; padding:4px 12px;
  border-radius:20px; color:white; letter-spacing:.04em;
}
.status-tersedia { background:#1a7a4a; }
.status-dipinjam { background:#d93025; }

.detail-info { flex:1; padding:32px 36px; display:flex; flex-direction:column; gap:10px; }
.detail-kategori {
  font-size:12px; font-weight:600; color:#b07c2e;
  background:#fdf3dc; border:1px solid #f0d9a0;
  padding:3px 10px; border-radius:20px;
  display:inline-block; width:fit-content;
}
.detail-judul { font-size:1.6rem; font-weight:700; color:#1a2235; line-height:1.3; margin:0; }
.detail-pengarang { font-size:14px; color:#6b7585; margin-top:-4px; }
.detail-divider { border:none; border-top:1px solid #e3e8f0; margin:4px 0; }
.detail-meta { display:grid; grid-template-columns:1fr 1fr; gap:10px 24px; }
.meta-item label { display:block; font-size:11px; font-weight:600; color:#9aa4b5; text-transform:uppercase; letter-spacing:.06em; margin-bottom:2px; }
.meta-item span { font-size:14px; color:#2d3748; font-weight:500; }
.detail-deskripsi-label { font-size:11px; font-weight:600; color:#9aa4b5; text-transform:uppercase; letter-spacing:.06em; }
.detail-deskripsi { font-size:13.5px; color:#4a5568; line-height:1.65; }
.detail-actions { margin-top:auto; padding-top:16px; display:flex; gap:10px; flex-wrap:wrap; }

.btn-pinjam {
  padding:10px 28px; background:#0f1f3d; color:#fff;
  border:none; border-radius:10px; font-size:14px; font-weight:600;
  cursor:pointer; text-decoration:none;
  display:inline-flex; align-items:center; gap:6px;
  transition:background .18s, transform .13s;
}
.btn-pinjam:hover { background:#162d52; transform:translateY(-1px); }
.btn-disabled-d {
  padding:10px 28px; background:#f0f2f6; color:#9aa4b5;
  border:none; border-radius:10px; font-size:14px; font-weight:600;
  cursor:not-allowed; display:inline-flex; align-items:center; gap:6px;
}
.btn-back {
  padding:10px 20px; background:#f0f2f6; color:#3a4254;
  border:none; border-radius:10px; font-size:14px; font-weight:600;
  cursor:pointer; text-decoration:none;
  display:inline-flex; align-items:center; gap:6px; transition:background .18s;
}
.btn-back:hover { background:#e3e8f0; }
.already-borrowed {
  background:#fff8e1; border:1px solid #ffe082; color:#8a6400;
  border-radius:10px; padding:10px 16px; font-size:13px; font-weight:500;
  display:inline-flex; align-items:center; gap:6px;
}

/* ── Ulasan ── */
.ulasan-section { margin-top: 32px; }
.ulasan-header {
  display: flex; align-items: center; gap: 12px;
  margin-bottom: 18px; flex-wrap: wrap;
}
.ulasan-header h3 { font-size:16px; font-weight:700; color:#1a2235; margin:0; }

.avg-rating {
  display:inline-flex; align-items:center; gap:5px;
  background:#fdf3dc; border:1px solid #f0d9a0;
  padding:4px 12px; border-radius:20px; font-size:13px;
}
.stars-avg { color:#f5a623; }
.avg-num { font-weight:700; color:#b07c2e; }
.avg-count { color:#9aa4b5; font-size:11px; }

/* Form */
.ulasan-form {
  background:#f8fafc; border:1px solid #e3e8f0;
  border-radius:14px; padding:20px 22px; margin-bottom:22px;
}
.ulasan-form h4 { font-size:14px; font-weight:700; color:#1a2235; margin:0 0 14px; }

/* Star picker — kanan ke kiri trick */
.star-picker { display:flex; flex-direction:row-reverse; justify-content:flex-end; gap:2px; margin-bottom:12px; }
.star-picker input[type=radio] { display:none; }
.star-picker label { font-size:30px; color:#ddd; cursor:pointer; transition:color .15s; line-height:1; }
.star-picker input:checked ~ label,
.star-picker label:hover,
.star-picker label:hover ~ label { color:#f5a623; }

.ulasan-form textarea {
  width:100%; padding:10px 12px;
  border:1.5px solid #e3e8f0; border-radius:10px;
  font-family:inherit; font-size:13px; color:#2d3748;
  resize:vertical; min-height:80px; outline:none;
  transition:border-color .18s; background:#fff;
}
.ulasan-form textarea:focus { border-color:#0f1f3d; }
.form-actions { display:flex; gap:8px; margin-top:10px; }
.btn-ul-submit {
  padding:8px 22px; background:#0f1f3d; color:#fff;
  border:none; border-radius:8px; font-size:13px; font-weight:600;
  cursor:pointer; transition:background .18s;
}
.btn-ul-submit:hover { background:#162d52; }
.btn-ul-delete {
  padding:8px 16px; background:#fff0f0; color:#d93025;
  border:1px solid #ffd4d0; border-radius:8px; font-size:13px; font-weight:600;
  cursor:pointer; transition:background .18s;
}
.btn-ul-delete:hover { background:#ffe0de; }

.locked-msg {
  background:#f8fafc; border:1px dashed #c8d4e8;
  border-radius:12px; padding:16px 20px;
  font-size:13px; color:#6b7585; margin-bottom:20px;
  display:flex; align-items:center; gap:8px;
}

/* List */
.ulasan-list { display:flex; flex-direction:column; gap:12px; }
.ulasan-item {
  background:#fff; border:1px solid #e3e8f0;
  border-radius:12px; padding:16px 18px;
}
.ulasan-item.mine { border-color:#c8d4e8; background:#f5f8ff; }
.ulasan-head { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:6px; gap:8px; }
.ulasan-nama { font-size:13px; font-weight:700; color:#1a2235; }
.ulasan-kelas { font-size:11px; color:#9aa4b5; }
.ulasan-me-tag { font-size:11px; color:#0f1f3d; font-weight:600; margin-left:4px; }
.ulasan-stars { color:#f5a623; font-size:14px; white-space:nowrap; }
.ulasan-text { font-size:13px; color:#4a5568; line-height:1.65; }

.empty-ul {
  text-align:center; padding:28px 20px; color:#9aa4b5; font-size:13px;
  background:#f8fafc; border-radius:12px; border:1px dashed #e3e8f0;
}

@media (max-width:640px) {
  .detail-card  { flex-direction:column; }
  .detail-cover { flex:0 0 220px; min-height:220px; }
  .detail-info  { padding:20px; }
  .detail-judul { font-size:1.25rem; }
  .detail-meta  { grid-template-columns:1fr; }
}
</style>

<div class="detail-wrap">

  <a href="katalog.php" class="back-link">← Kembali ke Katalog</a>

  <!-- ── Detail Buku ── -->
  <div class="detail-card">
    <div class="detail-cover">
      <?php
        $gambar  = $b['gambar_buku'];
        $img_src = '';
        if ($gambar && is_string($gambar) && file_exists(__DIR__ . '/../' . $gambar))
          $img_src = BASE_URL . '/' . $gambar;
      ?>
      <?php if ($img_src): ?>
        <img src="<?= htmlspecialchars($img_src) ?>" alt="cover">
      <?php else: ?> 📖 <?php endif ?>
      <span class="status-badge <?= $b['status']==='tersedia'?'status-tersedia':'status-dipinjam' ?>">
        <?= $b['status']==='tersedia' ? 'TERSEDIA' : 'DIPINJAM' ?>
      </span>
    </div>

    <div class="detail-info">
      <span class="detail-kategori"><?= htmlspecialchars($b['nama_kategori']) ?></span>
      <h1 class="detail-judul"><?= htmlspecialchars($b['judul_buku']) ?></h1>
      <p class="detail-pengarang">oleh <strong><?= htmlspecialchars($b['pengarang']) ?></strong></p>
      <hr class="detail-divider">
      <div class="detail-meta">
        <div class="meta-item"><label>Penerbit</label><span><?= htmlspecialchars($b['penerbit']?:'-') ?></span></div>
        <div class="meta-item"><label>Tahun Terbit</label><span><?= htmlspecialchars($b['tahun_terbit']?:'-') ?></span></div>
        <div class="meta-item"><label>Kategori</label><span><?= htmlspecialchars($b['nama_kategori']) ?></span></div>
        <div class="meta-item">
          <label>Status</label>
          <span style="color:<?= $b['status']==='tersedia'?'#1a7a4a':'#d93025' ?>;font-weight:700;">
            <?= $b['status']==='tersedia'?'Tersedia':'Tidak Tersedia' ?>
          </span>
        </div>
      </div>
      <?php if ($b['deskripsi_buku']): ?>
      <hr class="detail-divider">
      <div class="detail-deskripsi-label">Deskripsi</div>
      <div class="detail-deskripsi"><?= nl2br(htmlspecialchars($b['deskripsi_buku'])) ?></div>
      <?php endif ?>
      <div class="detail-actions">
        <?php if ($sudah_pinjam): ?>
          <span class="already-borrowed">⚠️ Kamu sudah meminjam buku ini</span>
        <?php elseif ($b['status']==='tersedia'): ?>
          <a href="pinjam.php?id=<?= $b['id_buku'] ?>" class="btn-pinjam">📤 Pinjam Buku</a>
        <?php else: ?>
          <button class="btn-disabled-d" disabled>Tidak Tersedia</button>
        <?php endif ?>
        <a href="katalog.php" class="btn-back">📚 Katalog</a>
      </div>
    </div>
  </div>

  <!-- ══ ULASAN ══ -->
  <div class="ulasan-section">
    <div class="ulasan-header">
      <h3>💬 Ulasan Pembaca</h3>
      <?php if ($avg_row['total'] > 0): ?>
      <span class="avg-rating">
        <span class="stars-avg">
          <?= str_repeat('★', (int)round($avg_row['avg_r'])) ?><?= str_repeat('☆', 5-(int)round($avg_row['avg_r'])) ?>
        </span>
        <span class="avg-num"><?= $avg_row['avg_r'] ?></span>
        <span class="avg-count">(<?= $avg_row['total'] ?> ulasan)</span>
      </span>
      <?php endif ?>
    </div>

    <?php if ($pernah_pinjam): ?>
    <!-- Form tulis / edit ulasan -->
    <div class="ulasan-form">
      <h4><?= $ulasan_saya ? '✏️ Ulasan Kamu' : '✍️ Tulis Ulasan' ?></h4>
      <form method="POST">
        <input type="hidden" name="act_ulasan" value="<?= $ulasan_saya ? 'edit' : 'add' ?>">

        <!-- Bintang -->
        <div class="star-picker">
          <?php
            $cur = (int)($ulasan_saya['rating'] ?? 5);
            for ($i = 5; $i >= 1; $i--):
          ?>
          <input type="radio" name="rating" id="s<?= $i ?>" value="<?= $i ?>" <?= $cur==$i?'checked':'' ?>>
          <label for="s<?= $i ?>">★</label>
          <?php endfor ?>
        </div>

        <textarea name="text" placeholder="Bagaimana pendapatmu tentang buku ini?" required><?= htmlspecialchars($ulasan_saya['text'] ?? '') ?></textarea>

        <div class="form-actions">
          <button type="submit" class="btn-ul-submit">
            <?= $ulasan_saya ? '💾 Perbarui Ulasan' : '📨 Kirim Ulasan' ?>
          </button>
          <?php if ($ulasan_saya): ?>
          <button type="submit" name="act_ulasan" value="delete" class="btn-ul-delete"
            onclick="return confirm('Hapus ulasan ini?')">🗑️ Hapus</button>
          <?php endif ?>
        </div>
      </form>
    </div>

    <?php else: ?>
    <div class="locked-msg">
      🔒 Kamu hanya bisa memberi ulasan setelah meminjam buku ini.
    </div>
    <?php endif ?>

    <!-- Daftar ulasan -->
    <div class="ulasan-list">
      <?php if ($ulasan_list->num_rows === 0): ?>
        <div class="empty-ul">Belum ada ulasan. Jadilah yang pertama memberikan ulasan! 🌟</div>
      <?php else: ?>
        <?php while ($u = $ulasan_list->fetch_assoc()): ?>
        <div class="ulasan-item <?= $u['id_anggota']==$id_anggota?'mine':'' ?>">
          <div class="ulasan-head">
            <div>
              <span class="ulasan-nama"><?= htmlspecialchars($u['nama_anggota']) ?></span>
              <span class="ulasan-kelas"> · <?= htmlspecialchars($u['kelas']) ?></span>
              <?php if ($u['id_anggota']==$id_anggota): ?>
                <span class="ulasan-me-tag">(Kamu)</span>
              <?php endif ?>
            </div>
            <div class="ulasan-stars">
              <?= str_repeat('★',(int)$u['rating']) ?><?= str_repeat('☆',5-(int)$u['rating']) ?>
            </div>
          </div>
          <div class="ulasan-text"><?= nl2br(htmlspecialchars($u['text'])) ?></div>
        </div>
        <?php endwhile ?>
      <?php endif ?>
    </div>

  </div><!-- /ulasan-section -->

</div><!-- /detail-wrap -->

<?php include __DIR__ . '/../includes/layout_footer.php' ?>