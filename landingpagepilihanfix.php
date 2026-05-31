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

// Hitung notif belum dibaca
$_nb_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tb_notifikasi WHERE user_id = ? AND dibaca = 0");
$_nb_stmt->bind_param("i", $_SESSION['id']);
$_nb_stmt->execute();
$_nb_count = $_nb_stmt->get_result()->fetch_assoc()['total'];

// Ambil 5 artikel Sejarah
$q_sejarah = $conn->query("
    SELECT a.id, a.judul, a.slug, a.thumbnail,
           COALESCE(AVG(u.rating), 0) AS rata_rating,
           COUNT(u.id) AS jml_ulasan
    FROM tb_artikel a
    LEFT JOIN tb_ulasan u ON u.artikel_id = a.id
    WHERE a.status = 'published'
      AND a.id IN (1, 4, 11, 12, 10)
    GROUP BY a.id
    ORDER BY FIELD(a.id, 1, 4, 11, 12, 10)
");
$list_sejarah = $q_sejarah ? $q_sejarah->fetch_all(MYSQLI_ASSOC) : [];

// Ambil 5 artikel Biografi
$q_biografi = $conn->query("
    SELECT a.id, a.judul, a.slug, a.thumbnail,
           COALESCE(AVG(u.rating), 0) AS rata_rating,
           COUNT(u.id) AS jml_ulasan
    FROM tb_artikel a
    LEFT JOIN tb_ulasan u ON u.artikel_id = a.id
    WHERE a.status = 'published'
      AND a.kategori = 'biografi'
    GROUP BY a.id
    ORDER BY a.id ASC
    LIMIT 5
");
$list_biografi = $q_biografi ? $q_biografi->fetch_all(MYSQLI_ASSOC) : [];

function renderBintang($nilai) {
    $penuh  = round($nilai);
    $kosong = 5 - $penuh;
    $html   = '';
    for ($i = 0; $i < $penuh;  $i++) $html .= '★';
    for ($i = 0; $i < $kosong; $i++) $html .= '<span class="unfilled-star">★</span>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sejiwa.id</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; background-color: #f7f7f7; }

        /* ── NAVBAR ── */
        header {
            height: 50px; background-color: #4a2c18; color: #f7f7f7;
            padding: 0 15px; display: flex; justify-content: space-between;
            align-items: center; position: sticky; top: 0; left: 0; width: 100%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); z-index: 1000;
        }
        .logo { display: flex; align-items: center; }
        .logo-img { height: 40px; width: auto; }
        .logo-text-img { height: 70px; width: auto; margin-left: -12px; position: relative; top: -2px; }

        .dropdown { position: relative; }
        .dropdown-menu {
            position: absolute; top: calc(100% + 14px); left: -10px;
            background: #fff; border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.18);
            min-width: 190px; padding: 8px 0; display: none;
            border: 1px solid #f0e6de; z-index: 2000;
        }
        .dropdown-menu::before {
            content: ''; position: absolute; top: -8px; left: 24px;
            width: 16px; height: 16px; background: #fff;
            border-left: 1px solid #f0e6de; border-top: 1px solid #f0e6de;
            transform: rotate(45deg);
        }
        .dropdown-menu .ddm-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 16px; color: #333; font-size: 14px;
            font-weight: 500; text-decoration: none; transition: background .15s;
        }
        .dropdown-menu .ddm-item:hover { background: #fdf5f0; color: #4a2c18; }
        .dropdown-menu .ddm-item i { width: 18px; text-align: center; color: #a3826f; font-size: 15px; }
        .dropdown-menu .ddm-separator { height: 0.5px; background: #f3e8e0; margin: 4px 8px; }

        nav ul { display: flex; list-style: none; padding: 0; }
        nav ul li { margin-left: 20px; }
        nav ul li a { text-decoration: none; color: inherit; font-size: 0.95em; font-weight: bold; transition: 0.3s; }
        nav ul li a:hover { color: #a3826f; }

        .hamburger-menu { display: none; background: none; border: none; color: #f7f7f7; font-size: 1.5em; cursor: pointer; padding: 5px; margin-right: 10px; }
        .sidebar { height: 100%; width: 0; position: fixed; z-index: 1001; top: 0; right: 0; background-color: #4a2c18; overflow-x: hidden; transition: 0.3s; padding-top: 60px; box-shadow: -5px 0 15px rgba(0,0,0,0.4); }
        .sidebar a { padding: 15px 25px; text-decoration: none; font-size: 18px; color: #f7f7f7; display: block; transition: 0.3s; }
        .sidebar a:hover { background-color: #6d4c41; }
        .sidebar .close-btn { position: absolute; top: 0; right: 15px; font-size: 36px; margin-left: 50px; color: #f7f7f7; border: none; background: none; cursor: pointer; }
        .sidebar-user-icon { padding: 20px 25px; border-bottom: 1px solid #6d4c41; margin-bottom: 10px; }
        .sidebar-user-icon a { font-size: 20px; font-weight: bold; }
        .sidebar-user-icon i { margin-right: 10px; }
        .dropdown-menu-sidebar { display: none; background-color: #724636; padding: 5px 0; }
        .dropdown-menu-sidebar .dropdown-inner { background: #fff; border-radius: 8px; border: 2px solid #724636; padding: 2px 0; margin: 5px 15px; }
        .dropdown-menu-sidebar .dropdown-inner a { color: #000; padding: 5px 10px; font-size: 14px; text-align: center; }
        .dropdown-menu-sidebar .dropdown-separator { height: 5px; background: #724636; margin: 0 15px; }

        .profile-dropdown { position: relative; }
        .profile-dropdown-trigger { display: flex; align-items: center; cursor: pointer; color: #fff; margin-right: 15px; background: none; border: none; padding: 0; }
        .profile-dropdown-trigger i { font-size: 1.6em; }
        .profile-dropdown-trigger:hover i { color: #a3826f; }
        .profile-dropdown-menu {
            position: absolute; top: calc(100% + 14px); right: 0;
            background: #fff; border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.18);
            min-width: 215px; padding: 8px 0; display: none; z-index: 2000;
            border: 1px solid #f0e6de;
        }
        .profile-dropdown-menu.show { display: block; }
        .profile-dropdown-menu::before {
            content: ''; position: absolute; top: -8px; right: 18px;
            width: 16px; height: 16px; background: #fff;
            border-left: 1px solid #f0e6de; border-top: 1px solid #f0e6de;
            transform: rotate(45deg);
        }
        .pdm-user { padding: 12px 18px 10px; border-bottom: 1px solid #f3e8e0; margin-bottom: 4px; }
        .pdm-user-name { font-size: 14px; font-weight: 700; color: #2d1a0e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px; }
        .pdm-user-role { font-size: 11px; color: #a3826f; margin-top: 1px; }
        .pdm-item { display: flex; align-items: center; gap: 12px; padding: 10px 18px; color: #333; font-size: 14px; font-weight: 500; text-decoration: none; transition: background .15s; }
        .pdm-item:hover { background: #fdf5f0; color: #4a2c18; }
        .pdm-item i { width: 18px; text-align: center; color: #a3826f; font-size: 15px; }
        .pdm-item.logout { border-top: 1px solid #f3e8e0; margin-top: 4px; color: #c0392b; }
        .pdm-item.logout i { color: #c0392b; }
        .pdm-item.logout:hover { background: #fff5f5; }
        .pdm-notif-badge { margin-left: auto; background: #ef4444; color: #fff; font-size: 11px; font-weight: 700; min-width: 18px; height: 18px; border-radius: 9px; display: flex; align-items: center; justify-content: center; padding: 0 4px; }

        @media (max-width: 1024px) {
            nav, .profile-dropdown { display: none; }
            .hamburger-menu { display: block; }
        }

        /* Hero */
        .hero-section { display: flex; max-width: 1200px; margin: 20px auto; padding: 20px 50px; gap: 50px; align-items: center; }
        .hero-text { flex: 1; max-width: 50%; }
        .hero-text h1 { font-size: 2.7em; color: #000; margin-bottom: 30px; line-height: 1.1; font-weight: 800; text-shadow: 10px 10px 10px rgba(0,0,0,0.3); }
        .hero-text p { color: #666; margin-bottom: 30px; font-size: 1.1em; }
        .hero-button { background-color: #4a2c18; color: white; padding: 10px 25px; border: none; border-radius: 15px; cursor: pointer; font-weight: bold; transition: background-color 0.3s; }
        .hero-button:hover { background-color: #a3826f; }
        .image-container { flex: 1; display: flex; gap: 15px; position: relative; padding: 20px; background-color: #f7f7f7; }
        .left-panel { flex: 1; }
        .main-image { width: 100%; height: 350px; object-fit: cover; border-radius: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin: 70px auto; }
        .right-panel { flex: 1; display: flex; flex-direction: column; gap: 15px; }
        .top-image, .bottom-image { width: 100%; height: 250px; object-fit: cover; border-radius: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }

        /* Rekomendasi */
        .rekomendasi-section { padding: 20px 50px; margin-top: 50px; }
        .rekomendasi-section h2 { margin-bottom: 2px; color: black; }
        .rekomendasi-section p { position: relative; top: -10px; color: #666; }
        .cards-container { display: flex; overflow-x: scroll; overflow-y: hidden; padding-bottom: 20px; flex-wrap: nowrap; scroll-behavior: smooth; }
        .cards-container::-webkit-scrollbar { display: none; }
        .card { flex: 0 0 250px; margin-right: 20px; padding: 15px; background-color: #4a2c18; border-radius: 15px; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.2s; display: flex; flex-direction: column; }
        .card:hover { transform: translateY(-5px); }
        .card img { width: 100%; height: 345px; object-fit: cover; border-radius: 10px; }
        .card h3 { font-size: 1.1em; margin: 10px 0 5px; color: #f0f0f0; flex: 1; }
        .rating-stars { font-size: 1.5rem; color: #fbbf24; margin-bottom: 0.75rem; letter-spacing: 2px; position: relative; top: -10px; }
        .rating-stars .unfilled-star { color: rgba(255,255,255,0.5); }
        .card button { display: flex; align-items: center; justify-content: center; padding: 10px 20px; background-color: #f7f7f7; color: black; border: none; border-radius: 20px; cursor: pointer; margin-top: auto; width: 100%; font-weight: bold; transition: background-color 0.2s, transform 0.2s; }
        .card button:hover { background-color: #e0e0e0; transform: scale(1.02); }
        .card button img { width: 18px; height: 18px; margin-left: 5px; object-fit: contain; }

        /* Mengapa */
        .container { display: flex; align-items: center; justify-content: center; gap: 50px; background-color: white; padding: 50px; border-radius: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 1000px; margin: 40px auto; }
        .images { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .images img { width: 250px; height: 180px; object-fit: cover; }
        .img1 { border-top-left-radius: 60px; box-shadow: -13px -15px 0px #4a2c18; }
        .img2 { border-top-right-radius: 60px; }
        .img3 { border-bottom-left-radius: 60px; }
        .img4 { border-bottom-right-radius: 60px; box-shadow: 13px 15px 0px #4a2c18; }
        .text { max-width: 450px; }
        .text h2 { margin-bottom: 10px; color: #333; }
        .text ul { list-style-type: disc; padding-left: 20px; color: #444; line-height: 1.6; }

        /* Footer */
        .footer { background-color: #4a2c18; color: #fff; padding: 40px 20px; font-size: 14px; }
        .footer-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1.2fr 2.5fr 1fr; gap: 20px; align-items: flex-start; }
        .footer-kolom { padding: 0 10px; }
        .logo-info { display: flex; flex-direction: column; align-items: flex-start; }
        .logo-title { display: flex; align-items: center; margin-bottom: 10px; }
        .logo-title img { width: 40px; height: 40px; margin-right: 8px; }
        .logo-title h3 { color: white; margin: 0; font-size: 18px; }
        .logo-info p { margin: 0 0 15px 0; line-height: 1.5; }
        .sosial-media-wrapper { margin-top: 10px; }
        .sosial-media { display: flex; margin-bottom: 5px; }
        .sosial-media a { color: #fff; font-size: 24px; margin-right: 15px; text-decoration: none; }
        .middle-section { display: flex; flex-direction: column; }
        .navigasi-horizontal { display: flex; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; color: white; }
        .navigasi-horizontal a { color: inherit; text-decoration: none; font-weight: bold; font-size: 16px; padding-right: 20px; }
        .middle-section h4 { color: white; margin-top: 0; margin-bottom: 10px; font-weight: bold; font-size: 16px; }
        .kontak .item-kontak { margin-bottom: 15px; }
        .kontak .item-kontak p { margin: 2px 0; }
        .kontak .item-kontak p:first-child { font-weight: bold; margin-bottom: 2px; color: white; display: flex; align-items: center; }
        .kontak .item-kontak i { margin-right: 8px; font-size: 18px; color: #fff; }

        a { text-decoration: none; }

        @media (max-width: 768px) {
            .hero-section { flex-direction: column; padding: 20px; gap: 30px; }
            .hero-text { max-width: 100%; text-align: center; }
            .hero-text h1 { font-size: 2.2em; margin-bottom: 20px; }
            .hero-button { width: 100%; max-width: 250px; }
            .image-container { flex-direction: column; padding: 0; }
            .main-image { height: 300px; margin: 0 auto 15px auto; }
            .top-image, .bottom-image { height: 180px; }
            .footer-container { grid-template-columns: 1fr; gap: 30px; }
            .navigasi-horizontal { flex-direction: column; margin-bottom: 15px; }
            .navigasi-horizontal a { margin-bottom: 10px; padding-right: 0; }
        }
        @media (max-width: 900px) {
            .container { flex-direction: column; gap: 30px; padding: 30px 20px; }
            .images { grid-template-columns: repeat(2, 1fr); gap: 15px; width: 100%; }
            .images img { width: 100%; height: auto; min-height: 120px; max-height: 150px; }
            .text { max-width: 100%; }
        }
        @media (max-width: 640px) { .rekomendasi-section { padding: 20px; } }
    </style>
</head>
<body>

<?php include 'navbar_user.php'; ?>

<div class="hero-section">
    <div class="hero-text">
        <h1>EXPLORE SEJARAH BANGSA INDONESIA</h1>
        <p>Explore Sejarah Indonesia, menghadirkan artikel-artikel menarik yang mengajak Anda menyelami kisah-kisah epik bangsa. Temukan peristiwa bersejarah dan tokoh penting yang membentuk Indonesia seperti yang kita kenal hari ini.</p>
        <a href="viewmore.php"><button class="hero-button">Lihat Lebih Banyak</button></a>
    </div>
    <div class="image-container">
        <div class="left-panel">
            <img src="candi1.jpg" alt="pura" class="main-image">
        </div>
        <div class="right-panel">
            <img src="candi2.jpg" alt="Candi Borobudur" class="top-image">
            <img src="candi3.jpg" alt="Candi Prambanan" class="bottom-image">
        </div>
    </div>
</div>

<section class="rekomendasi-section">
    <h2>Rekomendasi Artikel</h2>
    <p>Temukan lebih banyak hal menarik!</p>
    <div class="cards-container">
        <?php if (empty($list_sejarah)): ?>
            <div style="color:#666;padding:20px;font-size:14px;">Belum ada artikel sejarah yang dipublikasikan.</div>
        <?php else: ?>
            <?php foreach ($list_sejarah as $art): ?>
            <div class="card">
                <?php
                $thumb = $art['thumbnail'];
                $src   = (!empty($thumb) && file_exists(__DIR__.'/uploads/'.$thumb)) ? 'uploads/'.$thumb : (!empty($thumb) ? $thumb : 'cover1.jpg');
                ?>
                <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($art['judul']) ?>" onerror="this.src='cover1.jpg'">
                <h3><?= htmlspecialchars($art['judul']) ?></h3>
                <div class="rating-stars"><?= renderBintang(round($art['rata_rating'])) ?></div>
                <a href="detail_artikel.php?slug=<?= urlencode($art['slug']) ?>">
                    <button>Baca Sekarang <img src="majesticons_arrow-right.png" alt=""></button>
                </a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="rekomendasi-section">
    <h2>Rekomendasi Biografi Tokoh</h2>
    <p>Temukan lebih banyak hal menarik!</p>
    <div class="cards-container">
        <?php if (empty($list_biografi)): ?>
            <div style="color:#666;padding:20px;font-size:14px;">Belum ada artikel biografi yang dipublikasikan.</div>
        <?php else: ?>
            <?php foreach ($list_biografi as $art): ?>
            <div class="card">
                <?php
                $thumb = $art['thumbnail'];
                $src   = (!empty($thumb) && file_exists(__DIR__.'/uploads/'.$thumb)) ? 'uploads/'.$thumb : (!empty($thumb) ? $thumb : 'tokoh1.jpg');
                ?>
                <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($art['judul']) ?>" onerror="this.src='tokoh1.jpg'">
                <h3><?= htmlspecialchars($art['judul']) ?></h3>
                <a href="detail_artikel.php?slug=<?= urlencode($art['slug']) ?>">
                    <button>Baca Sekarang <img src="majesticons_arrow-right.png" alt=""></button>
                </a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <div class="images">
        <img src="bg1.png" alt="Foto 1" class="img1">
        <img src="bg2.png" alt="Foto 2" class="img2">
        <img src="bg3.png" alt="Foto 3" class="img3">
        <img src="bg1.1.jpg" alt="Foto 4" class="img4">
    </div>
    <div class="text">
        <h2>Mengapa Memilih Kami?</h2>
        <ul>
            <li>Penjelasan dibuat menarik dan mudah dipahami untuk semua usia</li>
            <li>Gratis & mudah diakses, bisa dibuka kapan saja tanpa biaya</li>
            <li>Membantu mengenal sejarah Indonesia secara mudah</li>
            <li>Menyajikan informasi sejarah Indonesia yang terpercaya</li>
            <li>Desain interaktif sehingga belajar sejarah terasa seru, bukan membosankan</li>
        </ul>
    </div>
</div>

<div class="footer">
    <div class="footer-container">
        <div class="footer-kolom logo-info">
            <div class="logo-title">
                <img src="logobenar.png" alt="Logo Sejiwa">
                <h3>Sejiwa.id</h3>
            </div>
            <p>Sejarah Indonesia: Warisan berharga, Sumber Pengetahuan</p>
            <div class="sosial-media-wrapper">
                <div class="sosial-media">
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                </div>
                <span>Ikuti Kami</span>
            </div>
        </div>
        <div class="footer-kolom middle-section">
            <div class="navigasi-horizontal">
                <a href="landingpagepilihanfix.php">Beranda</a>
                <a href="daftarsearchSejarah.php">Artikel</a>
                <a href="favorit.php">Favorit</a>
                <a href="rating.php">Ulasan</a>
            </div>
            <div class="tentang-kita-container">
                <h4>Tentang Kita</h4>
                <p>Sejiwa.id adalah proyek kami untuk mengeksplorasi sejarah Indonesia dan berbagai cerita yang berharga dari masa lalu.</p>
            </div>
        </div>
        <div class="footer-kolom kontak">
            <div class="item-kontak">
                <p><i class="fas fa-envelope"></i> Email:</p>
                <p>blaabla@gmail.com</p>
                <p>uwthh@gmail.com</p>
            </div>
            <div class="item-kontak">
                <p><i class="fas fa-phone"></i> Call:</p>
                <p>(480) 555-0103</p>
                <p>(406) 555-0120</p>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleDropdown(event) {
        event.preventDefault();
        const menu = document.getElementById('dropdown-menu');
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('dropdown-menu');
        const dd   = document.querySelector('.dropdown');
        if (dd && !dd.contains(e.target)) menu.style.display = 'none';
    });

    const sidebar         = document.getElementById('sidebar');
    const hamburgerBtn    = document.getElementById('hamburger-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    function openSidebar() {
        const w = window.innerWidth;
        sidebar.style.width = w < 350 ? '90%' : w < 450 ? '300px' : '250px';
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.style.width = '0';
        document.body.style.overflow = '';
        const ad = document.querySelector('.dropdown-menu-sidebar[style*="display: block"]');
        if (ad) ad.style.display = 'none';
    }
    function toggleDropdownSidebar(event) {
        event.preventDefault();
        const dd  = event.target.nextElementSibling;
        const all = document.querySelectorAll('.dropdown-menu-sidebar');
        all.forEach(d => { if (d !== dd) d.style.display = 'none'; });
        dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
    }
    hamburgerBtn.addEventListener('click', openSidebar);
    closeSidebarBtn.addEventListener('click', closeSidebar);

    function toggleProfileDropdown(event) {
        event.stopPropagation();
        document.getElementById('profile-dropdown-menu').classList.toggle('show');
    }
    document.addEventListener('click', function(e) {
        const pd   = document.getElementById('profile-dropdown');
        const menu = document.getElementById('profile-dropdown-menu');
        if (menu && pd && !pd.contains(e.target)) menu.classList.remove('show');
    });
</script>
</body>
</html>