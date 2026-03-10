<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

requireLoginMulti(['Admin','Petugas']);

$db = getDB();

$filter  = $_GET['filter'] ?? 'transaksi';
$dari    = $_GET['dari'] ?? date('Y-m-01');
$sampai  = $_GET['sampai'] ?? date('Y-m-d');


if ($filter === 'transaksi') {

$query = "
SELECT 
t.id_transaksi,
a.nama_anggota,
a.kelas,
b.judul_buku,
k.nama_kategori,
t.tgl_pinjam,
t.tgl_kembali,
t.status_transaksi
FROM transaksi t
JOIN anggota a ON t.id_anggota=a.id_anggota
JOIN buku b ON t.id_buku=b.id_buku
JOIN kategori k ON b.id_kategori=k.id_kategori
WHERE DATE(t.tgl_pinjam) BETWEEN '$dari' AND '$sampai'
ORDER BY t.tgl_pinjam DESC
";

$cols = ['No','Anggota','Kelas','Buku','Kategori','Tgl Pinjam','Tgl Kembali','Status'];

}

elseif ($filter === 'denda') {

$query = "
SELECT
a.nama_anggota,
a.kelas,
b.judul_buku,
t.tgl_kembali,
DATEDIFF(NOW(),t.tgl_kembali) AS hari_telat
FROM transaksi t
JOIN anggota a ON t.id_anggota=a.id_anggota
JOIN buku b ON t.id_buku=b.id_buku
WHERE t.status_transaksi='Peminjaman'
AND t.tgl_kembali < NOW()
";

$cols = ['No','Anggota','Kelas','Buku','Tgl Kembali','Hari Telat','Denda'];

}

$data = $db->query($query);
?>

<!DOCTYPE html>
<html>
<head>

<title>Cetak Laporan</title>

<style>

body{
font-family:Arial;
padding:30px;
}

h2{
text-align:center;
margin-bottom:5px;
}

.subtitle{
text-align:center;
margin-bottom:20px;
font-size:14px;
}

table{
width:100%;
border-collapse:collapse;
}

th,td{
border:1px solid #000;
padding:8px;
font-size:13px;
}

th{
background:#eee;
}

.print-btn{
margin-bottom:20px;
}
.btn{
display:inline-block;
padding:8px 16px;
border-radius:6px;
border:none;
cursor:pointer;
font-size:14px;
text-decoration:none;
}

.btn-primary{
background:#2563eb;
color:white;
}

.btn-primary:hover{
background:#1d4ed8;
}

@media print{

.print-btn{
display:none;
}

}

</style>

</head>

<body>

<div class="print-btn">
<button onclick="window.print()">🖨 Print</button>
</div>

<h2>LAPORAN PERPUSTAKAAN</h2>

<div class="subtitle">
Filter : <?= ucfirst($filter) ?> |
Tanggal : <?= $dari ?> s/d <?= $sampai ?>
</div>

<table>

<thead>
<tr>
<?php foreach($cols as $c): ?>
<th><?= $c ?></th>
<?php endforeach ?>
</tr>
</thead>

<tbody>

<?php $no=1; while($r=$data->fetch_assoc()): ?>

<tr>

<td><?= $no++ ?></td>

<?php if($filter=='transaksi'): ?>

<td><?= $r['nama_anggota'] ?></td>
<td><?= $r['kelas'] ?></td>
<td><?= $r['judul_buku'] ?></td>
<td><?= $r['nama_kategori'] ?></td>
<td><?= date('d/m/Y',strtotime($r['tgl_pinjam'])) ?></td>
<td><?= date('d/m/Y',strtotime($r['tgl_kembali'])) ?></td>
<td><?= $r['status_transaksi'] ?></td>

<?php elseif($filter=='denda'): ?>

<td><?= $r['nama_anggota'] ?></td>
<td><?= $r['kelas'] ?></td>
<td><?= $r['judul_buku'] ?></td>
<td><?= date('d/m/Y',strtotime($r['tgl_kembali'])) ?></td>
<td><?= $r['hari_telat'] ?> hari</td>
<td>Rp <?= number_format($r['hari_telat']*1000,0,',','.') ?></td>

<?php endif ?>

</tr>

<?php endwhile ?>

</tbody>

</table>

<script>
window.onload=function(){
window.print();
}
</script>

</body>
</html>