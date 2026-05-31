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

// Tandai satu notif dibaca
if (isset($_GET['baca_notif'])) {
    $notif_id = (int)$_GET['baca_notif'];
    $upd = $conn->prepare("UPDATE tb_notifikasi SET dibaca = 1 WHERE id = ? AND user_id = ?");
    $upd->bind_param("ii", $notif_id, $_SESSION['id']);
    $upd->execute();
    header("Location: notifikasi.php");
    exit();
}

// Tandai semua dibaca
if (isset($_GET['baca_semua'])) {
    $upd_all = $conn->prepare("UPDATE tb_notifikasi SET dibaca = 1 WHERE user_id = ?");
    $upd_all->bind_param("i", $_SESSION['id']);
    $upd_all->execute();
    header("Location: notifikasi.php");
    exit();
}

// Ambil notifikasi
$stmt_notif = $conn->prepare("
    SELECT * FROM tb_notifikasi
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt_notif->bind_param("i", $_SESSION['id']);
$stmt_notif->execute();
$notifikasi_list = $stmt_notif->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung belum dibaca
$stmt_unread = $conn->prepare("SELECT COUNT(*) AS total FROM tb_notifikasi WHERE user_id = ? AND dibaca = 0");
$stmt_unread->bind_param("i", $_SESSION['id']);
$stmt_unread->execute();
$unread_count = $stmt_unread->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Notifikasi</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Arial,sans-serif; }
    html,body { height:100%; background:#f7f7f7; }

    /* SIDEBAR */
    .sidebar {
      position:fixed; top:0; left:0;
      width:240px; height:100vh;
      background:#4a2c18;
      padding:30px 25px;
      color:white;
      display:flex; flex-direction:column; align-items:flex-start;
      transition:transform 0.35s ease-in-out;
      z-index:1000;
    }
    .sidebar-logo { width:55px; margin-bottom:50px; }
    .sidebar a {
      width:100%; display:flex; align-items:center; gap:15px;
      font-size:16px; color:white; text-decoration:none;
      padding:14px 5px; margin:2px 0; border-radius:10px;
      position:relative;
    }
    .sidebar a.active { background:rgba(255,255,255,0.15); }
    .sidebar a:hover  { background:rgba(255,255,255,0.12); }
    .icon-menu { width:20px; height:20px; object-fit:contain; margin-right:14px; }

    .sidebar-notif-badge {
      position:absolute; right:10px; top:50%; transform:translateY(-50%);
      background:#ef4444; color:#fff;
      font-size:11px; font-weight:700;
      min-width:20px; height:20px; border-radius:10px;
      display:flex; align-items:center; justify-content:center; padding:0 5px;
    }

    /* HAMBURGER */
    .hamburger {
      display:none; position:fixed; top:32px; right:26px;
      font-size:28px; color:#4a2c18; cursor:pointer; z-index:1100;
    }

    /* MAIN */
    .main-wrapper {
      margin-left:240px; min-height:100vh;
      width:calc(100% - 240px); padding:30px 40px;
    }

    /* PAGE HEADER */
    .page-header {
      background:white; padding:18px 22px;
      border-radius:10px; box-shadow:0 0 7px rgba(0,0,0,0.12);
      margin-bottom:24px;
      display:flex; align-items:center; justify-content:space-between;
    }
    .page-header h3 { font-size:19px; font-weight:600; color:#333; }

    .btn-baca-semua {
      background:#8b3e26; color:#fff; border:none;
      padding:8px 18px; border-radius:8px; font-size:13px;
      font-weight:600; cursor:pointer; text-decoration:none;
      display:inline-flex; align-items:center; gap:6px;
      transition:opacity .2s;
    }
    .btn-baca-semua:hover { opacity:.85; }

    /* NOTIF CARD */
    .notif-wrap { max-width:760px; }

    .notif-item {
      display:flex; align-items:flex-start; gap:14px;
      padding:16px 18px; border-radius:12px; margin-bottom:12px;
      background:white; border:1.5px solid #f0e6de;
      box-shadow:0 1px 4px rgba(0,0,0,0.06);
      position:relative; transition:background .15s;
    }
    .notif-item.belum-dibaca { background:#fff9f0; border-color:#f59e0b; }
    .notif-item.peringatan   { border-left:4px solid #f59e0b; }
    .notif-item.info         { border-left:4px solid #3b82f6; }

    .notif-icon {
      width:44px; height:44px; border-radius:50%;
      display:flex; align-items:center; justify-content:center;
      flex-shrink:0; font-size:20px;
    }
    .notif-icon.peringatan { background:#fef3c7; }
    .notif-icon.info       { background:#dbeafe; }

    .notif-body { flex:1; }
    .notif-judul { font-size:14px; font-weight:700; color:#222; margin-bottom:5px; }
    .notif-pesan { font-size:13px; color:#555; line-height:1.55; }
    .notif-waktu {
      font-size:11.5px; color:#aaa; margin-top:8px;
      display:flex; align-items:center; gap:5px;
    }
    .notif-dot {
      width:10px; height:10px; background:#ef4444; border-radius:50%;
      position:absolute; top:14px; right:14px; flex-shrink:0;
    }
    .notif-baca-btn {
      font-size:12px; color:#8b3e26; text-decoration:none;
      font-weight:600; margin-top:8px; display:inline-block;
    }
    .notif-baca-btn:hover { text-decoration:underline; }

    /* KOSONG */
    .notif-kosong {
      text-align:center; padding:60px 20px; color:#bbb;
      background:white; border-radius:12px;
      box-shadow:0 1px 4px rgba(0,0,0,0.06);
    }
    .notif-kosong i { font-size:52px; display:block; margin-bottom:14px; color:#ddd; }
    .notif-kosong p { font-size:15px; }

    /* RESPONSIVE */
    @media(max-width:900px){
      .hamburger { display:block; }
      .sidebar { transform:translateX(-100%); }
      .sidebar.show { transform:translateX(0); }
      .main-wrapper { margin-left:0; width:100%; padding:20px; }
    }
  </style>
</head>
<body>

<div class="hamburger" id="hamburger"><i class="fas fa-bars"></i></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
  <img src="logobenar.png" class="sidebar-logo">

  <a href="notifikasi.php" class="active">
    <i class="fas fa-bell" style="font-size:18px;width:20px;text-align:center;margin-right:14px;"></i>
    Notifikasi
    <?php if ($unread_count > 0): ?>
      <span class="sidebar-notif-badge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
    <?php endif; ?>
  </a>

  <a href="landingpagepilihanfix.php">
    <img src="Group.png" class="icon-menu"> Keluar
  </a>
</div>

<!-- MAIN -->
<div class="main-wrapper">

  <div class="page-header">
    <h3>
      <i class="fas fa-bell" style="color:#d97706;margin-right:8px"></i>
      Notifikasi
      <?php if ($unread_count > 0): ?>
        <span style="background:#ef4444;color:#fff;font-size:12px;font-weight:700;
          padding:2px 9px;border-radius:12px;margin-left:8px;vertical-align:middle">
          <?= $unread_count ?> baru
        </span>
      <?php endif; ?>
    </h3>
    <?php if ($unread_count > 0): ?>
      <a href="notifikasi.php?baca_semua=1" class="btn-baca-semua">
        <i class="fas fa-check-double"></i> Tandai semua dibaca
      </a>
    <?php endif; ?>
  </div>

  <div class="notif-wrap">

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
          <div class="notif-waktu">
            <i class="fas fa-clock"></i> <?= $waktu ?>
          </div>
          <?php if ($is_unread): ?>
            <a href="notifikasi.php?baca_notif=<?= $n['id'] ?>" class="notif-baca-btn">
              ✓ Tandai sudah dibaca
            </a>
          <?php endif; ?>
        </div>

        <?php if ($is_unread): ?>
          <div class="notif-dot"></div>
        <?php endif; ?>

      </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</div>

<script>
  const hamburger = document.getElementById("hamburger");
  const sidebar   = document.getElementById("sidebar");
  hamburger.addEventListener("click", () => sidebar.classList.toggle("show"));
</script>

</body>
</html>