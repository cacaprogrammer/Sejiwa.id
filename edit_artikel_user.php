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

$cek = $conn->prepare("SELECT * FROM tb_artikel WHERE id = ? AND created_by = ?");
$cek->bind_param("ii", $id_artikel, $user_id);
$cek->execute();
$data_edit = $cek->get_result()->fetch_assoc();

if (!$data_edit) {
    header("Location: history.php?tab=artikel");
    exit();
}

if ($data_edit['status'] === 'published') {
    header("Location: history.php?tab=artikel");
    exit();
}

$pesan = '';
$tipe  = '';

$daftar_kategori = [];
$res_kat = $conn->query("SELECT id_kategori, nama_kategori, slug_kategori FROM tb_kategori ORDER BY nama_kategori ASC");
if ($res_kat) {
    while ($row = $res_kat->fetch_assoc()) $daftar_kategori[] = $row;
}

function buatSlug($judul) {
    $slug = strtolower(trim($judul));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return $slug;
}

function uploadGambar($file, $thumbnail_lama = '') {
    $upload_dir = __DIR__ . '/';
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) return ['error' => 'Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.'];
    if ($file['size'] > 5 * 1024 * 1024) return ['error' => 'Ukuran gambar maksimal 5MB.'];
    $nama_baru = 'artikel_' . time() . '_' . rand(100, 999) . '.' . $ext;
    $path_tuju = $upload_dir . $nama_baru;
    if (!move_uploaded_file($file['tmp_name'], $path_tuju)) return ['error' => 'Gagal upload gambar.'];
    if (!empty($thumbnail_lama) && strpos($thumbnail_lama, 'artikel_') === 0) {
        $path_lama = $upload_dir . $thumbnail_lama;
        if (file_exists($path_lama)) unlink($path_lama);
    }
    return ['nama' => $nama_baru];
}

$bio_edit = ['lahir' => '', 'meninggal' => '', 'pekerjaan' => ''];
$preview_teks_edit = '';
if (!empty($data_edit['preview'])) {
    $decoded = json_decode($data_edit['preview'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $bio_edit['lahir']     = $decoded['lahir']        ?? '';
        $bio_edit['meninggal'] = $decoded['meninggal']    ?? '';
        $bio_edit['pekerjaan'] = $decoded['pekerjaan']    ?? '';
        $preview_teks_edit     = $decoded['teks_preview'] ?? '';
    } else {
        $preview_teks_edit = $data_edit['preview'];
    }
}

$status_saat_ini = $data_edit['status'];

// ═══════════════════════════════════════════════════════════
//  PROSES SIMPAN & AJUKAN ULANG
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'edit') {

    $judul       = trim($_POST['judul']);
    $id_kategori = (int)$_POST['id_kategori'];
    $konten      = trim($_POST['konten']);

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

    $thumbnail = $data_edit['thumbnail'];
    if (!empty($_FILES['thumbnail']['name'])) {
        $hasil = uploadGambar($_FILES['thumbnail'], $thumbnail);
        if (isset($hasil['error'])) { $pesan = $hasil['error']; $tipe = 'error'; }
        else $thumbnail = $hasil['nama'];
    }

    if ($tipe !== 'error') {
        // Selalu set status ke pending (ajukan ulang) — tidak ada opsi draft
        $stmt = $conn->prepare(
            "UPDATE tb_artikel 
             SET judul=?, konten=?, preview=?, thumbnail=?, kategori=?, id_kategori=?, status='pending', catatan_admin=NULL
             WHERE id=? AND created_by=?"
        );
        $stmt->bind_param("sssssiii", $judul, $konten, $preview, $thumbnail, $kategori, $id_kategori, $id_artikel, $user_id);

        if ($stmt->execute()) {
            header("Location: history.php?tab=artikel&sukses=ajukan");
            exit();
        } else {
            $pesan = 'Gagal menyimpan perubahan: ' . $stmt->error;
            $tipe  = 'error';
        }
    }

    $cek2 = $conn->prepare("SELECT * FROM tb_artikel WHERE id = ? AND created_by = ?");
    $cek2->bind_param("ii", $id_artikel, $user_id);
    $cek2->execute();
    $data_edit = $cek2->get_result()->fetch_assoc();
}

function statusLabel($s) {
    return match($s) {
        'pending'   => ['label' => 'Menunggu Verifikasi', 'class' => 'pill-menunggu'],
        'published' => ['label' => 'Diterbitkan',         'class' => 'pill-published'],
        'rejected'  => ['label' => 'Ditolak',             'class' => 'pill-ditolak'],
        'draft'     => ['label' => 'Draft',               'class' => 'pill-draft'],
        default     => ['label' => ucfirst($s),           'class' => 'pill-draft'],
    };
}
$s_label = statusLabel($status_saat_ini);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Artikel — Sejiwa.id</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --sj-dark:    #4a2c18;
      --sj-darker:  #4D1E0A;
      --sj-mid:     #7B4F2C;
      --sj-gold:    #D99B3E;
      --sj-sand:    #AD8D77;
      --brown-100:  #f0e4d2;
      --brown-200:  #dfc8b0;
      --brown-50:   #faf5ec;
      --border:     #e2d0b8;
      --border-soft:#ede4d3;
      --surface-2:  #fdfcf6;
      --text-primary:#2a1508;
      --text-secondary:#4A2C18;
      --text-muted: #7B4F2C;
      --text-disabled:#AD8D77;
      --shadow-md:  0 4px 16px rgba(74,44,24,.10), 0 2px 6px rgba(74,44,24,.05);
      --radius-sm:  6px;
      --radius-md:  10px;
      --radius-lg:  14px;
      --radius-xl:  18px;
      --transition: all 0.2s cubic-bezier(.4,0,.2,1);
      --green-bg:   #ecfdf5; --green-text: #065f46; --green-border: #a7f3d0;
      --red-bg:     #fff1f2; --red-text:   #be123c; --red-border:   #fda4af;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    html, body { height:100%; background:#f4f1ee; font-family:'Roboto', sans-serif; }

    /* ── SIDEBAR ── */
    .sidebar {
      position:fixed; top:0; left:0; width:240px; height:100vh;
      background:#4a2c18; padding:28px 20px; color:#fff;
      display:flex; flex-direction:column; align-items:flex-start;
      z-index:1000; transition:transform .35s ease-in-out; overflow-y:auto;
    }
    .sidebar-logo { width:52px; margin-bottom:36px; }
    .sidebar-nav-label { font-size:10px; font-weight:700; letter-spacing:.08em; color:rgba(255,255,255,.45); text-transform:uppercase; padding:0 6px; margin:8px 0 4px; }
    .sidebar a { width:100%; display:flex; align-items:center; gap:12px; font-size:14.5px; color:rgba(255,255,255,.85); text-decoration:none; padding:11px 12px; margin:2px 0; border-radius:10px; border-left:3px solid transparent; transition:background .15s; }
    .sidebar a.active { background:rgba(255,255,255,.16); color:#fff; border-left-color:#f0c080; font-weight:600; }
    .sidebar a:hover { background:rgba(255,255,255,.1); color:#fff; }
    .sidebar a i { width:18px; text-align:center; font-size:15px; flex-shrink:0; }

    /* ── HAMBURGER ── */
    .hamburger { display:none; position:fixed; top:14px; right:18px; font-size:22px; color:#4a2c18; cursor:pointer; z-index:1100; background:#fff; width:38px; height:38px; border-radius:8px; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,.15); }

    /* ── MAIN ── */
    .main-wrapper { margin-left:240px; min-height:100vh; width:calc(100% - 240px); padding:32px 40px; }

    /* ── PAGE HEADER ── */
    .page-header { background:#fff; padding:16px 22px; border-radius:12px; box-shadow:0 1px 6px rgba(0,0,0,.08); margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
    .page-header-left h3 { font-size:18px; font-weight:700; color:#2d1a0e; font-family:'Montserrat', sans-serif; }
    .breadcrumb-text { font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:5px; margin-top:3px; }
    .breadcrumb-text a { color:var(--text-muted); text-decoration:none; }
    .breadcrumb-text a:hover { color:var(--sj-dark); }

    /* ── NOTIFIKASI ── */
    .notif-box { display:flex; align-items:center; gap:12px; padding:14px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:500; font-size:14px; box-shadow:0 1px 4px rgba(0,0,0,.06); }
    .notif-box.sukses { background:var(--green-bg); color:var(--green-text); border:1px solid var(--green-border); }
    .notif-box.error  { background:var(--red-bg);   color:var(--red-text);   border:1px solid var(--red-border); }
    .notif-close { margin-left:auto; cursor:pointer; border:none; background:none; font-size:16px; color:inherit; opacity:.7; padding:4px; border-radius:4px; }
    .notif-close:hover { opacity:1; }

    /* ── STATUS PILL ── */
    .status-pill { font-size:11.5px; padding:3px 12px; border-radius:20px; font-weight:600; display:inline-flex; align-items:center; gap:5px; }
    .pill-menunggu  { background:#fff3d6; color:#7a5200; }
    .pill-published { background:#d6f5e3; color:#1a6b3a; }
    .pill-ditolak   { background:#fde8e8; color:#8b1a1a; }
    .pill-draft     { background:#f0f0f0; color:#555; }

    /* ── CATATAN ADMIN ── */
    .catatan-admin-box { display:flex; align-items:flex-start; gap:10px; background:#fff9f0; border:1.5px solid #f59e0b; border-left:4px solid #f59e0b; border-radius:var(--radius-md); padding:14px 16px; margin-bottom:22px; }
    .catatan-admin-box .icon { font-size:18px; color:#d97706; margin-top:1px; flex-shrink:0; }
    .catatan-admin-box .text { font-size:13.5px; color:#78350f; line-height:1.6; }
    .catatan-admin-box .text strong { display:block; font-size:12px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; color:#92400e; }

    /* ── FORM CARD ── */
    .form-card { background:#fff; border-radius:var(--radius-xl); box-shadow:var(--shadow-md); border:1px solid var(--border-soft); margin-bottom:24px; overflow:hidden; }
    .form-card-header { background:linear-gradient(135deg, var(--sj-darker) 0%, var(--sj-dark) 55%, var(--sj-mid) 100%); padding:20px 28px; display:flex; align-items:center; gap:12px; }
    .form-card-header .header-icon { width:38px; height:38px; background:rgba(255,255,255,.15); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; font-size:18px; color:white; }
    .form-card-header h2 { font-family:'Montserrat', sans-serif; color:white; font-size:17px; font-weight:700; margin:0; }
    .form-card-body { padding:28px; }
    .form-section-label { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--sj-sand); margin-bottom:14px; margin-top:4px; padding-bottom:8px; border-bottom:1px solid var(--border-soft); font-family:'Montserrat', sans-serif; }
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:18px; }
    .form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:18px; margin-bottom:18px; }
    .form-grid-1 { display:grid; grid-template-columns:1fr; gap:18px; margin-bottom:18px; }
    .form-group label { display:block; font-size:13px; font-weight:600; color:var(--text-secondary); margin-bottom:6px; }
    .form-group label .required { color:#dc2626; margin-left:2px; }
    .form-group input, .form-group select, .form-group textarea { width:100%; padding:10px 14px; border:1.5px solid var(--border); border-radius:var(--radius-md); font-size:14px; font-family:'Roboto', sans-serif; color:var(--text-primary); background:var(--surface-2); transition:var(--transition); outline:none; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--sj-mid); background:#fff; box-shadow:0 0 0 3px rgba(123,79,44,.12); }
    .form-group input::placeholder, .form-group textarea::placeholder { color:var(--text-disabled); }
    .form-group textarea { resize:vertical; min-height:100px; line-height:1.6; }
    #konten-textarea { min-height:300px; font-size:13.5px; }
    .form-hint { font-size:12px; color:var(--text-muted); margin-top:5px; }
    .preview-img { max-width:140px; max-height:90px; border-radius:var(--radius-md); margin-top:10px; object-fit:cover; border:2px solid var(--border); }

    /* ── BIO SECTION ── */
    .bio-section { display:none; background:linear-gradient(135deg,#fdf5f0,#faf0e8); border:1.5px solid #e8c9b0; border-radius:var(--radius-lg); padding:20px 22px; margin-bottom:18px; }
    .bio-section.tampil { display:block; }
    .bio-section-title { display:flex; align-items:center; gap:8px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--sj-mid); margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid #e0c4a8; font-family:'Montserrat',sans-serif; }
    .bio-hint { display:flex; align-items:flex-start; gap:10px; background:white; border:1px solid #e0c4a8; border-radius:var(--radius-md); padding:12px 14px; margin-top:14px; font-size:12.5px; color:var(--text-muted); }

    /* ── FOOTER BUTTONS ── */
    .form-footer { display:flex; align-items:center; gap:10px; padding-top:20px; border-top:1px solid var(--border-soft); flex-wrap:wrap; }
    .btn-ajukan { display:inline-flex; align-items:center; gap:8px; background:#16a34a; color:#fff; padding:11px 24px; border-radius:var(--radius-md); font-size:14px; font-weight:700; font-family:'Montserrat',sans-serif; border:none; cursor:pointer; transition:var(--transition); box-shadow:0 2px 8px rgba(22,163,74,.25); }
    .btn-ajukan:hover { background:#15803d; transform:translateY(-1px); }
    .btn-batal { display:inline-flex; align-items:center; gap:6px; background:var(--brown-100); color:var(--sj-dark); padding:11px 20px; border-radius:var(--radius-md); font-size:14px; font-weight:600; font-family:'Montserrat',sans-serif; text-decoration:none; border:1px solid var(--brown-200); transition:var(--transition); }
    .btn-batal:hover { background:var(--brown-200); color:var(--sj-darker); text-decoration:none; }

    /* ── INFO BOX ── */
    .info-box { display:flex; align-items:flex-start; gap:9px; background:#f0f7ff; border:1px solid #bfdbfe; border-radius:var(--radius-md); padding:12px 14px; margin-bottom:20px; font-size:13px; color:#1d4ed8; }
    .info-box i { margin-top:1px; flex-shrink:0; }

    /* ── RESPONSIVE ── */
    @media(max-width:900px){
      .hamburger { display:flex; }
      .sidebar { transform:translateX(-100%); }
      .sidebar.show { transform:translateX(0); }
      .main-wrapper { margin-left:0; width:100%; padding:20px 16px; padding-top:64px; }
      .form-grid-2, .form-grid-3 { grid-template-columns:1fr; }
    }
  </style>
</head>
<body>

<div class="hamburger" id="hamburger"><i class="fas fa-bars"></i></div>

<!-- ═══ SIDEBAR ═══ -->
<div class="sidebar" id="sidebar">
  <img src="logobenar.png" class="sidebar-logo" alt="Logo">
  <div class="sidebar-nav-label">Menu</div>
  <a href="history.php?tab=profil"><i class="fas fa-user"></i> Profil &amp; Riwayat</a>
  <a href="history.php?tab=artikel" class="active"><i class="fas fa-pen-to-square"></i> Kelola Artikel</a>
  <a href="history.php?tab=notifikasi"><i class="fas fa-bell"></i> Notifikasi</a>
  <div style="margin-top:auto;padding-top:24px;width:100%;">
    <div style="height:1px;background:rgba(255,255,255,.15);margin:0 4px 16px;"></div>
    <a href="landingpagepilihanfix.php" style="color:rgba(255,255,255,.6);">
      <i class="fas fa-arrow-left"></i> Kembali ke Beranda
    </a>
  </div>
</div>

<!-- ═══ MAIN ═══ -->
<div class="main-wrapper">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="page-header-left">
      <h3><i class="fas fa-pen-to-square" style="color:#8b3e26;margin-right:8px"></i>Edit Artikel</h3>
      <div class="breadcrumb-text">
        <a href="history.php?tab=artikel"><i class="fas fa-arrow-left"></i> Kelola Artikel</a>
        <i class="fas fa-chevron-right" style="font-size:10px"></i>
        <span style="color:var(--sj-dark);font-weight:600;">Edit: <?= htmlspecialchars(mb_strimwidth($data_edit['judul'], 0, 40, '...')) ?></span>
      </div>
    </div>
    <span class="status-pill <?= $s_label['class'] ?>">
      <i class="fas <?= $status_saat_ini === 'rejected' ? 'fa-times-circle' : 'fa-clock' ?>"></i>
      <?= $s_label['label'] ?>
    </span>
  </div>

  <?php if ($pesan): ?>
  <div class="notif-box <?= $tipe ?>" id="notif-box">
    <i class="fas <?= $tipe === 'sukses' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
    <?= htmlspecialchars($pesan) ?>
    <button class="notif-close" onclick="this.parentElement.style.display='none'">✕</button>
  </div>
  <?php endif; ?>

  <!-- Catatan admin jika ditolak -->
  <?php if ($status_saat_ini === 'rejected' && !empty($data_edit['catatan_admin'])): ?>
  <div class="catatan-admin-box">
    <i class="fas fa-exclamation-triangle icon"></i>
    <div class="text">
      <strong><i class="fas fa-shield-alt"></i> Catatan dari Admin</strong>
      <?= htmlspecialchars($data_edit['catatan_admin']) ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Info -->
  <div class="info-box">
    <i class="fas fa-info-circle"></i>
    <span>Perbaiki artikel Anda, lalu klik <strong>Simpan &amp; Ajukan Ulang</strong> untuk mengirimkan kembali ke admin untuk diverifikasi.</span>
  </div>

  <!-- ═══ FORM EDIT ═══ -->
  <div class="form-card">
    <div class="form-card-header">
      <div class="header-icon"><i class="fas fa-edit"></i></div>
      <h2>Edit Artikel</h2>
    </div>
    <div class="form-card-body">
      <form method="post" enctype="multipart/form-data" id="form-edit">
        <input type="hidden" name="aksi" value="edit">

        <div class="form-section-label">Informasi Dasar</div>

        <div class="form-grid-2">
          <div class="form-group">
            <label>Judul Artikel <span class="required">*</span></label>
            <input type="text" name="judul" required placeholder="Masukkan judul artikel..."
                   value="<?= htmlspecialchars($data_edit['judul']) ?>">
          </div>
          <div class="form-group">
            <label>Kategori <span class="required">*</span></label>
            <select name="id_kategori" id="select_kategori" required onchange="cekBiografi(this.value)">
              <option value="">— Pilih Kategori —</option>
              <?php foreach ($daftar_kategori as $kat): ?>
              <option value="<?= $kat['id_kategori'] ?>"
                      data-slug="<?= htmlspecialchars($kat['slug_kategori']) ?>"
                  <?= $data_edit['id_kategori'] == $kat['id_kategori'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($kat['nama_kategori']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- ═══ SECTION BIOGRAFI ═══ -->
        <div class="bio-section" id="bio-section">
          <div class="bio-section-title">
            <i class="fas fa-user-circle"></i>
            Info Pribadi Tokoh — ditampilkan di card biografi
          </div>
          <div class="form-grid-3">
            <div class="form-group">
              <label>Tanggal Lahir</label>
              <input type="text" name="bio_lahir" id="bio_lahir"
                     placeholder="cth: 2 Mei 1889"
                     value="<?= htmlspecialchars($bio_edit['lahir']) ?>">
            </div>
            <div class="form-group">
              <label>Tanggal Meninggal</label>
              <input type="text" name="bio_meninggal" id="bio_meninggal"
                     placeholder="cth: 26 April 1959 (umur 69)"
                     value="<?= htmlspecialchars($bio_edit['meninggal']) ?>">
            </div>
            <div class="form-group">
              <label>Pekerjaan / Profesi</label>
              <input type="text" name="bio_pekerjaan" id="bio_pekerjaan"
                     placeholder="cth: Pendidik, Pahlawan Nasional"
                     value="<?= htmlspecialchars($bio_edit['pekerjaan']) ?>">
            </div>
          </div>
          <div class="bio-hint">
            <i class="fas fa-info-circle" style="color:var(--sj-sand);margin-top:1px;flex-shrink:0;"></i>
            <span>Data ini ditampilkan di card <strong>Info Pribadi</strong> pada halaman detail artikel biografi. Kosongkan field yang tidak diketahui — akan tampil sebagai tanda <strong>—</strong>.</span>
          </div>
        </div>

        <div class="form-section-label" style="margin-top:10px;">Konten &amp; Media</div>

        <div class="form-grid-2">
          <div class="form-group">
            <label>Cover / Thumbnail</label>
            <input type="file" name="thumbnail" accept="image/*" onchange="previewGambar(this)">
            <div class="form-hint">Format: JPG, PNG, GIF, WebP. Maks. 5MB.</div>
            <?php if (!empty($data_edit['thumbnail'])): ?>
              <div style="margin-top:6px;font-size:12px;color:var(--text-muted);">Cover saat ini:</div>
              <img src="/website/<?= htmlspecialchars($data_edit['thumbnail']) ?>"
                   id="preview-img" class="preview-img"
                   onerror="this.style.display='none'">
            <?php else: ?>
              <img id="preview-img" class="preview-img" style="display:none;">
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label>Teks Preview</label>
            <textarea name="preview" id="textarea-preview"
                      placeholder="Ringkasan singkat artikel (maks. 3 kalimat)..."><?= htmlspecialchars($preview_teks_edit) ?></textarea>
          </div>
        </div>

        <div class="form-grid-1">
          <div class="form-group">
            <label>Isi Artikel Lengkap <span class="required">*</span></label>
            <div class="form-hint" style="margin-bottom:6px;">
              Gunakan tag &lt;p&gt; untuk setiap paragraf.
            </div>
            <textarea name="konten" id="konten-textarea" required
              placeholder="<p>Paragraf pertama...</p>&#10;<p>Paragraf kedua...</p>"><?= htmlspecialchars($data_edit['konten']) ?></textarea>
          </div>
        </div>

        <div class="form-footer">
          <!-- Ajukan Ulang (satu-satunya aksi submit) -->
          <button type="button" class="btn-ajukan" onclick="submitForm()">
            <i class="fas fa-paper-plane"></i> Simpan &amp; Ajukan Ulang
          </button>
          <a href="history.php?tab=artikel" class="btn-batal">
            <i class="fas fa-times"></i> Batal
          </a>
        </div>
      </form>
    </div>
  </div>

</div><!-- /main-wrapper -->

<script>
  // Sidebar hamburger
  const hamburger = document.getElementById('hamburger');
  const sidebar   = document.getElementById('sidebar');
  hamburger.addEventListener('click', () => sidebar.classList.toggle('show'));
  document.addEventListener('click', function(e) {
    if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
      sidebar.classList.remove('show');
    }
  });

  // Preview gambar
  function previewGambar(input) {
    const img = document.getElementById('preview-img');
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = e => { img.src = e.target.result; img.style.display = 'block'; };
      reader.readAsDataURL(input.files[0]);
    }
  }

  // Submit dengan konfirmasi
  function submitForm() {
    if (!confirm('Yakin ingin mengajukan ulang artikel ini untuk diverifikasi admin?')) return;
    document.getElementById('form-edit').submit();
  }

  // Tampil/sembunyikan section biografi
  function cekBiografi(id_kategori) {
    const sel  = document.getElementById('select_kategori');
    const opt  = sel ? sel.options[sel.selectedIndex] : null;
    const slug = opt ? (opt.dataset.slug || '').toLowerCase() : '';
    document.getElementById('bio-section').classList.toggle('tampil', slug === 'biografi');
  }

  document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('select_kategori');
    if (sel) cekBiografi(sel.value);
  });
</script>
</body>
</html>