<?php
// ============================================================
// pages/artikel.php  —  Manajemen Artikel (CRUD lengkap)
// ============================================================
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../cek_admin.php';

$pesan = '';
$tipe  = '';

function buatSlug($judul) {
    $slug = strtolower(trim($judul));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return $slug;
}

function uploadGambar($file, $thumbnail_lama = '') {
    $upload_dir = __DIR__ . '/../uploads/';
    $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed    = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) return ['error' => 'Format gambar tidak didukung.'];
    if ($file['size'] > 5 * 1024 * 1024) return ['error' => 'Ukuran gambar maksimal 5MB.'];
    $nama_baru = 'artikel_' . time() . '_' . rand(100,999) . '.' . $ext;
    $path_tuju = $upload_dir . $nama_baru;
    if (!move_uploaded_file($file['tmp_name'], $path_tuju)) return ['error' => 'Gagal upload gambar.'];
    if ($thumbnail_lama && file_exists($upload_dir . $thumbnail_lama) && strpos($thumbnail_lama, 'artikel_') === 0) {
        unlink($upload_dir . $thumbnail_lama);
    }
    return ['nama' => $nama_baru];
}

function getImgSrc($thumb) {
    if (empty($thumb)) return '';
    if (strpos($thumb, 'artikel_') === 0) {
        return '/website/uploads/' . $thumb;
    }
    return '/website/' . $thumb;
}

// ═══════════════════════════════════════════════════════════
//  PROSES TAMBAH ARTIKEL
// ═══════════════════════════════════════════════════════════
if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    $judul    = trim($_POST['judul']);
    $kategori = $_POST['kategori'];
    $status   = $_POST['status'];
    $penulis  = trim($_POST['penulis']) ?: 'Admin';
    $preview  = trim($_POST['preview']);
    $konten   = trim($_POST['konten']);
    $slug     = buatSlug($judul);

    $cek_slug = $conn->prepare("SELECT id FROM tb_artikel WHERE slug = ?");
    $cek_slug->bind_param("s", $slug);
    $cek_slug->execute();
    if ($cek_slug->get_result()->num_rows > 0) {
        $slug = $slug . '-' . time();
    }

    $thumbnail = '';
    if (!empty($_FILES['thumbnail']['name'])) {
        $hasil = uploadGambar($_FILES['thumbnail']);
        if (isset($hasil['error'])) { $pesan = $hasil['error']; $tipe = 'error'; }
        else { $thumbnail = $hasil['nama']; }
    }

    if ($tipe !== 'error') {
        $stmt = $conn->prepare("INSERT INTO tb_artikel (judul, slug, konten, preview, penulis, thumbnail, kategori, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $admin_id = $_SESSION['id'] ?? 1;
        $stmt->bind_param("ssssssssi", $judul, $slug, $konten, $preview, $penulis, $thumbnail, $kategori, $status, $admin_id);
        if ($stmt->execute()) { $pesan = 'Artikel berhasil ditambahkan!'; $tipe = 'sukses'; }
        else { $pesan = 'Gagal: ' . $stmt->error; $tipe = 'error'; }
    }
}

// ═══════════════════════════════════════════════════════════
//  PROSES EDIT ARTIKEL
// ═══════════════════════════════════════════════════════════
if (isset($_POST['aksi']) && $_POST['aksi'] === 'edit') {
    $id       = (int)$_POST['id'];
    $judul    = trim($_POST['judul']);
    $kategori = $_POST['kategori'];
    $status   = $_POST['status'];
    $penulis  = trim($_POST['penulis']) ?: 'Admin';
    $preview  = trim($_POST['preview']);
    $konten   = trim($_POST['konten']);

    $lama      = $conn->query("SELECT thumbnail FROM tb_artikel WHERE id=$id")->fetch_assoc();
    $thumbnail = $lama['thumbnail'];

    if (!empty($_FILES['thumbnail']['name'])) {
        $hasil = uploadGambar($_FILES['thumbnail'], $thumbnail);
        if (isset($hasil['error'])) { $pesan = $hasil['error']; $tipe = 'error'; }
        else { $thumbnail = $hasil['nama']; }
    }

    if ($tipe !== 'error') {
        $stmt = $conn->prepare("UPDATE tb_artikel SET judul=?, konten=?, preview=?, penulis=?, thumbnail=?, kategori=?, status=? WHERE id=?");
        $stmt->bind_param("sssssssi", $judul, $konten, $preview, $penulis, $thumbnail, $kategori, $status, $id);
        if ($stmt->execute()) { $pesan = 'Artikel berhasil diperbarui!'; $tipe = 'sukses'; }
        else { $pesan = 'Gagal: ' . $stmt->error; $tipe = 'error'; }
    }
}

// ═══════════════════════════════════════════════════════════
//  PROSES HAPUS ARTIKEL
// ═══════════════════════════════════════════════════════════
if (isset($_GET['hapus'])) {
    $id   = (int)$_GET['hapus'];
    $lama = $conn->query("SELECT thumbnail FROM tb_artikel WHERE id=$id")->fetch_assoc();
    if ($lama && $lama['thumbnail'] && strpos($lama['thumbnail'], 'artikel_') === 0) {
        $path_gambar = __DIR__ . '/../uploads/' . $lama['thumbnail'];
        if (file_exists($path_gambar)) unlink($path_gambar);
    }
    $conn->query("DELETE FROM tb_favorit  WHERE artikel_id=$id");
    $conn->query("DELETE FROM tb_ulasan   WHERE artikel_id=$id");
    $conn->query("DELETE FROM tb_history  WHERE artikel_id=$id");
    $conn->query("DELETE FROM tb_artikel  WHERE id=$id");
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

// ═══════════════════════════════════════════════════════════
//  AMBIL DAFTAR ARTIKEL (search, filter, sort, pagination)
// ═══════════════════════════════════════════════════════════
$cari        = isset($_GET['cari'])     ? trim($_GET['cari'])          : '';
$filter_kat  = isset($_GET['kategori']) ? $_GET['kategori']            : '';
$filter_st   = isset($_GET['status'])   ? $_GET['status']              : '';
$sort_by     = isset($_GET['sort'])     ? $_GET['sort']                : 'created_at';
$sort_dir    = isset($_GET['dir'])      ? $_GET['dir']                 : 'DESC';
$halaman     = isset($_GET['halaman'])  ? max(1,(int)$_GET['halaman']) : 1;
$per_halaman = 10;

$sort_allowed = ['judul','kategori','status','penulis','created_at','view_count'];
if (!in_array($sort_by, $sort_allowed)) $sort_by = 'created_at';
$sort_dir  = $sort_dir === 'ASC' ? 'ASC' : 'DESC';
$sort_next = $sort_dir === 'ASC' ? 'DESC' : 'ASC';

$where  = "WHERE 1=1";
$params = [];
$types  = "";
if ($cari !== '') {
    $cari_like = '%' . $cari . '%';
    $where .= " AND (judul LIKE ? OR penulis LIKE ?)";
    $params[] = $cari_like; $params[] = $cari_like; $types .= "ss";
}
if ($filter_kat !== '') { $where .= " AND kategori = ?"; $params[] = $filter_kat; $types .= "s"; }
if ($filter_st  !== '') { $where .= " AND status = ?";   $params[] = $filter_st;  $types .= "s"; }

$q_total = $conn->prepare("SELECT COUNT(*) as total FROM tb_artikel $where");
if ($params) $q_total->bind_param($types, ...$params);
$q_total->execute();
$total_data  = $q_total->get_result()->fetch_assoc()['total'];
$total_page  = ceil($total_data / $per_halaman);
$offset      = ($halaman - 1) * $per_halaman;

$q_data        = $conn->prepare("SELECT * FROM tb_artikel $where ORDER BY $sort_by $sort_dir LIMIT ? OFFSET ?");
$params_data   = $params;
$params_data[] = $per_halaman;
$params_data[] = $offset;
$types_data    = $types . "ii";
$q_data->bind_param($types_data, ...$params_data);
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
    --sj-dark:    #4A2C18;
    --sj-darker:  #4D1E0A;
    --sj-mid:     #7B4F2C;
    --sj-gold:    #D99B3E;
    --sj-sand:    #AD8D77;
    --sj-cream:   #F8F8DC;
    --brown-900: #3a1f0d;
    --brown-800: #4D1E0A;
    --brown-700: #4A2C18;
    --brown-600: #7B4F2C;
    --brown-500: #9a6540;
    --brown-400: #AD8D77;
    --brown-300: #c8ab93;
    --brown-200: #dfc8b0;
    --brown-100: #f0e4d2;
    --brown-50:  #faf5ec;
    --cream-bg:  #F8F8DC;
    --white:     #ffffff;
    --surface:   #ffffff;
    --surface-2: #fdfcf6;
    --text-primary:   #2a1508;
    --text-secondary: #4A2C18;
    --text-muted:     #7B4F2C;
    --text-disabled:  #AD8D77;
    --border:      #e2d0b8;
    --border-soft: #ede4d3;
    --shadow-sm:  0 1px 3px rgba(74,44,24,0.07), 0 1px 2px rgba(74,44,24,0.05);
    --shadow-md:  0 4px 16px rgba(74,44,24,0.10), 0 2px 6px rgba(74,44,24,0.05);
    --shadow-lg:  0 8px 32px rgba(74,44,24,0.13), 0 4px 12px rgba(74,44,24,0.07);
    --radius-sm:  6px;
    --radius-md:  10px;
    --radius-lg:  14px;
    --radius-xl:  18px;
    --transition:      all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    --font-heading: 'Montserrat', sans-serif;
    --font-body:    'Roboto', sans-serif;
    --green-bg:   #ecfdf5; --green-text: #065f46; --green-border: #a7f3d0;
    --yellow-bg:  #fef9ee; --yellow-text: #7a4a00; --yellow-border: #f5d07a;
    --blue-bg:    #eff6ff; --blue-text:   #1d4ed8; --blue-border:  #bfdbfe;
    --purple-bg:  #f5f3ff; --purple-text: #5b21b6; --purple-border: #ddd6fe;
    --red-bg:     #fff1f2; --red-text:    #be123c;  --red-border:   #fda4af;
}

.artikel-page * { box-sizing: border-box; }
.artikel-page { font-family: var(--font-body); color: var(--text-primary); }

.notif-modern {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px; border-radius: var(--radius-md);
    margin-bottom: 20px; font-weight: 500; font-size: 14px;
    animation: slideDown 0.3s ease; box-shadow: var(--shadow-sm);
}
.notif-modern.sukses { background: var(--green-bg); color: var(--green-text); border: 1px solid var(--green-border); }
.notif-modern.error  { background: var(--red-bg);   color: var(--red-text);   border: 1px solid var(--red-border); }
.notif-modern .notif-icon {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
}
.notif-modern.sukses .notif-icon { background: #d1fae5; }
.notif-modern.error  .notif-icon { background: #ffe4e6; }
.notif-modern .notif-close {
    margin-left: auto; cursor: pointer; opacity: 0.6;
    transition: var(--transition); padding: 4px; border-radius: 4px;
    border: none; background: none; font-size: 16px; color: inherit;
}
.notif-modern .notif-close:hover { opacity: 1; background: rgba(0,0,0,0.06); }

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}

.page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.page-header-left h1 {
    font-family: var(--font-heading); font-size: 26px; font-weight: 800;
    color: var(--sj-darker); margin: 0 0 4px 0; letter-spacing: -0.4px;
}
.page-header-left .breadcrumb-text {
    font-size: 13px; color: var(--text-muted);
    display: flex; align-items: center; gap: 6px;
}
.btn-tambah-new {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--sj-dark); color: var(--white);
    padding: 10px 20px; border-radius: var(--radius-md);
    font-size: 14px; font-weight: 700; text-decoration: none;
    border: none; cursor: pointer; transition: var(--transition);
    box-shadow: 0 2px 8px rgba(74,44,24,0.28); white-space: nowrap;
    font-family: var(--font-heading); letter-spacing: 0.2px;
}
.btn-tambah-new:hover {
    background: var(--sj-darker); transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(77,30,10,0.36); color: var(--white); text-decoration: none;
}
.btn-tambah-new:active { transform: translateY(0); }

.form-card-new {
    background: var(--white); border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md); border: 1px solid var(--border-soft);
    margin-bottom: 24px; overflow: hidden; animation: fadeInUp 0.3s ease;
}
.form-card-header {
    background: linear-gradient(135deg, var(--sj-darker) 0%, var(--sj-dark) 55%, var(--sj-mid) 100%);
    padding: 20px 28px; display: flex; align-items: center; gap: 12px;
}
.form-card-header .header-icon {
    width: 38px; height: 38px; background: rgba(255,255,255,0.15);
    border-radius: var(--radius-sm); display: flex; align-items: center;
    justify-content: center; font-size: 18px; color: white;
}
.form-card-header h2 {
    font-family: var(--font-heading); color: white; font-size: 17px;
    font-weight: 700; margin: 0; letter-spacing: 0.2px;
}
.form-card-body { padding: 28px; }
.form-section-label {
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1px; color: var(--sj-sand); margin-bottom: 14px;
    margin-top: 4px; padding-bottom: 8px; border-bottom: 1px solid var(--border-soft);
    font-family: var(--font-heading);
}
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
.form-grid-1 { display: grid; grid-template-columns: 1fr; gap: 18px; margin-bottom: 18px; }
.form-group label {
    display: block; font-size: 13px; font-weight: 600;
    color: var(--text-secondary); margin-bottom: 6px; letter-spacing: 0.1px;
}
.form-group label .required { color: #dc2626; margin-left: 2px; }
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%; padding: 10px 14px; border: 1.5px solid var(--border);
    border-radius: var(--radius-md); font-size: 14px; font-family: var(--font-body);
    color: var(--text-primary); background: var(--surface-2);
    transition: var(--transition); outline: none;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--sj-mid); background: var(--white);
    box-shadow: 0 0 0 3px rgba(123,79,44,0.12);
}
.form-group input::placeholder,
.form-group textarea::placeholder { color: var(--text-disabled); }
.form-group textarea { resize: vertical; min-height: 100px; line-height: 1.6; }
#konten-textarea { min-height: 280px; font-size: 13.5px; }
.form-hint { font-size: 12px; color: var(--text-muted); margin-top: 5px; }
.preview-img-new {
    max-width: 140px; max-height: 90px; border-radius: var(--radius-md);
    margin-top: 10px; object-fit: cover; border: 2px solid var(--border);
    box-shadow: var(--shadow-sm);
}
.form-footer {
    display: flex; align-items: center; gap: 10px;
    padding-top: 8px; border-top: 1px solid var(--border-soft); margin-top: 4px;
}
.btn-simpan-new {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--sj-dark); color: white; padding: 11px 24px;
    border-radius: var(--radius-md); font-size: 14px; font-weight: 700;
    font-family: var(--font-heading); letter-spacing: 0.2px; border: none;
    cursor: pointer; transition: var(--transition);
    box-shadow: 0 2px 8px rgba(74,44,24,0.22);
}
.btn-simpan-new:hover { background: var(--sj-darker); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(77,30,10,0.30); }
.btn-batal-new {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--brown-100); color: var(--sj-dark); padding: 11px 20px;
    border-radius: var(--radius-md); font-size: 14px; font-weight: 600;
    font-family: var(--font-heading); text-decoration: none;
    border: 1px solid var(--brown-200); transition: var(--transition);
}
.btn-batal-new:hover { background: var(--brown-200); color: var(--sj-darker); text-decoration: none; }

.tabel-card-new {
    background: var(--white); border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md); border: 1px solid var(--border-soft);
    overflow: hidden; animation: fadeInUp 0.3s ease;
}
.tabel-card-header { padding: 20px 24px 0 24px; border-bottom: 1px solid var(--border-soft); }
.tabel-card-header-top {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px; flex-wrap: wrap; gap: 10px;
}
.tabel-card-title {
    font-size: 16px; font-weight: 700; color: var(--sj-darker);
    display: flex; align-items: center; gap: 8px; font-family: var(--font-heading);
}
.tabel-card-title .title-icon {
    width: 30px; height: 30px; background: var(--brown-100);
    border-radius: var(--radius-sm); display: flex; align-items: center;
    justify-content: center; color: var(--sj-mid); font-size: 14px;
}
.count-badge {
    display: inline-flex; align-items: center; justify-content: center;
    background: #fef3d6; color: #7a4a00; font-size: 11px; font-weight: 700;
    padding: 2px 9px; border-radius: 20px; border: 1px solid #f5d07a;
    font-family: var(--font-body);
}

.toolbar-new {
    display: flex; align-items: center; gap: 10px; padding: 16px 0; flex-wrap: wrap;
}
.search-wrapper { flex: 1; min-width: 220px; position: relative; }
.search-wrapper .bx {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); font-size: 17px; pointer-events: none;
}
.search-input-new {
    width: 100%; padding: 10px 14px 10px 38px; border: 1.5px solid var(--border);
    border-radius: var(--radius-md); font-size: 14px; font-family: var(--font-body);
    background: var(--surface-2); color: var(--text-primary);
    transition: var(--transition); outline: none;
}
.search-input-new:focus {
    border-color: var(--sj-mid); background: white;
    box-shadow: 0 0 0 3px rgba(123,79,44,0.10);
}
.select-filter {
    padding: 10px 14px; border: 1.5px solid var(--border);
    border-radius: var(--radius-md); font-size: 13.5px; font-family: var(--font-body);
    background: var(--surface-2); color: var(--text-secondary);
    transition: var(--transition); outline: none; cursor: pointer; min-width: 140px;
}
.select-filter:focus {
    border-color: var(--sj-mid); background: white;
    box-shadow: 0 0 0 3px rgba(123,79,44,0.10);
}
.btn-refresh {
    display: inline-flex; align-items: center; gap: 7px;
    background: var(--white); color: var(--sj-dark); padding: 10px 16px;
    border-radius: var(--radius-md); font-size: 13.5px; font-weight: 600;
    font-family: var(--font-heading); border: 1.5px solid var(--sj-sand);
    cursor: pointer; transition: var(--transition); white-space: nowrap;
}
.btn-refresh:hover { background: var(--brown-50); border-color: var(--sj-mid); color: var(--sj-darker); }
.btn-refresh .bx { font-size: 16px; }

.info-total-new {
    font-size: 13px; color: var(--text-muted); padding: 10px 24px;
    background: var(--brown-50); border-bottom: 1px solid var(--border-soft);
    display: flex; align-items: center; gap: 6px;
}
.info-total-new strong { color: var(--sj-dark); }

.artikel-table-new { width: 100%; border-collapse: collapse; min-width: 720px; }
.artikel-table-new thead tr { background: var(--brown-50); }
.artikel-table-new th {
    padding: 12px 16px; text-align: left; font-size: 11.5px;
    text-transform: uppercase; letter-spacing: 0.7px; color: var(--text-muted);
    font-weight: 700; white-space: nowrap; border-bottom: 2px solid var(--border);
}
.artikel-table-new th a {
    color: var(--text-muted); text-decoration: none;
    display: inline-flex; align-items: center; gap: 5px;
    transition: var(--transition); font-family: var(--font-heading);
}
.artikel-table-new th a:hover { color: var(--sj-dark); }
.artikel-table-new th a .sort-icon { font-size: 13px; }
.artikel-table-new td {
    padding: 13px 16px; font-size: 14px; border-bottom: 1px solid var(--border-soft);
    vertical-align: middle; transition: var(--transition);
}
.artikel-table-new tbody tr { transition: var(--transition); }
.artikel-table-new tbody tr:hover td { background: var(--brown-50); }
.artikel-table-new tbody tr:last-child td { border-bottom: none; }

.thumb-wrap {
    width: 52px; height: 40px; border-radius: var(--radius-sm); overflow: hidden;
    background: var(--brown-100); display: flex; align-items: center;
    justify-content: center; flex-shrink: 0; border: 1px solid var(--border);
}
.thumb-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
.thumb-placeholder { color: var(--brown-400); font-size: 18px; }

.judul-cell {
    font-weight: 600; color: var(--text-primary); font-size: 14px;
    font-family: var(--font-heading); white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis; max-width: 220px;
}

.badge-new {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: 20px; font-size: 11.5px;
    font-weight: 700; letter-spacing: 0.1px; white-space: nowrap;
}
.badge-new .bx { font-size: 11px; }
.badge-new.status-published { background: var(--green-bg); color: var(--green-text); border: 1px solid var(--green-border); }
.badge-new.status-draft     { background: var(--yellow-bg); color: var(--yellow-text); border: 1px solid var(--yellow-border); }
.badge-new.kat-sejarah  { background: #fef3d6; color: #7a4a00; border: 1px solid #f5d07a; }
.badge-new.kat-biografi { background: var(--brown-100); color: var(--sj-dark); border: 1px solid var(--brown-200); }

.row-num { font-size: 12px; color: var(--text-disabled); font-weight: 500; font-variant-numeric: tabular-nums; }
.cell-muted  { color: var(--text-muted); font-size: 13.5px; }
.cell-date   { color: var(--text-muted); font-size: 13px; white-space: nowrap; }
.cell-views  { color: var(--text-muted); font-size: 13.5px; font-variant-numeric: tabular-nums; }

.aksi-group { display: flex; align-items: center; gap: 5px; justify-content: flex-end; }
.aksi-btn-new {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 11px; border-radius: var(--radius-sm); font-size: 12.5px;
    font-weight: 600; font-family: var(--font-body); cursor: pointer;
    border: 1px solid transparent; text-decoration: none;
    transition: var(--transition); white-space: nowrap; line-height: 1;
}
.aksi-btn-new .bx { font-size: 13px; }
.btn-edit-new  { background: var(--yellow-bg); color: var(--yellow-text); border-color: var(--yellow-border); }
.btn-edit-new:hover  { background: #fef3c7; color: #78350f; transform: translateY(-1px); text-decoration: none; }
.btn-hapus-new { background: var(--red-bg); color: var(--red-text); border-color: var(--red-border); }
.btn-hapus-new:hover { background: #ffe4e6; color: #be123c; transform: translateY(-1px); text-decoration: none; }

.no-data-new { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.no-data-new .no-data-icon { font-size: 48px; color: var(--brown-200); display: block; margin-bottom: 12px; }
.no-data-new p { font-size: 15px; margin: 0; }
.no-data-new small { font-size: 13px; color: var(--text-disabled); margin-top: 4px; display: block; }

.pagination-new {
    display: flex; align-items: center; gap: 5px; padding: 16px 24px;
    border-top: 1px solid var(--border-soft); background: var(--brown-50); flex-wrap: wrap;
}
.page-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 36px; height: 36px; padding: 0 10px; border-radius: var(--radius-sm);
    font-size: 13.5px; font-weight: 500; font-family: var(--font-body);
    text-decoration: none; border: 1.5px solid var(--border);
    color: var(--text-secondary); background: var(--white); transition: var(--transition); cursor: pointer;
}
.page-btn:hover { border-color: var(--sj-sand); color: var(--sj-dark); background: var(--brown-50); text-decoration: none; }
.page-btn.aktif { background: var(--sj-dark); color: white; border-color: var(--sj-dark); font-weight: 700; box-shadow: 0 2px 6px rgba(74,44,24,0.28); }
.page-btn.nonaktif { color: var(--text-disabled); cursor: default; pointer-events: none; }
.page-ellipsis { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; color: var(--text-muted); font-size: 14px; }
.page-info { margin-left: auto; font-size: 13px; color: var(--text-muted); }
.table-scroll { overflow-x: auto; }

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.spinning { animation: spin 0.6s linear; }

@media (max-width: 768px) {
    .form-grid-2 { grid-template-columns: 1fr; }
    .toolbar-new { flex-direction: column; align-items: stretch; }
    .search-wrapper { min-width: 100%; }
    .select-filter { min-width: 0; }
    .page-header { flex-direction: column; align-items: flex-start; }
    .form-card-body { padding: 18px; }
    .aksi-group { flex-wrap: wrap; }
    .tabel-card-header-top { flex-direction: column; align-items: flex-start; }
    .page-info { margin-left: 0; }
}
@media (max-width: 480px) {
    .page-header-left h1 { font-size: 22px; }
    .pagination-new { justify-content: center; }
}
</style>

<div class="artikel-page">

<!-- NOTIFIKASI -->
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

<!-- PAGE HEADER (mode list) -->
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
        <i class='bx bx-plus'></i>
        Tambah Artikel Baru
    </a>
</div>
<?php endif; ?>

<!-- FORM TAMBAH / EDIT -->
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
        <form method="post" enctype="multipart/form-data" action="">
            <input type="hidden" name="aksi" value="<?= $mode_edit ? 'edit' : 'tambah' ?>">
            <?php if ($mode_edit): ?>
            <input type="hidden" name="id" value="<?= $data_edit['id'] ?>">
            <?php endif; ?>

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
                        value="<?= htmlspecialchars($data_edit['penulis'] ?? 'Admin') ?>">
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Kategori <span class="required">*</span></label>
                    <select name="kategori" required>
                        <option value="">— Pilih Kategori —</option>
                        <option value="sejarah"  <?= ($data_edit['kategori'] ?? '') === 'sejarah'  ? 'selected' : '' ?>>Sejarah</option>
                        <option value="biografi" <?= ($data_edit['kategori'] ?? '') === 'biografi' ? 'selected' : '' ?>>Biografi Tokoh</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Publikasi</label>
                    <select name="status">
                        <option value="published" <?= ($data_edit['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published — Tayang</option>
                        <option value="draft"     <?= ($data_edit['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft — Disimpan</option>
                    </select>
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
                    <textarea name="preview" placeholder="Ringkasan singkat artikel (maks. 3 kalimat)..."><?= htmlspecialchars($data_edit['preview'] ?? '') ?></textarea>
                    <div class="form-hint">Ditampilkan di halaman daftar artikel.</div>
                </div>
            </div>

            <div class="form-grid-1">
                <div class="form-group">
                    <label>Isi Artikel Lengkap <span class="required">*</span></label>
                    <div class="form-hint" style="margin-bottom:6px;">Gunakan tag &lt;p&gt; untuk setiap paragraf.</div>
                    <textarea name="konten" id="konten-textarea" required
                        placeholder="<p>Paragraf pertama...</p>&#10;<p>Paragraf kedua...</p>"><?= htmlspecialchars($data_edit['konten'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn-simpan-new">
                    <i class='bx <?= $mode_edit ? "bx-save" : "bx-check" ?>'></i>
                    <?= $mode_edit ? 'Simpan Perubahan' : 'Tambah Artikel' ?>
                </button>
                <a href="dashboardAdmin.php?page=artikel" class="btn-batal-new">
                    <i class='bx bx-arrow-back'></i>
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>

<!-- TABEL ARTIKEL -->
<div class="tabel-card-new">

    <div class="tabel-card-header">
        <div class="tabel-card-header-top">
            <div class="tabel-card-title">
                <div class="title-icon"><i class='bx bx-news'></i></div>
                Daftar Artikel
                <span class="count-badge"><?= $total_data ?> artikel</span>
            </div>
        </div>

        <form method="get" action="" id="filter-form">
            <input type="hidden" name="page" value="artikel">
            <div class="toolbar-new">
                <div class="search-wrapper">
                    <i class='bx bx-search'></i>
                    <input type="text" name="cari" class="search-input-new"
                           placeholder="Cari judul atau penulis..."
                           value="<?= htmlspecialchars($cari) ?>">
                </div>

                <select name="kategori" class="select-filter" onchange="document.getElementById('filter-form').submit()">
                    <option value="">Semua Kategori</option>
                    <option value="sejarah"  <?= $filter_kat === 'sejarah'  ? 'selected' : '' ?>>Sejarah</option>
                    <option value="biografi" <?= $filter_kat === 'biografi' ? 'selected' : '' ?>>Biografi</option>
                </select>

                <select name="status" class="select-filter" onchange="document.getElementById('filter-form').submit()">
                    <option value="">Semua Status</option>
                    <option value="published" <?= $filter_st === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft"     <?= $filter_st === 'draft'     ? 'selected' : '' ?>>Draft</option>
                </select>

                <button type="button" class="btn-refresh" id="btn-refresh" onclick="handleRefresh()">
                    <i class='bx bx-refresh' id="refresh-icon"></i>
                    Refresh
                </button>

                <button type="submit" style="display:none;"></button>
            </div>
        </form>
    </div>

    <div class="info-total-new">
        <i class='bx bx-info-circle' style="font-size:14px;"></i>
        Menampilkan <strong><?= count($artikel_list) ?></strong> dari <strong><?= $total_data ?></strong> artikel
        <?php if ($cari): ?>
            &nbsp;·&nbsp; Pencarian: <strong>"<?= htmlspecialchars($cari) ?>"</strong>
        <?php endif; ?>
        <?php if ($filter_kat): ?>
            &nbsp;·&nbsp; Kategori: <strong><?= ucfirst($filter_kat) ?></strong>
        <?php endif; ?>
        <?php if ($filter_st): ?>
            &nbsp;·&nbsp; Status: <strong><?= ucfirst($filter_st) ?></strong>
        <?php endif; ?>
    </div>

    <div class="table-scroll">
        <table class="artikel-table-new">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th style="width:60px;">Cover</th>
                    <th>
                        <a href="<?= buildUrl(['sort'=>'judul','dir'=>$sort_by==='judul'?$sort_next:'ASC','halaman'=>1]) ?>">
                            Judul <i class='bx <?= $sort_by==="judul" ? ($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt") : "bx-sort-alt-2" ?> sort-icon'></i>
                        </a>
                    </th>
                    <th>
                        <a href="<?= buildUrl(['sort'=>'kategori','dir'=>$sort_by==='kategori'?$sort_next:'ASC','halaman'=>1]) ?>">
                            Kategori <i class='bx <?= $sort_by==="kategori" ? ($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt") : "bx-sort-alt-2" ?> sort-icon'></i>
                        </a>
                    </th>
                    <th>
                        <a href="<?= buildUrl(['sort'=>'penulis','dir'=>$sort_by==='penulis'?$sort_next:'ASC','halaman'=>1]) ?>">
                            Penulis <i class='bx <?= $sort_by==="penulis" ? ($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt") : "bx-sort-alt-2" ?> sort-icon'></i>
                        </a>
                    </th>
                    <th>
                        <a href="<?= buildUrl(['sort'=>'status','dir'=>$sort_by==='status'?$sort_next:'ASC','halaman'=>1]) ?>">
                            Status <i class='bx <?= $sort_by==="status" ? ($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt") : "bx-sort-alt-2" ?> sort-icon'></i>
                        </a>
                    </th>
                    <th>
                        <a href="<?= buildUrl(['sort'=>'view_count','dir'=>$sort_by==='view_count'?$sort_next:'DESC','halaman'=>1]) ?>">
                            Views <i class='bx <?= $sort_by==="view_count" ? ($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt") : "bx-sort-alt-2" ?> sort-icon'></i>
                        </a>
                    </th>
                    <th>
                        <a href="<?= buildUrl(['sort'=>'created_at','dir'=>$sort_by==='created_at'?$sort_next:'DESC','halaman'=>1]) ?>">
                            Tanggal <i class='bx <?= $sort_by==="created_at" ? ($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt") : "bx-sort-alt-2" ?> sort-icon'></i>
                        </a>
                    </th>
                    <th style="text-align:right; padding-right:20px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($artikel_list)): ?>
                <tr>
                    <td colspan="9">
                        <div class="no-data-new">
                            <i class='bx bx-news no-data-icon'></i>
                            <p>Tidak ada artikel ditemukan</p>
                            <small><?= $cari ? 'Coba ubah kata kunci pencarian Anda.' : 'Mulai dengan menambahkan artikel baru.' ?></small>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($artikel_list as $i => $a): ?>
                <tr>
                    <td><span class="row-num"><?= ($halaman-1)*$per_halaman+$i+1 ?></span></td>
                    <td>
                        <div class="thumb-wrap">
                            <?php if (!empty($a['thumbnail'])): ?>
                            <img src="<?= htmlspecialchars(getImgSrc($a['thumbnail'])) ?>" alt=""
                                 onerror="this.parentNode.innerHTML='<i class=\'bx bx-image-alt thumb-placeholder\'></i>'">
                            <?php else: ?>
                            <i class='bx bx-image-alt thumb-placeholder'></i>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="judul-cell" title="<?= htmlspecialchars($a['judul']) ?>">
                            <?= htmlspecialchars($a['judul']) ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($a['kategori'] === 'sejarah'): ?>
                            <span class="badge-new kat-sejarah"><i class='bx bx-time-five'></i> Sejarah</span>
                        <?php else: ?>
                            <span class="badge-new kat-biografi"><i class='bx bx-user'></i> Biografi</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="cell-muted"><?= htmlspecialchars($a['penulis'] ?? '—') ?></span></td>
                    <td>
                        <?php if ($a['status'] === 'published'): ?>
                            <span class="badge-new status-published"><i class='bx bx-check-circle'></i> Published</span>
                        <?php else: ?>
                            <span class="badge-new status-draft"><i class='bx bx-pencil'></i> Draft</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="cell-views"><?= number_format($a['view_count']) ?></span></td>
                    <td><span class="cell-date"><?= date('d M Y', strtotime($a['created_at'])) ?></span></td>
                    <td>
                        <div class="aksi-group">
                            <a href="?page=artikel&edit=<?= $a['id'] ?>"
                               class="aksi-btn-new btn-edit-new">
                                <i class='bx bx-edit'></i> Edit
                            </a>
                            <a href="?page=artikel&hapus=<?= $a['id'] ?>"
                               onclick="return confirmHapus('<?= htmlspecialchars(addslashes($a['judul'])) ?>')"
                               class="aksi-btn-new btn-hapus-new">
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
            <a href="<?= buildUrl(['halaman'=>$halaman-1]) ?>" class="page-btn"><i class='bx bx-chevron-left'></i></a>
        <?php else: ?>
            <span class="page-btn nonaktif"><i class='bx bx-chevron-left'></i></span>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_page; $i++): ?>
            <?php if ($i === $halaman): ?>
                <span class="page-btn aktif"><?= $i ?></span>
            <?php elseif ($i === 1 || $i === $total_page || abs($i - $halaman) <= 2): ?>
                <a href="<?= buildUrl(['halaman'=>$i]) ?>" class="page-btn"><?= $i ?></a>
            <?php elseif (abs($i - $halaman) === 3): ?>
                <span class="page-ellipsis">…</span>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($halaman < $total_page): ?>
            <a href="<?= buildUrl(['halaman'=>$halaman+1]) ?>" class="page-btn"><i class='bx bx-chevron-right'></i></a>
        <?php else: ?>
            <span class="page-btn nonaktif"><i class='bx bx-chevron-right'></i></span>
        <?php endif; ?>

        <span class="page-info">Halaman <?= $halaman ?> dari <?= $total_page ?></span>
    </div>
    <?php endif; ?>

</div><!-- end tabel-card-new -->

<?php endif; ?>

</div><!-- end artikel-page -->

<script>
function previewGambar(input) {
    const img = document.getElementById('preview-img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function confirmHapus(judul) {
    return confirm('Hapus artikel "' + judul + '"?\n\nArtikel yang dihapus tidak dapat dikembalikan.');
}

function handleRefresh() {
    const icon = document.getElementById('refresh-icon');
    icon.classList.add('spinning');
    setTimeout(() => { window.location.reload(); }, 300);
}

const notif = document.getElementById('notif-box');
if (notif) {
    setTimeout(() => {
        notif.style.transition = 'opacity 0.4s ease';
        notif.style.opacity = '0';
        setTimeout(() => notif.style.display = 'none', 400);
    }, 5000);
}

const searchInput = document.querySelector('.search-input-new');
if (searchInput) {
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('filter-form').submit();
        }
    });
}
</script>