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

$stmt_user = $conn->prepare("SELECT * FROM tb_user WHERE id = ?");
$stmt_user->bind_param("i", $_SESSION['id']);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();

$foto = $user_data['foto_profile'] ?? null;
if ($foto && file_exists("uploads/" . $foto)) {
    $foto_src = "/website/uploads/" . $foto;
} else {
    $foto_src = "https://i.pravatar.cc/160";
}

$user_nama = htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username']);
$user_id   = (int)($_SESSION['id'] ?? 0);

$artikel_id = (int)($_GET['artikel_id'] ?? 0);

$artikel_data = null;
if ($artikel_id > 0) {
    $sa = $conn->prepare("SELECT id, judul, thumbnail, slug FROM tb_artikel WHERE id = ?");
    $sa->bind_param("i", $artikel_id);
    $sa->execute();
    $artikel_data = $sa->get_result()->fetch_assoc();
}

$ulasan_list = [];
if ($artikel_id > 0) {
    $su = $conn->prepare("
        SELECT u.id, u.rating, u.komentar, u.created_at, u.user_id,
               us.nama_lengkap, us.username, us.foto_profile
        FROM tb_ulasan u
        JOIN tb_user us ON u.user_id = us.id
        WHERE u.artikel_id = ?
        ORDER BY u.created_at DESC
    ");
    $su->bind_param("i", $artikel_id);
    $su->execute();
    $res = $su->get_result();
    while ($row = $res->fetch_assoc()) {
        $ulasan_list[] = $row;
    }
}

$rata_rating = 0;
if (count($ulasan_list) > 0) {
    $rata_rating = round(array_sum(array_column($ulasan_list, 'rating')) / count($ulasan_list));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rating & Ulasan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; background-color: #f7f7f7; }
        .main-content-section { display: flex; justify-content: flex-start; padding: 50px; align-items: flex-start; gap: 60px; }
        .kartu-artikel { width: 250px; padding: 10px; background-color: #4a2c18; border-radius: 20px; text-align: center; box-shadow: 0 6px 12px rgba(0,0,0,0.4); flex-shrink: 0; }
        .area-gambar { width: 100%; height: 315px; border-radius: 15px; margin-bottom: 8px; overflow: hidden; }
        .gambar-utama { width: 100%; height: 100%; object-fit: cover; background-color: #724636; display: block; }
        .judul-artikel { font-size: 17px; margin: 0 0 4px 0; color: #fff; font-weight: bold; }
        .rating-bintang { font-size: 23px; color: #fbbf24; margin-bottom: 10px; position: relative; top: -8px; }
        .tombol-baca { display: flex; align-items: center; justify-content: center; padding: 8px 15px; background-color: #f7f7f7; color: #000; border: none; border-radius: 25px; cursor: pointer; margin-bottom: 5px; width: 100%; font-size: 13px; font-weight: bold; text-decoration: none; }
        .tombol-baca:hover { background-color: #e0d8d4; }
        .ikon-panah { width: 18px; height: 18px; margin-left: 5px; object-fit: contain; }
        .container-ulasan { width: 100%; max-width: 700px; }
        hr { border: 0; height: 1px; background: #e0e0e0; margin: 20px 0; }
        .form-tulis-ulasan { padding: 15px; background-color: #fff; border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .judul-form { font-size: 1.1em; font-weight: bold; color: #333; margin-bottom: 15px; }
        .info-artikel-aktif { background: #f5ede8; border-left: 4px solid #4a2c18; border-radius: 6px; padding: 10px 14px; margin-bottom: 15px; font-size: 13px; color: #4a2c18; }
        .info-artikel-aktif strong { font-weight: bold; }
        .input-profil { display: flex; align-items: center; margin-bottom: 10px; gap: 8px; }
        .avatar-form { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background-color: #ccc; flex-shrink: 0; }
        .nama-pengguna-form { font-weight: bold; color: #555; }
        .rating-input { font-size: 1.8em; cursor: pointer; display: flex; gap: 4px; margin-left: auto; }
        .rating-input .star { color: #ddd; transition: color 0.15s; user-select: none; line-height: 1; }
        .rating-input .star.aktif { color: #ffc107; }
        .rating-input .star.hover { color: #ffc107; }
        .send-icon-wrapper { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background-color: #4a2c18; border-radius: 50%; cursor: pointer; transition: background-color 0.3s; border: none; flex-shrink: 0; }
        .send-icon-wrapper:hover { background-color: #724636; }
        .send-icon-wrapper:disabled { background-color: #aaa; cursor: not-allowed; }
        .ikon-kirim { color: #fff; font-size: 14px; }
        .input-komentar { width: 100%; min-height: 80px; padding: 10px; border: 1px solid #ccc; border-radius: 4px; resize: vertical; font-size: 1em; font-family: Arial, sans-serif; }
        .input-komentar:focus { outline: none; border-color: #4a2c18; box-shadow: 0 0 0 2px rgba(74,44,24,0.15); }
        .error-msg { color: #c0392b; font-size: 12px; margin-top: 6px; display: none; }
        .total-ulasan { font-size: 1.5em; font-weight: bold; color: #333; margin-bottom: 25px; }
        .ulasan-item { display: flex; padding: 15px 0; border-bottom: 1px solid #eee; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
        .ulasan-avatar { width: 45px; height: 45px; border-radius: 50%; margin-right: 15px; flex-shrink: 0; background-color: #ddd; object-fit: cover; }
        .ulasan-konten { flex-grow: 1; }
        .ulasan-header { display: flex; align-items: center; margin-bottom: 5px; gap: 8px; flex-wrap: wrap; }
        .ulasan-nama { font-weight: bold; color: #333; }
        .ulasan-rating .star-filled { color: #ffc107; }
        .ulasan-rating .star-empty { color: #aaa; }
        .ulasan-teks { font-size: 0.9em; color: #555; line-height: 1.4; }
        .ulasan-tanggal { font-size: 0.75em; color: #aaa; margin-left: auto; }
        .ulasan-item:last-child { border-bottom: none; }
        .tag-saya { font-size: 11px; background: #4a2c18; color: #fff; padding: 2px 8px; border-radius: 20px; }
        .kosong-ulasan { text-align: center; padding: 40px 0; color: #aaa; }
        .kosong-ulasan i { font-size: 36px; margin-bottom: 10px; display: block; }
        .no-artikel { background: #f5ede8; border-radius: 10px; padding: 40px; text-align: center; color: #4a2c18; }
        .no-artikel i { font-size: 40px; margin-bottom: 12px; display: block; opacity: 0.5; }
        .toast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(20px); background-color: #4a2c18; color: #fff; padding: 10px 22px; border-radius: 30px; font-size: 0.9em; font-weight: bold; opacity: 0; transition: opacity 0.3s, transform 0.3s; z-index: 9999; pointer-events: none; }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
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
        .navigasi-horizontal { display: flex; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; color: white; }
        .navigasi-horizontal a { color: inherit; text-decoration: none; font-weight: bold; font-size: 16px; padding-right: 20px; }
        .middle-section h4 { color: white; margin-top: 0; margin-bottom: 10px; font-size: 16px; }
        .kontak .item-kontak { margin-bottom: 15px; }
        .kontak .item-kontak p { margin: 2px 0; }
        .kontak .item-kontak p:first-child { font-weight: bold; color: white; display: flex; align-items: center; }
        .kontak .item-kontak i { margin-right: 8px; font-size: 18px; }
        a { text-decoration: none; }
        @media (max-width: 768px) { .footer-container { grid-template-columns: 1fr; gap: 30px; } .main-content-section { flex-direction: column; padding: 20px; gap: 20px; } .kartu-artikel { width: 100%; } .container-ulasan { max-width: 100%; } }
    </style>
</head>
<body>

<?php include 'navbar_user.php'; ?>

<div class="toast" id="toast"></div>

<div class="main-content-section">

    <!-- Kartu Artikel -->
    <div class="kartu-artikel">
        <?php if ($artikel_data): ?>
            <?php
                $thumb = '';
                if (!empty($artikel_data['thumbnail'])) {
                    if (file_exists(__DIR__ . '/uploads/' . $artikel_data['thumbnail'])) {
                        $thumb = 'uploads/' . $artikel_data['thumbnail'];
                    } else {
                        $thumb = $artikel_data['thumbnail'];
                    }
                } else {
                    $thumb = 'cover1.jpg';
                }
                $bintang_str = str_repeat('★', $rata_rating) . str_repeat('☆', 5 - $rata_rating);
            ?>
            <div class="area-gambar">
                <img src="<?= htmlspecialchars($thumb) ?>" alt="Gambar Artikel" class="gambar-utama"
                     onerror="this.style.background='#724636'">
            </div>
            <h3 class="judul-artikel"><?= htmlspecialchars($artikel_data['judul']) ?></h3>
            <div class="rating-bintang"><?= $bintang_str ?></div>
            <a href="detail_artikel.php?slug=<?= urlencode($artikel_data['slug']) ?>&baca=1" class="tombol-baca">
                Baca Sekarang
                <img src="majesticons_arrow-right.png" alt="Panah" class="ikon-panah" onerror="this.style.display='none'">
            </a>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:350px;opacity:0.7;">
                <i class="fas fa-book-open" style="font-size:40px;color:#DAC6BB;"></i>
                <p style="color:#DAC6BB;font-size:14px;margin-top:10px;line-height:1.4;text-align:center;padding:0 10px;">
                    Pilih artikel dan tekan "Rating &amp; Ulasan" untuk memberikan komentar
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Form & Daftar Ulasan -->
    <div class="container-ulasan">

        <?php if ($artikel_data): ?>
        <div class="form-tulis-ulasan">
            <div class="judul-form">Menulis Ulasan</div>
            <div class="info-artikel-aktif">
                Menulis ulasan untuk: <strong><?= htmlspecialchars($artikel_data['judul']) ?></strong>
            </div>
            <div class="input-profil">
                <img class="avatar-form"
                     src="<?= htmlspecialchars($foto_src) ?>"
                     alt="Foto Profil"
                     onerror="this.src='https://i.pravatar.cc/40'">
                <div class="nama-pengguna-form"><?= $user_nama ?></div>
                <div class="rating-input" id="rating-input">
                    <span class="star" data-nilai="1">★</span>
                    <span class="star" data-nilai="2">★</span>
                    <span class="star" data-nilai="3">★</span>
                    <span class="star" data-nilai="4">★</span>
                    <span class="star" data-nilai="5">★</span>
                </div>
                <button class="send-icon-wrapper" id="btn-kirim" onclick="kirimUlasan()" title="Kirim ulasan">
                    <i class="fas fa-paper-plane ikon-kirim"></i>
                </button>
            </div>
            <textarea class="input-komentar" id="input-komentar"
                placeholder="Tulis komentar Anda di sini..."></textarea>
            <p class="error-msg" id="error-msg">Mohon isi komentar dan pilih rating bintang terlebih dahulu.</p>
        </div>
        <?php else: ?>
        <div class="no-artikel">
            <i class="fas fa-book-open"></i>
            <p style="font-size:15px;font-weight:bold;margin-bottom:6px;">Belum ada artikel dipilih</p>
            <p style="font-size:13px;opacity:0.7;">Buka halaman artikel lalu klik tombol "Rating &amp; Ulasan"</p>
        </div>
        <?php endif; ?>

        <hr>

        <div class="total-ulasan">Ulasan (<?= count($ulasan_list) ?>)</div>

        <div class="daftar-ulasan" id="daftar-ulasan">
            <?php if (count($ulasan_list) === 0): ?>
                <div class="kosong-ulasan">
                    <i class="far fa-comment-dots"></i>
                    Belum ada ulasan untuk artikel ini.
                </div>
            <?php else: ?>
                <?php foreach ($ulasan_list as $u): ?>
                    <?php
                        $f_ulasan = $u['foto_profile'] ?? null;
                        if ($f_ulasan && file_exists("uploads/" . $f_ulasan)) {
                            $avatar_src = "/website/uploads/" . $f_ulasan;
                        } else {
                            $avatar_src = "https://i.pravatar.cc/45?u=" . $u['user_id'];
                        }
                        $filled  = str_repeat('★', (int)$u['rating']);
                        $empty   = str_repeat('★', 5 - (int)$u['rating']);
                        $tgl     = date('d M Y', strtotime($u['created_at']));
                        $is_saya = ((int)$u['user_id'] === $user_id);
                    ?>
                    <div class="ulasan-item">
                        <img class="ulasan-avatar"
                             src="<?= htmlspecialchars($avatar_src) ?>"
                             alt="Avatar"
                             onerror="this.src='https://i.pravatar.cc/45?u=<?= $u['user_id'] ?>'">
                        <div class="ulasan-konten">
                            <div class="ulasan-header">
                                <span class="ulasan-nama">@<?= htmlspecialchars($u['username']) ?></span>
                                <span class="ulasan-rating">
                                    <span class="star-filled"><?= $filled ?></span>
                                    <span class="star-empty"><?= $empty ?></span>
                                </span>
                                <?php if ($is_saya): ?>
                                    <span class="tag-saya">Ulasan Anda</span>
                                <?php endif; ?>
                                <span class="ulasan-tanggal"><?= $tgl ?></span>
                            </div>
                            <p class="ulasan-teks"><?= htmlspecialchars($u['komentar']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
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

<script>
    const ARTIKEL_ID  = <?= $artikel_id ?>;
    const USER_ID     = <?= $user_id ?>;
    let ratingDipilih = 0;

    // ── Bintang rating ──
    const stars = document.querySelectorAll('#rating-input .star');

    function updateBintang(nilai) {
        stars.forEach(function(s) {
            const n = parseInt(s.getAttribute('data-nilai'));
            if (n <= nilai) {
                s.classList.add('aktif');
                s.classList.remove('hover');
            } else {
                s.classList.remove('aktif');
                s.classList.remove('hover');
            }
        });
    }

    stars.forEach(function(star) {
        star.addEventListener('click', function() {
            ratingDipilih = parseInt(this.getAttribute('data-nilai'));
            updateBintang(ratingDipilih);
        });
        star.addEventListener('mouseenter', function() {
            updateBintang(parseInt(this.getAttribute('data-nilai')));
        });
        star.addEventListener('mouseleave', function() {
            updateBintang(ratingDipilih);
        });
    });

    // mouseleave pada container untuk reset ke nilai terpilih
    const ratingContainer = document.getElementById('rating-input');
    if (ratingContainer) {
        ratingContainer.addEventListener('mouseleave', function() {
            updateBintang(ratingDipilih);
        });
    }

    // ── Kirim ulasan ──
    async function kirimUlasan() {
        if (ARTIKEL_ID === 0) {
            showToast('Pilih artikel terlebih dahulu.');
            return;
        }

        const teks  = document.getElementById('input-komentar').value.trim();
        const errEl = document.getElementById('error-msg');
        const btnEl = document.getElementById('btn-kirim');

        if (!teks || ratingDipilih === 0) {
            errEl.style.display = 'block';
            return;
        }

        errEl.style.display = 'none';
        btnEl.disabled = true;

        try {
            const res = await fetch('simpan_ulasan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    artikel_id: ARTIKEL_ID,
                    rating:     ratingDipilih,
                    komentar:   teks
                })
            });

            const text = await res.text();
            console.log('Raw response simpan_ulasan:', text);

            let json;
            try {
                json = JSON.parse(text);
            } catch(e) {
                showToast('Terjadi kesalahan server.');
                console.error('Response bukan JSON:', text);
                btnEl.disabled = false;
                return;
            }

            if (json.sukses) {
                showToast('✓ Ulasan berhasil dikirim!');
                setTimeout(function(){ location.reload(); }, 1200);
            } else {
                showToast('Gagal: ' + (json.pesan || 'Terjadi kesalahan'));
                console.error('Error simpan ulasan:', json);
                btnEl.disabled = false;
            }

        } catch(err) {
            showToast('Terjadi kesalahan jaringan.');
            console.error('Fetch error:', err);
            btnEl.disabled = false;
        }
    }

    function showToast(pesan) {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = pesan;
        toast.classList.add('show');
        setTimeout(function(){ toast.classList.remove('show'); }, 2500);
    }
</script>
</body>
</html>