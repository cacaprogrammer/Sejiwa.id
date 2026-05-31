<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: loginpage.php");
    exit();
}

require_once 'koneksi.php';

// ── Hitung notif (untuk navbar_user.php) ──
$_nb_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tb_notifikasi WHERE user_id = ? AND dibaca = 0");
$_nb_stmt->bind_param("i", $_SESSION['id']);
$_nb_stmt->execute();
$_nb_count = $_nb_stmt->get_result()->fetch_assoc()['total'];

// ── Ambil slug dari URL ──
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header("Location: landingpagepilihanfix.php");
    exit();
}

// ════════════════════════════════════════════════════════
// PERUBAHAN 1: Tambah JOIN ke tb_user untuk ambil nama_penulis
// ════════════════════════════════════════════════════════
$stmt = $conn->prepare("
    SELECT a.*,
           COALESCE(AVG(u.rating), 0) AS rata_rating,
           COUNT(u.id) AS jml_ulasan,
           COALESCE(a.penulis, tu.nama_lengkap, 'Admin') AS nama_penulis
    FROM tb_artikel a
    LEFT JOIN tb_ulasan u ON u.artikel_id = a.id
    LEFT JOIN tb_user tu ON a.created_by = tu.id
    WHERE a.slug = ? AND a.status = 'published'
    GROUP BY a.id
");
$stmt->bind_param("s", $slug);
$stmt->execute();
$artikel = $stmt->get_result()->fetch_assoc();

if (!$artikel) {
    http_response_code(404);
    echo "<p style='text-align:center;margin-top:80px;font-family:Arial'>Artikel tidak ditemukan. <a href='landingpagepilihanfix.php'>Kembali</a></p>";
    exit();
}

// ── Tambah view count ──
$conn->query("UPDATE tb_artikel SET view_count = view_count + 1 WHERE id = {$artikel['id']}");

// ── Catat history baca ──
if (isset($_SESSION['id'])) {
    $uid = (int)$_SESSION['id'];
    $aid = (int)$artikel['id'];
    $cek = $conn->query("SELECT id FROM tb_history WHERE user_id=$uid AND artikel_id=$aid");
    if ($cek->num_rows === 0) {
        $conn->query("INSERT INTO tb_history (user_id, artikel_id, read_at) VALUES ($uid, $aid, NOW())");
    } else {
        $conn->query("UPDATE tb_history SET read_at=NOW() WHERE user_id=$uid AND artikel_id=$aid");
    }
}

// ── Helper path thumbnail ──
function thumbSrc($thumb) {
    if (empty($thumb)) return 'cover1.jpg';
    if (file_exists(__DIR__ . '/uploads/' . $thumb)) return 'uploads/' . $thumb;
    return $thumb;
}

// ── Render bintang ──
function bintang($nilai) {
    $penuh  = round($nilai);
    $kosong = 5 - $penuh;
    $html   = '';
    for ($i = 0; $i < $penuh;  $i++) $html .= '★';
    for ($i = 0; $i < $kosong; $i++) $html .= '<span class="unfilled-star">★</span>';
    return $html;
}

$thumb      = thumbSrc($artikel['thumbnail']);
$mode       = isset($_GET['baca']) ? 'baca' : 'sampul';
$isBiografi = (strtolower($artikel['kategori']) === 'biografi');

// ── Tentukan label penulis di pojok kanan ──
// Jika artikel dari admin (created_by NULL atau penulis = 'Admin') → tampil 'Sejiwa.id'
// Jika artikel dari user yang sudah diverifikasi → tampil nama user
if (empty($artikel['created_by']) || strtolower(trim($artikel['penulis'])) === 'admin') {
    $label_penulis = 'Sejiwa.id';
} else {
    $label_penulis = htmlspecialchars($artikel['nama_penulis']);
}

// ── Parse info biografi ──
$bio_info = ['lahir' => '', 'meninggal' => '', 'pekerjaan' => ''];

if ($isBiografi) {
    if (!empty($artikel['preview'])) {
        $decoded = json_decode($artikel['preview'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (!empty($decoded['lahir']))     $bio_info['lahir']     = $decoded['lahir'];
            if (!empty($decoded['meninggal'])) $bio_info['meninggal'] = $decoded['meninggal'];
            if (!empty($decoded['pekerjaan'])) $bio_info['pekerjaan'] = $decoded['pekerjaan'];
        }
    }
    if (!empty($artikel['konten'])) {
        $konten_plain = strip_tags($artikel['konten']);
        if (empty($bio_info['lahir']) && preg_match('/lahir[^,\n.]*?([\d]{1,2}\s+\w+\s+\d{4})/iu', $konten_plain, $m))
            $bio_info['lahir'] = $m[1];
        if (empty($bio_info['meninggal']) && preg_match('/(wafat|meninggal)[^,\n.]*?([\d]{1,2}\s+\w+\s+\d{4})/iu', $konten_plain, $m))
            $bio_info['meninggal'] = $m[2];
        if (empty($bio_info['pekerjaan']) && preg_match('/(insinyur|politikus|presiden|proklamator|pahlawan|dokter|jenderal|ulama|penulis|ilmuwan|seniman|aktivis|pendidik)[^,\n.]*/iu', $konten_plain, $m))
            $bio_info['pekerjaan'] = ucfirst(strtolower(trim($m[0], ' .,;')));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($artikel['judul']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; background-color: #f7f7f7; }

        /* ════ LAYOUT SAMPUL ════ */
        .sampul-container { display: grid; grid-template-columns: 33% 67%; min-height: 100vh; }
        .sampul-left { background: #4a2a18; position: relative; padding: 20px; color: white; }
        .photo-box { position: relative; top: calc(40% + 40px); left: 70%; transform: translate(-50%, -50%); width: 350px; }
        .photo-box img { width: 100%; display: block; position: relative; z-index: 2; border-radius: 2px; }
        .photo-shadow { position: absolute; top: 8px; left: 15px; width: 100%; height: 98%; border-radius: 2px; background-color: #ccc; z-index: 1; }
        .sampul-right { padding: 30px 120px; }
        .sampul-right h2 { font-size: 32px; margin: 0 0 15px 0; }
        .stars span { font-size: 22px; color: gold; }
        .stars .unfilled-star { font-size: 22px; color: #ccc; }
        .sampul-right p { line-height: 1.6; text-align: justify; font-size: 15px; margin-bottom: 15px; }
        .button { margin-top: 25px; }
        .btn-baca { background: #4a2a18; color: #fff; padding: 12px 10px; display: inline-flex; align-items: center; text-decoration: none; border-radius: 4px; font-size: 14px; }
        .btn-baca img { margin-left: 8px; width: 18px; }

        /* ════ LAYOUT ISI — CONTAINER ════ */
        .isi-container {
            width: 1230px; margin: 30px auto; padding: 40px 55px;
            background-color: #fff; box-shadow: 0 4px 30px rgba(0,0,0,0.08);
            border-radius: 14px; position: relative; top: -30px;
        }
        .article-title-bar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px; }
        .main-title { font-size: 28px; font-weight: 700; color: #000; line-height: 1; margin: 0; }
        .site-name-right { color: #000; font-weight: 600; font-size: 16px; }
        .separator { border: 0; height: 3px; background-color: #000; margin-bottom: 28px; }
        .article-content { display: flex; gap: 50px; align-items: flex-start; }

        /* ════ CARD KIRI SEJARAH ════ */
        .left-column-sejarah { flex-basis: 300px; flex-shrink: 0; display: flex; flex-direction: column; gap: 15px; }
        .article-card-wrapper {
            border: 1px solid #ddd; padding: 15px; background-color: #fff;
            border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex; flex-direction: column; gap: 10px;
            width: 280px; max-height: 580px; position: relative;
        }
        .image-box { border: none; padding: 0; background-color: transparent; border-radius: 6px; overflow: hidden; position: relative; flex-grow: 1; }
        .main-article-image { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; filter: brightness(0.8); }
        .article-title-card {
            background-color: #DAC6BB; padding: 10px 15px; text-align: center;
            font-weight: 700; font-size: 16px; border-radius: 6px; color: #000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 42px;
        }
        .rating-box-sejarah {
            background-color: #4A2C18; color: white; padding: 10px; text-align: center;
            font-weight: bold; cursor: pointer; border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: background-color 0.2s, transform 0.15s;
            display: flex; justify-content: center; align-items: center;
            gap: 8px; width: 249px; position: relative; left: 14px;
            border: none; font-size: 14px;
        }
        .rating-box-sejarah:hover { background-color: #6d4c41; transform: translateY(-1px); }

        /* ════ CARD KIRI BIOGRAFI ════ */
        .left-card-biografi {
            width: 280px; flex-shrink: 0; background: #ffffff;
            border-radius: 12px; padding: 15px;
            box-shadow: 0 3px 14px rgba(0,0,0,0.12);
            border: 1px solid #e6e6e6; position: relative; align-self: flex-start;
        }
        .bio-photo { width: 100%; border-radius: 8px; box-shadow: 0 0 6px rgba(0,0,0,0.1); display: block; }
        .bio-photo-caption {
            text-align: center; margin-top: 8px; margin-bottom: 4px;
            font-size: 14px; font-weight: 600; color: #2a1508;
        }
        .info-title {
            background: #e8c9c0; padding: 10px; border-radius: 6px;
            text-align: center; font-weight: 600; font-size: 14px;
            margin-top: 12px; margin-bottom: 10px; color: #2a1508;
        }
        .info-table { width: 100%; margin-top: 5px; border-collapse: collapse; }
        .info-table td { padding: 6px 0; font-size: 14px; vertical-align: top; color: #333; }
        .info-table td:first-child { width: 40%; font-weight: 600; color: #4a2c18; padding-right: 8px; }
        .info-table tr + tr td { border-top: 1px solid #f0e4d2; }
        .info-table .kosong { color: #aaa; font-style: italic; font-size: 12px; }

        /* ── Bookmark ── */
        .btn-bookmark {
            position: absolute; bottom: 10px; right: 10px; z-index: 5;
            background: none; border: none; cursor: pointer; padding: 0;
            width: 32px; height: 32px; display: flex; align-items: center;
            justify-content: center; transition: transform 0.2s;
        }
        .btn-bookmark:hover { transform: scale(1.2); }
        .btn-bookmark .fa-bookmark { font-size: 1.5em; color: #4a2c18; transition: color 0.2s; }
        .btn-bookmark.active .fa-bookmark { color: #e67e22; }
        .rating-icon { width: 20px; height: 20px; object-fit: contain; }

        /* ════ KONTEN KANAN ════ */
        .right-column-description {
            flex-grow: 1; height: 580px; overflow-y: auto;
            padding-right: 14px; font-size: 15px; line-height: 1.7;
        }
        .right-column-description::-webkit-scrollbar { width: 7px; }
        .right-column-description::-webkit-scrollbar-thumb { background: #c9c9c9; border-radius: 8px; }
        .right-column-description p { margin-bottom: 20px; text-align: justify; }
        .right-column-description h2 { font-size: 28px; font-weight: 700; margin-bottom: 16px; color: #2a1508; }

        /* ════════════════════════════════════════════════
           PERUBAHAN 2: CSS Nama Penulis
        ════════════════════════════════════════════════ */
        .penulis-bar {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: #7a6050;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0e4d2;
        }
        .penulis-bar i  { color: #a3826f; }
        .penulis-bar strong { color: #4a2c18; }

        /* Toast */
        .toast {
            position: fixed; bottom: 30px; left: 50%;
            transform: translateX(-50%) translateY(20px);
            background-color: #4a2c18; color: #fff; padding: 10px 22px;
            border-radius: 30px; font-size: 0.9em; font-weight: bold;
            opacity: 0; transition: opacity 0.3s, transform 0.3s;
            z-index: 9999; pointer-events: none;
        }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        /* ── Responsive ── */
        @media (max-width: 1300px) { .isi-container { width: 98%; padding: 25px 20px; } }
        @media (max-width: 1024px) {
            .sampul-right { padding: 50px; }
            .photo-box { width: 300px; left: 70%; }
        }
        @media (max-width: 768px) {
            .sampul-container { grid-template-columns: 1fr; }
            .sampul-left { height: 350px; }
            .photo-box { top: 50%; left: 50%; width: 260px; transform: translate(-50%, -50%); }
            .sampul-right { padding: 30px 20px; }
            .sampul-right h2 { font-size: 26px; }
            .article-content { flex-direction: column; gap: 20px; }
            .left-column-sejarah { flex-basis: auto; }
            .left-card-biografi { width: 100%; }
            .right-column-description { height: auto; overflow-y: visible; padding-right: 0; }
            .isi-container { padding: 15px; }
            .rating-box-sejarah { width: 100%; left: 0; }
            .article-card-wrapper { width: 100%; }
        }
    </style>
</head>
<body>

<?php include 'navbar_user.php'; ?>

<div class="toast" id="toast"></div>

<?php if ($mode === 'sampul'): ?>
<!-- ════ TAMPILAN SAMPUL ════ -->
<div class="sampul-container">
    <div class="sampul-left">
        <div class="photo-box">
            <div class="photo-shadow"></div>
            <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($artikel['judul']) ?>">
        </div>
    </div>
    <div class="sampul-right">
        <h2><?= htmlspecialchars($artikel['judul']) ?></h2>
        <?php if (!$isBiografi): ?>
        <div class="stars">
            <span><?= bintang(round($artikel['rata_rating'])) ?></span>
        </div>
        <?php endif; ?>
        <?php
        $preview_raw     = $artikel['preview'];
        $preview_decoded = json_decode($preview_raw, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $teks = $preview_decoded['teks_preview'] ?? '';
            if (!empty($teks)) {
                $paragraf = array_filter(array_map('trim', explode("\n", strip_tags($teks))));
                foreach (array_slice(array_values($paragraf), 0, 3) as $par) {
                    echo '<p>' . htmlspecialchars($par) . '</p>';
                }
            }
        } else {
            $paragraf = array_filter(array_map('trim', explode("\n", strip_tags($preview_raw))));
            foreach (array_slice(array_values($paragraf), 0, 3) as $par) {
                echo '<p>' . htmlspecialchars($par) . '</p>';
            }
        }
        ?>
        <div class="button">
            <a class="btn-baca" href="detail_artikel.php?slug=<?= urlencode($artikel['slug']) ?>&baca=1">
                Baca Selengkapnya
                <img src="iconbaca.png" alt="">
            </a>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ════ TAMPILAN ISI ARTIKEL ════ -->
<div class="isi-container">
    <div class="article-title-bar">
        <h2 class="main-title"><?= strtoupper(htmlspecialchars($artikel['kategori'])) ?></h2>
        <span class="site-name-right"><?= $isBiografi ? 'Wikipedia.com' : $label_penulis ?></span>
    </div>
    <hr class="separator">

    <div class="article-content">

        <?php if ($isBiografi): ?>
        <!-- ════ CARD KIRI — BIOGRAFI ════ -->
        <div class="left-card-biografi">
            <img src="<?= htmlspecialchars($thumb) ?>"
                 alt="<?= htmlspecialchars($artikel['judul']) ?>"
                 class="bio-photo">
            <div class="bio-photo-caption"><?= htmlspecialchars($artikel['judul']) ?></div>
            <div class="info-title">Info Pribadi</div>
            <table class="info-table">
                <tr>
                    <td>Lahir</td>
                    <td><?= !empty($bio_info['lahir']) ? htmlspecialchars($bio_info['lahir']) : '<span class="kosong">-</span>' ?></td>
                </tr>
                <tr>
                    <td>Meninggal</td>
                    <td><?= !empty($bio_info['meninggal']) ? htmlspecialchars($bio_info['meninggal']) : '<span class="kosong">-</span>' ?></td>
                </tr>
                <tr>
                    <td>Pekerjaan</td>
                    <td><?= !empty($bio_info['pekerjaan']) ? htmlspecialchars($bio_info['pekerjaan']) : '<span class="kosong">-</span>' ?></td>
                </tr>
            </table>
            <button class="btn-bookmark" id="btn-bookmark" onclick="toggleFavorit()" title="Tambah ke Favorit">
                <i class="far fa-bookmark" id="bookmark-icon"></i>
            </button>
        </div>

        <?php else: ?>
        <!-- ════ CARD KIRI — SEJARAH ════ -->
        <div class="left-column-sejarah">
            <div class="article-card-wrapper">
                <div class="image-box">
                    <img src="<?= htmlspecialchars($thumb) ?>"
                         alt="<?= htmlspecialchars($artikel['judul']) ?>"
                         class="main-article-image">
                </div>
                <div class="article-title-card"><?= htmlspecialchars($artikel['judul']) ?></div>
                <button class="btn-bookmark" id="btn-bookmark" onclick="toggleFavorit()" title="Tambah ke Favorit">
                    <i class="far fa-bookmark" id="bookmark-icon"></i>
                </button>
            </div>
            <button class="rating-box-sejarah" onclick="bukaRating()">
                <span>Rating &amp; Ulasan</span>
                <img src="mdi_read-more-outline.png" alt="Rating" class="rating-icon">
            </button>
        </div>
        <?php endif; ?>

        <!-- ════ KONTEN KANAN ════ -->
        <div class="right-column-description" id="article-body">

            <?php if ($isBiografi): ?>
                <h2><?= htmlspecialchars($artikel['judul']) ?></h2>
            <?php endif; ?>

            <?php
            $konten = trim($artikel['konten']);
            echo (!empty($konten)) ? $konten : '<p>Konten belum tersedia.</p>';
            ?>
        </div>

    </div>
</div>
<?php endif; ?>

<script>
const ARTIKEL_DATA = {
    id:         '<?= addslashes($artikel["slug"]) ?>',
    artikel_id: <?= (int)$artikel["id"] ?>,
    judul:      '<?= addslashes(htmlspecialchars($artikel["judul"])) ?>',
    gambar:     '<?= addslashes($thumb) ?>',
    link:       'detail_artikel.php?slug=<?= urlencode($artikel["slug"]) ?>&baca=1',
    kategori:   '<?= addslashes(strtolower($artikel["kategori"])) ?>'
};
const FAVORIT_KEY = 'sejiwa_favorit';

function showToast(pesan) {
    const toast = document.getElementById('toast');
    toast.textContent = pesan;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
}

function getFavorit() { try { return JSON.parse(localStorage.getItem(FAVORIT_KEY)) || []; } catch { return []; } }
function saveFavorit(list) { localStorage.setItem(FAVORIT_KEY, JSON.stringify(list)); }
function isFavorit() { return getFavorit().some(f => f.id === ARTIKEL_DATA.id); }
function updateBookmarkIcon() {
    const btn  = document.getElementById('btn-bookmark');
    const icon = document.getElementById('bookmark-icon');
    if (!btn) return;
    if (isFavorit()) {
        btn.classList.add('active'); icon.className = 'fas fa-bookmark'; btn.title = 'Hapus dari Favorit';
    } else {
        btn.classList.remove('active'); icon.className = 'far fa-bookmark'; btn.title = 'Tambah ke Favorit';
    }
}
function toggleFavorit() {
    let favorit = getFavorit();
    if (isFavorit()) {
        favorit = favorit.filter(f => f.id !== ARTIKEL_DATA.id);
        saveFavorit(favorit); updateBookmarkIcon(); showToast('Dihapus dari Favorit');
    } else {
        favorit.push({ ...ARTIKEL_DATA, progress: hitungProgress() });
        saveFavorit(favorit); updateBookmarkIcon(); showToast('Ditambahkan ke Favorit!');
    }
}
function hitungProgress() {
    const el = document.getElementById('article-body');
    if (!el) return 0;
    const sh = el.scrollHeight - el.clientHeight;
    return sh <= 0 ? 100 : Math.round((el.scrollTop / sh) * 100);
}
function simpanProgress() {
    const favorit = getFavorit();
    const idx = favorit.findIndex(f => f.id === ARTIKEL_DATA.id);
    if (idx !== -1) { favorit[idx].progress = hitungProgress(); saveFavorit(favorit); }
}
const ab = document.getElementById('article-body');
if (ab) ab.addEventListener('scroll', simpanProgress);

function bukaRating() {
    window.location.href = 'rating.php?artikel_id=' + ARTIKEL_DATA.artikel_id;
}

function toggleDropdown(event) { event.preventDefault(); const m=document.getElementById('dropdown-menu'); m.style.display=(m.style.display==='block')?'none':'block'; }
document.addEventListener('click',function(e){const m=document.getElementById('dropdown-menu');const d=document.querySelector('.dropdown');if(d&&!d.contains(e.target))m.style.display='none';});
function toggleProfileDropdown(event){event.stopPropagation();document.getElementById('profile-dropdown-menu').classList.toggle('show');}
document.addEventListener('click',function(e){const pd=document.getElementById('profile-dropdown');const m=document.getElementById('profile-dropdown-menu');if(m&&pd&&!pd.contains(e.target))m.classList.remove('show');});
const sidebar=document.getElementById('sidebar');
const hamburgerBtn=document.getElementById('hamburger-btn');
const closeSidebarBtn=document.getElementById('close-sidebar-btn');
function openSidebar(){const w=window.innerWidth;sidebar.style.width=w<350?'90%':w<450?'300px':'250px';document.body.style.overflow='hidden';}
function closeSidebar(){sidebar.style.width='0';document.body.style.overflow='';const ad=document.querySelector('.dropdown-menu-sidebar[style*="display: block"]');if(ad)ad.style.display='none';}
function toggleDropdownSidebar(event){event.preventDefault();const dd=event.target.nextElementSibling;document.querySelectorAll('.dropdown-menu-sidebar').forEach(d=>{if(d!==dd)d.style.display='none';});dd.style.display=dd.style.display==='block'?'none':'block';}
hamburgerBtn.addEventListener('click',openSidebar);
closeSidebarBtn.addEventListener('click',closeSidebar);

updateBookmarkIcon();
</script>
</body>
</html>