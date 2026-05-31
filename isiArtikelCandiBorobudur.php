<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isi Candi Borobudur</title>
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
                        <img src="bd.jpg" alt="Candi Borobudur" class="main-article-image">
                    </div>
                    <div class="article-title-card">Candi Borobudur</div>
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
                <p>Candi Borobudur adalah sebuah candi Buddha yang terletak di Borobudur, Magelang, Jawa Tengah, Indonesia. Candi ini berjarak sekitar 40 km di sebelah barat laut Yogyakarta. Borobudur merupakan candi Buddha terbesar di dunia sekaligus salah satu monumen Buddha terbesar di dunia. Candi ini didirikan oleh para penganut agama Buddha Mahayana sekitar tahun 800-an Masehi pada masa pemerintahan wangsa Syailendra.</p>
                <p>Borobudur terdiri atas enam teras berbentuk bujur sangkar yang di atasnya terdapat tiga pelataran melingkar. Dindingnya dihiasi dengan 2.672 panel relief dan aslinya terdapat 504 arca Buddha. Borobudur memiliki koleksi relief Buddha terlengkap dan terbanyak di dunia. Stupa utama terbesar terletak di tengah memahkotai bangunan ini, dikelilingi oleh tiga barisan melingkar 72 stupa berlubang yang di dalamnya terdapat arca Buddha duduk bersila dalam posisi teratai sempurna.</p>
                <p>Borobudur merupakan model alam semesta dan dibangun sebagai tempat suci untuk memuliakan Buddha sekaligus sebagai tempat ziarah untuk menuntun umat manusia beralih dari alam nafsu duniawi menuju pencerahan. Para peziarah masuk melalui sisi timur dan berjalan melingkari bangunan searah jarum jam sambil naik ke undakan berikutnya melalui tiga tingkatan ranah kosmologi Buddha, yaitu Kamadhatu (ranah hawa nafsu), Rupadhatu (ranah berwujud), dan Arupadhatu (ranah tak berwujud).</p>
                <p>Borobudur diperkirakan dibangun sekitar tahun 800 Masehi pada masa puncak kejayaan wangsa Syailendra yang menguasai tahta Kerajaan Medang di Jawa Tengah. Pembangunan diperkirakan menghabiskan waktu antara 75 hingga 100 tahun dan benar-benar dirampungkan pada masa pemerintahan raja Samaratungga pada tahun 825 Masehi. Sekitar 55.000 meter kubik batu andesit diangkut dari tambang batu untuk membangun monumen ini, dan struktur bangunannya tidak menggunakan semen sama sekali, melainkan sistem saling kunci antar batu.</p>
                <p>Menurut bukti-bukti sejarah, Borobudur ditinggalkan pada sekitar abad ke-10 seiring dipindahnya pusat Kerajaan Mataram Kuno ke Jawa Timur, dan selama berabad-abad tersembunyi terkubur di bawah lapisan tanah dan debu vulkanik. Dunia mulai menyadari kembali keberadaan bangunan ini sejak ditemukan pada tahun 1814 oleh Sir Thomas Stamford Raffles yang saat itu menjabat sebagai Gubernur Jenderal Inggris atas Jawa. Raffles mengutus insinyur H.C. Cornelius bersama 200 bawahannya untuk menebang pepohonan dan membersihkan lapisan tanah yang mengubur candi ini.</p>
                <p>Setelah serangkaian pemugaran kecil oleh pemerintah Hindia Belanda, proyek pemugaran terbesar digelar pada kurun waktu 1975 hingga 1982 atas upaya Pemerintah Republik Indonesia dan UNESCO. Proyek kolosal ini melibatkan 600 orang dan menghabiskan biaya total sebesar 6,9 juta dolar Amerika Serikat. Pondasi diperkukuh, seluruh 1.460 panel relief dibersihkan, dan sistem drainase diperbaiki dengan menanamkan saluran air ke dalam monumen. Setelah pemugaran selesai, UNESCO resmi memasukkan Borobudur ke dalam daftar Situs Warisan Dunia pada tahun 1991.</p>
                <p>Pada 21 Januari 1985, sembilan stupa Borobudur rusak parah akibat serangan sembilan bom yang dilakukan oleh kelompok Islam ekstrem pimpinan Husein Ali Al Habsyi. Pelaku utama dihukum penjara seumur hidup pada 1991, sementara dua anggota kelompoknya dijatuhi hukuman 20 tahun penjara dan seorang lainnya 13 tahun penjara. Stupa-stupa yang rusak kemudian segera diperbaiki kembali sehingga Borobudur bisa kembali dikunjungi oleh masyarakat umum.</p>
                <p>Borobudur sangat terdampak letusan Gunung Merapi pada Oktober dan November 2010. Debu vulkanik dari Merapi menutupi kompleks candi hingga setebal 2,5 sentimeter, mematikan tanaman di sekitarnya, dan mengancam kerusakan batuan karena sifat kimia debu yang asam. Kompleks candi ditutup dari 5 hingga 9 November 2010 untuk pembersihan. UNESCO menyumbangkan dana sebesar 3 juta dolar AS untuk rehabilitasi, dan lebih dari 55.000 blok batu candi harus dibongkar untuk memperbaiki sistem drainase yang tersumbat. Restorasi berakhir pada November 2011, lebih awal dari perkiraan.</p>
                <p>Borobudur kini masih digunakan sebagai tempat ziarah keagamaan yang aktif. Setiap tahun pada saat bulan purnama sekitar bulan Mei atau Juni, umat Buddha dari seluruh Indonesia dan mancanegara berkumpul di Borobudur untuk memperingati Hari Trisuci Waisak dengan prosesi berjalan dari Candi Mendut menuju Candi Pawon dan berakhir di Candi Borobudur. Selain sebagai pusat keagamaan, Borobudur juga merupakan objek wisata tunggal yang paling banyak dikunjungi di Indonesia, dengan jumlah pengunjung yang mencapai 2,5 juta orang per tahun pada pertengahan era 1990-an.</p>
                <p>Pada 11 Februari 2022, pemerintah Indonesia secara resmi meresmikan kembali status Candi Borobudur sebagai tempat peribadatan umat Buddha di Indonesia dan dunia. Sebagaimana peran Angkor Wat bagi Kamboja, Borobudur telah menjadi simbol yang kuat bagi Indonesia sebagai saksi kejayaan masa lalu. Monumen ini tampil dalam uang rupiah, perangko, dan berbagai materi promosi pariwisata Indonesia. Borobudur menjadi ikon nasional yang dikelompokkan bersama wayang dan gamelan sebagai wujud budaya klasik Jawa yang menjadi kebanggaan dan inspirasi bangsa Indonesia.</p>
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