<?php
define('BASE_URL', '..');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
requireLogin('Anggota');

$db         = getDB();
$id_anggota = $_SESSION['user_id'];
$nama       = $_SESSION['nama'];

$total_pinjam = $db->query("SELECT COUNT(*) as c FROM transaksi WHERE id_anggota=$id_anggota")->fetch_assoc()['c'];
$aktif_pinjam = $db->query("SELECT COUNT(*) as c FROM transaksi WHERE id_anggota=$id_anggota AND status_transaksi='Peminjaman'")->fetch_assoc()['c'];
$total_ulasan = $db->query("SELECT COUNT(*) as c FROM ulasan_buku WHERE id_anggota=$id_anggota")->fetch_assoc()['c'];
$total_buku   = $db->query("SELECT COUNT(*) as c FROM buku WHERE status='tersedia'")->fetch_assoc()['c'];

$buku_terbaru = $db->query("SELECT b.*, k.nama_kategori FROM buku b JOIN kategori k ON b.id_kategori=k.id_kategori ORDER BY b.id_buku DESC LIMIT 6");
$pinjaman_aktif = $db->query("SELECT t.*, b.judul_buku, b.pengarang, b.gambar_buku FROM transaksi t JOIN buku b ON t.id_buku=b.id_buku WHERE t.id_anggota=$id_anggota AND t.status_transaksi='Peminjaman' ORDER BY t.tgl_kembali ASC LIMIT 3");

$anggota = $db->query("SELECT foto, kelas FROM anggota WHERE id_anggota=$id_anggota")->fetch_assoc();
$foto_src = '';
if (!empty($anggota['foto']) && file_exists(__DIR__ . '/../' . $anggota['foto']))
    $foto_src = BASE_URL . '/' . $anggota['foto'];

$pageTitle = 'Beranda';
$activeNav = 'beranda';
$role      = 'Anggota';
include __DIR__ . '/../includes/layout.php';
?>

<style>
/* Hero */
.hero-dash {
  background: linear-gradient(135deg, #0f1f3d 0%, #1a3a6b 60%, #2a5298 100%);
  border-radius: 20px; padding: 32px 36px;
  display: flex; align-items: center; justify-content: space-between;
  gap: 20px; margin-bottom: 26px; position: relative; overflow: hidden;
}
.hero-dash::before {
  content: ''; position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.hero-txt { position: relative; z-index: 1; }
.hero-gr  { font-size: 12px; color: rgba(255,255,255,.55); font-weight: 500; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 6px; }
.hero-nm  { font-size: 1.85rem; font-weight: 800; color: #fff; line-height: 1.2; margin-bottom: 6px; }
.hero-sb  { font-size: 13px; color: rgba(255,255,255,.52); }
.hero-kl  { display: inline-block; margin-top: 10px; background: rgba(255,255,255,.12); color: rgba(255,255,255,.88); padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid rgba(255,255,255,.18); }
.hero-av  { position: relative; z-index: 1; flex-shrink: 0; }
.av-img   { width: 88px; height: 88px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,.3); box-shadow: 0 8px 24px rgba(0,0,0,.3); display: block; }
.av-ph    { width: 88px; height: 88px; border-radius: 50%; background: rgba(255,255,255,.14); border: 3px solid rgba(255,255,255,.28); display: flex; align-items: center; justify-content: center; font-size: 36px; }

/* Shortcut buttons in hero */
.hero-links { display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; }
.hl-btn { padding: 7px 16px; background: rgba(255,255,255,.12); color: rgba(255,255,255,.88); border: 1px solid rgba(255,255,255,.2); border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; transition: background .18s; display: inline-flex; align-items: center; gap: 5px; }
.hl-btn:hover { background: rgba(255,255,255,.2); }
.hl-btn-accent { background: rgba(255,255,255,.95); color: #0f1f3d; border-color: transparent; }
.hl-btn-accent:hover { background: #fff; }

/* Stats */
.stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 26px; }
.stat-c { background: #fff; border-radius: 13px; padding: 18px; box-shadow: 0 2px 10px rgba(0,0,0,.07); display: flex; align-items: center; gap: 12px; transition: transform .2s, box-shadow .2s; }
.stat-c:hover { transform: translateY(-3px); box-shadow: 0 8px 22px rgba(0,0,0,.11); }
.si { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.si-bl{background:#e8f0fe}.si-or{background:#fff3e0}.si-gr{background:#e8f5ee}.si-pu{background:#f3e8fd}
.sn { font-size: 1.6rem; font-weight: 800; color: #1a2235; line-height: 1; }
.sl { font-size: 11px; color: #6b7585; margin-top: 3px; }

/* Section title */
.sec-t { font-size: 14px; font-weight: 700; color: #1a2235; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; }
.sec-t a { font-size: 12px; color: #0f1f3d; font-weight: 600; text-decoration: none; }
.sec-t a:hover { text-decoration: underline; }

/* Pinjaman aktif */
.pinjam-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 26px; }
.pinjam-item { background: #fff; border-radius: 12px; padding: 13px 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); display: flex; align-items: center; gap: 12px; }
.pinjam-cov { width: 42px; height: 58px; border-radius: 6px; overflow: hidden; background: #f0f2f6; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.pinjam-cov img { width: 100%; height: 100%; object-fit: cover; }
.pinjam-jd { font-size: 13px; font-weight: 700; color: #1a2235; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pinjam-au { font-size: 11px; color: #6b7585; }
.pinjam-due { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; white-space: nowrap; flex-shrink: 0; }
.d-ok{background:#e8f5ee;color:#1a7a4a}.d-warn{background:#fff3e0;color:#e65100}.d-late{background:#fff0f0;color:#d93025}

/* Mini book grid */
.mini-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(148px,1fr)); gap: 14px; }
.mini-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,.07); transition: transform .2s, box-shadow .2s; text-decoration: none; color: inherit; display: block; }
.mini-card:hover { transform: translateY(-4px); box-shadow: 0 8px 22px rgba(0,0,0,.12); }
.mini-cov { width: 100%; height: 175px; background: #eef0f4; display: flex; align-items: center; justify-content: center; font-size: 38px; overflow: hidden; }
.mini-cov img { width: 100%; height: 100%; object-fit: cover; display: block; }
.mini-inf { padding: 10px 12px; }
.mini-t { font-size: 12px; font-weight: 700; color: #1a2235; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.mini-a { font-size: 11px; color: #6b7585; margin-top: 2px; }
.mini-b { display: inline-block; margin-top: 5px; font-size: 10px; padding: 2px 8px; border-radius: 20px; font-weight: 600; }
.mb-ok{background:#e8f5ee;color:#1a7a4a}.mb-no{background:#fff0f0;color:#d93025}

.empty-sm { background: #f8fafc; border: 1px dashed #e3e8f0; border-radius: 12px; padding: 22px; text-align: center; color: #9aa4b5; font-size: 13px; }
.empty-sm a { color: #0f1f3d; font-weight: 600; }

@media(max-width:700px){
  .stats-row{grid-template-columns:repeat(2,1fr)}
  .hero-dash{flex-direction:column;padding:22px 18px}
  .hero-nm{font-size:1.5rem}
}
</style>

<!-- Hero -->
<div class="hero-dash">
  <div class="hero-txt">
    <div class="hero-gr">Selamat datang kembali</div>
    <div class="hero-nm">Halo, <?= htmlspecialchars(explode(' ',$nama)[0]) ?>! 👋</div>
    <div class="hero-sb">Semangat membaca hari ini!</div>
    <?php if($anggota['kelas']): ?>
      <span class="hero-kl">📚 <?= htmlspecialchars($anggota['kelas']) ?></span>
    <?php endif ?>
    <div class="hero-links">
      <a href="katalog.php"  class="hl-btn hl-btn-accent">📖 Katalog Buku</a>
      <a href="pinjam.php"   class="hl-btn">📤 Pinjam Buku</a>
      <a href="riwayat.php"  class="hl-btn">🕓 Riwayat</a>
    </div>
  </div>
  <div class="hero-av">
    <?php if($foto_src): ?>
      <img src="<?= htmlspecialchars($foto_src) ?>" class="av-img" alt="foto">
    <?php else: ?>
      <div class="av-ph">👤</div>
    <?php endif ?>
  </div>
</div>

<!-- Stats -->
<div class="stats-row">
  <div class="stat-c"><div class="si si-bl">📖</div><div><div class="sn"><?= $total_pinjam ?></div><div class="sl">Total Dipinjam</div></div></div>
  <div class="stat-c"><div class="si si-or">⏳</div><div><div class="sn"><?= $aktif_pinjam ?></div><div class="sl">Sedang Dipinjam</div></div></div>
  <div class="stat-c"><div class="si si-gr">💬</div><div><div class="sn"><?= $total_ulasan ?></div><div class="sl">Ulasan Ditulis</div></div></div>
  <div class="stat-c"><div class="si si-pu">📚</div><div><div class="sn"><?= $total_buku ?></div><div class="sl">Buku Tersedia</div></div></div>
</div>

<!-- Pinjaman Aktif -->
<div class="sec-t"><span>⏳ Pinjaman Aktif</span><a href="riwayat.php">Lihat semua →</a></div>
<?php if($pinjaman_aktif->num_rows===0): ?>
  <div class="empty-sm" style="margin-bottom:26px">
    Tidak ada buku yang sedang dipinjam. <a href="katalog.php">Cari buku →</a>
  </div>
<?php else: ?>
<div class="pinjam-list">
  <?php while($p=$pinjaman_aktif->fetch_assoc()):
    $tk   = strtotime($p['tgl_kembali']);
    $hari = (int)(($tk-time())/86400);
    if($hari<0)      {$dc='d-late'; $dt='Terlambat '.abs($hari).' hari';}
    elseif($hari<=2) {$dc='d-warn'; $dt='Due '.$hari.' hari';}
    else             {$dc='d-ok';   $dt=$hari.' hari lagi';}
    $g=$p['gambar_buku'];
    $ip=($g&&is_string($g)&&file_exists(__DIR__.'/../'.$g))?BASE_URL.'/'.$g:'';
  ?>
  <div class="pinjam-item">
    <div class="pinjam-cov"><?php if($ip):?><img src="<?= htmlspecialchars($ip)?>" alt=""><?php else:?>📖<?php endif?></div>
    <div style="flex:1;min-width:0"><div class="pinjam-jd"><?= htmlspecialchars($p['judul_buku'])?></div><div class="pinjam-au"><?= htmlspecialchars($p['pengarang'])?></div></div>
    <span class="pinjam-due <?= $dc?>"><?= $dt?></span>
  </div>
  <?php endwhile ?>
</div>
<?php endif ?>

<!-- Buku Terbaru -->
<div class="sec-t"><span>🆕 Buku Terbaru</span><a href="katalog.php">Lihat semua →</a></div>
<div class="mini-grid">
  <?php while($bk=$buku_terbaru->fetch_assoc()):
    $g=$bk['gambar_buku'];
    $isrc=($g&&is_string($g)&&file_exists(__DIR__.'/../'.$g))?BASE_URL.'/'.$g:'';
  ?>
  <a href="detail.php?id=<?= $bk['id_buku']?>" class="mini-card">
    <div class="mini-cov"><?php if($isrc):?><img src="<?= htmlspecialchars($isrc)?>" alt=""><?php else:?>📖<?php endif?></div>
    <div class="mini-inf">
      <div class="mini-t"><?= htmlspecialchars($bk['judul_buku'])?></div>
      <div class="mini-a"><?= htmlspecialchars($bk['pengarang'])?></div>
      <span class="mini-b <?= $bk['status']==='tersedia'?'mb-ok':'mb-no'?>"><?= $bk['status']==='tersedia'?'Tersedia':'Dipinjam'?></span>
    </div>
  </a>
  <?php endwhile ?>
</div>

<?php include __DIR__ . '/../includes/layout_footer.php' ?>