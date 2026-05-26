<?php
// ============================================================
// pages/artikel.php  —  Manajemen Artikel (Clean & Standard)
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

    if (!in_array($ext, $allowed)) {
        return ['error' => 'Format gambar tidak didukung.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'Ukuran gambar maksimal 5MB.'];
    }

    $nama_baru = 'artikel_' . time() . '_' . rand(100,999) . '.' . $ext;
    $path_tuju = $upload_dir . $nama_baru;

    if (!move_uploaded_file($file['tmp_name'], $path_tuju)) {
        return ['error' => 'Gagal upload gambar.'];
    }

    if (
        $thumbnail_lama &&
        file_exists($upload_dir . $thumbnail_lama) &&
        strpos($thumbnail_lama, 'artikel_') === 0
    ) {
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

// ============================================================
// PROSES TAMBAH ARTIKEL
// ============================================================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {

    $judul    = trim($_POST['judul']);
    $kategori = $_POST['kategori'];
    $status   = $_POST['status'];

    if ($_SESSION['role'] === 'penulis') {
        $penulis = $_SESSION['nama_lengkap'];
    } else {
        $penulis = trim($_POST['penulis']) ?: $_SESSION['nama_lengkap'];
    }

    $preview = trim($_POST['preview']);
    $konten  = trim($_POST['konten']);
    $slug    = buatSlug($judul);

    $cek_slug = $conn->prepare("SELECT id FROM tb_artikel WHERE slug = ?");
    $cek_slug->bind_param("s", $slug);
    $cek_slug->execute();

    if ($cek_slug->get_result()->num_rows > 0) {
        $slug = $slug . '-' . time();
    }

    $thumbnail = '';

    if (!empty($_FILES['thumbnail']['name'])) {
        $hasil = uploadGambar($_FILES['thumbnail']);

        if (isset($hasil['error'])) {
            $pesan = $hasil['error'];
            $tipe  = 'error';
        } else {
            $thumbnail = $hasil['nama'];
        }
    }

    if ($tipe !== 'error') {

        $stmt = $conn->prepare("
            INSERT INTO tb_artikel
            (
                judul,
                slug,
                konten,
                preview,
                penulis,
                thumbnail,
                kategori,
                status,
                created_by,
                created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $user_id = $_SESSION['id'] ?? 1;

        $stmt->bind_param(
            "ssssssssi",
            $judul,
            $slug,
            $konten,
            $preview,
            $penulis,
            $thumbnail,
            $kategori,
            $status,
            $user_id
        );

        if ($stmt->execute()) {
            $pesan = 'Artikel berhasil ditambahkan!';
            $tipe  = 'sukses';
        } else {
            $pesan = 'Gagal: ' . $stmt->error;
            $tipe  = 'error';
        }
    }
}

// ============================================================
// PROSES EDIT ARTIKEL
// ============================================================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'edit') {

    $id       = (int)$_POST['id'];
    $judul    = trim($_POST['judul']);
    $kategori = $_POST['kategori'];
    $status   = $_POST['status'];

    if ($_SESSION['role'] === 'penulis') {
        $penulis = $_SESSION['nama_lengkap'];
    } else {
        $penulis = trim($_POST['penulis']) ?: 'Admin';
    }

    $preview = trim($_POST['preview']);
    $konten  = trim($_POST['konten']);

    $lama      = $conn->query("SELECT thumbnail FROM tb_artikel WHERE id=$id")->fetch_assoc();
    $thumbnail = $lama['thumbnail'];

    if (!empty($_FILES['thumbnail']['name'])) {

        $hasil = uploadGambar($_FILES['thumbnail'], $thumbnail);

        if (isset($hasil['error'])) {
            $pesan = $hasil['error'];
            $tipe  = 'error';
        } else {
            $thumbnail = $hasil['nama'];
        }
    }

    if ($tipe !== 'error') {

        $stmt = $conn->prepare("
            UPDATE tb_artikel
            SET
                judul=?,
                konten=?,
                preview=?,
                penulis=?,
                thumbnail=?,
                kategori=?,
                status=?
            WHERE id=?
        ");

        $stmt->bind_param(
            "sssssssi",
            $judul,
            $konten,
            $preview,
            $penulis,
            $thumbnail,
            $kategori,
            $status,
            $id
        );

        if ($stmt->execute()) {
            $pesan = 'Artikel berhasil diperbarui!';
            $tipe  = 'sukses';
        } else {
            $pesan = 'Gagal: ' . $stmt->error;
            $tipe  = 'error';
        }
    }
}

// ============================================================
// PROSES HAPUS ARTIKEL
// ============================================================
if (isset($_GET['hapus'])) {

    $id   = (int)$_GET['hapus'];

    $lama = $conn->query("SELECT thumbnail FROM tb_artikel WHERE id=$id")->fetch_assoc();

    if (
        $lama &&
        $lama['thumbnail'] &&
        strpos($lama['thumbnail'], 'artikel_') === 0
    ) {
        $path_gambar = __DIR__ . '/../uploads/' . $lama['thumbnail'];

        if (file_exists($path_gambar)) {
            unlink($path_gambar);
        }
    }

    $conn->query("DELETE FROM tb_favorit WHERE artikel_id=$id");
    $conn->query("DELETE FROM tb_ulasan WHERE artikel_id=$id");
    $conn->query("DELETE FROM tb_history WHERE artikel_id=$id");
    $conn->query("DELETE FROM tb_artikel WHERE id=$id");

    $pesan = 'Artikel berhasil dihapus.';
    $tipe  = 'sukses';
}

// ============================================================
// AMBIL DATA EDIT
// ============================================================
$mode_edit = false;
$data_edit = null;

if (isset($_GET['edit'])) {

    $id_edit   = (int)$_GET['edit'];
    $data_edit = $conn->query("SELECT * FROM tb_artikel WHERE id=$id_edit")->fetch_assoc();

    if ($data_edit) {
        $mode_edit = true;
    }
}

// ============================================================
// FILTER DATA
// ============================================================
$cari        = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$filter_kat  = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$filter_st   = isset($_GET['status']) ? $_GET['status'] : '';
$sort_by     = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_dir    = isset($_GET['dir']) ? $_GET['dir'] : 'DESC';
$halaman     = isset($_GET['halaman']) ? max(1,(int)$_GET['halaman']) : 1;
$per_halaman = 10;

$sort_allowed = [
    'judul',
    'kategori',
    'status',
    'penulis',
    'created_at',
    'view_count'
];

if (!in_array($sort_by, $sort_allowed)) {
    $sort_by = 'created_at';
}

$sort_dir  = $sort_dir === 'ASC' ? 'ASC' : 'DESC';
$sort_next = $sort_dir === 'ASC' ? 'DESC' : 'ASC';

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($cari !== '') {

    $cari_like = '%' . $cari . '%';

    $where .= " AND (judul LIKE ? OR penulis LIKE ?)";

    $params[] = $cari_like;
    $params[] = $cari_like;

    $types .= "ss";
}

if ($filter_kat !== '') {
    $where .= " AND kategori = ?";
    $params[] = $filter_kat;
    $types .= "s";
}

if ($filter_st !== '') {
    $where .= " AND status = ?";
    $params[] = $filter_st;
    $types .= "s";
}

$q_total = $conn->prepare("SELECT COUNT(*) as total FROM tb_artikel $where");

if ($params) {
    $q_total->bind_param($types, ...$params);
}

$q_total->execute();

$total_data = $q_total->get_result()->fetch_assoc()['total'];
$total_page = ceil($total_data / $per_halaman);
$offset     = ($halaman - 1) * $per_halaman;

$q_data = $conn->prepare("
    SELECT *
    FROM tb_artikel
    $where
    ORDER BY $sort_by $sort_dir
    LIMIT ? OFFSET ?
");

$params_data   = $params;
$params_data[] = $per_halaman;
$params_data[] = $offset;

$types_data = $types . "ii";

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

:root{
    --sj-dark:#4A2C18;
    --sj-darker:#4D1E0A;
    --sj-mid:#7B4F2C;
    --sj-gold:#D99B3E;
    --sj-sand:#AD8D77;
    --brown-100:#f0e4d2;
    --brown-200:#dfc8b0;
    --brown-50:#faf5ec;
    --white:#fff;
    --border:#e2d0b8;
    --border-soft:#ede4d3;
    --text-primary:#2a1508;
    --text-secondary:#4A2C18;
    --text-muted:#7B4F2C;
    --text-disabled:#AD8D77;
    --radius-sm:6px;
    --radius-md:10px;
    --radius-lg:14px;
    --radius-xl:18px;
    --shadow-sm:0 1px 3px rgba(74,44,24,.07);
    --shadow-md:0 4px 16px rgba(74,44,24,.10);
    --transition:all .2s ease;
}

body{
    font-family:'Roboto',sans-serif;
    color:var(--text-primary);
}

.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:24px;
    flex-wrap:wrap;
    gap:12px;
}

.page-header-left h1{
    font-size:28px;
    margin:0;
    font-family:'Montserrat',sans-serif;
    font-weight:800;
    color:var(--sj-darker);
}

.breadcrumb-text{
    display:flex;
    align-items:center;
    gap:6px;
    margin-top:6px;
    font-size:13px;
    color:var(--text-muted);
}

.header-action{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

.btn-tambah-new,
.btn-logout{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:11px 20px;
    border-radius:var(--radius-md);
    text-decoration:none;
    font-weight:700;
    transition:var(--transition);
    font-size:14px;
    font-family:'Montserrat',sans-serif;
}

.btn-tambah-new{
    background:var(--sj-dark);
    color:#fff;
}

.btn-tambah-new:hover{
    background:var(--sj-darker);
    color:#fff;
    transform:translateY(-1px);
}

.btn-logout{
    background:#fff1f2;
    color:#be123c;
    border:1px solid #fda4af;
}

.btn-logout:hover{
    background:#ffe4e6;
    color:#9f1239;
    transform:translateY(-1px);
}

.notif-modern{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 18px;
    border-radius:var(--radius-md);
    margin-bottom:20px;
}

.notif-modern.sukses{
    background:#ecfdf5;
    color:#065f46;
}

.notif-modern.error{
    background:#fff1f2;
    color:#be123c;
}

.form-card-new,
.tabel-card-new{
    background:#fff;
    border-radius:var(--radius-xl);
    box-shadow:var(--shadow-md);
    border:1px solid var(--border-soft);
    overflow:hidden;
}

.form-card-header{
    background:linear-gradient(135deg,var(--sj-darker),var(--sj-dark));
    color:#fff;
    padding:20px 26px;
    display:flex;
    align-items:center;
    gap:10px;
}

.form-card-body{
    padding:26px;
}

.form-grid-2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
    margin-bottom:18px;
}

.form-grid-1{
    display:grid;
    grid-template-columns:1fr;
    margin-bottom:18px;
}

.form-group label{
    display:block;
    margin-bottom:7px;
    font-size:13px;
    font-weight:600;
}

.form-group input,
.form-group select,
.form-group textarea{
    width:100%;
    padding:11px 14px;
    border-radius:var(--radius-md);
    border:1.5px solid var(--border);
    outline:none;
    transition:var(--transition);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{
    border-color:var(--sj-mid);
    box-shadow:0 0 0 3px rgba(123,79,44,.10);
}

.form-group textarea{
    resize:vertical;
    min-height:100px;
}

#konten-textarea{
    min-height:260px;
}

.form-footer{
    display:flex;
    align-items:center;
    gap:10px;
    margin-top:18px;
}

.btn-simpan-new,
.btn-batal-new{
    padding:11px 22px;
    border-radius:var(--radius-md);
    text-decoration:none;
    font-weight:700;
    border:none;
    cursor:pointer;
    transition:var(--transition);
}

.btn-simpan-new{
    background:var(--sj-dark);
    color:#fff;
}

.btn-simpan-new:hover{
    background:var(--sj-darker);
}

.btn-batal-new{
    background:var(--brown-100);
    color:var(--sj-dark);
}

.tabel-card-header{
    padding:22px;
    border-bottom:1px solid var(--border-soft);
}

.toolbar-new{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:16px;
}

.search-wrapper{
    position:relative;
    flex:1;
    min-width:220px;
}

.search-wrapper i{
    position:absolute;
    top:50%;
    left:12px;
    transform:translateY(-50%);
    color:var(--text-muted);
}

.search-input-new{
    width:100%;
    padding:11px 14px 11px 38px;
    border-radius:var(--radius-md);
    border:1.5px solid var(--border);
}

.select-filter{
    padding:11px 14px;
    border-radius:var(--radius-md);
    border:1.5px solid var(--border);
}

.btn-refresh{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:11px 18px;
    border-radius:var(--radius-md);
    text-decoration:none;
    background:#fff;
    border:1.5px solid var(--border);
    color:var(--sj-dark);
    font-weight:600;
}

.table-scroll{
    overflow-x:auto;
}

.artikel-table-new{
    width:100%;
    border-collapse:collapse;
    min-width:780px;
}

.artikel-table-new th{
    background:var(--brown-50);
    padding:14px 16px;
    text-align:left;
    font-size:12px;
}

.artikel-table-new td{
    padding:14px 16px;
    border-bottom:1px solid var(--border-soft);
}

.thumb-wrap{
    width:52px;
    height:40px;
    border-radius:8px;
    overflow:hidden;
    background:#f0e4d2;
}

.thumb-wrap img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.badge-new{
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:5px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
}

.status-published{
    background:#ecfdf5;
    color:#065f46;
}

.status-draft{
    background:#fef9ee;
    color:#7a4a00;
}

.kat-sejarah{
    background:#fef3d6;
    color:#7a4a00;
}

.kat-biografi{
    background:#f0e4d2;
    color:#4A2C18;
}

.aksi-group{
    display:flex;
    justify-content:flex-end;
    gap:6px;
}

.aksi-btn-new{
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:7px 11px;
    border-radius:8px;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
}

.btn-edit-new{
    background:#fef9ee;
    color:#7a4a00;
}

.btn-hapus-new{
    background:#fff1f2;
    color:#be123c;
}

.pagination-new{
    padding:18px 24px;
    display:flex;
    align-items:center;
    gap:6px;
    flex-wrap:wrap;
    border-top:1px solid var(--border-soft);
}

.page-btn{
    min-width:36px;
    height:36px;
    display:inline-flex;
    justify-content:center;
    align-items:center;
    border-radius:8px;
    text-decoration:none;
    border:1px solid var(--border);
    color:var(--text-secondary);
}

.page-btn.aktif{
    background:var(--sj-dark);
    color:#fff;
}

.page-info{
    margin-left:auto;
    font-size:13px;
    color:var(--text-muted);
}

.preview-img-new{
    margin-top:10px;
    max-width:140px;
    border-radius:10px;
}

.no-data-new{
    text-align:center;
    padding:60px 20px;
}

@media(max-width:768px){

    .form-grid-2{
        grid-template-columns:1fr;
    }

    .toolbar-new{
        flex-direction:column;
    }

    .header-action{
        width:100%;
    }

    .btn-tambah-new,
    .btn-logout{
        width:100%;
        justify-content:center;
    }
}

</style>

<?php if ($pesan): ?>
<div class="notif-modern <?= $tipe ?>" id="notif-box">
    <i class='bx <?= $tipe === "sukses" ? "bx-check-circle" : "bx-x-circle" ?>'></i>
    <span><?= htmlspecialchars($pesan) ?></span>
</div>
<?php endif; ?>

<?php if (!$mode_edit && !isset($_GET['tambah'])): ?>

<div class="page-header">

    <div class="page-header-left">
        <h1>Manajemen Artikel</h1>

        <div class="breadcrumb-text">
            <i class='bx bx-home-alt'></i>
            Dashboard
            <i class='bx bx-chevron-right'></i>
            <span style="font-weight:700;">Artikel</span>
        </div>
    </div>

    <div class="header-action">

        <a href="?tambah=1" class="btn-tambah-new">
            <i class='bx bx-plus'></i>
            Tambah Artikel
        </a>

        <!-- TOMBOL LOGOUT -->
        <a href="../logout.php"
           class="btn-logout"
           onclick="return confirm('Yakin ingin logout?')">

            <i class='bx bx-log-out'></i>
            Logout
        </a>

    </div>
</div>

<?php endif; ?>

<?php if (isset($_GET['tambah']) || $mode_edit): ?>

<div class="page-header">

    <div class="page-header-left">

        <h1><?= $mode_edit ? 'Edit Artikel' : 'Tambah Artikel Baru' ?></h1>

        <div class="breadcrumb-text">
            <i class='bx bx-home-alt'></i>
            Dashboard
            <i class='bx bx-chevron-right'></i>

            <a href="?page=artikel"
               style="text-decoration:none;color:var(--text-muted);">
               Artikel
            </a>

            <i class='bx bx-chevron-right'></i>

            <span style="font-weight:700;">
                <?= $mode_edit ? 'Edit' : 'Tambah' ?>
            </span>
        </div>
    </div>

    <!-- TOMBOL LOGOUT -->
    <div class="header-action">

        <a href="?page=artikel" class="btn-tambah-new">
            <i class='bx bx-arrow-back'></i>
            Kembali
        </a>

        <a href="../logout.php"
           class="btn-logout"
           onclick="return confirm('Yakin ingin logout?')">

            <i class='bx bx-log-out'></i>
            Logout
        </a>

    </div>

</div>

<div class="form-card-new">

    <div class="form-card-header">
        <i class='bx <?= $mode_edit ? "bx-edit" : "bx-file-plus" ?>'></i>

        <h2>
            <?= $mode_edit ? 'Edit Artikel' : 'Artikel Baru' ?>
        </h2>
    </div>

    <div class="form-card-body">

        <form method="post" enctype="multipart/form-data">

            <input type="hidden"
                   name="aksi"
                   value="<?= $mode_edit ? 'edit' : 'tambah' ?>">

            <?php if ($mode_edit): ?>
                <input type="hidden"
                       name="id"
                       value="<?= $data_edit['id'] ?>">
            <?php endif; ?>

            <div class="form-grid-2">

                <div class="form-group">
                    <label>Judul Artikel</label>

                    <input type="text"
                           name="judul"
                           required
                           placeholder="Masukkan judul artikel..."
                           value="<?= htmlspecialchars($data_edit['judul'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Penulis</label>

                    <input type="text"
                           name="penulis"
                           value="<?= htmlspecialchars($data_edit['penulis'] ?? $_SESSION['nama_lengkap']) ?>"
                           <?= ($_SESSION['role'] === 'penulis') ? 'readonly' : '' ?>>
                </div>

            </div>

            <div class="form-grid-2">

                <div class="form-group">

                    <label>Kategori</label>

                    <select name="kategori" required>
                        <option value="">Pilih Kategori</option>

                        <option value="sejarah"
                            <?= ($data_edit['kategori'] ?? '') === 'sejarah' ? 'selected' : '' ?>>
                            Sejarah
                        </option>

                        <option value="biografi"
                            <?= ($data_edit['kategori'] ?? '') === 'biografi' ? 'selected' : '' ?>>
                            Biografi
                        </option>
                    </select>
                </div>

                <div class="form-group">

                    <label>Status</label>

                    <select name="status">

                        <option value="published"
                            <?= ($data_edit['status'] ?? '') === 'published' ? 'selected' : '' ?>>
                            Published
                        </option>

                        <option value="draft"
                            <?= ($data_edit['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>
                            Draft
                        </option>

                    </select>
                </div>

            </div>

            <div class="form-grid-2">

                <div class="form-group">

                    <label>Thumbnail</label>

                    <input type="file"
                           name="thumbnail"
                           accept="image/*"
                           onchange="previewGambar(this)">

                    <?php if ($mode_edit && !empty($data_edit['thumbnail'])): ?>

                        <img src="<?= htmlspecialchars(getImgSrc($data_edit['thumbnail'])) ?>"
                             id="preview-img"
                             class="preview-img-new">

                    <?php else: ?>

                        <img id="preview-img"
                             class="preview-img-new"
                             style="display:none;">

                    <?php endif; ?>

                </div>

                <div class="form-group">

                    <label>Preview</label>

                    <textarea name="preview"><?= htmlspecialchars($data_edit['preview'] ?? '') ?></textarea>

                </div>

            </div>

            <div class="form-grid-1">

                <div class="form-group">

                    <label>Isi Artikel</label>

                    <textarea name="konten"
                              id="konten-textarea"
                              required><?= htmlspecialchars($data_edit['konten'] ?? '') ?></textarea>

                </div>

            </div>

            <div class="form-footer">

                <button type="submit" class="btn-simpan-new">
                    <i class='bx bx-save'></i>

                    <?= $mode_edit ? 'Simpan Perubahan' : 'Tambah Artikel' ?>
                </button>

                <a href="?page=artikel" class="btn-batal-new">
                    Batal
                </a>

            </div>

        </form>

    </div>

</div>

<?php endif; ?>

<?php if (!$mode_edit && !isset($_GET['tambah'])): ?>

<div class="tabel-card-new">

    <div class="tabel-card-header">

        <h3 style="margin:0;">
            Daftar Artikel (<?= $total_data ?>)
        </h3>

        <form method="get">

            <input type="hidden" name="page" value="artikel">

            <div class="toolbar-new">

                <div class="search-wrapper">

                    <i class='bx bx-search'></i>

                    <input type="text"
                           name="cari"
                           class="search-input-new"
                           placeholder="Cari artikel..."
                           value="<?= htmlspecialchars($cari) ?>">

                </div>

                <select name="kategori"
                        class="select-filter"
                        onchange="this.form.submit()">

                    <option value="">Semua Kategori</option>

                    <option value="sejarah"
                        <?= $filter_kat === 'sejarah' ? 'selected' : '' ?>>
                        Sejarah
                    </option>

                    <option value="biografi"
                        <?= $filter_kat === 'biografi' ? 'selected' : '' ?>>
                        Biografi
                    </option>

                </select>

                <select name="status"
                        class="select-filter"
                        onchange="this.form.submit()">

                    <option value="">Semua Status</option>

                    <option value="published"
                        <?= $filter_st === 'published' ? 'selected' : '' ?>>
                        Published
                    </option>

                    <option value="draft"
                        <?= $filter_st === 'draft' ? 'selected' : '' ?>>
                        Draft
                    </option>

                </select>

                <a href="?page=artikel" class="btn-refresh">
                    <i class='bx bx-refresh'></i>
                    Reset
                </a>

            </div>

        </form>

    </div>

    <div class="table-scroll">

        <table class="artikel-table-new">

            <thead>

                <tr>
                    <th>No</th>
                    <th>Cover</th>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Penulis</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>

            </thead>

            <tbody>

                <?php if (empty($artikel_list)): ?>

                    <tr>
                        <td colspan="8">

                            <div class="no-data-new">
                                <i class='bx bx-folder-open'
                                   style="font-size:50px;"></i>

                                <p>Tidak ada artikel.</p>
                            </div>

                        </td>
                    </tr>

                <?php else: ?>

                    <?php
                    $no = $offset + 1;

                    foreach ($artikel_list as $art):
                    ?>

                    <tr>

                        <td><?= $no++ ?></td>

                        <td>

                            <div class="thumb-wrap">

                                <?php if (!empty($art['thumbnail'])): ?>

                                    <img src="<?= htmlspecialchars(getImgSrc($art['thumbnail'])) ?>">

                                <?php else: ?>

                                    <i class='bx bx-image'></i>

                                <?php endif; ?>

                            </div>

                        </td>

                        <td>
                            <?= htmlspecialchars($art['judul']) ?>
                        </td>

                        <td>

                            <?php if ($art['kategori'] === 'sejarah'): ?>

                                <span class="badge-new kat-sejarah">
                                    Sejarah
                                </span>

                            <?php else: ?>

                                <span class="badge-new kat-biografi">
                                    Biografi
                                </span>

                            <?php endif; ?>

                        </td>

                        <td><?= htmlspecialchars($art['penulis']) ?></td>

                        <td><?= date('d M Y', strtotime($art['created_at'])) ?></td>

                        <td>

                            <?php if ($art['status'] === 'published'): ?>

                                <span class="badge-new status-published">
                                    Published
                                </span>

                            <?php else: ?>

                                <span class="badge-new status-draft">
                                    Draft
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <div class="aksi-group">

                                <a href="?page=artikel&edit=<?= $art['id'] ?>"
                                   class="aksi-btn-new btn-edit-new">

                                    <i class='bx bx-edit'></i>
                                    Edit
                                </a>

                                <a href="?page=artikel&hapus=<?= $art['id'] ?>"
                                   class="aksi-btn-new btn-hapus-new"
                                   onclick="return confirm('Yakin ingin menghapus artikel ini?')">

                                    <i class='bx bx-trash'></i>
                                    Hapus
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

        <?php for ($i = 1; $i <= $total_page; $i++): ?>

            <a href="<?= buildUrl(['halaman' => $i]) ?>"
               class="page-btn <?= $i == $halaman ? 'aktif' : '' ?>">

                <?= $i ?>
            </a>

        <?php endfor; ?>

        <div class="page-info">
            Halaman <?= $halaman ?> dari <?= $total_page ?>
        </div>

    </div>

    <?php endif; ?>

</div>

<?php endif; ?>

<script>

function previewGambar(input){

    const preview = document.getElementById('preview-img');

    if(input.files && input.files[0]){

        const reader = new FileReader();

        reader.onload = function(e){
            preview.src = e.target.result;
            preview.style.display = 'block';
        }

        reader.readAsDataURL(input.files[0]);
    }
}
</script>