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

$pesan = "";
$tipe  = "";

if (isset($_POST['simpan'])) {
    $nama    = trim($_POST['nama_lengkap']);
    $email   = trim($_POST['email']);
    $negara  = trim($_POST['negara']);
    $kota    = trim($_POST['kota']);
    $pw_baru = $_POST['password_baru'];
    $foto_nama = $user['foto_profile'];

    if (!empty($_FILES['foto']['name'])) {
        $ext     = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $foto_nama = "profil_" . $_SESSION['id'] . "_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/" . $foto_nama);
        } else {
            $pesan = "Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.";
            $tipe  = "error";
        }
    }

    if ($pesan === "") {
        if (!empty($pw_baru)) {
            $hash = password_hash($pw_baru, PASSWORD_DEFAULT);
            $upd  = $conn->prepare("UPDATE tb_user SET nama_lengkap=?, email=?, negara=?, kota=?, password=?, foto_profile=? WHERE id=?");
            $upd->bind_param("ssssssi", $nama, $email, $negara, $kota, $hash, $foto_nama, $_SESSION['id']);
        } else {
            $upd  = $conn->prepare("UPDATE tb_user SET nama_lengkap=?, email=?, negara=?, kota=?, foto_profile=? WHERE id=?");
            $upd->bind_param("sssssi", $nama, $email, $negara, $kota, $foto_nama, $_SESSION['id']);
        }

        if ($upd->execute()) {
            $_SESSION['nama_lengkap'] = $nama;
            header("Location: history.php");
            exit();
        } else {
            $pesan = "Gagal menyimpan perubahan.";
            $tipe  = "error";
        }
    }
}

$foto = $user['foto_profile'] ?? null;
if ($foto && file_exists("uploads/" . $foto)) {
    $foto_src = "/website/uploads/" . $foto;
} else {
    $foto_src = "https://i.pravatar.cc/160";
}

// Notifikasi unread count
$stmt_unread = $conn->prepare("SELECT COUNT(*) AS total FROM tb_notifikasi WHERE user_id = ? AND dibaca = 0");
$stmt_unread->bind_param("i", $_SESSION['id']);
$stmt_unread->execute();
$unread_count = $stmt_unread->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Profile - Sejiwa.id</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }

    html, body { height: 100%; background: #f4f1ee; overflow-x: hidden; }

    /* ───── SIDEBAR ───── */
    .sidebar {
      position: fixed;
      top: 0; left: 0;
      width: 240px;
      height: 100vh;
      background: #4a2c18;
      padding: 28px 20px 28px;
      color: #fff;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      z-index: 1000;
      transition: transform .35s ease-in-out;
      overflow-y: auto;
    }
    .sidebar-logo { width: 52px; margin-bottom: 36px; }
    .sidebar-nav-label {
      font-size: 10px; font-weight: 700; letter-spacing: .08em;
      color: rgba(255,255,255,.45); text-transform: uppercase;
      padding: 0 6px; margin: 8px 0 4px;
    }
    .sidebar a {
      width: 100%; display: flex; align-items: center; gap: 12px;
      font-size: 14.5px; color: rgba(255,255,255,.85); text-decoration: none;
      padding: 11px 12px; margin: 2px 0; border-radius: 10px;
      position: relative; transition: background .15s;
      border-left: 3px solid transparent;
    }
    .sidebar a.active {
      background: rgba(255,255,255,.16);
      color: #fff; border-left-color: #f0c080; font-weight: 600;
    }
    .sidebar a:hover { background: rgba(255,255,255,.1); color: #fff; }
    .sidebar a i { width: 18px; text-align: center; font-size: 15px; flex-shrink: 0; }

    .notif-badge {
      position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
      background: #ef4444; color: #fff; font-size: 10px; font-weight: 700;
      min-width: 18px; height: 18px; border-radius: 9px;
      display: flex; align-items: center; justify-content: center; padding: 0 4px;
    }

    /* ───── HAMBURGER ───── */
    .hamburger {
      display: none; position: fixed; top: 14px; right: 18px;
      font-size: 22px; color: #4a2c18; cursor: pointer; z-index: 1100;
      background: #fff; width: 38px; height: 38px; border-radius: 8px;
      align-items: center; justify-content: center;
      box-shadow: 0 2px 8px rgba(0,0,0,.15);
    }

    /* ───── MAIN ───── */
    .main-wrapper {
      margin-left: 240px;
      min-height: 100vh;
      width: calc(100% - 240px);
      padding: 32px 40px;
    }

    /* ───── PAGE HEADER ───── */
    .page-header {
      background: #fff; padding: 16px 22px; border-radius: 12px;
      box-shadow: 0 1px 6px rgba(0,0,0,.08); margin-bottom: 24px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .page-header h3 { font-size: 18px; font-weight: 700; color: #2d1a0e; }

    /* ───── FORM ───── */
    .edit-container {
      background: #fff;
      padding: 30px 35px;
      border-radius: 14px;
      max-width: 720px;
      box-shadow: 0 1px 8px rgba(0,0,0,.09);
      margin-bottom: 40px;
    }
    .edit-title { font-size: 17px; font-weight: 700; color: #2d1a0e; margin-bottom: 18px; }

    .form-group { margin-bottom: 14px; }
    label { display: block; font-size: 14px; margin-bottom: 6px; color: #333; }
    input, select {
      width: 100%;
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid #ddd;
      font-size: 14px;
      background: #fff;
    }
    input:focus, select:focus {
      outline: none;
      border-color: #8b3e26;
      box-shadow: 0 0 0 3px rgba(139,62,38,0.07);
    }

    .row { display: flex; gap: 16px; }
    .col { flex: 1; }

    .actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 18px; }
    .btn { padding: 10px 16px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 13px; }
    .btn-secondary { background: #fff; border: 1px solid #ccc; color: #333; }
    .btn-primary   { background: #5a2f1f; color: #fff; }
    .btn-primary:hover { background: #7a3f2f; }

    .pesan-error {
      background: #fff0f0; color: #c0392b;
      border: 1px solid #fccaca;
      padding: 12px 16px; border-radius: 8px;
      margin-bottom: 18px; font-size: 14px;
    }

    .foto-wrap { position: relative; display: inline-block; }
    .foto-wrap img { width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 3px solid #e8ddd5; }
    .foto-wrap label.ubah-foto {
      position: absolute; bottom: 0; right: 0;
      background: #8b3e26; color: #fff;
      border-radius: 50%; width: 30px; height: 30px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; font-size: 13px;
    }
    #input-foto { display: none; }

    /* ───── RESPONSIVE ───── */
    @media (max-width: 900px) {
      .hamburger { display: flex; }
      .sidebar { transform: translateX(-100%); }
      .sidebar.show { transform: translateX(0); }
      .main-wrapper { margin-left: 0; width: 100%; padding: 20px 16px; padding-top: 60px; }
      .row { flex-direction: column; gap: 0; }
    }
  </style>
</head>
<body>

<div class="hamburger" id="hamburger"><i class="fas fa-bars"></i></div>

<!-- ═══ SIDEBAR ═══ -->
<div class="sidebar" id="sidebar">
  <img src="logobenar.png" class="sidebar-logo" alt="Logo">

  <div class="sidebar-nav-label">Menu</div>

  <a href="history.php?tab=profil">
    <i class="fas fa-user"></i> Profil &amp; Riwayat
  </a>

  <a href="history.php?tab=artikel">
    <i class="fas fa-pen-to-square"></i> Kelola Artikel
  </a>

  <a href="history.php?tab=notifikasi">
    <i class="fas fa-bell"></i> Notifikasi
    <?php if ($unread_count > 0): ?>
      <span class="notif-badge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
    <?php endif; ?>
  </a>
</div>

<!-- ═══ MAIN ═══ -->
<div class="main-wrapper">

  <div class="page-header">
    <h3><i class="fas fa-user-pen" style="color:#8b3e26;margin-right:8px"></i>Edit Profil</h3>
  </div>

  <section class="edit-container" aria-label="form ubah profil">
    <div class="edit-title">Ubah Informasi Akun</div>

    <?php if ($pesan !== ""): ?>
      <div class="pesan-error"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data">

      <div style="display:flex; align-items:center; gap:18px; margin-bottom:20px;">
        <div class="foto-wrap">
          <img src="<?= htmlspecialchars($foto_src) ?>" alt="foto profil" id="preview-foto" onerror="this.src='https://i.pravatar.cc/160'">
          <label class="ubah-foto" for="input-foto" title="Ganti foto">
            <i class="fas fa-camera"></i>
          </label>
          <input type="file" name="foto" id="input-foto" accept="image/*">
        </div>
        <div>
          <div style="font-weight:700; font-size:17px; color:#2d1a0e;">
            <?= htmlspecialchars($user['nama_lengkap']) ?>
          </div>
          <div style="color:#888; font-size:13px; margin-top:5px;">
            @<?= htmlspecialchars($user['username']) ?>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Nama Lengkap</label>
        <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
      </div>

      <div class="form-group">
        <label>Kata Sandi Baru <span style="color:#999; font-size:12px;">(kosongkan jika tidak ingin ganti)</span></label>
        <input type="password" name="password_baru" placeholder="Masukkan kata sandi baru">
      </div>

      <div class="row" style="margin-top:8px;">
        <div class="col form-group">
          <label>Negara</label>
          <select name="negara" id="select-negara" onchange="updateKota()">
            <?php
            $negara_list = ["Indonesia","Malaysia","Singapura","Brunei Darussalam","Thailand","Filipina","Vietnam","Myanmar","Laos","Kamboja","Timor Leste","Australia","Jepang","Korea Selatan","China","India","Amerika Serikat","Inggris","Belanda","Jerman","Prancis","Lainnya"];
            $negara_user = $user['negara'] ?? 'Indonesia';
            foreach ($negara_list as $n) {
                $sel = ($negara_user === $n) ? 'selected' : '';
                echo "<option value=\"$n\" $sel>$n</option>";
            }
            ?>
          </select>
        </div>
        <div class="col form-group">
          <label>Kota</label>
          <select name="kota" id="select-kota">
            <?php
            $kota_user = $user['kota'] ?? '';
            $kota_map = [
              "Indonesia" => ["Jakarta","Surabaya","Bandung","Medan","Semarang","Makassar","Palembang","Tangerang","Depok","Bekasi","Yogyakarta","Malang","Denpasar","Batam","Pekanbaru","Banjarmasin","Pontianak","Manado","Padang","Aceh","Bogor","Serang","Cilegon","Tasikmalaya","Cimahi","Balikpapan","Samarinda","Bandar Lampung","Jambi","Mataram","Kupang","Ambon","Jayapura","Sorong","Ternate","Kendari","Palu","Gorontalo","Bengkulu","Pangkal Pinang","Tanjung Pinang","Dumai","Binjai","Pematangsiantar","Tebing Tinggi","Padang Sidempuan","Gunungsitoli","Langsa","Lhokseumawe","Sabang","Subulussalam","Solok","Sawahlunto","Padang Panjang","Bukittinggi","Payakumbuh","Pariaman","Lubuklinggau","Pagar Alam","Prabumulih","Baturaja","Metro","Banjar","Sukabumi","Cirebon","Purwokerto","Magelang","Salatiga","Klaten","Surakarta","Kediri","Blitar","Madiun","Mojokerto","Pasuruan","Probolinggo","Batu","Jember","Banyuwangi","Situbondo","Bondowoso","Lumajang","Gresik","Lamongan","Tuban","Bojonegoro","Ngawi","Magetan","Ponorogo","Tulungagung","Trenggalek","Pacitan","Sampang","Pamekasan","Sumenep","Bangkalan","Singaraja","Gianyar","Tabanan","Negara","Selong","Bima","Dompu","Maumere","Ende","Ruteng","Waingapu","Atambua","Kefamenanu","Soe","Singkawang","Sambas","Sanggau","Sintang","Putussibau","Palangkaraya","Sampit","Muara Teweh","Kotabaru","Tanjung","Amuntai","Banjarbaru","Martapura","Nunukan","Tarakan","Bontang","Tenggarong","Sendawar","Tolitoli","Buol","Luwuk","Kolaka","Baubau","Raha","Wanggudu","Sofifi","Tidore","Tobelo","Tual","Saumlaki","Fakfak","Manokwari","Biak","Nabire","Wamena","Merauke","Timika"],
              "Malaysia"  => ["Kuala Lumpur","Johor Bahru","Penang","Ipoh","Kota Kinabalu","Kuching","Shah Alam","Petaling Jaya"],
              "Singapura" => ["Singapura"],
              "Australia" => ["Sydney","Melbourne","Brisbane","Perth","Adelaide","Canberra"],
              "Jepang"    => ["Tokyo","Osaka","Kyoto","Yokohama","Nagoya","Sapporo","Fukuoka"],
              "China"     => ["Beijing","Shanghai","Guangzhou","Shenzhen","Chengdu","Hangzhou"],
              "Amerika Serikat" => ["New York","Los Angeles","Chicago","Houston","Phoenix","Philadelphia","San Antonio","San Diego"],
              "Inggris"   => ["London","Manchester","Birmingham","Leeds","Glasgow","Liverpool"],
              "Lainnya"   => ["Lainnya"],
            ];
            $kota_sekarang = $kota_map[$negara_user] ?? ["Lainnya"];
            foreach ($kota_sekarang as $k) {
                $sel = ($kota_user === $k) ? 'selected' : '';
                echo "<option value=\"$k\" $sel>$k</option>";
            }
            ?>
          </select>
        </div>
      </div>

      <div class="actions">
        <button type="button" class="btn btn-secondary" onclick="window.history.back()">Kembali</button>
        <button type="submit" name="simpan" class="btn btn-primary">Simpan Perubahan</button>
      </div>

    </form>
  </section>

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

  const kotaMap = {
    "Indonesia": ["Jakarta","Surabaya","Bandung","Medan","Semarang","Makassar","Palembang","Tangerang","Depok","Bekasi","Yogyakarta","Malang","Denpasar","Batam","Pekanbaru","Banjarmasin","Pontianak","Manado","Padang","Aceh","Bogor","Serang","Cilegon","Tasikmalaya","Cimahi","Balikpapan","Samarinda","Bandar Lampung","Jambi","Mataram","Kupang","Ambon","Jayapura","Sorong","Ternate","Kendari","Palu","Gorontalo","Bengkulu","Pangkal Pinang","Tanjung Pinang","Dumai","Binjai","Pematangsiantar","Tebing Tinggi","Padang Sidempuan","Gunungsitoli","Langsa","Lhokseumawe","Sabang","Subulussalam","Solok","Sawahlunto","Padang Panjang","Bukittinggi","Payakumbuh","Pariaman","Lubuklinggau","Pagar Alam","Prabumulih","Baturaja","Metro","Banjar","Sukabumi","Cirebon","Purwokerto","Magelang","Salatiga","Klaten","Surakarta","Kediri","Blitar","Madiun","Mojokerto","Pasuruan","Probolinggo","Batu","Jember","Banyuwangi","Situbondo","Bondowoso","Lumajang","Gresik","Lamongan","Tuban","Bojonegoro","Ngawi","Magetan","Ponorogo","Tulungagung","Trenggalek","Pacitan","Sampang","Pamekasan","Sumenep","Bangkalan","Singaraja","Gianyar","Tabanan","Negara","Selong","Bima","Dompu","Maumere","Ende","Ruteng","Waingapu","Atambua","Kefamenanu","Soe","Singkawang","Sambas","Sanggau","Sintang","Putussibau","Palangkaraya","Sampit","Muara Teweh","Kotabaru","Tanjung","Amuntai","Banjarbaru","Martapura","Nunukan","Tarakan","Bontang","Tenggarong","Sendawar","Tolitoli","Buol","Luwuk","Kolaka","Baubau","Raha","Wanggudu","Sofifi","Tidore","Tobelo","Tual","Saumlaki","Fakfak","Manokwari","Biak","Nabire","Wamena","Merauke","Timika"],
    "Malaysia":  ["Kuala Lumpur","Johor Bahru","Penang","Ipoh","Kota Kinabalu","Kuching","Shah Alam","Petaling Jaya"],
    "Singapura": ["Singapura"],
    "Australia": ["Sydney","Melbourne","Brisbane","Perth","Adelaide","Canberra"],
    "Jepang":    ["Tokyo","Osaka","Kyoto","Yokohama","Nagoya","Sapporo","Fukuoka"],
    "China":     ["Beijing","Shanghai","Guangzhou","Shenzhen","Chengdu","Hangzhou"],
    "Amerika Serikat": ["New York","Los Angeles","Chicago","Houston","Phoenix","Philadelphia","San Antonio","San Diego"],
    "Inggris":   ["London","Manchester","Birmingham","Leeds","Glasgow","Liverpool"],
  };

  function updateKota() {
    const negara  = document.getElementById("select-negara").value;
    const kotaSel = document.getElementById("select-kota");
    const kota    = kotaMap[negara] || ["Lainnya"];
    kotaSel.innerHTML = "";
    kota.forEach(k => {
      const opt = document.createElement("option");
      opt.value = k;
      opt.textContent = k;
      kotaSel.appendChild(opt);
    });
  }

  document.getElementById("input-foto").addEventListener("change", function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = e => {
        document.getElementById("preview-foto").src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });
</script>

</body>
</html>