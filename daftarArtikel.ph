<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Artikel</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reset Default Browser */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box; 
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f7f7f7; 
            margin: 0; 
            padding: 0; 
        }
        
        /* Navbar */
        header {
            height: 50px; 
            background-color: #4a2c18; 
            color: #f7f7f7; 
            padding: 0 15px;
            display: flex;
            justify-content: space-between; 
            align-items: center; 
            position: sticky; 
            top: 0;
            left: 0;
            width: 100%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); 
            z-index: 1000;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo-img{
            height:40px;
            width: auto;
        }
        .logo-text-img{
            height: 70px;
            width: auto;
            margin-left: -12px;
            position: relative;
            top: -2px;
        }
        .logo i {
            margin-right: 8px;
            font-size: 1.4em;
        }
        nav ul {
            display: flex; 
            list-style: none; 
            padding: 0;
        }
        nav ul li {
            margin-left: 20px; 
        }
        nav ul li a {
            text-decoration: none;
            color: inherit;
            font-size: 0.95em; 
            font-weight: bold;
            transition: 0.3s;
        }
        nav ul li a:hover {
            color: #6d4c41;
        }        
        .artikel-link span {
             margin: 0 4px;
        }
        .user-icon i {
            font-size: 1.6em;
            cursor: pointer;
            margin-right: 5px;
        }
        .user-icon i:hover {
            color: #6d4c41;
        }

         
       /*pencarian*/
        .search-container {
            display: flex;
            justify-content: center;
            padding: 20px 0; 
            margin-bottom: 20px; 
        }

        .search-box {
            display: flex;
            align-items: center;
            background-color: #4A2C18; 
            border-radius: 50px; 
            padding: 10px 20px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .search-icon {
            font-size: 1.1em;
            color: #4A2C18; 
            margin-right: 10px;
        }

        .search-input {
            flex-grow: 1;
            border: none;
            background: #4A2C18;
            color: #ffff;
            font-size: 1em;
            outline: none;
            margin-left: 6px;
        }

        .search-input::placeholder {
            color: #d7ccc8;
        }
        
        /*artikel*/
        .article-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr); 
            gap: 30px; 
            padding: 0 5%; 
            margin-bottom: 50px; 
        }

        .card {
            background-color: white;
            padding: 3px;
            border-radius: 8px;
            box-shadow: 10px 10px 10px rgba(0, 0, 0, 0.3); 
        }
        
        .card-title {
            font-size: 1em; 
            font-weight: bold; 
            text-align: left; 
            margin: 0 0 10px 0; 
            padding: 0px 0;
            color: #333; 
            min-height: 20px; 
            position: relative;
            top: 15px;
            left: 15px;

        }

        /*card di dalam card (gambar) */
        .card-image-wrapper {
        width: 100%;
        display: flex; 
        justify-content: center; 
        align-items: center; 
        padding: 10px 0; 
        margin-bottom: 10px; 
        padding-top: 0; 
        position: relative; 
        min-height: 280px; 
        }

        /* gambar asli */
        .card-image {
        width: 70%; 
        height: auto; 
        aspect-ratio: 3 / 4; /* Rasio potret (tinggi 4x, lebar 3x) */
        object-fit: cover; 
        border-radius: 10px; 
        box-shadow: 10px 10px 10px rgba(0, 0, 0, 0.3); 
        display: block; 
}
        
        .card-footer {
            display: flex;
            justify-content: space-between; 
            padding: 10px 15px;
            align-items: center;
        }

        .card-year {
            font-size: 0.9em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            margin-left: 5px; 
        }

        .read-button {
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 160px; /* Lebar tombol, kini dapat diubah */
            margin: 10px auto;
            background-color: #DAC6BB; 
            border: none;
            color: black; 
            font-size: 0.85em; 
            font-weight: bold;
            cursor: pointer;
            padding: 5px 15px; 
            border-radius: 20px;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15); 
            transition: background-color 0.2s;
            position: relative;
            top: -5px;
        }

        .read-button:hover {
            background-color: #d3bba8;
        }

        .read-button-icon {
            width: 12px;
            height: 12px;
            object-fit: contain;
            margin-left: 0px; /* Jarak yang lebih baik antara teks dan ikon */
        }
        
        /* Demo Card */
        .card-demo {
            width: 250px; 
            padding: 15px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin: 50px auto; 
        }
        
        /*footer*/
        .footer {
            background-color: #4a2c18; 
            color: #fff; 
            padding: 40px 20px; 
            font-size: 14px;
        }

        .footer-container {
            max-width: 1200px; 
            margin: 0 auto; 
            display: grid;
            grid-template-columns: 1.2fr 2.5fr 1fr; 
            gap: 20px; 
            align-items: flex-start;
        }
        .footer-kolom {
            padding: 0 10px;
        }
        
        .logo-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .logo-title {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .logo-title img {
            width: 40px; 
            height: 40px;
            margin-right: 8px;
             
        }

        .logo-title h3 {
            color: white;
            margin: 0;
            font-size: 18px;
        }

        .logo-info p {
            margin: 0 0 15px 0;
            line-height: 1.5;
        }

        .sosial-media-wrapper {
            margin-top: 10px;
        }
        
        .sosial-media {
            display: flex;
            margin-bottom: 5px;
        }
        
        .sosial-media a {
            color: #fff; 
            font-size: 24px;
            margin-right: 15px;
            text-decoration: none;
        }
        
        .sosial-media-wrapper span {
            font-size: 14px;
            color: inherit;
        }
        
        .middle-section {
            display: flex;
            flex-direction: column;
        }
        
        .navigasi-horizontal {
            display: flex;
            justify-content: space-between; 
            margin-bottom: 30px; 
            flex-wrap: wrap;
            color: white;
        }
        
        .navigasi-horizontal a {
            color: inherit;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            padding-right: 20px; 
        }
        
        .middle-section h4 {
            color: white;
            margin-top: 0;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 16px;
        }
        
        .kontak .item-kontak {
            margin-bottom: 15px;
        }
        
        .kontak .item-kontak p {
            margin: 2px 0;
        }
        
        .kontak .item-kontak p:first-child {
            font-weight: bold;
            margin-bottom: 2px;
            color: white;
            display: flex;
            align-items: center;
        }
        
        .kontak .item-kontak i {
            margin-right: 8px;
            font-size: 18px;
            color: #fff;
        }
        @media (max-width: 768px) {
            .footer-container {
                grid-template-columns: 1fr; 
                gap: 30px;
            }
            .navigasi-horizontal {
                flex-direction: column;
                margin-bottom: 15px;
            }
            .navigasi-horizontal a {
                margin-bottom: 10px;
                padding-right: 0;
            }
        }

        
        @media (max-width: 1000px) {
            .article-grid {
                grid-template-columns: repeat(2, 1fr); 
            }
        }
        @media (max-width: 500px) {
            .article-grid {
                grid-template-columns: 1fr; 
            }
            .search-box {
                max-width: 90%;
            }
        }

        /*RESPONSIVE UKURAN */
        @media (max-width: 768px) {
            /* Layout berubah jadi 1 kolom */
            .container {
                grid-template-columns: 1fr;
            }

            .left {
                height: 350px;
            }

            /* Foto pindah ke tengah */
            .photo-box {
                top: 55%;
                left: 50%;
                width: 260px;
                transform: translate(-50%, -50%);
            }

            .right {
                padding: 30px 20px;
            }

            h2 {
                font-size: 26px;
            }
        }

        /* RESPONSIVE HP KECIL */
        @media (max-width: 480px) {
            .photo-box {
                width: 220px;
            }

            p {
                font-size: 14px;
            }
        }

        
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="logobenar.png"
            alt="logo PJBL" class="logo-img">
            <img src="sejput.png"
            alt="namaweb text" class="logo-text-img">
        </div>
        <nav>
            <ul>
                <li><a href="#">Beranda</a></li>
                <li class="artikel-link">
                    <a href="#">
                        Artikel 
                    <span class="dropdown-icon">
                        <i class="fas fa-caret-down">
                        </i></span></a></li> 
                <li><a href="#">Favorit</a></li>
                <li><a href="#">Ulasan</a></li>
            </ul>
        </nav>
        <div class="user-icon">
            <i class="fas fa-user-circle"></i>
        </div>
    </header>

    <div class="search-container">
        <form action="#" method="get" class="search-box">
             <img src="Vector (3).png" alt="ikon pencarian">
            <input type="text" placeholder="Pencarian..." name="q" class="search-input">
        </form>
    </div>

    <div class="article-grid">
        
        <div class="card">
            <div class="card-title">Proklamasi Kemerdekaan</div>
            <div class="card-image-wrapper">
                <img src="proklamasi.png" alt="Poster Proklamasi" class="card-image">
            </div>
            <div class="card-footer">
                <span class="card-year">2025</span>
                <a href="#" class="read-button">Baca Sekarang <img src="majesticons_arrow-right.png"> </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">Bandung Lautan Api</div>
            <div class="card-image-wrapper">
                <img src="bandung.jpg" alt="Poster Bandung Lautan Api" class="card-image">
            </div>
            <div class="card-footer">
                <span class="card-year">2025</span>
                <a href="#" class="read-button">Baca Sekarang <img src="majesticons_arrow-right.png"> </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">Kerusuhan Mei </div>
            <div class="card-image-wrapper">
                <img src="mei.jpg" alt="Poster Kerusuhan Mei" class="card-image">
            </div>
            <div class="card-footer">
                <span class="card-year">2025</span>
                <a href="#" class="read-button">Baca Sekarang <img src="majesticons_arrow-right.png"> </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">Prisiwa Malari </div>
            <div class="card-image-wrapper">
                <img src="malari.jpg" alt="Poster Prisiwa Malari" class="card-image">
            </div>
            <div class="card-footer">
                <span class="card-year">2025</span>
                <a href="#" class="read-button">Baca Sekarang <img src="majesticons_arrow-right.png"> </a>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Pertempuran Surabaya</div>
            <div class="card-image-wrapper">
                <img src="sby.jpeg" alt="Poster Pertempuran Surabaya" class="card-image">
            </div>
            <div class="card-footer">
                <span class="card-year">2025</span>
                <a href="#" class="read-button">Baca Sekarang <img src="majesticons_arrow-right.png"> </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">Prisiwa G30S/PKI</div>
            <div class="card-image-wrapper">
                <img src="g30.jpg" alt="Poster Prisiwa G30S/PKI" class="card-image">
            </div>
            <div class="card-footer">
                <span class="card-year">2025</span>
                <a href="#" class="read-button">Baca Sekarang <img src="majesticons_arrow-right.png"> </a>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Sumpah Pemuda</div>
            <div class="card-image-wrapper">
                <img src="sumpah.jpg" alt="Poster Sumpah Pemuda" class="card-image">
            </div>
            <div class="card-footer">
                <span class="card-year">2025</span>
                <a href="#" class="read-button">Baca Sekarang <img src="majesticons_arrow-right.png"> </a>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Indonesia Diduduki Jepang</div>
            <div class="card-image-wrapper">
                <img src="jepang.jpeg" alt="Poster Diduduki Jepang" class="card-image">
            </div>
            <div class="card-footer">
            <span class="card-year">2025</span>
                <a href="#" class="read-button">Baca Sekarang <img src="majesticons_arrow-right.png"> </a>
            </div>
        </div>
        
    </div>

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
                    <a href="#">Beranda</a>
                    <a href="#">Artikel</a>
                    <a href="#">Favorit</a>
                    <a href="#">Ulasan</a>
                </div>

                <div class="tentang-kita-container">
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
</body>
</html>
