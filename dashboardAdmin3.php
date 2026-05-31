<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Data User</title>
    
   <!--membuat semua class pakai tailwind-->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['ariel', 'sans-serif'],
                    },
                    colors: {
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
            font-family:  Arial, sans-serif; 
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
            left: -275px; 
            height: 100vh;
            overflow-y: auto;
            z-index: 1000; 
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
            background-color: #A3826F; /* Coklat muda (highlight) */
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

        /* area putih */
        .user-table-container {
            padding: 2rem;
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow-x: auto; 
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px; 
        }
        .user-table th, .user-table td {
            padding: 1rem 0.5rem;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .user-table th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #4A2C18;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .user-table tr:hover {
            background-color: #fcfcfc;
        }

        .level-badge {
            display: inline-block;
            padding: 0.3rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            min-width: 110px;
            text-align: center;
        }
        .badge-admin { background-color: #4A2C18; }
        .badge-penulis { background-color: #A3826F; }
        .badge-biasa { background-color: #DAC6BB; color: #4A2C18; } 

        .action-buttons button {
            padding: 0.3rem 0.6rem;
            margin-right: 0.5rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .btn-warn { background-color: #FBBF24; color: #78350F; }
        .btn-block { background-color: #EF4444; color: white; }
        .btn-warn:hover { background-color: #F59E0B; }
        .btn-block:hover { background-color: #DC2626; }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .table-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #4A2C18;
        }
        .btn-add-user {
            background-color: #4A2C18;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .btn-add-user:hover {
            background-color: #6B3E23;
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
                    <a href="dashboardAdmin1.php" id="navDashboard">
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
                    <a href="dashboardAdmin3html" class="active" id="navDataUser">
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
    <!-- TOMBOL BURGER  -->
    <button id="sidebarToggle" class="menu-toggle" aria-controls="sidebar" aria-expanded="false">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
    </button>
</header>   <!-- ★★★ INI YANG KURANG ★★★ -->

            
        <!-- DATA USER -->
        <main class="data-user-container">
            <section id="dataUserContent" class="content-section"> 
                <div class="user-table-container">
                    <div class="table-header">
                        <h2 class="table-title">Manajemen Data User</h2>
                        
                        <!-- TOMBOL TAMBAH PENGGUNA BARU -->
                        <button onclick="alertModal('Simulasi: Form Tambah Pengguna akan muncul.', 'Tambah Pengguna Baru')" class="btn-add-user flex items-center shadow-lg hover:shadow-xl transition duration-200">
                             <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Tambah Pengguna
                        </button>
                    </div>
                    
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>No ID</th>
                                <th>Nama Pengguna</th>
                                <th>Email</th>
                                <th>Akses</th>
                                <th>Terakhir Login</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>USR001</td>
                                <td>Admin Utama</td>
                                <td>admin@sejiwa.id</td>
                                <td><span class="level-badge badge-biasa">Pembaca</span></td>
                                <td>Baru saja</td>
                                <td class="action-buttons">
                                    <button class="btn-warn" onclick="alertModal('Simulasi: Peringatan dikirim ke USR001.', 'Aksi Peringatan')">Peringatkan</button>
                                    <button class="btn-block" onclick="alertModal('Simulasi: USR001 diblokir.', 'Aksi Blokir')">Blokir</button>
                                </td>
                            </tr>
                            <tr>
                                <td>USR002</td>
                                <td>Rudianto (Penulis)</td>
                                <td>rudianto@email.com</td>
                                <td><span class="level-badge badge-biasa">Pembaca</span></td>
                                <td>2 jam lalu</td>
                                <td class="action-buttons">
                                    <button class="btn-warn" onclick="alertModal('Simulasi: Peringatan dikirim ke USR002.', 'Aksi Peringatan')">Peringatkan</button>
                                    <button class="btn-block" onclick="alertModal('Simulasi: USR002 diblokir.', 'Aksi Blokir')">Blokir</button>
                                </td>
                            </tr>
                            <tr>
                                <td>USR003</td>
                                <td>Dewi Lestari</td>
                                <td>dewi.l@mail.id</td>
                                <td><span class="level-badge badge-biasa">Pembaca </span></td>
                                <td>3 hari lalu</td>
                                <td class="action-buttons">
                                    <button class="btn-warn" onclick="alertModal('Simulasi: Peringatan dikirim ke USR003.', 'Aksi Peringatan')">Peringatkan</button>
                                    <button class="btn-block" onclick="alertModal('Simulasi: USR003 diblokir.', 'Aksi Blokir')">Blokir</button>
                                </td>
                            </tr>
                            <tr>
                                <td>USR004</td>
                                <td>Budi Susanto</td>
                                <td>budi.susanto@mail.id</td>
                                <td><span class="level-badge badge-biasa">Pembaca </span></td>
                                <td>2 minggu lalu</td>
                                <td class="action-buttons">
                                    <button class="btn-warn" onclick="alertModal('Simulasi: Peringatan dikirim ke USR004.', 'Aksi Peringatan')">Peringatkan</button>
                                    <button class="btn-block" onclick="alertModal('Simulasi: USR004 diblokir.', 'Aksi Blokir')">Blokir</button>
                                </td>
                            </tr>
                            <tr>
                                <td>USR005</td>
                                <td>Citra Kirana</td>
                                <td>citrakrn@email.com</td>
                                <td><span class="level-badge badge-biasa">Pembaca</span></td>
                                <td>1 bulan lalu</td>
                                <td class="action-buttons">
                                    <button class="btn-warn" onclick="alertModal('Simulasi: Peringatan dikirim ke USR005.', 'Aksi Peringatan')">Peringatkan</button>
                                    <button class="btn-block" onclick="alertModal('Simulasi: USR005 diblokir.', 'Aksi Blokir')">Blokir</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    
    <!-- OVERLAY (untuk menutup sidebar saat diklik di luar) -->
    <div class="overlay" id="sidebarOverlay"></div>

    <script>
        // JavaScript untuk mengontrol toggle sidebar dan modal kustom
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
            
            // Tutup sidebar jika layar diubah dari mobile ke desktop
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove('is-open');
                    overlay.classList.remove('is-active');
                    toggleButton.setAttribute('aria-expanded', false);
                }
            });

            /**
             * Creates and shows a custom modal instead of using window.alert()
             * @param {string} message 
             * @param {string} title .
             */
            window.alertModal = function(message, title = 'Notifikasi') {
                let modal = document.getElementById('custom-alert-modal');
                
                // 1. Create modal structure if it doesn't exist
                if (!modal) {
                    modal = document.createElement('div');
                    modal.id = 'custom-alert-modal';
                    // Tailwind classes for fixed overlay, centered, hidden initially
                    modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50 transition-opacity duration-300 opacity-0 pointer-events-none';
                    modal.innerHTML = `
                        <div class="bg-white rounded-xl shadow-2xl p-6 w-11/12 max-w-sm transform scale-95 transition-transform duration-300">
                            <h3 class="text-xl font-bold text-gray-800 mb-4" id="modal-title"></h3>
                            <p class="text-gray-600 mb-6" id="modal-message"></p>
                            <div class="flex justify-end">
                                <button id="modal-ok-btn" class="bg-sejiwa-dark text-white font-semibold py-2 px-4 rounded-lg hover:bg-sejiwa-medium transition duration-150">
                                    OK
                                </button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);

                    // Add click handler to close the modal
                    document.getElementById('modal-ok-btn').onclick = () => {
                        modal.classList.remove('opacity-100', 'pointer-events-auto');
                        modal.classList.add('opacity-0', 'pointer-events-none');
                    };
                }

                // 2. Set content
                document.getElementById('modal-title').textContent = title;
                document.getElementById('modal-message').textContent = message;

                // 3. Show modal with animation trigger
                setTimeout(() => {
                    modal.classList.add('opacity-100', 'pointer-events-auto');
                    modal.classList.remove('opacity-0', 'pointer-events-none');
                }, 10);
            }
        });
    </script>
</body>
</html>
