<?php
// cek_admin.php
// File ini dipanggil di setiap halaman admin
// Fungsinya: memastikan yang mengakses adalah admin yang sudah login

session_start();

// Kalau belum login → redirect ke login
if (!isset($_SESSION['username'])) {
    header("Location: ../loginpage.php");
    exit();
}

// Kalau sudah login tapi bukan admin → tolak akses
if ($_SESSION['role'] !== 'admin') {
    echo "<p style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            ⛔ Akses ditolak! Halaman ini hanya untuk admin.<br><br>
            <a href='../landingpagepilihanfix.php'>Kembali ke Beranda</a>
        </p>";
    exit();
}
?>