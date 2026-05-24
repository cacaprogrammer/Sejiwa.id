<?php
session_start();

// Kalau sudah login, langsung redirect sesuai role
if (isset($_SESSION['username'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboardAdmin.php");
    } else {
        header("Location: landingpagepilihanfix.php");
    }
    exit();
}

include "koneksi.php";

$pesan = "";

if (isset($_POST['submit'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Cari user berdasarkan email
    $stmt = $conn->prepare("SELECT * FROM tb_user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $hasil = $stmt->get_result();
    $user  = $hasil->fetch_assoc();

    if ($user) {
        // Verifikasi password hash (bcrypt sesuai modul 16)
        if (password_verify($password, $user['password'])) {
            // Simpan data ke session
            $_SESSION['id']           = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role']         = $user['role'];

            // Redirect sesuai role
            if ($user['role'] === 'admin') {
                header("Location: dashboardAdmin.php");
            } else {
                header("Location: landingpagepilihanfix.php");
            }
            exit();
        } else {
            $pesan = "Kata sandi salah!";
        }
    } else {
        $pesan = "Email tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <title>Halaman Login — Sejiwa.id</title>

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: "Poppins", sans-serif;
      background-color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      display: flex;
      max-width: 1000px;
      width: 100%;
      background-color: #fff;
      border-radius: 15px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      overflow: hidden;
      animation: fadeIn 0.8s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* === PANEL KIRI === */
    .left {
      flex: 1;
      background-color: #ffffff;
      background-image: url("login1.png");
      background-repeat: no-repeat;
      background-position: 35px center;
      background-size: 415px auto;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 30px;
    }

    /* === PANEL KANAN === */
    .right {
      flex: 1;
      padding: 50px 55px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .welcome {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 6px;
    }

    .welcome img { width: 26px; height: 26px; }

    .welcome h3 {
      font-size: 22px;
      font-weight: 700;
      color: #2e2e2e;
      margin: 0;
    }

    .subtitle {
      font-size: 13px;
      color: #888;
      margin-bottom: 28px;
    }

    /* Pesan error */
    .pesan-error {
      background: #fff0f0;
      color: #c0392b;
      border: 1px solid #fccaca;
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 12.5px;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .field { margin-bottom: 18px; }

    .field label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: #3d1f0f;
      margin-bottom: 6px;
      letter-spacing: 0.3px;
    }

    .input-wrap { position: relative; }

    .input-wrap i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #a0613a;
      font-size: 14px;
    }

    .input-wrap input {
      width: 100%;
      padding: 11px 14px 11px 38px;
      background: #f5ede4;
      border: 1.5px solid #e8d5c4;
      border-radius: 10px;
      font-size: 13px;
      font-family: "Poppins", sans-serif;
      color: #3d1f0f;
      transition: border-color 0.2s, box-shadow 0.2s;
      outline: none;
    }

    .input-wrap input:focus {
      border-color: #603a2b;
      box-shadow: 0 0 0 3px rgba(96,58,43,0.1);
      background: #fff;
    }

    .input-wrap input::placeholder {
      color: #bbb;
      font-size: 12px;
    }

    .options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 12px;
      color: #777;
      margin-bottom: 22px;
    }

    .options label {
      display: flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
    }

    .options input[type="checkbox"] {
      accent-color: #603a2b;
      width: 14px; height: 14px;
    }

    .options a {
      color: #603a2b;
      font-weight: 600;
      text-decoration: none;
      font-size: 12px;
    }

    .options a:hover { text-decoration: underline; }

    /* Tombol submit yang sesungguhnya */
    .btn-masuk {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, #3d1f0f, #603a2b);
      color: #fff;
      border: none;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 600;
      font-family: "Poppins", sans-serif;
      cursor: pointer;
      transition: all 0.3s;
      letter-spacing: 0.5px;
    }

    .btn-masuk:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 20px rgba(61,31,15,0.3);
    }

    .btn-masuk:active { transform: translateY(0); }

    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 18px 0;
      color: #bbb;
      font-size: 12px;
    }

    .divider::before,
    .divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background: #e8d5c4;
    }

    .social-row {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin-bottom: 18px;
    }

    .social-btn {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      padding: 9px;
      border: 1.5px solid #e8d5c4;
      border-radius: 10px;
      font-size: 12px;
      color: #555;
      cursor: pointer;
      transition: all 0.2s;
      background: #fff;
      text-decoration: none;
    }

    .social-btn:hover {
      border-color: #a0613a;
      background: #f5ede4;
    }

    .social-btn img { width: 17px; }

    .daftar-link {
      text-align: center;
      font-size: 12.5px;
      color: #888;
    }

    .daftar-link a {
      color: #603a2b;
      font-weight: 600;
      text-decoration: none;
    }

    .daftar-link a:hover { text-decoration: underline; }

    @media (max-width: 768px) {
      .container { flex-direction: column; width: 90%; }
      .left { display: none; }
      .right { padding: 40px 30px; }
    }

    @media (max-width: 480px) {
      .right { padding: 30px 20px; }
    }
  </style>
</head>
<body>

<div class="container">

  <!-- PANEL KIRI (tidak diubah) -->
  <div class="left"></div>

  <!-- PANEL KANAN -->
  <div class="right">

    <div class="welcome">
      <img src="ri_admin-fill.png" alt="icon">
      <h3>Selamat Datang!</h3>
    </div>
    <p class="subtitle">Masuk ke akun kamu untuk mulai menjelajah</p>

    <!-- Tampilkan pesan error jika ada -->
    <?php if ($pesan != ""): ?>
      <div class="pesan-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= $pesan ?>
      </div>
    <?php endif; ?>

    <!-- Form dengan method POST agar PHP bisa membaca inputnya -->
    <form method="post" action="">

      <div class="field">
        <label>Email</label>
        <div class="input-wrap">
          <i class="fa-solid fa-envelope"></i>
          <input type="email" name="email" placeholder="Masukkan email kamu" required>
        </div>
      </div>

      <div class="field">
        <label>Kata Sandi</label>
        <div class="input-wrap">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" placeholder="Masukkan kata sandi" required>
        </div>
      </div>

      <div class="options">
        <label>
          <input type="checkbox"> Ingatkan Saya
        </label>
        <a href="#">Lupa kata sandi?</a>
      </div>

      <!-- Tombol submit yang benar, bukan <a href> -->
      <button type="submit" name="submit" class="btn-masuk">Masuk</button>

      <div class="divider">atau masuk dengan</div>

      <div class="social-row">
        <a href="#" class="social-btn">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/google/google-original.svg"> Google
        </a>
        <a href="#" class="social-btn">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/facebook/facebook-original.svg"> Facebook
        </a>
      </div>

      <div class="daftar-link">
        Belum punya akun? <a href="register.php">Daftar di sini</a>
      </div>

    </form>
  </div>

</div>

</body>
</html>