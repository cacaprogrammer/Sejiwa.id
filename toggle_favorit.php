<?php
/**
 * toggle_favorit.php
 * Dipanggil via AJAX (fetch POST) dari detail_artikel.php
 * POST body: id_artikel=<int>
 */
session_start();
header('Content-Type: application/json');

// Harus login
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Belum login']);
    exit;
}

require_once 'koneksi.php';
require_once 'artikel_helper.php';

$id_artikel = isset($_POST['id_artikel']) ? (int)$_POST['id_artikel'] : 0;
$id_user    = getSessionUserId();

if ($id_artikel <= 0 || $id_user <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
    exit;
}

$hasil = toggleFavoritDB($conn, $id_user, $id_artikel);
echo json_encode(['status' => $hasil]);