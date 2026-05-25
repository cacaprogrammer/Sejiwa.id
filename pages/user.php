<?php
// ============================================================
// pages/user.php  —  Halaman Data User (Admin Dashboard)
// Struktur tb_user: id, nama_lengkap, email, username,
//                   password, role, status, created_at
// ============================================================

// ===== KEAMANAN: Hanya admin =====
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit();
}

// ===== AUTO-CREATE: kolom warning_note & tabel tb_notifikasi =====
$conn->query("ALTER TABLE tb_user ADD COLUMN IF NOT EXISTS warning_note TEXT DEFAULT NULL");
$conn->query("
    CREATE TABLE IF NOT EXISTS tb_notifikasi (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        judul      VARCHAR(150) NOT NULL,
        pesan      TEXT NOT NULL,
        tipe       ENUM('peringatan','info') DEFAULT 'info',
        dibaca     TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id),
        INDEX(dibaca)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ===== HELPER: Kirim notifikasi ke user =====
function kirimNotifikasi($conn, $user_id, $judul, $pesan, $tipe = 'info') {
    $stmt = $conn->prepare(
        "INSERT INTO tb_notifikasi (user_id, judul, pesan, tipe) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("isss", $user_id, $judul, $pesan, $tipe);
    $stmt->execute();
}

// ===== PROSES: HAPUS USER =====
if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    $stmt_cek = $conn->prepare("SELECT role FROM tb_user WHERE id = ?");
    $stmt_cek->bind_param("i", $hapus_id);
    $stmt_cek->execute();
    $cek = $stmt_cek->get_result()->fetch_assoc();
    if ($cek && $cek['role'] !== 'admin') {
        $del = $conn->prepare("DELETE FROM tb_user WHERE id = ? AND role != 'admin'");
        $del->bind_param("i", $hapus_id);
        $del->execute();
        header("Location: dashboardAdmin.php?page=user&msg=hapus");
    } else {
        header("Location: dashboardAdmin.php?page=user&msg=error_admin");
    }
    exit();
}

// ===== PROSES: PERINGATAN USER =====
if (isset($_POST['aksi']) && $_POST['aksi'] === 'peringatan') {
    $warn_id  = (int)$_POST['warn_id'];
    $warn_msg = mb_substr(strip_tags(trim($_POST['warn_msg'])), 0, 500);

    // Simpan warning_note ke tb_user
    $stmt_w = $conn->prepare("UPDATE tb_user SET warning_note = ? WHERE id = ? AND role != 'admin'");
    $stmt_w->bind_param("si", $warn_msg, $warn_id);
    $stmt_w->execute();

    // Kirim notifikasi ke user
    if ($stmt_w->affected_rows > 0) {
        $pesan_notif = $warn_msg ?: 'Kamu mendapat peringatan dari administrator Sejiwa.id. Harap perhatikan aturan penggunaan platform.';
        kirimNotifikasi(
            $conn, $warn_id,
            '⚠️ Kamu Mendapat Peringatan dari Admin',
            $pesan_notif,
            'peringatan'
        );
    }

    header("Location: dashboardAdmin.php?page=user&msg=peringatan");
    exit();
}

// ===== PROSES: TAMBAH USER =====
if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah_user') {
    $t_nama     = mb_substr(strip_tags(trim($_POST['t_nama'])),     0, 100);
    $t_email    = mb_substr(strip_tags(trim($_POST['t_email'])),    0, 100);
    $t_username = mb_substr(strip_tags(trim($_POST['t_username'])), 0, 50);
    $t_password = trim($_POST['t_password']);
    $t_role     = in_array($_POST['t_role'], ['admin','user']) ? $_POST['t_role'] : 'user';

    $err_tambah = [];

    // Validasi tidak boleh kosong
    if ($t_nama     === '') $err_tambah[] = 'Nama lengkap wajib diisi.';
    if ($t_email    === '') $err_tambah[] = 'Email wajib diisi.';
    if ($t_username === '') $err_tambah[] = 'Username wajib diisi.';
    if ($t_password === '') $err_tambah[] = 'Password wajib diisi.';
    if (strlen($t_password) < 6) $err_tambah[] = 'Password minimal 6 karakter.';
    if (!filter_var($t_email, FILTER_VALIDATE_EMAIL)) $err_tambah[] = 'Format email tidak valid.';

    // Cek duplikat email & username
    if (empty($err_tambah)) {
        $cek_dup = $conn->prepare("SELECT id FROM tb_user WHERE email = ? OR username = ? LIMIT 1");
        $cek_dup->bind_param("ss", $t_email, $t_username);
        $cek_dup->execute();
        if ($cek_dup->get_result()->num_rows > 0) {
            $err_tambah[] = 'Email atau username sudah digunakan.';
        }
    }

    if (empty($err_tambah)) {
        $hash = password_hash($t_password, PASSWORD_BCRYPT);
        $ins  = $conn->prepare(
            "INSERT INTO tb_user (nama_lengkap, email, username, password, role, status)
             VALUES (?, ?, ?, ?, ?, 'active')"
        );
        $ins->bind_param("sssss", $t_nama, $t_email, $t_username, $hash, $t_role);
        if ($ins->execute()) {
            header("Location: dashboardAdmin.php?page=user&msg=tambah");
        } else {
            header("Location: dashboardAdmin.php?page=user&msg=error_tambah");
        }
        exit();
    }
    // Ada error — simpan ke session supaya bisa ditampilkan di modal
    $_SESSION['err_tambah']  = $err_tambah;
    $_SESSION['form_tambah'] = compact('t_nama','t_email','t_username','t_role');
    header("Location: dashboardAdmin.php?page=user&modal=tambah");
    exit();
}

// Ambil error tambah dari session (jika ada redirect balik)
$err_tambah  = $_SESSION['err_tambah']  ?? [];
$form_tambah = $_SESSION['form_tambah'] ?? [];
unset($_SESSION['err_tambah'], $_SESSION['form_tambah']);
$open_modal_tambah = isset($_GET['modal']) && $_GET['modal'] === 'tambah';

// ===== PARAMETER: Search, Filter, Sort, Pagination =====
$cari        = isset($_GET['cari'])    ? trim($_GET['cari'])           : '';
$filter_role = isset($_GET['role'])    ? $_GET['role']                 : '';
$sort_by     = isset($_GET['sort'])    ? $_GET['sort']                 : 'created_at';
$sort_dir    = isset($_GET['dir'])     ? strtoupper($_GET['dir'])      : 'DESC';
$halaman     = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 10;

// Whitelist kolom sort — cegah SQL Injection
$allowed_sort = ['id', 'nama_lengkap', 'username', 'email', 'role', 'created_at'];
if (!in_array($sort_by, $allowed_sort)) $sort_by = 'created_at';
$sort_dir  = ($sort_dir === 'ASC') ? 'ASC' : 'DESC';
$sort_next = ($sort_dir === 'ASC') ? 'DESC' : 'ASC';

// Whitelist filter role
$allowed_role = ['admin', 'user'];
if (!in_array($filter_role, $allowed_role)) $filter_role = '';

// ===== BUILD QUERY AMAN =====
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($cari !== '') {
    $where   .= " AND (nama_lengkap LIKE ? OR username LIKE ? OR email LIKE ?)";
    $keyword  = "%$cari%";
    $params   = array_merge($params, [$keyword, $keyword, $keyword]);
    $types   .= "sss";
}
if ($filter_role !== '') {
    $where  .= " AND role = ?";
    $params  = array_merge($params, [$filter_role]);
    $types  .= "s";
}

// Hitung total
$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tb_user $where");
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_data = $count_stmt->get_result()->fetch_assoc()['total'];
$total_page = max(1, (int)ceil($total_data / $per_halaman));
if ($halaman > $total_page) $halaman = $total_page;
$offset = ($halaman - 1) * $per_halaman;

// Ambil data
$sql  = "SELECT id, nama_lengkap, username, email, role, status, created_at, warning_note
         FROM tb_user $where ORDER BY $sort_by $sort_dir LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types . "ii", ...array_merge($params, [$per_halaman, $offset]));
$stmt->execute();
$user_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ===== HELPER URL =====
function userUrl($extra) {
    $base = array_merge($_GET, $extra);
    unset($base['hapus'], $base['msg']);
    return 'dashboardAdmin.php?' . http_build_query($base);
}

// ===== PESAN NOTIFIKASI =====
$notif_map = [
    'hapus'        => ['sukses',  '✅ User berhasil dihapus dari sistem.'],
    'peringatan'   => ['warning', '📩 Peringatan berhasil dikirim ke user.'],
    'tambah'       => ['sukses',  '✅ User baru berhasil ditambahkan.'],
    'error_tambah' => ['error',   '⛔ Gagal menambahkan user, coba lagi.'],
    'error_admin'  => ['error',   '⛔ Tidak dapat melakukan aksi pada akun admin.'],
];
$pesan = $tipe = '';
if (isset($_GET['msg']) && isset($notif_map[$_GET['msg']])) {
    [$tipe, $pesan] = $notif_map[$_GET['msg']];
}
?>
<!-- ============================================================
     CSS — Data User Page (konsisten dengan Manajemen Artikel)
     ============================================================ -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<style>
:root {
    --br-900:#3B1F0E; --br-700:#4A2C18; --br-600:#6B3E23;
    --br-400:#A3826F; --br-100:#F5EDE6;
    --green-bg:#ECFDF5; --green-tx:#065F46;
    --red-bg:#FEF2F2;   --red-tx:#991B1B;
    --ylw-bg:#FFFBEB;   --ylw-tx:#92400E;
    --blu-bg:#EFF6FF;   --blu-tx:#1D4ED8;
    --g50:#F9FAFB; --g100:#F3F4F6; --g200:#E5E7EB;
    --g400:#9CA3AF; --g600:#4B5563; --g800:#1F2937;
    --muted:#6B7280;
    --r:10px;
    --sh-sm:0 1px 3px rgba(0,0,0,.07),0 1px 2px rgba(0,0,0,.04);
    --sh-md:0 4px 6px -1px rgba(0,0,0,.08),0 2px 4px -1px rgba(0,0,0,.04);
}
.up *{box-sizing:border-box}

/* PAGE HEADER */
.up-header{display:flex;justify-content:space-between;align-items:flex-start;
    margin-bottom:24px;flex-wrap:wrap;gap:12px}
.up-header h1{font-family:'Montserrat',sans-serif;font-size:26px;font-weight:800;
    color:#4D1E0A;margin:0 0 4px;letter-spacing:-0.4px}
.up-breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:#7B4F2C}
.up-btn-tambah{display:inline-flex;align-items:center;gap:8px;
    background:#4A2C18;color:#fff;
    padding:10px 20px;border-radius:10px;
    font-size:14px;font-weight:700;
    font-family:'Montserrat',sans-serif;letter-spacing:0.2px;
    border:none;cursor:pointer;text-decoration:none;white-space:nowrap;
    box-shadow:0 2px 8px rgba(74,44,24,0.28);
    transition:all 0.2s cubic-bezier(0.4,0,0.2,1)}
.up-btn-tambah:hover{background:#4D1E0A;transform:translateY(-1px);
    box-shadow:0 4px 14px rgba(77,30,10,0.36);color:#fff}
.up-btn-tambah:active{transform:translateY(0)}
.up-btn-tambah i{font-size:18px}

/* NOTIF */
.up-notif{display:flex;align-items:center;gap:10px;padding:12px 16px;
    border-radius:var(--r);font-size:13.5px;font-weight:500;
    margin-bottom:18px;box-shadow:var(--sh-sm)}
.up-notif.sukses {background:var(--green-bg);color:var(--green-tx);border-left:4px solid #10B981}
.up-notif.warning{background:var(--ylw-bg);color:var(--ylw-tx);border-left:4px solid #F59E0B}
.up-notif.error  {background:var(--red-bg);color:var(--red-tx);border-left:4px solid #EF4444}
.up-notif-ico{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;
    justify-content:center;flex-shrink:0;background:rgba(0,0,0,.06)}
.up-notif-cls{margin-left:auto;background:none;border:none;cursor:pointer;
    font-size:17px;color:inherit;opacity:.6;padding:2px;line-height:1}
.up-notif-cls:hover{opacity:1}

/* CARD */
.up-card{background:#fff;border-radius:var(--r);box-shadow:var(--sh-md);
    overflow:hidden;margin-bottom:24px}
.up-card-head{padding:18px 20px 14px;border-bottom:1px solid var(--g100);
    display:flex;flex-direction:column;gap:14px}
.up-card-title{display:flex;align-items:center;gap:10px;
    font-size:15px;font-weight:700;color:var(--br-700)}
.up-title-ico{width:32px;height:32px;background:var(--br-100);border-radius:8px;
    display:flex;align-items:center;justify-content:center}
.up-title-ico i{color:var(--br-700);font-size:17px}
.up-badge-count{background:var(--br-100);color:var(--br-600);font-size:11.5px;
    font-weight:600;padding:3px 10px;border-radius:20px}

/* TOOLBAR */
.up-toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.up-search-wrap{position:relative;flex:1;min-width:200px}
.up-search-wrap i{position:absolute;left:10px;top:50%;transform:translateY(-50%);
    color:var(--g400);font-size:16px;pointer-events:none}
.up-search{width:100%;padding:8px 12px 8px 34px;border:1.5px solid var(--g200);
    border-radius:8px;font-size:13px;color:var(--g800);outline:none;
    transition:border-color .2s;background:var(--g50)}
.up-search:focus{border-color:var(--br-600);background:#fff}
.up-select{padding:8px 12px;border:1.5px solid var(--g200);border-radius:8px;
    font-size:13px;color:var(--g600);outline:none;cursor:pointer;
    background:var(--g50);transition:border-color .2s}
.up-select:focus{border-color:var(--br-600)}
.up-btn-refresh{display:flex;align-items:center;gap:6px;padding:8px 16px;
    background:var(--br-700);color:#fff;border:none;border-radius:8px;
    font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;white-space:nowrap}
.up-btn-refresh:hover{background:var(--br-600)}
.up-btn-refresh i{font-size:16px}
@keyframes spin{to{transform:rotate(360deg)}}
.spinning{animation:spin .6s linear infinite}

/* INFO BAR */
.up-infobar{padding:9px 20px;background:var(--g50);border-bottom:1px solid var(--g100);
    font-size:12.5px;color:var(--muted);display:flex;align-items:center;
    gap:5px;flex-wrap:wrap}
.up-infobar strong{color:var(--br-700)}

/* TABLE */
.up-table-wrap{overflow-x:auto}
.up-table{width:100%;border-collapse:collapse;font-size:13px;min-width:700px}
.up-table thead tr{background:var(--g50);border-bottom:2px solid var(--g200)}
.up-table thead th{padding:11px 14px;text-align:left;font-size:11.5px;
    font-weight:700;text-transform:uppercase;letter-spacing:.05em;
    color:var(--muted);white-space:nowrap}
.up-table thead th a{color:inherit;text-decoration:none;display:inline-flex;
    align-items:center;gap:4px;transition:color .15s}
.up-table thead th a:hover{color:var(--br-700)}
.up-table tbody tr{border-bottom:1px solid var(--g100);transition:background .15s}
.up-table tbody tr:hover{background:var(--br-100)}
.up-table tbody tr:last-child{border-bottom:none}
.up-table td{padding:11px 14px;vertical-align:middle;color:var(--g800)}

/* AVATAR */
.up-avatar{width:36px;height:36px;border-radius:50%;background:var(--br-700);
    color:#fff;display:flex;align-items:center;justify-content:center;
    font-size:14px;font-weight:700;flex-shrink:0;text-transform:uppercase}
.up-name-cell{display:flex;align-items:center;gap:10px}
.up-name{font-weight:600;color:var(--g800);max-width:150px;
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.up-uname{font-size:11.5px;color:var(--muted);margin-top:1px}
.up-email{color:var(--muted);font-size:12.5px;max-width:190px;
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* BADGE */
.up-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;
    border-radius:20px;font-size:11.5px;font-weight:600;white-space:nowrap}
.b-admin{background:var(--br-700);color:#fff}
.b-user {background:var(--g200);color:var(--g600)}
.up-date{color:var(--muted);font-size:12px;white-space:nowrap}
.up-rownum{color:var(--g400);font-size:12px}

/* AKSI */
.up-aksi{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
.up-btn{display:inline-flex;align-items:center;gap:4px;padding:5px 11px;
    border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;
    transition:opacity .15s,transform .1s;border:none;cursor:pointer;white-space:nowrap}
.up-btn:hover{opacity:.85;transform:translateY(-1px)}
.up-btn-hapus{background:var(--red-bg);color:var(--red-tx)}
.up-btn-warn {background:var(--ylw-bg);color:var(--ylw-tx)}

/* NO DATA */
.up-nodata{text-align:center;padding:50px 20px;color:var(--g400)}
.up-nodata i{font-size:48px;display:block;margin-bottom:12px}
.up-nodata p{font-size:14px;margin-bottom:4px}
.up-nodata small{font-size:12px}

/* PAGINATION */
.up-pagination{display:flex;align-items:center;gap:6px;padding:14px 20px;
    border-top:1px solid var(--g100);flex-wrap:wrap}
.pg-btn{display:flex;align-items:center;justify-content:center;min-width:34px;
    height:34px;padding:0 8px;border-radius:7px;font-size:13px;font-weight:600;
    text-decoration:none;color:var(--g600);background:var(--g100);
    transition:background .15s,color .15s;border:none;cursor:pointer}
.pg-btn:hover{background:var(--br-100);color:var(--br-700)}
.pg-btn.aktif{background:var(--br-700);color:#fff}
.pg-btn.off{opacity:.35;cursor:not-allowed;pointer-events:none}
.pg-dots{color:var(--g400);font-size:13px;padding:0 2px}
.pg-info{margin-left:auto;font-size:12px;color:var(--muted)}

/* MODAL */
.up-modal-ov{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;
    display:flex;align-items:center;justify-content:center;padding:20px;
    opacity:0;pointer-events:none;transition:opacity .2s}
.up-modal-ov.show{opacity:1;pointer-events:all}
.up-modal-box{background:#fff;border-radius:14px;padding:28px 28px 24px;
    width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.2);
    transform:translateY(20px);transition:transform .2s}
.up-modal-ov.show .up-modal-box{transform:translateY(0)}
.up-modal-title{font-size:16px;font-weight:700;color:var(--g800);margin-bottom:4px}
.up-modal-sub{font-size:12.5px;color:var(--muted);margin-bottom:14px}
.up-modal-ta{width:100%;min-height:90px;padding:10px 12px;
    border:1.5px solid var(--g200);border-radius:8px;font-size:13px;
    resize:vertical;outline:none;transition:border-color .2s;font-family:inherit}
.up-modal-ta:focus{border-color:var(--br-600)}
.up-modal-hint{font-size:11px;color:var(--muted);margin-top:4px}
.up-modal-foot{display:flex;gap:8px;justify-content:flex-end;margin-top:14px}
.up-modal-ok{padding:8px 18px;background:var(--br-700);color:#fff;border:none;
    border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s}
.up-modal-ok:hover{background:var(--br-600)}
.up-modal-cancel{padding:8px 16px;background:var(--g100);color:var(--g600);
    border:none;border-radius:8px;font-size:13px;font-weight:600;
    cursor:pointer;transition:background .2s}
.up-modal-cancel:hover{background:var(--g200)}

/* FORM TAMBAH USER */
.up-form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.up-form-row.full{grid-template-columns:1fr}
.up-form-group{display:flex;flex-direction:column;gap:5px}
.up-form-label{font-size:12.5px;font-weight:600;color:var(--g600)}
.up-form-label span{color:#EF4444;margin-left:2px}
.up-form-input{padding:9px 12px;border:1.5px solid var(--g200);border-radius:8px;
    font-size:13px;color:var(--g800);outline:none;transition:border-color .2s;
    background:var(--g50);font-family:inherit;width:100%}
.up-form-input:focus{border-color:var(--br-600);background:#fff}
.up-form-input.err{border-color:#EF4444;background:#FFF5F5}
.up-form-hint{font-size:11px;color:var(--muted)}
.up-form-err-list{background:var(--red-bg);color:var(--red-tx);border-left:3px solid #EF4444;
    border-radius:6px;padding:10px 14px;font-size:12.5px;margin-bottom:14px}
.up-form-err-list li{margin-bottom:3px}
.up-form-err-list li:last-child{margin-bottom:0}
.up-pwd-wrap{position:relative}
.up-pwd-wrap input{padding-right:38px}
.up-pwd-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);
    background:none;border:none;cursor:pointer;color:var(--g400);font-size:17px;
    padding:2px;line-height:1;transition:color .15s}
.up-pwd-toggle:hover{color:var(--br-600)}
.up-modal-divider{height:1px;background:var(--g100);margin:16px 0}
@media(max-width:480px){.up-form-row{grid-template-columns:1fr}}

/* RESPONSIVE */
@media(max-width:768px){
    .up-header{flex-direction:column}
    .up-toolbar{flex-direction:column}
    .up-search-wrap{min-width:100%}
    .pg-info{margin-left:0}
}
</style>

<div class="up">

<!-- NOTIFIKASI -->
<?php if ($pesan): ?>
<div class="up-notif <?= $tipe ?>" id="up-notif">
    <div class="up-notif-ico">
        <i class='bx <?= $tipe==="sukses"?"bx-check":($tipe==="error"?"bx-x":"bx-error") ?>'
           style="font-size:17px"></i>
    </div>
    <span><?= $pesan ?></span>
    <button class="up-notif-cls" onclick="this.closest('.up-notif').remove()">
        <i class='bx bx-x'></i>
    </button>
</div>
<?php endif; ?>

<!-- PAGE HEADER -->
<div class="up-header">
    <div>
        <h1>Data User</h1>
        <div class="up-breadcrumb">
            <i class='bx bx-home-alt' style="font-size:13px"></i>
            <span>Dashboard</span>
            <i class='bx bx-chevron-right' style="font-size:14px"></i>
            <span style="color:var(--br-600);font-weight:600">Data User</span>
        </div>
    </div>
    <button type="button" class="up-btn-tambah" onclick="upOpenTambah()">
        <i class='bx bx-user-plus'></i> Tambah User Baru
    </button>
</div>

<!-- TABEL CARD -->
<div class="up-card">

    <!-- Header card: judul + toolbar -->
    <div class="up-card-head">
        <div class="up-card-title">
            <div class="up-title-ico"><i class='bx bx-group'></i></div>
            Daftar User Terdaftar
            <span class="up-badge-count"><?= number_format($total_data) ?> user</span>
        </div>

        <form method="get" action="dashboardAdmin.php" id="up-filter-form">
            <input type="hidden" name="page" value="user">
            <div class="up-toolbar">

                <!-- Search realtime -->
                <div class="up-search-wrap">
                    <i class='bx bx-search'></i>
                    <input type="text" name="cari" id="up-search"
                           class="up-search"
                           placeholder="Cari nama, username, email..."
                           value="<?= htmlspecialchars($cari) ?>"
                           autocomplete="off">
                </div>

                <!-- Filter Role -->
                <select name="role" class="up-select"
                        onchange="document.getElementById('up-filter-form').submit()">
                    <option value="">Semua Role</option>
                    <option value="admin" <?= $filter_role==='admin'?'selected':'' ?>>Admin</option>
                    <option value="user"  <?= $filter_role==='user' ?'selected':'' ?>>User</option>
                </select>

                <!-- Refresh -->
                <button type="button" class="up-btn-refresh" onclick="upRefresh()">
                    <i class='bx bx-refresh' id="up-ref-ico"></i>
                    Refresh
                </button>

                <button type="submit" style="display:none"></button>
            </div>
        </form>
    </div>

    <!-- Info bar -->
    <div class="up-infobar">
        <i class='bx bx-info-circle' style="font-size:14px"></i>
        Menampilkan <strong><?= count($user_list) ?></strong>
        dari <strong><?= number_format($total_data) ?></strong> user
        <?php if ($cari):        ?>&nbsp;·&nbsp; Pencarian: <strong>"<?= htmlspecialchars($cari) ?>"</strong><?php endif ?>
        <?php if ($filter_role): ?>&nbsp;·&nbsp; Role: <strong><?= ucfirst($filter_role) ?></strong><?php endif ?>
    </div>

    <!-- TABEL -->
    <div class="up-table-wrap">
        <table class="up-table">
            <thead>
                <tr>
                    <th style="width:44px">No</th>
                    <th>
                        <a href="<?= userUrl(['sort'=>'nama_lengkap','dir'=>$sort_by==='nama_lengkap'?$sort_next:'ASC','halaman'=>1]) ?>">
                            Nama <i class='bx <?= $sort_by==="nama_lengkap"?($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt"):"bx-sort-alt-2" ?>'></i>
                        </a>
                    </th>
                    <th>
                        <a href="<?= userUrl(['sort'=>'email','dir'=>$sort_by==='email'?$sort_next:'ASC','halaman'=>1]) ?>">
                            Email <i class='bx <?= $sort_by==="email"?($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt"):"bx-sort-alt-2" ?>'></i>
                        </a>
                    </th>
                    <th>
                        <a href="<?= userUrl(['sort'=>'role','dir'=>$sort_by==='role'?$sort_next:'ASC','halaman'=>1]) ?>">
                            Role <i class='bx <?= $sort_by==="role"?($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt"):"bx-sort-alt-2" ?>'></i>
                        </a>
                    </th>
                    <th>
                        <a href="<?= userUrl(['sort'=>'created_at','dir'=>$sort_by==='created_at'?$sort_next:'DESC','halaman'=>1]) ?>">
                            Tanggal Daftar <i class='bx <?= $sort_by==="created_at"?($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt"):"bx-sort-alt-2" ?>'></i>
                        </a>
                    </th>
                    <th style="text-align:right;padding-right:20px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($user_list)): ?>
                <tr>
                    <td colspan="6">
                        <div class="up-nodata">
                            <i class='bx bx-user-x'></i>
                            <p>Tidak ada user ditemukan</p>
                            <small><?= $cari ? 'Coba ubah kata kunci pencarian.' : 'Belum ada user terdaftar.' ?></small>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($user_list as $i => $u):
                    $initial = mb_strtoupper(mb_substr($u['nama_lengkap'] ?? 'U', 0, 1));
                    $no      = ($halaman - 1) * $per_halaman + $i + 1;
                ?>
                <tr>
                    <td><span class="up-rownum"><?= $no ?></span></td>

                    <!-- Nama + Username -->
                    <td>
                        <div class="up-name-cell">
                            <div class="up-avatar"><?= htmlspecialchars($initial) ?></div>
                            <div>
                                <div class="up-name" title="<?= htmlspecialchars($u['nama_lengkap']) ?>">
                                    <?= htmlspecialchars($u['nama_lengkap']) ?>
                                </div>
                                <div class="up-uname">@<?= htmlspecialchars($u['username']) ?></div>
                            </div>
                        </div>
                    </td>

                    <!-- Email -->
                    <td>
                        <span class="up-email" title="<?= htmlspecialchars($u['email']) ?>">
                            <?= htmlspecialchars($u['email']) ?>
                        </span>
                    </td>

                    <!-- Role -->
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="up-badge b-admin"><i class='bx bx-shield'></i> Admin</span>
                        <?php else: ?>
                            <span class="up-badge b-user"><i class='bx bx-user'></i> User</span>
                        <?php endif ?>
                    </td>

                    <!-- Tanggal Daftar -->
                    <td>
                        <span class="up-date"><?= date('d M Y', strtotime($u['created_at'])) ?></span>
                    </td>

                    <!-- Aksi -->
                    <td>
                        <div class="up-aksi">
                        <?php if ($u['role'] !== 'admin'): ?>

                            <!-- Peringatan -->
                            <button type="button"
                                    class="up-btn up-btn-warn"
                                    onclick="upOpenWarn(<?= $u['id'] ?>,'<?= addslashes(htmlspecialchars($u['nama_lengkap'])) ?>')">
                                <i class='bx bx-bell'></i> Peringatan
                            </button>

                            <!-- Hapus -->
                            <a href="<?= userUrl(['hapus'=>$u['id']]) ?>"
                               onclick="return upConfirmHapus('<?= addslashes(htmlspecialchars($u['nama_lengkap'])) ?>')"
                               class="up-btn up-btn-hapus">
                                <i class='bx bx-trash'></i> Hapus
                            </a>

                        <?php else: ?>
                            <span style="color:var(--g400);font-size:12px">— Admin —</span>
                        <?php endif ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_page > 1): ?>
    <div class="up-pagination">

        <?php if ($halaman > 1): ?>
            <a href="<?= userUrl(['halaman'=>$halaman-1]) ?>" class="pg-btn"><i class='bx bx-chevron-left'></i></a>
        <?php else: ?>
            <span class="pg-btn off"><i class='bx bx-chevron-left'></i></span>
        <?php endif ?>

        <?php for ($p = 1; $p <= $total_page; $p++):
            if ($p === $halaman): ?>
                <span class="pg-btn aktif"><?= $p ?></span>
            <?php elseif ($p === 1 || $p === $total_page || abs($p - $halaman) <= 2): ?>
                <a href="<?= userUrl(['halaman'=>$p]) ?>" class="pg-btn"><?= $p ?></a>
            <?php elseif (abs($p - $halaman) === 3): ?>
                <span class="pg-dots">…</span>
            <?php endif ?>
        <?php endfor ?>

        <?php if ($halaman < $total_page): ?>
            <a href="<?= userUrl(['halaman'=>$halaman+1]) ?>" class="pg-btn"><i class='bx bx-chevron-right'></i></a>
        <?php else: ?>
            <span class="pg-btn off"><i class='bx bx-chevron-right'></i></span>
        <?php endif ?>

        <span class="pg-info">
            Halaman <strong><?= $halaman ?></strong> dari <strong><?= $total_page ?></strong>
        </span>
    </div>
    <?php endif ?>

</div><!-- /up-card -->

<!-- MODAL TAMBAH USER -->
<div class="up-modal-ov" id="up-modal-tambah">
    <div class="up-modal-box" style="max-width:500px">
        <div class="up-modal-title">
            <i class='bx bx-user-plus' style="color:var(--green-tx);margin-right:6px"></i>
            Tambah User Baru
        </div>
        <div class="up-modal-sub">Isi data lengkap untuk membuat akun user baru.</div>

        <?php if (!empty($err_tambah)): ?>
        <ul class="up-form-err-list">
            <?php foreach ($err_tambah as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach ?>
        </ul>
        <?php endif ?>

        <form method="post" action="dashboardAdmin.php?page=user" id="up-form-tambah">
            <input type="hidden" name="aksi" value="tambah_user">

            <!-- Nama Lengkap -->
            <div class="up-form-row full" style="margin-bottom:12px">
                <div class="up-form-group">
                    <label class="up-form-label">Nama Lengkap <span>*</span></label>
                    <input type="text" name="t_nama" class="up-form-input <?= !empty($err_tambah)?'err':'' ?>"
                           placeholder="cth: Budi Santoso"
                           value="<?= htmlspecialchars($form_tambah['t_nama'] ?? '') ?>"
                           maxlength="100" required>
                </div>
            </div>

            <!-- Email & Username -->
            <div class="up-form-row" style="margin-bottom:12px">
                <div class="up-form-group">
                    <label class="up-form-label">Email <span>*</span></label>
                    <input type="email" name="t_email" class="up-form-input <?= !empty($err_tambah)?'err':'' ?>"
                           placeholder="cth: budi@email.com"
                           value="<?= htmlspecialchars($form_tambah['t_email'] ?? '') ?>"
                           maxlength="100" required>
                </div>
                <div class="up-form-group">
                    <label class="up-form-label">Username <span>*</span></label>
                    <input type="text" name="t_username" class="up-form-input <?= !empty($err_tambah)?'err':'' ?>"
                           placeholder="cth: budi123"
                           value="<?= htmlspecialchars($form_tambah['t_username'] ?? '') ?>"
                           maxlength="50" required>
                </div>
            </div>

            <!-- Password & Role -->
            <div class="up-form-row" style="margin-bottom:12px">
                <div class="up-form-group">
                    <label class="up-form-label">Password <span>*</span></label>
                    <div class="up-pwd-wrap">
                        <input type="password" name="t_password" id="up-t-pwd"
                               class="up-form-input"
                               placeholder="Min. 6 karakter"
                               maxlength="100" required>
                        <button type="button" class="up-pwd-toggle" onclick="upTogglePwd()">
                            <i class='bx bx-hide' id="up-pwd-ico"></i>
                        </button>
                    </div>
                    <span class="up-form-hint">Minimal 6 karakter</span>
                </div>
                <div class="up-form-group">
                    <label class="up-form-label">Role <span>*</span></label>
                    <select name="t_role" class="up-form-input">
                        <option value="user"  <?= ($form_tambah['t_role']??'user')==='user' ?'selected':'' ?>>User</option>
                        <option value="admin" <?= ($form_tambah['t_role']??'')==='admin'?'selected':'' ?>>Admin</option>
                    </select>
                </div>
            </div>

            <div class="up-modal-divider"></div>
            <div class="up-modal-foot">
                <button type="button" class="up-modal-cancel" onclick="upCloseTambah()">Batal</button>
                <button type="submit" class="up-modal-ok" style="background:var(--green-tx)">
                    <i class='bx bx-user-plus'></i> Simpan User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL PERINGATAN -->
<div class="up-modal-ov" id="up-modal-warn">
    <div class="up-modal-box">
        <div class="up-modal-title">
            <i class='bx bx-bell' style="color:var(--ylw-tx);margin-right:6px"></i>
            Kirim Peringatan
        </div>
        <div class="up-modal-sub" id="up-warn-sub">Peringatan untuk user</div>
        <form method="post" action="dashboardAdmin.php?page=user">
            <input type="hidden" name="aksi"    value="peringatan">
            <input type="hidden" name="warn_id" id="up-warn-id" value="">
            <textarea name="warn_msg" class="up-modal-ta"
                      placeholder="Tulis pesan peringatan untuk user ini..."
                      maxlength="500" required></textarea>
            <div class="up-modal-hint">Peringatan dicatat di sistem dan dikirim ke user (maks. 500 karakter).</div>
            <div class="up-modal-foot">
                <button type="button" class="up-modal-cancel" onclick="upCloseModal()">Batal</button>
                <button type="submit" class="up-modal-ok">
                    <i class='bx bx-send'></i> Kirim
                </button>
            </div>
        </form>
    </div>
</div>

</div><!-- /up -->

<script>
// Refresh
function upRefresh() {
    const ico = document.getElementById('up-ref-ico');
    ico.classList.add('spinning');
    setTimeout(() => location.reload(), 300);
}

// Konfirmasi hapus
function upConfirmHapus(nama) {
    return confirm('Hapus user "' + nama + '"?\n\nData dihapus permanen dan tidak bisa dikembalikan.');
}

// Modal Tambah User
function upOpenTambah() {
    document.getElementById('up-modal-tambah').classList.add('show');
}
function upCloseTambah() {
    document.getElementById('up-modal-tambah').classList.remove('show');
}
document.getElementById('up-modal-tambah').addEventListener('click', function(e) {
    if (e.target === this) upCloseTambah();
});
// Toggle show/hide password
function upTogglePwd() {
    const inp = document.getElementById('up-t-pwd');
    const ico = document.getElementById('up-pwd-ico');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'bx bx-show';
    } else {
        inp.type = 'password';
        ico.className = 'bx bx-hide';
    }
}
// Buka modal otomatis jika ada error validasi
<?php if ($open_modal_tambah): ?>
window.addEventListener('DOMContentLoaded', () => upOpenTambah());
<?php endif ?>

// Modal Peringatan
function upOpenWarn(id, nama) {
    document.getElementById('up-warn-id').value = id;
    document.getElementById('up-warn-sub').textContent = 'Peringatan untuk: ' + nama;
    document.getElementById('up-modal-warn').classList.add('show');
}
function upCloseModal() {
    document.getElementById('up-modal-warn').classList.remove('show');
}
document.getElementById('up-modal-warn').addEventListener('click', function(e) {
    if (e.target === this) upCloseModal();
});

// Search realtime (debounce 400ms)
(function() {
    let t;
    const s = document.getElementById('up-search');
    if (!s) return;
    s.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => document.getElementById('up-filter-form').submit(), 400);
    });
    s.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); clearTimeout(t); document.getElementById('up-filter-form').submit(); }
        if (e.key === 'Escape') { s.value = ''; clearTimeout(t); document.getElementById('up-filter-form').submit(); }
    });
})();

// Auto-hide notif 5s
(function() {
    const n = document.getElementById('up-notif');
    if (!n) return;
    setTimeout(() => {
        n.style.transition = 'opacity .4s';
        n.style.opacity = '0';
        setTimeout(() => n.remove(), 400);
    }, 5000);
})();
</script>