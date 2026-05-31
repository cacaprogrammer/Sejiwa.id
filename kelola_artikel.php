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

$user_id = $_SESSION['id'];

// Ambil semua artikel milik user ini
$stmt = $conn->prepare("
    SELECT id, judul, slug, status, created_at, kategori, catatan_admin, penulis
    FROM tb_artikel
    WHERE created_by = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$artikel_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung per status
$total       = count($artikel_list);
$menunggu    = count(array_filter($artikel_list, fn($a) => $a['status'] === 'pending'));
$diterbitkan = count(array_filter($artikel_list, fn($a) => $a['status'] === 'published'));
$ditolak     = count(array_filter($artikel_list, fn($a) => $a['status'] === 'rejected'));

// Filter tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'semua';
$filtered = match($tab) {
    'menunggu'    => array_filter($artikel_list, fn($a) => $a['status'] === 'pending'),
    'diterbitkan' => array_filter($artikel_list, fn($a) => $a['status'] === 'published'),
    'ditolak'     => array_filter($artikel_list, fn($a) => $a['status'] === 'rejected'),
    default       => $artikel_list,
};

// ── Ambil kategori dinamis dari DB (sama seperti navbar_user.php) ──
$daftar_kategori = [];
$res_kat = $conn->query("SELECT nama_kategori, slug_kategori FROM tb_kategori ORDER BY nama_kategori ASC");
if ($res_kat) {
    while ($row = $res_kat->fetch_assoc()) {
        $daftar_kategori[] = $row;
    }
}

// Icon per slug (fallback ke generic jika tidak dikenal)
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Artikel — Sejiwa.id</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f1ee;
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 240px;
            flex-shrink: 0;
            background-color: #4a2c18;
            color: white;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 1.5rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: white;
        }

        .sidebar-logo img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
        }

        .sidebar-logo-name { font-size: 15px; font-weight: bold; line-height: 1.2; }
        .sidebar-logo-sub  { font-size: 11px; color: rgba(255,255,255,0.6); }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
            border-left: 3px solid transparent;
        }

        .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }

        .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: #f0c080;
            font-weight: bold;
        }

        .nav-link i { width: 18px; text-align: center; font-size: 15px; }

        .sidebar-divider {
            height: 1px;
            background: rgba(255,255,255,0.15);
            margin: 0.5rem 0.75rem;
        }

        .nav-link.logout { color: #ffb3a7; }
        .nav-link.logout:hover { background: rgba(255,100,80,0.15); color: #ff7c6c; }

        /* ===== MAIN ===== */
        .main { flex: 1; padding: 2rem; max-width: 900px; }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }

        .page-title { font-size: 22px; font-weight: bold; color: #2d1a0e; }
        .page-sub   { font-size: 13px; color: #888; margin-top: 3px; }

        /* ── Dropdown Tambah Artikel ── */
        .dropdown-wrap { position: relative; }

        .btn-primary {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #4a2c18;
            color: white;
            padding: 9px 18px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
        }

        .btn-primary:hover { background: #6b3e23; }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            background: white;
            border: 1px solid #e8ddd5;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            min-width: 200px;
            padding: 6px 0;
            display: none;
            z-index: 100;
        }

        .dropdown-menu.open { display: block; }

        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -7px;
            right: 20px;
            width: 14px;
            height: 14px;
            background: white;
            border-left: 1px solid #e8ddd5;
            border-top: 1px solid #e8ddd5;
            transform: rotate(45deg);
        }

        .ddm-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            color: #333;
            font-size: 14px;
            text-decoration: none;
            transition: background 0.15s;
        }

        .ddm-item:hover { background: #fdf5f0; color: #4a2c18; }
        .ddm-item i { color: #a3826f; width: 16px; text-align: center; }
        .ddm-sep { height: 1px; background: #f0e8e0; margin: 4px 8px; }

        /* Tab bar */
        .tab-bar {
            display: flex;
            gap: 0;
            border-bottom: 1.5px solid #e0d5cd;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }

        .tab {
            padding: 9px 16px;
            font-size: 13px;
            color: #888;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -1.5px;
            transition: color 0.15s;
            white-space: nowrap;
        }

        .tab:hover { color: #4a2c18; }
        .tab.active { color: #4a2c18; border-bottom-color: #4a2c18; font-weight: bold; }

        /* Artikel cards */
        .artikel-list { display: flex; flex-direction: column; gap: 12px; }

        .artikel-card {
            background: white;
            border: 1px solid #e8ddd5;
            border-radius: 14px;
            padding: 16px 18px;
        }

        .artikel-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .artikel-judul { font-size: 14.5px; font-weight: bold; color: #2d1a0e; line-height: 1.4; }

        .status-pill {
            font-size: 11.5px;
            padding: 3px 12px;
            border-radius: 20px;
            white-space: nowrap;
            flex-shrink: 0;
            font-weight: 600;
        }

        .pill-menunggu  { background: #fff3d6; color: #7a5200; }
        .pill-published { background: #d6f5e3; color: #1a6b3a; }
        .pill-ditolak   { background: #fde8e8; color: #8b1a1a; }
        .pill-draft     { background: #f0f0f0; color: #555; }

        .artikel-meta {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }

        .artikel-meta i { margin-right: 4px; font-size: 12px; }

        .info-box {
            font-size: 12.5px;
            padding: 9px 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 7px;
        }

        .info-box.locked   { background: #f5f0eb; color: #7a6050; }
        .info-box.rejected { background: #fff3d6; color: #7a5200; }

        .btn-row { display: flex; gap: 8px; flex-wrap: wrap; }

        .btn {
            font-size: 12.5px;
            padding: 6px 14px;
            border: 1px solid #d5c9c0;
            border-radius: 8px;
            background: white;
            color: #333;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.15s;
        }

        .btn:hover    { background: #f7f3f0; }
        .btn-danger   { border-color: #f5b8b8; color: #8b1a1a; }
        .btn-danger:hover { background: #fde8e8; }
        .btn-success  { background: #4a2c18; color: white; border-color: #4a2c18; }
        .btn-success:hover { background: #6b3e23; }

        .empty-state { text-align: center; padding: 3rem 1rem; color: #aaa; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; color: #d5c9c0; display: block; }
        .empty-state p { font-size: 14px; }

        /* Mobile */
        .mobile-header {
            display: none;
            align-items: center;
            justify-content: space-between;
            background: #4a2c18;
            color: white;
            padding: 0 16px;
            height: 50px;
            position: sticky;
            top: 0;
            z-index: 200;
        }

        .mobile-header-title { font-size: 15px; font-weight: bold; }
        .hamburger-btn { background: none; border: none; color: white; font-size: 20px; cursor: pointer; }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 150;
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }

            .sidebar {
                position: fixed;
                top: 0;
                left: -260px;
                height: 100vh;
                z-index: 300;
                transition: left 0.3s;
            }

            .sidebar.open { left: 0; }
            .sidebar-overlay.open { display: block; }
            .mobile-header { display: flex; }
            .main { padding: 1.25rem; max-width: 100%; }
            .page-header { flex-direction: column; gap: 10px; }
            .btn-primary { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    <!-- Mobile Header -->
    <div class="mobile-header">
        <button class="hamburger-btn" id="hamburgerBtn">
            <i class="fas fa-bars"></i>
        </button>
        <span class="mobile-header-title">Kelola Artikel</span>
        <span style="width:32px"></span>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="landingpagepilihanfix.php" class="sidebar-logo">
                <img src="logobenar.png" alt="Logo">
                <div>
                    <div class="sidebar-logo-name">Sejiwa.id</div>
                    <div class="sidebar-logo-sub"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></div>
                </div>
            </a>
        </div>

        <nav class="sidebar-nav">
            <a href="kelola_artikel.php" class="nav-link active">
                <i class="fas fa-pen-to-square"></i> Kelola Artikel
            </a>

            <div class="sidebar-divider"></div>

            <a href="logout.php" class="nav-link logout">
                <i class="fas fa-sign-out-alt"></i> Keluar
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title">Kelola Artikel</div>
                <div class="page-sub">Pantau dan kelola artikel yang pernah Anda unggah</div>
            </div>

            <!-- ── Dropdown Tambah Artikel DINAMIS ── -->
            <div class="dropdown-wrap" id="ddWrap">
                <button class="btn-primary" id="ddBtn">
                    <i class="fas fa-plus"></i> Tambah Artikel
                    <i class="fas fa-chevron-down" style="font-size:11px"></i>
                </button>
                <div class="dropdown-menu" id="ddMenu">
                    <?php if (empty($daftar_kategori)): ?>
                        <span class="ddm-item" style="color:#aaa;cursor:default;">
                            <i class="fas fa-exclamation-circle"></i> Belum ada kategori
                        </span>
                    <?php else: ?>
                        <?php foreach ($daftar_kategori as $i => $kat): ?>
                            <?php if ($i > 0): ?>
                                <div class="ddm-sep"></div>
                            <?php endif; ?>
                            <a href="tambah_artikel.php?kategori=<?= urlencode($kat["slug_kategori"]) ?>" class="ddm-item">
                                <i class="<?= ikonKategori($kat["slug_kategori"]) ?>"></i>
                                <?= htmlspecialchars($kat["nama_kategori"]) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab filter -->
        <div class="tab-bar">
            <a href="kelola_artikel.php?tab=semua"
               class="tab <?= $tab === 'semua' ? 'active' : '' ?>">
                Semua (<?= $total ?>)
            </a>
            <a href="kelola_artikel.php?tab=menunggu"
               class="tab <?= $tab === 'menunggu' ? 'active' : '' ?>">
                Menunggu (<?= $menunggu ?>)
            </a>
            <a href="kelola_artikel.php?tab=diterbitkan"
               class="tab <?= $tab === 'diterbitkan' ? 'active' : '' ?>">
                Diterbitkan (<?= $diterbitkan ?>)
            </a>
            <a href="kelola_artikel.php?tab=ditolak"
               class="tab <?= $tab === 'ditolak' ? 'active' : '' ?>">
                Ditolak (<?= $ditolak ?>)
            </a>
        </div>

        <!-- Daftar artikel -->
        <div class="artikel-list">
            <?php if (empty($filtered)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <p>Belum ada artikel di kategori ini.</p>
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
                            <span>Artikel ini sudah diverifikasi dan dikelola oleh admin. Anda hanya dapat melihat statusnya.</span>
                        </div>
                        <div class="btn-row">
                            <a href="detail_artikel.php?slug=<?= urlencode($art['slug']) ?>"
                               class="btn" target="_blank">
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
                            <a href="edit_artikel.php?id=<?= $art['id'] ?>" class="btn">
                                <i class="fas fa-edit"></i> Perbaiki
                            </a>
                            <a href="ajukan_ulang.php?id=<?= $art['id'] ?>" class="btn btn-success">
                                <i class="fas fa-paper-plane"></i> Ajukan Ulang
                            </a>
                            <a href="hapus_artikel.php?id=<?= $art['id'] ?>"
                               class="btn btn-danger"
                               onclick="return confirm('Yakin ingin menghapus artikel ini?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </div>

                    <?php else: /* pending atau draft */ ?>
                        <div class="btn-row">
                            <a href="edit_artikel.php?id=<?= $art['id'] ?>" class="btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="hapus_artikel.php?id=<?= $art['id'] ?>"
                               class="btn btn-danger"
                               onclick="return confirm('Yakin ingin menghapus artikel ini?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        const ddBtn  = document.getElementById('ddBtn');
        const ddMenu = document.getElementById('ddMenu');
        const ddWrap = document.getElementById('ddWrap');

        ddBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            ddMenu.classList.toggle('open');
        });

        document.addEventListener('click', function(e) {
            if (!ddWrap.contains(e.target)) ddMenu.classList.remove('open');
        });

        const sidebar        = document.getElementById('sidebar');
        const hamburgerBtn   = document.getElementById('hamburgerBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        hamburgerBtn.addEventListener('click', function() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('open');
        });

        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('open');
        });
    </script>
</body>
</html>