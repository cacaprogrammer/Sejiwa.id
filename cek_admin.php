<?php
// cek_admin.php
// File ini dipanggil di setiap halaman manajemen/admin
// Fungsinya: memastikan yang mengakses adalah admin atau penulis yang sudah login

// Mencegah error jika session sudah dimulai di file utama
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kalau belum login → redirect ke login
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php"); // Disesuaikan ke file login.php milikmu
    exit();
}

// PERBAIKAN: Kalau sudah login tapi bukan admin DAN bukan penulis → tolak akses
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'penulis') {
    echo "<p style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            ⛔ Akses ditolak! Halaman ini hanya untuk Admin dan Penulis.<br><br>
            <a href='../landingpagepilihanfix.php'>Kembali ke Beranda</a>
        </p>";
    exit();
}
?>