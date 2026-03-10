<?php
define('BASE_URL', '..');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
requireLoginMulti(['Admin','Petugas']);
$pageTitle = 'Laporan'; $activeNav = 'laporan'; $role = getRole();
// Include same laporan as admin (pengguna section checks role internally)
include __DIR__ . '/../includes/layout.php';
$db = getDB();
$filter = $_GET['filter'] ?? 'transaksi';
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
if ($filter === 'pengguna') $filter = 'transaksi';
?>
<div class="filter-bar">
  <form method="GET" class="flex gap-2 items-center" style="flex-wrap:wrap;">
    <select name="filter" class="form-control" style="width:180px;">
      <option value="transaksi" <?=$filter==='transaksi'?'selected':''?>>Peminjaman/Pengembalian</option>
      <option value="denda" <?=$filter==='denda'?'selected':''?>>Denda</option>
      <option value="buku" <?=$filter==='buku'?'selected':''?>>Koleksi Buku</option>
      <option value="anggota" <?=$filter==='anggota'?'selected':''?>>Anggota</option>
    </select>
    <?php if(in_array($filter,['transaksi','denda'])): ?>
    <input type="date" name="dari" class="form-control" value="<?=$dari?>" style="width:160px;">
    <span>s/d</span>
    <input type="date" name="sampai" class="form-control" value="<?=$sampai?>" style="width:160px;">
    <?php endif ?>
    <button type="submit" class="btn btn-primary">🔍 Tampilkan</button>
    <a href="?filter=<?=$filter?>&dari=<?=$dari?>&sampai=<?=$sampai?>&export=1" class="btn btn-accent">📥 Export CSV</a>
  </form>
</div>
<?php
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_'.$filter.'_'.date('Ymd').'.csv"');
    $out = fopen('php://output','w');
}
if ($filter==='transaksi') { $data=$db->query("SELECT t.id_transaksi,a.nama_anggota,a.kelas,b.judul_buku,k.nama_kategori,t.tgl_pinjam,t.tgl_kembali,t.status_transaksi FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku JOIN kategori k ON b.id_kategori=k.id_kategori WHERE DATE(t.tgl_pinjam) BETWEEN '$dari' AND '$sampai' ORDER BY t.tgl_pinjam DESC"); $cols=['#','Anggota','Kelas','Buku','Kategori','Tgl Pinjam','Tgl Kembali','Status']; }
elseif ($filter==='denda') { $data=$db->query("SELECT t.id_transaksi,a.nama_anggota,a.kelas,b.judul_buku,t.tgl_kembali,DATEDIFF(NOW(),t.tgl_kembali) AS hari_telat,(DATEDIFF(NOW(),t.tgl_kembali)*1000) AS denda FROM transaksi t JOIN anggota a ON t.id_anggota=a.id_anggota JOIN buku b ON t.id_buku=b.id_buku WHERE t.status_transaksi='Peminjaman' AND t.tgl_kembali<NOW() AND DATE(t.tgl_pinjam) BETWEEN '$dari' AND '$sampai' ORDER BY hari_telat DESC"); $cols=['#','Anggota','Kelas','Buku','Tgl Kembali','Hari Telat','Denda (Rp)']; }
elseif ($filter==='buku') { $data=$db->query("SELECT b.id_buku,b.judul_buku,k.nama_kategori,b.pengarang,b.penerbit,b.tahun_terbit,b.status FROM buku b JOIN kategori k ON b.id_kategori=k.id_kategori ORDER BY b.judul_buku"); $cols=['#','Judul','Kategori','Pengarang','Penerbit','Tahun','Status']; }
elseif ($filter==='anggota') { $data=$db->query("SELECT a.id_anggota,a.nis,a.nama_anggota,a.email,a.kelas,COUNT(t.id_transaksi) AS total_pinjam FROM anggota a LEFT JOIN transaksi t ON a.id_anggota=t.id_anggota GROUP BY a.id_anggota ORDER BY a.nama_anggota"); $cols=['#','NIS','Nama','Email','Kelas','Total Pinjam']; }
if(isset($_GET['export'])){fputcsv($out,$cols);while($r=$data->fetch_row())fputcsv($out,$r);fclose($out);exit;}
?>
<div class="card">
  <div class="card-header"><h3>📊 Laporan <?=ucfirst($filter)?></h3><span class="text-muted" style="font-size:13px;"><?=$data->num_rows?> data</span></div>
  <div class="table-wrap"><table id="dataTable"><thead><tr><?php foreach($cols as $c):?><th><?=$c?></th><?php endforeach?></tr></thead><tbody>
<?php if($filter==='transaksi') while($r=$data->fetch_assoc()):?>
<tr><td><?=$r['id_transaksi']?></td><td><?=htmlspecialchars($r['nama_anggota'])?></td><td><?=htmlspecialchars($r['kelas'])?></td><td><?=htmlspecialchars($r['judul_buku'])?></td><td><?=htmlspecialchars($r['nama_kategori'])?></td><td><?=date('d/m/Y',strtotime($r['tgl_pinjam']))?></td><td><?=date('d/m/Y',strtotime($r['tgl_kembali']))?></td><td><span class="badge <?=$r['status_transaksi']==='Peminjaman'?'badge-warning':'badge-success'?>"><?=$r['status_transaksi']?></span></td></tr>
<?php endwhile ?>
<?php if($filter==='denda') while($r=$data->fetch_assoc()):$d=max(0,$r['hari_telat'])*1000;?>
<tr><td><?=$r['id_transaksi']?></td><td><?=htmlspecialchars($r['nama_anggota'])?></td><td><?=htmlspecialchars($r['kelas'])?></td><td><?=htmlspecialchars($r['judul_buku'])?></td><td><?=date('d/m/Y',strtotime($r['tgl_kembali']))?></td><td><span class="badge badge-danger"><?=$r['hari_telat']?> hari</span></td><td><strong class="denda-amount">Rp <?=number_format($d,0,',','.')?></strong></td></tr>
<?php endwhile ?>
<?php if($filter==='buku') while($r=$data->fetch_assoc()):?>
<tr><td><?=$r['id_buku']?></td><td><?=htmlspecialchars($r['judul_buku'])?></td><td><?=htmlspecialchars($r['nama_kategori'])?></td><td><?=htmlspecialchars($r['pengarang'])?></td><td><?=htmlspecialchars($r['penerbit'])?></td><td><?=$r['tahun_terbit']?></td><td><span class="badge <?=$r['status']==='tersedia'?'badge-success':'badge-danger'?>"><?=$r['status']?></span></td></tr>
<?php endwhile ?>
<?php if($filter==='anggota') while($r=$data->fetch_assoc()):?>
<tr><td><?=$r['id_anggota']?></td><td><?=htmlspecialchars($r['nis'])?></td><td><?=htmlspecialchars($r['nama_anggota'])?></td><td><?=htmlspecialchars($r['email'])?></td><td><?=htmlspecialchars($r['kelas'])?></td><td><span class="badge badge-primary"><?=$r['total_pinjam']?> kali</span></td></tr>
<?php endwhile ?>
  </tbody></table></div></div>
<?php include __DIR__ . '/../includes/layout_footer.php' ?>
