<?php
define('BASE_URL', '..');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
requireLogin('Admin');

$db = getDB();
$error = $success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add' || $act === 'edit') {
        $username = trim($_POST['username'] ?? '');
        $nama = trim($_POST['nama_pengguna'] ?? '');
        $level = $_POST['level'] ?? 'Petugas';
        $pass = $_POST['password'] ?? '';

        if ($act === 'add') {
            if (!$username || !$nama || !$pass) { $error = 'Semua field wajib diisi.'; }
            else {
                $hashed = password_hash($pass, PASSWORD_DEFAULT);
                $s = $db->prepare("INSERT INTO pengguna (username,password,nama_pengguna,level) VALUES (?,?,?,?)");
                $s->bind_param('ssss', $username, $hashed, $nama, $level);
                if ($s->execute()) $success = 'Pengguna berhasil ditambahkan.';
                else $error = 'Username sudah digunakan.';
            }
        } else {
            $id = (int)$_POST['id'];
            if ($pass) {
                $hashed = password_hash($pass, PASSWORD_DEFAULT);
                $s = $db->prepare("UPDATE pengguna SET username=?,password=?,nama_pengguna=?,level=? WHERE id_pengguna=?");
                $s->bind_param('ssssi', $username, $hashed, $nama, $level, $id);
            } else {
                $s = $db->prepare("UPDATE pengguna SET username=?,nama_pengguna=?,level=? WHERE id_pengguna=?");
                $s->bind_param('sssi', $username, $nama, $level, $id);
            }
            if ($s->execute()) $success = 'Pengguna berhasil diperbarui.';
            else $error = 'Gagal memperbarui.';
        }
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        if ($id == $_SESSION['user_id']) { $error = 'Tidak bisa menghapus akun sendiri.'; }
        else {
            $db->query("DELETE FROM pengguna WHERE id_pengguna=$id");
            $success = 'Pengguna dihapus.';
        }
    }
}

$pengguna = $db->query("SELECT * FROM pengguna ORDER BY id_pengguna");
$pageTitle = 'Manajemen Pengguna';
$activeNav = 'pengguna';
$role = 'Admin';
include __DIR__ . '/../includes/layout.php';
?>

<?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif ?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif ?>

<div class="page-header">
  <h2>Daftar Pengguna</h2>
  <div class="flex gap-2 items-center">
    <div class="search-bar"><input type="text" id="searchInput" placeholder="Cari pengguna..."></div>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Tambah Pengguna</button>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table id="dataTable">
      <thead>
        <tr><th>#</th><th>Username</th><th>Nama</th><th>Level</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php $nomor = 1; while ($u = $pengguna->fetch_assoc()): ?>
        <tr>
          <td><?= $nomor++; $nomor; ?></td>
          <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
          <td><?= htmlspecialchars($u['nama_pengguna']) ?></td>
          <td><span class="badge <?= $u['level']==='Admin'?'badge-primary':'badge-gold' ?>"><?= $u['level'] ?></span></td>
          <td class="actions-cell">
            <button class="btn btn-warning btn-sm" onclick="editPengguna(<?= htmlspecialchars(json_encode($u)) ?>)">✏️ Edit</button>
            <?php if ($u['id_pengguna'] != $_SESSION['user_id']): ?>
            <form method="POST" onsubmit="return confirmDelete()">
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" value="<?= $u['id_pengguna'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">🗑️ Hapus</button>
            </form>
            <?php endif ?>
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
    <div class="modal-header">
      <h3>Tambah Pengguna</h3>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="act" value="add">
        <div class="form-group"><label>Username</label><input type="text" name="username" class="form-control" required></div>
        <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_pengguna" class="form-control" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
        <div class="form-group"><label>Level</label>
          <select name="level" class="form-control">
            <option value="Admin">Admin</option>
            <option value="Petugas">Petugas</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Simpan</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Pengguna</h3>
      <button class="modal-close" onclick="closeModal('editModal')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="act" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="form-group"><label>Username</label><input type="text" name="username" id="edit_username" class="form-control" required></div>
        <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_pengguna" id="edit_nama" class="form-control" required></div>
        <div class="form-group"><label>Password Baru <small>(kosongkan jika tidak diubah)</small></label><input type="password" name="password" class="form-control"></div>
        <div class="form-group"><label>Level</label>
          <select name="level" id="edit_level" class="form-control">
            <option value="Admin">Admin</option>
            <option value="Petugas">Petugas</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Update</button>
      </form>
    </div>
  </div>
</div>

<script>
function editPengguna(u) {
  document.getElementById('edit_id').value = u.id_pengguna;
  document.getElementById('edit_username').value = u.username;
  document.getElementById('edit_nama').value = u.nama_pengguna;
  document.getElementById('edit_level').value = u.level;
  openModal('editModal');
}
</script>

<?php include __DIR__ . '/../includes/layout_footer.php' ?>
