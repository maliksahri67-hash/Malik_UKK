<?php
define('BASE_URL', '..');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

requireLoginMulti(['Admin','Petugas']);

$pageTitle = 'Laporan';
$activeNav = 'laporan';
$role = getRole();

$db = getDB();

$filter  = $_GET['filter'] ?? 'transaksi';
$dari    = $_GET['dari'] ?? date('Y-m-01');
$sampai  = $_GET['sampai'] ?? date('Y-m-d');

$data = null;
$cols = [];

/*
====================================
QUERY DATA
====================================
*/

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
    JOIN anggota a ON t.id_anggota = a.id_anggota
    JOIN buku b ON t.id_buku = b.id_buku
    JOIN kategori k ON b.id_kategori = k.id_kategori
    WHERE DATE(t.tgl_pinjam) BETWEEN '$dari' AND '$sampai'
    ORDER BY t.tgl_pinjam DESC
    ";

    $cols = ['#','Anggota','Kelas','Buku','Kategori','Tgl Pinjam','Tgl Kembali','Status'];

}

elseif ($filter === 'denda') {

    $query = "
    SELECT
        t.id_transaksi,
        a.nama_anggota,
        a.kelas,
        b.judul_buku,
        t.tgl_kembali,
        DATEDIFF(NOW(),t.tgl_kembali) AS hari_telat
    FROM transaksi t
    JOIN anggota a ON t.id_anggota = a.id_anggota
    JOIN buku b ON t.id_buku = b.id_buku
    WHERE t.status_transaksi='Peminjaman'
    AND t.tgl_kembali < NOW()
    AND DATE(t.tgl_pinjam) BETWEEN '$dari' AND '$sampai'
    ORDER BY hari_telat DESC
    ";

    $cols = ['#','Anggota','Kelas','Buku','Tgl Kembali','Hari Telat','Denda'];

}

elseif ($filter === 'buku') {

    $query = "
    SELECT
        b.id_buku,
        b.judul_buku,
        k.nama_kategori,
        b.pengarang,
        b.penerbit,
        b.tahun_terbit,
        b.status
    FROM buku b
    JOIN kategori k ON b.id_kategori = k.id_kategori
    ORDER BY b.judul_buku
    ";

    $cols = ['#','Judul','Kategori','Pengarang','Penerbit','Tahun','Status'];

}

elseif ($filter === 'anggota') {

    $query = "
    SELECT
        a.id_anggota,
        a.nis,
        a.nama_anggota,
        a.email,
        a.kelas,
        COUNT(t.id_transaksi) AS total_pinjam
    FROM anggota a
    LEFT JOIN transaksi t ON a.id_anggota = t.id_anggota
    GROUP BY a.id_anggota
    ORDER BY a.nama_anggota
    ";

    $cols = ['#','NIS','Nama','Email','Kelas','Total Pinjam'];

}

elseif ($filter === 'pengguna') {

    $query = "
    SELECT
        id_pengguna,
        username,
        nama_pengguna,
        level
    FROM pengguna
    ORDER BY level, nama_pengguna
    ";

    $cols = ['#','Username','Nama','Level'];

}

$data = $db->query($query);


/*
====================================
EXPORT CSV
====================================
*/

if (isset($_GET['export'])) {

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_'.$filter.'_'.date('Ymd').'.csv');

    $out = fopen('php://output', 'w');

    fputcsv($out,$cols);

    $no = 1;

    while ($row = $data->fetch_assoc()) {

        if ($filter === 'transaksi') {

            fputcsv($out,[
                $no++,
                $row['nama_anggota'],
                $row['kelas'],
                $row['judul_buku'],
                $row['nama_kategori'],
                $row['tgl_pinjam'],
                $row['tgl_kembali'],
                $row['status_transaksi']
            ]);

        }

        elseif ($filter === 'denda') {

            $denda = max(0,$row['hari_telat']) * 1000;

            fputcsv($out,[
                $no++,
                $row['nama_anggota'],
                $row['kelas'],
                $row['judul_buku'],
                $row['tgl_kembali'],
                $row['hari_telat'],
                $denda
            ]);

        }

        else {

            fputcsv($out,array_merge([$no++],array_values($row)));

        }

    }

    fclose($out);
    exit;

}


/*
====================================
LAYOUT
====================================
*/

include __DIR__ . '/../includes/layout.php';
?>

<div class="filter-bar">

<form method="GET" class="flex gap-2 items-center" style="flex-wrap:wrap;">

<select name="filter" class="form-control" style="width:180px;">

<option value="transaksi" <?= $filter==='transaksi'?'selected':'' ?>>Peminjaman/Pengembalian</option>

<option value="denda" <?= $filter==='denda'?'selected':'' ?>>Denda</option>

<option value="buku" <?= $filter==='buku'?'selected':'' ?>>Koleksi Buku</option>

<option value="anggota" <?= $filter==='anggota'?'selected':'' ?>>Anggota</option>

<?php if ($role === 'Admin'): ?>
<option value="pengguna" <?= $filter==='pengguna'?'selected':'' ?>>Pengguna Sistem</option>
<?php endif ?>

</select>

<?php if (in_array($filter,['transaksi','denda'])): ?>

<input type="date" name="dari" class="form-control" value="<?= $dari ?>" style="width:160px;">

<span>s/d</span>

<input type="date" name="sampai" class="form-control" value="<?= $sampai ?>" style="width:160px;">

<?php endif ?>

<button type="submit" class="btn btn-primary">🔍 Tampilkan</button>

<a href="?filter=<?= $filter ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>&export=1"
class="btn btn-accent">📥 Export CSV</a>
<div class="print-btn">
<button onclick="window.print()" class="btn btn-primary">🖨 Cetak Laporan</button>
</div>
</form>

</div>


<div class="card">

<div class="card-header">

<h3>📊 Laporan <?= ucfirst($filter) ?></h3>

<span class="text-muted" style="font-size:13px;"><?= $data->num_rows ?> data</span>

</div>


<div class="table-wrap">

<table id="dataTable">

<thead>

<tr>

<?php foreach ($cols as $c): ?>
<th><?= $c ?></th>
<?php endforeach ?>

</tr>

</thead>


<tbody>

<?php $no=1; while($r=$data->fetch_assoc()): ?>

<tr>

<td><?= $no++ ?></td>

<?php if($filter==='transaksi'): ?>

<td><?= htmlspecialchars($r['nama_anggota']) ?></td>
<td><?= htmlspecialchars($r['kelas']) ?></td>
<td><?= htmlspecialchars($r['judul_buku']) ?></td>
<td><?= htmlspecialchars($r['nama_kategori']) ?></td>
<td><?= date('d/m/Y',strtotime($r['tgl_pinjam'])) ?></td>
<td><?= date('d/m/Y',strtotime($r['tgl_kembali'])) ?></td>
<td>
<span class="badge <?= $r['status_transaksi']=='Peminjaman'?'badge-warning':'badge-success' ?>">
<?= $r['status_transaksi'] ?>
</span>
</td>

<?php elseif($filter==='denda'): ?>

<td><?= htmlspecialchars($r['nama_anggota']) ?></td>
<td><?= htmlspecialchars($r['kelas']) ?></td>
<td><?= htmlspecialchars($r['judul_buku']) ?></td>
<td><?= date('d/m/Y',strtotime($r['tgl_kembali'])) ?></td>
<td><span class="badge badge-danger"><?= $r['hari_telat'] ?> hari</span></td>
<td><strong>Rp <?= number_format(max(0,$r['hari_telat'])*1000,0,',','.') ?></strong></td>

<?php else: ?>

<?php foreach($r as $val): ?>
<td><?= htmlspecialchars($val) ?></td>
<?php endforeach ?>

<?php endif ?>

</tr>

<?php endwhile ?>

</tbody>

</table>

</div>

</div>


<?php include __DIR__ . '/../includes/layout_footer.php'; ?>