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

$stmt = $conn->prepare("SELECT * FROM tb_user WHERE id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$stmt2 = $conn->prepare("
    SELECT h.*, a.judul, a.slug, a.thumbnail, a.kategori
    FROM tb_history h
    JOIN tb_artikel a ON h.artikel_id = a.id
    WHERE h.user_id = ?
    ORDER BY h.read_at DESC
");
$stmt2->bind_param("i", $_SESSION['id']);
$stmt2->execute();
$histories = $stmt2->get_result();

if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    $del = $conn->prepare("DELETE FROM tb_history WHERE id = ? AND user_id = ?");
    $del->bind_param("ii", $hapus_id, $_SESSION['id']);
    $del->execute();
    header("Location: history.php");
    exit();
}

$foto = $user['foto_profile'] ?? null;
if ($foto && file_exists("uploads/" . $foto)) {
    $foto_src = "/website/uploads/" . $foto;
} else {
    $foto_src = "https://i.pravatar.cc/160";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>History</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
    html, body { height: 100%; }
    body { background: #ffffff; }

    .sidebar {
        position: fixed; top: 0; left: 0;
        width: 240px; height: 100vh;
        background: #4a2c18;
        padding: 30px 25px;
        color: white;
        display: flex; flex-direction: column; align-items: flex-start;
        transition: transform 0.35s ease-in-out;
        z-index: 1000;
    }
    .sidebar-logo { width: 55px; margin-bottom: 50px; }
    .sidebar a {
      width: 100%; display: flex; align-items: center; gap: 15px;
      font-size: 16px; color: white; text-decoration: none;
      padding: 14px 5px; margin: 2px 0; border-radius: 10px; position: relative;
    }
    .sidebar a.active { background: rgba(255,255,255,0.15); }
    .sidebar a:hover  { background: rgba(255,255,255,0.12); }
    .icon-menu { width: 20px; height: 20px; object-fit: contain; margin-right: 14px; }

    .hamburger {
      display: none; position: fixed; top: 32px; right: 26px;
      font-size: 28px; color: #4a2c18; cursor: pointer; z-index: 1100;
    }

    .main-wrapper { margin-left: 240px; min-height: 100vh; width: calc(100% - 240px); transition: 0.3s; }
    .content { padding: 30px 40px; flex: 1; }

    .profile-header {
      background: white; padding: 18px 22px; border-radius: 10px;
      box-shadow: 0 0 7px rgba(0,0,0,0.12); margin-bottom: 30px;
    }
    .profile-header h3 { font-size: 19px; font-weight: 600; color: #333; }

    .profile-card {
      background: white; padding: 28px 35px; border-radius: 12px;
      display: flex; align-items: center;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 480px; margin-bottom: 40px;
    }
    .profile-img { width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 3px solid #ddd; margin-right: 25px; }
    .profile-name { font-size: 22px; font-weight: 600; color: #333; }
    .profile-username { color: #777; font-size: 14px; margin: 4px 0 15px; }
    .btn-edit { background: #8b3e26; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; font-size: 14px; }
    .btn-edit:hover { opacity: 0.9; }

    .history-box {
      background: white; padding: 25px; border-radius: 12px;
      box-shadow: 0 0 8px rgba(0,0,0,0.12);
      width: 100%; max-width: 960px; margin-bottom: 30px;
    }
    .history-title { font-size: 20px; font-weight: 600; margin-bottom: 15px; color: #333; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th { background: #fafafa; padding: 12px; text-align: left; border-bottom: 2px solid #ddd; }
    td { padding: 14px; border-bottom: 1px solid #e5e5e5; }
    .badge { padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
    .selesai { background: #d8f5d0; color: #3c7c29; }
    .btn-delete { background: #f8cccc; border: none; color: #9b1b1b; padding: 6px 15px; border-radius: 8px; cursor: pointer; font-size: 13px; }
    .btn-delete:hover { background: #f5a5a5; }
    .kosong { text-align: center; padding: 40px; color: #999; font-size: 15px; }
    .icon-riwayat { width: 15px; margin-right: 6px; }
    .judul-riwayat { display: flex; align-items: flex-start; gap: 8px; }
    .judul-wrap { display: flex; flex-direction: column; }
    .subjudul { font-size: 10px; color: gray; }
    .baca-link { color: #8b3e26; font-weight: 600; text-decoration: none; font-size: 13px; }
    .baca-link:hover { text-decoration: underline; }

    @media (max-width: 900px) {
      .hamburger { display: block; }
      .sidebar { transform: translateX(-100%); }
      .sidebar.show { transform: translateX(0); }
      .main-wrapper { margin-left: 0; width: 100%; }
      .content { padding: 20px; }
      .profile-card { width: 100%; }
    }
  </style>
</head>
<body>

<div class="hamburger" id="hamburger"><i class="fas fa-bars"></i></div>

<!-- SIDEBAR — hanya Profil dan Keluar -->
<div class="sidebar" id="sidebar">
  <img src="logobenar.png" class="sidebar-logo">

  <a href="history.php" class="active">
    <img src="iconamoon_profile.png" class="icon-menu"> Profil
  </a>

  <a href="landingpagepilihanfix.php">
    <img src="Group.png" class="icon-menu"> Keluar
  </a>
</div>

<div class="main-wrapper">
  <div class="content">

    <div class="profile-header">
      <h3>Profil Saya</h3>
    </div>

    <div class="profile-card">
      <img src="<?= htmlspecialchars($foto_src) ?>" class="profile-img" onerror="this.src='https://i.pravatar.cc/160'">
      <div>
        <div class="profile-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
        <div class="profile-username">@<?= htmlspecialchars($user['username']) ?></div>
        <a href="profile.php"><button class="btn-edit">Ubah Profil</button></a>
      </div>
    </div>

    <div class="history-box">
      <div class="history-title">Riwayat</div>
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
          <?php while ($row = $histories->fetch_assoc()): ?>
            <?php $tgl = date("d M Y, H:i", strtotime($row['read_at'])); ?>
            <tr>
              <td class="judul-riwayat">
                <img src="Vector (5).png" class="icon-riwayat" onerror="this.style.display='none'">
                <div class="judul-wrap">
                  <?= htmlspecialchars($row['judul']) ?>
                  <div class="subjudul"><?= ucfirst($row['kategori']) ?></div>
                </div>
              </td>
              <td><span class="badge selesai"><?= ucfirst($row['kategori']) ?></span></td>
              <td><a href="isi.php?slug=<?= urlencode($row['slug']) ?>" class="baca-link">Baca Sekarang</a></td>
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

  </div>
</div>

<script>
  const hamburger = document.getElementById("hamburger");
  const sidebar   = document.getElementById("sidebar");
  hamburger.addEventListener("click", () => sidebar.classList.toggle("show"));
</script>
</body>
</html>