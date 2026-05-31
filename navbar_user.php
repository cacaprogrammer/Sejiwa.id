<?php
/**
 * navbar_user.php — Navbar dinamis untuk halaman user
 * Include file ini di setiap halaman user setelah koneksi DB tersedia.
 */

$_nav_kat = $conn->query("SELECT nama_kategori, slug_kategori FROM tb_kategori ORDER BY created_at ASC");
$_nav_list_kat = $_nav_kat ? $_nav_kat->fetch_all(MYSQLI_ASSOC) : [];

function navKatIcon($slug) {
    $map = [
        'sejarah'  => 'fa-landmark',
        'biografi' => 'fa-user',
        'budaya'   => 'fa-masks-theater',
        'pahlawan' => 'fa-shield-halved',
        'politik'  => 'fa-scale-balanced',
        'ekonomi'  => 'fa-chart-line',
    ];
    foreach ($map as $key => $icon) {
        if (str_contains(strtolower($slug), $key)) return $icon;
    }
    return 'fa-folder';
}

function navKatLink($slug) {
    $camel = ucfirst(strtolower(str_replace('-', '', $slug)));
    $file  = "daftarsearch{$camel}.php";
    if (file_exists(__DIR__ . '/' . $file)) return $file;
    return "daftarkategori.php?kategori=" . urlencode($slug);
}
?>
<style>
    /* ── NAVBAR USER (navbar_user.php) ── */
    header {
        height: 50px; background-color: #4a2c18; color: #fff;
        padding: 0 15px; display: flex; justify-content: space-between;
        align-items: center; position: sticky; top: 0; left: 0; width: 100%;
        box-shadow: 0 2px 4px rgba(0,0,0,.1); z-index: 1000;
    }
    .logo { display: flex; align-items: center; }
    .logo-img { height: 40px; width: auto; }
    .logo-text-img { height: 70px; width: auto; margin-left: -12px; position: relative; top: -2px; }

    /* Dropdown Artikel Desktop */
    .dropdown { position: relative; }
    .dropdown-menu {
        position: absolute; top: calc(100% + 14px); left: -10px;
        background: #fff; border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,.18);
        min-width: 190px; padding: 8px 0;
        display: none; border: 1px solid #f0e6de; z-index: 2000;
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

    nav ul { display: flex; list-style: none; padding: 0; margin: 0; }
    nav ul li { margin-left: 20px; }
    nav ul li a { text-decoration: none; color: inherit; font-size: .95em; font-weight: bold; transition: .3s; }
    nav ul li a:hover { color: #a3826f; }

    /* Hamburger */
    .hamburger-menu { display: none; background: none; border: none; color: #f7f7f7; font-size: 1.5em; cursor: pointer; padding: 5px; margin-right: 10px; }

    /* Sidebar Mobile */
    .sidebar {
        height: 100%; width: 0; position: fixed; z-index: 1001; top: 0; right: 0;
        background-color: #4a2c18; overflow-x: hidden; transition: .3s;
        padding-top: 60px; box-shadow: -5px 0 15px rgba(0,0,0,.4);
    }
    .sidebar a { padding: 15px 25px; text-decoration: none; font-size: 18px; color: #f7f7f7; display: block; transition: .3s; }
    .sidebar a:hover { background-color: #6d4c41; }
    .sidebar .close-btn { position: absolute; top: 0; right: 15px; font-size: 36px; color: #f7f7f7; border: none; background: none; cursor: pointer; }
    .sidebar-user-icon { padding: 20px 25px; border-bottom: 1px solid #6d4c41; margin-bottom: 10px; }
    .sidebar-user-icon a { font-size: 20px; font-weight: bold; }
    .sidebar-user-icon i { margin-right: 10px; }
    .dropdown-menu-sidebar { display: none; background-color: #724636; padding: 5px 0; }
    .dropdown-menu-sidebar .dropdown-inner { background: #fff; border-radius: 8px; border: 2px solid #724636; padding: 2px 0; margin: 5px 15px; }
    .dropdown-menu-sidebar .dropdown-inner a { color: #000; padding: 5px 10px; font-size: 14px; text-align: center; }
    .dropdown-menu-sidebar .dropdown-separator { height: 5px; background: #724636; margin: 0 15px; }

    /* Profile Dropdown */
    .profile-dropdown { position: relative; }
    .profile-dropdown-trigger {
        display: flex; align-items: center; cursor: pointer;
        color: #fff; margin-right: 15px; background: none; border: none; padding: 0;
    }
    .profile-dropdown-trigger i { font-size: 1.6em; }
    .profile-dropdown-trigger:hover i { color: #c9a68a; }
    .profile-dropdown-menu {
        position: absolute; top: calc(100% + 14px); right: 0;
        background: #fff; border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,.18);
        min-width: 215px; padding: 8px 0;
        display: none; z-index: 2000; border: 1px solid #f0e6de;
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
    .pdm-notif-badge {
        margin-left: auto; background: #ef4444; color: #fff;
        font-size: 11px; font-weight: 700; min-width: 18px; height: 18px;
        border-radius: 9px; display: flex; align-items: center;
        justify-content: center; padding: 0 4px;
    }

    @media (max-width: 1024px) {
        nav, .profile-dropdown { display: none; }
        .hamburger-menu { display: block; }
    }
</style>

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
                    <?php if (empty($_nav_list_kat)): ?>
                        <a href="viewmore.php" class="ddm-item">
                            <i class="fas fa-book"></i> Semua Artikel
                        </a>
                    <?php else: ?>
                        <?php foreach ($_nav_list_kat as $idx => $kat): ?>
                            <?php if ($idx > 0): ?>
                                <div class="ddm-separator"></div>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars(navKatLink($kat['slug_kategori'])) ?>" class="ddm-item">
                                <i class="fas <?= navKatIcon($kat['slug_kategori']) ?>"></i>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                <?php if (empty($_nav_list_kat)): ?>
                    <div class="dropdown-inner">
                        <a href="viewmore.php" onclick="closeSidebar()">Semua Artikel</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($_nav_list_kat as $idx => $kat): ?>
                        <?php if ($idx > 0): ?>
                            <div class="dropdown-separator"></div>
                        <?php endif; ?>
                        <div class="dropdown-inner">
                            <a href="<?= htmlspecialchars(navKatLink($kat['slug_kategori'])) ?>" onclick="closeSidebar()">
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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