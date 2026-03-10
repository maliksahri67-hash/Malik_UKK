<?php
$db = getDB();
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'add') {
        $nama = trim($_POST['nama_kategori'] ?? '');
        if (!$nama) { $error = 'Nama kategori wajib diisi.'; }
        else {
            $s = $db->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
            $s->bind_param('s', $nama);
            if ($s->execute()) $success = 'Kategori berhasil ditambahkan.';
            else $error = 'Kategori sudah ada.';
        }
    } elseif ($act === 'edit') {
        $id = (int)$_POST['id'];
        $nama = trim($_POST['nama_kategori'] ?? '');
        $s = $db->prepare("UPDATE kategori SET nama_kategori=? WHERE id_kategori=?");
        $s->bind_param('si', $nama, $id);
        if ($s->execute()) $success = 'Kategori diperbarui.';
        else $error = 'Gagal memperbarui.';
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM kategori WHERE id_kategori=$id");
        $success = 'Kategori dihapus.';
    }
}

$kategori = $db->query("SELECT k.*, COUNT(b.id_buku) as jml_buku FROM kategori k LEFT JOIN buku b ON k.id_kategori=b.id_kategori GROUP BY k.id_kategori ORDER BY k.id_kategori");
?>

<?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif ?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif ?>

<div class="page-header">
  <h2>Kategori Buku</h2>
  <button class="btn btn-primary" onclick="openModal('addModal')">+ Tambah Kategori</button>
</div>

<div class="card">
  <div class="table-wrap">
    <table id="dataTable">
      <thead><tr><th>#</th><th>Nama Kategori</th><th>Jumlah Buku</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php while ($k = $kategori->fetch_assoc()): ?>
        <tr>
          <td><?= $k['id_kategori'] ?></td>
          <td><?= htmlspecialchars($k['nama_kategori']) ?></td>
          <td><span class="badge badge-primary"><?= $k['jml_buku'] ?> buku</span></td>
          <td class="actions-cell">
            <button class="btn btn-warning btn-sm" onclick="editKat(<?= $k['id_kategori'] ?>, '<?= htmlspecialchars(addslashes($k['nama_kategori'])) ?>')">✏️ Edit</button>
            <form method="POST" onsubmit="return confirmDelete()">
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" value="<?= $k['id_kategori'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
            </form>
          </td>
        </tr>
        <?php endwhile ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header"><h3>Tambah Kategori</h3><button class="modal-close" onclick="closeModal('addModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="act" value="add">
        <div class="form-group"><label>Nama Kategori</label><input type="text" name="nama_kategori" class="form-control" required></div>
        <button type="submit" class="btn btn-primary btn-block">Simpan</button>
      </form>
    </div>
  </div>
</div>

<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header"><h3>Edit Kategori</h3><button class="modal-close" onclick="closeModal('editModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="act" value="edit">
        <input type="hidden" name="id" id="ek_id">
        <div class="form-group"><label>Nama Kategori</label><input type="text" name="nama_kategori" id="ek_nama" class="form-control" required></div>
        <button type="submit" class="btn btn-primary btn-block">Update</button>
      </form>
    </div>
  </div>
</div>

<script>
function editKat(id, nama) {
  document.getElementById('ek_id').value = id;
  document.getElementById('ek_nama').value = nama;
  openModal('editModal');
}
</script>
