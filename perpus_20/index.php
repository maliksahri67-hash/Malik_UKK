<?php
define('BASE_URL', '');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';

$logged_in = isLoggedIn();
$role      = $logged_in ? strtolower(getRole()) : '';
$nama      = $logged_in ? $_SESSION['nama'] : '';
$dashboard = $role ? $role . '/dashboard.php' : 'login.php';

$db = getDB();
$total_buku    = $db->query("SELECT COUNT(*) as c FROM buku")->fetch_assoc()['c'];
$total_anggota = $db->query("SELECT COUNT(*) as c FROM anggota")->fetch_assoc()['c'];
$total_kat     = $db->query("SELECT COUNT(*) as c FROM kategori")->fetch_assoc()['c'];
$buku_tersedia = $db->query("SELECT COUNT(*) as c FROM buku WHERE status='tersedia'")->fetch_assoc()['c'];
$showcase      = $db->query("SELECT b.*, k.nama_kategori FROM buku b JOIN kategori k ON b.id_kategori=k.id_kategori ORDER BY b.id_buku DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perpus Digital — Perpustakaan Modern</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Patrick+Hand&display=swap" rel="stylesheet">
<style>
:root{
  --cream:#fef9f3;
  --cream-dark:#f5e6d3;
  --orange:#ff8c42;
  --orange-light:#ffb088;
  --teal:#2ec4b6;
  --teal-light:#7fdbd4;
  --purple:#9b5de5;
  --purple-light:#c77dff;
  --pink:#f15bb5;
  --yellow:#fee440;
  --dark:#2d3436;
  --gray:#636e72;
  --white:#ffffff;
  --shadow:0 10px 40px -10px rgba(0,0,0,0.1);
  --shadow-hover:0 20px 60px -15px rgba(0,0,0,0.15);
  --radius:24px;
  --radius-sm:16px;
  --radius-lg:32px;
}
*{
  margin:0;
  padding:0;
  box-sizing:border-box;
}
html{
  scroll-behavior:smooth;
}
body{
  font-family:'Outfit',sans-serif;
  background:var(--cream);
  color:var(--dark);
  line-height:1.6;
  overflow-x:hidden;
}
.handwritten{
  font-family:'Patrick Hand',cursive;
}

/* Blobs */
.blob{
  position:absolute;
  border-radius:50%;
  filter:blur(60px);
  opacity:0.4;
  z-index:0;
}
.blob-1{
  width:500px;
  height:500px;
  background:var(--orange-light);
  top:-200px;
  right:-100px;
}
.blob-2{
  width:400px;
  height:400px;
  background:var(--teal-light);
  bottom:10%;
  left:-150px;
}
.blob-3{
  width:300px;
  height:300px;
  background:var(--purple-light);
  top:40%;
  right:10%;
}

/* Navigation */
.nav{
  position:fixed;
  top:20px;
  left:50%;
  transform:translateX(-50%);
  z-index:100;
  background:rgba(255,255,255,0.8);
  backdrop-filter:blur(20px);
  border-radius:100px;
  padding:0.75rem 1.5rem;
  box-shadow:var(--shadow);
  border:2px solid rgba(255,255,255,0.5);
}
.nav-inner{
  display:flex;
  align-items:center;
  gap:2rem;
}
.nav-logo{
  display:flex;
  align-items:center;
  gap:0.75rem;
  text-decoration:none;
}
.logo-box{
  width:40px;
  height:40px;
  background:linear-gradient(135deg,var(--orange),var(--pink));
  border-radius:12px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:1.25rem;
  transform:rotate(-5deg);
  transition:transform 0.3s;
}
.nav-logo:hover .logo-box{
  transform:rotate(5deg) scale(1.1);
}
.logo-text{
  font-weight:700;
  font-size:1.25rem;
  color:var(--dark);
}
.nav-links{
  display:flex;
  align-items:center;
  gap:1.5rem;
}
.nav-link{
  font-size:0.9375rem;
  font-weight:500;
  color:var(--gray);
  text-decoration:none;
  padding:0.5rem 1rem;
  border-radius:100px;
  transition:all 0.2s;
}
.nav-link:hover{
  background:var(--cream-dark);
  color:var(--dark);
}
.nav-cta{
  display:flex;
  align-items:center;
  gap:0.75rem;
}
.btn{
  padding:0.75rem 1.5rem;
  border-radius:100px;
  font-size:0.9375rem;
  font-weight:600;
  text-decoration:none;
  transition:all 0.3s;
  border:none;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  gap:0.5rem;
}
.btn-outline{
  background:transparent;
  color:var(--dark);
  border:2px solid var(--cream-dark);
}
.btn-outline:hover{
  background:var(--cream-dark);
  border-color:var(--orange);
}
.btn-fill{
  background:var(--dark);
  color:var(--white);
  box-shadow:0 4px 15px rgba(0,0,0,0.2);
}
.btn-fill:hover{
  transform:translateY(-2px);
  box-shadow:0 8px 25px rgba(0,0,0,0.3);
}
.btn-orange{
  background:var(--orange);
  color:var(--white);
}
.btn-orange:hover{
  background:var(--orange-light);
  color:var(--dark);
}
.user-badge{
  display:flex;
  align-items:center;
  gap:0.75rem;
  padding:0.5rem 1rem 0.5rem 0.5rem;
  background:var(--white);
  border-radius:100px;
  border:2px solid var(--cream-dark);
}
.user-avatar{
  width:36px;
  height:36px;
  background:linear-gradient(135deg,var(--teal),var(--purple));
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:0.875rem;
  color:var(--white);
  font-weight:600;
}

/* Hero */
.hero{
  min-height:100vh;
  display:flex;
  align-items:center;
  padding:8rem 2rem 4rem;
  position:relative;
  overflow:hidden;
}
.hero-inner{
  max-width:1200px;
  margin:0 auto;
  width:100%;
  display:grid;
  grid-template-columns:1.2fr 1fr;
  gap:4rem;
  align-items:center;
  position:relative;
  z-index:1;
}
.hero-badge{
  display:inline-flex;
  align-items:center;
  gap:0.5rem;
  padding:0.5rem 1rem;
  background:var(--white);
  border-radius:100px;
  font-size:0.875rem;
  font-weight:600;
  color:var(--orange);
  margin-bottom:1.5rem;
  box-shadow:0 4px 15px rgba(255,140,66,0.2);
  width:fit-content;
}
.hero-badge::before{
  content:'✨';
}
.hero-title{
  font-size:clamp(2.5rem,5vw,4rem);
  font-weight:800;
  line-height:1.1;
  margin-bottom:1.5rem;
  letter-spacing:-0.02em;
}
.hero-title .highlight{
  position:relative;
  display:inline-block;
}
.hero-title .highlight::after{
  content:'';
  position:absolute;
  bottom:5px;
  left:-5px;
  right:-5px;
  height:12px;
  background:var(--yellow);
  z-index:-1;
  transform:rotate(-2deg);
  border-radius:4px;
}
.hero-desc{
  font-size:1.125rem;
  color:var(--gray);
  margin-bottom:2rem;
  max-width:480px;
}
.hero-buttons{
  display:flex;
  gap:1rem;
  margin-bottom:3rem;
  flex-wrap:wrap;
}
.btn-lg{
  padding:1rem 2rem;
  font-size:1rem;
}
.trust-pills{
  display:flex;
  gap:1rem;
  flex-wrap:wrap;
}
.trust-pill{
  display:flex;
  align-items:center;
  gap:0.5rem;
  padding:0.5rem 1rem;
  background:var(--white);
  border-radius:100px;
  font-size:0.875rem;
  font-weight:500;
  box-shadow:0 2px 10px rgba(0,0,0,0.05);
}
.trust-pill .emoji{
  font-size:1.25rem;
}

/* Hero Visual */
.hero-visual{
  position:relative;
  height:500px;
}
.floating-stack{
  position:relative;
  width:100%;
  height:100%;
}
.book-card-3d{
  position:absolute;
  background:var(--white);
  border-radius:var(--radius);
  padding:1rem;
  box-shadow:var(--shadow);
  transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1);
  cursor:pointer;
}
.book-card-3d:hover{
  transform:scale(1.05) rotate(0deg) !important;
  z-index:10 !important;
  box-shadow:var(--shadow-hover);
}
.book-card-3d:nth-child(1){
  width:200px;
  top:10%;
  left:10%;
  transform:rotate(-8deg);
  z-index:3;
}
.book-card-3d:nth-child(2){
  width:180px;
  top:30%;
  right:10%;
  transform:rotate(6deg);
  z-index:2;
}
.book-card-3d:nth-child(3){
  width:160px;
  bottom:15%;
  left:20%;
  transform:rotate(-4deg);
  z-index:1;
}
.book-thumb{
  width:100%;
  aspect-ratio:3/4;
  background:linear-gradient(135deg,var(--cream-dark),var(--cream));
  border-radius:var(--radius-sm);
  margin-bottom:0.75rem;
  overflow:hidden;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:3rem;
}
.book-thumb img{
  width:100%;
  height:100%;
  object-fit:cover;
}
.book-mini-title{
  font-weight:600;
  font-size:0.875rem;
  margin-bottom:0.25rem;
}
.book-mini-author{
  font-size:0.75rem;
  color:var(--gray);
}
.floating-icon{
  position:absolute;
  width:60px;
  height:60px;
  background:var(--white);
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:1.5rem;
  box-shadow:var(--shadow);
  animation:float 3s ease-in-out infinite;
}
.floating-icon:nth-child(4){
  top:5%;
  right:20%;
  animation-delay:0s;
}
.floating-icon:nth-child(5){
  bottom:20%;
  right:5%;
  animation-delay:1s;
}
.floating-icon:nth-child(6){
  top:50%;
  left:-5%;
  animation-delay:2s;
}
@keyframes float{
  0%,100%{transform:translateY(0)}
  50%{transform:translateY(-20px)}
}

/* Wave Divider */
.wave{
  position:absolute;
  bottom:0;
  left:0;
  width:100%;
  overflow:hidden;
  line-height:0;
}
.wave svg{
  position:relative;
  display:block;
  width:calc(100% + 1.3px);
  height:120px;
}

/* Stats Section */
.stats-section{
  background:var(--white);
  padding:4rem 2rem;
  position:relative;
}
.stats-inner{
  max-width:1000px;
  margin:0 auto;
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:2rem;
}
.stat-box{
  text-align:center;
  padding:2rem;
  background:var(--cream);
  border-radius:var(--radius);
  transition:transform 0.3s;
}
.stat-box:hover{
  transform:translateY(-5px);
}
.stat-number{
  font-size:3rem;
  font-weight:800;
  background:linear-gradient(135deg,var(--orange),var(--pink));
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  margin-bottom:0.5rem;
}
.stat-label{
  font-size:0.9375rem;
  color:var(--gray);
  font-weight:500;
}

/* Features */
.section{
  padding:6rem 2rem;
  position:relative;
}
.section-inner{
  max-width:1200px;
  margin:0 auto;
}
.section-header{
  text-align:center;
  margin-bottom:4rem;
}
.section-label{
  display:inline-block;
  padding:0.5rem 1.5rem;
  background:var(--white);
  border-radius:100px;
  font-size:0.875rem;
  font-weight:600;
  color:var(--teal);
  margin-bottom:1rem;
  box-shadow:0 4px 15px rgba(46,196,182,0.15);
}
.section-title{
  font-size:clamp(2rem,4vw,3rem);
  font-weight:700;
  margin-bottom:1rem;
}
.section-subtitle{
  font-size:1.125rem;
  color:var(--gray);
  max-width:600px;
  margin:0 auto;
}

/* Feature Cards */
.features-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:2rem;
}
.feature-card{
  background:var(--white);
  border-radius:var(--radius-lg);
  padding:2.5rem;
  box-shadow:var(--shadow);
  transition:all 0.3s;
  position:relative;
  overflow:hidden;
}
.feature-card::before{
  content:'';
  position:absolute;
  top:0;
  left:0;
  right:0;
  height:4px;
  background:linear-gradient(90deg,var(--orange),var(--pink));
  transform:scaleX(0);
  transition:transform 0.3s;
}
.feature-card:hover{
  transform:translateY(-10px);
  box-shadow:var(--shadow-hover);
}
.feature-card:hover::before{
  transform:scaleX(1);
}
.feature-icon{
  width:70px;
  height:70px;
  border-radius:var(--radius);
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:2rem;
  margin-bottom:1.5rem;
  transform:rotate(-5deg);
}
.feature-card:nth-child(1) .feature-icon{
  background:linear-gradient(135deg,#ffeaa7,#fdcb6e);
}
.feature-card:nth-child(2) .feature-icon{
  background:linear-gradient(135deg,#81ecec,#00cec9);
}
.feature-card:nth-child(3) .feature-icon{
  background:linear-gradient(135deg,#fab1a0,#e17055);
}
.feature-card:nth-child(4) .feature-icon{
  background:linear-gradient(135deg,#a29bfe,#6c5ce7);
}
.feature-card:nth-child(5) .feature-icon{
  background:linear-gradient(135deg,#fd79a8,#e84393);
}
.feature-card:nth-child(6) .feature-icon{
  background:linear-gradient(135deg,#55efc4,#00b894);
}
.feature-title{
  font-size:1.25rem;
  font-weight:700;
  margin-bottom:0.75rem;
}
.feature-text{
  color:var(--gray);
  line-height:1.7;
}

/* Books Masonry */
.books-section{
  background:var(--white);
  border-radius:var(--radius-lg) var(--radius-lg) 0 0;
  margin-top:4rem;
}
.books-grid{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:1.5rem;
}
.book-item{
  background:var(--cream);
  border-radius:var(--radius);
  overflow:hidden;
  transition:all 0.3s;
  text-decoration:none;
  color:var(--dark);
}
.book-item:hover{
  transform:translateY(-8px) rotate(1deg);
  box-shadow:var(--shadow-hover);
}
.book-cover{
  aspect-ratio:3/4;
  background:linear-gradient(135deg,var(--cream-dark),var(--cream));
  position:relative;
  overflow:hidden;
}
.book-cover img{
  width:100%;
  height:100%;
  object-fit:cover;
}
.book-badge{
  position:absolute;
  top:1rem;
  left:1rem;
  padding:0.375rem 1rem;
  background:var(--white);
  border-radius:100px;
  font-size:0.75rem;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:0.05em;
}
.book-badge.available{
  color:var(--teal);
}
.book-badge.borrowed{
  color:var(--pink);
}
.book-details{
  padding:1.5rem;
}
.book-cat{
  font-size:0.75rem;
  font-weight:600;
  text-transform:uppercase;
  letter-spacing:0.1em;
  color:var(--orange);
  margin-bottom:0.5rem;
}
.book-title-main{
  font-size:1.125rem;
  font-weight:700;
  line-height:1.4;
  margin-bottom:0.5rem;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
}
.book-author-main{
  font-size:0.9375rem;
  color:var(--gray);
}

/* CTA */
.cta-section{
  background:linear-gradient(135deg,var(--orange),var(--pink));
  padding:6rem 2rem;
  text-align:center;
  position:relative;
  overflow:hidden;
}
.cta-section::before{
  content:'';
  position:absolute;
  inset:0;
  background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.cta-inner{
  position:relative;
  z-index:1;
  max-width:700px;
  margin:0 auto;
}
.cta-title{
  font-size:clamp(2rem,5vw,3.5rem);
  font-weight:800;
  color:var(--white);
  margin-bottom:1.5rem;
  text-shadow:0 2px 10px rgba(0,0,0,0.1);
}
.cta-text{
  font-size:1.25rem;
  color:rgba(255,255,255,0.9);
  margin-bottom:2.5rem;
}
.cta-buttons{
  display:flex;
  gap:1rem;
  justify-content:center;
  flex-wrap:wrap;
}
.btn-white{
  background:var(--white);
  color:var(--orange);
}
.btn-white:hover{
  background:var(--cream);
  transform:translateY(-2px);
}
.btn-outline-white{
  background:transparent;
  color:var(--white);
  border:2px solid rgba(255,255,255,0.5);
}
.btn-outline-white:hover{
  background:rgba(255,255,255,0.1);
  border-color:var(--white);
}

/* Footer */
.footer{
  background:var(--dark);
  color:var(--white);
  padding:4rem 2rem 2rem;
}
.footer-inner{
  max-width:1200px;
  margin:0 auto;
}
.footer-top{
  display:grid;
  grid-template-columns:2fr repeat(3,1fr);
  gap:4rem;
  margin-bottom:4rem;
}
.footer-brand{
  max-width:300px;
}
.footer-logo{
  display:flex;
  align-items:center;
  gap:0.75rem;
  margin-bottom:1.5rem;
}
.footer-logo .logo-box{
  background:linear-gradient(135deg,var(--orange),var(--pink));
}
.footer-logo .logo-text{
  color:var(--white);
}
.footer-desc{
  color:#b2bec3;
  line-height:1.8;
}
.footer-title{
  font-weight:700;
  margin-bottom:1.5rem;
  color:var(--white);
}
.footer-links{
  display:flex;
  flex-direction:column;
  gap:0.875rem;
}
.footer-link{
  color:#b2bec3;
  text-decoration:none;
  transition:color 0.2s;
}
.footer-link:hover{
  color:var(--orange);
}
.footer-bottom{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding-top:2rem;
  border-top:1px solid #636e72;
  color:#b2bec3;
  font-size:0.9375rem;
}
.footer-social{
  display:flex;
  gap:1rem;
}
.social-btn{
  width:44px;
  height:44px;
  background:#636e72;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  color:var(--white);
  text-decoration:none;
  transition:all 0.3s;
  font-size:1.25rem;
}
.social-btn:hover{
  background:var(--orange);
  transform:translateY(-3px);
}

/* Responsive */
@media (max-width:1024px){
  .hero-inner{
    grid-template-columns:1fr;
    text-align:center;
  }
  .hero-desc{
    margin-left:auto;
    margin-right:auto;
  }
  .hero-buttons{
    justify-content:center;
  }
  .trust-pills{
    justify-content:center;
  }
  .hero-visual{
    display:none;
  }
  .features-grid{
    grid-template-columns:repeat(2,1fr);
  }
  .books-grid{
    grid-template-columns:repeat(3,1fr);
  }
  .stats-inner{
    grid-template-columns:repeat(2,1fr);
  }
  .footer-top{
    grid-template-columns:1fr 1fr;
    gap:2rem;
  }
}
@media (max-width:768px){
  .nav{
    left:1rem;
    right:1rem;
    transform:none;
    border-radius:var(--radius);
  }
  .nav-links{
    display:none;
  }
  .features-grid{
    grid-template-columns:1fr;
  }
  .books-grid{
    grid-template-columns:repeat(2,1fr);
  }
  .stats-inner{
    grid-template-columns:1fr;
  }
  .footer-top{
    grid-template-columns:1fr;
    text-align:center;
  }
  .footer-bottom{
    flex-direction:column;
    gap:1.5rem;
    text-align:center;
  }
}
</style>
</head>
<body>

<!-- Blobs -->
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<!-- Navigation -->
<nav class="nav">
  <div class="nav-inner">
    <a href="index.php" class="nav-logo">
      <div class="logo-box">📚</div>
      <span class="logo-text">Perpus</span>
    </a>
    
    <div class="nav-links">
      <a href="#fitur" class="nav-link">Fitur</a>
      <a href="#koleksi" class="nav-link">Koleksi</a>
      <a href="#tentang" class="nav-link">Tentang</a>
    </div>
    
    <div class="nav-cta">
      <?php if ($logged_in): ?>
        <div class="user-badge">
          <div class="user-avatar"><?= strtoupper(substr($nama,0,1)) ?></div>
          <span style="font-weight:600;"><?= htmlspecialchars(explode(' ',$nama)[0]) ?></span>
        </div>
        <a href="<?= $dashboard ?>" class="btn btn-orange">Dashboard</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline">Masuk</a>
        <a href="registrasi.php" class="btn btn-fill">Daftar</a>
      <?php endif ?>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="hero-inner">
    <div class="hero-content">
      <div class="hero-badge">
        Gratis untuk semua siswa!
      </div>
      
      <h1 class="hero-title">
        Pinjam Buku Jadi <span class="highlight handwritten">Mudah & Fun!</span> 🎉
      </h1>
      
      <p class="hero-desc">
        Platform perpustakaan digital dengan desain yang friendly dan mudah digunakan. Tidak perlu antri, pinjam kapan saja!
      </p>
      
      <div class="hero-buttons">
        <?php if ($logged_in): ?>
          <a href="<?= $dashboard ?>" class="btn btn-orange btn-lg">Ke Dashboard 🚀</a>
          <a href="<?= $role ?>/katalog.php" class="btn btn-outline btn-lg">Lihat Buku</a>
        <?php else: ?>
          <a href="registrasi.php" class="btn btn-fill btn-lg">Daftar Gratis 🎉</a>
          <a href="login.php" class="btn btn-outline btn-lg">Sudah Punya Akun?</a>
        <?php endif ?>
      </div>
      
      <div class="trust-pills">
        <div class="trust-pill">
          <span class="emoji">📖</span>
          <span><?= number_format($total_buku) ?>+ Buku</span>
        </div>
        <div class="trust-pill">
          <span class="emoji">👥</span>
          <span><?= number_format($total_anggota) ?> Anggota</span>
        </div>
        <div class="trust-pill">
          <span class="emoji">⚡</span>
          <span>Proses Cepat</span>
        </div>
      </div>
    </div>
    
    <div class="hero-visual">
      <div class="floating-stack">
        <?php
          $books = [];
          $br = $db->query("SELECT gambar_buku, judul_buku, pengarang FROM buku WHERE gambar_buku!='' ORDER BY RAND() LIMIT 3");
          while($r=$br->fetch_assoc()) $books[]=$r;
          for($i=0;$i<3;$i++):
            $g = $books[$i]['gambar_buku'] ?? '';
            $src = ($g && is_string($g) && file_exists(__DIR__.'/'.$g)) ? $g : '';
        ?>
        <div class="book-card-3d">
          <div class="book-thumb">
            <?php if($src): ?>
              <img src="<?= htmlspecialchars($src) ?>" alt="">
            <?php else: ?>
              📚
            <?php endif ?>
          </div>
          <div class="book-mini-title"><?= htmlspecialchars($books[$i]['judul_buku'] ?? 'Buku Menarik') ?></div>
          <div class="book-mini-author"><?= htmlspecialchars($books[$i]['pengarang'] ?? 'Penulis') ?></div>
        </div>
        <?php endfor ?>
        
        <div class="floating-icon">🔥</div>
        <div class="floating-icon">💡</div>
        <div class="floating-icon">⭐</div>
      </div>
    </div>
  </div>
  
  <div class="wave">
    <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
      <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" fill="#ffffff"></path>
    </svg>
  </div>
</section>

<!-- Stats -->
<section class="stats-section">
  <div class="stats-inner">
    <div class="stat-box">
      <div class="stat-number"><?= number_format($total_buku) ?></div>
      <div class="stat-label">Total Buku</div>
    </div>
    <div class="stat-box">
      <div class="stat-number"><?= number_format($buku_tersedia) ?></div>
      <div class="stat-label">Tersedia</div>
    </div>
    <div class="stat-box">
      <div class="stat-number"><?= number_format($total_anggota) ?></div>
      <div class="stat-label">Anggota</div>
    </div>
    <div class="stat-box">
      <div class="stat-number"><?= number_format($total_kat) ?></div>
      <div class="stat-label">Kategori</div>
    </div>
  </div>
</section>

<!-- Features -->
<section class="section" id="fitur">
  <div class="section-inner">
    <div class="section-header">
      <div class="section-label">✨ Kenapa Pilih Kami?</div>
      <h2 class="section-title">Fitur Keren yang Bikin Betah Baca</h2>
      <p class="section-subtitle">Semua yang kamu butuhkan untuk pengalaman meminjam buku terbaik</p>
    </div>
    
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">🔍</div>
        <h3 class="feature-title">Cari Buku Gampang</h3>
        <p class="feature-text">Pencarian super cepat dengan filter kategori yang intuitif. Temukan buku favoritmu dalam hitungan detik!</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">⚡</div>
        <h3 class="feature-title">Pinjam Kilat</h3>
        <p class="feature-text">Proses peminjaman hanya 3 klik. Nggak perlu ngantri atau isi form panjang!</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">🔔</div>
        <h3 class="feature-title">Ingatkan Otomatis</h3>
        <p class="feature-text">Sistem bakal ingetin kamu sebelum tenggat pengembalian. Nggak ada lagi denda keterlambatan!</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">📱</div>
        <h3 class="feature-title">Akses Dimana Saja</h3>
        <p class="feature-text">Bisa diakses dari HP, laptop, atau tablet. Perpustakaan digital yang selalu ada di saku kamu.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">💬</div>
        <h3 class="feature-title">Review & Rating</h3>
        <p class="feature-text">Lihat ulasan dari teman-teman lain sebelum meminjam. Sharing is caring!</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">🏆</div>
        <h3 class="feature-title">Reward System</h3>
        <p class="feature-text">Dapatkan poin setiap kali membaca dan tukarkan dengan hadiah menarik!</p>
      </div>
    </div>
  </div>
</section>

<!-- Books -->
<?php if($showcase->num_rows > 0): ?>
<section class="section books-section" id="koleksi">
  <div class="section-inner">
    <div class="section-header">
      <div class="section-label">📚 Koleksi Terbaru</div>
      <h2 class="section-title">Buku-Buku Kece yang Baru Masuk</h2>
      <p class="section-subtitle">Jangan sampai kehabisan! Stok terbatas lho 😊</p>
    </div>
    
    <div class="books-grid">
      <?php while($bk=$showcase->fetch_assoc()):
        $g=$bk['gambar_buku'];
        $isrc=($g&&is_string($g)&&file_exists(__DIR__.'/'.$g))?$g:'';
        $available=$bk['status']==='tersedia';
      ?>
      <a href="<?= $logged_in ? $role.'/detail.php?id='.$bk['id_buku'] : 'login.php' ?>" class="book-item">
        <div class="book-cover">
          <?php if($isrc):?>
            <img src="<?= htmlspecialchars($isrc)?>" alt="<?= htmlspecialchars($bk['judul_buku'])?>">
          <?php else:?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:4rem;">📖</div>
          <?php endif?>
          <div class="book-badge <?= $available?'available':'borrowed' ?>">
            <?= $available?'Tersedia':'Dipinjam' ?>
          </div>
        </div>
        <div class="book-details">
          <div class="book-cat"><?= htmlspecialchars($bk['nama_kategori'])?></div>
          <h3 class="book-title-main"><?= htmlspecialchars($bk['judul_buku'])?></h3>
          <div class="book-author-main"><?= htmlspecialchars($bk['pengarang'])?></div>
        </div>
      </a>
      <?php endwhile ?>
    </div>
    
    <div style="text-align:center;margin-top:3rem;">
      <a href="<?= $logged_in ? $role.'/katalog.php' : 'login.php' ?>" class="btn btn-fill btn-lg">Lihat Semua Koleksi →</a>
    </div>
  </div>
</section>
<?php endif ?>

<!-- CTA -->
<section class="cta-section">
  <div class="cta-inner">
    <h2 class="cta-title">Yuk, Gabung Jadi Anggota! 🚀</h2>
    <p class="cta-text">Gratis 100% tanpa biaya tersembunyi. Mulai perjalanan literasi kamu sekarang juga!</p>
    <div class="cta-buttons">
      <?php if($logged_in): ?>
        <a href="<?= $dashboard ?>" class="btn btn-white btn-lg">Dashboard Saya</a>
      <?php else: ?>
        <a href="registrasi.php" class="btn btn-white btn-lg">Daftar Sekarang 🎉</a>
        <a href="login.php" class="btn btn-outline-white btn-lg">Sudah Punya Akun</a>
      <?php endif ?>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="footer-logo">
          <div class="logo-box">📚</div>
          <span class="logo-text">Perpus Digital</span>
        </div>
        <p class="footer-desc">Platform perpustakaan modern yang bikin pinjam buku jadi lebih seru dan mudah!</p>
      </div>
      
      <div>
        <h4 class="footer-title">Menu</h4>
        <div class="footer-links">
          <a href="#fitur" class="footer-link">Fitur</a>
          <a href="#koleksi" class="footer-link">Koleksi</a>
          <a href="#" class="footer-link">Tentang Kami</a>
        </div>
      </div>
      
      <div>
        <h4 class="footer-title">Akun</h4>
        <div class="footer-links">
          <?php if (!$logged_in): ?>
            <a href="login.php" class="footer-link">Masuk</a>
            <a href="registrasi.php" class="footer-link">Daftar</a>
          <?php else: ?>
            <a href="<?= $dashboard ?>" class="footer-link">Dashboard</a>
            <a href="logout.php" class="footer-link">Keluar</a>
          <?php endif ?>
        </div>
      </div>
      
      <div>
        <h4 class="footer-title">Bantuan</h4>
        <div class="footer-links">
          <a href="#" class="footer-link">FAQ</a>
          <a href="#" class="footer-link">Kontak</a>
          <a href="#" class="footer-link">Panduan</a>
        </div>
      </div>
    </div>
    
    <div class="footer-bottom">
      <div>© <?= date('Y') ?> Perpus Digital. Dibuat dengan ❤️ untuk pembaca Indonesia</div>
      <div class="footer-social">
        <a href="#" class="social-btn">📘</a>
        <a href="#" class="social-btn">📸</a>
        <a href="#" class="social-btn">🐦</a>
      </div>
    </div>
  </div>
</footer>

</body>
</html>