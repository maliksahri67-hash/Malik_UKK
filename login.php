<?php
define('BASE_URL', '');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

// Jika sudah login, redirect ke index (bukan dashboard)
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error   = '';
$success = '';

if (!empty($_GET['registered'])) {
    $success = 'Registrasi berhasil! Silahkan login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db       = getDB();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Cek di tabel pengguna (Admin / Petugas)
    $stmt = $db->prepare("SELECT * FROM pengguna WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && (password_verify($password, $user['password']) || $user['password'] === $password)) {
        $_SESSION['user_id']  = $user['id_pengguna'];
        $_SESSION['nama']     = $user['nama_pengguna'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['level'];
        // Redirect ke INDEX, bukan dashboard
        header("Location: index.php");
        exit;
    }

    // Cek di tabel anggota pakai NIS atau Email
    $stmt = $db->prepare("SELECT * FROM anggota WHERE nis = ? OR email = ? LIMIT 1");
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $anggota = $stmt->get_result()->fetch_assoc();

    if ($anggota && (password_verify($password, $anggota['password']) || $anggota['password'] === $password)) {
        $_SESSION['user_id'] = $anggota['id_anggota'];
        $_SESSION['nama']    = $anggota['nama_anggota'];
        $_SESSION['nis']     = $anggota['nis'];
        $_SESSION['kelas']   = $anggota['kelas'];
        $_SESSION['role']    = 'Anggota';
        // Redirect ke INDEX, bukan dashboard
        header("Location: index.php");
        exit;
    }

    $error = 'Username/NIS/Email atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — Sistem Perpustakaan Digital Terpadu</title>
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
  --green:     #1a7a4a;
  --r:         10px;
}

html, body { height: 100%; font-family: 'DM Sans', sans-serif; color: var(--gray-700); }

.page {
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  padding: 1.5rem;
  background: linear-gradient(140deg, #dce4f0 0%, #c8d5e9 100%);
}

.card {
  display: flex;
  width: 100%; max-width: 880px; min-height: 540px;
  border-radius: 22px; overflow: hidden;
  background: var(--white);
  box-shadow: 0 24px 64px rgba(0,0,0,0.13), 0 4px 16px rgba(0,0,0,0.07);
  animation: rise .45s cubic-bezier(.22,.68,0,1.15) both;
}
@keyframes rise {
  from { opacity:0; transform:translateY(20px) scale(.98); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}

.form-panel {
  flex: 0 0 50%;
  padding: 3rem 3.2rem 2.5rem;
  display: flex; flex-direction: column;
}

.book-icon {
  width: 50px; height: 50px;
  background: var(--gray-100);
  border-radius: 13px;
  border: 1.5px solid var(--gray-200);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 1.5rem;
}
.book-icon svg { width: 22px; height: 22px; color: var(--navy); }

h1 {
  font-family: 'Playfair Display', serif;
  font-size: 1.9rem; font-weight: 700;
  color: var(--gray-700); line-height: 1.2;
  margin-bottom: .3rem;
}
.sub {
  font-size: .82rem; color: var(--gray-400);
  margin-bottom: 1.8rem;
}

.alert {
  display: flex; align-items: center; gap: .5rem;
  padding: .65rem .9rem;
  border-radius: var(--r);
  font-size: .82rem; margin-bottom: 1rem;
}
.alert-err { background:#fff2f1; color:var(--red);   border:1px solid #ffd4d0; }
.alert-ok  { background:#f0faf5; color:var(--green); border:1px solid #b6e8ce; }

.field { margin-bottom: .9rem; }
.field label {
  display:block; font-size:.78rem; font-weight:500;
  color:var(--gray-500); margin-bottom:.35rem; letter-spacing:.01em;
}
.inp {
  width:100%; padding:.7rem .9rem;
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
/* Hide browser password eye */
input::-ms-reveal,
input::-ms-clear {
  display: none;
}

input::-webkit-contacts-auto-fill-button,
input::-webkit-credentials-auto-fill-button {
  display: none !important;
}
.pw { position:relative; }
.pw .inp { padding-right:2.8rem; }
.eye {
  position:absolute; right:.85rem; top:50%; transform:translateY(-50%);
  background:none; border:none; cursor:pointer;
  color:var(--gray-400); display:flex; align-items:center; padding:0;
  transition:color .18s;
}
.eye:hover { color:var(--gray-500); }

.btn {
  width:100%; margin-top:.4rem; padding:.78rem;
  background:var(--navy); color:var(--white);
  border:none; border-radius:var(--r);
  font-family:'DM Sans',sans-serif; font-size:.9rem; font-weight:600;
  cursor:pointer; letter-spacing:.02em;
  box-shadow:0 4px 14px rgba(15,31,61,.28);
  transition:background .18s, transform .13s, box-shadow .18s;
}
.btn:hover  { background:var(--navy-h); transform:translateY(-1px); box-shadow:0 6px 20px rgba(15,31,61,.35); }
.btn:active { transform:translateY(0); }

.foot {
  margin-top:auto; padding-top:1.6rem;
  text-align:center; font-size:.82rem; color:var(--gray-400);
}
.foot a { color:var(--navy); font-weight:600; text-decoration:none; }
.foot a:hover { text-decoration:underline; }

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
  animation-delay: var(--dl, 0s);
  opacity:var(--o,.6);
}
@keyframes twinkle {
  from { opacity:var(--o,.6); }
  to   { opacity:calc(var(--o,.6) * .25); }
}

.mountain { position:absolute; bottom:0; left:0; right:0; z-index:2; }

.quote-box {
  position:absolute; bottom:2.8rem; left:2.2rem; right:2.2rem;
  z-index:3; color:#fff;
}
.quote-txt {
  font-family:'Playfair Display',serif;
  font-size:1.5rem; font-weight:700; line-height:1.42;
  margin-bottom:.65rem;
  text-shadow:0 2px 18px rgba(0,0,0,.45);
}
.quote-sub { font-size:.82rem; color:rgba(255,255,255,.55); font-weight:300; }

@media (max-width:660px) {
  .visual      { display:none; }
  .form-panel  { flex:1; padding:2rem 1.5rem; }
}
</style>
</head>
<body>
<div class="page">
  <div class="card">

    <div class="form-panel">
      <div class="book-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
          <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
        </svg>
      </div>

      <h1>Masuk ke Akun</h1>
      <p class="sub">Sistem Perpustakaan Digital Terpadu</p>

      <?php if ($error): ?>
      <div class="alert alert-err">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif ?>

      <?php if ($success): ?>
      <div class="alert alert-ok">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        <?= htmlspecialchars($success) ?>
      </div>
      <?php endif ?>

      <form method="POST" autocomplete="on">
        <div class="field">
          <label>Username / NIS / Email</label>
          <input type="text" name="username" class="inp"
            placeholder="Masukkan username, NIS, atau email"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            autocomplete="username" required>
        </div>

        <div class="field">
          <label>Password</label>
          <div class="pw">
            <input type="password" name="password" id="pw1" class="inp"
              placeholder="Masukkan password" autocomplete="current-password" required>
            <button type="button" class="eye" onclick="togglePw('pw1',this)">
              <?= eyeIcon() ?>
            </button>
          </div>
        </div>

        <button type="submit" class="btn">Masuk</button>
      </form>

      <p class="foot">
        Belum punya akun? <a href="registrasi.php">Daftar Sekarang</a>
      </p>
    </div>

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
        <p class="quote-txt">"Membaca adalah nafas hidup dan jembatan masa depan."</p>
        <p class="quote-sub">Jelajahi ribuan koleksi buku digital kami.</p>
      </div>
    </div>

  </div>
</div>

<?php
function eyeIcon() {
  return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}
?>

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
    ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
    : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
}
</script>
</body>
</html>