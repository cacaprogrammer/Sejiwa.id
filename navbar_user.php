<?php
/**
 * navbar_user.php — Navbar dinamis: guest & user login
 * Dropdown profile: hanya Profil dan Keluar
 * Dropdown Artikel: dinamis dari tb_kategori
 */

$_nav_is_logged_in = isset($_SESSION['username']);

// Hitung notif belum dibaca
$_nb_count = 0;
if ($_nav_is_logged_in && isset($conn)) {
    $_nb_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tb_notifikasi WHERE user_id = ? AND dibaca = 0");
    $_nb_stmt->bind_param("i", $_SESSION['id']);
    $_nb_stmt->execute();
    $_nb_count = $_nb_stmt->get_result()->fetch_assoc()['total'];
}

// Ambil kategori dinamis dari DB
$_nav_list_kat = [];
if (isset($conn)) {
    $_nav_kat = $conn->query("SELECT nama_kategori, slug_kategori FROM tb_kategori ORDER BY created_at ASC");
    $_nav_list_kat = $_nav_kat ? $_nav_kat->fetch_all(MYSQLI_ASSOC) : [];
}

function navKatIcon($slug) {
    $map = [
        'sejarah'  => 'fa-landmark',
        'biografi' => 'fa-user',
        'budaya'   => 'fa-masks-theater',
        'pahlawan' => 'fa-shield-halved',
        'politik'  => 'fa-scale-balanced',
        'ekonomi'  => 'fa-chart-line',
        'agama'    => 'fa-mosque',
        'seni'     => 'fa-palette',
        'sastra'   => 'fa-book-open',
        'militer'  => 'fa-gun',
        'tokoh'    => 'fa-person',
        'nasional' => 'fa-flag',
    ];
    foreach ($map as $key => $icon) {
        if (strpos(strtolower($slug), $key) !== false) return $icon;
    }
    return 'fa-folder';
}

function navKatLink($slug) {
    return "daftarkategori.php?kategori=" . urlencode($slug);
}
?>
<style>
    /* ── NAVBAR USER ── */
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
        min-width: 200px; max-width: 260px;
        padding: 8px 0; display: none;
        border: 1px solid #f0e6de; z-index: 2000;
        max-height: 400px; overflow-y: auto;
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
    .dropdown-menu .ddm-item i { width: 18px; text-align: center; color: #a3826f; font-size: 15px; flex-shrink: 0; }
    .dropdown-menu .ddm-separator { height: 0.5px; background: #f3e8e0; margin: 4px 8px; }
    .dropdown-menu .ddm-empty { padding: 14px 16px; color: #aaa; font-size: 13px; text-align: center; font-style: italic; cursor: default; }

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
    .dropdown-menu-sidebar { display: none; background-color: #724636; padding: 5px 0; }
    .dropdown-menu-sidebar .dropdown-inner { background: #fff; border-radius: 8px; border: 2px solid #724636; padding: 2px 0; margin: 5px 15px; }
    .dropdown-menu-sidebar .dropdown-inner a { color: #000; padding: 5px 10px; font-size: 14px; text-align: center; display: block; text-decoration: none; }
    .dropdown-menu-sidebar .dropdown-separator { height: 5px; background: #724636; margin: 0 15px; }
    .dropdown-menu-sidebar .ddm-sidebar-empty { color: #ccc; font-size: 13px; font-style: italic; text-align: center; padding: 10px 0; display: block; }

    /* ── Profile Dropdown — hanya 2 item ── */
    .profile-dropdown { position: relative; }
    .profile-dropdown-trigger {
        display: flex; align-items: center; gap: 8px; cursor: pointer;
        color: #fff; margin-right: 15px; background: none; border: none; padding: 4px 6px;
        border-radius: 8px; transition: background .2s;
    }
    .profile-dropdown-trigger:hover { background: rgba(255,255,255,.12); }
    .profile-dropdown-trigger i.fa-user-circle { font-size: 1.6em; }
    .profile-trigger-name {
        font-size: 13px; font-weight: 600; max-width: 110px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .profile-notif-dot {
        width: 8px; height: 8px; background: #ef4444; border-radius: 50%;
        position: absolute; top: 4px; right: 4px;
    }

    .profile-dropdown-menu {
        position: absolute; top: calc(100% + 10px); right: 0;
        background: #fff; border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,.18);
        min-width: 200px; padding: 8px 0;
        display: none; z-index: 2000; border: 1px solid #f0e6de;
    }
    .profile-dropdown-menu.show { display: block; }
    .profile-dropdown-menu::before {
        content: ''; position: absolute; top: -8px; right: 18px;
        width: 16px; height: 16px; background: #fff;
        border-left: 1px solid #f0e6de; border-top: 1px solid #f0e6de;
        transform: rotate(45deg);
    }

    /* Info user di atas dropdown */
    .pdm-user { padding: 12px 18px 10px; border-bottom: 1px solid #f3e8e0; margin-bottom: 4px; }
    .pdm-user-name { font-size: 14px; font-weight: 700; color: #2d1a0e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
    .pdm-user-role { font-size: 11px; color: #a3826f; margin-top: 2px; }

    /* Item menu */
    .pdm-item {
        display: flex; align-items: center; gap: 12px;
        padding: 11px 18px; color: #333; font-size: 14px;
        font-weight: 500; text-decoration: none; transition: background .15s;
    }
    .pdm-item:hover { background: #fdf5f0; color: #4a2c18; }
    .pdm-item i { width: 18px; text-align: center; color: #a3826f; font-size: 15px; }
    .pdm-item.logout { border-top: 1px solid #f3e8e0; margin-top: 4px; color: #c0392b; }
    .pdm-item.logout i { color: #c0392b; }
    .pdm-item.logout:hover { background: #fff5f5; }

    /* Badge notif pada trigger */
    .pdm-notif-badge {
        margin-left: auto; background: #ef4444; color: #fff;
        font-size: 11px; font-weight: 700; min-width: 18px; height: 18px;
        border-radius: 9px; display: flex; align-items: center;
        justify-content: center; padding: 0 4px;
    }

    /* Tombol Login (guest) */
    .nav-login-btn {
        display: flex; align-items: center; gap: 7px;
        background: rgba(255,255,255,0.15); color: #fff;
        border: 1.5px solid rgba(255,255,255,0.4);
        border-radius: 20px; padding: 6px 16px;
        font-size: 0.88em; font-weight: bold;
        text-decoration: none; margin-right: 10px;
        transition: background 0.2s, border-color 0.2s;
    }
    .nav-login-btn:hover { background: rgba(255,255,255,0.28); border-color: rgba(255,255,255,0.7); }

    @media (max-width: 1024px) {
        nav, .profile-dropdown, .nav-login-btn { display: none; }
        .hamburger-menu { display: block; }
    }
</style>

<header>
    <div class="logo">
        <img src="logobenar.png" alt="logo" class="logo-img">
        <img src="sejput.png" alt="Sejiwa.id" class="logo-text-img">
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
                    <?php if (!empty($_nav_list_kat)): ?>
                        <?php foreach ($_nav_list_kat as $idx => $kat): ?>
                            <?php if ($idx > 0): ?><div class="ddm-separator"></div><?php endif; ?>
                            <a href="<?= navKatLink($kat['slug_kategori']) ?>" class="ddm-item">
                                <i class="fas <?= navKatIcon($kat['slug_kategori']) ?>"></i>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="ddm-empty">Belum ada kategori</div>
                    <?php endif; ?>
                </div>
            </li>
            <li><a href="favorit.php">Favorit</a></li>
            <li><a href="rating.php">Ulasan</a></li>
        </ul>
    </nav>

    <?php if ($_nav_is_logged_in): ?>
    <!-- ── Profile Dropdown: hanya Profil & Keluar ── -->
    <div class="profile-dropdown" id="profile-dropdown">
        <button class="profile-dropdown-trigger" onclick="toggleProfileDropdown(event)"
                title="<?= htmlspecialchars($_SESSION['nama_lengkap']) ?>">
            <i class="fas fa-user-circle"></i>
            <span class="profile-trigger-name"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
            <?php if ($_nb_count > 0): ?>
                <span class="profile-notif-dot"></span>
            <?php endif; ?>
        </button>
        <div class="profile-dropdown-menu" id="profile-dropdown-menu">
            <div class="pdm-user">
                <div class="pdm-user-name"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></div>
                <div class="pdm-user-role">Member</div>
            </div>
            <!-- Hanya 2 item: Profil dan Keluar -->
            <a href="history.php" class="pdm-item">
                <i class="fas fa-user"></i> Profil
                <?php if ($_nb_count > 0): ?>
                    <span class="pdm-notif-badge" title="<?= $_nb_count ?> notifikasi belum dibaca">
                        <?= $_nb_count > 99 ? '99+' : $_nb_count ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="logout.php" class="pdm-item logout">
                <i class="fas fa-sign-out-alt"></i> Keluar
            </a>
        </div>
    </div>
    <?php else: ?>
    <a href="loginpage.php" class="nav-login-btn">
        <i class="fas fa-sign-in-alt"></i> Masuk
    </a>
    <?php endif; ?>
</header>

<!-- Sidebar Mobile -->
<div class="sidebar" id="sidebar">
    <button class="close-btn" id="close-sidebar-btn">&times;</button>
    <div class="sidebar-content">

        <?php if ($_nav_is_logged_in): ?>
        <div class="sidebar-user-icon">
            <a href="history.php" onclick="closeSidebar()">
                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
            </a>
        </div>
        <?php else: ?>
        <div class="sidebar-user-icon">
            <a href="loginpage.php" onclick="closeSidebar()">
                <i class="fas fa-sign-in-alt"></i> Masuk / Daftar
            </a>
        </div>
        <?php endif; ?>

        <a href="landingpagepilihanfix.php" onclick="closeSidebar()">Beranda</a>

        <div class="dropdown-sidebar">
            <a href="#" onclick="toggleDropdownSidebar(event)">Artikel ▾</a>
            <div class="dropdown-menu-sidebar" id="dropdown-menu-sidebar">
                <?php if (!empty($_nav_list_kat)): ?>
                    <?php foreach ($_nav_list_kat as $idx => $kat): ?>
                        <?php if ($idx > 0): ?><div class="dropdown-separator"></div><?php endif; ?>
                        <div class="dropdown-inner">
                            <a href="<?= navKatLink($kat['slug_kategori']) ?>" onclick="closeSidebar()">
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="ddm-sidebar-empty">Belum ada kategori</span>
                <?php endif; ?>
            </div>
        </div>

        <a href="favorit.php" onclick="closeSidebar()">Favorit</a>
        <a href="rating.php" onclick="closeSidebar()">Ulasan</a>

        <?php if ($_nav_is_logged_in): ?>
        <div style="border-top:1px solid rgba(255,255,255,0.15);margin:8px 0;"></div>
        <a href="history.php" onclick="closeSidebar()" style="display:flex;align-items:center">
            <i class="fas fa-user" style="width:18px;margin-right:12px"></i> Profil
            <?php if ($_nb_count > 0): ?>
                <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:11px;
                    font-weight:700;min-width:18px;height:18px;border-radius:9px;
                    display:inline-flex;align-items:center;justify-content:center;padding:0 4px">
                    <?= $_nb_count > 99 ? '99+' : $_nb_count ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="logout.php" onclick="closeSidebar()" style="color:#ffccaa;">
            <i class="fas fa-sign-out-alt" style="width:18px;margin-right:12px"></i> Keluar
        </a>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleDropdown(event) {
        event.preventDefault();
        const menu = document.getElementById('dropdown-menu');
        if (menu) menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }

    function toggleProfileDropdown(event) {
        event.stopPropagation();
        const menu = document.getElementById('profile-dropdown-menu');
        if (menu) menu.classList.toggle('show');
    }

    function closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) sidebar.style.width = '0';
        document.body.style.overflow = '';
    }

    function toggleDropdownSidebar(event) {
        event.preventDefault();
        const dd = event.target.nextElementSibling;
        if (dd && dd.classList.contains('dropdown-menu-sidebar')) {
            document.querySelectorAll('.dropdown-menu-sidebar').forEach(d => { if (d !== dd) d.style.display = 'none'; });
            dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
        }
    }

    document.addEventListener('click', function(e) {
        const menu = document.getElementById('dropdown-menu');
        const dd   = document.querySelector('.dropdown');
        if (menu && dd && !dd.contains(e.target)) menu.style.display = 'none';

        const pd  = document.getElementById('profile-dropdown');
        const pdm = document.getElementById('profile-dropdown-menu');
        if (pdm && pd && !pd.contains(e.target)) pdm.classList.remove('show');
    });

    const hamburgerBtn = document.getElementById('hamburger-btn');
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const w = window.innerWidth;
            if (sidebar) sidebar.style.width = w < 350 ? '90%' : (w < 450 ? '300px' : '250px');
            document.body.style.overflow = 'hidden';
        });
    }

    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
</script>