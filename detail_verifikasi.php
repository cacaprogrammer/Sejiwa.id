<?php
// detail_verifikasi.php — Halaman detail artikel yang menunggu verifikasi (Admin)
include "cek_admin.php";
include "koneksi.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header("Location: dashboardAdmin.php?page=verifikasi");
    exit();
}

// Ambil data artikel + nama pengirim dari tb_user
$stmt = $conn->prepare("
    SELECT a.*, 
           k.nama_kategori,
           u.nama_lengkap AS nama_pengirim,
           u.username     AS username_pengirim
    FROM tb_artikel a
    LEFT JOIN tb_kategori k ON k.id_kategori = a.id_kategori
    LEFT JOIN tb_user     u ON u.id          = a.created_by
    WHERE a.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$artikel = $stmt->get_result()->fetch_assoc();

if (!$artikel) {
    header("Location: dashboardAdmin.php?page=verifikasi");
    exit();
}

// Hanya tampilkan artikel yang masih pending
// (opsional: hapus baris ini jika admin boleh melihat semua status)
// if ($artikel['status'] !== 'pending') {
//     header("Location: dashboardAdmin.php?page=verifikasi");
//     exit();
// }

// Path gambar — gambar disimpan di folder uploads/ di root project
// Sama dengan logika thumbSrc() di detail_artikel.php
function getImgSrc($thumb) {
    if (empty($thumb)) return '';
    if (file_exists(__DIR__ . '/uploads/' . $thumb)) return 'uploads/' . $thumb;
    return $thumb; // fallback jika path sudah lengkap
}

function statusLabel($status) {
    return match($status) {
        'pending'   => ['label' => 'Menunggu Verifikasi', 'bg' => '#fff3d6', 'color' => '#7a5200'],
        'published' => ['label' => 'Diterbitkan',         'bg' => '#d6f5e3', 'color' => '#1a6b3a'],
        'rejected'  => ['label' => 'Ditolak',             'bg' => '#fde8e8', 'color' => '#8b1a1a'],
        'draft'     => ['label' => 'Draft',               'bg' => '#f0f0f0', 'color' => '#555'],
        default     => ['label' => ucfirst($status),      'bg' => '#f0f0f0', 'color' => '#555'],
    };
}

$s   = statusLabel($artikel['status']);
$tgl = date('d F Y, H:i', strtotime($artikel['created_at']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Artikel — <?= htmlspecialchars($artikel['judul']) ?> | Sejiwa Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --sj-dark:   #4a2c18;
            --sj-darker: #4D1E0A;
            --sj-mid:    #7B4F2C;
            --sj-sand:   #AD8D77;
            --brown-100: #f0e4d2;
            --brown-200: #dfc8b0;
            --brown-50:  #faf5ec;
            --border:    #e2d0b8;
            --border-soft: #ede4d3;
            --text-primary:   #2a1508;
            --text-secondary: #4A2C18;
            --text-muted:     #7B4F2C;
            --green-bg:  #ecfdf5; --green-text: #065f46; --green-border: #a7f3d0;
            --red-bg:    #fff1f2; --red-text:   #be123c; --red-border:   #fda4af;
            --yellow-bg: #fef9ee; --yellow-text:#7a4a00; --yellow-border:#f5d07a;
            --radius-md: 10px; --radius-lg: 14px; --radius-xl: 18px;
            --shadow-md: 0 4px 16px rgba(74,44,24,.10),0 2px 6px rgba(74,44,24,.05);
            --font-heading: 'Montserrat', sans-serif;
            --font-body:    'Roboto', sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font-body); background: #f4f1ee; color: var(--text-primary); padding: 2rem; }

        /* ── WRAPPER ── */
        .wrap { max-width: 820px; margin: 0 auto; }

        /* ── TOP BAR ── */
        .top-bar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;
        }

        .breadcrumb {
            display: flex; align-items: center; gap: 6px;
            font-size: 13px; color: var(--text-muted);
        }

        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--sj-dark); }
        .breadcrumb i { font-size: 11px; }

        .back-btn {
            display: inline-flex; align-items: center; gap: 7px;
            background: white; color: var(--sj-dark);
            padding: 9px 16px; border-radius: var(--radius-md);
            font-size: 13.5px; font-weight: 600;
            font-family: var(--font-heading);
            border: 1.5px solid var(--border); text-decoration: none;
            transition: all .2s;
        }

        .back-btn:hover { background: var(--brown-50); border-color: var(--sj-sand); text-decoration: none; }

        /* ── CARD ── */
        .card {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-soft);
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .card-header {
            background: linear-gradient(135deg, var(--sj-darker) 0%, var(--sj-dark) 55%, var(--sj-mid) 100%);
            padding: 20px 28px;
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
        }

        .card-header-left { display: flex; align-items: center; gap: 12px; }

        .header-icon {
            width: 38px; height: 38px;
            background: rgba(255,255,255,.15);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: white; flex-shrink: 0;
        }

        .card-header h2 {
            font-family: var(--font-heading); color: white;
            font-size: 17px; font-weight: 700; margin: 0;
        }

        .card-header .sub { font-size: 12px; color: rgba(255,255,255,.65); margin-top: 2px; }

        .status-pill {
            font-size: 12px; font-weight: 700;
            padding: 5px 14px; border-radius: 20px;
            white-space: nowrap;
        }

        .card-body { padding: 28px; }

        /* ── META TABLE ── */
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .meta-table tr { border-bottom: 1px solid var(--border-soft); }
        .meta-table tr:last-child { border-bottom: none; }
        .meta-table td { padding: 10px 0; font-size: 13.5px; vertical-align: top; }
        .meta-table td:first-child {
            width: 140px; color: var(--text-muted);
            font-weight: 600; font-family: var(--font-heading);
            font-size: 12px; text-transform: uppercase; letter-spacing: .5px;
            padding-right: 16px; white-space: nowrap;
        }

        .meta-table td:last-child { color: var(--text-primary); font-weight: 500; }

        /* ── THUMBNAIL ── */
        .thumbnail-wrap {
            width: 100%; max-height: 320px; border-radius: var(--radius-lg);
            overflow: hidden; margin-bottom: 24px;
            border: 1px solid var(--border); background: var(--brown-100);
            display: flex; align-items: center; justify-content: center;
        }

        .thumbnail-wrap img { width: 100%; max-height: 320px; object-fit: cover; display: block; }
        .thumbnail-placeholder { padding: 3rem; color: var(--sj-sand); font-size: 13px; text-align: center; }
        .thumbnail-placeholder i { font-size: 3rem; display: block; margin-bottom: 8px; color: var(--brown-200); }

        /* ── PREVIEW ── */
        .section-label {
            font-size: 10.5px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; color: var(--sj-sand);
            margin-bottom: 10px; padding-bottom: 8px;
            border-bottom: 1px solid var(--border-soft);
            font-family: var(--font-heading);
        }

        .preview-box {
            background: var(--brown-50); border: 1px solid var(--border);
            border-radius: var(--radius-md); padding: 14px 16px;
            font-size: 14px; line-height: 1.7; color: var(--text-muted);
            font-style: italic; margin-bottom: 24px;
        }

        /* ── KONTEN ── */
        .konten-box {
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-md);
            padding: 20px 22px;
            font-size: 14.5px; line-height: 1.85;
            color: var(--text-primary);
            max-height: 500px; overflow-y: auto;
        }

        .konten-box p { margin-bottom: 1em; }
        .konten-box p:last-child { margin-bottom: 0; }

        /* ── CATATAN ADMIN ── */
        .catatan-box {
            background: var(--yellow-bg); border: 1px solid var(--yellow-border);
            border-radius: var(--radius-md); padding: 14px 16px;
            font-size: 13.5px; color: var(--yellow-text); margin-bottom: 24px;
            display: flex; align-items: flex-start; gap: 10px;
        }

        .catatan-box i { margin-top: 2px; flex-shrink: 0; }

        /* ── AKSI CARD ── */
        .aksi-card {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-soft);
            padding: 20px 28px;
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap;
        }

        .aksi-info { font-size: 13.5px; color: var(--text-muted); }
        .aksi-info strong { color: var(--sj-dark); }

        .aksi-btn-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 20px; border-radius: var(--radius-md);
            font-size: 14px; font-weight: 700; font-family: var(--font-heading);
            cursor: pointer; border: none; transition: all .2s;
            text-decoration: none; white-space: nowrap;
        }

        .btn-approve {
            background: var(--green-bg); color: var(--green-text);
            border: 1.5px solid var(--green-border);
        }
        .btn-approve:hover { background: #d1fae5; transform: translateY(-1px); }

        .btn-reject {
            background: var(--red-bg); color: var(--red-text);
            border: 1.5px solid var(--red-border);
        }
        .btn-reject:hover { background: #ffe4e6; transform: translateY(-1px); }

        /* ── MODAL TOLAK ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.5); z-index: 500;
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }

        .modal {
            background: white; border-radius: var(--radius-xl);
            padding: 28px; width: 100%; max-width: 440px;
            box-shadow: 0 20px 60px rgba(0,0,0,.2);
            animation: popIn .2s ease;
        }

        @keyframes popIn { from { opacity:0; transform:scale(.95); } to { opacity:1; transform:scale(1); } }

        .modal h3 {
            font-family: var(--font-heading); font-size: 17px; font-weight: 700;
            color: var(--sj-darker); margin-bottom: 6px;
        }

        .modal p { font-size: 13.5px; color: var(--text-muted); margin-bottom: 16px; }

        .modal textarea {
            width: 100%; padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 14px; font-family: var(--font-body);
            resize: vertical; min-height: 100px; outline: none;
            transition: all .2s;
        }

        .modal textarea:focus { border-color: var(--sj-mid); box-shadow: 0 0 0 3px rgba(123,79,44,.12); }

        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; }

        .btn-modal-batal {
            padding: 9px 18px; border-radius: var(--radius-md);
            background: var(--brown-100); color: var(--sj-dark);
            border: 1px solid var(--brown-200); font-size: 13.5px;
            font-weight: 600; cursor: pointer; transition: all .2s;
        }
        .btn-modal-batal:hover { background: var(--brown-200); }

        .btn-modal-tolak {
            padding: 9px 18px; border-radius: var(--radius-md);
            background: var(--red-bg); color: var(--red-text);
            border: 1.5px solid var(--red-border); font-size: 13.5px;
            font-weight: 700; font-family: var(--font-heading);
            cursor: pointer; transition: all .2s;
        }
        .btn-modal-tolak:hover { background: #ffe4e6; }

        @media (max-width: 600px) {
            body { padding: 1rem; }
            .card-body { padding: 18px; }
            .aksi-card { padding: 16px 18px; }
            .top-bar { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<div class="wrap">

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="breadcrumb">
            <i class="fas fa-home"></i>
            <a href="dashboardAdmin.php">Dashboard</a>
            <i class="fas fa-chevron-right"></i>
            <a href="dashboardAdmin.php?page=verifikasi">Verifikasi Artikel</a>
            <i class="fas fa-chevron-right"></i>
            <span style="color:var(--sj-mid);font-weight:600;">Detail Artikel</span>
        </div>
        <a href="dashboardAdmin.php?page=verifikasi" class="back-btn">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Detail Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="header-icon"><i class="fas fa-file-alt"></i></div>
                <div>
                    <h2><?= htmlspecialchars($artikel['judul']) ?></h2>
                    <div class="sub">Dikirim pada <?= $tgl ?></div>
                </div>
            </div>
            <span class="status-pill"
                  style="background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>">
                <?= $s['label'] ?>
            </span>
        </div>

        <div class="card-body">

            <!-- Catatan admin jika ditolak -->
            <?php if ($artikel['status'] === 'rejected' && !empty($artikel['catatan_admin'])): ?>
            <div class="catatan-box">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Catatan Penolakan:</strong><br>
                    <?= htmlspecialchars($artikel['catatan_admin']) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Thumbnail -->
            <div class="thumbnail-wrap">
                <?php if (!empty($artikel['thumbnail'])): ?>
                    <img src="<?= htmlspecialchars(getImgSrc($artikel['thumbnail'])) ?>"
                         alt="Cover <?= htmlspecialchars($artikel['judul']) ?>"
                         onerror="this.parentElement.innerHTML='<div class=\'thumbnail-placeholder\'><i class=\'fas fa-image\'></i>Gambar tidak dapat ditampilkan.</div>'">
                <?php else: ?>
                    <div class="thumbnail-placeholder">
                        <i class="fas fa-image"></i>
                        Tidak ada gambar cover.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Metadata -->
            <table class="meta-table">
                <tr>
                    <td>Judul</td>
                    <td><?= htmlspecialchars($artikel['judul']) ?></td>
                </tr>
                <tr>
                    <td>Penulis</td>
                    <td><?= htmlspecialchars($artikel['penulis']) ?></td>
                </tr>
                <tr>
                    <td>Pengirim</td>
                    <td>
                        <?= htmlspecialchars($artikel['nama_pengirim'] ?? '—') ?>
                        <?php if (!empty($artikel['username_pengirim'])): ?>
                            <span style="color:var(--text-muted);font-size:12px;">
                                (@<?= htmlspecialchars($artikel['username_pengirim']) ?>)
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Kategori</td>
                    <td><?= htmlspecialchars($artikel['nama_kategori'] ?? ucfirst($artikel['kategori'] ?? '—')) ?></td>
                </tr>
                <tr>
                    <td>Tanggal Kirim</td>
                    <td><?= $tgl ?></td>
                </tr>
                <?php if (!empty($artikel['published_at'])): ?>
                <tr>
                    <td>Diterbitkan</td>
                    <td><?= date('d F Y, H:i', strtotime($artikel['published_at'])) ?></td>
                </tr>
                <?php endif; ?>
            </table>

            <!-- Preview / Ringkasan -->
            <?php if (!empty($artikel['preview'])): ?>
            <div class="section-label">Ringkasan</div>
            <div class="preview-box"><?= htmlspecialchars($artikel['preview']) ?></div>
            <?php endif; ?>

            <!-- Isi Artikel -->
            <div class="section-label">Isi Artikel</div>
            <div class="konten-box">
                <?php
                $konten = $artikel['konten'];
                // Jika konten tidak mengandung tag HTML, bungkus tiap baris dengan <p>
                if (strip_tags($konten) === $konten) {
                    $paragraf = array_filter(explode("\n", $konten));
                    foreach ($paragraf as $p) {
                        echo '<p>' . htmlspecialchars(trim($p)) . '</p>';
                    }
                } else {
                    // Sudah berisi HTML, tampilkan langsung (hati-hati XSS — tambahkan HTML purifier jika perlu)
                    echo $konten;
                }
                ?>
            </div>

        </div>
    </div>

    <!-- Aksi (hanya tampil jika masih pending) -->
    <?php if ($artikel['status'] === 'pending'): ?>
    <div class="aksi-card">
        <div class="aksi-info">
            Artikel dari <strong><?= htmlspecialchars($artikel['nama_pengirim'] ?? $artikel['penulis']) ?></strong>
            menunggu keputusan Anda.
        </div>
        <div class="aksi-btn-group">
            <button class="btn btn-approve" onclick="konfirmasiVerifikasi(<?= $artikel['id'] ?>)">
                <i class="fas fa-check"></i> Verifikasi & Terbitkan
            </button>
            <button class="btn btn-reject" onclick="bukaModalTolak(<?= $artikel['id'] ?>)">
                <i class="fas fa-times"></i> Tolak
            </button>
        </div>
    </div>
    <?php elseif ($artikel['status'] === 'published'): ?>
    <div class="aksi-card">
        <div class="aksi-info">
            Artikel ini sudah <strong>diterbitkan</strong> dan dapat dilihat di website.
        </div>
        <a href="detail_artikel.php?slug=<?= urlencode($artikel['slug']) ?>"
           class="btn" target="_blank"
           style="background:var(--green-bg);color:var(--green-text);border:1.5px solid var(--green-border)">
            <i class="fas fa-eye"></i> Lihat di Website
        </a>
    </div>
    <?php endif; ?>

</div>

<!-- Modal Tolak -->
<div class="modal-overlay" id="modalTolak">
    <div class="modal">
        <h3><i class="fas fa-times-circle" style="color:#be123c;margin-right:8px"></i>Tolak Artikel</h3>
        <p>Berikan catatan kepada penulis agar mereka dapat memperbaiki artikelnya.</p>
        <textarea id="catatanInput" placeholder="Contoh: Konten kurang lengkap, mohon tambahkan sumber referensi..."></textarea>
        <div class="modal-footer">
            <button class="btn-modal-batal" onclick="tutupModal()">Batal</button>
            <button class="btn-modal-tolak" onclick="kirimTolak()">
                <i class="fas fa-times"></i> Konfirmasi Tolak
            </button>
        </div>
    </div>
</div>

<script>
    let artikelIdPending = 0;

    function konfirmasiVerifikasi(id) {
        if (confirm('Yakin ingin memverifikasi dan menerbitkan artikel ini?')) {
            window.location.href = 'aksi_verifikasi.php?aksi=approve&id=' + id + '&redirect=detail&artikel_id=' + id;
        }
    }

    function bukaModalTolak(id) {
        artikelIdPending = id;
        document.getElementById('catatanInput').value = '';
        document.getElementById('modalTolak').classList.add('open');
    }

    function tutupModal() {
        document.getElementById('modalTolak').classList.remove('open');
    }

    function kirimTolak() {
        const catatan = document.getElementById('catatanInput').value.trim();
        if (!catatan) {
            alert('Mohon isi catatan penolakan untuk penulis.');
            return;
        }
        window.location.href = 'aksi_verifikasi.php?aksi=reject&id=' + artikelIdPending
            + '&catatan=' + encodeURIComponent(catatan)
            + '&redirect=verifikasi';
    }

    // Tutup modal saat klik overlay
    document.getElementById('modalTolak').addEventListener('click', function(e) {
        if (e.target === this) tutupModal();
    });
</script>
</body>
</html>