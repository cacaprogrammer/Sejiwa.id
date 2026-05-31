<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Biografi Soetomo</title>

  <!-- Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: #f4f4f4;
      font-family: 'Poppins', sans-serif;
    }

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
      width: 100%;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      z-index: 1000;
      font-family: Arial, sans-serif;
    }

    .logo {
      display: flex;
      align-items: center;
    }

    .logo-img {
      height: 40px;
      width: auto;
    }

    .logo-text-img {
      height: 70px;
      width: auto;
      margin-left: -12px;
      position: relative;
      top: -2px;
    }

    nav ul {
      display: flex;
      list-style: none;
    }

    nav ul li {
      margin-left: 20px;
      position: relative;
    }

    nav ul li a {
      text-decoration: none;
      color: inherit;
      font-size: 0.95em;
      font-weight: bold;
      transition: .3s;
    }

    nav ul li a:hover {
      color: #d7b8a7;
    }

    /* DROPDOWN */
    .dropdown {
      position: relative;
    }

    .dropdown-menu {
      position: absolute;
      top: 35px;
      left: -20px;
      background: #724636;
      border: none;
      width: 150px;
      padding: 7px;
      display: none;
      flex-direction: column;
      border-radius: 8px;
      gap: 0px;
    }

    .dropdown-inner {
      background: #ffffff;
      border-radius: 8px;
      border: 2px solid #724636;
      padding: 6px 0;
      overflow: hidden;
    }

    .dropdown-inner a {
      color: #000;
      padding: 2px 0;
      font-size: 14px;
      display: block;
      text-align: center;
    }

    .dropdown-separator {
      height: 8px;
      background: #724636;
      margin: 0;
    }

    .user-icon i {
      font-size: 1.6em;
      cursor: pointer;
      margin-right: 15px;
    }

    .user-icon a {
      color: #ffffff;
    }

    .container {
      width: 1230px;
      background: #ffffff;
      padding: 40px 55px;
      border-radius: 14px;
      box-shadow: 0 4px 30px rgba(0,0,0,0.08);
      margin: 0 auto;
    }

    h2 {
      margin-bottom: 12px;
      font-size: 28px;
      font-weight: 700;
    }

    hr {
      margin-bottom: 28px;
      border: 0;
      border-top: 3px solid #000;
    }

    .content {
      display: flex;
      gap: 50px;
    }

    /* CARD */
    .left-card {
      width: 320px;
      background: #ffffff;
      border-radius: 12px;
      padding: 22px;
      box-shadow: 0 3px 14px rgba(0,0,0,0.12);
      border: 1px solid #e6e6e6;
      position: relative;
    }

    .person-name {
      text-align: center;
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 15px;
    }

    .photo {
      width: 100%;
      border-radius: 8px;
      box-shadow: 0 0 6px rgba(0,0,0,0.1);
    }

    .caption {
      text-align: center;
      margin-top: 8px;
      font-size: 12px;
      color: #777;
    }

    .info-title {
      background: #e8c9c0;
      padding: 10px;
      border-radius: 6px;
      text-align: center;
      font-weight: 600;
      margin-top: 18px;
      margin-bottom: 10px;
    }

    table {
      width: 100%;
      margin-top: 5px;
    }

    td {
      padding: 6px 0;
      font-size: 14px;
    }

    td:first-child {
      width: 35%;
      font-weight: 600;
    }

    /* BOOKMARK */
    .btn-bookmark {
      position: absolute;
      bottom: 10px;
      right: 10px;
      z-index: 5;
      background: none;
      border: none;
      cursor: pointer;
      padding: 0;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform 0.2s;
    }

    .btn-bookmark:hover {
      transform: scale(1.2);
    }

    .btn-bookmark .fa-bookmark {
      font-size: 1.5em;
      color: #4a2c18;
      transition: color 0.2s;
    }

    .btn-bookmark.active .fa-bookmark {
      color: #e67e22;
    }

    /* TOAST */
    .toast {
      position: fixed;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%) translateY(20px);
      background-color: #4a2c18;
      color: #fff;
      padding: 10px 22px;
      border-radius: 30px;
      font-size: 0.9em;
      font-weight: bold;
      opacity: 0;
      transition: opacity 0.3s, transform 0.3s;
      z-index: 9999;
      pointer-events: none;
    }

    .toast.show {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }

    .right {
      width: 70%;
      height: 580px;
      overflow-y: auto;
      padding-right: 14px;
    }

    .right::-webkit-scrollbar {
      width: 7px;
    }

    .right::-webkit-scrollbar-thumb {
      background: #c9c9c9;
      border-radius: 8px;
    }

    .site-name-right {
      float: right;
      font-weight: 600;
      font-size: 16px;
      margin-top: -30px;
    }

    p {
      margin-bottom: 20px;
      line-height: 1.6;
    }
  </style>
</head>

<body>

<header>
  <div class="logo">
    <img src="logobenar.png" class="logo-img">
    <img src="sejput.png" class="logo-text-img">
  </div>

  <nav>
    <ul>
      <li><a href="landingpagepilihanfix.php">Beranda</a></li>

      <li class="dropdown">
        <a href="javascript:void(0)" onclick="toggleDropdown()">Artikel ▾</a>

        <div class="dropdown-menu" id="dropdown-menu">
          <div class="dropdown-inner">
            <a href="daftarsearchSejarah.php">Sejarah</a>
          </div>

          <div class="dropdown-separator"></div>

          <div class="dropdown-inner">
            <a href="daftarsearchBiografi.php">Biografi Tokoh</a>
          </div>
        </div>
      </li>

      <li><a href="favorit.php">Favorit</a></li>
      <li><a href="rating.php">Ulasan</a></li>
    </ul>
  </nav>

  <div class="user-icon">
    <a href="history.php">
      <i class="fas fa-user-circle"></i>
    </a>
  </div>
</header>

<!-- Toast -->
<div class="toast" id="toast"></div>

<div class="container">

  <h2>BIOGRAFI</h2>
  <span class="site-name-right">detik.com</span>
  <hr>

  <div class="content">

    <div class="left-card">
      <div class="person-name">Soetomo</div>

      <img src="Soetomo.png" class="photo">

      <div class="caption">Waktu Potret Tidak Diketahui</div>

      <div class="info-title">Info Pribadi</div>

      <table>
        <tr><td>Lahir</td><td>30 Juli 1888</td></tr>
        <tr><td>Meninggal</td><td>30 Mei 1938 (umur 49)</td></tr>
        <tr><td>Pekerjaan</td><td>Dokter, profesor</td></tr>
      </table>

      <!-- Tombol Bookmark -->
      <button class="btn-bookmark" id="btn-bookmark" onclick="toggleFavorit()" title="Tambah ke Favorit">
        <i class="fa-bookmark" id="bookmark-icon"></i>
      </button>
    </div>

    <div class="right" id="article-body">

      <h2>Dr. Soetomo</h2>

      <p>
        Dr. Soetomo adalah seorang dokter teladan yang menjadi penggerak Kebangkitan Nasional.
        Sutomo yang mempunyai nama kecil Soebroto, lahir pada 30 Juli 1888 di Desa Ngepah,
        Kabupaten Nganjuk, Jawa Timur.
      </p>

      <p>
        Ia merupakan anak dari Raden Suwaji, seorang bangsawan yang menjabat sebagai wedana
        atau camat di Maospati, Madiun.
      </p>

      <p>
        Dilahirkan di kalangan keluarga berada dan terpandang, Sutomo kecil mulai mengenyam
        pendidikan formal di Europeesche Lagere School (ELS).
      </p>

      <p>
        Di sekolah, Sutomo termasuk siswa yang pandai sehingga disegani oleh teman-temannya,
        baik anak Indonesia maupun anak-anak Belanda.
      </p>

      <p>
        Bahkan guru-guru Belanda juga menyayanginya.
      </p>

      <p>
        Setelah menyelesaikan pendidikan dasar, Soetomo melanjutkan pendidikan kedokteran di
        School tot Opleiding van Inlandsche Artsen (STOVIA) di Batavia.
      </p>

      <p>
        Di STOVIA, Soetomo aktif berdiskusi dengan para pelajar pribumi mengenai nasib bangsa
        Indonesia yang saat itu masih dijajah Belanda.
      </p>

      <p>
        Pada tahun 1908, Soetomo bersama rekan-rekannya mendirikan organisasi Budi Utomo,
        organisasi modern pertama di Indonesia yang bertujuan meningkatkan pendidikan dan
        kesejahteraan rakyat.
      </p>

      <p>
        Berdirinya Budi Utomo menjadi awal Kebangkitan Nasional Indonesia dan diperingati
        setiap tanggal 20 Mei sebagai Hari Kebangkitan Nasional.
      </p>

      <p>
        Setelah lulus sebagai dokter, Soetomo bekerja di berbagai daerah sambil terus aktif
        dalam kegiatan sosial dan pendidikan masyarakat.
      </p>

      <p>
        Ia dikenal sebagai sosok yang sederhana, cerdas, dan memiliki kepedulian tinggi terhadap rakyat kecil.
      </p>

      <p>
        Soetomo wafat pada 30 Mei 1938 di Surabaya dan dikenang sebagai salah satu tokoh penting
        dalam sejarah perjuangan bangsa Indonesia.
      </p>

    </div>

  </div>
</div>

<script>
  // DATA ARTIKEL
  const ARTIKEL_DATA = {
    id: 'soetomo',
    judul: 'Soetomo',
    gambar: 'Soetomo.png',
    link: 'isibiografiSoetomo.php'
  };

  const FAVORIT_KEY = 'sejiwa_favorit';

  // DROPDOWN
  function toggleDropdown() {
    const menu = document.getElementById('dropdown-menu');
    menu.style.display = (menu.style.display === "flex") ? "none" : "flex";
  }

  document.addEventListener("click", function (e) {
    const menu = document.getElementById('dropdown-menu');
    const dropdown = document.querySelector(".dropdown");

    if (dropdown && !dropdown.contains(e.target)) {
      menu.style.display = "none";
    }
  });

  // TOAST
  function showToast(pesan) {
    const toast = document.getElementById('toast');

    toast.textContent = pesan;
    toast.classList.add('show');

    setTimeout(() => {
      toast.classList.remove('show');
    }, 2500);
  }

  // FAVORIT
  function getFavorit() {
    try {
      return JSON.parse(localStorage.getItem(FAVORIT_KEY)) || [];
    } catch {
      return [];
    }
  }

  function saveFavorit(list) {
    localStorage.setItem(FAVORIT_KEY, JSON.stringify(list));
  }

  function isFavorit() {
    return getFavorit().some(f => f.id === ARTIKEL_DATA.id);
  }

  function updateBookmarkIcon() {
    const btn = document.getElementById('btn-bookmark');
    const icon = document.getElementById('bookmark-icon');

    if (isFavorit()) {
      btn.classList.add('active');
      icon.className = 'fas fa-bookmark';
      btn.title = 'Hapus dari Favorit';
    } else {
      btn.classList.remove('active');
      icon.className = 'far fa-bookmark';
      btn.title = 'Tambah ke Favorit';
    }
  }

  function toggleFavorit() {
    let favorit = getFavorit();

    if (isFavorit()) {
      favorit = favorit.filter(f => f.id !== ARTIKEL_DATA.id);

      saveFavorit(favorit);

      updateBookmarkIcon();

      showToast('Dihapus dari Favorit');
    } else {
      favorit.push({
        ...ARTIKEL_DATA,
        progress: hitungProgress()
      });

      saveFavorit(favorit);

      updateBookmarkIcon();

      showToast('Ditambahkan ke Favorit!');
    }
  }

  // PROGRESS BACA
  function hitungProgress() {
    const el = document.getElementById('article-body');

    if (!el) return 0;

    const scrollHeight = el.scrollHeight - el.clientHeight;

    if (scrollHeight <= 0) return 100;

    return Math.round((el.scrollTop / scrollHeight) * 100);
  }

  function simpanProgress() {
    const favorit = getFavorit();

    const index = favorit.findIndex(f => f.id === ARTIKEL_DATA.id);

    if (index !== -1) {
      favorit[index].progress = hitungProgress();

      saveFavorit(favorit);
    }
  }

  const articleBody = document.getElementById('article-body');

  if (articleBody) {
    articleBody.addEventListener('scroll', simpanProgress);
  }

  // INISIALISASI
  updateBookmarkIcon();
</script>

</body>
</html>