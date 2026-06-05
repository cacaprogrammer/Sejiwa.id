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

require_once 'koneksi.php';

$user_id = $_SESSION['id'];
$pesan   = '';
$tipe    = '';

// ─── PROSES HAPUS ARTIKEL (OTOMATIS) ───
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus' && isset($_GET['id'])) {
    $id_hapus = (int)$_GET['id'];
    
    // Pastikan artikel yang dihapus adalah milik user yang sedang login (keamanan)
    $stmt_cek = $conn->prepare("SELECT thumbnail FROM tb_artikel WHERE id = ? AND created_by = ?");
    $stmt_cek->bind_param("ii", $id_hapus, $user_id);
    $stmt_cek->execute();
    $res_cek = $stmt_cek->get_result();

    if ($res_cek->num_rows > 0) {
        $data_art = $res_cek->fetch_assoc();
        
        // Hapus file gambar thumbnail fisik jika ada
        if (!empty($data_art['thumbnail']) && file_exists(__DIR__ . '/' . $data_art['thumbnail'])) {
            unlink(__DIR__ . '/' . $data_art['thumbnail']);
        }
        
        // Jalankan query delete
        $stmt_del = $conn->prepare("DELETE FROM tb_artikel WHERE id = ? AND created_by = ?");
        $stmt_del->bind_param("ii", $id_hapus, $user_id);
        
        if ($stmt_del->execute()) {
            header("Location: history.php?tab=artikel&hapus_sukses=1");
            exit();
        } else {
            $pesan = "Gagal menghapus artikel dari database.";
            $tipe  = "error";
        }
    } else {
        $pesan = "Artikel tidak ditemukan atau Anda tidak memiliki akses.";
        $tipe  = "error";
    }
}

// Ambil kategori dari DB
$daftar_kategori = $conn->query(
    "SELECT id_kategori, nama_kategori, slug_kategori FROM tb_kategori ORDER BY nama_kategori ASC"
)->fetch_all(MYSQLI_ASSOC);

// Jika kategori dari URL (sejarah/biografi), preselect
$kategori_url = isset($_GET['kategori']) ? strtolower(trim($_GET['kategori'])) : '';

function buatSlug($judul) {
    $slug = strtolower(trim($judul));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return $slug;
}

function uploadGambar($file) {
    $upload_dir = __DIR__ . '/';
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) return ['error' => 'Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.'];
    if ($file['size'] > 5 * 1024 * 1024) return ['error' => 'Ukuran gambar maksimal 5MB.'];
    $nama_baru = 'artikel_' . time() . '_' . rand(100, 999) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $nama_baru)) return ['error' => 'Gagal upload gambar.'];
    return ['nama' => $nama_baru];
}

// ─── PROSES SUBMIT TAMBAH ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    $judul       = trim($_POST['judul']);
    $id_kategori = (int)$_POST['id_kategori'];
    $konten      = trim($_POST['konten']);
    $penulis     = htmlspecialchars($_SESSION['nama_lengkap']);
    $status      = 'pending';

    $q_kat = $conn->prepare("SELECT slug_kategori FROM tb_kategori WHERE id_kategori = ?");
    $q_kat->bind_param("i", $id_kategori);
    $q_kat->execute();
    $row_kat  = $q_kat->get_result()->fetch_assoc();
    $kategori = $row_kat ? $row_kat['slug_kategori'] : '';

    if (strtolower($kategori) === 'biografi') {
        $preview = json_encode([
            'lahir'        => trim($_POST['bio_lahir']     ?? ''),
            'meninggal'    => trim($_POST['bio_meninggal'] ?? ''),
            'pekerjaan'    => trim($_POST['bio_pekerjaan'] ?? ''),
            'teks_preview' => trim($_POST['preview']       ?? ''),
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $preview = trim($_POST['preview'] ?? '');
    }

    $slug = buatSlug($judul);
    $cek  = $conn->prepare("SELECT id FROM tb_artikel WHERE slug = ?");
    $cek->bind_param("s", $slug);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) $slug = $slug . '-' . time();

    $thumbnail = '';
    if (!empty($_FILES['thumbnail']['name'])) {
        $hasil = uploadGambar($_FILES['thumbnail']);
        if (isset($hasil['error'])) { $pesan = $hasil['error']; $tipe = 'error'; }
        else $thumbnail = $hasil['nama'];
    }

    if ($tipe !== 'error') {
        if (empty($judul) || empty($konten) || empty($id_kategori)) {
            $pesan = 'Judul, kategori, dan isi artikel wajib diisi.';
            $tipe  = 'error';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO tb_artikel 
                 (judul, slug, konten, preview, penulis, thumbnail, kategori, id_kategori, status, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param("sssssssisi",
                $judul, $slug, $konten, $preview, $penulis,
                $thumbnail, $kategori, $id_kategori, $status, $user_id
            );

            if ($stmt->execute()) {
                $notif_pesan = "Artikel baru '{$judul}' dikirim oleh {$penulis} dan menunggu verifikasi.";
                $notif_q = $conn->prepare(
                    "INSERT INTO tb_notifikasi (user_id, pesan, tipe, created_at) VALUES (1, ?, 'artikel_baru', NOW())"
                );
                if ($notif_q) {
                    $notif_q->bind_param("s", $notif_pesan);
                    $notif_q->execute();
                }

                header("Location: history.php?tab=artikel&sukses=1");
                exit();
            } else {
                $pesan = 'Gagal menyimpan artikel: ' . $stmt->error;
                $tipe  = 'error';
            }
        }
    }
}

// Ambil jumlah notifikasi belum dibaca
$stmt_unread = $conn->prepare("SELECT COUNT(*) AS total FROM tb_notifikasi WHERE user_id = ? AND dibaca = 0");
$stmt_unread->bind_param("i", $user_id);
$stmt_unread->execute();
$unread_count = $stmt_unread->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Artikel — Sejiwa.id</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
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
            --surface-2: #fdfcf6;
            --text-primary:   #2a1508;
            --text-secondary: #4A2C18;
            --text-muted:     #7B4F2C;
            --text-disabled:  #AD8D77;
            --green-bg:   #ecfdf5; --green-text: #065f46; --green-border: #a7f3d0;
            --red-bg:     #fff1f2; --red-text:   #be123c; --red-border:   #fda4af;
            --yellow-bg:  #fef9ee; --yellow-text:#7a4a00; --yellow-border:#f5d07a;
            --radius-sm: 6px; --radius-md: 10px; --radius-lg: 14px; --radius-xl: 18px;
            --shadow-sm: 0 1px 3px rgba(74,44,24,.07),0 1px 2px rgba(74,44,24,.05);
            --shadow-md: 0 4px 16px rgba(74,44,24,.10),0 2px 6px rgba(74,44,24,.05);
            --transition: all 0.2s cubic-bezier(0.4,0,0.2,1);
            --font-heading: 'Montserrat', sans-serif;
            --font-body:    'Roboto', sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-body);
            background: #f4f1ee;
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            flex-shrink: 0;
            background: #4a2c18;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            padding: 28px 20px;
            transition: transform .35s ease-in-out;
            z-index: 1000;
        }
        .sidebar-logo {
            width: 52px;
            margin-bottom: 36px;
        }
        .sidebar-nav-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            color: rgba(255,255,255,.45);
            text-transform: uppercase;
            padding: 0 6px;
            margin: 8px 0 4px;
            width: 100%;
        }
        .sidebar a {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14.5px;
            color: rgba(255,255,255,.85);
            text-decoration: none;
            padding: 11px 12px;
            margin: 2px 0;
            border-radius: 10px;
            position: relative;
            transition: background .15s;
            border-left: 3px solid transparent;
        }
        .sidebar a.active {
            background: rgba(255,255,255,.16);
            color: #fff;
            border-left-color: #f0c080;
            font-weight: 600;
        }
        .sidebar a:hover { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar a i {
            width: 18px;
            text-align: center;
            font-size: 15px;
            flex-shrink: 0;
        }
        .notif-badge {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }
        .sidebar-bottom {
            margin-top: auto;
            padding-top: 24px;
            width: 100%;
        }
        .sidebar-bottom-divider {
            height: 1px;
            background: rgba(255,255,255,.15);
            margin: 0 4px 16px;
        }

        .hamburger {
            display: none;
            position: fixed;
            top: 14px;
            right: 18px;
            font-size: 22px;
            color: #4a2c18;
            cursor: pointer;
            z-index: 1100;
            background: #fff;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
            border: none;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 150;
        }

        .main { flex: 1; padding: 2rem; min-width: 0; }

        .page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap; }
        .page-header-left h1 { font-family: var(--font-heading); font-size: 22px; font-weight: 800; color: var(--sj-darker); margin: 0 0 4px; }
        .breadcrumb-text { font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
        .breadcrumb-text a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb-text a:hover { color: var(--sj-dark); }

        .notif { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px; font-weight: 500; animation: slideDown .3s ease; box-shadow: var(--shadow-sm); }
        .notif.error  { background: var(--red-bg);  color: var(--red-text);  border: 1px solid var(--red-border); }
        .notif.sukses { background: var(--green-bg); color: var(--green-text); border: 1px solid var(--green-border); }
        .notif-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
        .notif.error  .notif-icon { background: #ffe4e6; }
        .notif.sukses .notif-icon { background: #d1fae5; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .form-card { background: white; border-radius: var(--radius-xl); box-shadow: var(--shadow-md); border: 1px solid var(--border-soft); overflow: hidden; }
        .form-card-header { background: linear-gradient(135deg, var(--sj-darker) 0%, var(--sj-dark) 55%, var(--sj-mid) 100%); padding: 20px 28px; display: flex; align-items: center; gap: 12px; }
        .header-icon { width: 38px; height: 38px; background: rgba(255,255,255,.15); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; }
        .form-card-header h2 { font-family: var(--font-heading); color: white; font-size: 17px; font-weight: 700; margin: 0; }
        .form-card-body { padding: 28px; }

        .section-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--sj-sand); margin-bottom: 14px; margin-top: 4px; padding-bottom: 8px; border-bottom: 1px solid var(--border-soft); font-family: var(--font-heading); }

        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 0; }
        .form-grid-1 { display: grid; grid-template-columns: 1fr; gap: 18px; margin-bottom: 18px; }

        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; }
        .required { color: #dc2626; margin-left: 2px; }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 10px 14px;
            border: 1.5px solid var(--border); border-radius: var(--radius-md);
            font-size: 14px; font-family: var(--font-body);
            color: var(--text-primary); background: var(--surface-2);
            transition: var(--transition); outline: none;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { border-color: var(--sj-mid); background: white; box-shadow: 0 0 0 3px rgba(123,79,44,.12); }
        .form-group input::placeholder,
        .form-group textarea::placeholder { color: var(--text-disabled); }
        .form-group textarea { resize: vertical; min-height: 100px; line-height: 1.6; }
        #konten { min-height: 280px; font-size: 13.5px; }
        .form-hint { font-size: 12px; color: var(--text-muted); margin-top: 5px; }

        .preview-img { max-width: 140px; max-height: 90px; border-radius: var(--radius-md); margin-top: 10px; object-fit: cover; border: 2px solid var(--border); box-shadow: var(--shadow-sm); display: none; }

        .bio-section {
            display: none;
            background: linear-gradient(135deg, #fdf5f0 0%, #faf0e8 100%);
            border: 1.5px solid #e8c9b0;
            border-radius: var(--radius-lg);
            padding: 20px 22px;
            margin-bottom: 18px;
        }
        .bio-section.tampil { display: block; animation: fadeInUp .25s ease; }
        .bio-section-title {
            display: flex; align-items: center; gap: 8px;
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; color: var(--sj-mid);
            margin-bottom: 14px; padding-bottom: 10px;
            border-bottom: 1px solid #e0c4a8;
            font-family: var(--font-heading);
        }
        .bio-section-title i { font-size: 16px; color: var(--sj-sand); }
        .bio-preview-card {
            display: flex; align-items: flex-start; gap: 10px;
            background: white; border: 1px solid #e0c4a8;
            border-radius: var(--radius-md); padding: 12px 14px;
            margin-top: 14px; font-size: 12.5px; color: var(--text-muted);
        }
        .bio-preview-card i { color: var(--sj-sand); font-size: 15px; margin-top: 1px; flex-shrink: 0; }

        .status-info { display: flex; align-items: center; gap: 10px; background: var(--yellow-bg); color: var(--yellow-text); border: 1px solid var(--yellow-border); border-radius: var(--radius-md); padding: 12px 16px; font-size: 13px; margin-bottom: 18px; }
        .status-info i { font-size: 16px; flex-shrink: 0; }

        .form-footer { display: flex; align-items: center; gap: 10px; padding-top: 8px; border-top: 1px solid var(--border-soft); margin-top: 4px; flex-wrap: wrap; }
        .btn-simpan { display: inline-flex; align-items: center; gap: 8px; background: var(--sj-dark); color: white; padding: 11px 24px; border-radius: var(--radius-md); font-size: 14px; font-weight: 700; font-family: var(--font-heading); border: none; cursor: pointer; transition: var(--transition); box-shadow: 0 2px 8px rgba(74,44,24,.22); }
        .btn-simpan:hover { background: var(--sj-darker); transform: translateY(-1px); }
        .btn-batal { display: inline-flex; align-items: center; gap: 6px; background: var(--brown-100); color: var(--sj-dark); padding: 11px 20px; border-radius: var(--radius-md); font-size: 14px; font-weight: 600; font-family: var(--font-heading); text-decoration: none; border: 1px solid var(--brown-200); transition: var(--transition); }
        .btn-batal:hover { background: var(--brown-200); color: var(--sj-darker); text-decoration: none; }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 900px) {
            .hamburger { display: flex; }
            .sidebar { position: fixed; top: 0; left: 0; transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .main { padding: 1.25rem; padding-top: 60px; }
            .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
            .form-card-body { padding: 18px; }
        }
    </style>
</head>
<body>

    <button class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <img src="logobenar.png" class="sidebar-logo" alt="Logo">
        <div class="sidebar-nav-label">Menu</div>
        <a href="history.php?tab=profil"><i class="fas fa-user"></i> Profil &amp; Riwayat</a>
        <a href="history.php?tab=artikel" class="active"><i class="fas fa-pen-to-square"></i> Kelola Artikel</a>
        <a href="history.php?tab=notifikasi">
            <i class="fas fa-bell"></i> Notifikasi
            <?php if ($unread_count > 0): ?>
                <span class="notif-badge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
            <?php endif; ?>
        </a>
        <div class="sidebar-bottom">
            <div class="sidebar-bottom-divider"></div>
            <a href="landingpagepilihanfix.php" style="color:rgba(255,255,255,.6);"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
        </div>
    </aside>

    <main class="main">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Tambah Artikel Baru</h1>
                <div class="breadcrumb-text">
                    <i class="fas fa-home" style="font-size:12px"></i>
                    <a href="history.php?tab=artikel">Kelola Artikel</a>
                    <i class="fas fa-chevron-right" style="font-size:11px"></i>
                    <span style="color:var(--sj-mid);font-weight:600;">Tambah Artikel</span>
                </div>
            </div>
        </div>

        <?php if ($pesan): ?>
        <div class="notif <?= $tipe ?>" id="notif-box">
            <div class="notif-icon">
                <?php if ($tipe === 'sukses'): ?>
                    <i class="fas fa-check" style="color:var(--green-text)"></i>
                <?php else: ?>
                    <i class="fas fa-times" style="color:var(--red-text)"></i>
                <?php endif; ?>
            </div>
            <span><?= htmlspecialchars($pesan) ?></span>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-card-header">
                <div class="header-icon"><i class="fas fa-file-plus"></i></div>
                <h2>Artikel Baru</h2>
            </div>
            <div class="form-card-body">
                <div class="status-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Artikel yang Anda kirim akan berstatus <strong>Menunggu Verifikasi</strong> dan akan ditinjau oleh admin sebelum ditayangkan.</span>
                </div>

                <form method="post" enctype="multipart/form-data" action="tambah_artikel.php<?= $kategori_url ? '?kategori=' . urlencode($kategori_url) : '' ?>">
                    <input type="hidden" name="aksi" value="tambah">
                    <div class="section-label">Informasi Dasar</div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Judul Artikel <span class="required">*</span></label>
                            <input type="text" name="judul" required placeholder="Masukkan judul artikel..." value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Penulis</label>
                            <input type="text" value="<?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '') ?>" readonly style="background:#f0eae1;cursor:not-allowed;">
                            <div class="form-hint">Nama penulis dikunci sesuai akun login.</div>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Kategori <span class="required">*</span></label>
                            <select name="id_kategori" id="select_kategori" required onchange="cekBiografi(this.value)">
                                <option value="">— Pilih Kategori —</option>
                                <?php foreach ($daftar_kategori as $kat): 
                                    $selected = ((!empty($kategori_url) && strtolower($kat['slug_kategori']) === $kategori_url) || (isset($_POST['id_kategori']) && $_POST['id_kategori'] == $kat['id_kategori'])) ? 'selected' : '';
                                ?>
                                <option value="<?= $kat['id_kategori'] ?>" data-slug="<?= htmlspecialchars($kat['slug_kategori']) ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <input type="text" value="Menunggu Verifikasi (Pending)" readonly style="background:#fef9ee;color:#7a4a00;cursor:not-allowed;font-weight:600;">
                            <div class="form-hint">Status ditentukan secara otomatis.</div>
                        </div>
                    </div>

                    <div class="bio-section" id="bio-section">
                        <div class="bio-section-title"><i class="fas fa-user-circle"></i> Info Pribadi Tokoh — ditampilkan di card biografi</div>
                        <div class="form-grid-3">
                            <div class="form-group">
                                <label>Tanggal Lahir</label>
                                <input type="text" name="bio_lahir" placeholder="cth: 2 Mei 1889" value="<?= htmlspecialchars($_POST['bio_lahir'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Tanggal Meninggal</label>
                                <input type="text" name="bio_meninggal" placeholder="cth: 26 April 1959 (umur 69)" value="<?= htmlspecialchars($_POST['bio_meninggal'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Pekerjaan / Profesi</label>
                                <input type="text" name="bio_pekerjaan" placeholder="cth: Pendidik, Pahlawan Nasional" value="<?= htmlspecialchars($_POST['bio_pekerjaan'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="bio-preview-card">
                            <i class="fas fa-circle-info"></i>
                            <span>Data ini ditampilkan di card <strong>Info Pribadi</strong> pada halaman detail artikel biografi. Kosongkan field yang tidak diketahui — akan tampil sebagai tanda <strong>—</strong>.</span>
                        </div>
                    </div>

                    <div class="section-label" style="margin-top:10px;">Konten &amp; Media</div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Cover / Thumbnail</label>
                            <input type="file" name="thumbnail" accept="image/*" onchange="previewGambar(this)">
                            <div class="form-hint">Format: JPG, PNG, GIF, WebP. Maks. 5MB.</div>
                            <img id="preview-img" class="preview-img" alt="Preview">
                        </div>
                        <div class="form-group">
                            <label>Teks Preview</label>
                            <textarea name="preview" placeholder="Ringkasan singkat artikel (maks. 3 kalimat)..."><?= htmlspecialchars($_POST['preview'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-grid-1">
                        <div class="form-group">
                            <label>Isi Artikel Lengkap <span class="required">*</span></label>
                            <div class="form-hint" style="margin-bottom:6px;">Gunakan tag &lt;p&gt; untuk setiap paragraf. Contoh: &lt;p&gt;Isi paragraf...&lt;/p&gt;</div>
                            <textarea name="konten" id="konten" required placeholder="&lt;p&gt;Paragraf pertama...&lt;/p&gt;&#10;&lt;p&gt;Paragraf kedua...&lt;/p&gt;"><?= htmlspecialchars($_POST['konten'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn-simpan"><i class="fas fa-paper-plane"></i> Kirim Artikel</button>
                        <a href="history.php?tab=artikel" class="btn-batal"><i class="fas fa-arrow-left"></i> Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function previewGambar(input) {
            const preview = document.getElementById('preview-img');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function cekBiografi(id_kategori) {
            const sel  = document.getElementById('select_kategori');
            const opt  = sel ? sel.options[sel.selectedIndex] : null;
            const slug = opt ? (opt.dataset.slug || '').toLowerCase() : '';
            const bioSection = document.getElementById('bio-section');
            if (slug === 'biografi') { bioSection.classList.add('tampil'); } 
            else { bioSection.classList.remove('tampil'); }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const sel = document.getElementById('select_kategori');
            if (sel) cekBiografi(sel.value);
        });

        const sidebar        = document.getElementById('sidebar');
        const hamburgerBtn   = document.getElementById('hamburgerBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        hamburgerBtn?.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('open');
        });

        sidebarOverlay?.addEventListener('click', () => {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('open');
        });
    </script>
</body>
</html>