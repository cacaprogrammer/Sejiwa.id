<?php
// dashboardAdmin.php — File utama admin (Modul 18: Pemanggilan Halaman)
include "cek_admin.php";
include "koneksi.php";

// Whitelist halaman yang boleh dipanggil (keamanan dari LFI)
$allowed = ['dashboard', 'artikel', 'user'];

// Ambil parameter ?page= dari URL, default: dashboard
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Kalau halaman tidak ada di whitelist, paksa ke dashboard
if (!in_array($page, $allowed)) {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Sejiwa.id</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'sejiwa-dark':   '#4A2C18',
                        'sejiwa-medium': '#6B3E23',
                        'sejiwa-light':  '#A3826F',
                    }
                }
            }
        }
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 250px;
            flex-shrink: 0;
            background-color: #4A2C18;
            padding: 2rem 0.5rem;
            color: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.2);
            position: fixed;
            top: 0; left: -275px;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: left 0.3s ease-in-out;
        }

        .sidebar.is-open { left: 0; }

        .menu-toggle {
            background: none; border: none;
            cursor: pointer; padding: 0.5rem;
            margin-right: 1rem; color: #4A2C18;
            display: block; border-radius: 0.375rem;
            transition: background-color 0.2s;
        }
        .menu-toggle:hover { background-color: #e0e0e0; }
        .menu-toggle svg { width: 1.5rem; height: 1.5rem; }

        .overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999; display: none;
        }
        .overlay.is-active { display: block; }

        .main-content-area {
            flex-grow: 1;
            padding: 1rem;
            width: 100%;
        }

        @media (min-width: 1024px) {
            body { flex-direction: row; }
            .sidebar {
                position: sticky; left: 0 !important;
                top: 0; height: 100vh; z-index: 10;
            }
            .menu-toggle { display: none; }
            .main-content-area { padding: 2rem; }
            .overlay.is-active { display: none; }
        }

        /* ===== NAV ===== */
        .sidebar-header {
            margin-bottom: 2rem; padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.3);
            padding-left: 1rem;
        }
        .sidebar-nav ul { list-style: none; padding: 0; margin: 0; }
        .nav-item a {
            display: flex; align-items: center;
            padding: 0.75rem 1rem; margin-bottom: 0.5rem;
            border-radius: 0.5rem; color: white;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .nav-item a:hover { background-color: #6B3E23; }
        .nav-item a.active {
            background-color: #A3826F;
            font-weight: bold;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .icon {
            fill: currentColor; margin-right: 0.75rem;
            width: 1.25rem; height: 1.25rem;
        }

        /* ===== HEADER ===== */
        .main-header {
            margin-bottom: 1.5rem;
            display: flex; align-items: center;
            justify-content: space-between;
        }
        .header-left { display: flex; align-items: center; }
        .logo-img {
            width: 3rem; height: 3rem;
            border-radius: 50%; margin-right: 0.75rem;
        }
        .logo-text-img {
            height: 45px; width: auto;
            margin-left: -15px; position: relative; bottom: 3px;
        }

        /* Info admin di kanan header */
        .admin-info {
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; color: #555;
        }
        .admin-info strong { color: #4A2C18; }
        .btn-logout {
            background: #4A2C18; color: white;
            padding: 6px 14px; border-radius: 8px;
            text-decoration: none; font-size: 13px;
            transition: background 0.2s;
        }
        .btn-logout:hover { background: #6B3E23; }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h1 class="text-xl font-bold">Sejiwa Admin</h1>
        <p class="text-xs text-white/60 mt-1">Halo, <?= $_SESSION['nama_lengkap'] ?>!</p>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item">
                <a href="dashboardAdmin.php?page=dashboard" 
                   class="<?= $page === 'dashboard' ? 'active' : '' ?>">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                    </svg>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="dashboardAdmin.php?page=artikel"
                   class="<?= $page === 'artikel' ? 'active' : '' ?>">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 16H6c-.55 0-1-.45-1-1V6c0-.55.45-1 1-1h12c.55 0 1 .45 1 1v12c0 .55-.45 1-1 1zM7 9h2v2H7zm4 0h6v2h-6zm-4 4h2v2H7zm4 0h6v2h-6z"/>
                    </svg>
                    Manajemen Artikel
                </a>
            </li>
            <li class="nav-item">
                <a href="dashboardAdmin.php?page=user"
                   class="<?= $page === 'user' ? 'active' : '' ?>">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    Data User
                </a>
            </li>
            <li class="nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem;">
                <a href="logout.php">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                    Logout
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- ===== KONTEN UTAMA ===== -->
<div class="main-content-area">

    <header class="main-header">
        <div class="header-left">
            <!-- Tombol burger (mobile) -->
            <button id="sidebarToggle" class="menu-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                </svg>
            </button>
            <img src="logobenar.png" alt="Logo" class="logo-img">
            <img src="sejput.png" alt="Sejiwa" class="logo-text-img">
        </div>
        <div class="admin-info">
            <span>👤 <strong><?= $_SESSION['nama_lengkap'] ?></strong> (Admin)</span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <!-- Konten halaman dipanggil sesuai ?page= (Modul 18) -->
    <main>
        <?php include "pages/$page.php"; ?>
    </main>

</div>

<!-- Overlay mobile -->
<div class="overlay" id="sidebarOverlay"></div>

<script>
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('is-open');
        overlay.classList.toggle('is-active');
    }

    toggleBtn.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('is-open');
            overlay.classList.remove('is-active');
        }
    });
</script>

</body>
</html>