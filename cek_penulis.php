<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role'])) {
    header("Location: /website/loginpage.php");
    exit();
}

if ($_SESSION['role'] !== 'penulis' && $_SESSION['role'] !== 'admin') {
    header("Location: /website/loginpage.php");
    exit();
}
?>