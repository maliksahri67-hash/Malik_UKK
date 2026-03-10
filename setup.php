<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup — Perpus Digital</title>
<style>
body{font-family:sans-serif;background:#f0f0f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
.box{background:white;padding:40px;border-radius:12px;max-width:600px;width:90%;box-shadow:0 4px 24px rgba(0,0,0,.1);}
h1{color:#1a3a2a;margin-bottom:8px;}
.log{background:#1a1a1a;color:#00ff88;padding:20px;border-radius:8px;margin-top:20px;font-family:monospace;font-size:13px;max-height:400px;overflow-y:auto;}
.ok{color:#00ff88;} .err{color:#ff4444;} .info{color:#ffcc00;}
.btn{display:inline-block;padding:12px 24px;background:#1a3a2a;color:white;border:none;border-radius:8px;cursor:pointer;font-size:15px;text-decoration:none;margin-top:16px;}
.btn:hover{background:#2d5a3d;}
input[type=text],input[type=password]{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:12px;font-size:14px;box-sizing:border-box;}
label{display:block;font-weight:600;margin-bottom:4px;font-size:13px;color:#555;}
</style>
</head>
<body>
<div class="box">
  <h1>📚 Setup Perpus Digital</h1>
  <p style="color:#666;">Konfigurasi koneksi database dan buat tabel otomatis.</p>

  <?php if (!isset($_POST['install'])): ?>
  <form method="POST">
    <label>Host Database</label>
    <input type="text" name="db_host" value="localhost">
    <label>Username Database</label>
    <input type="text" name="db_user" value="root">
    <label>Password Database</label>
    <input type="password" name="db_pass" value="">
    <label>Nama Database</label>
    <input type="text" name="db_name" value="perpus_20">
    <button type="submit" name="install" class="btn">🚀 Install Sekarang</button>
  </form>
  <?php else:
    $host = $_POST['db_host'];
    $user = $_POST['db_user'];
    $pass = $_POST['db_pass'];
    $name = $_POST['db_name'];
    
    echo '<div class="log">';
    
    // Connect
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        echo '<span class="err">✗ Koneksi gagal: '.$conn->connect_error.'</span><br>';
    } else {
        echo '<span class="ok">✓ Koneksi ke MySQL berhasil</span><br>';
        
        // Create DB
        if ($conn->query("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
            echo '<span class="ok">✓ Database `'.$name.'` siap</span><br>';
        }
        
        $conn->select_db($name);
        
        // Read SQL file
        $sql_file = __DIR__ . '/perpus_db.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            $ok = $err = 0;
            foreach ($queries as $q) {
                if (empty($q) || strpos($q, '--') === 0) continue;
                if ($conn->query($q)) $ok++;
                else { echo '<span class="err">✗ '.$conn->error.'</span><br>'; $err++; }
            }
            echo "<span class=\"ok\">✓ $ok query berhasil</span><br>";
            if ($err) echo "<span class=\"err\">✗ $err query gagal</span><br>";
        } else {
            echo '<span class="info">ℹ SQL file tidak ditemukan, skip import data awal</span><br>';
        }
        
        // Update config
        $cfg = "<?php\ndefine('DB_HOST', '$host');\ndefine('DB_USER', '$user');\ndefine('DB_PASS', '$pass');\ndefine('DB_NAME', '$name');\n\nfunction getDB() {\n    static \$conn = null;\n    if (\$conn === null) {\n        \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);\n        if (\$conn->connect_error) die('DB Error: '.\$conn->connect_error);\n        \$conn->set_charset('utf8mb4');\n    }\n    return \$conn;\n}\n?>";
        if (file_put_contents(__DIR__.'/config/database.php', $cfg)) {
            echo '<span class="ok">✓ config/database.php diperbarui</span><br>';
        }
        
        // Create uploads dir
        if (!is_dir(__DIR__.'/uploads')) mkdir(__DIR__.'/uploads', 0755, true);
        echo '<span class="ok">✓ Folder uploads siap</span><br>';
        
        echo '<br><span class="ok">✅ INSTALASI SELESAI!</span>';
    }
    echo '</div>';
    echo '<a href="index.php" class="btn">Masuk ke Aplikasi →</a>';
  endif ?>
</div>
</body>
</html>
