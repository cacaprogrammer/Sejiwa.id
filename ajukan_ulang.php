<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: loginpage.php");
    exit();
}
if ($_SESSION['role'] === 'admin') {
    header("Location: dashboardAdmin.php");
    exit();
}

include "koneksi.php";

$user_id = $_SESSION['id'];

if (!isset($_GET['id'])) {
    header("Location: history.php?tab=artikel");
    exit();
}

$id_artikel = (int)$_GET['id'];

// Hanya artikel rejected/draft milik user ini yang boleh diajukan ulang
$cek = $conn->prepare("SELECT id, status FROM tb_artikel WHERE id = ? AND created_by = ? AND status IN ('rejected','draft')");
$cek->bind_param("ii", $id_artikel, $user_id);
$cek->execute();
$row = $cek->get_result()->fetch_assoc();

if (!$row) {
    header("Location: history.php?tab=artikel");
    exit();
}

// Update status ke pending dan hapus catatan admin
$upd = $conn->prepare("UPDATE tb_artikel SET status = 'pending', catatan_admin = NULL WHERE id = ? AND created_by = ?");
$upd->bind_param("ii", $id_artikel, $user_id);
$upd->execute();

header("Location: history.php?tab=artikel&sukses=ajukan");
exit();