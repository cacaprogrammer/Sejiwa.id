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

    html, body { height: 100%; }
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      background: #ffffff;
      overflow-x: hidden;
    }

    .sidebar {
      position: fixed;
      top: 0; left: 0;
      width: 240px;
      height: 100vh;
      background: #4a2c18;
      padding: 30px 25px;
      color: white;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      transition: transform 0.35s ease-in-out;
      z-index: 1000;
    }

    .sidebar-logo { width: 55px; margin-bottom: 50px; }

    .sidebar a {
      width: 100%;
      display: flex;
      align-items: center;
      gap: 15px;
      font-size: 16px;
      color: white;
      text-decoration: none;
      padding: 14px 5px;
      margin: 2px 0;
      border-radius: 10px;
    }

    .icon-menu { width: 20px; height: 20px; object-fit: contain; margin-right: 14px; }
    .sidebar a.active { background: rgba(255,255,255,0.15); }
    .sidebar a:hover  { background: rgba(255,255,255,0.12); }

    .hamburger {
      display: none;
      position: fixed;
      top: 32px; right: 26px;
      font-size: 28px;
      color: #4a2c18;
      cursor: pointer;
      z-index: 1100;
    }

    .main-wrapper {
      margin-left: 230px;
      flex: 1;
      display: flex;
      flex-direction: column;
      transition: margin-left 0.35s ease;
    }

    .content { padding: 30px 40px; }

    .profile-header {
      background: #fff;
      padding: 18px 22px;
      border-radius: 10px;
      box-shadow: 0 0 7px rgba(0,0,0,0.12);
      margin-bottom: 30px;
    }
    .profile-header h3 { font-size: 19px; color: #333; font-weight: 600; }

    .edit-container {
      background: #fff;
      padding: 30px 35px;
      border-radius: 12px;
      max-width: 720px;
      box-shadow: 0 0 10px rgba(0,0,0,0.08);
      margin-bottom: 40px;
    }
    .edit-title { font-size: 20px; margin-bottom: 18px; color: #333; font-weight: 600; }

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
    .btn { padding: 10px 16px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; }
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
    .foto-wrap img { width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 3px solid #ddd; }
    .foto-wrap label.ubah-foto {
      position: absolute; bottom: 0; right: 0;
      background: #8b3e26; color: #fff;
      border-radius: 50%; width: 30px; height: 30px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; font-size: 13px;
    }
    #input-foto { display: none; }

    @media (max-width: 900px) {
      .hamburger { display: block; }
      .sidebar { transform: translateX(-100%); position: fixed; width: 240px; }
      .sidebar.show { transform: translateX(0); }
      .main-wrapper { margin-left: 0; }
      .content { margin-left: 0; padding: 20px; }
      .row { flex-direction: column; gap: 0; }
    }
  </style>
</head>
<body>

  <div class="hamburger" id="hamburger">
    <i class="fas fa-bars"></i>
  </div>

  <div class="sidebar" id="sidebar">
    <img src="logobenar.png" class="sidebar-logo">
    <a href="history.php">
      <img src="iconamoon_profile.png" class="icon-menu"> Profil
    </a>
    <a href="logout.php">
      <img src="Group.png" class="icon-menu"> Keluar
    </a>
  </div>

  <div class="main-wrapper">
    <main class="content">
      <div class="profile-header">
        <h3>Edit Profile</h3>
      </div>

      <section class="edit-container" aria-label="form ubah profil">
        <div class="edit-title">Edit Profil</div>

        <?php if ($pesan !== ""): ?>
          <div class="pesan-error"><?= htmlspecialchars($pesan) ?></div>
        <?php endif; ?>

        <form method="post" action="" enctype="multipart/form-data">

          <div style="display:flex; align-items:center; gap:18px; margin-bottom:18px;">
            <div class="foto-wrap">
              <img src="<?= htmlspecialchars($foto_src) ?>" alt="foto profil" id="preview-foto" onerror="this.src='https://i.pravatar.cc/160'">
              <label class="ubah-foto" for="input-foto" title="Ganti foto">
                <i class="fas fa-camera"></i>
              </label>
              <input type="file" name="foto" id="input-foto" accept="image/*">
            </div>
            <div>
              <div style="font-weight:700; font-size:18px; color:#333;">
                <?= htmlspecialchars($user['nama_lengkap']) ?>
              </div>
              <div style="color:#666; margin-top:6px;">
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
    </main>
  </div>

  <script>
    const hamburger = document.getElementById("hamburger");
    const sidebar   = document.getElementById("sidebar");
    hamburger.addEventListener("click", () => {
      sidebar.classList.toggle("show");
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