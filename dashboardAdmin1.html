<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Utama</title>
    
    <!--membuat semua class pakai tailwind-->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Ariel', 'sans-serif'],
                    },
                    colors: {
                        // admin dashboard
                        'sejiwa-dark': '#4A2C18',
                        'sejiwa-medium': '#6B3E23',
                        'sejiwa-light': '#A3826F', 
                        
                    }
                }
            }
        }
    </script>
    
    <style>
        /* reset defauld browser*/
        body {
            font-family:Arial, sans-serif;
            background-color: #f7f7f7; 
            margin: 0;
            padding: 0;
            display: flex; 
            flex-direction: column; 
            min-height: 100vh;
            position: relative;
        }

        /* Sidebar: disembunyikan/tergeser di layar kecil */
        .sidebar {
            width: 250px; 
            flex-shrink: 0;
            background-color: #4A2C18; 
            padding: 2rem 0.5rem;
            color: white;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2); 
            
            /* KUNCI MOBILE */
            position: fixed; 
            top: 0;
            left: -275px; /* ini untuk sembunyi */
            height: 100vh;
            overflow-y: auto;
            z-index: 1000; /* ini untuk di atas semua elemen */
            transition: left 0.3s ease-in-out; /* Animasi geser */
        }
        
        /* Kelas untuk menampilkan sidebar (ditambah JS) */
        .sidebar.is-open {
            left: 0; 
        }

        /* tombol burger */
        .menu-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            margin-right: 1rem;
            color: #4A2C18; 
            display: block; 
            border-radius: 0.375rem;
            transition: background-color 0.2s;
        }
        .menu-toggle:hover {
            background-color: #e0e0e0;
        }
        .menu-toggle svg {
            width: 1.5rem;
            height: 1.5rem;
        }
        
        /* Overlay di Mobile saat sidebar terbuka */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        .overlay.is-active {
            display: block;
        }

        /* area putih */
        .main-content-area {
            flex-grow: 1;
            padding: 1rem; 
            width: 100%; 
        }

        /* Responsive Breakpoint (Desktop) */
        @media (min-width: 1024px) {
            body {
                flex-direction: row; 
            }
            .sidebar {
                position: sticky;
                left: 0;
                top: 0;
                height: 100vh; 
                box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1); 
                z-index: 10;
                left: 0 !important; 
            }
            .menu-toggle {
                display: none; 
            }
            .main-content-area {
                padding: 2rem;
            }
            .overlay.is-active {
                display: none;
            }
        }


        /*disebar dan navigasi link*/
        .sidebar-header {
            margin-bottom: 3rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgb(255, 255, 255);
        }
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .nav-item a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            color: rgb(255, 255, 255);
            text-decoration: none;
            transition: background-color 0.2s, color 0.2s;
        }
        .nav-item a:hover {
            background-color: #6B3E23; /* Coklat sedang */
            color: white;
        }
        .nav-item .active {
            background-color: #A3826F; 
            color: white;
            font-weight: bold;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Icon Utility (for sidebar) */
        .icon {
            fill: currentColor;
            margin-right: 0.75rem;
            width: 1.25rem;
            height: 1.25rem;
        }


        /*header dan logo*/
        .main-header {
            margin-bottom: 2rem; 
            display: flex;
            align-items: center;
            position: static;
        }
        .logo-img {
            width: 3rem; 
            height: 3rem; 
            border-radius: 50%;
            margin-right: 0.75rem;
            position: static; 
            top: auto;
        }
        .logo-text-img{
            height: 55px;
            width: auto;
            margin-left: -20px;
            position: static; 
            top: auto;
            position: relative;
            bottom: 5px;
        }
        .text-main-title {
            color: #000; 
        } 

        /*Layout Konten */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr; 
            gap: 2rem; 
        }
        @media (min-width: 1024px) { 
            .main-grid {
                grid-template-columns: repeat(5, 1fr); 
            }
        }

        /* Penempatan Kolom */
        .col-left {
            grid-column: span 1;
        }
        .col-right {
            grid-column: span 1;
        }
        @media (min-width: 1024px) {
            .col-left {
                grid-column: span 3; 
            }
            .col-right {
                grid-column: span 2; 
            }
        }
        .space-y > * + * { 
            margin-top: 2rem;
        }

        /* Styling Card */
        .card {
            padding: 1.5rem; 
            border-radius: 1rem; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); 
            min-height: 200px;
        }
        .card-title {
            font-size: 1.5rem; 
            font-weight: 600; 
            margin-bottom: 1.5rem; 
        }
        
        .card-stats {
            background: linear-gradient(
                to bottom,
                #A3826F 0%,
                #6B3E23 40%,
                #4A2C18 100%
            );
            overflow: hidden;
            position: relative;
            color: white;
            min-height: 300px;
            
            /*Layout Flex untuk Statistik */
            display: flex; 
            flex-direction: column; 
            padding: 2.5rem; 
        }

        .card-kategori {
            background-color: #A3826F;
            overflow: hidden;
            position: relative;
            color: white;
            min-height: 300px;
        }
        .card-popularitas {
            background-color: #DAC6BB; 
        }
        .card-performa {
            background-color: #D9D9D9; 
        }

        /* Statistik Pengunjung*/
        .card-stats .card-title {
            position: static; /* Normalisasi posisi judul */
            font-size: 1.5rem; 
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            top: 15px;
            right: 5px;
        }
        
        /* Wrapper utama untuk Angka, Chart, dan Ilustrasi */
        .stats-main-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            position: relative;
            flex-grow: 1;
            padding-right: 150px; 
        }

        .stats-number-area {
             flex-shrink: 0;
             text-align: center;
             padding-bottom: 1rem; 
             position: relative;
             z-index: 2; /
        }
        .stats-number-area span { 
            font-size: 5rem; 
            font-weight: bold;
            line-height: 1; 
            display: block;
            position: static; 
            color: white; 
        }

        /* Area Chart yang di-scroll */
        .chart-scroll-area {
            overflow-x: auto; 
            flex-grow: 1;
            -webkit-overflow-scrolling: touch; 
            padding-bottom: 25px; 
            
            /*  MENGHILANGKAN SCROLLBAR */
            /* Untuk Chrome, Safari, dan Opera */
            &::-webkit-scrollbar {
                display: none;
            }
            /* Untuk Firefox */
            scrollbar-width: none; 
        }

        /* Container Chart */
        .bar-chart-container {
            height: 150px; 
            display: flex;
            align-items: flex-end;
            gap: 15px; 
            position: relative;
            width: 500px; 
            max-width: none; 
        }
        .bar-chart-container .bar {
            width: 10px;
            border-radius: 4px;
            transition: all 0.3s ease-in-out; 
            width: 30px;
            flex-grow: 0;
            flex-shrink: 0;
        }
        .bar-labels {
            position: absolute;
            left: 0;
            right: 0;
            bottom: -20px; 
            display: flex;
            justify-content:space-between;
            padding: 0 2px;
            width: 300px; /* Samakan dengan lebar .bar-chart-container */
        }
        .bar-labels span {
            flex: none; 
            width: 30px;
            text-align: center;
            font-size: 0.65rem;
            gap: 5px; 
        }

        /* Ilustrasi */
        .illustration-area {
            position: absolute;
            top: 0; 
            right: 0;
            width: 50%; /* Jaga ruang untuk ilustrasi */
            height: 100%; 
            overflow: hidden;
            border-radius: 0 1rem 1rem 0; 
            pointer-events: none; 
            z-index: 1; 
        }
        .illustration-img {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 105%; 
            height: 105%;
            object-fit: contain;
            transform: scale(1.1) translateX(10%); 
        }
        @media (min-width: 1024px) {
             .stats-main-wrapper {
                padding-right: 180px;
            }
            .illustration-area {
                width: 30%;
            }
            .bar-chart-container {
                width: 450px; 
            }
        }

        /* Popularitas */
        .article-list { 
            display: flex; 
            flex-direction: column; 
            gap: 0.5rem; }
        .article-item { 
            display: flex; 
            align-items: center; 
            padding: 0.75rem 0; 
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        .article-item:last-child {
             border-bottom: none;
        }
        .article-thumb { 
            width: 2.5rem; 
            height: 2.5rem; 
            margin-right: 1rem; 
            border-radius: 50%; 
            object-fit: cover; 
        }
        .article-title { 
            font-weight: 600; 
            color: #000; 
            line-height: 1.2; 
        }
        .article-meta { 
            font-size: 0.8rem; 
            color: #000; 
            margin-top: 0.1rem; 
        }

        /* Kategori */
        .category-content-wrapper { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: flex-start; 
            gap: 1.5rem; 
            padding-top: 1rem; 
        }
        .category-chart-row { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            width: 100%; 
            gap: 1rem; 
            flex-direction: column; 
        }
        @media (min-width: 640px) {
            .category-chart-row {
                flex-direction: row; 
            }
        }
        .pie-chart-visual {
            width: 130px; 
            height: 130px; 
            border-radius: 50%; 
            flex-shrink: 0;
            background: conic-gradient( #4A2C18 0% 75%, #FFF 75% 100% ); 
            box-shadow: 0 0 0 10px rgba(0, 0, 0, 0.05);
        }
        .chart-legend { 
            display: flex; 
            flex-direction: column; 
            gap: 0.75rem; 
            padding-left: 0; 
            flex-grow: 1; 
            max-width: 100%; 
        }
        .legend-item { 
            display: flex; 
            align-items: center; 
        }
        .legend-dot { 
            display: inline-block; 
            width: 0.75rem; 
            height: 0.75rem; 
            border-radius: 50%; 
            margin-right: 0.75rem; 
            border: 1px solid; 
        }
        .legend-dot.bg-chart-dark 
        { background-color: #4A2C18; 
          border-color: #4A2C18; 
        }
        .legend-dot.bg-chart-light { 
            background-color: #fff; 
            border-color: #fff; 
        }
        .legend-text { 
            font-size: 0.95rem; 
            font-weight: 500; 
            color: white; }

        /* Performa */
        .performa-grid { 
            display: grid; 
            grid-template-columns: repeat(1, 1fr); 
            column-gap: 1.5rem; row-gap: 2rem; 
        }
        @media (min-width: 640px) {
            .performa-grid { 
                grid-template-columns: repeat(2, 1fr); }
        }
        .performa-subtitle { 
            font-size: 1rem; 
            font-weight: 600; 
            color: #000; 
            margin-bottom: 1rem; 
        }
        .performa-list > * + * { 
            margin-top: 0.75rem; }
        .performa-item { 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            transition: transform 0.2s;
        }
        .performa-item:hover {
            transform: translateX(5px);
        }
        .performa-thumb-round, .performa-thumb-square { 
            height: 2.5rem; 
            width: 2.5rem; 
            flex-shrink: 0; 
            object-fit: cover; 
            border: 1px solid #D6D6D6; 
            background-color: #EAEAEA; 
        }
        .performa-thumb-round { 
            border-radius: 50%; 
        }
        .performa-thumb-square { 
            border-radius: 0.375rem; 
        }
        .performa-text { 
            font-size: 0.875rem; 
            color: #000; 
            line-height: 1.25; 
            font-weight: 500; 
        }
        
    </style>
</head>
<body class="admin-layout">
    
   <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header px-4 pb-4 mb-4 border-b border-white/30">
            <h1 class="text-xl font-bold">Sejiwa Admin</h1>
        </div>

        <nav class="sidebar-nav">
            <ul>
                
                <li class="nav-item">
                    <a href="dashboardAdmin1.php" class="active" id="navDashboard">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15H9v-5h2v5zm4 0h-2v-5h2v5zm-5-8H9V7h2v2zm4 0h-2V7h2v2z"/></svg>
                        Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a href="dashboardAdmin2.php" id="navManajemenArtikel">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 16H6c-.55 0-1-.45-1-1v-4h14v4c0 .55-.45 1-1 1zm0-6H5V6c0-.55.45-1 1-1h12c.55 0 1 .45 1 1v7zM7 9h2v2H7z"/></svg>
                        Manajemen Artikel
                    </a>
                </li>

                <li class="nav-item">
                    <a href="dashboardAdmin3.php" id="navDataUser">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        Data User
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- AREA KONTEN UTAMA -->
    <div class="main-content-area">

        <header class="main-header">
            <!-- TOMBOL BURGER -->
            <button id="sidebarToggle" class="menu-toggle" aria-controls="sidebar" aria-expanded="false">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
            </button>
            
            
            
        </header>

        <!-- KONTEN UTAMA DASHBOARD -->
        <main class="dashboard-grid-container">
            
            <section id="dashboardContent" class="content-section"> 
                <div class="main-grid">
                    
                    <div class="col-left space-y">
                        
                        <!--  STATISTIK PENGUNJUNG -->
                        <div class="card card-stats">
                            <h2 class="card-title text-white">Statistik Pengunjung</h2>
                            
                            <div class="stats-main-wrapper"> 
                                
                                <div class="stats-number-area">
                                    <span class="text-8xl font-bold text-white leading-none">40</span>
                                </div>

                                <!-- Area Scroll Chart (Bisa digeser) -->
                                <div class="chart-scroll-area">
                                    <div class="bar-chart-container">
                                        
                                        <!-- Data baris chart -->
                                        <div class="bar h-4/5" style="height: 80%; background-color: #FFD400;"></div>
                                        <div class="bar h-3/5" style="height: 60%; background-color: #FFD400;"></div>
                                        <div class="bar h-full" style="height: 50%; background-color: #FFD400;"></div>
                                        <div class="bar h-3/5" style="height: 60%; background-color: #FFD400;"></div>
                                        <div class="bar h-1/2" style="height: 90%; background-color: #63B754;"></div>
                                        <div class="bar h-1/3" style="height: 33%; background-color: #FFD400;"></div>
                                        <div class="bar h-1/4" style="height: 25%; background-color: #FFD400;"></div>
                                        
                                        
                                        <!-- Label Hari -->
                                        <div class="bar-labels">
                                            <span>Sen</span>
                                            <span>Sel</span>
                                            <span>Rab</span>
                                            <span>Kam</span>
                                            <span>Jum</span>
                                            <span>Sab</span>
                                            <span>Ming</span>
                                            
                                        </div>
                                    </div>
                                </div>

                                <!-- gambar ilustrasi -->
                                <div class="illustration-area"> 
                                    <div class="illustration-bg">
                                        <img 
                                            src="d1.png" 
                                            alt="Ilustrasi Wanita Bekerja" 
                                            class="illustration-img"
        
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- POPULARITAS ARTIKEL -->
                        <div class="card card-popularitas">
                            <h2 class="card-title text-main-title">Popularitas Artikel</h2>
                            
                            <div class="article-list">
                                
                                <div class="article-item">
                                    <img 
                                        src="sumpah.jpg" 
                                        alt="Thumbnail Sumpah Pemuda" 
                                        class="article-thumb"
                                        
                                    >
                                    <div>
                                        <p class="article-title">Sumpah Pemuda</p>
                                        <p class="article-meta">36 Pembaca</p>
                                    </div>
                                </div>
                                
                                <div class="article-item">
                                    <img 
                                        src="proklamasi.png" 
                                        alt="Thumbnail Proklamasi" 
                                        class="article-thumb"
                                        
                                    >
                                    <div>
                                        <p class="article-title">Proklamasi Kemerdekaan</p>
                                        <p class="article-meta">50 Pembaca</p>
                                    </div>
                                </div>
                                
                                <div class="article-item">
                                    <img 
                                        src="bandung.jpg" 
                                        alt="Thumbnail Bandung Lautan Api" 
                                        class="article-thumb"
                                       
                                    >
                                    <div>
                                        <p class="article-title">Bandung Lautan Api</p>
                                        <p class="article-meta">22 Pembaca</p>
                                    </div>
                                </div>
                                
                                <div class="article-item">
                                    <img 
                                        src="sby.jpeg" 
                                        alt="Thumbnail Pertempuran Surabaya" 
                                        class="article-thumb"
                                        
                                    >
                                    <div>
                                        <p class="article-title">Pertempuran Surabaya</p>
                                        <p class="article-meta">30 Pembaca</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-right space-y">
                        
                        <!-- KATEGORI ARTIKEL  -->
                        <div class="card card-kategori">
                            <h2 class="card-title text-white">Kategori Artikel</h2>
                            
                            <div class="category-content-wrapper">
                                <div class="category-chart-row">
                                    <div class="pie-chart-visual">
                                    
                                    </div>

                                    
                                    <div class="chart-legend">
                                        <div class="legend-item">
                                            <span class="legend-dot bg-chart-dark border-white"></span>
                                            <span class="legend-text">Artikel Sejarah</span>
                                        </div>
                                        <div class="legend-item">
                                            <span class="legend-dot bg-chart-light border-gray"></span>
                                            <span class="legend-text">Artikel Biografi Tokoh </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--  PERFORMA KONTEN -->
                        <div class="card card-performa">
                            <h2 class="card-title text-main-title">Performa Konten</h2>
                            
                            <div class="performa-grid">
                                <div class="performa-col">
                                    <p class="performa-subtitle">Terbaru</p>
                                    <div class="performa-list">
                                        <div class="performa-item">
                                            <img src="ir.jpg" alt="Foto Ir.Sukarno" class="performa-thumb-round" onerror="this.src='https://placehold.co/40x40/D9D9D9/000?text=IS'">
                                            <span class="performa-text">Biografi Ir.Sukarno</span>
                                        </div>
                                        <div class="performa-item">
                                            <img src="Rectangle 15.png" alt="Foto Candi Prambanan" class="performa-thumb-round" onerror="this.src='https://placehold.co/40x40/D9D9D9/000?text=CP'">
                                            <span class="performa-text">Candi Prambanan</span>
                                        </div>
                                        <div class="performa-item">
                                            <img src="mey.jpg" alt="Foto Kerusuhan Mei 1998" class="performa-thumb-round" onerror="this.src='https://placehold.co/40x40/D9D9D9/000?text=KM'">
                                            <span class="performa-text">Kerusuhan Mei </span>
                                        </div>
                                    </div>
                                </div>

                                <!--  Rekomendasi Artikel  -->
                                <div class="performa-col">
                                    <p class="performa-subtitle">Rekomendasi Artikel</p>
                                    <div class="performa-list">
                                        <div class="performa-item">
                                            <img src="proklamasi.png" alt="Thumbnail Proklamasi" class="performa-thumb-square" onerror="this.src='https://placehold.co/40x40/D9D9D9/000?text=PK'">
                                            <span class="performa-text">Proklamasi Kemerdekaan</span>
                                        </div>
                                        <div class="performa-item">
                                            <img src="sumpah.jpg" alt="Thumbnail Sumpah Pemuda" class="performa-thumb-square" onerror="this.src='https://placehold.co/40x40/D9D9D9/000?text=SP'">
                                            <span class="performa-text">Sumpah Pemuda</span>
                                        </div>
                                        <div class="performa-item">
                                            <img src="g30.jpg" alt="Thumbnail Prisiwa G30s/PKI" class="performa-thumb-square" onerror="this.src='https://placehold.co/40x40/D9D9D9/000?text=G30'">
                                            <span class="performa-text">Prisiwa G30S/PKI</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
        </main>
    </div>
    
    <!-- OVERLAY (untuk menutup sidebar saat diklik di luar) -->
    <div class="overlay" id="sidebarOverlay"></div>

    <script>
        // JavaScript untuk mengontrol toggle sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');

            // Fungsi untuk membuka/menutup sidebar
            function toggleSidebar() {
                sidebar.classList.toggle('is-open');
                overlay.classList.toggle('is-active');
                const isExpanded = sidebar.classList.contains('is-open');
                toggleButton.setAttribute('aria-expanded', isExpanded);
            }

            // Event Listener untuk tombol burger
            if (toggleButton) {
                toggleButton.addEventListener('click', toggleSidebar);
            }

            // Event Listener untuk menutup sidebar saat klik overlay
            if (overlay) {
                overlay.addEventListener('click', toggleSidebar);
            }
            
            // Tutup sidebar di desktop jika resize dari mobile
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove('is-open');
                    overlay.classList.remove('is-active');
                    toggleButton.setAttribute('aria-expanded', 'false');
                }
            });
            
        });
    </script>
</body>
</html>
