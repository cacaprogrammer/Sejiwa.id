<?php
// pages/verifikasi.php
// Dipanggil dari dashboardAdmin.php via include "pages/$page.php"
// $conn sudah tersedia dari dashboardAdmin.php

$r_menunggu = $conn->query("SELECT COUNT(*) AS total FROM tb_artikel WHERE status = 'pending'")->fetch_assoc()['total'];
$r_verified = $conn->query("SELECT COUNT(*) AS total FROM tb_artikel WHERE status = 'published'")->fetch_assoc()['total'];

// Ambil artikel pending + nama pengirim dari tb_user
// Sesuai DB: tb_user (bukan tb_users), kolom created_by
$stmt = $conn->query("
    SELECT a.id, a.judul, a.kategori, a.created_at, a.penulis,
           u.nama_lengkap AS nama_pengirim
    FROM tb_artikel a
    LEFT JOIN tb_user u ON u.id = a.created_by
    WHERE a.status = 'pending'
    ORDER BY a.created_at DESC
");
$list_pending = $stmt ? $stmt->fetch_all(MYSQLI_ASSOC) : [];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    .vf-wrap { max-width: 860px; }
    .vf-title { font-size: 22px; font-weight: bold; color: #2d1a0e; margin-bottom: 4px; }
    .vf-sub   { font-size: 13px; color: #888; margin-bottom: 1.5rem; }

    .vf-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 1.75rem;
        max-width: 380px;
    }

    .vf-stat-card {
        background: #f5f0eb;
        border-radius: 12px;
        padding: 14px 18px;
    }

    .vf-stat-label { font-size: 12px; color: #888; margin-bottom: 5px; }
    .vf-stat-val   { font-size: 26px; font-weight: bold; color: #2d1a0e; }
    .vf-stat-val.amber { color: #8a5800; }
    .vf-stat-val.green { color: #1a6b3a; }

    .vf-list { display: flex; flex-direction: column; gap: 12px; }

    .vf-card {
        background: white;
        border: 1px solid #e8ddd5;
        border-left: 4px solid #4a2c18;
        border-radius: 12px;
        padding: 16px 18px;
    }

    .vf-card-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13.5px;
        font-weight: bold;
        color: #4a2c18;
        margin-bottom: 12px;
    }

    .vf-rows {
        display: grid;
        grid-template-columns: 90px 1fr;
        gap: 5px 8px;
        margin-bottom: 12px;
    }

    .vf-key { font-size: 13px; color: #999; }
    .vf-val { font-size: 13px; color: #2d1a0e; font-weight: 600; }

    .vf-btn-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        padding-top: 12px;
        border-top: 1px solid #f0e8e0;
    }

    .vf-btn {
        font-size: 12.5px;
        padding: 7px 15px;
        border-radius: 8px;
        border: 1px solid #d5c9c0;
        background: white;
        color: #333;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.15s;
    }

    .vf-btn:hover         { background: #f7f3f0; }
    .vf-btn-success       { background: #d6f5e3; color: #1a6b3a; border-color: #a8e6c5; }
    .vf-btn-success:hover { background: #b8ecce; }
    .vf-btn-danger        { background: #fde8e8; color: #8b1a1a; border-color: #f5b8b8; }
    .vf-btn-danger:hover  { background: #fbd0d0; }

    .vf-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: #aaa;
        background: white;
        border: 1px dashed #d5c9c0;
        border-radius: 12px;
    }

    .vf-empty i { font-size: 3rem; color: #d5c9c0; margin-bottom: 1rem; display: block; }
    .vf-empty p { font-size: 14px; }

    /* Alert flash */
    .vf-alert {
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 13px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .vf-alert.success { background: #d6f5e3; color: #1a6b3a; }
    .vf-alert.danger  { background: #fde8e8; color: #8b1a1a; }
</style>

<div class="vf-wrap">
    <div class="vf-title">Verifikasi Artikel</div>
    <div class="vf-sub">Tinjau dan verifikasi artikel yang dikirimkan oleh pengguna</div>

    <!-- Flash message -->
    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'approved'): ?>
            <div class="vf-alert success"><i class="fas fa-check-circle"></i> Artikel berhasil diverifikasi dan diterbitkan.</div>
        <?php elseif ($_GET['status'] === 'rejected'): ?>
            <div class="vf-alert danger"><i class="fas fa-times-circle"></i> Artikel berhasil ditolak dan user telah diberitahu.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Kartu ringkasan -->
    <div class="vf-stats">
        <div class="vf-stat-card">
            <div class="vf-stat-label">Menunggu Verifikasi</div>
            <div class="vf-stat-val amber"><?= $r_menunggu ?></div>
        </div>
        <div class="vf-stat-card">
            <div class="vf-stat-label">Sudah Diverifikasi</div>
            <div class="vf-stat-val green"><?= $r_verified ?></div>
        </div>
    </div>

    <!-- Daftar artikel pending -->
    <div class="vf-list">
        <?php if (empty($list_pending)): ?>
            <div class="vf-empty">
                <i class="fas fa-check-circle"></i>
                <p>Tidak ada artikel yang menunggu verifikasi saat ini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($list_pending as $art):
                $tgl = date('d M Y', strtotime($art['created_at']));
                $kat = ucfirst($art['kategori'] ?? '-');
                $pengirim = $art['nama_pengirim'] ?? ($art['penulis'] ?? 'Tidak diketahui');
            ?>
            <div class="vf-card">
                <div class="vf-card-header">
                    <i class="fas fa-bell"></i>
                    Permintaan verifikasi artikel baru
                </div>
                <div class="vf-rows">
                    <span class="vf-key">Judul</span>
                    <span class="vf-val"><?= htmlspecialchars($art['judul']) ?></span>
                    <span class="vf-key">Pengirim</span>
                    <span class="vf-val"><?= htmlspecialchars($pengirim) ?></span>
                    <span class="vf-key">Kategori</span>
                    <span class="vf-val"><?= $kat ?></span>
                    <span class="vf-key">Tanggal</span>
                    <span class="vf-val"><?= $tgl ?></span>
                </div>
                <div class="vf-btn-row">
                    <a href="detail_verifikasi.php?id=<?= $art['id'] ?>" class="vf-btn">
                        <i class="fas fa-eye"></i> Lihat Detail
                    </a>
                    <a href="aksi_verifikasi.php?id=<?= $art['id'] ?>&aksi=approve"
                       class="vf-btn vf-btn-success"
                       onclick="return confirm('Verifikasi dan terbitkan artikel ini?')">
                        <i class="fas fa-check"></i> Verifikasi
                    </a>
                    <a href="#" class="vf-btn vf-btn-danger"
                       onclick="return confirmTolak(<?= $art['id'] ?>)">
                        <i class="fas fa-times"></i> Tolak
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmTolak(id) {
    var catatan = prompt('Masukkan catatan penolakan untuk user (wajib):');
    if (catatan === null) return false;
    catatan = catatan.trim();
    if (catatan === '') {
        alert('Catatan tidak boleh kosong.');
        return false;
    }
    window.location.href = 'aksi_verifikasi.php?id=' + id + '&aksi=reject&catatan=' + encodeURIComponent(catatan);
    return false;
}
</script>