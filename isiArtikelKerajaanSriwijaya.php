<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isi Kerajaan Sriwijaya</title>
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
                        <img src="jy.jpg" alt="Kerajaan Sriwijaya" class="main-article-image">
                    </div>
                    <div class="article-title-card">Kerajaan Sriwijaya</div>
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

                <p>Sriwijaya adalah kedatuan bahari historis yang berasal dari Pulau Sumatra sekitar abad ke-7 sampai abad ke-11. Dalam bahasa Melayu Kuno, "sri" berarti "bercahaya" atau "gemilang", dan "vijaya" berarti "kemenangan" atau "kejayaan", sehingga Sriwijaya bermakna "kemenangan yang gilang-gemilang". Lokasi ibu kota Sriwijaya dapat disimpulkan berada di Kota Palembang, tepatnya di muara Sungai Musi. Sriwijaya terdiri dari sejumlah pelabuhan yang saling berhubungan di sekitar Selat Malaka dan banyak memberi pengaruh pada perkembangan sejarah Asia Tenggara, terutama di kawasan Nusantara bagian barat.</p>

                <p>Bukti awal keberadaan Sriwijaya berasal dari abad ke-7. Seorang pendeta Tiongkok dari Dinasti Tang bernama I Tsing menulis bahwa ia mengunjungi Sriwijaya pada tahun 671 dan tinggal selama enam bulan untuk belajar tata bahasa Sanskerta. Prasasti tertua mengenai Sriwijaya adalah Prasasti Kedukan Bukit di Palembang yang bertarikh 682 Masehi, ditulis dalam bahasa Melayu Kuno dan mendapat pengaruh dari budaya Hindu-Buddha. Prasasti ini mencatat suatu perjalanan suci yang dipimpin oleh Dapunta Hyang Sri Jayanasa, yang dipahami sebagai ekspedisi politik dan keagamaan untuk memperluas wilayah kekuasaan Sriwijaya.</p>

                <p>Setelah sempat terlupakan, keberadaan Sriwijaya baru diketahui kembali lewat publikasi tahun 1918 oleh sejarawan Prancis George Cœdès dari École française d'Extrême-Orient. Tidak ada orang Indonesia modern yang mendengar mengenai Sriwijaya sampai tahun 1920-an, ketika Cœdès mempublikasikan penemuannya dalam surat kabar berbahasa Belanda dan Indonesia. Cœdès menyatakan bahwa referensi Tiongkok terhadap "San-fo-ts'i" dan beberapa prasasti dalam Melayu Kuno merujuk pada kerajaan yang sama, yaitu Sriwijaya. Hingga kini belum banyak bukti fisik mengenai Sriwijaya yang dapat ditemukan, dan masa lalunya sebagian besar dibentuk kembali oleh sarjana asing.</p>

                <p>Sriwijaya tumbuh dan berhasil mengendalikan jalur perdagangan maritim di Selat Malaka, Selat Sunda, Laut Cina Selatan, Laut Jawa, dan Selat Karimata. Di dunia perdagangan, Sriwijaya menjadi pengendali jalur perdagangan antara India dan Tiongkok. Orang Arab mencatat bahwa Sriwijaya memiliki aneka komoditas seperti kapur barus, kayu gaharu, cengkih, pala, kapulaga, gading, emas, dan timah. Pada puncak kejayaannya antara abad ke-7 hingga ke-13, kekuasaan Sriwijaya mencakup wilayah Sumatra, Semenanjung Malaya, Jawa Barat, hingga Kamboja.</p>

                <p>Sriwijaya menjadi pusat pendidikan dan kegiatan pengajaran Buddha Mahayana yang sangat penting di Asia Tenggara. Dalam catatannya, I-Tsing menuliskan bahwa banyak pelajar dari berbagai wilayah Asia datang ke Sriwijaya untuk mempelajari tata bahasa Sanskerta dan Vinaya sebelum melanjutkan perjalanan ke India. Sebagai bukti hubungan internasional Sriwijaya dengan dunia Buddha, Raja Balaputradewa dari Sriwijaya membangun sebuah vihara di kompleks Universitas Nalanda di India pada abad ke-9, yang diperuntukkan bagi para pelajar agama dari Sriwijaya yang belajar di sana. Hal ini menunjukkan dukungan diplomatik dan finansial Sriwijaya terhadap institusi keilmuan Buddha terbesar di India pada masa itu.</p>

                <p>Berdasarkan sumber catatan sejarah dari Arab, Sriwijaya disebut dengan nama Sribuza. Pada tahun 955 Masehi, Al-Masudi, seorang musafir sekaligus sejarawan Arab klasik, menulis catatan tentang Sriwijaya yang menggambarkannya sebagai sebuah kerajaan besar yang kaya raya dengan tentara yang sangat banyak. Disebutkan bahwa kapal yang tercepat pun dalam waktu dua tahun tidak cukup untuk mengelilingi seluruh pulau wilayahnya. Hasil bumi Sriwijaya antara lain kapur barus, kayu gaharu, cengkih, kayu cendana, pala, kapulaga, gambir, dan berbagai komoditas berharga lainnya yang menjadikan Sriwijaya salah satu pusat perdagangan terkaya di Asia.</p>

                <p>Salah satu raja terkemuka Sriwijaya adalah Balaputradewa, Maharaja Sriwijaya ke-11 yang berasal dari Wangsa Sailendra. Namanya dikenal melalui Prasasti Nalanda di India yang mencatat persahabatannya dengan raja India, Dewapaladewa. Pada masa pemerintahannya, Sriwijaya berhasil meraih puncak kejayaan dalam bidang perdagangan maritim dan hubungan internasional. Rakai Pikatan dari Jawa juga berperan penting dalam sejarah Sriwijaya karena berhasil mengalahkan Balaputradewa dalam perebutan kekuasaan di Jawa, yang mendorong Balaputradewa mewarisi takhta Sriwijaya dari ibunya dan memindahkan pusat kekuasaan ke Sumatra.</p>

                <p>Pengaruh Sriwijaya mulai menyusut akibat serangkaian peperangan. Serangan besar pada tahun 1025 dilancarkan oleh pasukan Rajendra Chola I dari Koromandel, India. Serangan ini berhasil menghancurkan beberapa kota penting Sriwijaya dan mengakhiri kekuasaan Wangsa Sailendra atas Sumatra dan Semenanjung Malaya. Invasi kedua kemudian dipimpin oleh Virarajendra Chola yang berhasil menaklukkan Kedah, salah satu wilayah penting Sriwijaya, pada akhir abad ke-11. Kedatangan kekuatan Chola secara perlahan mengurangi keagungan dan dominasi Sriwijaya di kawasan Asia Tenggara.</p>

                <p>Setelah mengalami tekanan dari berbagai arah, Sriwijaya akhirnya mengalami keruntuhan total pada abad ke-13 hingga ke-14. Melemahnya kekuasaan Sriwijaya membuka jalan bagi munculnya kerajaan-kerajaan Islam di Sumatra yang tumbuh dari bekas wilayah Sriwijaya. Pada tahun 1324, pangeran Sriwijaya bernama Sang Nila Utama mendirikan Kerajaan Singapura di Temasek, yang kemudian menjadi cikal bakal Kesultanan Malaka. Kesultanan Malaka akhirnya berhasil menggantikan peran Sriwijaya sebagai entitas politik Melayu yang dominan di kawasan Nusantara.</p>

                <p>Sriwijaya menjadi simbol kebesaran Sumatra dan salah satu kerajaan terbesar Nusantara. Pada abad ke-20, Sriwijaya bersama Majapahit menjadi referensi kaum nasionalis untuk menunjukkan bahwa Indonesia merupakan satu kesatuan negara bahkan sebelum kolonialisme Belanda. Nama Sriwijaya juga diabadikan dalam berbagai hal, antara lain nama maskapai penerbangan Sriwijaya Air, nama stadion kebanggaan Sumatera Selatan, serta nama universitas di Palembang. Setiap tahun Festival Sriwijaya digelar di Palembang sebagai bentuk penghormatan dan pelestarian warisan budaya serta sejarah kejayaan Kerajaan Sriwijaya yang menjadi identitas Sumatera Selatan dan kebanggaan bangsa Indonesia. Sumber: Wikipedia — Sriwijaya (https://id.wikipedia.org/wiki/Kerajaan_Sriwijaya)</p>

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