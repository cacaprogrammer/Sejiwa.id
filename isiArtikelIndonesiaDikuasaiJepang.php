<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isi Indonesia Dikuasai Jepang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; background-color: #f7f7f7; }
        header {
            height: 50px;
            background-color: #4a2c18;
            color: #f7f7f7;
            padding: 0 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0; left: 0; width: 100%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .dropdown { position: relative; }
        .dropdown-menu {
            position: absolute; top: 35px; left: -20px;
            background: #724636; border: none; width: 150px;
            padding: 7px; display: none; flex-direction: column;
            border-radius: 8px; gap: 0px;
        }
        .dropdown-inner {
            background: #ffffff; border-radius: 8px;
            border: 2px solid #724636; padding: 2px 0; overflow: hidden;
        }
        .dropdown-inner a { color: #000; padding: 2px 0; font-size: 14px; display: block; text-align: center; }
        .dropdown-separator { height: 8px; background: #724636; margin: 0; }
        .logo { display: flex; align-items: center; }
        .logo-img { height: 40px; width: auto; }
        .logo-text-img { height: 70px; width: auto; margin-left: -12px; position: relative; top: -2px; }
        nav ul { display: flex; list-style: none; padding: 0; }
        nav ul li { margin-left: 20px; }
        nav ul li a { text-decoration: none; color: inherit; font-size: 0.95em; font-weight: bold; transition: 0.3s; }
        nav ul li a:hover { color: #6d4c41; }
        .user-icon i { font-size: 1.6em; cursor: pointer; margin-right: 15px; }
        .user-icon a { color: #ffffff; }
        .user-icon i:hover { color: #6d4c41; }
        .hamburger-menu { display: none; background: none; border: none; color: #f7f7f7; font-size: 1.5em; cursor: pointer; padding: 5px; margin-right: 10px; }
        .sidebar {
            height: 100%; width: 0; position: fixed; z-index: 1001;
            top: 0; right: 0; background-color: #4a2c18;
            overflow-x: hidden; transition: 0.3s; padding-top: 60px;
            box-shadow: -5px 0 15px rgba(0,0,0,0.4);
        }
        .sidebar a { padding: 15px 25px; text-decoration: none; font-size: 18px; color: #f7f7f7; display: block; transition: 0.3s; }
        .sidebar a:hover { background-color: #6d4c41; }
        .sidebar .close-btn { position: absolute; top: 0; right: 15px; font-size: 36px; color: #f7f7f7; border: none; background: none; cursor: pointer; }
        .sidebar-user-icon { padding: 20px 25px; border-bottom: 1px solid #6d4c41; margin-bottom: 10px; }
        .sidebar-user-icon a { font-size: 20px; font-weight: bold; }
        .sidebar-user-icon i { margin-right: 10px; }
        .dropdown-menu-sidebar { display: none; background-color: #724636; padding: 5px 0; }
        .dropdown-menu-sidebar .dropdown-inner { background: #ffffff; border-radius: 8px; border: 2px solid #724636; padding: 2px 0; margin: 5px 15px; }
        .dropdown-menu-sidebar .dropdown-inner a { color: #000; padding: 5px 10px; font-size: 14px; text-align: center; }
        .dropdown-menu-sidebar .dropdown-separator { height: 5px; background: #724636; margin: 0 15px; }

        .content-container {
            max-width: 1400px; margin: 30px auto; padding: 25px;
            background-color: #fff; box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 10px; position: relative; top: -30px;
        }
        .article-title-bar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
        .main-title { font-size: 40px; font-weight: 700; color: #000; line-height: 1; margin: 0; padding-bottom: 35px; position: relative; top: 40px; }
        .site-name-right { color: #000; font-weight: 600; font-size: 16px; line-height: 1; bottom: -10px; position: relative; left: -5px; }
        .separator { border: 0; height: 3px; background-color: #000; margin-top: 5px; margin-bottom: 20px; }
        .article-content { display: flex; gap: 30px; }
        .left-column { flex-basis: 300px; flex-shrink: 0; display: flex; flex-direction: column; gap: 15px; }
        .article-card-wrapper {
            border: 1px solid #ddd; padding: 15px; background-color: #fff;
            border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex; flex-direction: column; gap: 10px;
            width: 280px; max-height: 580px; position: relative;
        }
        .image-box { border: none; padding: 0; background-color: transparent; border-radius: 6px; overflow: hidden; box-shadow: none; position: relative; flex-grow: 1; }
        .main-article-image { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; filter: brightness(0.8); }
        .article-title-card {
            background-color: #DAC6BB; padding: 10px 15px; text-align: center;
            font-weight: 700; font-size: 16px; border-radius: 6px; color: #000;
            cursor: pointer; transition: background-color 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 42px;
        }
        .article-title-card:hover { background-color: #e5e5e5; }
        .article-icon-overlay { width: 28px !important; height: auto !important; position: absolute; bottom: 10px; right: 10px; z-index: 5; }
        .rating-box {
            background-color: #4A2C18; color: white; padding: 10px; text-align: center;
            font-weight: bold; cursor: pointer; border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex;
            justify-content: center; align-items: center; gap: 8px;
            width: 249px; position: relative; left: 14px;
        }
        .rating-box a { color: #fff; text-decoration: none; }
        .rating-icon { width: 20px; height: 20px; object-fit: contain; position: relative; top: 5px; }
        .right-column-description { flex-grow: 1; height: 600px; overflow-y: scroll; padding-right: 20px; font-size: 16px; text-align: justify; }
        .right-column-description p { margin-bottom: 20px; }

        @media (max-width: 1024px) {
            nav, .user-icon { display: none; }
            .hamburger-menu { display: block; }
        }
        @media (max-width: 768px) {
            .article-content { flex-direction: column; gap: 20px; }
            .left-column { flex-basis: auto; }
            .right-column-description { height: auto; overflow-y: visible; padding-right: 0; }
            .content-container { padding: 15px; }
            .main-title { font-size: 36px; }
        }
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
                        <div class="dropdown-inner"><a href="daftarsearchSejarah.php">Sejarah</a></div>
                        <div class="dropdown-separator"></div>
                        <div class="dropdown-inner"><a href="daftarsearchBiografi.php">Biografi Tokoh</a></div>
                    </div>
                </li>
                <li><a href="favorit.php">Favorit</a></li>
                <li><a href="rating.php">Ulasan</a></li>
            </ul>
        </nav>

        <div class="user-icon">
            <a href="history.php"><i class="fas fa-user-circle"></i></a>
        </div>

        <div class="sidebar" id="sidebar">
            <button class="close-btn" id="close-sidebar-btn">&times;</button>
            <div class="sidebar-content">
                <div class="sidebar-user-icon">
                    <a href="loginpage.php" onclick="closeSidebar()">
                        <i class="fas fa-user-circle"></i> Profil Pengguna
                    </a>
                </div>
                <a href="landingpagepilihanfix.php" onclick="closeSidebar()">Beranda</a>
                <div class="dropdown-sidebar">
                    <a href="#" onclick="toggleDropdownSidebar(event)">Artikel ▾</a>
                    <div class="dropdown-menu-sidebar" id="dropdown-menu-sidebar">
                        <div class="dropdown-inner"><a href="daftarArtikel.php" onclick="closeSidebar()">Sejarah</a></div>
                        <div class="dropdown-separator"></div>
                        <div class="dropdown-inner"><a href="daftarsearchBiografi.php" onclick="closeSidebar()">Biografi Tokoh</a></div>
                    </div>
                </div>
                <a href="favorit.php" onclick="closeSidebar()">Favorit</a>
                <a href="rating.php" onclick="closeSidebar()">Ulasan</a>
            </div>
        </div>
    </header>

    <div class="content-container">
        <div class="article-title-bar">
            <h2 class="main-title">SEJARAH</h2>
            <span class="site-name-right">Wikipedia.com</span>
        </div>
        <hr class="separator">

        <div class="article-content">
            <!-- Kolom Kiri -->
            <div class="left-column">
                <div class="article-card-wrapper">
                    <div class="image-box">
                        <img src="jepang.jpeg" alt="Indonesia Dikuasai Jepang" class="main-article-image">
                    </div>
                    <div class="article-title-card">Indonesia Dikuasai Jepang</div>
                    <a href="favorit.php">
                        <img src="material-symbols_bookmark-outline.png" alt="icon bookmark" class="article-icon-overlay">
                    </a>
                </div>
                <div class="rating-box">
                    <span>Rating & Ulasan</span>
                    <a href="rating.php">
                        <img src="mdi_read-more-outline.png" alt="Ikon Plus Tambah" class="rating-icon">
                    </a>
                </div>
            </div>

            <!-- Kolom Kanan: Isi Artikel -->
            <div class="right-column-description">
                <p>Pendudukan Jepang di Indonesia berlangsung dari Maret 1942 hingga September 1945, ketika Kekaisaran Jepang menguasai wilayah bekas Hindia Belanda selama Perang Dunia II. Invasi Jepang ke Indonesia dimulai pada 10 Januari 1942, dan dalam waktu kurang dari tiga bulan seluruh wilayah berhasil dikuasai. Belanda resmi menyerah tanpa syarat kepada Jepang pada 9 Maret 1942 di Kalijati, Subang, menandai berakhirnya kekuasaan kolonial Belanda dan dimulainya era pendudukan Jepang di Indonesia.</p>
                <p>Pada awalnya, sebagian besar rakyat Indonesia menyambut kedatangan Jepang dengan penuh harapan dan semangat. Jepang dipandang sebagai pembebas dari penjajahan Belanda yang telah berlangsung selama ratusan tahun. Seperti yang ditulis sastrawan Pramoedya Ananta Toer, "Dengan kedatangan Jepang, hampir semua orang penuh harapan, kecuali mereka yang pernah bekerja untuk melayani Belanda." Rakyat menyambut tentara Jepang sambil mengibarkan bendera dan meneriakkan "Jepang adalah kakak kita" dan "banzai Dai Nippon."</p>
                <p>Jepang membagi Indonesia menjadi tiga wilayah pemerintahan militer yang terpisah. Jawa dan Madura berada di bawah Angkatan Darat ke-16 yang bermarkas di Jakarta, Sumatera di bawah Angkatan Darat ke-25 yang bermarkas di Bukittinggi, sementara Kalimantan dan Indonesia bagian timur dikuasai oleh Armada Selatan ke-2 Angkatan Laut Kekaisaran Jepang yang berpusat di Makassar. Para pejabat Belanda digantikan oleh administrator Jepang maupun Indonesia yang bersedia bekerja sama dengan penguasa militer Jepang.</p>
                <p>Salah satu kebijakan paling kejam selama pendudukan Jepang adalah sistem kerja paksa yang dikenal sebagai romusha. Sekitar 4 hingga 10 juta orang Indonesia dipaksa bekerja sebagai buruh kasar pada proyek pembangunan ekonomi dan pertahanan Jepang di seluruh Asia. Antara 200.000 hingga 500.000 orang dikirim paksa dari Jawa ke pulau-pulau lain bahkan hingga ke Burma dan Siam. Dari mereka yang dikirim keluar Jawa, tidak lebih dari 70.000 orang yang selamat. Laporan PBB menyebutkan bahwa sekitar 4 juta orang Indonesia tewas akibat kelaparan dan kerja paksa selama pendudukan Jepang.</p>
                <p>Kehidupan sosial dan budaya rakyat Indonesia selama pendudukan Jepang sangat memprihatinkan. Semua kegiatan rakyat dicurahkan untuk memenuhi kebutuhan perang Jepang. Jepang mewajibkan sikap Seikerei, yaitu membungkuk 90 derajat ke arah matahari terbit sebagai penghormatan kepada Kaisar Jepang, yang mendapat penolakan keras dari kalangan ulama Islam karena dianggap menyekutukan Tuhan. Jepang juga mengganti nama-nama kota menjadi bahasa Indonesia, seperti Batavia menjadi Jakarta dan Buitenzorg menjadi Bogor, serta melarang penggunaan bendera merah-putih-biru Belanda.</p>
                <p>Meskipun di bawah pendudukan yang ketat, rakyat Indonesia tidak tinggal diam. Berbagai perlawanan fisik meletus di berbagai daerah. Di Aceh, Tengku Abdul Jalil memimpin perlawanan pada November 1942 yang berhasil memukul mundur pasukan Jepang dua kali sebelum akhirnya ditumpas. Di Singaparna, KH. Zainal Mustafa memimpin santri-santrinya menolak Seikerei pada 1943 hingga akhirnya ditangkap dan dihukum mati di Ancol. Perlawanan terbesar terjadi di Blitar pada 14 Februari 1945, ketika pasukan PETA di bawah pimpinan Syodanco Supriyadi menyerang gudang senjata Jepang sebagai bentuk protes atas perlakuan buruk terhadap rakyat.</p>
                <p>Meskipun bertujuan mendukung kepentingan perang Jepang, sejumlah organisasi yang dibentuk Jepang justru menjadi bekal perjuangan kemerdekaan Indonesia. Pada 3 Oktober 1943, Jepang membentuk PETA (Pembela Tanah Air) di Jawa sebagai angkatan bersenjata lokal. Hingga pertengahan 1945, PETA memiliki sekitar 120.000 pejuang yang kemudian menjadi inti Angkatan Bersenjata Indonesia. Selain PETA, dibentuk pula Heiho sebagai barisan cadangan prajurit, Seinendan sebagai barisan pemuda, Fujinkai sebagai barisan wanita, dan Keibodan sebagai barisan pembantu polisi.</p>
                <p>Soekarno dan Hatta memilih strategi pura-pura bekerja sama dengan Jepang demi meraih kemerdekaan Indonesia. Jepang memanfaatkan pengaruh besar Soekarno untuk memobilisasi rakyat mendukung kepentingan perang mereka, sementara Soekarno diam-diam mempersiapkan kemerdekaan. Pada Maret 1943, Jepang membentuk Putera (Pusat Tenaga Rakyat) dengan Soekarno sebagai ketuanya. Pada 1 Maret 1945, Jepang membentuk BPUPKI (Badan Penyelidik Usaha Persiapan Kemerdekaan Indonesia) yang anggotanya antara lain Soekarno, Hatta, dan Wahid Hasyim untuk mempersiapkan kemerdekaan Indonesia.</p>
                <p>Setelah bom atom dijatuhkan di Hiroshima dan Nagasaki pada 6 dan 9 Agustus 1945, Jepang menyerah kepada Sekutu pada 15 Agustus 1945. Para pemuda pejuang yang tidak sabar kemudian menculik Soekarno dan Hatta pada 16 Agustus 1945 dan membawa mereka ke Rengasdengklok untuk meyakinkan bahwa Jepang telah menyerah dan saatnya memproklamasikan kemerdekaan. Malam harinya Soekarno dan Hatta kembali ke Jakarta, dan keesokan paginya pada 17 Agustus 1945, teks Proklamasi Kemerdekaan Indonesia dibacakan oleh Soekarno, menandai berakhirnya pendudukan Jepang dan lahirnya bangsa Indonesia yang merdeka.</p>
                <p>Meskipun masa pendudukan Jepang penuh dengan penderitaan dan kekejaman, peristiwa ini meninggalkan dampak yang sangat besar bagi Indonesia. Di satu sisi, Jepang memfasilitasi politisasi rakyat Indonesia hingga ke tingkat desa dan membantu menghancurkan kekuatan kolonial Belanda. Di sisi lain, Jepang mendidik, melatih, dan mempersenjatai banyak pemuda Indonesia yang kemudian menjadi tulang punggung perjuangan kemerdekaan. Pendudukan Jepang juga mendorong berkembangnya bahasa Indonesia sebagai bahasa nasional dan memperkuat semangat nasionalisme yang menjadi pendorong utama kemerdekaan Indonesia pada 17 Agustus 1945.</p>
            </div>
        </div>
    </div>

    <script>
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

        function toggleDropdown(event) {
            event.stopPropagation();
            const menu = document.getElementById('dropdown-menu');
            menu.style.display = (menu.style.display === "flex") ? "none" : "flex";
        }

        document.addEventListener("click", function(e) {
            const menu = document.getElementById('dropdown-menu');
            const dropdown = document.querySelector(".dropdown");
            if (!dropdown.contains(e.target)) menu.style.display = "none";
        });

        window.addEventListener('resize', () => {
            const nav = document.querySelector('.nav-links');
            if (nav && window.innerWidth > 768) nav.classList.remove('active');
        });
    </script>
</body>
</html>