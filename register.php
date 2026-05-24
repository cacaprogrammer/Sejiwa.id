<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: landingpagepilihanfix.php");
    exit();
}

include "koneksi.php";

$pesan = "";
$tipe  = "";

if (isset($_POST['submit'])) {
    $nama     = trim($_POST['nama_lengkap']);
    $email    = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $cek = $conn->prepare("SELECT * FROM tb_user WHERE username = ?");
    $cek->bind_param("s", $username);
    $cek->execute();
    $hasil = $cek->get_result();

    if ($hasil->num_rows > 0) {
        $pesan = "Username sudah digunakan! Coba username lain.";
        $tipe  = "error";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO tb_user (nama_lengkap, email, username, password, role) VALUES (?, ?, ?, ?, 'user')");
        $stmt->bind_param("ssss", $nama, $email, $username, $hash);

        if ($stmt->execute()) {
            $pesan = "Pendaftaran berhasil! Silakan masuk.";
            $tipe  = "sukses";
        } else {
            $pesan = "Pendaftaran gagal, coba lagi.";
            $tipe  = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar — Sejiwa.id</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    :root {
      --coklat-tua:   #3d1f0f;
      --coklat-mid:   #603a2b;
      --coklat-muda:  #a0613a;
      --krem:         #f5ede4;
      --krem-gelap:   #e8d5c4;
      --emas:         #c9973a;
      --putih:        #fdfaf7;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: "Poppins", sans-serif;
      background-color: var(--krem);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow-x: hidden;
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background-image:
        radial-gradient(circle at 15% 50%, rgba(96,58,43,0.08) 0%, transparent 50%),
        radial-gradient(circle at 85% 20%, rgba(201,151,58,0.08) 0%, transparent 40%);
      pointer-events: none;
    }

    body::after {
      content: "";
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 6px;
      background: linear-gradient(90deg, var(--coklat-tua), var(--emas), var(--coklat-mid), var(--emas), var(--coklat-tua));
    }

    .card {
      width: 100%;
      max-width: 980px;
      background: var(--putih);
      border-radius: 24px;
      display: flex;
      overflow: hidden;
      box-shadow:
        0 20px 60px rgba(61,31,15,0.15),
        0 0 0 1px rgba(201,151,58,0.2);
      animation: slideUp 0.7s cubic-bezier(0.16,1,0.3,1) both;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .panel-kiri {
      flex: 1;
      background: linear-gradient(160deg, var(--coklat-tua) 0%, var(--coklat-mid) 60%, var(--coklat-muda) 100%);
      padding: 50px 40px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
    }

    .tagline {
      position: relative; z-index: 1;
    }

    .tagline h2 {
      font-family: "Playfair Display", serif;
      font-size: 28px;
      color: #fff;
      line-height: 1.4;
      margin-bottom: 12px;
    }

    .tagline h2 span {
      color: var(--emas);
    }

    .tagline p {
      font-size: 13px;
      color: rgba(255,255,255,0.65);
      line-height: 1.7;
    }

    .ornamen-emas {
      position: relative; z-index: 1;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .ornamen-emas::before,
    .ornamen-emas::after {
      content: "";
      flex: 1;
      height: 1px;
      background: rgba(201,151,58,0.4);
    }

    .ornamen-emas span {
      font-size: 18px;
      color: var(--emas);
    }

    .ilustrasi {
      position: relative; z-index: 1;
      text-align: center;
    }

    .ilustrasi img {
      width: 100%;
      max-width: 340px;
      filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3));
    }

    .panel-kanan {
      flex: 1.1;
      padding: 45px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .judul-form {
      margin-bottom: 28px;
    }

    .judul-form h1 {
      font-family: "Playfair Display", serif;
      font-size: 28px;
      color: var(--coklat-tua);
      font-weight: 700;
      line-height: 1.2;
    }

    .judul-form p {
      font-size: 13px;
      color: #888;
      margin-top: 6px;
    }

    .pesan {
      padding: 10px 14px;
      border-radius: 10px;
      font-size: 12.5px;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }

    .pesan.error  { background: #fff0f0; color: #c0392b; border: 1px solid #fccaca; }
    .pesan.sukses { background: #f0fff4; color: #1e7e34; border: 1px solid #b2f0c5; }

    .field {
      margin-bottom: 16px;
    }

    .field label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: var(--coklat-tua);
      margin-bottom: 6px;
      letter-spacing: 0.3px;
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--coklat-muda);
      font-size: 14px;
    }

    .input-wrap input {
      width: 100%;
      padding: 11px 14px 11px 38px;
      background: var(--krem);
      border: 1.5px solid var(--krem-gelap);
      border-radius: 10px;
      font-size: 13px;
      font-family: "Poppins", sans-serif;
      color: var(--coklat-tua);
      transition: border-color 0.2s, box-shadow 0.2s;
      outline: none;
    }

    .input-wrap input:focus {
      border-color: var(--coklat-mid);
      box-shadow: 0 0 0 3px rgba(96,58,43,0.1);
      background: #fff;
    }

    .input-wrap input::placeholder {
      color: #bbb;
      font-size: 12px;
    }

    .row-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .checkbox-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 20px;
    }

    .checkbox-wrap input[type="checkbox"] {
      accent-color: var(--coklat-mid);
      width: 15px; height: 15px;
      cursor: pointer;
    }

    .checkbox-wrap span {
      font-size: 12px;
      color: #777;
    }

    .btn-daftar {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, var(--coklat-tua), var(--coklat-mid));
      color: #fff;
      border: none;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 600;
      font-family: "Poppins", sans-serif;
      cursor: pointer;
      transition: all 0.3s;
      letter-spacing: 0.5px;
      position: relative;
      overflow: hidden;
    }

    .btn-daftar::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
      opacity: 0;
      transition: opacity 0.3s;
    }

    .btn-daftar:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 20px rgba(61,31,15,0.3);
    }

    .btn-daftar:hover::after { opacity: 1; }
    .btn-daftar:active { transform: translateY(0); }

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
      background: var(--krem-gelap);
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
      border: 1.5px solid var(--krem-gelap);
      border-radius: 10px;
      font-size: 12px;
      color: #555;
      cursor: pointer;
      transition: all 0.2s;
      background: #fff;
      text-decoration: none;
    }

    .social-btn:hover {
      border-color: var(--coklat-muda);
      background: var(--krem);
    }

    .social-btn img { width: 17px; }

    .login-link {
      text-align: center;
      font-size: 12.5px;
      color: #888;
    }

    .login-link a {
      color: var(--coklat-mid);
      font-weight: 600;
      text-decoration: none;
    }

    .login-link a:hover { text-decoration: underline; }

    @media (max-width: 800px) {
      .panel-kiri { display: none; }
      .panel-kanan { padding: 40px 30px; }
      .row-2 { grid-template-columns: 1fr; }
    }

    @media (max-width: 480px) {
      .panel-kanan { padding: 30px 20px; }
    }
  </style>
</head>
<body>

<div class="card">

  <!-- PANEL KIRI -->
  <div class="panel-kiri">

    <div class="ilustrasi">
      <img src="login1.png" alt="Ilustrasi">
    </div>

    <div class="ornamen-emas">
      <span>✦</span>
    </div>

    <div class="tagline">
      <h2>Jelajahi <span>Sejarah</span><br>Bangsa Indonesia</h2>
      <p>Bergabunglah bersama ribuan pembaca yang mencintai sejarah dan budaya nusantara.</p>
    </div>

  </div>

  <!-- PANEL KANAN -->
  <div class="panel-kanan">

    <div class="judul-form">
      <h1>Daftar Sekarang</h1>
      <p>Isi data diri kamu untuk mulai menjelajah</p>
    </div>

    <?php if ($pesan != ""): ?>
      <div class="pesan <?= $tipe ?>">
        <i class="fa-solid <?= $tipe === 'sukses' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= $pesan ?>
      </div>
    <?php endif; ?>

    <form method="post" action="">

      <div class="row-2">
        <div class="field">
          <label>Nama Lengkap</label>
          <div class="input-wrap">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="nama_lengkap" placeholder="Nama lengkap kamu" required>
          </div>
        </div>
        <div class="field">
          <label>Email</label>
          <div class="input-wrap">
            <i class="fa-solid fa-envelope"></i>
            <input type="email" name="email" placeholder="email@contoh.com" required>
          </div>
        </div>
      </div>

      <div class="row-2">
        <div class="field">
          <label>Username</label>
          <div class="input-wrap">
            <i class="fa-solid fa-at"></i>
            <input type="text" name="username" placeholder="Username unik kamu" required>
          </div>
        </div>
        <div class="field">
          <label>Password</label>
          <div class="input-wrap">
            <i class="fa-solid fa-lock"></i>
            <input type="password" name="password" placeholder="Min. 8 karakter" required>
          </div>
        </div>
      </div>

      <div class="checkbox-wrap">
        <input type="checkbox" id="ingat">
        <span>Saya menyetujui syarat & ketentuan Sejiwa.id</span>
      </div>

      <button type="submit" name="submit" class="btn-daftar">
        Daftar Sekarang
      </button>

      <div class="divider">atau daftar dengan</div>

      <div class="social-row">
        <a href="#" class="social-btn">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/google/google-original.svg"> Google
        </a>
        <a href="#" class="social-btn">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/facebook/facebook-original.svg"> Facebook
        </a>
      </div>

      <div class="login-link">
        Sudah punya akun? <a href="loginpage.php">Masuk di sini</a>
      </div>

    </form>
  </div>

</div>

</body>
</html>