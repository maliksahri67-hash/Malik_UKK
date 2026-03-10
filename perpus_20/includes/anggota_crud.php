<?php
$isAdmin = (getRole() === 'Admin');
$db = getDB();
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add') {
        $nis = trim($_POST['nis'] ?? '');
        $nama = trim($_POST['nama_anggota'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $pass = $_POST['password'] ?? '';
        if (!$nis || !$nama || !$email || !$kelas || !$pass) { $error = 'Semua field wajib diisi.'; }
        else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $foto = '';
            if (isset($_FILES['foto']) && $_FILES['foto']['size'] > 0) {
                $foto = 'uploads/anggota_' . time() . '_' . basename($_FILES['foto']['name']);
                move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/../' . $foto);
            }
            $s = $db->prepare("INSERT INTO anggota (nis,nama_anggota,email,password,kelas,foto) VALUES (?,?,?,?,?,?)");
            $s->bind_param('ssssss', $nis, $nama, $email, $hashed, $kelas, $foto);
            if ($s->execute()) $success = 'Anggota berhasil ditambahkan.';
            else $error = 'NIS atau email sudah terdaftar.';
        }
    } elseif ($act === 'edit') {
        $id = (int)$_POST['id'];
        $nis = trim($_POST['nis'] ?? '');
        $nama = trim($_POST['nama_anggota'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $s = $db->prepare("UPDATE anggota SET nis=?,nama_anggota=?,email=?,kelas=? WHERE id_anggota=?");
        $s->bind_param('ssssi', $nis, $nama, $email, $kelas, $id);
        if ($s->execute()) $success = 'Anggota berhasil diperbarui.';
        else $error = 'Gagal memperbarui.';
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM anggota WHERE id_anggota=$id");
        $success = 'Anggota dihapus.';
    } elseif ($act === 'resetpw') {
        $id = (int)$_POST['id'];
        $pass = $_POST['new_password'] ?? '';
        if (!$pass) { $error = 'Password baru wajib diisi.'; }
        else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $s = $db->prepare("UPDATE anggota SET password=? WHERE id_anggota=?");
            $s->bind_param('si', $hashed, $id);
            if ($s->execute()) $success = 'Password berhasil direset.';
            else $error = 'Gagal reset password.';
        }
    }
}

$anggota = $db->query("SELECT * FROM anggota ORDER BY id_anggota DESC");
?>

<?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif ?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif ?>

<div class="page-header">
  <h2>Daftar Anggota</h2>
  <div class="flex gap-2 items-center">
    <div class="search-bar"><input type="text" id="searchInput" placeholder="Cari anggota..."></div>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Tambah Anggota</button>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table id="dataTable">
      <thead>
        <tr><th>#</th><th>NIS</th><th>Nama</th><th>Email</th><th>Kelas</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php $nomor = 1; while ($a = $anggota->fetch_assoc()): ?>
        <tr>
          <td><?= $nomor++; $nomor; ?></td>
          <td><?= htmlspecialchars($a['nis']) ?></td>
          <td><strong><?= htmlspecialchars($a['nama_anggota']) ?></strong></td>
          <td><?= htmlspecialchars($a['email']) ?></td>
          <td><span class="badge badge-gold"><?= htmlspecialchars($a['kelas']) ?></span></td>
          <td class="actions-cell">
            <button class="btn btn-warning btn-sm" onclick="editAnggota(<?= htmlspecialchars(json_encode(['id'=>$a['id_anggota'],'nis'=>$a['nis'],'nama'=>$a['nama_anggota'],'email'=>$a['email'],'kelas'=>$a['kelas']])) ?>)">✏️ Edit</button>
            <button class="btn btn-outline btn-sm" onclick="resetPw(<?= $a['id_anggota'] ?>, '<?= htmlspecialchars($a['nama_anggota']) ?>')">🔑 Reset PW</button>
            <form method="POST" onsubmit="return confirmDelete()">
              <input type="hidden" name="act" value="delete">
              <input type="hidden" name="id" value="<?= $a['id_anggota'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
            </form>
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
    <div class="modal-header"><h3>Tambah Anggota</h3><button class="modal-close" onclick="closeModal('addModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="act" value="add">
        <div class="two-col">
          <div class="form-group"><label>NIS</label><input type="text" name="nis" class="form-control" required></div>
          <div class="form-group"><label>Kelas</label><input type="text" name="kelas" class="form-control" required></div>
        </div>
        <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_anggota" class="form-control" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
        <div class="form-group"><label>Foto (opsional)</label><input type="file" name="foto" class="form-control" accept="image/*"></div>
        <button type="submit" class="btn btn-primary btn-block">Simpan</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header"><h3>Edit Anggota</h3><button class="modal-close" onclick="closeModal('editModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="act" value="edit">
        <input type="hidden" name="id" id="e_id">
        <div class="two-col">
          <div class="form-group"><label>NIS</label><input type="text" name="nis" id="e_nis" class="form-control" required></div>
          <div class="form-group"><label>Kelas</label><input type="text" name="kelas" id="e_kelas" class="form-control" required></div>
        </div>
        <div class="form-group"><label>Nama</label><input type="text" name="nama_anggota" id="e_nama" class="form-control" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" id="e_email" class="form-control" required></div>
        <button type="submit" class="btn btn-primary btn-block">Update</button>
      </form>
    </div>
  </div>
</div>

<!-- Reset PW Modal -->
<div class="modal-overlay" id="resetModal">
  <div class="modal">
    <div class="modal-header"><h3>Reset Password Anggota</h3><button class="modal-close" onclick="closeModal('resetModal')">✕</button></div>
    <div class="modal-body">
      <p class="mb-2">Reset password untuk: <strong id="reset_nama"></strong></p>
      <form method="POST">
        <input type="hidden" name="act" value="resetpw">
        <input type="hidden" name="id" id="reset_id">
        <div class="form-group"><label>Password Baru</label><input type="password" name="new_password" class="form-control" required></div>
        <button type="submit" class="btn btn-warning btn-block">Reset Password</button>
      </form>
    </div>
  </div>
</div>

<script>
function editAnggota(a) {
  document.getElementById('e_id').value = a.id;
  document.getElementById('e_nis').value = a.nis;
  document.getElementById('e_nama').value = a.nama;
  document.getElementById('e_email').value = a.email;
  document.getElementById('e_kelas').value = a.kelas;
  openModal('editModal');
}
function resetPw(id, nama) {
  document.getElementById('reset_id').value = id;
  document.getElementById('reset_nama').textContent = nama;
  openModal('resetModal');
}
</script>
