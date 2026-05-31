<?php
// ============================================================
// pages/artikel.php  —  Manajemen Artikel (Admin)
// ============================================================
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../cek_admin.php';

$pesan = '';
$tipe  = '';

$daftar_kategori = $conn->query(
    "SELECT id_kategori, nama_kategori, slug_kategori FROM tb_kategori ORDER BY nama_kategori ASC"
)->fetch_all(MYSQLI_ASSOC);

function buatSlug($judul) {
    $slug = strtolower(trim($judul));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return $slug;
}

function uploadGambar($file, $thumbnail_lama = '') {
    $upload_dir = __DIR__ . '/../';
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) return ['error' => 'Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.'];
    if ($file['size'] > 5 * 1024 * 1024) return ['error' => 'Ukuran gambar maksimal 5MB.'];
    $nama_baru = 'artikel_' . time() . '_' . rand(100, 999) . '.' . $ext;
    $path_tuju = $upload_dir . $nama_baru;
    if (!move_uploaded_file($file['tmp_name'], $path_tuju)) return ['error' => 'Gagal upload gambar.'];
    if (!empty($thumbnail_lama) && strpos($thumbnail_lama, 'artikel_') === 0) {
        $path_lama = $upload_dir . $thumbnail_lama;
        if (file_exists($path_lama)) unlink($path_lama);
    }
    return ['nama' => $nama_baru];
}

function getImgSrc($thumb) {
    if (empty($thumb)) return '';
    $base_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if (basename($base_dir) === 'pages') $base_dir = dirname($base_dir);
    return $base_dir . '/' . $thumb;
}

// ═══════════════════════════════════════════════════════════
//  PROSES TAMBAH ARTIKEL
// ═══════════════════════════════════════════════════════════
if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    $judul       = trim($_POST['judul']);
    $id_kategori = (int)$_POST['id_kategori'];

    $raw_status  = isset($_POST['status_value']) ? trim($_POST['status_value']) : 'published';
    $status      = in_array($raw_status, ['published', 'draft']) ? $raw_status : 'published';

    $q_kat = $conn->prepare("SELECT slug_kategori FROM tb_kategori WHERE id_kategori = ?");
    $q_kat->bind_param("i", $id_kategori);
    $q_kat->execute();
    $row_kat  = $q_kat->get_result()->fetch_assoc();
    $kategori = $row_kat ? $row_kat['slug_kategori'] : '';

    if ($_SESSION['role'] === 'penulis') {
        $penulis = $_SESSION['nama_lengkap'];
    } else {
        $penulis = trim($_POST['penulis']) ?: $_SESSION['nama_lengkap'];
    }

    $konten = trim($_POST['konten']);

    // ── Susun preview: JSON untuk biografi (termasuk teks preview), teks biasa untuk lainnya ──
    if (strtolower($kategori) === 'biografi') {
        $preview = json_encode([
            'lahir'        => trim($_POST['bio_lahir']     ?? ''),
            'meninggal'    => trim($_POST['bio_meninggal'] ?? ''),
            'pekerjaan'    => trim($_POST['bio_pekerjaan'] ?? ''),
            'teks_preview' => trim($_POST['preview']       ?? ''),
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $preview = trim($_POST['preview'] ?? '');
    }

    $slug = buatSlug($judul);
    $cek_slug = $conn->prepare("SELECT id FROM tb_artikel WHERE slug = ?");
    $cek_slug->bind_param("s", $slug);
    $cek_slug->execute();
    if ($cek_slug->get_result()->num_rows > 0) $slug = $slug . '-' . time();

    $thumbnail = '';
    if (!empty($_FILES['thumbnail']['name'])) {
        $hasil = uploadGambar($_FILES['thumbnail']);
        if (isset($hasil['error'])) { $pesan = $hasil['error']; $tipe = 'error'; }
        else $thumbnail = $hasil['nama'];
    }

    if ($tipe !== 'error') {
        $stmt = $conn->prepare(
            "INSERT INTO tb_artikel 
             (judul, slug, konten, preview, penulis, thumbnail, kategori, id_kategori, status, created_by, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $user_id = $_SESSION['id'] ?? 1;
        $stmt->bind_param("sssssssisi", $judul, $slug, $konten, $preview, $penulis, $thumbnail, $kategori, $id_kategori, $status, $user_id);
        if ($stmt->execute()) { $pesan = 'Artikel berhasil ditambahkan!'; $tipe = 'sukses'; }
        else { $pesan = 'Gagal menyimpan artikel: ' . $stmt->error; $tipe = 'error'; }
    }
}

// ═══════════════════════════════════════════════════════════
//  PROSES EDIT ARTIKEL
// ═══════════════════════════════════════════════════════════
if (isset($_POST['aksi']) && $_POST['aksi'] === 'edit') {
    $id          = (int)$_POST['id'];
    $judul       = trim($_POST['judul']);
    $id_kategori = (int)$_POST['id_kategori'];

    $raw_status  = isset($_POST['status_value']) ? trim($_POST['status_value']) : 'published';
    $status      = in_array($raw_status, ['published', 'draft']) ? $raw_status : 'published';

    $q_kat = $conn->prepare("SELECT slug_kategori FROM tb_kategori WHERE id_kategori = ?");
    $q_kat->bind_param("i", $id_kategori);
    $q_kat->execute();
    $row_kat  = $q_kat->get_result()->fetch_assoc();
    $kategori = $row_kat ? $row_kat['slug_kategori'] : '';

    if ($_SESSION['role'] === 'penulis') {
        $penulis = $_SESSION['nama_lengkap'];
    } else {
        $penulis = trim($_POST['penulis']) ?: 'Admin';
    }

    $konten = trim($_POST['konten']);

    // ── Susun preview ──
    if (strtolower($kategori) === 'biografi') {
        $preview = json_encode([
            'lahir'        => trim($_POST['bio_lahir']     ?? ''),
            'meninggal'    => trim($_POST['bio_meninggal'] ?? ''),
            'pekerjaan'    => trim($_POST['bio_pekerjaan'] ?? ''),
            'teks_preview' => trim($_POST['preview']       ?? ''),  // ← FIX: teks preview ikut tersimpan
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $preview = trim($_POST['preview'] ?? '');
    }

    $lama      = $conn->query("SELECT thumbnail FROM tb_artikel WHERE id = $id")->fetch_assoc();
    $thumbnail = $lama['thumbnail'] ?? '';

    if (!empty($_FILES['thumbnail']['name'])) {
        $hasil = uploadGambar($_FILES['thumbnail'], $thumbnail);
        if (isset($hasil['error'])) { $pesan = $hasil['error']; $tipe = 'error'; }
        else $thumbnail = $hasil['nama'];
    }

    if ($tipe !== 'error') {
        $stmt = $conn->prepare(
            "UPDATE tb_artikel 
             SET judul=?, konten=?, preview=?, penulis=?, thumbnail=?, 
                 kategori=?, id_kategori=?, status=? 
             WHERE id=?"
        );
        $stmt->bind_param("ssssssisi", $judul, $konten, $preview, $penulis, $thumbnail, $kategori, $id_kategori, $status, $id);
        if ($stmt->execute()) { $pesan = 'Artikel berhasil diperbarui!'; $tipe = 'sukses'; }
        else { $pesan = 'Gagal memperbarui artikel: ' . $stmt->error; $tipe = 'error'; }
    }
}

// ═══════════════════════════════════════════════════════════
//  PROSES HAPUS ARTIKEL
// ═══════════════════════════════════════════════════════════
if (isset($_GET['hapus'])) {
    $id   = (int)$_GET['hapus'];
    $lama = $conn->query("SELECT thumbnail FROM tb_artikel WHERE id=$id")->fetch_assoc();
    if ($lama && !empty($lama['thumbnail']) && strpos($lama['thumbnail'], 'artikel_') === 0) {
        $path_gambar = __DIR__ . '/../' . $lama['thumbnail'];
        if (file_exists($path_gambar)) unlink($path_gambar);
    }
    $conn->query("DELETE FROM tb_favorit WHERE artikel_id = $id");
    $conn->query("DELETE FROM tb_ulasan  WHERE artikel_id = $id");
    $conn->query("DELETE FROM tb_history WHERE artikel_id = $id");
    $conn->query("DELETE FROM tb_artikel WHERE id = $id");
    $pesan = 'Artikel berhasil dihapus.';
    $tipe  = 'sukses';
}

// ═══════════════════════════════════════════════════════════
//  AMBIL DATA UNTUK FORM EDIT
// ═══════════════════════════════════════════════════════════
$mode_edit = false;
$data_edit = null;
if (isset($_GET['edit'])) {
    $id_edit   = (int)$_GET['edit'];
    $data_edit = $conn->query("SELECT * FROM tb_artikel WHERE id=$id_edit")->fetch_assoc();
    if ($data_edit) $mode_edit = true;
}

// ── Parse bio_info dari preview JSON jika edit biografi ──
$bio_edit = ['lahir' => '', 'meninggal' => '', 'pekerjaan' => ''];
$preview_teks_edit = '';
if ($mode_edit && !empty($data_edit['preview'])) {
    $decoded = json_decode($data_edit['preview'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // JSON → biografi atau campuran
        $bio_edit['lahir']     = $decoded['lahir']        ?? '';
        $bio_edit['meninggal'] = $decoded['meninggal']    ?? '';
        $bio_edit['pekerjaan'] = $decoded['pekerjaan']    ?? '';
        $preview_teks_edit     = $decoded['teks_preview'] ?? '';
    } else {
        // Bukan JSON → teks biasa (non-biografi)
        $preview_teks_edit = $data_edit['preview'];
    }
}

// ═══════════════════════════════════════════════════════════
//  AMBIL DAFTAR ARTIKEL
// ═══════════════════════════════════════════════════════════
$cari        = isset($_GET['cari'])     ? trim($_GET['cari'])           : '';
$filter_kat  = isset($_GET['kategori']) ? $_GET['kategori']             : '';
$filter_st   = isset($_GET['status'])   ? $_GET['status']               : '';
$sort_by     = isset($_GET['sort'])     ? $_GET['sort']                 : 'created_at';
$sort_dir    = isset($_GET['dir'])      ? $_GET['dir']                  : 'DESC';
$halaman     = isset($_GET['halaman'])  ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 10;

$sort_allowed = ['judul', 'kategori', 'status', 'penulis', 'created_at', 'view_count'];
if (!in_array($sort_by, $sort_allowed)) $sort_by = 'created_at';
$sort_dir  = $sort_dir === 'ASC' ? 'ASC' : 'DESC';
$sort_next = $sort_dir === 'ASC' ? 'DESC' : 'ASC';

$where  = "WHERE 1=1";
$params = [];
$types  = "";
if ($cari !== '') {
    $cari_like = '%' . $cari . '%';
    $where    .= " AND (a.judul LIKE ? OR a.penulis LIKE ?)";
    $params[]  = $cari_like; $params[] = $cari_like; $types .= "ss";
}
if ($filter_kat !== '') { $where .= " AND a.kategori = ?"; $params[] = $filter_kat; $types .= "s"; }
if ($filter_st !== '')  { $where .= " AND a.status = ?";   $params[] = $filter_st;  $types .= "s"; }

$q_total = $conn->prepare("SELECT COUNT(*) as total FROM tb_artikel a LEFT JOIN tb_kategori k ON k.id_kategori = a.id_kategori $where");
if ($params) $q_total->bind_param($types, ...$params);
$q_total->execute();
$total_data = $q_total->get_result()->fetch_assoc()['total'];
$total_page = max(1, ceil($total_data / $per_halaman));
$offset     = ($halaman - 1) * $per_halaman;

$q_data = $conn->prepare("SELECT a.*, k.nama_kategori, k.slug_kategori FROM tb_artikel a LEFT JOIN tb_kategori k ON k.id_kategori = a.id_kategori $where ORDER BY a.$sort_by $sort_dir LIMIT ? OFFSET ?");
$params_data = $params; $params_data[] = $per_halaman; $params_data[] = $offset;
$q_data->bind_param($types . "ii", ...$params_data);
$q_data->execute();
$artikel_list = $q_data->get_result()->fetch_all(MYSQLI_ASSOC);

function buildUrl($extra = []) {
    $params = array_merge($_GET, $extra);
    unset($params['edit'], $params['hapus']);
    return '?' . http_build_query($params);
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<style>
:root {
    --sj-dark:    #4A2C18; --sj-darker:  #4D1E0A; --sj-mid: #7B4F2C;
    --sj-gold:    #D99B3E; --sj-sand:    #AD8D77; --sj-cream: #F8F8DC;
    --brown-900:  #3a1f0d; --brown-800:  #4D1E0A; --brown-700: #4A2C18;
    --brown-600:  #7B4F2C; --brown-400:  #AD8D77; --brown-200: #dfc8b0;
    --brown-100:  #f0e4d2; --brown-50:   #faf5ec; --cream-bg: #F8F8DC;
    --white: #ffffff; --surface: #ffffff; --surface-2: #fdfcf6;
    --text-primary: #2a1508; --text-secondary: #4A2C18;
    --text-muted: #7B4F2C; --text-disabled: #AD8D77;
    --border: #e2d0b8; --border-soft: #ede4d3;
    --shadow-sm: 0 1px 3px rgba(74,44,24,0.07), 0 1px 2px rgba(74,44,24,0.05);
    --shadow-md: 0 4px 16px rgba(74,44,24,0.10), 0 2px 6px rgba(74,44,24,0.05);
    --shadow-lg: 0 8px 32px rgba(74,44,24,0.13), 0 4px 12px rgba(74,44,24,0.07);
    --radius-sm: 6px; --radius-md: 10px; --radius-lg: 14px; --radius-xl: 18px;
    --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    --font-heading: 'Montserrat', sans-serif; --font-body: 'Roboto', sans-serif;
    --green-bg: #ecfdf5; --green-text: #065f46; --green-border: #a7f3d0;
    --yellow-bg: #fef9ee; --yellow-text: #7a4a00; --yellow-border: #f5d07a;
    --blue-bg: #eff6ff; --blue-text: #1d4ed8; --blue-border: #bfdbfe;
    --red-bg: #fff1f2; --red-text: #be123c; --red-border: #fda4af;
}
.artikel-page * { box-sizing: border-box; }
.artikel-page { font-family: var(--font-body); color: var(--text-primary); }
.notif-modern { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 20px; font-weight: 500; font-size: 14px; animation: slideDown 0.3s ease; box-shadow: var(--shadow-sm); }
.notif-modern.sukses { background: var(--green-bg); color: var(--green-text); border: 1px solid var(--green-border); }
.notif-modern.error  { background: var(--red-bg); color: var(--red-text); border: 1px solid var(--red-border); }
.notif-modern .notif-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
.notif-modern.sukses .notif-icon { background: #d1fae5; }
.notif-modern.error  .notif-icon { background: #ffe4e6; }
.notif-modern .notif-close { margin-left: auto; cursor: pointer; opacity: 0.6; transition: var(--transition); padding: 4px; border-radius: 4px; border: none; background: none; font-size: 16px; color: inherit; }
.notif-modern .notif-close:hover { opacity: 1; background: rgba(0,0,0,0.06); }
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
.page-header-left h1 { font-family: var(--font-heading); font-size: 26px; font-weight: 800; color: var(--sj-darker); margin: 0 0 4px 0; letter-spacing: -0.4px; }
.page-header-left .breadcrumb-text { font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
.btn-tambah-new { display: inline-flex; align-items: center; gap: 8px; background: var(--sj-dark); color: var(--white); padding: 10px 20px; border-radius: var(--radius-md); font-size: 14px; font-weight: 700; text-decoration: none; border: none; cursor: pointer; transition: var(--transition); box-shadow: 0 2px 8px rgba(74,44,24,0.28); white-space: nowrap; font-family: var(--font-heading); letter-spacing: 0.2px; }
.btn-tambah-new:hover { background: var(--sj-darker); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(77,30,10,0.36); color: var(--white); text-decoration: none; }
.form-card-new { background: var(--white); border-radius: var(--radius-xl); box-shadow: var(--shadow-md); border: 1px solid var(--border-soft); margin-bottom: 24px; overflow: hidden; animation: fadeInUp 0.3s ease; }
.form-card-header { background: linear-gradient(135deg, var(--sj-darker) 0%, var(--sj-dark) 55%, var(--sj-mid) 100%); padding: 20px 28px; display: flex; align-items: center; gap: 12px; }
.form-card-header .header-icon { width: 38px; height: 38px; background: rgba(255,255,255,0.15); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; }
.form-card-header h2 { font-family: var(--font-heading); color: white; font-size: 17px; font-weight: 700; margin: 0; letter-spacing: 0.2px; }
.form-card-body { padding: 28px; }
.form-section-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--sj-sand); margin-bottom: 14px; margin-top: 4px; padding-bottom: 8px; border-bottom: 1px solid var(--border-soft); font-family: var(--font-heading); }
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 18px; }
.form-grid-1 { display: grid; grid-template-columns: 1fr; gap: 18px; margin-bottom: 18px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; letter-spacing: 0.1px; }
.form-group label .required { color: #dc2626; margin-left: 2px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-md); font-size: 14px; font-family: var(--font-body); color: var(--text-primary); background: var(--surface-2); transition: var(--transition); outline: none; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--sj-mid); background: var(--white); box-shadow: 0 0 0 3px rgba(123,79,44,0.12); }
.form-group input::placeholder, .form-group textarea::placeholder { color: var(--text-disabled); }
.form-group textarea { resize: vertical; min-height: 100px; line-height: 1.6; }
#konten-textarea { min-height: 280px; font-size: 13.5px; }
.form-hint { font-size: 12px; color: var(--text-muted); margin-top: 5px; }
.preview-img-new { max-width: 140px; max-height: 90px; border-radius: var(--radius-md); margin-top: 10px; object-fit: cover; border: 2px solid var(--border); box-shadow: var(--shadow-sm); }
.form-footer { display: flex; align-items: center; gap: 10px; padding-top: 8px; border-top: 1px solid var(--border-soft); margin-top: 4px; }
.btn-simpan-new { display: inline-flex; align-items: center; gap: 8px; background: var(--sj-dark); color: white; padding: 11px 24px; border-radius: var(--radius-md); font-size: 14px; font-weight: 700; font-family: var(--font-heading); letter-spacing: 0.2px; border: none; cursor: pointer; transition: var(--transition); box-shadow: 0 2px 8px rgba(74,44,24,0.22); }
.btn-simpan-new:hover { background: var(--sj-darker); transform: translateY(-1px); }
.btn-batal-new { display: inline-flex; align-items: center; gap: 6px; background: var(--brown-100); color: var(--sj-dark); padding: 11px 20px; border-radius: var(--radius-md); font-size: 14px; font-weight: 600; font-family: var(--font-heading); text-decoration: none; border: 1px solid var(--brown-200); transition: var(--transition); }
.btn-batal-new:hover { background: var(--brown-200); color: var(--sj-darker); text-decoration: none; }

/* ════ SECTION BIOGRAFI ════ */
.bio-section {
    display: none;
    background: linear-gradient(135deg, #fdf5f0 0%, #faf0e8 100%);
    border: 1.5px solid #e8c9b0;
    border-radius: var(--radius-lg);
    padding: 20px 22px;
    margin-bottom: 18px;
    animation: fadeInUp 0.25s ease;
}
.bio-section.tampil { display: block; }
.bio-section-title {
    display: flex; align-items: center; gap: 8px;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1px; color: var(--sj-mid);
    margin-bottom: 14px; padding-bottom: 10px;
    border-bottom: 1px solid #e0c4a8;
    font-family: var(--font-heading);
}
.bio-section-title i { font-size: 16px; color: var(--sj-sand); }
.bio-preview-card {
    display: flex; align-items: flex-start; gap: 10px;
    background: white; border: 1px solid #e0c4a8;
    border-radius: var(--radius-md); padding: 12px 14px;
    margin-top: 14px; font-size: 12.5px; color: var(--text-muted);
}
.bio-preview-card i { color: var(--sj-sand); font-size: 15px; margin-top: 1px; flex-shrink: 0; }

.tabel-card-new { background: var(--white); border-radius: var(--radius-xl); box-shadow: var(--shadow-md); border: 1px solid var(--border-soft); overflow: hidden; animation: fadeInUp 0.3s ease; }
.tabel-card-header { padding: 20px 24px 0 24px; border-bottom: 1px solid var(--border-soft); }
.tabel-card-header-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.tabel-card-title { font-size: 16px; font-weight: 700; color: var(--sj-darker); display: flex; align-items: center; gap: 8px; font-family: var(--font-heading); }
.tabel-card-title .title-icon { width: 30px; height: 30px; background: var(--brown-100); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; color: var(--sj-mid); font-size: 14px; }
.count-badge { display: inline-flex; align-items: center; justify-content: center; background: #fef3d6; color: #7a4a00; font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 20px; border: 1px solid #f5d07a; }
.toolbar-new { display: flex; align-items: center; gap: 10px; padding: 16px 0; flex-wrap: wrap; }
.search-wrapper { flex: 1; min-width: 220px; position: relative; }
.search-wrapper .bx { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 17px; pointer-events: none; }
.search-input-new { width: 100%; padding: 10px 14px 10px 38px; border: 1.5px solid var(--border); border-radius: var(--radius-md); font-size: 14px; font-family: var(--font-body); background: var(--surface-2); color: var(--text-primary); transition: var(--transition); outline: none; }
.search-input-new:focus { border-color: var(--sj-mid); background: white; box-shadow: 0 0 0 3px rgba(123,79,44,0.10); }
.select-filter { padding: 10px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-md); font-size: 13.5px; font-family: var(--font-body); background: var(--surface-2); color: var(--text-secondary); transition: var(--transition); outline: none; cursor: pointer; min-width: 140px; }
.select-filter:focus { border-color: var(--sj-mid); background: white; box-shadow: 0 0 0 3px rgba(123,79,44,0.10); }
.btn-refresh { display: inline-flex; align-items: center; gap: 7px; background: var(--white); color: var(--sj-dark); padding: 10px 16px; border-radius: var(--radius-md); font-size: 13.5px; font-weight: 600; font-family: var(--font-heading); border: 1.5px solid var(--sj-sand); cursor: pointer; transition: var(--transition); white-space: nowrap; text-decoration: none; }
.btn-refresh:hover { background: var(--brown-50); border-color: var(--sj-mid); color: var(--sj-darker); text-decoration: none; }
.info-total-new { font-size: 13px; color: var(--text-muted); padding: 10px 24px; background: var(--brown-50); border-bottom: 1px solid var(--border-soft); display: flex; align-items: center; gap: 6px; }
.info-total-new strong { color: var(--sj-dark); }
.artikel-table-new { width: 100%; border-collapse: collapse; min-width: 720px; }
.artikel-table-new thead tr { background: var(--brown-50); }
.artikel-table-new th { padding: 12px 16px; text-align: left; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.7px; color: var(--text-muted); font-weight: 700; white-space: nowrap; border-bottom: 2px solid var(--border); }
.artikel-table-new th a { color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: var(--transition); font-family: var(--font-heading); }
.artikel-table-new th a:hover { color: var(--sj-dark); }
.artikel-table-new td { padding: 13px 16px; font-size: 14px; border-bottom: 1px solid var(--border-soft); vertical-align: middle; }
.artikel-table-new tbody tr { transition: var(--transition); }
.artikel-table-new tbody tr:hover td { background: var(--brown-50); }
.artikel-table-new tbody tr:last-child td { border-bottom: none; }
.thumb-wrap { width: 52px; height: 40px; border-radius: var(--radius-sm); overflow: hidden; background: var(--brown-100); display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid var(--border); }
.thumb-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
.thumb-placeholder { color: var(--brown-400); font-size: 18px; }
.judul-cell { font-weight: 600; color: var(--text-primary); font-size: 14px; font-family: var(--font-heading); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
.row-num { font-size: 12px; color: var(--text-disabled); font-weight: 500; }
.cell-muted { color: var(--text-muted); font-size: 13.5px; }
.cell-date { color: var(--text-muted); font-size: 13px; white-space: nowrap; }
.badge-new { display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 700; letter-spacing: 0.1px; white-space: nowrap; }
.badge-new .bx { font-size: 11px; }
.badge-new.status-published { background: var(--green-bg); color: var(--green-text); border: 1px solid var(--green-border); }
.badge-new.status-draft { background: var(--yellow-bg); color: var(--yellow-text); border: 1px solid var(--yellow-border); }
.aksi-group { display: flex; align-items: center; gap: 5px; justify-content: flex-end; }
.aksi-btn-new { display: inline-flex; align-items: center; gap: 5px; padding: 6px 11px; border-radius: var(--radius-sm); font-size: 12.5px; font-weight: 600; cursor: pointer; border: 1px solid transparent; text-decoration: none; transition: var(--transition); white-space: nowrap; }
.aksi-btn-new .bx { font-size: 13px; }
.btn-edit-new { background: var(--yellow-bg); color: var(--yellow-text); border-color: var(--yellow-border); }
.btn-edit-new:hover { background: #fef3c7; color: #78350f; transform: translateY(-1px); text-decoration: none; }
.btn-hapus-new { background: var(--red-bg); color: var(--red-text); border-color: var(--red-border); }
.btn-hapus-new:hover { background: #ffe4e6; color: #be123c; transform: translateY(-1px); text-decoration: none; }
.no-data-new { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.no-data-new .no-data-icon { font-size: 48px; color: var(--brown-200); display: block; margin-bottom: 12px; }
.no-data-new p { font-size: 15px; margin: 0; }
.no-data-new small { font-size: 13px; color: var(--text-disabled); margin-top: 4px; display: block; }
.pagination-new { display: flex; align-items: center; gap: 5px; padding: 16px 24px; border-top: 1px solid var(--border-soft); background: var(--brown-50); flex-wrap: wrap; }
.page-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 10px; border-radius: var(--radius-sm); font-size: 13.5px; font-weight: 500; text-decoration: none; border: 1.5px solid var(--border); color: var(--text-secondary); background: var(--white); transition: var(--transition); cursor: pointer; }
.page-btn:hover { border-color: var(--sj-sand); color: var(--sj-dark); background: var(--brown-50); text-decoration: none; }
.page-btn.aktif { background: var(--sj-dark); color: white; border-color: var(--sj-dark); font-weight: 700; }
.page-btn.nonaktif { color: var(--text-disabled); cursor: default; pointer-events: none; }
.page-ellipsis { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; color: var(--text-muted); }
.page-info { margin-left: auto; font-size: 13px; color: var(--text-muted); }
.table-scroll { overflow-x: auto; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.spinning { animation: spin 0.6s linear; }
@media (max-width: 768px) {
    .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
    .toolbar-new { flex-direction: column; align-items: stretch; }
    .search-wrapper { min-width: 100%; }
    .page-header { flex-direction: column; align-items: flex-start; }
    .form-card-body { padding: 18px; }
    .aksi-group { flex-wrap: wrap; }
}
@media (max-width: 480px) {
    .page-header-left h1 { font-size: 22px; }
    .pagination-new { justify-content: center; }
    .page-info { margin-left: 0; width: 100%; text-align: center; }
}
</style>

<?php if ($pesan): ?>
<div class="notif-modern <?= $tipe ?>" id="notif-box">
    <div class="notif-icon">
        <?php if ($tipe === 'sukses'): ?>
            <i class='bx bx-check' style="color:var(--green-text);font-size:17px;"></i>
        <?php else: ?>
            <i class='bx bx-x' style="color:var(--red-text);font-size:17px;"></i>
        <?php endif; ?>
    </div>
    <span><?= htmlspecialchars($pesan) ?></span>
    <button class="notif-close" onclick="document.getElementById('notif-box').style.display='none'">
        <i class='bx bx-x'></i>
    </button>
</div>
<?php endif; ?>

<?php if (!$mode_edit && !isset($_GET['tambah'])): ?>
<div class="page-header">
    <div class="page-header-left">
        <h1>Manajemen Artikel</h1>
        <div class="breadcrumb-text">
            <i class='bx bx-home-alt' style="font-size:13px;"></i>
            <span>Dashboard</span>
            <i class='bx bx-chevron-right' style="font-size:14px;"></i>
            <span style="color:var(--brown-600);font-weight:600;">Artikel</span>
        </div>
    </div>
    <a href="?page=artikel&tambah=1" class="btn-tambah-new">
        <i class='bx bx-plus'></i> Tambah Artikel Baru
    </a>
</div>
<?php endif; ?>

<?php if (isset($_GET['tambah']) || $mode_edit): ?>
<div class="page-header">
    <div class="page-header-left">
        <h1><?= $mode_edit ? 'Edit Artikel' : 'Tambah Artikel Baru' ?></h1>
        <div class="breadcrumb-text">
            <i class='bx bx-home-alt' style="font-size:13px;"></i>
            <span>Dashboard</span>
            <i class='bx bx-chevron-right' style="font-size:14px;"></i>
            <a href="?page=artikel" style="color:var(--text-muted);text-decoration:none;">Artikel</a>
            <i class='bx bx-chevron-right' style="font-size:14px;"></i>
            <span style="color:var(--brown-600);font-weight:600;"><?= $mode_edit ? 'Edit' : 'Tambah' ?></span>
        </div>
    </div>
</div>

<div class="form-card-new">
    <div class="form-card-header">
        <div class="header-icon">
            <i class='bx <?= $mode_edit ? "bx-edit" : "bx-file-plus" ?>'></i>
        </div>
        <h2><?= $mode_edit ? 'Edit Artikel' : 'Artikel Baru' ?></h2>
    </div>
    <div class="form-card-body">
        <form method="post" enctype="multipart/form-data" action="dashboardAdmin.php?page=artikel">
            <input type="hidden" name="aksi" value="<?= $mode_edit ? 'edit' : 'tambah' ?>">
            <?php if ($mode_edit): ?>
            <input type="hidden" name="id" value="<?= $data_edit['id'] ?>">
            <?php endif; ?>

            <?php
            $status_saat_ini = $data_edit['status'] ?? 'published';
            if (!in_array($status_saat_ini, ['published', 'draft'])) $status_saat_ini = 'published';
            ?>
            <input type="hidden" name="status_value" id="status_value_hidden" value="<?= $status_saat_ini ?>">

            <div class="form-section-label">Informasi Dasar</div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Judul Artikel <span class="required">*</span></label>
                    <input type="text" name="judul" required placeholder="Masukkan judul artikel..."
                           value="<?= htmlspecialchars($data_edit['judul'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Penulis</label>
                    <input type="text" name="penulis" placeholder="Nama penulis..."
                           value="<?= htmlspecialchars($data_edit['penulis'] ?? $_SESSION['nama_lengkap']) ?>"
                           <?= ($_SESSION['role'] === 'penulis') ? 'readonly style="background:#f0eae1;cursor:not-allowed;"' : '' ?>>
                    <?php if ($_SESSION['role'] === 'penulis'): ?>
                        <div class="form-hint" style="color:var(--sj-mid);">Nama penulis dikunci sesuai akun login.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Kategori <span class="required">*</span></label>
                    <select name="id_kategori" id="select_kategori" required
                            onchange="cekBiografi(this.value)">
                        <option value="">— Pilih Kategori —</option>
                        <?php foreach ($daftar_kategori as $kat): ?>
                        <option value="<?= $kat['id_kategori'] ?>"
                                data-slug="<?= htmlspecialchars($kat['slug_kategori']) ?>"
                            <?= ($data_edit['id_kategori'] ?? '') == $kat['id_kategori'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kat['nama_kategori']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Publikasi</label>
                    <select name="status" id="status_select"
                            onchange="document.getElementById('status_value_hidden').value = this.value">
                        <option value="published" <?= $status_saat_ini === 'published' ? 'selected' : '' ?>>
                            ✅ Published — Tayang
                        </option>
                        <option value="draft" <?= $status_saat_ini === 'draft' ? 'selected' : '' ?>>
                            📝 Draft — Disimpan
                        </option>
                    </select>
                </div>
            </div>

            <!-- ════ SECTION BIOGRAFI — muncul otomatis jika kategori = biografi ════ -->
            <div class="bio-section" id="bio-section">
                <div class="bio-section-title">
                    <i class='bx bx-user-circle'></i>
                    Info Pribadi Tokoh — ditampilkan di card biografi
                </div>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="text" name="bio_lahir" id="bio_lahir"
                               placeholder="cth: 2 Mei 1889"
                               value="<?= htmlspecialchars($bio_edit['lahir']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Meninggal</label>
                        <input type="text" name="bio_meninggal" id="bio_meninggal"
                               placeholder="cth: 26 April 1959 (umur 69)"
                               value="<?= htmlspecialchars($bio_edit['meninggal']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Pekerjaan / Profesi</label>
                        <input type="text" name="bio_pekerjaan" id="bio_pekerjaan"
                               placeholder="cth: Pendidik, Pahlawan Nasional"
                               value="<?= htmlspecialchars($bio_edit['pekerjaan']) ?>">
                    </div>
                </div>
                <div class="bio-preview-card">
                    <i class='bx bx-info-circle'></i>
                    <span>Data ini ditampilkan di card <strong>Info Pribadi</strong> pada halaman detail artikel biografi. Kosongkan field yang tidak diketahui — akan tampil sebagai tanda <strong>—</strong>.</span>
                </div>
            </div>

            <div class="form-section-label" style="margin-top:10px;">Konten & Media</div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Cover / Thumbnail</label>
                    <input type="file" name="thumbnail" accept="image/*" onchange="previewGambar(this)">
                    <div class="form-hint">Format: JPG, PNG, GIF, WebP. Maks. 5MB.</div>
                    <?php if ($mode_edit && !empty($data_edit['thumbnail'])): ?>
                        <div style="margin-top:6px;font-size:12px;color:var(--text-muted);">Cover saat ini:</div>
                        <img src="<?= htmlspecialchars(getImgSrc($data_edit['thumbnail'])) ?>"
                             id="preview-img" class="preview-img-new"
                             onerror="this.style.display='none'">
                    <?php else: ?>
                        <img id="preview-img" class="preview-img-new" style="display:none;">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Teks Preview</label>
                    <textarea name="preview" id="textarea-preview"
                              placeholder="Ringkasan singkat artikel (maks. 3 kalimat)..."><?= htmlspecialchars($preview_teks_edit) ?></textarea>
                </div>
            </div>

            <div class="form-grid-1">
                <div class="form-group">
                    <label>Isi Artikel Lengkap <span class="required">*</span></label>
                    <div class="form-hint" style="margin-bottom:6px;">
                        Gunakan tag &lt;p&gt; untuk setiap paragraf.
                    </div>
                    <textarea name="konten" id="konten-textarea" required
                        placeholder="<p>Paragraf pertama...</p>&#10;<p>Paragraf kedua...</p>"><?= htmlspecialchars($data_edit['konten'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn-simpan-new">
                    <i class='bx <?= $mode_edit ? "bx-save" : "bx-check" ?>'></i>
                    <?= $mode_edit ? 'Simpan Perubahan' : 'Tambah Artikel' ?>
                </button>
                <a href="?page=artikel" class="btn-batal-new">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (!$mode_edit && !isset($_GET['tambah'])): ?>
<div class="tabel-card-new">
    <div class="tabel-card-header">
        <div class="tabel-card-header-top">
            <div class="tabel-card-title">
                <div class="title-icon"><i class='bx bx-list-ul'></i></div>
                Daftar Artikel
                <span class="count-badge"><?= $total_data ?> Data</span>
            </div>
        </div>

        <form method="get" action="">
            <input type="hidden" name="page" value="artikel">
            <div class="toolbar-new">
                <div class="search-wrapper">
                    <i class='bx bx-search'></i>
                    <input type="text" name="cari" class="search-input-new"
                           placeholder="Cari judul atau penulis..."
                           value="<?= htmlspecialchars($cari) ?>">
                </div>
                <select name="kategori" class="select-filter" onchange="this.form.submit()">
                    <option value="">— Semua Kategori —</option>
                    <?php foreach ($daftar_kategori as $kat): ?>
                    <option value="<?= htmlspecialchars($kat['slug_kategori']) ?>"
                        <?= $filter_kat === $kat['slug_kategori'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="select-filter" onchange="this.form.submit()">
                    <option value="">— Semua Status —</option>
                    <option value="published" <?= $filter_st === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft"     <?= $filter_st === 'draft'     ? 'selected' : '' ?>>Draft</option>
                </select>
                <button type="submit" style="display:none;">Cari</button>
                <a href="?page=artikel" class="btn-refresh" id="btn-refresh-anim">
                    <i class='bx bx-refresh'></i> Reset Filter
                </a>
            </div>
        </form>
    </div>

    <?php if ($cari !== '' || $filter_kat !== '' || $filter_st !== ''): ?>
    <div class="info-total-new">
        <i class='bx bx-info-circle'></i> Menampilkan hasil pencarian:
        <?php if ($cari !== '')       echo " Kata kunci '<strong>" . htmlspecialchars($cari) . "</strong>';"; ?>
        <?php if ($filter_kat !== '') echo " Kategori '<strong>"   . htmlspecialchars($filter_kat) . "</strong>';"; ?>
        <?php if ($filter_st !== '')  echo " Status '<strong>"     . htmlspecialchars($filter_st) . "</strong>';"; ?>
    </div>
    <?php endif; ?>

    <div class="table-scroll">
        <table class="artikel-table-new">
            <thead>
                <tr>
                    <th style="width:50px;text-align:center;">No</th>
                    <th style="width:70px;">Cover</th>
                    <th><a href="<?= buildUrl(['sort'=>'judul','dir'=>$sort_next]) ?>">Judul Artikel <?php if ($sort_by==='judul') echo $sort_dir==='ASC' ? "<i class='bx bx-chevron-up'></i>" : "<i class='bx bx-chevron-down'></i>"; ?></a></th>
                    <th><a href="<?= buildUrl(['sort'=>'kategori','dir'=>$sort_next]) ?>">Kategori <?php if ($sort_by==='kategori') echo $sort_dir==='ASC' ? "<i class='bx bx-chevron-up'></i>" : "<i class='bx bx-chevron-down'></i>"; ?></a></th>
                    <th><a href="<?= buildUrl(['sort'=>'penulis','dir'=>$sort_next]) ?>">Penulis <?php if ($sort_by==='penulis') echo $sort_dir==='ASC' ? "<i class='bx bx-chevron-up'></i>" : "<i class='bx bx-chevron-down'></i>"; ?></a></th>
                    <th><a href="<?= buildUrl(['sort'=>'created_at','dir'=>$sort_next]) ?>">Tanggal <?php if ($sort_by==='created_at') echo $sort_dir==='ASC' ? "<i class='bx bx-chevron-up'></i>" : "<i class='bx bx-chevron-down'></i>"; ?></a></th>
                    <th><a href="<?= buildUrl(['sort'=>'status','dir'=>$sort_next]) ?>">Status <?php if ($sort_by==='status') echo $sort_dir==='ASC' ? "<i class='bx bx-chevron-up'></i>" : "<i class='bx bx-chevron-down'></i>"; ?></a></th>
                    <th style="width:140px;text-align:right;padding-right:24px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($artikel_list)): ?>
                <tr><td colspan="8">
                    <div class="no-data-new">
                        <i class='bx bx-folder-open no-data-icon'></i>
                        <p>Tidak ada artikel yang ditemukan</p>
                        <small>Silakan tambah artikel baru atau ubah kata kunci pencarian.</small>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php $no = $offset + 1; foreach ($artikel_list as $art): ?>
                <tr>
                    <td class="row-num" style="text-align:center;"><?= $no++ ?></td>
                    <td>
                        <div class="thumb-wrap">
                            <?php if (!empty($art['thumbnail'])): ?>
                                <img src="<?= htmlspecialchars(getImgSrc($art['thumbnail'])) ?>"
                                     alt="Cover"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <i class='bx bx-image thumb-placeholder' style="display:none;"></i>
                            <?php else: ?>
                                <i class='bx bx-image thumb-placeholder'></i>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><div class="judul-cell" title="<?= htmlspecialchars($art['judul']) ?>"><?= htmlspecialchars($art['judul']) ?></div></td>
                    <td>
                        <?php if (!empty($art['nama_kategori'])): ?>
                            <span class="badge-new" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;">
                                <i class='bx bx-folder'></i> <?= htmlspecialchars($art['nama_kategori']) ?>
                            </span>
                        <?php else: ?>
                            <span style="color:#9ca3af;font-size:12px;">— Tanpa Kategori —</span>
                        <?php endif; ?>
                    </td>
                    <td class="cell-muted"><?= htmlspecialchars($art['penulis']) ?></td>
                    <td class="cell-date"><?= date('d M Y', strtotime($art['created_at'])) ?></td>
                    <td>
                        <?php if ($art['status'] === 'published'): ?>
                            <span class="badge-new status-published"><i class='bx bx-check-circle'></i> Published</span>
                        <?php else: ?>
                            <span class="badge-new status-draft"><i class='bx bx-edit-alt'></i> Draft</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding-right:24px;">
                        <div class="aksi-group">
                            <a href="?page=artikel&edit=<?= $art['id'] ?>" class="aksi-btn-new btn-edit-new">
                                <i class='bx bx-edit'></i> Edit
                            </a>
                            <a href="?page=artikel&hapus=<?= $art['id'] ?>" class="aksi-btn-new btn-hapus-new"
                               onclick="return confirm('Yakin ingin menghapus artikel ini? Tindakan ini tidak bisa dibatalkan.')">
                                <i class='bx bx-trash'></i> Hapus
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_page > 1): ?>
    <div class="pagination-new">
        <?php if ($halaman > 1): ?>
            <a href="<?= buildUrl(['halaman' => $halaman - 1]) ?>" class="page-btn"><i class='bx bx-chevron-left'></i></a>
        <?php else: ?>
            <span class="page-btn nonaktif"><i class='bx bx-chevron-left'></i></span>
        <?php endif; ?>
        <?php
        $start_page = max(1, $halaman - 2);
        $end_page   = min($total_page, $halaman + 2);
        if ($start_page > 1) {
            echo '<a href="' . buildUrl(['halaman' => 1]) . '" class="page-btn">1</a>';
            if ($start_page > 2) echo '<span class="page-ellipsis">...</span>';
        }
        for ($i = $start_page; $i <= $end_page; $i++) {
            $aktif = ($i === $halaman) ? 'aktif' : '';
            echo '<a href="' . buildUrl(['halaman' => $i]) . '" class="page-btn ' . $aktif . '">' . $i . '</a>';
        }
        if ($end_page < $total_page) {
            if ($end_page < $total_page - 1) echo '<span class="page-ellipsis">...</span>';
            echo '<a href="' . buildUrl(['halaman' => $total_page]) . '" class="page-btn">' . $total_page . '</a>';
        }
        ?>
        <?php if ($halaman < $total_page): ?>
            <a href="<?= buildUrl(['halaman' => $halaman + 1]) ?>" class="page-btn"><i class='bx bx-chevron-right'></i></a>
        <?php else: ?>
            <span class="page-btn nonaktif"><i class='bx bx-chevron-right'></i></span>
        <?php endif; ?>
        <div class="page-info">
            Halaman <strong><?= $halaman ?></strong> dari <strong><?= $total_page ?></strong>
            (Total <strong><?= $total_data ?></strong> Artikel)
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
function previewGambar(input) {
    const preview = document.getElementById('preview-img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

// ── Tampilkan/sembunyikan section biografi berdasarkan kategori ──
function cekBiografi(id_kategori) {
    const sel = document.getElementById('select_kategori');
    const opt = sel ? sel.options[sel.selectedIndex] : null;
    const slug = opt ? (opt.dataset.slug || '').toLowerCase() : '';

    const bioSection = document.getElementById('bio-section');

    if (slug === 'biografi') {
        bioSection.classList.add('tampil');
    } else {
        bioSection.classList.remove('tampil');
    }
}

// Jalankan saat halaman load (penting untuk mode edit)
document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('select_kategori');
    if (sel) cekBiografi(sel.value);
});

document.getElementById('btn-refresh-anim')?.addEventListener('click', function () {
    const icon = this.querySelector('.bx');
    icon.classList.add('spinning');
    setTimeout(() => icon.classList.remove('spinning'), 600);
});
</script>