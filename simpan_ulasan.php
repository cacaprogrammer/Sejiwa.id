<?php
ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['sukses' => false, 'pesan' => 'Belum login']);
    exit();
}

require_once 'koneksi.php';

$input = json_decode(file_get_contents('php://input'), true);

$artikel_id = (int)($input['artikel_id'] ?? 0);
$rating     = (int)($input['rating'] ?? 0);
$komentar   = trim($input['komentar'] ?? '');
$user_id    = (int)$_SESSION['id'];

if ($artikel_id <= 0 || $rating < 1 || $rating > 5 || $komentar === '') {
    ob_end_clean();
    echo json_encode(['sukses' => false, 'pesan' => 'Data tidak valid', 'debug' => [
        'artikel_id' => $artikel_id,
        'rating'     => $rating,
        'komentar'   => $komentar,
        'user_id'    => $user_id
    ]]);
    exit();
}

$ins = $conn->prepare("INSERT INTO tb_ulasan (user_id, artikel_id, rating, komentar) VALUES (?, ?, ?, ?)");
$ins->bind_param("iiis", $user_id, $artikel_id, $rating, $komentar);

ob_end_clean();

if ($ins->execute()) {
    echo json_encode(['sukses' => true, 'pesan' => 'OK']);
} else {
    echo json_encode(['sukses' => false, 'pesan' => 'Gagal simpan: ' . $ins->error]);
}