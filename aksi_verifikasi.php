<?php
// aksi_verifikasi.php — Proses approve / reject artikel oleh admin
include "cek_admin.php";
include "koneksi.php";

$id     = isset($_GET['id'])     ? (int)$_GET['id']         : 0;
$aksi   = isset($_GET['aksi'])   ? trim($_GET['aksi'])       : '';
$catatan= isset($_GET['catatan'])? trim($_GET['catatan'])    : '';

// Tentukan kemana redirect setelah aksi
// 'detail' = kembali ke halaman detail_verifikasi.php
// default  = kembali ke halaman verifikasi di dashboard admin
$redirect_target = isset($_GET['redirect']) ? $_GET['redirect'] : 'verifikasi';
$artikel_id      = isset($_GET['artikel_id']) ? (int)$_GET['artikel_id'] : $id;

if (!$id || !in_array($aksi, ['approve', 'reject'])) {
    header("Location: dashboardAdmin.php?page=verifikasi&error=invalid");
    exit();
}

// Cek artikel ada & masih pending
$cek = $conn->prepare("SELECT id, judul, created_by, penulis FROM tb_artikel WHERE id = ? AND status = 'pending'");
$cek->bind_param("i", $id);
$cek->execute();
$artikel = $cek->get_result()->fetch_assoc();

if (!$artikel) {
    header("Location: dashboardAdmin.php?page=verifikasi&error=notfound");
    exit();
}

if ($aksi === 'approve') {
    // ── APPROVE ──
    $stmt = $conn->prepare(
        "UPDATE tb_artikel 
         SET status = 'published', published_at = NOW(), catatan_admin = NULL
         WHERE id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Kirim notifikasi ke penulis
    if (!empty($artikel['created_by'])) {
        // NOTIFIKASI DENGAN FORMAT ID ARTIKEL
        $pesan_notif = "ID: {$artikel['id']} - Artikel Anda berjudul '{$artikel['judul']}' telah diverifikasi dan diterbitkan.";
        $judul_notif = "Artikel Disetujui";
        $notif = $conn->prepare(
            "INSERT INTO tb_notifikasi (user_id, judul, pesan, tipe, created_at) VALUES (?, ?, ?, 'info', NOW())"
        );
        if ($notif) {
            $notif->bind_param("iss", $artikel['created_by'], $judul_notif, $pesan_notif);
            $notif->execute();
        }
    }

    $sukses_msg = urlencode('Artikel berhasil diverifikasi dan diterbitkan.');

    if ($redirect_target === 'detail') {
        header("Location: detail_verifikasi.php?id={$artikel_id}&sukses=" . $sukses_msg);
    } else {
        header("Location: dashboardAdmin.php?page=verifikasi&sukses=" . $sukses_msg);
    }
    exit();

} elseif ($aksi === 'reject') {
    // ── REJECT ──
    if (empty($catatan)) {
        if ($redirect_target === 'detail') {
            header("Location: detail_verifikasi.php?id={$artikel_id}&error=nocatatan");
        } else {
            header("Location: dashboardAdmin.php?page=verifikasi&error=nocatatan");
        }
        exit();
    }

    $stmt = $conn->prepare(
        "UPDATE tb_artikel 
         SET status = 'rejected', catatan_admin = ?
         WHERE id = ?"
    );
    $stmt->bind_param("si", $catatan, $id);
    $stmt->execute();

    // Kirim notifikasi ke penulis
    if (!empty($artikel['created_by'])) {
        // NOTIFIKASI DENGAN FORMAT ID ARTIKEL (PENTING!)
        $pesan_notif = "ID: {$artikel['id']} - Artikel Anda berjudul '{$artikel['judul']}' ditolak. Catatan admin: {$catatan}";
        $judul_notif = "Artikel Ditolak";
        $notif = $conn->prepare(
            "INSERT INTO tb_notifikasi (user_id, judul, pesan, tipe, created_at) VALUES (?, ?, ?, 'peringatan', NOW())"
        );
        if ($notif) {
            $notif->bind_param("iss", $artikel['created_by'], $judul_notif, $pesan_notif);
            $notif->execute();
        }
    }

    $sukses_msg = urlencode('Artikel berhasil ditolak. Penulis telah diberitahu.');

    if ($redirect_target === 'detail') {
        header("Location: detail_verifikasi.php?id={$artikel_id}&sukses=" . $sukses_msg);
    } else {
        header("Location: dashboardAdmin.php?page=verifikasi&sukses=" . $sukses_msg);
    }
    exit();
}

// Fallback
header("Location: dashboardAdmin.php?page=verifikasi");
exit();
?>