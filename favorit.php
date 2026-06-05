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

$_nb_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tb_notifikasi WHERE user_id = ? AND dibaca = 0");
$_nb_stmt->bind_param("i", $_SESSION['id']);
$_nb_stmt->execute();
$_nb_count = $_nb_stmt->get_result()->fetch_assoc()['total'];

$daftar_kategori = [];
$res_kat = $conn->query("SELECT nama_kategori, slug_kategori FROM tb_kategori ORDER BY nama_kategori ASC");
if ($res_kat) {
    while ($row = $res_kat->fetch_assoc()) {
        $daftar_kategori[] = $row;
    }
}

$uid = (int)$_SESSION['id'];
$favorit_data = [];
$fav_res = $conn->query("
    SELECT a.id, a.judul, a.slug, a.thumbnail, a.kategori,
           COALESCE(AVG(u.rating), 0) AS rata_rating
    FROM tb_favorit f
    JOIN tb_artikel a ON a.id = f.artikel_id
    LEFT JOIN tb_ulasan u ON u.artikel_id = a.id
    WHERE f.user_id = $uid AND a.status = 'published'
    GROUP BY a.id
    ORDER BY f.saved_at DESC
");
if ($fav_res) {
    while ($row = $fav_res->fetch_assoc()) {
        if (!empty($row['thumbnail']) && file_exists(__DIR__ . '/uploads/' . $row['thumbnail'])) {
            $row['thumb_path'] = 'uploads/' . $row['thumbnail'];
        } elseif (!empty($row['thumbnail'])) {
            $row['thumb_path'] = $row['thumbnail'];
        } else {
            $row['thumb_path'] = 'cover1.jpg';
        }
        $favorit_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; background-color: #f7f7f7; overflow-x: hidden; }
        .search-container { display: flex; justify-content: center; padding: 20px 0; margin-bottom: 20px; }
        .search-box { display: flex; align-items: center; background-color: #4A2C18; border-radius: 50px; padding: 10px 20px; width: 100%; max-width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .search-icon { font-size: 1.1em; color: #f7f7f7; margin-right: 10px; }
        .search-input { flex-grow: 1; border: none; background: #4A2C18; color: #f7f7f7; font-size: 1em; outline: none; margin-left: 6px; }
        .search-input::placeholder { color: #d7ccc8; }
        .main-wrapper { background-color: #4A2C18; padding: 1.5rem; border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); max-width: 1200px; width: calc(100% - 30px); margin: 0 auto 20px; }
        .main-wrapper.light-theme { background-color: #ffffff; padding: 1rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        @media (min-width: 768px) { .main-wrapper { padding: 2rem; width: calc(100% - 60px); } }
        .header-title { font-size: 1.5rem; font-weight: 700; color: #ffffff; margin-bottom: 1rem; }
        .main-wrapper.light-theme .header-title { font-size: 1.25rem; color: #000; margin-bottom: 0.75rem; }
        .scroll-container { display: flex; overflow-x: scroll; padding-bottom: 0.5rem; gap: 1rem; -ms-overflow-style: none; scrollbar-width: none; }
        .scroll-container::-webkit-scrollbar { display: none; }
        .article-card { flex-shrink: 0; width: 320px; background-color: #4A2C18; border-radius: 0.75rem; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid rgba(68,64,60,0.5); display: flex; position: relative; }
        .main-wrapper.light-theme .article-card { background-color: #ffffff; border: 1px solid #f3f4f6; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .card-image-area { width: 40%; aspect-ratio: 1/1; overflow: hidden; }
        .card-image { width: 100%; height: 100%; object-fit: cover; }
        .card-content-area { width: 60%; padding: 0.75rem; display: flex; flex-direction: column; justify-content: space-between; color: #f9fafb; }
        .main-wrapper.light-theme .card-content-area { color: initial; }
        .content-title { font-weight: 600; font-size: 1rem; line-height: 1.25; margin-bottom: 0.25rem; color: #f9fafb; }
        .main-wrapper.light-theme .content-title { color: #333333; margin-bottom: 0.5rem; }
        .rating-stars { font-size: 1rem; color: #fbbf24; margin-bottom: 0.75rem; letter-spacing: 2px; }
        .rating-stars .unfilled-star { color: rgba(255,255,255,0.5); }
        .rating-stars .empty-star { color: #d1d5db; }
        .action-area { display: flex; flex-direction: column; gap: 0.5rem; }
        .action-button { width: 100%; font-size: 0.75rem; font-weight: bold; background-color: #ffffff; color: #000; padding: 0.25rem 0; border-radius: 1rem; cursor: pointer; transition: background-color 150ms; border: none; display: flex; align-items: center; justify-content: center; }
        .action-button:hover { background-color: #f3f4f6; }
        .arrow-icon { width: 1.2rem; height: 1.2rem; margin-left: 0.25rem; vertical-align: middle; }
        .main-wrapper.light-theme .action-button { font-size: 0.875rem; background-color: #DAC6BB; color: #000; padding: 0.35rem 0; border-radius: 1rem; }
        .main-wrapper.light-theme .action-button:hover { background-color: #e5e7eb; }
        .nav-tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .tab { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 600; cursor: pointer; border: 1px solid #e5e7eb; transition: all 0.15s; }
        .tab:not(.active) { background-color: #ffffff; color: #000; border-color: #6b7280; }
        .tab.active { background-color: #DAC6BB; color: #000; border-color: #DAC6BB; }
        .btn-hapus-favorit { position: absolute; top: 6px; right: 6px; background: rgba(0,0,0,0.4); border: none; color: #fff; border-radius: 50%; width: 22px; height: 22px; font-size: 0.7em; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; z-index: 10; }
        .btn-hapus-favorit:hover { background: rgba(200,0,0,0.7); }
        .favorit-kosong { color: rgba(255,255,255,0.7); font-size: 0.95em; padding: 10px 0; text-align: center; width: 100%; }
        .main-wrapper.light-theme .favorit-kosong { color: #888; }
        .footer { background-color: #4a2c18; color: #fff; padding: 40px 20px; font-size: 14px; }
        .footer-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1.2fr 2.5fr 1fr; gap: 20px; align-items: flex-start; }
        .footer-kolom { padding: 0 10px; }
        .logo-info { display: flex; flex-direction: column; align-items: flex-start; }
        .logo-title { display: flex; align-items: center; margin-bottom: 10px; }
        .logo-title img { width: 40px; height: 40px; margin-right: 8px; }
        .logo-title h3 { color: white; margin: 0; font-size: 18px; }
        .logo-info p { margin: 0 0 15px 0; line-height: 1.5; }
        .sosial-media-wrapper { margin-top: 10px; }
        .sosial-media { display: flex; margin-bottom: 5px; }
        .sosial-media a { color: #fff; font-size: 24px; margin-right: 15px; text-decoration: none; }
        .middle-section { display: flex; flex-direction: column; }
        .navigasi-horizontal { display: flex; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; }
        .navigasi-horizontal a { color: #fff; text-decoration: none; font-weight: bold; font-size: 16px; padding-right: 20px; }
        .middle-section h4 { color: white; margin-top: 0; margin-bottom: 10px; font-weight: bold; font-size: 16px; }
        .kontak .item-kontak { margin-bottom: 15px; }
        .kontak .item-kontak p { margin: 2px 0; }
        .kontak .item-kontak p:first-child { font-weight: bold; color: white; display: flex; align-items: center; }
        .kontak .item-kontak i { margin-right: 8px; font-size: 18px; color: #fff; }
        @media (max-width: 768px) { .footer-container { grid-template-columns: 1fr; gap: 30px; } }
        a { text-decoration: none; color: #fff; }
        .action-area a { color: #000; }
    </style>
</head>
<body>

<?php include 'navbar_user.php'; ?>

<div class="search-container">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="input-cari" placeholder="Pencarian..." class="search-input" oninput="filterKartu()">
    </div>
</div>

<!-- ARTIKEL FAVORIT -->
<div class="main-wrapper">
    <h2 class="header-title">Artikel Favorit</h2>
    <div class="scroll-container" id="scroll-favorit">
        <?php if (empty($favorit_data)): ?>
            <p class="favorit-kosong"><i class="far fa-bookmark" style="margin-right:8px;"></i>Belum ada artikel yang difavoritkan.</p>
        <?php else: ?>
            <?php foreach ($favorit_data as $art):
                $isBio  = strtolower($art['kategori']) === 'biografi';
                $penuh  = round($art['rata_rating']);
                $kosong = 5 - $penuh;
                $bintang = str_repeat('★', $penuh) . str_repeat('<span class="unfilled-star">★</span>', $kosong);
            ?>
                <div class="article-card"
                     data-judul="<?= htmlspecialchars(strtolower($art['judul'])) ?>"
                     data-kategori="<?= htmlspecialchars(strtolower($art['kategori'])) ?>">
                    <button class="btn-hapus-favorit" title="Hapus dari Favorit"
                            onclick="hapusFavorit(this, <?= (int)$art['id'] ?>)">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="card-image-area">
                        <img src="<?= htmlspecialchars($art['thumb_path']) ?>"
                             alt="<?= htmlspecialchars($art['judul']) ?>"
                             class="card-image"
                             onerror="this.src='cover1.jpg'">
                    </div>
                    <div class="card-content-area">
                        <div>
                            <h3 class="content-title"><?= htmlspecialchars($art['judul']) ?></h3>
                            <?php if (!$isBio): ?>
                                <div class="rating-stars"><?= $bintang ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="action-area">
                            <a href="detail_artikel.php?slug=<?= urlencode($art['slug']) ?>&baca=1">
                                <button class="action-button">
                                    Baca Sekarang
                                    <img src="majesticons_arrow-right.png" alt="panah" class="arrow-icon">
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- LAINNYA -->
<div class="main-wrapper light-theme">
    <h2 class="header-title">Lainnya</h2>
    <div class="nav-tabs">
        <?php foreach ($daftar_kategori as $i => $kat): ?>
            <button class="tab <?= $i === 0 ? 'active' : '' ?>"
                    onclick="gantiTab(this, '<?= htmlspecialchars(strtolower($kat['slug_kategori'])) ?>')">
                <?= htmlspecialchars($kat['nama_kategori']) ?>
            </button>
        <?php endforeach; ?>
    </div>
    <div class="scroll-container" id="scroll-lainnya"></div>
</div>

<!-- FOOTER -->
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
                <a href="landingpagepilihanfix.php">Beranda</a>
                <a href="viewmore.php">Artikel</a>
                <a href="favorit.php">Favorit</a>
                <a href="rating.php">Ulasan</a>
            </div>
            <div>
                <h4>Tentang Kita</h4>
                <p>Sejiwa.id adalah proyek kami untuk mengeksplorasi sejarah Indonesia dan berbagai cerita yang berharga dari masa lalu.</p>
            </div>
        </div>
        <div class="footer-kolom kontak">
            <div class="item-kontak">
                <p><i class="fas fa-envelope"></i> Email:</p>
                <p>blaabla@gmail.com</p>
            </div>
            <div class="item-kontak">
                <p><i class="fas fa-phone"></i> Call:</p>
                <p>(480) 555-0103</p>
            </div>
        </div>
    </div>
</div>

<script>
var FAVORIT_DATA = <?= json_encode($favorit_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function hapusFavorit(btn, artikelId) {
    if (!confirm('Hapus dari favorit?')) return;
    var formData = new FormData();
    formData.append('id_artikel', artikelId);
    fetch('toggle_favorit.php', { method: 'POST', body: formData })
    .then(function(res){ return res.json(); })
    .then(function(data){
        if (data.status === 'removed') {
            var kartu = btn.closest('.article-card');
            if (kartu) kartu.remove();
            FAVORIT_DATA = FAVORIT_DATA.filter(function(f){ return f.id != artikelId; });
            renderLainnya();
            var container = document.getElementById('scroll-favorit');
            if (container && container.querySelectorAll('.article-card').length === 0) {
                container.innerHTML = '<p class="favorit-kosong"><i class="far fa-bookmark" style="margin-right:8px;"></i>Belum ada artikel yang difavoritkan.</p>';
            }
        } else {
            alert('Gagal menghapus: ' + (data.message || 'Error'));
        }
    })
    .catch(function(){ alert('Koneksi gagal.'); });
}

var tabAktif = '<?= !empty($daftar_kategori) ? strtolower($daftar_kategori[0]['slug_kategori']) : '' ?>';

function gantiTab(tabEl, kategori) {
    document.querySelectorAll('.tab').forEach(function(t){ t.classList.remove('active'); });
    tabEl.classList.add('active');
    tabAktif = kategori;
    renderLainnya();
}

function renderLainnya() {
    var container = document.getElementById('scroll-lainnya');
    container.innerHTML = '';
    var filtered = FAVORIT_DATA.filter(function(f){
        return (f.kategori || '').toLowerCase() === tabAktif;
    });
    if (filtered.length === 0) {
        container.innerHTML = '<p class="favorit-kosong" style="color:#888;"><i class="far fa-bookmark" style="margin-right:8px;"></i>Belum ada artikel favorit di kategori ini.</p>';
        return;
    }
    filtered.forEach(function(art) {
        var isBio  = (art.kategori || '').toLowerCase() === 'biografi';
        var thumb  = art.thumb_path || 'cover1.jpg';
        var penuh  = Math.round(art.rata_rating || 0);
        var bintang = '★'.repeat(penuh) + '<span class="empty-star" style="color:#d1d5db;">★</span>'.repeat(5 - penuh);
        var ratingHtml = isBio ? '' : '<div class="rating-stars">' + bintang + '</div>';
        var kartu = document.createElement('div');
        kartu.className = 'article-card';
        kartu.setAttribute('data-judul', (art.judul || '').toLowerCase());
        kartu.setAttribute('data-kategori', (art.kategori || '').toLowerCase());
        kartu.innerHTML =
            '<button class="btn-hapus-favorit" title="Hapus dari Favorit" onclick="hapusFavorit(this, ' + art.id + ')">' +
                '<i class="fas fa-times"></i>' +
            '</button>' +
            '<div class="card-image-area">' +
                '<img src="' + thumb + '" alt="' + (art.judul || '') + '" class="card-image" onerror="this.src=\'cover1.jpg\'">' +
            '</div>' +
            '<div class="card-content-area">' +
                '<div>' +
                    '<h3 class="content-title">' + (art.judul || '') + '</h3>' +
                    ratingHtml +
                '</div>' +
                '<div class="action-area">' +
                    '<a href="detail_artikel.php?slug=' + encodeURIComponent(art.slug || '') + '&baca=1">' +
                        '<button class="action-button">Baca Sekarang</button>' +
                    '</a>' +
                '</div>' +
            '</div>';
        container.appendChild(kartu);
    });
}

function filterKartu() {
    var kata = document.getElementById('input-cari').value.toLowerCase();
    document.querySelectorAll('#scroll-favorit .article-card').forEach(function(kartu){
        var judul = kartu.getAttribute('data-judul') || '';
        kartu.style.display = judul.includes(kata) ? '' : 'none';
    });
}

renderLainnya();
</script>
</body>
</html>