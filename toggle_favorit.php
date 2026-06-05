<?php
ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Belum login']);
    exit;
}

require_once 'koneksi.php';

$id_user = 0;
if (!empty($_SESSION['id'])) {
    $id_user = (int)$_SESSION['id'];
} elseif (!empty($_SESSION['user_id'])) {
    $id_user = (int)$_SESSION['user_id'];
} else {
    $uname = $conn->real_escape_string($_SESSION['username']);
    $row   = $conn->query("SELECT id FROM tb_user WHERE username = '$uname' LIMIT 1")->fetch_assoc();
    if ($row) $id_user = (int)$row['id'];
}

$id_artikel = 0;
if (isset($_POST['id_artikel'])) {
    $id_artikel = (int)$_POST['id_artikel'];
} elseif (isset($_GET['id_artikel'])) {
    $id_artikel = (int)$_GET['id_artikel'];
}

if ($id_artikel <= 0 || $id_user <= 0) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Data tidak valid',
        'debug'   => [
            'id_user'    => $id_user,
            'id_artikel' => $id_artikel,
            'post'       => $_POST,
            'session'    => array_keys($_SESSION)
        ]
    ]);
    exit;
}

$cek = $conn->prepare("SELECT id FROM tb_favorit WHERE user_id = ? AND artikel_id = ?");
$cek->bind_param("ii", $id_user, $id_artikel);
$cek->execute();
$ada = $cek->get_result()->num_rows > 0;
$cek->close();

ob_end_clean();

if ($ada) {
    $del = $conn->prepare("DELETE FROM tb_favorit WHERE user_id = ? AND artikel_id = ?");
    $del->bind_param("ii", $id_user, $id_artikel);
    if (!$del->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal hapus: ' . $del->error]);
        exit;
    }
    $del->close();
    echo json_encode(['status' => 'removed']);
} else {
    $ins = $conn->prepare("INSERT INTO tb_favorit (user_id, artikel_id, saved_at) VALUES (?, ?, NOW())");
    $ins->bind_param("ii", $id_user, $id_artikel);
    if (!$ins->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal tambah: ' . $ins->error]);
        exit;
    }
    $ins->close();
    echo json_encode(['status' => 'added']);
}