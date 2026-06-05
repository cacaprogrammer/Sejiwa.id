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

// ── Hapus riwayat ──
if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    $del = $conn->prepare("DELETE FROM tb_history WHERE id = ? AND user_id = ?");
    $del->bind_param("ii", $hapus_id, $user_id);
    $del->execute();
    header("Location: history.php?tab=profil");
    exit();
}

// ── Tandai satu notif dibaca ──
if (isset($_GET['baca_notif'])) {
    $notif_id = (int)$_GET['baca_notif'];
    $upd = $conn->prepare("UPDATE tb_notifikasi SET dibaca = 1 WHERE id = ? AND user_id = ?");
    $upd->bind_param("ii", $notif_id, $user_id);
    $upd->execute();
    header("Location: history.php?tab=notifikasi");
    exit();
}

// ── Tandai semua notif dibaca ──
if (isset($_GET['baca_semua'])) {
    $upd_all = $conn->prepare("UPDATE tb_notifikasi SET dibaca = 1 WHERE user_id = ?");
    $upd_all->bind_param("i", $user_id);
    $upd_all->execute();
    header("Location: history.php?tab=notifikasi");
    exit();
}

// ── Tab aktif ──
$tab     = isset($_GET['tab'])     ? $_GET['tab']     : 'profil';
$tab_kel = isset($_GET['tab_kel']) ? $_GET['tab_kel'] : 'semua';

// ── Pencarian artikel ──
$cari_kel = isset($_GET['cari_kel']) ? trim($_GET['cari_kel']) : '';

// ── Data profil ──
$stmt = $conn->prepare("SELECT * FROM tb_user WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$foto = $user['foto_profile'] ?? null;
$foto_src = ($foto && file_exists("uploads/" . $foto))
    ? "/website/uploads/" . $foto
    : "https://i.pravatar.cc/160";

// ── Riwayat baca ──
$stmt2 = $conn->prepare("
    SELECT h.*, a.judul, a.slug, a.thumbnail, a.kategori, a.id AS artikel_id
    FROM tb_history h
    JOIN tb_artikel a ON h.artikel_id = a.id
    WHERE h.user_id = ?
    ORDER BY h.read_at DESC
");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$histories = $stmt2->get_result();

// ── Notifikasi ──
$stmt_notif = $conn->prepare("
    SELECT * FROM tb_notifikasi
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt_notif->bind_param("i", $user_id);
$stmt_notif->execute();
$notifikasi_list = $stmt_notif->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_unread = $conn->prepare("SELECT COUNT(*) AS total FROM tb_notifikasi WHERE user_id = ? AND dibaca = 0");
$stmt_unread->bind_param("i", $user_id);
$stmt_unread->execute();
$unread_count = $stmt_unread->get_result()->fetch_assoc()['total'];

// ── Kelola Artikel (dengan pencarian) ──
if ($cari_kel !== '') {
    $cari_like = '%' . $cari_kel . '%';
    $stmt_art = $conn->prepare("
        SELECT id, judul, slug, status, created_at, kategori, catatan_admin, penulis
        FROM tb_artikel
        WHERE created_by = ? AND (judul LIKE ? OR kategori LIKE ?)
        ORDER BY created_at DESC
    ");
    $stmt_art->bind_param("iss", $user_id, $cari_like, $cari_like);
} else {
    $stmt_art = $conn->prepare("
        SELECT id, judul, slug, status, created_at, kategori, catatan_admin, penulis
        FROM tb_artikel
        WHERE created_by = ?
        ORDER BY created_at DESC
    ");
    $stmt_art->bind_param("i", $user_id);
}
$stmt_art->execute();
$artikel_list = $stmt_art->get_result()->fetch_all(MYSQLI_ASSOC);

$total_art   = count($artikel_list);
$menunggu    = count(array_filter($artikel_list, fn($a) => $a['status'] === 'pending'));
$diterbitkan = count(array_filter($artikel_list, fn($a) => $a['status'] === 'published'));
$ditolak     = count(array_filter($artikel_list, fn($a) => $a['status'] === 'rejected'));

$filtered = match($tab_kel) {
    'menunggu'    => array_filter($artikel_list, fn($a) => $a['status'] === 'pending'),
    'diterbitkan' => array_filter($artikel_list, fn($a) => $a['status'] === 'published'),
    'ditolak'     => array_filter($artikel_list, fn($a) => $a['status'] === 'rejected'),
    default       => $artikel_list,
};

// ── Kategori untuk tambah artikel ──
$daftar_kategori = [];
$res_kat = $conn->query("SELECT nama_kategori, slug_kategori FROM tb_kategori ORDER BY nama_kategori ASC");
if ($res_kat) {
    while ($row = $res_kat->fetch_assoc()) $daftar_kategori[] = $row;
}

function ikonKategori($slug) {
    return match(strtolower($slug)) {
        'sejarah'  => 'fas fa-landmark',
        'biografi' => 'fas fa-user-circle',
        'budaya'   => 'fas fa-masks-theater',
        'politik'  => 'fas fa-scale-balanced',
        'ekonomi'  => 'fas fa-chart-line',
        default    => 'fas fa-folder-open',
    };
}

function statusLabel($status) {
    return match($status) {
        'pending'   => ['label' => 'Menunggu Verifikasi', 'class' => 'pill-menunggu'],
        'published' => ['label' => 'Diterbitkan',         'class' => 'pill-published'],
        'rejected'  => ['label' => 'Ditolak',             'class' => 'pill-ditolak'],
        'draft'     => ['label' => 'Draft',               'class' => 'pill-draft'],
        default     => ['label' => ucfirst($status),      'class' => 'pill-draft'],
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profil — Sejiwa.id</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Arial,sans-serif; }
    html,body { height:100%; background:#f4f1ee; }

    .sidebar {
      position:fixed; top:0; left:0;
      width:240px; height:100vh;
      background:#4a2c18;
      padding:28px 20px 28px;
      color:#fff;
      display:flex; flex-direction:column; align-items:flex-start;
      z-index:1000;
      transition:transform .35s ease-in-out;
      overflow-y:auto;
    }
    .sidebar-logo { width:52px; margin-bottom:36px; }
    .sidebar-nav-label {
      font-size:10px; font-weight:700; letter-spacing:.08em;
      color:rgba(255,255,255,.45); text-transform:uppercase;
      padding:0 6px; margin:8px 0 4px;
    }
    .sidebar a {
      width:100%; display:flex; align-items:center; gap:12px;
      font-size:14.5px; color:rgba(255,255,255,.85); text-decoration:none;
      padding:11px 12px; margin:2px 0; border-radius:10px;
      position:relative; transition:background .15s;
      border-left:3px solid transparent;
    }
    .sidebar a.active {
      background:rgba(255,255,255,.16);
      color:#fff; border-left-color:#f0c080; font-weight:600;
    }
    .sidebar a:hover { background:rgba(255,255,255,.1); color:#fff; }
    .sidebar a i { width:18px; text-align:center; font-size:15px; flex-shrink:0; }

    .notif-badge {
      position:absolute; right:10px; top:50%; transform:translateY(-50%);
      background:#ef4444; color:#fff; font-size:10px; font-weight:700;
      min-width:18px; height:18px; border-radius:9px;
      display:flex; align-items:center; justify-content:center; padding:0 4px;
    }

    .hamburger {
      display:none; position:fixed; top:14px; right:18px;
      font-size:22px; color:#4a2c18; cursor:pointer; z-index:1100;
      background:#fff; width:38px; height:38px; border-radius:8px;
      align-items:center; justify-content:center;
      box-shadow:0 2px 8px rgba(0,0,0,.15);
    }

    .main-wrapper {
      margin-left:240px; min-height:100vh;
      width:calc(100% - 240px); padding:32px 40px;
    }

    .page-header {
      background:#fff; padding:16px 22px; border-radius:12px;
      box-shadow:0 1px 6px rgba(0,0,0,.08); margin-bottom:24px;
      display:flex; align-items:center; justify-content:space-between;
    }
    .page-header h3 { font-size:18px; font-weight:700; color:#2d1a0e; }

    .profile-card {
      background:#fff; padding:28px 30px; border-radius:14px;
      display:flex; align-items:center; gap:24px;
      box-shadow:0 1px 8px rgba(0,0,0,.09);
      max-width:520px; margin-bottom:28px;
    }
    .profile-img { width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid #e8ddd5; flex-shrink:0; }
    .profile-name { font-size:20px; font-weight:700; color:#2d1a0e; }
    .profile-username { color:#888; font-size:13px; margin:4px 0 14px; }
    .btn-edit {
      background:#8b3e26; color:#fff; border:none;
      padding:8px 20px; border-radius:8px; cursor:pointer;
      font-size:13px; font-weight:600; transition:opacity .2s;
    }
    .btn-edit:hover { opacity:.85; }

    .section-box {
      background:#fff; padding:24px; border-radius:14px;
      box-shadow:0 1px 8px rgba(0,0,0,.09); margin-bottom:24px;
    }
    .section-title { font-size:17px; font-weight:700; color:#2d1a0e; margin-bottom:16px; }
    table { width:100%; border-collapse:collapse; font-size:13.5px; }
    th { background:#fafafa; padding:11px 12px; text-align:left; border-bottom:2px solid #eee; color:#555; font-size:12px; text-transform:uppercase; letter-spacing:.04em; }
    td { padding:13px 12px; border-bottom:1px solid #f0ebe6; vertical-align:middle; }
    .badge { padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }
    .badge-sejarah  { background:#fef3c7; color:#92400e; }
    .badge-biografi { background:#dbeafe; color:#1e40af; }
    .badge-default  { background:#f0f4f8; color:#4b5563; }
    .baca-link {
      display:inline-flex; align-items:center; gap:6px;
      color:#fff; background:#8b3e26;
      font-weight:600; text-decoration:none; font-size:12.5px;
      padding:6px 14px; border-radius:8px;
      transition:background .18s, box-shadow .18s;
      box-shadow:0 2px 6px rgba(139,62,38,.18);
      white-space:nowrap;
    }
    .baca-link:hover { background:#6b2e1a; box-shadow:0 4px 12px rgba(139,62,38,.28); }
    .baca-link i { font-size:12px; }
    .btn-delete { background:#fde8e8; border:none; color:#8b1a1a; padding:5px 14px; border-radius:7px; cursor:pointer; font-size:12px; font-weight:600; }
    .btn-delete:hover { background:#f5b8b8; }
    .kosong { text-align:center; padding:36px; color:#bbb; font-size:14px; }
    .judul-wrap { display:flex; flex-direction:column; }
    .subjudul-kat { font-size:11px; color:#aaa; margin-top:2px; }

    .kel-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px; }
    .dropdown-wrap { position:relative; }
    .btn-primary {
      display:flex; align-items:center; gap:7px;
      background:#4a2c18; color:#fff; padding:9px 16px;
      border:none; border-radius:10px; font-size:13px;
      font-weight:600; cursor:pointer; transition:background .2s; white-space:nowrap;
    }
    .btn-primary:hover { background:#6b3e23; }
    .ddm-menu {
      position:absolute; top:calc(100% + 6px); right:0;
      background:#fff; border:1px solid #e8ddd5; border-radius:12px;
      box-shadow:0 8px 24px rgba(0,0,0,.12); min-width:190px;
      padding:6px 0; display:none; z-index:200;
    }
    .ddm-menu.open { display:block; }
    .ddm-menu::before {
      content:''; position:absolute; top:-7px; right:18px;
      width:14px; height:14px; background:#fff;
      border-left:1px solid #e8ddd5; border-top:1px solid #e8ddd5;
      transform:rotate(45deg);
    }
    .ddm-item { display:flex; align-items:center; gap:10px; padding:10px 16px; color:#333; font-size:13.5px; text-decoration:none; transition:background .15s; }
    .ddm-item:hover { background:#fdf5f0; color:#4a2c18; }
    .ddm-item i { color:#a3826f; width:16px; text-align:center; }
    .ddm-sep { height:1px; background:#f0e8e0; margin:4px 8px; }

    .search-bar-wrap { margin-bottom: 14px; }
    .search-bar-inner { position: relative; max-width: 420px; }
    .search-bar-inner .search-icon {
      position: absolute; left: 12px; top: 50%;
      transform: translateY(-50%);
      color: #a3826f; font-size: 13px; pointer-events: none;
    }
    .search-bar-input {
      width: 100%; padding: 9px 36px 9px 34px;
      border: 1.5px solid #e0d5cd; border-radius: 10px;
      font-size: 13.5px; font-family: Arial, sans-serif;
      color: #2d1a0e; background: #fdfaf7; outline: none;
      transition: border-color .2s, box-shadow .2s;
    }
    .search-bar-input:focus {
      border-color: #8b3e26;
      box-shadow: 0 0 0 3px rgba(139,62,38,0.10);
      background: #fff;
    }
    .search-bar-input::placeholder { color: #c4b09a; }
    .search-clear {
      position: absolute; right: 10px; top: 50%;
      transform: translateY(-50%);
      color: #bbb; font-size: 15px;
      text-decoration: none; line-height: 1; transition: color .15s;
    }
    .search-clear:hover { color: #8b3e26; }
    .search-info {
      margin-top: 7px; font-size: 12px; color: #a3826f;
      display: flex; align-items: center; gap: 5px;
    }
    .search-info strong { color: #4a2c18; }

    .tab-bar { display:flex; gap:0; border-bottom:1.5px solid #e0d5cd; margin-bottom:16px; flex-wrap:wrap; }
    .tab-link { padding:8px 14px; font-size:12.5px; color:#888; text-decoration:none; border-bottom:2px solid transparent; margin-bottom:-1.5px; transition:color .15s; white-space:nowrap; }
    .tab-link:hover { color:#4a2c18; }
    .tab-link.active { color:#4a2c18; border-bottom-color:#4a2c18; font-weight:700; }

    .artikel-card {
      background:#fff; border:1px solid #e8ddd5; border-radius:12px;
      padding:16px 18px; margin-bottom:12px;
    }
    .artikel-top { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:8px; }
    .artikel-judul { font-size:14px; font-weight:700; color:#2d1a0e; line-height:1.4; }
    .status-pill { font-size:11px; padding:3px 11px; border-radius:20px; white-space:nowrap; flex-shrink:0; font-weight:600; }
    .pill-menunggu  { background:#fff3d6; color:#7a5200; }
    .pill-published { background:#d6f5e3; color:#1a6b3a; }
    .pill-ditolak   { background:#fde8e8; color:#8b1a1a; }
    .pill-draft     { background:#f0f0f0; color:#555; }
    .artikel-meta { display:flex; gap:12px; flex-wrap:wrap; font-size:12px; color:#aaa; margin-bottom:10px; }
    .artikel-meta i { margin-right:3px; }
    .info-box { font-size:12px; padding:9px 12px; border-radius:8px; margin-bottom:10px; display:flex; align-items:flex-start; gap:7px; }
    .info-box.locked   { background:#f5f0eb; color:#7a6050; }
    .info-box.rejected { background:#fff3d6; color:#7a5200; }
    .btn-row { display:flex; gap:7px; flex-wrap:wrap; }
    .btn-act {
      font-size:12px; padding:5px 13px; border:1px solid #d5c9c0;
      border-radius:7px; background:#fff; color:#333; cursor:pointer;
      text-decoration:none; display:inline-flex; align-items:center; gap:5px; transition:background .15s;
    }
    .btn-act:hover { background:#f7f3f0; }
    .btn-act.danger { border-color:#f5b8b8; color:#8b1a1a; }
    .btn-act.danger:hover { background:#fde8e8; }
    .btn-act.success { background:#4a2c18; color:#fff; border-color:#4a2c18; }
    .btn-act.success:hover { background:#6b3e23; }

    .notif-item {
      display:flex; align-items:flex-start; gap:13px;
      padding:15px 17px; border-radius:12px; margin-bottom:11px;
      background:#fff; border:1.5px solid #f0e6de;
      box-shadow:0 1px 4px rgba(0,0,0,.05); position:relative;
    }
    .notif-item.belum-dibaca { background:#fff9f0; border-color:#f59e0b; }
    .notif-item.peringatan   { border-left:4px solid #f59e0b; }
    .notif-item.info         { border-left:4px solid #3b82f6; }
    .notif-icon { width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:19px; }
    .notif-icon.peringatan { background:#fef3c7; }
    .notif-icon.info       { background:#dbeafe; }
    .notif-body { flex:1; }
    .notif-judul { font-size:13.5px; font-weight:700; color:#222; margin-bottom:4px; }
    .notif-pesan { font-size:12.5px; color:#555; line-height:1.55; }
    .notif-waktu { font-size:11px; color:#bbb; margin-top:7px; display:flex; align-items:center; gap:5px; }
    .notif-dot { width:9px; height:9px; background:#ef4444; border-radius:50%; position:absolute; top:13px; right:13px; }
    .notif-baca-btn { font-size:12px; color:#8b3e26; text-decoration:none; font-weight:600; margin-top:7px; display:inline-block; }
    .notif-baca-btn:hover { text-decoration:underline; }
    .btn-baca-semua {
      background:#8b3e26; color:#fff; border:none; padding:7px 16px;
      border-radius:8px; font-size:12.5px; font-weight:600; cursor:pointer;
      text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:opacity .2s;
    }
    .btn-baca-semua:hover { opacity:.85; }
    .notif-kosong { text-align:center; padding:50px 20px; color:#bbb; }
    .notif-kosong i { font-size:46px; display:block; margin-bottom:12px; color:#ddd; }

    .empty-state { text-align:center; padding:40px 20px; color:#bbb; }
    .empty-state i { font-size:40px; display:block; margin-bottom:12px; color:#d5c9c0; }
    .empty-state p { font-size:13.5px; }

    @media(max-width:900px){
      .hamburger { display:flex; }
      .sidebar { transform:translateX(-100%); }
      .sidebar.show { transform:translateX(0); }
      .main-wrapper { margin-left:0; width:100%; padding:20px 16px; padding-top:60px; }
      .profile-card { max-width:100%; }
      .search-bar-inner { max-width:100%; }
      table { font-size:12px; }
      .baca-link { font-size:11.5px; padding:5px 10px; }
    }
  </style>
</head>
<body>

<div class="hamburger" id="hamburger"><i class="fas fa-bars"></i></div>

<div class="sidebar" id="sidebar">
  <img src="logobenar.png" class="sidebar-logo" alt="Logo">
  <div class="sidebar-nav-label">Menu</div>
  <a href="history.php?tab=profil" class="<?= $tab === 'profil' ? 'active' : '' ?>">
    <i class="fas fa-user"></i> Profil &amp; Riwayat
  </a>
  <a href="history.php?tab=artikel" class="<?= $tab === 'artikel' ? 'active' : '' ?>">
    <i class="fas fa-pen-to-square"></i> Kelola Artikel
  </a>
  <a href="history.php?tab=notifikasi" class="<?= $tab === 'notifikasi' ? 'active' : '' ?>">
    <i class="fas fa-bell"></i> Notifikasi
    <?php if ($unread_count > 0): ?>
      <span class="notif-badge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
    <?php endif; ?>
  </a>
  <div style="margin-top:auto; padding-top:24px; width:100%;">
    <div style="height:1px; background:rgba(255,255,255,.15); margin:0 4px 16px;"></div>
    <a href="landingpagepilihanfix.php" style="color:rgba(255,255,255,.6);">
      <i class="fas fa-arrow-left"></i> Kembali ke Beranda
    </a>
  </div>
</div>

<div class="main-wrapper">

  <?php if ($tab === 'profil'): ?>

  <div class="page-header">
    <h3><i class="fas fa-user" style="color:#8b3e26;margin-right:8px"></i>Profil Saya</h3>
  </div>

  <div class="profile-card">
    <img src="<?= htmlspecialchars($foto_src) ?>" class="profile-img" onerror="this.src='https://i.pravatar.cc/160'" alt="Foto Profil">
    <div>
      <div class="profile-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
      <div class="profile-username">@<?= htmlspecialchars($user['username']) ?></div>
      <a href="profile.php"><button class="btn-edit">Ubah Profil</button></a>
    </div>
  </div>

  <div class="section-box">
    <div class="section-title">Riwayat Baca</div>
    <table>
      <tr>
        <th>Judul</th>
        <th>Kategori</th>
        <th>Baca Sekarang</th>
        <th>Terakhir Dibaca</th>
        <th>Hapus</th>
      </tr>
      <?php if ($histories->num_rows === 0): ?>
        <tr><td colspan="5" class="kosong">Belum ada riwayat baca.</td></tr>
      <?php else: ?>
        <?php while ($row = $histories->fetch_assoc()):
          $tgl = date("d M Y, H:i", strtotime($row['read_at']));
          $kat_lower = strtolower($row['kategori']);
          $badge_class = str_contains($kat_lower,'sejarah') ? 'badge-sejarah'
                       : (str_contains($kat_lower,'biografi') ? 'badge-biografi' : 'badge-default');

          // ── URL Baca Sekarang ──
          if (!empty($row['slug'])) {
              $url_baca = "detail_artikel.php?slug=" . urlencode($row['slug']);
          } else {
              $url_baca = "isi_artikel.php?id=" . (int)$row['artikel_id'];
          }
        ?>
        <tr>
          <td>
            <div class="judul-wrap">
              <span><?= htmlspecialchars($row['judul']) ?></span>
              <span class="subjudul-kat"><?= ucfirst($row['kategori']) ?></span>
            </div>
          </td>
          <td><span class="badge <?= $badge_class ?>"><?= ucfirst($row['kategori']) ?></span></td>
          <td>
            <a href="<?= htmlspecialchars($url_baca) ?>" class="baca-link" target="_blank">
              <i class="fas fa-book-open"></i> Baca Sekarang
            </a>
          </td>
          <td><?= $tgl ?></td>
          <td>
            <a href="history.php?hapus=<?= $row['id'] ?>" onclick="return confirm('Hapus riwayat ini?')">
              <button class="btn-delete">Hapus</button>
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </table>
  </div>

  <?php elseif ($tab === 'artikel'): ?>

  <div class="page-header">
    <h3><i class="fas fa-pen-to-square" style="color:#8b3e26;margin-right:8px"></i>Kelola Artikel</h3>
  </div>

  <div class="section-box">
    <div class="kel-header">
      <div style="font-size:13px;color:#888;">Pantau dan kelola artikel yang pernah Anda unggah</div>
      <div class="dropdown-wrap" id="ddWrap">
        <button class="btn-primary" id="ddBtn">
          <i class="fas fa-plus"></i> Tambah Artikel
          <i class="fas fa-chevron-down" style="font-size:10px"></i>
        </button>
        <div class="ddm-menu" id="ddMenu">
          <?php if (empty($daftar_kategori)): ?>
            <span class="ddm-item" style="color:#aaa;cursor:default;">
              <i class="fas fa-exclamation-circle"></i> Belum ada kategori
            </span>
          <?php else: ?>
            <?php foreach ($daftar_kategori as $i => $kat): ?>
              <?php if ($i > 0): ?><div class="ddm-sep"></div><?php endif; ?>
              <a href="tambah_artikel.php?kategori=<?= urlencode($kat['slug_kategori']) ?>" class="ddm-item">
                <i class="<?= ikonKategori($kat['slug_kategori']) ?>"></i>
                <?= htmlspecialchars($kat['nama_kategori']) ?>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="search-bar-wrap">
      <form method="get" action="" id="form-cari-kel">
        <input type="hidden" name="tab" value="artikel">
        <input type="hidden" name="tab_kel" value="<?= htmlspecialchars($tab_kel) ?>">
        <div class="search-bar-inner">
          <i class="fas fa-search search-icon"></i>
          <input
            type="text" name="cari_kel" id="input-cari-kel"
            class="search-bar-input"
            value="<?= htmlspecialchars($cari_kel) ?>"
            placeholder="Cari judul atau kategori artikel..."
            autocomplete="off"
          >
          <?php if ($cari_kel !== ''): ?>
            <a href="history.php?tab=artikel&tab_kel=<?= htmlspecialchars($tab_kel) ?>"
               class="search-clear" title="Hapus pencarian">✕</a>
          <?php endif; ?>
        </div>
        <?php if ($cari_kel !== ''): ?>
          <div class="search-info">
            <i class="fas fa-info-circle"></i>
            Hasil untuk: <strong>"<?= htmlspecialchars($cari_kel) ?>"</strong>
            — <?= count($filtered) ?> artikel ditemukan
          </div>
        <?php endif; ?>
      </form>
    </div>

    <div class="tab-bar">
      <a href="history.php?tab=artikel&tab_kel=semua<?= $cari_kel ? '&cari_kel='.urlencode($cari_kel) : '' ?>"
         class="tab-link <?= $tab_kel==='semua' ? 'active':'' ?>">Semua (<?= $total_art ?>)</a>
      <a href="history.php?tab=artikel&tab_kel=menunggu<?= $cari_kel ? '&cari_kel='.urlencode($cari_kel) : '' ?>"
         class="tab-link <?= $tab_kel==='menunggu' ? 'active':'' ?>">Menunggu (<?= $menunggu ?>)</a>
      <a href="history.php?tab=artikel&tab_kel=diterbitkan<?= $cari_kel ? '&cari_kel='.urlencode($cari_kel) : '' ?>"
         class="tab-link <?= $tab_kel==='diterbitkan' ? 'active':'' ?>">Diterbitkan (<?= $diterbitkan ?>)</a>
      <a href="history.php?tab=artikel&tab_kel=ditolak<?= $cari_kel ? '&cari_kel='.urlencode($cari_kel) : '' ?>"
         class="tab-link <?= $tab_kel==='ditolak' ? 'active':'' ?>">Ditolak (<?= $ditolak ?>)</a>
    </div>

    <?php if (empty($filtered)): ?>
      <div class="empty-state">
        <i class="fas fa-<?= $cari_kel ? 'search' : 'file-alt' ?>"></i>
        <p><?= $cari_kel ? 'Tidak ada artikel yang cocok dengan pencarian Anda.' : 'Belum ada artikel di kategori ini.' ?></p>
        <?php if ($cari_kel): ?>
          <p style="margin-top:8px;font-size:12px;">
            <a href="history.php?tab=artikel&tab_kel=<?= htmlspecialchars($tab_kel) ?>"
               style="color:#8b3e26;text-decoration:none;">← Kembali ke semua artikel</a>
          </p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <?php foreach ($filtered as $art):
        $s   = statusLabel($art['status']);
        $tgl = date('d M Y', strtotime($art['created_at']));
        $kat = ucfirst($art['kategori'] ?? '-');
      ?>
      <div class="artikel-card">
        <div class="artikel-top">
          <div class="artikel-judul"><?= htmlspecialchars($art['judul']) ?></div>
          <span class="status-pill <?= $s['class'] ?>"><?= $s['label'] ?></span>
        </div>
        <div class="artikel-meta">
          <span><i class="fas fa-tag"></i><?= $kat ?></span>
          <span><i class="fas fa-calendar-alt"></i><?= $tgl ?></span>
          <?php if (!empty($art['penulis'])): ?>
          <span><i class="fas fa-user"></i><?= htmlspecialchars($art['penulis']) ?></span>
          <?php endif; ?>
        </div>
        <?php if ($art['status'] === 'published'): ?>
          <div class="info-box locked">
            <i class="fas fa-lock" style="margin-top:1px"></i>
            <span>Artikel sudah diverifikasi admin. Anda hanya dapat melihat statusnya.</span>
          </div>
          <div class="btn-row">
            <a href="detail_artikel.php?slug=<?= urlencode($art['slug']) ?>" class="btn-act" target="_blank">
              <i class="fas fa-eye"></i> Lihat di Website
            </a>
          </div>
        <?php elseif ($art['status'] === 'rejected'): ?>
          <?php if (!empty($art['catatan_admin'])): ?>
          <div class="info-box rejected">
            <i class="fas fa-info-circle" style="margin-top:1px"></i>
            <span><strong>Catatan admin:</strong> <?= htmlspecialchars($art['catatan_admin']) ?></span>
          </div>
          <?php endif; ?>
          <div class="btn-row">
            <a href="edit_artikel_user.php?id=<?= $art['id'] ?>" class="btn-act"><i class="fas fa-edit"></i> Perbaiki</a>
            <a href="ajukan_ulang.php?id=<?= $art['id'] ?>" class="btn-act success"><i class="fas fa-paper-plane"></i> Ajukan Ulang</a>
            <a href="hapus_artikel.php?id=<?= $art['id'] ?>" class="btn-act danger" onclick="return confirm('Yakin ingin menghapus artikel ini?')"><i class="fas fa-trash"></i> Hapus</a>
          </div>
        <?php else: ?>
          <div class="btn-row">
            <a href="edit_artikel_user.php?id=<?= $art['id'] ?>" class="btn-act"><i class="fas fa-edit"></i> Edit</a>
            <a href="hapus_artikel.php?id=<?= $art['id'] ?>" class="btn-act danger" onclick="return confirm('Yakin ingin menghapus artikel ini?')"><i class="fas fa-trash"></i> Hapus</a>
          </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php elseif ($tab === 'notifikasi'): ?>

  <div class="page-header">
    <h3>
      <i class="fas fa-bell" style="color:#d97706;margin-right:8px"></i>Notifikasi
      <?php if ($unread_count > 0): ?>
        <span style="background:#ef4444;color:#fff;font-size:11px;font-weight:700;padding:2px 9px;border-radius:12px;margin-left:8px;vertical-align:middle">
          <?= $unread_count ?> baru
        </span>
      <?php endif; ?>
    </h3>
    <?php if ($unread_count > 0): ?>
      <a href="history.php?tab=notifikasi&baca_semua=1" class="btn-baca-semua">
        <i class="fas fa-check-double"></i> Tandai semua dibaca
      </a>
    <?php endif; ?>
  </div>

  <div class="section-box">
    <?php if (empty($notifikasi_list)): ?>
      <div class="notif-kosong">
        <i class="fas fa-bell-slash"></i>
        <p>Belum ada notifikasi.</p>
      </div>
    <?php else: ?>
      <?php foreach ($notifikasi_list as $n):
        $is_unread = !$n['dibaca'];
        $tipe      = $n['tipe'];
        $icon      = $tipe === 'peringatan' ? '⚠️' : 'ℹ️';
        $waktu     = date('d M Y, H:i', strtotime($n['created_at']));
      ?>
      <div class="notif-item <?= $tipe ?> <?= $is_unread ? 'belum-dibaca' : '' ?>">
        <div class="notif-icon <?= $tipe ?>"><?= $icon ?></div>
        <div class="notif-body">
          <div class="notif-judul"><?= htmlspecialchars($n['judul']) ?></div>
          <div class="notif-pesan"><?= nl2br(htmlspecialchars($n['pesan'])) ?></div>
          <div class="notif-waktu"><i class="fas fa-clock"></i><?= $waktu ?></div>
          <?php if ($is_unread): ?>
            <a href="history.php?tab=notifikasi&baca_notif=<?= $n['id'] ?>" class="notif-baca-btn">✓ Tandai sudah dibaca</a>
          <?php endif; ?>
        </div>
        <?php if ($is_unread): ?><div class="notif-dot"></div><?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php endif; ?>

</div>

<script>
  const hamburger = document.getElementById('hamburger');
  const sidebar   = document.getElementById('sidebar');
  hamburger.addEventListener('click', () => sidebar.classList.toggle('show'));
  document.addEventListener('click', function(e) {
    if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
      sidebar.classList.remove('show');
    }
  });

  const ddBtn  = document.getElementById('ddBtn');
  const ddMenu = document.getElementById('ddMenu');
  const ddWrap = document.getElementById('ddWrap');
  if (ddBtn) {
    ddBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      ddMenu.classList.toggle('open');
    });
    document.addEventListener('click', function(e) {
      if (ddWrap && !ddWrap.contains(e.target)) ddMenu.classList.remove('open');
    });
  }

  const inputCari = document.getElementById('input-cari-kel');
  if (inputCari) {
    let timer;
    inputCari.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(() => {
        document.getElementById('form-cari-kel').submit();
      }, 400);
    });
  }
</script>
</body>
</html>