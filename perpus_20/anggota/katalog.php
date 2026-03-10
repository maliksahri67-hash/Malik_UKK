<?php
define('BASE_URL', '..');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
requireLogin('Anggota');

$db = getDB();
$q   = trim($_GET['q'] ?? '');
$kat = (int)($_GET['kategori'] ?? 0);

$where = "WHERE 1=1";
if ($q)   $where .= " AND (b.judul_buku LIKE '%".addslashes($q)."%' OR b.pengarang LIKE '%".addslashes($q)."%')";
if ($kat) $where .= " AND b.id_kategori=$kat";

$buku          = $db->query("SELECT b.*, k.nama_kategori FROM buku b JOIN kategori k ON b.id_kategori=k.id_kategori $where ORDER BY b.judul_buku");
$kategori_list = $db->query("SELECT * FROM kategori ORDER BY nama_kategori");

$pageTitle = 'Katalog Buku';
$activeNav = 'katalog';
$role      = 'Anggota';
include __DIR__ . '/../includes/layout.php';
?>

<style>
/* ── Filter Bar ── */
.filter-bar {
  margin-bottom: 24px;
}
.filter-bar form {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
}

/* ── Grid ── */
.books-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(195px, 1fr));
  gap: 20px;
}

/* ── Card ── */
.book-card {
  background: #fff;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  display: flex;
  flex-direction: column;
  transition: transform .2s, box-shadow .2s;
}
.book-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 28px rgba(0,0,0,0.13);
}

/* ── Cover ── */
.book-cover {
  width: 100%;
  height: 320px;
  background: #eef0f4;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 56px;
  flex-shrink: 0;
}
.book-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform .35s ease;
}
.book-card:hover .book-cover img {
  transform: scale(1.04);
}

/* ── Info ── */
.book-info {
  padding: 12px 14px 14px;
  display: flex;
  flex-direction: column;
  flex: 1;
  gap: 3px;
}

.book-title {
  font-size: 14px;
  font-weight: 700;
  color: #1a2235;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.book-author {
  font-size: 12px;
  color: #5d76c7;
}

.book-kat {
  margin-top: 3px;
}

/* ── Bottom row: status + tombol pinjam ── */
.book-bottom {
  margin-top: auto;
  padding-top: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.book-status {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}
.dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}
.dot-ok   { background: #1a7a4a; }
.dot-no   { background: #d93025; }
.clr-ok   { color: #1a7a4a; }
.clr-no   { color: #d93025; }

.btn-pinjam-sm {
  padding: 6px 16px;
  background: #1a7a4a;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  white-space: nowrap;
  transition: background .18s;
  display: inline-block;
}
.btn-pinjam-sm:hover { background: #155f3a; }

.btn-na-sm {
  padding: 6px 12px;
  background: #f0f2f6;
  color: #9aa4b5;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: not-allowed;
  white-space: nowrap;
}

/* Detail button — full width, below */
.btn-detail-sm {
  display: block;
  width: 100%;
  text-align: center;
  padding: 7px 0;
  background: #f0f6f1;
  color: #3a4254;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  margin-top: 7px;
  transition: background .18s;
}
.btn-detail-sm:hover { background: #e3e8f0; }
</style>

<!-- Filter -->
<div class="filter-bar">
  <form method="GET">
    <div class="search-bar">
      <input type="text" name="q" placeholder="Cari judul / pengarang..."
        value="<?= htmlspecialchars($q) ?>" style="width:250px;">
    </div>
    <select name="kategori" class="form-control" style="width:160px;">
      <option value="">Semua Kategori</option>
      <?php while ($k = $kategori_list->fetch_assoc()): ?>
      <option value="<?= $k['id_kategori'] ?>" <?= $kat==$k['id_kategori']?'selected':'' ?>>
        <?= htmlspecialchars($k['nama_kategori']) ?>
      </option>
      <?php endwhile ?>
    </select>
    <button type="submit" class="btn btn-primary">Cari</button>
    <?php if ($q || $kat): ?>
      <a href="katalog.php" class="btn btn-outline">Reset</a>
    <?php endif ?>
  </form>
</div>

<?php if ($buku->num_rows === 0): ?>
<div class="card">
  <div class="card-body empty-state">
    <div class="icon">🔍</div>
    <h3>Buku tidak ditemukan</h3>
    <p>Coba kata kunci lain atau lihat semua buku.</p>
    <a href="katalog.php" class="btn btn-primary mt-2">Lihat Semua</a>
  </div>
</div>

<?php else: ?>
<div style="margin-bottom:16px;color:var(--text-muted,#6b7585);font-size:14px;">
  <?= $buku->num_rows ?> buku ditemukan
</div>

<div class="books-grid">
  <?php while ($b = $buku->fetch_assoc()):
    $gambar  = $b['gambar_buku'];
    $img_src = '';
    if ($gambar && is_string($gambar) && file_exists(__DIR__ . '/../' . $gambar)) {
      $img_src = BASE_URL . '/' . $gambar;
    }
    $tersedia = ($b['status'] === 'tersedia');
  ?>
  <div class="book-card">

    <!-- Cover -->
    <div class="book-cover">
      <?php if ($img_src): ?>
        <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($b['judul_buku']) ?>">
      <?php else: ?>
        📖
      <?php endif ?>
    </div>

    <!-- Info -->
    <div class="book-info">
      <div class="book-title"><?= htmlspecialchars($b['judul_buku']) ?></div>
      <div class="book-author"><?= htmlspecialchars($b['pengarang']) ?></div>
      <div class="book-kat">
        <span class="badge badge-gold" style="font-size:11px;"><?= htmlspecialchars($b['nama_kategori']) ?></span>
      </div>

      <!-- Status + Pinjam -->
      <div class="book-bottom">
        <div class="book-status">
          <span class="dot <?= $tersedia ? 'dot-ok' : 'dot-no' ?>"></span>
          <span class="<?= $tersedia ? 'clr-ok' : 'clr-no' ?>">
            <?= $tersedia ? 'Tersedia' : 'Dipinjam' ?>
          </span>
        </div>

        <?php if ($tersedia): ?>
          <a href="pinjam.php?id=<?= $b['id_buku'] ?>" class="btn-pinjam-sm">Pinjam</a>
        <?php else: ?>
          <button class="btn-na-sm" disabled>Dipinjam</button>
        <?php endif ?>
      </div>

      <!-- Detail -->
      <a href="detail.php?id=<?= $b['id_buku'] ?>" class="btn-detail-sm">🔍 Lihat Detail</a>
    </div>

  </div>
  <?php endwhile ?>
</div>
<?php endif ?>

<?php include __DIR__ . '/../includes/layout_footer.php' ?>