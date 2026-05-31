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

// Ambil slug kategori dari URL
$slug_kat = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
if (empty($slug_kat)) {
    header("Location: landingpagepilihanfix.php");
    exit();
}

// Ambil data kategori
$stmt_kat = $conn->prepare("SELECT * FROM tb_kategori WHERE slug_kategori = ?");
$stmt_kat->bind_param("s", $slug_kat);
$stmt_kat->execute();
$data_kat = $stmt_kat->get_result()->fetch_assoc();

if (!$data_kat) {
    header("Location: landingpagepilihanfix.php");
    exit();
}

// Ambil semua artikel kategori ini
$stmt_art = $conn->prepare("
    SELECT a.id, a.judul, a.slug, a.thumbnail, a.created_at,
           YEAR(a.created_at) AS tahun
    FROM tb_artikel a
    INNER JOIN tb_kategori k ON k.id_kategori = a.id_kategori
    WHERE a.status = 'published'
      AND k.slug_kategori = ?
    ORDER BY a.created_at DESC
");
$stmt_art->bind_param("s", $slug_kat);
$stmt_art->execute();
$list_artikel = $stmt_art->get_result()->fetch_all(MYSQLI_ASSOC);

$nama_kat = htmlspecialchars($data_kat['nama_kategori']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $nama_kat ?> — Sejiwa.id</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:Arial,sans-serif; line-height:1.6; background-color:#f7f7f7; }

        /* NAVBAR */
        header {
            height:50px; background-color:#4a2c18; color:#fff;
            padding:0 15px; display:flex; justify-content:space-between;
            align-items:center; position:sticky; top:0; left:0; width:100%;
            box-shadow:0 2px 4px rgba(0,0,0,.1); z-index:1000;
        }
        .logo { display:flex; align-items:center; }
        .logo-img { height:40px; width:auto; }
        .logo-text-img { height:70px; width:auto; margin-left:-12px; position:relative; top:-2px; }
        .dropdown { position:relative; }
        .dropdown-menu {
            position:absolute; top:calc(100% + 14px); left:-10px;
            background:#fff; border-radius:12px;
            box-shadow:0 8px 30px rgba(0,0,0,.18);
            min-width:190px; padding:8px 0;
            display:none; border:1px solid #f0e6de; z-index:2000;
        }
        .dropdown-menu::before {
            content:''; position:absolute; top:-8px; left:24px;
            width:16px; height:16px; background:#fff;
            border-left:1px solid #f0e6de; border-top:1px solid #f0e6de;
            transform:rotate(45deg);
        }
        .dropdown-menu .ddm-item { display:flex; align-items:center; gap:12px; padding:10px 16px; color:#333; font-size:14px; font-weight:500; text-decoration:none; transition:background .15s; }
        .dropdown-menu .ddm-item:hover { background:#fdf5f0; color:#4a2c18; }
        .dropdown-menu .ddm-item i { width:18px; text-align:center; color:#a3826f; font-size:15px; }
        .dropdown-menu .ddm-separator { height:0.5px; background:#f3e8e0; margin:4px 8px; }
        nav ul { display:flex; list-style:none; padding:0; }
        nav ul li { margin-left:20px; }
        nav ul li a { text-decoration:none; color:inherit; font-size:.95em; font-weight:bold; transition:.3s; }
        nav ul li a:hover { color:#a3826f; }
        .hamburger-menu { display:none; background:none; border:none; color:#f7f7f7; font-size:1.5em; cursor:pointer; padding:5px; margin-right:10px; }
        .sidebar { height:100%; width:0; position:fixed; z-index:1001; top:0; right:0; background-color:#4a2c18; overflow-x:hidden; transition:.3s; padding-top:60px; box-shadow:-5px 0 15px rgba(0,0,0,.4); }
        .sidebar a { padding:15px 25px; text-decoration:none; font-size:18px; color:#f7f7f7; display:block; transition:.3s; }
        .sidebar a:hover { background-color:#6d4c41; }
        .sidebar .close-btn { position:absolute; top:0; right:15px; font-size:36px; color:#f7f7f7; border:none; background:none; cursor:pointer; }
        .sidebar-user-icon { padding:20px 25px; border-bottom:1px solid #6d4c41; margin-bottom:10px; }
        .sidebar-user-icon a { font-size:20px; font-weight:bold; }
        .sidebar-user-icon i { margin-right:10px; }
        .dropdown-menu-sidebar { display:none; background-color:#724636; padding:5px 0; }
        .dropdown-menu-sidebar .dropdown-inner { background:#fff; border-radius:8px; border:2px solid #724636; padding:2px 0; margin:5px 15px; }
        .dropdown-menu-sidebar .dropdown-inner a { color:#000; padding:5px 10px; font-size:14px; text-align:center; }
        .dropdown-menu-sidebar .dropdown-separator { height:5px; background:#724636; margin:0 15px; }
        .profile-dropdown { position:relative; }
        .profile-dropdown-trigger { display:flex; align-items:center; cursor:pointer; color:#fff; margin-right:15px; background:none; border:none; padding:0; }
        .profile-dropdown-trigger i { font-size:1.6em; }
        .profile-dropdown-trigger:hover i { color:#c9a68a; }
        .profile-dropdown-menu { position:absolute; top:calc(100% + 14px); right:0; background:#fff; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,.18); min-width:215px; padding:8px 0; display:none; z-index:2000; border:1px solid #f0e6de; }
        .profile-dropdown-menu.show { display:block; }
        .profile-dropdown-menu::before { content:''; position:absolute; top:-8px; right:18px; width:16px; height:16px; background:#fff; border-left:1px solid #f0e6de; border-top:1px solid #f0e6de; transform:rotate(45deg); }
        .pdm-user { padding:12px 18px 10px; border-bottom:1px solid #f3e8e0; margin-bottom:4px; }
        .pdm-user-name { font-size:14px; font-weight:700; color:#2d1a0e; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:170px; }
        .pdm-user-role { font-size:11px; color:#a3826f; margin-top:1px; }
        .pdm-item { display:flex; align-items:center; gap:12px; padding:10px 18px; color:#333; font-size:14px; font-weight:500; text-decoration:none; transition:background .15s; }
        .pdm-item:hover { background:#fdf5f0; color:#4a2c18; }
        .pdm-item i { width:18px; text-align:center; color:#a3826f; font-size:15px; }
        .pdm-item.logout { border-top:1px solid #f3e8e0; margin-top:4px; color:#c0392b; }
        .pdm-item.logout i { color:#c0392b; }
        .pdm-item.logout:hover { background:#fff5f5; }
        .pdm-notif-badge { margin-left:auto; background:#ef4444; color:#fff; font-size:11px; font-weight:700; min-width:18px; height:18px; border-radius:9px; display:flex; align-items:center; justify-content:center; padding:0 4px; }

        @media (max-width:1024px) { nav, .profile-dropdown { display:none; } .hamburger-menu { display:block; } }

        /* SEARCH */
        .search-container { display:flex; justify-content:center; padding:20px 0 20px; position:relative; z-index:10; }
        .search-box { display:flex; align-items:center; background-color:#4A2C18; border-radius:50px; padding:10px 20px; width:100%; max-width:500px; box-shadow:0 4px 6px rgba(0,0,0,.1); z-index:12; position:relative; }
        .search-box .fa-search { color:#fff; font-size:1.2em; margin-right:10px; margin-left:-5px; }
        .search-input { flex-grow:1; border:none; background:#4A2C18; color:#fff; font-size:1em; outline:none; margin-left:6px; }
        .search-input::placeholder { color:#d7ccc8; }
        .search-clear { background:none; border:none; color:#d7ccc8; font-size:1em; cursor:pointer; padding:0 0 0 8px; display:none; }
        .search-clear.visible { display:block; }
        .history-dropdown { position:absolute; top:90%; left:50%; transform:translateX(-50%); width:100%; max-width:500px; background-color:#4A2C18; border-bottom-left-radius:20px; border-bottom-right-radius:20px; padding-top:20px; padding-bottom:10px; margin-top:-30px; box-shadow:0 15px 30px rgba(0,0,0,.4); z-index:5; display:none; }
        .history-dropdown.show { display:block; }
        .separator-line { height:1px; background-color:rgba(255,255,255,.3); margin:0 20px 10px; }
        .history-header { display:flex; justify-content:space-between; align-items:center; padding:0 20px 6px; color:#d7ccc8; font-size:.8em; }
        .history-header button { background:none; border:none; color:#d7ccc8; cursor:pointer; font-size:.85em; text-decoration:underline; padding:0; }
        .history-item { display:flex; align-items:center; gap:8px; padding:5px 20px; color:#fff; font-size:1em; cursor:pointer; }
        .history-item:hover { background-color:rgba(255,255,255,.1); }
        .history-item .fa-history { color:#fff; margin-right:15px; }
        .history-text { flex-grow:1; font-weight:500; }
        .history-item .fa-times-circle { margin-left:auto; color:#d7ccc8; font-size:.85em; opacity:0; transition:opacity .2s; }
        .history-item:hover .fa-times-circle { opacity:1; }

        .no-result { text-align:center; padding:40px 20px; color:#888; font-size:1.1em; display:none; }
        .no-result i { font-size:2.5em; color:#ccc; display:block; margin-bottom:10px; }

        /* ARTICLE GRID */
        .article-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:30px; padding:0 5%; margin-bottom:50px; }
        .card { background-color:#fff; padding:3px; border-radius:8px; box-shadow:10px 10px 10px rgba(0,0,0,.3); transition:transform .2s; display:flex; flex-direction:column; }
        .card:hover { transform:translateY(-5px); }
        .card.hidden { display:none; }
        .card-title { font-size:1em; font-weight:bold; text-align:left; margin:15px 15px 0; color:#333; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:2.6em; line-height:1.3em; }
        .card-image-wrapper { flex:1; width:100%; display:flex; justify-content:center; align-items:center; padding:10px 0; margin-bottom:10px; }
        .card-image { width:70%; height:auto; aspect-ratio:3/4; object-fit:cover; border-radius:10px; box-shadow:10px 10px 10px rgba(0,0,0,.3); display:block; }
        .card-footer { display:flex; justify-content:space-between; padding:10px 15px; align-items:center; }
        .card-year { font-size:.9em; font-weight:bold; color:#333; margin-bottom:10px; margin-left:5px; }
        .read-button { display:flex; align-items:center; justify-content:center; max-width:160px; margin:10px auto; background-color:#DAC6BB; border:none; color:#000; font-size:.85em; font-weight:bold; cursor:pointer; padding:5px 15px; border-radius:20px; text-decoration:none; box-shadow:0 2px 5px rgba(0,0,0,.15); transition:background-color .2s; position:relative; top:-5px; }
        .read-button:hover { background-color:#d3bba8; }
        .read-button-icon { width:12px; height:12px; object-fit:contain; margin-left:5px; }

        /* EMPTY STATE */
        .empty-state { text-align:center; padding:80px 20px; color:#888; grid-column:1/-1; }
        .empty-state i { font-size:3.5em; color:#ddd; display:block; margin-bottom:16px; }
        .empty-state h3 { font-size:1.2em; color:#aaa; margin-bottom:8px; }
        .empty-state p { font-size:14px; color:#bbb; }

        /* FOOTER */
        .footer { background-color:#4a2c18; color:#fff; padding:40px 20px; font-size:14px; }
        .footer-container { max-width:1200px; margin:0 auto; display:grid; grid-template-columns:1.2fr 2.5fr 1fr; gap:20px; align-items:flex-start; }
        .footer-kolom { padding:0 10px; }
        .logo-info { display:flex; flex-direction:column; align-items:flex-start; }
        .logo-title { display:flex; align-items:center; margin-bottom:10px; }
        .logo-title img { width:40px; height:40px; margin-right:8px; }
        .logo-title h3 { color:#fff; margin:0; font-size:18px; }
        .logo-info p { margin:0 0 15px; line-height:1.5; }
        .sosial-media-wrapper { margin-top:10px; }
        .sosial-media { display:flex; margin-bottom:5px; }
        .sosial-media a { color:#fff; font-size:24px; margin-right:15px; text-decoration:none; }
        .middle-section { display:flex; flex-direction:column; }
        .navigasi-horizontal { display:flex; justify-content:space-between; margin-bottom:30px; flex-wrap:wrap; color:#fff; }
        .navigasi-horizontal a { color:inherit; text-decoration:none; font-weight:bold; font-size:16px; padding-right:20px; }
        .middle-section h4 { color:#fff; margin-top:0; margin-bottom:10px; font-weight:bold; font-size:16px; }
        .kontak .item-kontak { margin-bottom:15px; }
        .kontak .item-kontak p { margin:2px 0; }
        .kontak .item-kontak p:first-child { font-weight:bold; margin-bottom:2px; color:#fff; display:flex; align-items:center; }
        .kontak .item-kontak i { margin-right:8px; font-size:18px; color:#fff; }
        a { text-decoration:none; }

        @media (max-width:1000px) { .article-grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:500px) { .article-grid { grid-template-columns:1fr; padding:0 20px; } .search-box,.history-dropdown { max-width:90%; } }
        @media (max-width:768px) { .footer-container { grid-template-columns:1fr; gap:30px; } .navigasi-horizontal { flex-direction:column; margin-bottom:15px; } .navigasi-horizontal a { margin-bottom:10px; padding-right:0; } }
    </style>
</head>
<body>

<?php include 'navbar_user.php'; ?>

<!-- SEARCH -->
<div class="search-container">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Cari artikel <?= $nama_kat ?>..." class="search-input" id="search-input" autocomplete="off">
        <button class="search-clear" id="search-clear"><i class="fas fa-times"></i></button>
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

<div class="no-result" id="no-result">
    <i class="fas fa-search"></i>
    Tidak ada artikel yang cocok dengan "<span id="no-result-keyword"></span>"
</div>

<!-- ARTICLE GRID -->
<div class="article-grid" id="article-grid">
    <?php if (empty($list_artikel)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <h3>Belum ada artikel di kategori ini</h3>
            <p>Artikel akan muncul di sini setelah admin menambahkannya.</p>
        </div>
    <?php else: ?>
        <?php foreach ($list_artikel as $art):
            $thumb = $art['thumbnail'];
            if (!empty($thumb)) {
                $src = file_exists(__DIR__ . '/uploads/' . $thumb)
                     ? 'uploads/' . htmlspecialchars($thumb)
                     : htmlspecialchars($thumb);
            } else {
                $src = 'cover1.jpg';
            }
        ?>
        <div class="card" data-title="<?= htmlspecialchars($art['judul']) ?>">
            <div class="card-title" title="<?= htmlspecialchars($art['judul']) ?>"><?= htmlspecialchars($art['judul']) ?></div>
            <div class="card-image-wrapper">
                <img src="<?= $src ?>"
                     alt="<?= htmlspecialchars($art['judul']) ?>"
                     class="card-image"
                     onerror="this.src='cover1.jpg'">
            </div>
            <div class="card-footer">
                <span class="card-year"><?= $art['tahun'] ?></span>
                <a href="detail_artikel.php?slug=<?= urlencode($art['slug']) ?>" class="read-button">
                    Baca Sekarang <img src="majesticons_arrow-right.png" class="read-button-icon">
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

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
            <div>
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
    // DROPDOWN & SIDEBAR
    function toggleDropdown(event) {
        event.preventDefault();
        const menu = document.getElementById('dropdown-menu');
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('dropdown-menu');
        const dd = document.querySelector('.dropdown');
        if (dd && !dd.contains(e.target)) menu.style.display = 'none';
    });
    function toggleProfileDropdown(event) {
        event.stopPropagation();
        document.getElementById('profile-dropdown-menu').classList.toggle('show');
    }
    document.addEventListener('click', function(e) {
        const pd = document.getElementById('profile-dropdown');
        const menu = document.getElementById('profile-dropdown-menu');
        if (menu && pd && !pd.contains(e.target)) menu.classList.remove('show');
    });
    const sidebar = document.getElementById('sidebar');
    const hamburgerBtn = document.getElementById('hamburger-btn');
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
        const dd = event.target.nextElementSibling;
        document.querySelectorAll('.dropdown-menu-sidebar').forEach(d => { if (d !== dd) d.style.display = 'none'; });
        dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
    }
    hamburgerBtn.addEventListener('click', openSidebar);
    closeSidebarBtn.addEventListener('click', closeSidebar);

    // PENCARIAN
    const HISTORY_KEY = 'sejiwa_search_<?= $slug_kat ?>';
    const MAX_HISTORY = 8;
    const searchInput     = document.getElementById('search-input');
    const searchClear     = document.getElementById('search-clear');
    const historyDropdown = document.getElementById('history-dropdown');
    const historyList     = document.getElementById('history-list');
    const noResult        = document.getElementById('no-result');
    const noResultKw      = document.getElementById('no-result-keyword');
    const cards           = document.querySelectorAll('.card');

    function getHistory()      { try { return JSON.parse(localStorage.getItem(HISTORY_KEY)) || []; } catch { return []; } }
    function saveHistory(list) { localStorage.setItem(HISTORY_KEY, JSON.stringify(list)); }
    function addToHistory(kw)  { if (!kw.trim()) return; let h = getHistory().filter(x => x.toLowerCase() !== kw.toLowerCase()); h.unshift(kw); if (h.length > MAX_HISTORY) h = h.slice(0, MAX_HISTORY); saveHistory(h); }
    function removeHistoryItem(kw) { saveHistory(getHistory().filter(h => h !== kw)); renderHistory(); }
    function clearAllHistory()     { saveHistory([]); renderHistory(); }

    function renderHistory() {
        const history = getHistory();
        historyList.innerHTML = '';
        if (!history.length) { historyList.innerHTML = '<p style="color:#d7ccc8;font-size:.85em;padding:6px 20px 10px;">Belum ada riwayat.</p>'; return; }
        history.forEach(kw => {
            const item = document.createElement('div');
            item.className = 'history-item';
            item.innerHTML = `<i class="fas fa-history"></i><span class="history-text">${kw}</span><i class="fas fa-times-circle"></i>`;
            item.querySelector('.history-text').addEventListener('click', () => { searchInput.value = kw; filterCards(kw); toggleClear(); historyDropdown.classList.remove('show'); });
            item.querySelector('.fa-times-circle').addEventListener('click', e => { e.stopPropagation(); removeHistoryItem(kw); });
            historyList.appendChild(item);
        });
    }
    function filterCards(kw) {
        const q = kw.trim().toLowerCase();
        let vis = 0;
        cards.forEach(c => { const t = c.getAttribute('data-title').toLowerCase(); if (!q || t.includes(q)) { c.classList.remove('hidden'); vis++; } else c.classList.add('hidden'); });
        noResult.style.display = (q && !vis) ? 'block' : 'none';
        if (q && !vis) noResultKw.textContent = kw;
    }
    function toggleClear() { searchInput.value.length ? searchClear.classList.add('visible') : searchClear.classList.remove('visible'); }

    searchInput.addEventListener('input', function() { filterCards(this.value); toggleClear(); if (this.value.trim()) historyDropdown.classList.remove('show'); else { renderHistory(); historyDropdown.classList.add('show'); } });
    searchInput.addEventListener('focus', function() { if (!this.value.trim()) { renderHistory(); historyDropdown.classList.add('show'); } });
    searchInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') { const kw = this.value.trim(); if (kw) { addToHistory(kw); filterCards(kw); } historyDropdown.classList.remove('show'); this.blur(); } });
    searchClear.addEventListener('click', function() { searchInput.value = ''; filterCards(''); toggleClear(); renderHistory(); historyDropdown.classList.add('show'); searchInput.focus(); });
    document.body.addEventListener('click', function(e) { const sc = document.querySelector('.search-container'); if (sc && !sc.contains(e.target)) { const kw = searchInput.value.trim(); if (kw) addToHistory(kw); setTimeout(() => historyDropdown.classList.remove('show'), 100); } });
</script>
</body>
</html>