<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Biografi R.A Kartini</title>

  <!-- Font untuk konten -->
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

    /* === DROPDOWN FIX === */
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

    /* === CARD KIRI === */
    .left-card {
      width: 320px;
      background: #ffffff;
      border-radius: 12px;
      padding: 22px;
      box-shadow: 0 3px 14px rgba(0,0,0,0.12);
      border: 1px solid #e6e6e6;
      position: relative;
    }

    /* === IKON FAVORIT DI DALAM CARD === */
    .favorite-icon {
      position: absolute;
      bottom: 5px;
      right: 10px;
      width: 30px;
      cursor: pointer;
      z-index: 50;
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

<div class="container">

  <h2>BIOGRAFI</h2>
  <span class="site-name-right">Wikipedia.com</span>
  <hr>

  <div class="content">

    <div class="left-card">
      <div class="person-name">R.A Kartini</div>
      <img src="kr.jpg" class="photo">
      <div class="caption">Waktu Potret Tidak Diketahui</div>

      <div class="info-title">Info Pribadi</div>

      <table>
        <tr><td>Lahir</td><td>21 April 1879</td></tr>
        <tr><td>Meninggal</td><td>17 September 1904 (umur 25)</td></tr>
        <tr><td>Pekerjaan</td><td>Penulis & Pengirim Surat (Korespondensi), Pendidik / Perintis Sekolah Wanita</td></tr>
      </table>
      <!-- Ikon Favorit -->
      <a href="favorit.php">
        <img src="material-symbols_bookmark-outline.png" class="favorite-icon">
      </a>
    </div>

    <div class="right">
      <h2>R.A Kartini</h2>
      <p>Raden Ajeng Kartini berasal dari kalangan priyayi atau kelas bangsawan Jawa. Ia merupakan putri dari Raden Mas Adipati Aryo Sosroningrat, seorang patih yang diangkat menjadi bupati Jepara segera setelah Kartini lahir. Kartini adalah putri dari istri pertama, tetapi bukan istri utama (Garwa Ampil). Ibunya bernama Mas Ajeng Ngasirah, putri dari Nyai Haji Siti Aminah dan Kyai Haji Madirono, seorang guru agama di Telukawur, Jepara. Kakek Kartini dari pihak ayah, Kanjeng Pangeran Aryo Tjondronegoro Adiningrat IV, menikah dengan Gusti Kangjeng Ratu Hayu, putri ke-10 dari Sultan Hamengkubuwana VI. Sang nenek juga secara kebetulan adalah saudara seibu dengan Sultan Hamengkubuwana VII dari pernikahan dengan permaisuri Gusti Kangjeng Ratu Sultan atau Gusti Kangjeng Ratu Hageng. Garis keturunan Bupati Sosroningrat bahkan dapat ditilik kembali ke istana Kerajaan Majapahit. Semenjak Pangeran Dangirin menjadi bupati Surabaya pada abad ke-18, nenek moyang Sosroningrat mengisi banyak posisi penting di Pangreh Praja.</p> <br> <p>Ayah Kartini pada mulanya adalah seorang wedana di Mayong. Peraturan kolonial waktu itu mengharuskan seorang bupati beristerikan seorang bangsawan. Karena Mas Ajeng Ngasirah bukanlah bangsawan tinggi, maka ayahnya menikah lagi dengan Raden Ajeng atau Raden Ayu Woerjan (Moerjam), keturunan langsung Raja Madura. Setelah perkawinan itu, ayah Kartini diangkat menjadi bupati di Jepara menggantikan kedudukan ayah kandung R.A. Woerjan, R.A.A. Tjitrowikromo.</p> <br> <p>Kartini adalah anak ke-5 dari 11 bersaudara kandung dan tiri. Dari semua saudara sekandung, Kartini adalah anak perempuan tertua. Kakeknya, Kanjeng Pangeran Aryo Tjondronegoro Adiningrat IV, diangkat menjadi bupati Demak dalam usia 25 tahun dan dikenal pada pertengahan abad ke-19 sebagai salah satu bupati pertama yang memberi pendidikan Barat kepada anak-anaknya. Kakak Kartini, R.M. Sosrokartono, adalah seorang yang pintar dalam bidang bahasa. Sampai usia 12 tahun, Kartini diperbolehkan bersekolah di Europeesche Lagere School (ELS). Di sini Kartini belajar bahasa Belanda. Namun, setelah usia 12 tahun, ia harus tinggal di rumah karena harus dipingit. Karena Kartini bisa berbahasa Belanda, di rumah ia mulai belajar sendiri dan menulis surat kepada teman-teman korespondensi yang berasal dari Belanda. Salah satunya adalah Rosa Abendanon yang banyak mendukungnya. Dari buku-buku, koran, dan majalah Eropa, Kartini tertarik pada kemajuan berpikir perempuan Eropa. Timbul keinginannya untuk memajukan perempuan pribumi karena ia melihat bahwa perempuan pribumi berada pada status sosial yang rendah.</p> <br> <p>Kartini banyak membaca surat kabar Semarang De Locomotief yang diasuh Pieter Brooshooft. Ia juga menerima leestrommel (paket majalah yang diedarkan toko buku kepada langganan). Di antaranya terdapat majalah kebudayaan dan ilmu pengetahuan yang cukup berat, juga ada majalah wanita Belanda De Hollandsche Lelie. Kartini pun kemudian beberapa kali mengirimkan tulisannya dan dimuat di De Hollandsche Lelie. Dari surat-suratnya tampak Kartini membaca apa saja dengan penuh perhatian sambil membuat catatan-catatan. Kadang-kadang Kartini menyebut salah satu karangan atau mengutip beberapa kalimat. Perhatiannya tidak hanya semata-mata soal emansipasi wanita, tetapi juga masalah sosial umum. Kartini melihat perjuangan wanita agar memperoleh kebebasan, otonomi dan persamaan hukum sebagai bagian dari gerakan yang lebih luas. Di antara buku yang dibaca Kartini sebelum berumur 20, terdapat judul Max Havelaar dan Surat-Surat Cinta karya Multatuli, yang pada November 1901 sudah dibacanya dua kali. Selain itu, Kartini juga membaca De Stille Kracht (Kekuatan Gaib) karya Louis Couperus dan karya Van Eeden yang bermutu tinggi, karya Augusta de Witt yang sedang-sedang saja, roman-feminis karya Nyonya Goekoop de-Jong Van Beek dan sebuah roman anti-perang karangan Berta Von Suttner, Die Waffen Nieder (Letakkan Senjata). Semuanya berbahasa Belanda.</p> <br> <p>Oleh orang tuanya, Kartini dijodohkan dengan bupati Rembang, K.R.M. Adipati Aryo Singgih Djojoadiningrat, yang sudah pernah memiliki tiga istri. Kartini menikah pada tanggal 12 November 1903. Suaminya mengerti keinginan Kartini dan Kartini diberi kebebasan serta didukung mendirikan sekolah wanita di sebelah timur pintu gerbang kompleks kantor kabupaten Rembang atau di sebuah bangunan yang kini digunakan sebagai Gedung Pramuka. Anak satu-satunya, R.M. Soesalit Djojoadhiningrat, lahir pada tanggal 13 September 1904. Beberapa hari kemudian, 17 September 1904, Kartini meninggal pada usia 25 tahun. Kartini dimakamkan di Desa Bulu, Kecamatan Bulu, Rembang.</p> <br> <p>Berkat kegigihan Kartini, belakangan didirikan Sekolah Wanita oleh Yayasan Kartini di Semarang pada 1912, dan kemudian di Surabaya, Yogyakarta, Malang, Madiun, Cirebon, dan daerah lainnya. Nama sekolah tersebut adalah "Sekolah Kartini". Yayasan Kartini ini didirikan oleh keluarga Van Deventer, seorang tokoh Politik Etis.</p> <br> <p>Meski tidak sempat berbuat banyak untuk kemajuan bangsa dan tanah air, Kartini mengemukakan ide-ide pembaruan masyarakat yang melampaui zamannya melalui surat-suratnya yang bersejarah.</p> <br> <p>Cita-citanya yang tinggi dituangkan dalam surat-suratnya kepada kenalan dan sahabatnya orang Belanda di luar negeri, seperti Tuan EC Abendanon, Ny MCE Ovink-Soer, Zeehandelaar, Prof Dr GK Anton, dan Ny Tuan HH von Kol, serta Ny HG de Booij-Boissevain. Surat-surat Kartini diterbitkan di negeri Belanda pada 1911 oleh Mr JH Abendanon dengan judul Door Duisternis tot Licht. Diterjemahkan ke bahasa Indonesia oleh sastrawan pujangga baru Armijn Pane pada 1922 dengan judul Habis Gelap Terbitlah Terang.</p>

    </div>

  </div>
</div>

<script>
  function toggleDropdown() {
    const menu = document.getElementById('dropdown-menu');
    menu.style.display = (menu.style.display === "flex") ? "none" : "flex";
  }

  document.addEventListener("click", function (e) {
    const menu = document.getElementById('dropdown-menu');
    const dropdown = document.querySelector(".dropdown");

    if (!dropdown.contains(e.target)) {
      menu.style.display = "none";
    }
  });
</script>

</body>
</html>
