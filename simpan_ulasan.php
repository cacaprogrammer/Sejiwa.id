<?php
session_start();

if (!isset($_SESSION['username'])) {
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
    echo json_encode(['sukses' => false, 'pesan' => 'Data tidak valid']);
    exit();
}

// Selalu INSERT — user boleh ulasan berkali-kali
$ins = $conn->prepare("INSERT INTO tb_ulasan (user_id, artikel_id, rating, komentar) VALUES (?, ?, ?, ?)");
$ins->bind_param("iiis", $user_id, $artikel_id, $rating, $komentar);
$ins->execute();

echo json_encode(['sukses' => true, 'pesan' => 'OK']);