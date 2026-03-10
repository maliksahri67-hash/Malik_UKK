<?php
define('BASE_URL', '');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

if (isLoggedIn()) {
    $r = strtolower(getRole());
    header("Location: {$r}/dashboard.php");
    exit;
}

$error   = '';
$success = '';
$old     = []; // sticky values

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db    = getDB();
    $old   = $_POST;

    // Tabel anggota: nis, nama_anggota, email, password, kelas, foto
    $nis   = trim($_POST['nis']          ?? '');
    $nama  = trim($_POST['nama_anggota'] ?? '');
    $email = trim($_POST['email']        ?? '');
    $kelas = trim($_POST['kelas']        ?? '');
    $pass  = $_POST['password']          ?? '';
    $pass2 = $_POST['password2']         ?? '';

    if (!$nis || !$nama || !$email || !$kelas || !$pass) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($pass !== $pass2) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $check = $db->prepare("SELECT id_anggota FROM anggota WHERE nis = ? OR email = ?");
        $check->bind_param('ss', $nis, $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'NIS atau email sudah terdaftar.';
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $foto   = ''; // default kosong sesuai skema
            $ins    = $db->prepare("INSERT INTO anggota (nis, nama_anggota, email, password, kelas, foto) VALUES (?,?,?,?,?,?)");
            $ins->bind_param('ssssss', $nis, $nama, $email, $hashed, $kelas, $foto);

            if ($ins->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = 'Gagal mendaftar. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Akun — Sistem Perpustakaan Digital Terpadu</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --navy:      #0f1f3d;
  --navy-h:    #162d52;
  --white:     #ffffff;
  --gray-100:  #f0f2f6;
  --gray-200:  #e3e8f0;
  --gray-400:  #9aa4b5;
  --gray-500:  #6b7585;
  --gray-700:  #3a4254;
  --red:       #d93025;
  --r:         10px;
}

html, body { height: 100%; font-family: 'DM Sans', sans-serif; color: var(--gray-700); }

.page {
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  padding: 1.5rem;
  background: linear-gradient(140deg, #dce4f0 0%, #c8d5e9 100%);
}

/* CARD — wider for register form */
.card {
  display: flex;
  width: 100%; max-width: 920px; min-height: 560px;
  border-radius: 22px; overflow: hidden;
  background: var(--white);
  box-shadow: 0 24px 64px rgba(0,0,0,0.13), 0 4px 16px rgba(0,0,0,0.07);
  animation: rise .45s cubic-bezier(.22,.68,0,1.15) both;
}
@keyframes rise {
  from { opacity:0; transform:translateY(20px) scale(.98); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}

/* ── LEFT: FORM ── */
.form-panel {
  flex: 0 0 55%;
  padding: 2.6rem 3rem 2.2rem;
  display: flex; flex-direction: column;
  overflow-y: auto;
}

.book-icon {
  width: 48px; height: 48px;
  background: var(--gray-100);
  border-radius: 13px;
  border: 1.5px solid var(--gray-200);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 1.2rem;
}
.book-icon svg { width: 20px; height: 20px; color: var(--navy); }

h1 {
  font-family: 'Playfair Display', serif;
  font-size: 1.75rem; font-weight: 700;
  color: var(--gray-700); line-height: 1.2;
  margin-bottom: .28rem;
}
.sub { font-size: .8rem; color: var(--gray-400); margin-bottom: 1.5rem; }

/* Alert */
.alert {
  display: flex; align-items: center; gap: .45rem;
  padding: .6rem .85rem;
  border-radius: var(--r);
  font-size: .81rem; margin-bottom: .9rem;
  background:#fff2f1; color:var(--red); border:1px solid #ffd4d0;
}

/* Two-column grid */
.two { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }

/* Field */
.field { margin-bottom: .8rem; }
.field label {
  display:block; font-size:.77rem; font-weight:500;
  color:var(--gray-500); margin-bottom:.32rem; letter-spacing:.01em;
}
.inp {
  width:100%; padding:.68rem .9rem;
  border:1.5px solid var(--gray-200);
  border-radius:var(--r);
  background:var(--gray-100);
  font-family:'DM Sans',sans-serif; font-size:.875rem; color:var(--gray-700);
  outline:none; appearance:none;
  transition:border-color .18s, box-shadow .18s, background .18s;
}
.inp::placeholder { color:var(--gray-400); }
.inp:focus {
  border-color:var(--navy);
  background:var(--white);
  box-shadow:0 0 0 3px rgba(15,31,61,.09);
}

/* Password wrapper */
.pw { position:relative; }
.pw .inp { padding-right:2.8rem; }
.eye {
  position:absolute; right:.85rem; top:50%; transform:translateY(-50%);
  background:none; border:none; cursor:pointer;
  color:var(--gray-400); display:flex; align-items:center; padding:0;
  transition:color .18s;
}
.eye:hover { color:var(--gray-500); }

/* Submit */
.btn {
  width:100%; margin-top:.5rem; padding:.78rem;
  background:var(--navy); color:var(--white);
  border:none; border-radius:var(--r);
  font-family:'DM Sans',sans-serif; font-size:.9rem; font-weight:600;
  cursor:pointer; letter-spacing:.02em;
  box-shadow:0 4px 14px rgba(15,31,61,.28);
  transition:background .18s, transform .13s, box-shadow .18s;
}
.btn:hover  { background:var(--navy-h); transform:translateY(-1px); box-shadow:0 6px 20px rgba(15,31,61,.35); }
.btn:active { transform:translateY(0); }

/* Footer */
.foot {
  margin-top:auto; padding-top:1.4rem;
  text-align:center; font-size:.81rem; color:var(--gray-400);
}
.foot a { color:var(--navy); font-weight:600; text-decoration:none; }
.foot a:hover { text-decoration:underline; }

/* ── RIGHT: VISUAL ── */
.visual {
  flex:1; position:relative; overflow:hidden;
  background:#091525;
}
.visual::after {
  content:''; position:absolute; inset:0; z-index:1;
  background:
    radial-gradient(ellipse 80% 55% at 50% 5%,  rgba(25,65,140,.6) 0%, transparent 65%),
    radial-gradient(ellipse 55% 40% at 15% 55%, rgba(8,25,70,.45) 0%, transparent 60%);
}

#stars { position:absolute; inset:0; z-index:0; }
#stars span {
  position:absolute; border-radius:50%; background:#fff;
  animation:twinkle var(--d,3s) ease-in-out infinite alternate;
  animation-delay:var(--dl,0s); opacity:var(--o,.6);
}
@keyframes twinkle {
  from { opacity:var(--o,.6); }
  to   { opacity:calc(var(--o,.6) * .25); }
}

.mountain { position:absolute; bottom:0; left:0; right:0; z-index:2; }

.quote-box {
  position:absolute; bottom:2.8rem; left:2rem; right:2rem;
  z-index:3; color:#fff;
}
.quote-txt {
  font-family:'Playfair Display',serif;
  font-size:1.4rem; font-weight:700; line-height:1.42;
  margin-bottom:.6rem; text-shadow:0 2px 18px rgba(0,0,0,.45);
}
.quote-sub { font-size:.8rem; color:rgba(255,255,255,.55); font-weight:300; }

@media (max-width:700px) {
  .visual     { display:none; }
  .form-panel { flex:1; padding:1.8rem 1.4rem; }
  .two        { grid-template-columns:1fr; }
}
</style>
</head>
<body>
<div class="page">
  <div class="card">

    <!-- ══ LEFT: FORM ══ -->
    <div class="form-panel">

      <div class="book-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
          <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
        </svg>
      </div>

      <h1>Daftar Akun</h1>
      <p class="sub">Daftar sebagai Anggota Siswa Perpustakaan</p>

      <?php if ($error): ?>
      <div class="alert">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif ?>

      <form method="POST" autocomplete="on">

        <!-- NIS + Kelas -->
        <div class="two">
          <div class="field">
            <label>NIS <span style="color:var(--red)">*</span></label>
            <input type="text" name="nis" class="inp"
              placeholder="Nomor Induk Siswa"
              value="<?= htmlspecialchars($old['nis'] ?? '') ?>" required>
          </div>
          <div class="field">
            <label>Kelas <span style="color:var(--red)">*</span></label>
            <input type="text" name="kelas" class="inp"
              placeholder="cth. XI PPLG 1"
              value="<?= htmlspecialchars($old['kelas'] ?? '') ?>" required>
          </div>
        </div>

        <!-- Nama -->
        <div class="field">
          <label>Nama Lengkap <span style="color:var(--red)">*</span></label>
          <input type="text" name="nama_anggota" class="inp"
            placeholder="Nama lengkap sesuai data sekolah"
            value="<?= htmlspecialchars($old['nama_anggota'] ?? '') ?>" required>
        </div>

        <!-- Email -->
        <div class="field">
          <label>Email <span style="color:var(--red)">*</span></label>
          <input type="email" name="email" class="inp"
            placeholder="email@contoh.com"
            value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
        </div>

        <!-- Password + Konfirmasi -->
        <div class="two">
          <div class="field">
            <label>Password <span style="color:var(--red)">*</span></label>
            <div class="pw">
              <input type="password" name="password" id="pw1" class="inp"
                placeholder="Min. 6 karakter" required>
              <button type="button" class="eye" onclick="togglePw('pw1',this)">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
          </div>
          <div class="field">
            <label>Konfirmasi Password <span style="color:var(--red)">*</span></label>
            <div class="pw">
              <input type="password" name="password2" id="pw2" class="inp"
                placeholder="Ulangi password" required>
              <button type="button" class="eye" onclick="togglePw('pw2',this)">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <button type="submit" class="btn">Daftar Sekarang</button>
      </form>

      <p class="foot">
        Sudah punya akun? <a href="login.php">Masuk di sini</a>
      </p>
    </div>

    <!-- ══ RIGHT: VISUAL ══ -->
    <div class="visual">
      <div id="stars"></div>

      <svg class="mountain" viewBox="0 0 500 340" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMax slice">
        <defs>
          <linearGradient id="mg1" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#1b3d7a" stop-opacity=".8"/>
            <stop offset="100%" stop-color="#05101f"/>
          </linearGradient>
          <linearGradient id="mg2" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#0c1e40" stop-opacity=".9"/>
            <stop offset="100%" stop-color="#020a14"/>
          </linearGradient>
          <linearGradient id="mg3" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#06132a"/>
            <stop offset="100%" stop-color="#010608"/>
          </linearGradient>
        </defs>
        <path d="M-10 340 L55 165 L115 205 L195 85 L275 178 L345 108 L425 198 L485 128 L510 340Z" fill="url(#mg1)"/>
        <path d="M-10 340 L45 258 L95 292 L168 198 L245 268 L315 188 L395 272 L455 218 L510 252 L510 340Z" fill="url(#mg2)"/>
        <path d="M-10 340 L65 308 L135 322 L205 294 L285 318 L365 298 L445 320 L510 304 L510 340Z" fill="url(#mg3)"/>
      </svg>

      <div class="quote-box">
        <p class="quote-txt">"Ilmu adalah cahaya yang menerangi jalan kehidupan."</p>
        <p class="quote-sub">Bergabunglah dan mulai perjalanan literasimu bersama kami.</p>
      </div>
    </div>

  </div>
</div>

<script>
(function(){
  const c = document.getElementById('stars');
  for(let i=0;i<90;i++){
    const s = document.createElement('span');
    const sz = (Math.random()*1.8+.4).toFixed(1);
    s.style.cssText = `width:${sz}px;height:${sz}px;top:${(Math.random()*100).toFixed(1)}%;left:${(Math.random()*100).toFixed(1)}%;--o:${(Math.random()*.65+.25).toFixed(2)};--d:${(Math.random()*4+2).toFixed(1)}s;--dl:${(Math.random()*5).toFixed(1)}s`;
    c.appendChild(s);
  }
})();

function togglePw(id, btn){
  const inp = document.getElementById(id);
  const show = inp.type==='password';
  inp.type = show ? 'text' : 'password';
  btn.innerHTML = show
    ? `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
    : `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
}
</script>
</body>
</html>