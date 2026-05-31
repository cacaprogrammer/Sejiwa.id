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

// Hitung notif belum dibaca (untuk badge navbar & sidebar)
$_nb_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tb_notifikasi WHERE user_id = ? AND dibaca = 0");
$_nb_stmt->bind_param("i", $_SESSION['id']);
$_nb_stmt->execute();
$_nb_count = $_nb_stmt->get_result()->fetch_assoc()['total'];

// Ambil semua artikel biografi (horizontal scroll)
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
");
$list_biografi = $q_biografi ? $q_biografi->fetch_all(MYSQLI_ASSOC) : [];

// Ambil semua artikel sejarah (grid responsive)
$q_sejarah = $conn->query("
    SELECT a.id, a.judul, a.slug, a.thumbnail,
           COALESCE(AVG(u.rating), 0) AS rata_rating,
           COUNT(u.id) AS jml_ulasan
    FROM tb_artikel a
    LEFT JOIN tb_ulasan u ON u.artikel_id = a.id
    WHERE a.status = 'published'
      AND a.kategori = 'sejarah'
    GROUP BY a.id
    ORDER BY a.id ASC
");
$list_sejarah = $q_sejarah ? $q_sejarah->fetch_all(MYSQLI_ASSOC) : [];

// Helper render bintang
function renderBintang($nilai) {
    $penuh  = round($nilai);
    $kosong = 5 - $penuh;
    $html   = '';
    for ($i = 0; $i < $penuh;  $i++) $html .= '★';
    for ($i = 0; $i < $kosong; $i++) $html .= '<span class="unfilled-star">★</span>';
    return $html;
}

// Helper thumbnail
function thumbSrc($thumb, $fallback = 'logobenar.png') {
    if (empty($thumb)) return $fallback;
    if (file_exists(__DIR__ . '/uploads/' . $thumb)) return 'uploads/' . $thumb;
    if (file_exists(__DIR__ . '/' . $thumb)) return $thumb;
    return $fallback;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>viewmore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reset Default Browser */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f7f7f7;
        }

        /* Navbar */
        header {
            height: 50px;
            background-color: #4a2c18;
            color: #f7f7f7;
            padding: 0 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            left: 0;
            width: 100%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo-img {
            height: 40px;
            width: auto;
        }
        .logo-text-img {
            height: 70px;
            width: auto;
            margin-left: -12px;
            position: relative;
            top: -2px;
        }

        /* ── Artikel Dropdown Desktop ── */
        .dropdown {
            position: relative;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 14px);
            left: -10px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.18);
            min-width: 190px;
            padding: 8px 0;
            display: none;
            border: 1px solid #f0e6de;
            z-index: 2000;
        }

        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -8px;
            left: 24px;
            width: 16px;
            height: 16px;
            background: #fff;
            border-left: 1px solid #f0e6de;
            border-top: 1px solid #f0e6de;
            transform: rotate(45deg);
        }

        .dropdown-menu .ddm-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: background .15s;
        }

        .dropdown-menu .ddm-item:hover {
            background: #fdf5f0;
            color: #4a2c18;
        }

        .dropdown-menu .ddm-item i {
            width: 18px;
            text-align: center;
            color: #a3826f;
            font-size: 15px;
        }

        .dropdown-menu .ddm-separator {
            height: 0.5px;
            background: #f3e8e0;
            margin: 4px 8px;
        }

        /* Navbar Desktop */
        nav ul {
            display: flex;
            list-style: none;
            padding: 0;
        }
        nav ul li {
            margin-left: 20px;
        }
        nav ul li a {
            text-decoration: none;
            color: inherit;
            font-size: 0.95em;
            font-weight: bold;
            transition: 0.3s;
        }
        nav ul li a:hover {
            color: #a3826f;
        }

        /* Hamburger & Sidebar (Mobile) */
        .hamburger-menu {
            display: none;
            background: none;
            border: none;
            color: #f7f7f7;
            font-size: 1.5em;
            cursor: pointer;
            padding: 5px;
            margin-right: 10px;
        }
        .sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            z-index: 1001;
            top: 0;
            right: 0;
            background-color: #4a2c18;
            overflow-x: hidden;
            transition: 0.3s;
            padding-top: 60px;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.4);
        }
        .sidebar a {
            padding: 15px 25px;
            text-decoration: none;
            font-size: 18px;
            color: #f7f7f7;
            display: block;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background-color: #6d4c41;
        }
        .sidebar .close-btn {
            position: absolute;
            top: 0;
            right: 15px;
            font-size: 36px;
            margin-left: 50px;
            color: #f7f7f7;
            border: none;
            background: none;
            cursor: pointer;
        }
        .sidebar-user-icon {
            padding: 20px 25px;
            border-bottom: 1px solid #6d4c41;
            margin-bottom: 10px;
        }
        .sidebar-user-icon a {
            font-size: 20px;
            font-weight: bold;
        }
        .sidebar-user-icon i {
            margin-right: 10px;
        }

        /* Dropdown Sidebar (Mobile) */
        .dropdown-menu-sidebar {
            display: none;
            background-color: #724636;
            padding: 5px 0;
        }
        .dropdown-menu-sidebar .dropdown-inner {
            background: #ffffff;
            border-radius: 8px;
            border: 2px solid #724636;
            padding: 2px 0;
            margin: 5px 15px;
        }
        .dropdown-menu-sidebar .dropdown-inner a {
            color: #000;
            padding: 5px 10px;
            font-size: 14px;
            text-align: center;
        }
        .dropdown-menu-sidebar .dropdown-separator {
            height: 5px;
            background: #724636;
            margin: 0 15px;
        }

        /* ── Profile Dropdown Navbar ── */
        .profile-dropdown { position: relative; }
        .profile-dropdown-trigger {
            display: flex;
            align-items: center;
            cursor: pointer;
            color: #fff;
            margin-right: 15px;
            background: none;
            border: none;
            padding: 0;
        }
        .profile-dropdown-trigger i { font-size: 1.6em; }
        .profile-dropdown-trigger:hover i { color: #a3826f; }

        .profile-dropdown-menu {
            position: absolute;
            top: calc(100% + 14px);
            right: 0;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.18);
            min-width: 215px;
            padding: 8px 0;
            display: none;
            z-index: 2000;
            border: 1px solid #f0e6de;
        }
        .profile-dropdown-menu.show { display: block; }
        .profile-dropdown-menu::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 18px;
            width: 16px;
            height: 16px;
            background: #fff;
            border-left: 1px solid #f0e6de;
            border-top: 1px solid #f0e6de;
            transform: rotate(45deg);
        }
        .pdm-user {
            padding: 12px 18px 10px;
            border-bottom: 1px solid #f3e8e0;
            margin-bottom: 4px;
        }
        .pdm-user-name {
            font-size: 14px;
            font-weight: 700;
            color: #2d1a0e;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 170px;
        }
        .pdm-user-role { font-size: 11px; color: #a3826f; margin-top: 1px; }
        .pdm-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: background .15s;
        }
        .pdm-item:hover { background: #fdf5f0; color: #4a2c18; }
        .pdm-item i { width: 18px; text-align: center; color: #a3826f; font-size: 15px; }
        .pdm-item.logout {
            border-top: 1px solid #f3e8e0;
            margin-top: 4px;
            color: #c0392b;
        }
        .pdm-item.logout i { color: #c0392b; }
        .pdm-item.logout:hover { background: #fff5f5; }
        .pdm-notif-badge {
            margin-left: auto;
            background: #ef4444;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }

        /* === PENCARIAN === */
        .search-container {
            display: flex;
            justify-content: center;
            padding: 20px 0;
            margin-bottom: 20px;
            position: relative;
            z-index: 10;
        }
        .search-box {
            display: flex;
            align-items: center;
            background-color: #4A2C18;
            border-radius: 50px;
            padding: 10px 20px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 12;
            position: relative;
        }
        .search-box .fa-search {
            color: #FFFFFF;
            font-size: 1.2em;
            margin-right: 10px;
            margin-left: -5px;
        }
        .search-input {
            flex-grow: 1;
            border: none;
            background: #4A2C18;
            color: #ffff;
            font-size: 1em;
            outline: none;
            margin-left: 6px;
        }
        .search-input::placeholder { color: #d7ccc8; }
        .search-clear {
            background: none;
            border: none;
            color: #d7ccc8;
            font-size: 1em;
            cursor: pointer;
            padding: 0 0 0 8px;
            display: none;
        }
        .search-clear.visible { display: block; }
        .history-dropdown {
            position: absolute;
            top: 90%;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 500px;
            background-color: #4A2C18;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            padding-top: 20px;
            padding-bottom: 10px;
            margin-top: -30px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            z-index: 5;
            display: none;
        }
        .history-dropdown.show { display: block; }
        .separator-line {
            height: 1px;
            background-color: rgba(255, 255, 255, 0.3);
            margin: 0 20px;
            margin-bottom: 10px;
        }
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px 6px;
            color: #d7ccc8;
            font-size: 0.8em;
        }
        .history-header button {
            background: none;
            border: none;
            color: #d7ccc8;
            cursor: pointer;
            font-size: 0.85em;
            text-decoration: underline;
            padding: 0;
        }
        .history-header button:hover { color: #fff; }
        .history-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 20px;
            color: #FFFFFF;
            font-size: 1em;
            cursor: pointer;
            text-decoration: none;
        }
        .history-item:hover { background-color: rgba(255, 255, 255, 0.1); }
        .history-item .fa-history { color: #FFFFFF; margin-right: 15px; }
        .history-text { flex-grow: 1; font-weight: 500; }
        .history-item .fa-times-circle {
            margin-left: auto;
            color: #d7ccc8;
            font-size: 0.85em;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .history-item:hover .fa-times-circle { opacity: 1; }
        .no-result-search {
            text-align: center;
            padding: 40px 20px;
            color: #888;
            font-size: 1.1em;
            display: none;
        }
        .no-result-search i {
            font-size: 2.5em;
            color: #ccc;
            display: block;
            margin-bottom: 10px;
        }
        .card.hidden, .card-responsive.hidden { display: none; }

        /* BIOGRAFI (Horizontal Scroll) */
        .rekomendasi-section {
            padding: 20px 0;
            margin-top: 50px;
        }
        .rekomendasi-section h2 {
            margin-bottom: 2px;
            color: black;
            position: relative;
            left: 28px;
        }
        .cards-container {
            display: flex;
            overflow-x: scroll;
            overflow-y: hidden;
            padding-bottom: 20px;
            flex-wrap: nowrap;
            scroll-behavior: smooth;
            background-color: #724636;
            padding-left: 25px;
            padding-top: 25px;
        }
        .cards-container::-webkit-scrollbar { display: none; }
        .card {
            flex: 0 0 250px;
            margin-right: 20px;
            padding: 15px;
            background-color: #4a2c18;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card:hover { transform: translateY(-5px); }
        .card img {
            width: 100%;
            height: 345px;
            object-fit: cover;
            border-radius: 10px;
        }
        .card h3 {
            font-size: 1.1em;
            margin: 10px 0 5px;
            color: #f0f0f0;
            position: relative;
            top: -5px;
        }
        .card button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background-color: #f7f7f7;
            color: black;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            margin-top: 10px;
            width: 100%;
            font-weight: bold;
            transition: background-color 0.2s, transform 0.2s;
        }
        .card button:hover { background-color: #e0e0e0; transform: scale(1.02); }
        .card button img {
            width: 18px;
            height: 18px;
            margin-left: 0px;
            object-fit: contain;
        }

        /* SEJARAH (Responsive Grid) */
        .sejarah-section {
            padding: 20px 0;
            margin-top: 40px;
        }
        .sejarah-section h2 {
            padding-left: 28px;
            position: relative;
            top: 15px;
        }
        .cards-container-responsive {
            display: flex;
            flex-wrap: wrap;
            padding: 20px 50px;
            justify-content: flex-start;
            gap: 20px;
        }
        .card-responsive {
            display: flex;
            flex-direction: column;
            flex: 0 0 calc(25% - 15px);
            padding: 15px;
            background-color: #4a2c18;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card-spacer {
            flex: 0 0 calc(25% - 15px);
            visibility: hidden;
            height: 0;
            padding: 0;
            margin: 0;
        }
        .card-responsive img {
            width: 100%;
            height: 345px;
            object-fit: cover;
            border-radius: 10px;
        }
        .card-responsive h3 {
            font-size: 1.1em;
            margin: 10px 0 5px;
            color: #f0f0f0;
            flex-grow: 1;
        }
        .rating-stars {
            font-size: 1.5rem;
            color: #fbbf24;
            margin-bottom: 0.75rem;
            letter-spacing: 2px;
            position: relative;
            top: -10px;
        }
        .rating-stars .unfilled-star { color: rgba(255, 255, 255, 0.5); }
        .card-responsive button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background-color: #f7f7f7;
            color: black;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            margin-top: 10px;
            width: 100%;
            font-weight: bold;
            position: relative;
            top: -10px;
        }
        .card-responsive button:hover { background-color: #e0e0e0; transform: scale(1.02); }
        .card-responsive button img {
            width: 18px;
            height: 18px;
            margin-left: 0px;
            object-fit: contain;
        }

        /* FOOTER */
        .footer {
            background-color: #4a2c18;
            color: #fff;
            padding: 40px 20px;
            font-size: 14px;
        }
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.2fr 2.5fr 1fr;
            gap: 20px;
            align-items: flex-start;
        }
        .footer-kolom { padding: 0 10px; }
        .logo-info { display: flex; flex-direction: column; align-items: flex-start; }
        .logo-title { display: flex; align-items: center; margin-bottom: 10px; }
        .logo-title img { width: 40px; height: 40px; margin-right: 8px; }
        .logo-title h3 { color: white; margin: 0; font-size: 18px; }
        .logo-info p { margin: 0 0 15px 0; line-height: 1.5; }
        .sosial-media-wrapper { margin-top: 10px; }
        .sosial-media { display: flex; margin-bottom: 5px; }
        .sosial-media a { color: #fff; font-size: 24px; margin-right: 15px; text-decoration: none; }
        .sosial-media-wrapper span { font-size: 14px; color: inherit; }
        .middle-section { display: flex; flex-direction: column; }
        .navigasi-horizontal {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            color: white;
        }
        .navigasi-horizontal a {
            color: inherit;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            padding-right: 20px;
        }
        .middle-section h4 { color: white; margin-top: 0; margin-bottom: 10px; font-weight: bold; font-size: 16px; }
        .kontak .item-kontak { margin-bottom: 15px; }
        .kontak .item-kontak p { margin: 2px 0; }
        .kontak .item-kontak p:first-child { font-weight: bold; margin-bottom: 2px; color: white; display: flex; align-items: center; }
        .kontak .item-kontak i { margin-right: 8px; font-size: 18px; color: #fff; }

        @media (max-width: 1024px) {
            nav, .profile-dropdown { display: none; }
            .hamburger-menu { display: block; }
            .cards-container-responsive { padding: 20px 30px; }
            .sejarah-section h2 { padding-left: 30px; }
            .card-responsive, .card-spacer { flex: 0 0 calc(50% - 10px); }
        }
        @media (max-width: 900px) {
            .cards-container-responsive { padding: 20px; }
        }
        @media (max-width: 600px) {
            header { height: 50px; padding: 0 15px; }
            .logo-text-img { height: 55px; }
            .search-box { max-width: 90%; }
            .history-dropdown { max-width: 90%; }
            .cards-container-responsive { padding: 20px; }
            .sejarah-section h2, .rekomendasi-section h2 { padding-left: 20px; left: 0; }
            .card-responsive, .card-spacer { flex: 0 0 100%; }
            .footer-container { grid-template-columns: 1fr; gap: 30px; }
            .navigasi-horizontal { flex-direction: column; margin-bottom: 15px; }
            .navigasi-horizontal a { margin-bottom: 10px; padding-right: 0; }
        }

        a { text-decoration: none; }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <img src="logobenar.png" alt="logo PJBL" class="logo-img">
            <img src="sejput.png" alt="namaweb text" class="logo-text-img">
        </div>

        <button class="hamburger-menu" id="hamburger-btn">
            <i class="fas fa-bars"></i>
        </button>

        <nav class="desktop-nav">
            <ul>
                <li><a href="landingpagepilihanfix.php">Beranda</a></li>
                <li class="dropdown">
                    <a href="#" onclick="toggleDropdown(event)">Artikel ▾</a>
                    <div class="dropdown-menu" id="dropdown-menu">
                        <a href="daftarsearchSejarah.php" class="ddm-item">
                            <i class="fas fa-landmark"></i> Sejarah
                        </a>
                        <div class="ddm-separator"></div>
                        <a href="daftarsearchBiografi.php" class="ddm-item">
                            <i class="fas fa-user"></i> Biografi Tokoh
                        </a>
                    </div>
                </li>
                <li><a href="favorit.php">Favorit</a></li>
                <li><a href="rating.php">Ulasan</a></li>
            </ul>
        </nav>

        <!-- Profile Dropdown -->
        <div class="profile-dropdown" id="profile-dropdown">
            <button class="profile-dropdown-trigger" onclick="toggleProfileDropdown(event)"
                    title="<?= htmlspecialchars($_SESSION['nama_lengkap']) ?>">
                <i class="fas fa-user-circle"></i>
            </button>
            <div class="profile-dropdown-menu" id="profile-dropdown-menu">
                <div class="pdm-user">
                    <div class="pdm-user-name"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></div>
                    <div class="pdm-user-role">Member</div>
                </div>
                <a href="history.php" class="pdm-item">
                    <i class="fas fa-user"></i> Profil &amp; Riwayat
                </a>
                <a href="kelola_artikel.php" class="pdm-item">
                    <i class="fas fa-pen-to-square"></i> Kelola Artikel
                </a>
                <a href="notifikasi.php" class="pdm-item">
                    <i class="fas fa-bell"></i> Notifikasi
                    <?php if ($_nb_count > 0): ?>
                        <span class="pdm-notif-badge"><?= $_nb_count > 99 ? '99+' : $_nb_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="logout.php" class="pdm-item logout">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>
            </div>
        </div>
    </header>

    <!-- Sidebar Mobile -->
    <div class="sidebar" id="sidebar">
        <button class="close-btn" id="close-sidebar-btn">&times;</button>
        <div class="sidebar-content">
            <div class="sidebar-user-icon">
                <a href="history.php" onclick="closeSidebar()">
                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
                </a>
            </div>
            <a href="landingpagepilihanfix.php" onclick="closeSidebar()">Beranda</a>
            <div class="dropdown-sidebar">
                <a href="#" onclick="toggleDropdownSidebar(event)">Artikel ▾</a>
                <div class="dropdown-menu-sidebar" id="dropdown-menu-sidebar">
                    <div class="dropdown-inner"><a href="daftarsearchSejarah.php" onclick="closeSidebar()">Sejarah</a></div>
                    <div class="dropdown-separator"></div>
                    <div class="dropdown-inner"><a href="daftarsearchBiografi.php" onclick="closeSidebar()">Biografi Tokoh</a></div>
                </div>
            </div>
            <a href="favorit.php" onclick="closeSidebar()">Favorit</a>
            <a href="rating.php" onclick="closeSidebar()">Ulasan</a>
            <div style="border-top:1px solid rgba(255,255,255,0.15);margin:8px 0;"></div>
            <a href="history.php" onclick="closeSidebar()">
                <i class="fas fa-user" style="width:18px;margin-right:10px"></i> Profil &amp; Riwayat
            </a>
            <a href="kelola_artikel.php" onclick="closeSidebar()">
                <i class="fas fa-pen-to-square" style="width:18px;margin-right:10px"></i> Kelola Artikel
            </a>
            <a href="notifikasi.php" onclick="closeSidebar()" style="display:flex;align-items:center">
                <i class="fas fa-bell" style="width:18px;margin-right:10px"></i> Notifikasi
                <?php if ($_nb_count > 0): ?>
                    <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:11px;
                        font-weight:700;min-width:18px;height:18px;border-radius:9px;
                        display:inline-flex;align-items:center;justify-content:center;padding:0 4px">
                        <?= $_nb_count > 99 ? '99+' : $_nb_count ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="logout.php" onclick="closeSidebar()" style="color:#ffccaa;">
                <i class="fas fa-sign-out-alt" style="width:18px;margin-right:10px"></i> Keluar
            </a>
        </div>
    </div>

    <!-- SEARCH BAR -->
    <div class="search-container">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Pencarian..." class="search-input" id="search-input" autocomplete="off">
            <button class="search-clear" id="search-clear" title="Hapus pencarian">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="history-dropdown" id="history-dropdown">
            <div class="separator-line"></div>
            <div class="history-header">
                <span>Riwayat Pencarian</span>
                <button onclick="clearAllHistory()">Hapus Semua</button>
            </div>
            <div id="history-list"></div>
        </div>
    </div>

    <div class="no-result-search" id="no-result-search">
        <i class="fas fa-search"></i>
        Tidak ada artikel yang cocok dengan "<span id="no-result-keyword"></span>"
    </div>

    <!-- BIOGRAFI TOKOH -->
    <section class="rekomendasi-section">
        <h2>Biografi Tokoh</h2>
        <div class="cards-container">
            <?php if (empty($list_biografi)): ?>
                <p style="color:#f0f0f0;padding:20px;">Belum ada artikel biografi yang dipublikasikan.</p>
            <?php else: ?>
                <?php foreach ($list_biografi as $art): ?>
                <div class="card" data-title="<?= htmlspecialchars($art['judul']) ?>">
                    <img src="<?= htmlspecialchars(thumbSrc($art['thumbnail'])) ?>"
                         alt="<?= htmlspecialchars($art['judul']) ?>"
                         onerror="this.src='logobenar.png'">
                    <h3><?= htmlspecialchars($art['judul']) ?></h3>
                    <a href="detail_artikel.php?slug=<?= urlencode($art['slug']) ?>">
                        <button>Baca Sekarang <img src="majesticons_arrow-right.png" alt=""></button>
                    </a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- ARTIKEL SEJARAH -->
    <section class="rekomendasi-section sejarah-section">
        <h2>Artikel Sejarah</h2>
        <div class="cards-container-responsive">
            <?php if (empty($list_sejarah)): ?>
                <p style="color:#333;">Belum ada artikel sejarah yang dipublikasikan.</p>
            <?php else: ?>
                <?php foreach ($list_sejarah as $art): ?>
                <div class="card-responsive" data-title="<?= htmlspecialchars($art['judul']) ?>">
                    <img src="<?= htmlspecialchars(thumbSrc($art['thumbnail'])) ?>"
                         alt="<?= htmlspecialchars($art['judul']) ?>"
                         onerror="this.src='logobenar.png'">
                    <h3><?= htmlspecialchars($art['judul']) ?></h3>
                    <div class="rating-stars">
                        <?= renderBintang(round($art['rata_rating'])) ?>
                    </div>
                    <a href="detail_artikel.php?slug=<?= urlencode($art['slug']) ?>">
                        <button>Baca Sekarang <img src="majesticons_arrow-right.png" alt=""></button>
                    </a>
                </div>
                <?php endforeach; ?>
                <div class="card-spacer"></div>
                <div class="card-spacer"></div>
            <?php endif; ?>
        </div>
    </section>

    <!-- FOOTER -->
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
                    <a href="viewmore.php">Artikel</a>
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
        // ── Artikel Dropdown ──
        function toggleDropdown(event) {
            event.preventDefault();
            const menu = document.getElementById('dropdown-menu');
            const isOpen = menu.style.display === 'block';
            menu.style.display = isOpen ? 'none' : 'block';
        }
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('dropdown-menu');
            const dropdown = document.querySelector('.dropdown');
            if (dropdown && !dropdown.contains(e.target)) {
                menu.style.display = 'none';
            }
        });

        // ── Sidebar Mobile ──
        const sidebar = document.getElementById('sidebar');
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const closeSidebarBtn = document.getElementById('close-sidebar-btn');

        function openSidebar() {
            const screenWidth = window.innerWidth;
            let sidebarWidth = '250px';
            if (screenWidth < 350) sidebarWidth = '90%';
            else if (screenWidth < 450) sidebarWidth = '300px';
            sidebar.style.width = sidebarWidth;
            document.body.style.overflow = 'hidden';
        }
        hamburgerBtn.addEventListener('click', openSidebar);
        closeSidebarBtn.addEventListener('click', closeSidebar);

        function closeSidebar() {
            sidebar.style.width = '0';
            document.body.style.overflow = '';
            const activeDropdown = document.querySelector('.dropdown-menu-sidebar[style*="display: block"]');
            if (activeDropdown) activeDropdown.style.display = 'none';
        }

        function toggleDropdownSidebar(event) {
            event.preventDefault();
            const dropdown = event.target.nextElementSibling;
            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            } else {
                document.querySelectorAll('.dropdown-menu-sidebar').forEach(d => {
                    if (d !== dropdown) d.style.display = 'none';
                });
                dropdown.style.display = 'block';
            }
        }

        // ── Profile Dropdown ──
        function toggleProfileDropdown(event) {
            event.stopPropagation();
            document.getElementById('profile-dropdown-menu').classList.toggle('show');
        }
        document.addEventListener('click', function(e) {
            var pd   = document.getElementById('profile-dropdown');
            var menu = document.getElementById('profile-dropdown-menu');
            if (menu && pd && !pd.contains(e.target)) {
                menu.classList.remove('show');
            }
        });

        // ── Pencarian & Riwayat ──
        const HISTORY_KEY = 'sejiwa_search_history';
        const MAX_HISTORY = 8;

        const searchInput     = document.getElementById('search-input');
        const searchClear     = document.getElementById('search-clear');
        const historyDropdown = document.getElementById('history-dropdown');
        const historyList     = document.getElementById('history-list');
        const noResult        = document.getElementById('no-result-search');
        const noResultKw      = document.getElementById('no-result-keyword');
        const cards           = document.querySelectorAll('.card[data-title], .card-responsive[data-title]');

        function getHistory() {
            try { return JSON.parse(localStorage.getItem(HISTORY_KEY)) || []; }
            catch { return []; }
        }
        function saveHistory(list) { localStorage.setItem(HISTORY_KEY, JSON.stringify(list)); }
        function addToHistory(keyword) {
            if (!keyword.trim()) return;
            let h = getHistory().filter(x => x.toLowerCase() !== keyword.toLowerCase());
            h.unshift(keyword);
            if (h.length > MAX_HISTORY) h = h.slice(0, MAX_HISTORY);
            saveHistory(h);
        }
        function removeHistoryItem(keyword) {
            saveHistory(getHistory().filter(h => h !== keyword));
            renderHistory();
        }
        function clearAllHistory() { saveHistory([]); renderHistory(); }

        function renderHistory() {
            const history = getHistory();
            historyList.innerHTML = '';
            if (history.length === 0) {
                historyList.innerHTML = '<p style="color:#d7ccc8;font-size:0.85em;padding:6px 20px 10px;">Belum ada riwayat pencarian.</p>';
                return;
            }
            history.forEach(keyword => {
                const item = document.createElement('div');
                item.className = 'history-item';
                item.innerHTML = `
                    <i class="fas fa-history"></i>
                    <span class="history-text">${keyword}</span>
                    <i class="fas fa-times-circle" title="Hapus"></i>
                `;
                item.querySelector('.history-text').addEventListener('click', function() {
                    searchInput.value = keyword;
                    filterCards(keyword);
                    toggleClearBtn();
                    historyDropdown.classList.remove('show');
                });
                item.querySelector('.fa-times-circle').addEventListener('click', function(e) {
                    e.stopPropagation();
                    removeHistoryItem(keyword);
                });
                historyList.appendChild(item);
            });
        }

        function filterCards(keyword) {
            const q = keyword.trim().toLowerCase();
            let visible = 0;
            cards.forEach(card => {
                const title = card.getAttribute('data-title').toLowerCase();
                if (q === '' || title.includes(q)) {
                    card.classList.remove('hidden');
                    visible++;
                } else {
                    card.classList.add('hidden');
                }
            });
            if (q !== '' && visible === 0) {
                noResult.style.display = 'block';
                noResultKw.textContent = keyword;
            } else {
                noResult.style.display = 'none';
            }
        }

        function toggleClearBtn() {
            searchClear.classList.toggle('visible', searchInput.value.length > 0);
        }

        searchInput.addEventListener('input', function() {
            filterCards(this.value);
            toggleClearBtn();
            if (this.value.trim() !== '') {
                historyDropdown.classList.remove('show');
            } else {
                renderHistory();
                historyDropdown.classList.add('show');
            }
        });
        searchInput.addEventListener('focus', function() {
            if (this.value.trim() === '') {
                renderHistory();
                historyDropdown.classList.add('show');
            }
        });
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const kw = this.value.trim();
                if (kw) { addToHistory(kw); filterCards(kw); }
                historyDropdown.classList.remove('show');
                this.blur();
            }
        });
        searchClear.addEventListener('click', function() {
            searchInput.value = '';
            filterCards('');
            toggleClearBtn();
            renderHistory();
            historyDropdown.classList.add('show');
            searchInput.focus();
        });
        document.body.addEventListener('click', function(e) {
            const sc = document.querySelector('.search-container');
            if (sc && !sc.contains(e.target)) {
                const kw = searchInput.value.trim();
                if (kw) addToHistory(kw);
                setTimeout(() => historyDropdown.classList.remove('show'), 100);
            }
        });
    </script>
</body>
</html>