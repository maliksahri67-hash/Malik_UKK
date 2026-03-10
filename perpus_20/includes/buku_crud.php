<?php
$db = getDB();
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'add' || $act === 'edit') {
        $judul = trim($_POST['judul_buku'] ?? '');
        $id_kat = (int)$_POST['id_kategori'];
        $pengarang = trim($_POST['pengarang'] ?? '');
        $penerbit = trim($_POST['penerbit'] ?? '');
        $tahun = (int)$_POST['tahun_terbit'];
        $deskripsi = trim($_POST['deskripsi_buku'] ?? '');
        $status = $_POST['status'] ?? 'tersedia';

        $gambar = '';
        if (isset($_FILES['gambar_buku']) && $_FILES['gambar_buku']['size'] > 0) {
            $ext = pathinfo($_FILES['gambar_buku']['name'], PATHINFO_EXTENSION);
            $fname = 'buku_' . time() . '_' . rand(100,999) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/' . $fname;
            if (!is_dir(__DIR__ . '/../uploads')) mkdir(__DIR__ . '/../uploads', 0755, true);
            move_uploaded_file($_FILES['gambar_buku']['tmp_name'], $dest);
            $gambar = 'uploads/' . $fname;
        }

        if ($act === 'add') {
            if (!$judul || !$pengarang) { $error = 'Judul dan pengarang wajib diisi.'; }
            else {
                if ($gambar) {
                    $s = $db->prepare("INSERT INTO buku (judul_buku,id_kategori,pengarang,penerbit,tahun_terbit,gambar_buku,deskripsi_buku,status) VALUES (?,?,?,?,?,?,?,?)");
                   $gpath = $gambar;
                    $s = $db->prepare("INSERT INTO buku 
                    (judul_buku,id_kategori,pengarang,penerbit,tahun_terbit,gambar_buku,deskripsi_buku,status) 
                    VALUES (?,?,?,?,?,?,?,?)");

                    $s->bind_param('sississs', 
                        $judul, 
                        $id_kat, 
                        $pengarang, 
                        $penerbit, 
                        $tahun, 
                        $gpath, 
                        $deskripsi, 
                        $status
                    );
                    // Actually store path as text since blob storage is complex
                    $s2 = $db->prepare("INSERT INTO buku (judul_buku,id_kategori,pengarang,penerbit,tahun_terbit,gambar_buku,deskripsi_buku,status) VALUES (?,?,?,?,?,?,?,?)");
                    $gpath = $gambar;
                    $s2->bind_param('sississs', $judul, $id_kat, $pengarang, $penerbit, $tahun, $gpath, $deskripsi, $status);
                    if ($s2->execute()) $success = 'Buku berhasil ditambahkan.';
                    else $error = 'Gagal menambahkan buku: ' . $db->error;
                } else {
                    $gpath = '';
                    $s = $db->prepare("INSERT INTO buku (judul_buku,id_kategori,pengarang,penerbit,tahun_terbit,gambar_buku,deskripsi_buku,status) VALUES (?,?,?,?,?,?,?,?)");
                    $s->bind_param('sississs', $judul, $id_kat, $pengarang, $penerbit, $tahun, $gpath, $deskripsi, $status);
                    if ($s->execute()) $success = 'Buku berhasil ditambahkan.';
                    else $error = 'Gagal: ' . $db->error;
                }
            }
        } else {
            $id = (int)$_POST['id'];
            if ($gambar) {
                $gpath = $gambar;
                $s = $db->prepare("UPDATE buku SET judul_buku=?,id_kategori=?,pengarang=?,penerbit=?,tahun_terbit=?,gambar_buku=?,deskripsi_buku=?,status=? WHERE id_buku=?");
                $s->bind_param('sississsi', $judul, $id_kat, $pengarang, $penerbit, $tahun, $gpath, $deskripsi, $status, $id);
            } else {
                $s = $db->prepare("UPDATE buku SET judul_buku=?,id_kategori=?,pengarang=?,penerbit=?,tahun_terbit=?,deskripsi_buku=?,status=? WHERE id_buku=?");
                $s->bind_param('sississi', $judul, $id_kat, $pengarang, $penerbit, $tahun, $deskripsi, $status, $id);
            }
            if ($s->execute()) $success = 'Buku berhasil diperbarui.';
            else $error = 'Gagal: ' . $db->error;
        }
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM buku WHERE id_buku=$id");
        $success = 'Buku dihapus.';
    }
}

$buku = $db->query("SELECT b.*, k.nama_kategori FROM buku b JOIN kategori k ON b.id_kategori=k.id_kategori ORDER BY b.id_buku DESC");
$kategori_list = $db->query("SELECT * FROM kategori ORDER BY nama_kategori");
?>

<?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif ?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif ?>

<div class="page-header">
  <h2>Koleksi Buku</h2>
  <div class="flex gap-2 items-center">
    <div class="search-bar"><input type="text" id="searchInput" placeholder="Cari buku..."></div>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Tambah Buku</button>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table id="dataTable">
      <thead>
        <tr>
          <th>No</th>
          <th>Cover</th>
          <th>Judul</th>
          <th>Kategori</th>
          <th>Pengarang</th>
          <th>Penerbit</th>
          <th>Tahun</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>

      <tbody>
        <?php 
        $no = 1;
        while ($b = $buku->fetch_assoc()): 
        ?>
        <tr>
          <td><?= $no++ ?></td>

          <td>
            <?php if(!empty($b['gambar_buku'])): ?>
              <img src="../<?= $b['gambar_buku'] ?>" 
                   style="width:50px;height:70px;object-fit:cover;border-radius:6px;">
            <?php else: ?>
              <span style="color:#999;">Tidak ada</span>
            <?php endif; ?>
          </td>

          <td><strong><?= htmlspecialchars($b['judul_buku']) ?></strong></td>
          <td><?= htmlspecialchars($b['nama_kategori']) ?></td>
          <td><?= htmlspecialchars($b['pengarang']) ?></td>
          <td><?= htmlspecialchars($b['penerbit']) ?></td>
          <td><?= $b['tahun_terbit'] ?></td>

          <td>
            <span class="badge <?= $b['status']==='tersedia'?'badge-success':'badge-danger' ?>">
              <?= ucfirst($b['status']) ?>
            </span>
          </td>
          <td>
            <div style="display:flex; flex-direction:row; align-items:center; gap:6px;">
              <button class="btn btn-warning btn-sm"
                onclick="editBuku(<?= htmlspecialchars(json_encode([
                  'id'=>$b['id_buku'],
                  'judul'=>$b['judul_buku'],
                  'id_kat'=>$b['id_kategori'],
                  'pengarang'=>$b['pengarang'],
                  'penerbit'=>$b['penerbit'],
                  'tahun'=>$b['tahun_terbit'],
                  'deskripsi'=>$b['deskripsi_buku'],
                  'status'=>$b['status']
                ])) ?>)">✏️ Edit</button>

              <form method="POST" onsubmit="return confirmDelete()"
                style="display:inline-flex; margin:0; padding:0;">
                <input type="hidden" name="act" value="delete">
                <input type="hidden" name="id" value="<?= $b['id_buku'] ?>">
                <button type="submit" class="btn btn-danger btn-sm" style="margin:0;">🗑️</button>
              </form>
            </div>
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
    <div class="modal-header"><h3>Tambah Buku</h3><button class="modal-close" onclick="closeModal('addModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="act" value="add">
        <div class="form-group"><label>Judul Buku</label><input type="text" name="judul_buku" class="form-control" required></div>
        <div class="two-col">
          <div class="form-group"><label>Kategori</label>
            <select name="id_kategori" class="form-control" required>
              <?php $kategori_list->data_seek(0); while ($k = $kategori_list->fetch_assoc()): ?>
              <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
              <?php endwhile ?>
            </select>
          </div>
          <div class="form-group"><label>Tahun Terbit</label><input type="number" name="tahun_terbit" class="form-control" value="<?= date('Y') ?>" min="1900" max="<?= date('Y') ?>"></div>
        </div>
        <div class="two-col">
          <div class="form-group"><label>Pengarang</label><input type="text" name="pengarang" class="form-control" required></div>
          <div class="form-group"><label>Penerbit</label><input type="text" name="penerbit" class="form-control"></div>
        </div>
        <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi_buku" class="form-control" rows="3"></textarea></div>
        <div class="two-col">
          <div class="form-group"><label>Status</label>
            <select name="status" class="form-control">
              <option value="tersedia">Tersedia</option>
              <option value="tidak">Tidak Tersedia</option>
            </select>
          </div>
          <div class="form-group"><label>Cover Buku</label><input type="file" name="gambar_buku" class="form-control" accept="image/*" onchange="previewImage(this,'add_preview')"></div>
        </div>
        <img id="add_preview" src="" style="display:none;max-width:100px;max-height:100px;border-radius:8px;margin-bottom:12px;">
        <button type="submit" class="btn btn-primary btn-block">Simpan Buku</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header"><h3>Edit Buku</h3><button class="modal-close" onclick="closeModal('editModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="act" value="edit">
        <input type="hidden" name="id" id="eb_id">
        <div class="form-group"><label>Judul Buku</label><input type="text" name="judul_buku" id="eb_judul" class="form-control" required></div>
        <div class="two-col">
          <div class="form-group"><label>Kategori</label>
            <select name="id_kategori" id="eb_kat" class="form-control">
              <?php $kategori_list->data_seek(0); while ($k = $kategori_list->fetch_assoc()): ?>
              <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
              <?php endwhile ?>
            </select>
          </div>
          <div class="form-group"><label>Tahun Terbit</label><input type="number" name="tahun_terbit" id="eb_tahun" class="form-control"></div>
        </div>
        <div class="two-col">
          <div class="form-group"><label>Pengarang</label><input type="text" name="pengarang" id="eb_pengarang" class="form-control" required></div>
          <div class="form-group"><label>Penerbit</label><input type="text" name="penerbit" id="eb_penerbit" class="form-control"></div>
        </div>
        <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi_buku" id="eb_deskripsi" class="form-control" rows="3"></textarea></div>
        <div class="two-col">
          <div class="form-group"><label>Status</label>
            <select name="status" id="eb_status" class="form-control">
              <option value="tersedia">Tersedia</option>
              <option value="tidak">Tidak Tersedia</option>
            </select>
          </div>
          <div class="form-group"><label>Cover Baru (opsional)</label><input type="file" name="gambar_buku" class="form-control" accept="image/*"></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Update Buku</button>
      </form>
    </div>
  </div>
</div>

<script>
function editBuku(b) {
  document.getElementById('eb_id').value = b.id;
  document.getElementById('eb_judul').value = b.judul;
  document.getElementById('eb_kat').value = b.id_kat;
  document.getElementById('eb_pengarang').value = b.pengarang;
  document.getElementById('eb_penerbit').value = b.penerbit;
  document.getElementById('eb_tahun').value = b.tahun;
  document.getElementById('eb_deskripsi').value = b.deskripsi;
  document.getElementById('eb_status').value = b.status;
  openModal('editModal');
}
</script>
