<?php
$db       = getDB();
$error    = $success = '';
$roleType = getRole();
$id       = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'update_profil') {
        if ($roleType === 'Anggota') {
            $nama  = trim($_POST['nama']  ?? '');
            $email = trim($_POST['email'] ?? '');
            $kelas = trim($_POST['kelas'] ?? '');

            if (isset($_FILES['foto']) && $_FILES['foto']['size'] > 0) {
                if (!is_dir(__DIR__ . '/../uploads')) mkdir(__DIR__ . '/../uploads', 0755, true);
                $ext   = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $fname = 'uploads/anggota_' . $id . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__ . '/../' . $fname);
                $s = $db->prepare("UPDATE anggota SET nama_anggota=?, email=?, kelas=?, foto=? WHERE id_anggota=?");
                $s->bind_param('ssssi', $nama, $email, $kelas, $fname, $id);
            } else {
                $s = $db->prepare("UPDATE anggota SET nama_anggota=?, email=?, kelas=? WHERE id_anggota=?");
                $s->bind_param('sssi', $nama, $email, $kelas, $id);
            }
            if ($s->execute()) { $_SESSION['nama'] = $nama; $success = 'Profil berhasil diperbarui.'; }
            else $error = 'Gagal memperbarui profil.';

        } else {
            $nama     = trim($_POST['nama']     ?? '');
            $username = trim($_POST['username'] ?? '');
            $s = $db->prepare("UPDATE pengguna SET nama_pengguna=?, username=? WHERE id_pengguna=?");
            $s->bind_param('ssi', $nama, $username, $id);
            if ($s->execute()) { $_SESSION['nama'] = $nama; $_SESSION['username'] = $username; $success = 'Profil berhasil diperbarui.'; }
            else $error = 'Gagal memperbarui profil.';
        }
    } elseif ($act === 'ganti_pw') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $u = $roleType === 'Anggota'
            ? $db->query("SELECT password FROM anggota WHERE id_anggota=$id")->fetch_assoc()
            : $db->query("SELECT password FROM pengguna WHERE id_pengguna=$id")->fetch_assoc();

        if (!($u && (password_verify($old,$u['password'])||$u['password']===$old))) $error = 'Password lama salah.';
        elseif (strlen($new) < 6) $error = 'Password baru minimal 6 karakter.';
        else {
            $h = password_hash($new, PASSWORD_DEFAULT);
            if ($roleType === 'Anggota') $db->query("UPDATE anggota SET password='$h' WHERE id_anggota=$id");
            else                         $db->query("UPDATE pengguna SET password='$h' WHERE id_pengguna=$id");
            $success = 'Password berhasil diubah.';
        }
    }
}

$user = $roleType === 'Anggota'
    ? $db->query("SELECT * FROM anggota WHERE id_anggota=$id")->fetch_assoc()
    : $db->query("SELECT * FROM pengguna WHERE id_pengguna=$id")->fetch_assoc();

$foto_src = '';
if ($roleType === 'Anggota' && !empty($user['foto']) && file_exists(__DIR__ . '/../' . $user['foto']))
    $foto_src = '../' . $user['foto'];

$total_pinjam = $roleType==='Anggota' ? $db->query("SELECT COUNT(*) as c FROM transaksi WHERE id_anggota=$id")->fetch_assoc()['c'] : 0;
$total_ulasan = $roleType==='Anggota' ? $db->query("SELECT COUNT(*) as c FROM ulasan_buku WHERE id_anggota=$id")->fetch_assoc()['c'] : 0;
?>

<style>
.profil-wrap { max-width: 860px; margin: 0 auto; }

/* Hero */
.ph-hero {
  background: linear-gradient(135deg,#0f1f3d 0%,#1a3a6b 60%,#2a5298 100%);
  border-radius: 20px; padding: 30px 34px;
  display: flex; align-items: center; gap: 24px;
  margin-bottom: 26px; position: relative; overflow: hidden;
}
.ph-hero::before {
  content:'';position:absolute;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

/* Avatar */
.ph-av { position:relative;flex-shrink:0;z-index:1;cursor:pointer; }
.ph-av-img { width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.35);box-shadow:0 8px 24px rgba(0,0,0,.3);display:block; }
.ph-av-ph  { width:96px;height:96px;border-radius:50%;background:rgba(255,255,255,.14);border:3px solid rgba(255,255,255,.28);display:flex;align-items:center;justify-content:center;font-size:38px; }
.ph-av-ov  { position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;font-size:22px;opacity:0;transition:opacity .2s; }
.ph-av:hover .ph-av-ov { opacity:1; }

.ph-info { z-index:1;flex:1; }
.ph-name { font-size:1.55rem;font-weight:800;color:#fff;line-height:1.2;margin-bottom:4px; }
.ph-role { font-size:11px;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px; }
.ph-stats { display:flex;gap:20px; }
.phs-n { font-size:1.35rem;font-weight:800;color:#fff;line-height:1; }
.phs-l { font-size:11px;color:rgba(255,255,255,.5);margin-top:2px; }
.phs-div { width:1px;background:rgba(255,255,255,.15);align-self:stretch; }

/* Cards */
.profil-grid { display:grid;grid-template-columns:1fr 1fr;gap:18px; }
.pcard { background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.07);overflow:hidden; }
.pcard-head { padding:18px 22px 0;display:flex;align-items:center;gap:8px; }
.pcard-head h3 { font-size:14px;font-weight:700;color:#1a2235;margin:0; }
.pcard-body { padding:16px 22px 22px; }

/* Alert */
.pal { padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:6px; }
.pal-ok  { background:#f0faf5;color:#1a7a4a;border:1px solid #b6e8ce; }
.pal-err { background:#fff2f1;color:#d93025;border:1px solid #ffd4d0; }

/* Fields */
.pf { margin-bottom:13px; }
.pf label { display:block;font-size:11px;font-weight:600;color:#9aa4b5;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px; }
.pf-inp { width:100%;padding:9px 12px;border:1.5px solid #e3e8f0;border-radius:10px;font-family:inherit;font-size:13px;color:#2d3748;background:#f8fafc;outline:none;transition:border-color .18s,background .18s; }
.pf-inp:focus { border-color:#0f1f3d;background:#fff; }
.pf-inp:disabled { background:#f0f2f6;color:#9aa4b5;cursor:not-allowed; }

.file-lbl { display:flex;align-items:center;gap:8px;padding:9px 12px;border:1.5px dashed #c8d4e8;border-radius:10px;cursor:pointer;background:#f8fafc;font-size:13px;color:#6b7585;transition:border-color .18s,background .18s; }
.file-lbl:hover { border-color:#0f1f3d;background:#f0f4ff; }
#foto_preview_box { display:none;margin-top:10px;text-align:center; }
#foto_preview_box img { width:76px;height:76px;border-radius:50%;object-fit:cover;border:3px solid #e3e8f0; }

.btn-save { width:100%;padding:10px;background:#0f1f3d;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:background .18s,transform .13s;margin-top:4px; }
.btn-save:hover { background:#162d52;transform:translateY(-1px); }
.btn-pw { width:100%;padding:10px;background:#fff3e0;color:#e65100;border:1px solid #ffe0b2;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:background .18s;margin-top:4px; }
.btn-pw:hover { background:#ffe0b2; }

#fotoInput { display:none; }

@media(max-width:640px){
  .profil-grid{grid-template-columns:1fr}
  .ph-hero{flex-direction:column;text-align:center;padding:22px 18px}
  .ph-stats{justify-content:center}
}
</style>

<div class="profil-wrap">

  <?php if($error): ?><div class="pal pal-err">⚠️ <?= htmlspecialchars($error) ?></div><?php endif ?>
  <?php if($success): ?><div class="pal pal-ok">✅ <?= htmlspecialchars($success) ?></div><?php endif ?>

  <!-- Hero -->
  <div class="ph-hero">
    <?php if($roleType==='Anggota'): ?>
    <div class="ph-av" onclick="document.getElementById('fotoInput').click()">
      <?php if($foto_src): ?>
        <img src="<?= htmlspecialchars($foto_src) ?>" class="ph-av-img" id="heroAv" alt="foto">
      <?php else: ?>
        <div class="ph-av-ph" id="heroAv">👤</div>
      <?php endif ?>
      <div class="ph-av-ov">📷</div>
    </div>
    <?php endif ?>

    <div class="ph-info">
      <div class="ph-name"><?= htmlspecialchars($roleType==='Anggota'?$user['nama_anggota']:$user['nama_pengguna']) ?></div>
      <div class="ph-role"><?php if($roleType==='Anggota'): ?><?= htmlspecialchars($user['kelas'])?> · Anggota<?php else: ?><?= htmlspecialchars($user['level'])?><?php endif ?></div>
      <?php if($roleType==='Anggota'): ?>
      <div class="ph-stats">
        <div><div class="phs-n"><?= $total_pinjam ?></div><div class="phs-l">Dipinjam</div></div>
        <div class="phs-div"></div>
        <div><div class="phs-n"><?= $total_ulasan ?></div><div class="phs-l">Ulasan</div></div>
      </div>
      <?php endif ?>
    </div>
  </div>

  <!-- Grid -->
  <div class="profil-grid">

    <!-- Info Profil -->
    <div class="pcard">
      <div class="pcard-head"><h3>👤 Informasi Profil</h3></div>
      <div class="pcard-body">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="act" value="update_profil">
          <input type="file" name="foto" id="fotoInput" accept="image/*" onchange="previewFoto(this)">

          <?php if($roleType==='Anggota'): ?>
            <div class="pf"><label>NIS</label><input type="text" class="pf-inp" value="<?= htmlspecialchars($user['nis']) ?>" disabled></div>
            <div class="pf"><label>Nama Lengkap</label><input type="text" name="nama" class="pf-inp" value="<?= htmlspecialchars($user['nama_anggota']) ?>" required></div>
            <div class="pf"><label>Email</label><input type="email" name="email" class="pf-inp" value="<?= htmlspecialchars($user['email']) ?>" required></div>
            <div class="pf"><label>Kelas</label><input type="text" name="kelas" class="pf-inp" value="<?= htmlspecialchars($user['kelas']) ?>"></div>
            <div class="pf">
              <label>Foto Profil</label>
              <label for="fotoInput" class="file-lbl">📷 <span id="fnTxt">Klik untuk pilih foto...</span></label>
              <div id="foto_preview_box"><img id="foto_preview" src="" alt="preview"><div style="font-size:11px;color:#6b7585;margin-top:4px">Preview</div></div>
            </div>
          <?php else: ?>
            <div class="pf"><label>Username</label><input type="text" name="username" class="pf-inp" value="<?= htmlspecialchars($user['username']) ?>" required></div>
            <div class="pf"><label>Nama Lengkap</label><input type="text" name="nama" class="pf-inp" value="<?= htmlspecialchars($user['nama_pengguna']) ?>" required></div>
            <div class="pf"><label>Level</label><input type="text" class="pf-inp" value="<?= htmlspecialchars($user['level']) ?>" disabled></div>
          <?php endif ?>

          <button type="submit" class="btn-save">💾 Simpan Perubahan</button>
        </form>
      </div>
    </div>

    <!-- Ganti Password -->
    <div class="pcard">
      <div class="pcard-head"><h3>🔑 Ganti Password</h3></div>
      <div class="pcard-body">
        <form method="POST">
          <input type="hidden" name="act" value="ganti_pw">
          <div class="pf"><label>Password Lama</label><input type="password" name="old_password" class="pf-inp" placeholder="Masukkan password lama" required></div>
          <div class="pf"><label>Password Baru</label><input type="password" name="new_password" class="pf-inp" placeholder="Min. 6 karakter" required></div>
          <div class="pf"><label>Konfirmasi Password Baru</label><input type="password" id="konfPw" class="pf-inp" placeholder="Ulangi password baru"></div>
          <button type="submit" class="btn-pw" onclick="return cekPw()">🔒 Ganti Password</button>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
function previewFoto(input) {
  if (!input.files || !input.files[0]) return;
  document.getElementById('fnTxt').textContent = input.files[0].name;
  const r = new FileReader();
  r.onload = e => {
    document.getElementById('foto_preview').src = e.target.result;
    document.getElementById('foto_preview_box').style.display = 'block';
    const av = document.getElementById('heroAv');
    if (av) {
      if (av.tagName === 'IMG') { av.src = e.target.result; }
      else {
        const img = document.createElement('img');
        img.src = e.target.result; img.className = 'ph-av-img'; img.id = 'heroAv'; img.alt = 'foto';
        av.replaceWith(img);
      }
    }
  };
  r.readAsDataURL(input.files[0]);
}
function cekPw() {
  const np = document.querySelector('input[name="new_password"]').value;
  const kp = document.getElementById('konfPw').value;
  if (np !== kp) { alert('Password baru dan konfirmasi tidak sama!'); return false; }
  return true;
}
</script>