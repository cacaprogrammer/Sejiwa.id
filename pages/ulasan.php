<?php
// ============================================================
// pages/ulasan.php  —  Halaman Kelola Ulasan (Admin Dashboard)
// Tabel: tb_ulasan (id, user_id, artikel_id, rating, komentar, created_at)
//        JOIN tb_user (nama_lengkap, username)
//        JOIN tb_artikel (judul)
// ============================================================

// ===== KEAMANAN: Hanya admin =====
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit();
}

// ===== AUTO-CREATE: kolom flagged & tabel tb_notifikasi (jika belum ada) =====
$conn->query("ALTER TABLE tb_ulasan ADD COLUMN IF NOT EXISTS flagged TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE tb_ulasan ADD COLUMN IF NOT EXISTS admin_note TEXT DEFAULT NULL");
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
function kirimNotifikasiUlasan($conn, $user_id, $judul, $pesan, $tipe = 'info') {
    $stmt = $conn->prepare(
        "INSERT INTO tb_notifikasi (user_id, judul, pesan, tipe) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("isss", $user_id, $judul, $pesan, $tipe);
    $stmt->execute();
}

// ===== PROSES: PERINGATAN USER VIA ULASAN =====
if (isset($_POST['aksi']) && $_POST['aksi'] === 'peringatan_ulasan') {
    $ulasan_id = (int)$_POST['ulasan_id'];
    $warn_msg  = mb_substr(strip_tags(trim($_POST['warn_msg'])), 0, 500);

    $stmt_uid = $conn->prepare("SELECT user_id FROM tb_ulasan WHERE id = ?");
    $stmt_uid->bind_param("i", $ulasan_id);
    $stmt_uid->execute();
    $row_uid = $stmt_uid->get_result()->fetch_assoc();

    if ($row_uid) {
        $uid = $row_uid['user_id'];

        $stmt_flag = $conn->prepare("UPDATE tb_ulasan SET flagged = 1, admin_note = ? WHERE id = ?");
        $stmt_flag->bind_param("si", $warn_msg, $ulasan_id);
        $stmt_flag->execute();

        $stmt_w = $conn->prepare("UPDATE tb_user SET warning_note = ? WHERE id = ? AND role != 'admin'");
        $stmt_w->bind_param("si", $warn_msg, $uid);
        $stmt_w->execute();

        $pesan_notif = $warn_msg ?: 'Kamu mendapat peringatan dari administrator terkait ulasan yang kamu tulis. Harap perhatikan aturan penggunaan platform.';
        kirimNotifikasiUlasan(
            $conn, $uid,
            '⚠️ Peringatan: Ulasan Kamu Melanggar Aturan',
            $pesan_notif,
            'peringatan'
        );
    }

    echo "<script>window.location.href='dashboardAdmin.php?page=ulasan&msg=peringatan';</script>";
    exit();
}

// ===== PARAMETER: Search, Filter, Sort, Pagination =====
$cari           = isset($_GET['cari'])    ? trim($_GET['cari'])           : '';
$filter_rating  = isset($_GET['rating'])  ? (int)$_GET['rating']         : 0;
$filter_flagged = isset($_GET['flagged']) ? $_GET['flagged']              : '';
$sort_by        = isset($_GET['sort'])    ? $_GET['sort']                 : 'u.created_at';
$sort_dir       = isset($_GET['dir'])     ? strtoupper($_GET['dir'])      : 'DESC';
$halaman        = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman    = 10;

$allowed_sort = ['u.created_at', 'u.rating', 'usr.nama_lengkap', 'a.judul'];
if (!in_array($sort_by, $allowed_sort)) $sort_by = 'u.created_at';
$sort_dir  = ($sort_dir === 'ASC') ? 'ASC' : 'DESC';
$sort_next = ($sort_dir === 'ASC') ? 'DESC' : 'ASC';

// ===== BUILD QUERY =====
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($cari !== '') {
    $where   .= " AND (usr.nama_lengkap LIKE ? OR usr.username LIKE ? OR u.komentar LIKE ? OR a.judul LIKE ?)";
    $keyword  = "%$cari%";
    $params   = array_merge($params, [$keyword, $keyword, $keyword, $keyword]);
    $types   .= "ssss";
}
if ($filter_rating >= 1 && $filter_rating <= 5) {
    $where  .= " AND u.rating = ?";
    $params  = array_merge($params, [$filter_rating]);
    $types  .= "i";
}
if ($filter_flagged === '1') {
    $where .= " AND u.flagged = 1";
} elseif ($filter_flagged === '0') {
    $where .= " AND u.flagged = 0";
}

$base_join = "FROM tb_ulasan u
              JOIN tb_user    usr ON usr.id = u.user_id
              JOIN tb_artikel a   ON a.id   = u.artikel_id";

$count_stmt = $conn->prepare("SELECT COUNT(*) AS total $base_join $where");
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_data = $count_stmt->get_result()->fetch_assoc()['total'];
$total_page = max(1, (int)ceil($total_data / $per_halaman));
if ($halaman > $total_page) $halaman = $total_page;
$offset = ($halaman - 1) * $per_halaman;

$sql  = "SELECT u.id, u.rating, u.komentar, u.created_at, u.flagged, u.admin_note,
                u.user_id,
                usr.nama_lengkap, usr.username,
                a.judul AS judul_artikel, a.slug AS slug_artikel
         $base_join $where
         ORDER BY $sort_by $sort_dir
         LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types . "ii", ...array_merge($params, [$per_halaman, $offset]));
$stmt->execute();
$ulasan_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ===== STATISTIK RINGKAS =====
$stat = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(flagged = 1) AS flagged,
        ROUND(AVG(rating),1) AS avg_rating,
        SUM(komentar IS NOT NULL AND komentar != '') AS dengan_komentar
    FROM tb_ulasan
")->fetch_assoc();

// ===== HELPER URL =====
function ulasanUrl($extra) {
    $base = array_merge($_GET, $extra);
    unset($base['msg']);
    return 'dashboardAdmin.php?' . http_build_query($base);
}

// ===== BINTANG RENDER =====
function renderBintang($r) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<i class="bx ' . ($i <= $r ? 'bxs-star' : 'bx-star') . '" style="color:' . ($i <= $r ? '#F59E0B' : '#D1D5DB') . ';font-size:13px"></i>';
    }
    return $out;
}

// ===== PESAN NOTIFIKASI =====
$notif_map = [
    'peringatan' => ['warning', '📩 Peringatan berhasil dikirim ke user.'],
    'error'      => ['error',   '⛔ Terjadi kesalahan, coba lagi.'],
];
$pesan = $tipe = '';
if (isset($_GET['msg']) && isset($notif_map[$_GET['msg']])) {
    [$tipe, $pesan] = $notif_map[$_GET['msg']];
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<style>
/* ===== VARIABEL ===== */
:root {
    --br-900:#3B1F0E; --br-700:#4A2C18; --br-600:#6B3E23;
    --br-400:#A3826F; --br-100:#F5EDE6;
    --green-bg:#ECFDF5; --green-tx:#065F46;
    --red-bg:#FEF2F2;   --red-tx:#991B1B;
    --ylw-bg:#FFFBEB;   --ylw-tx:#92400E;
    --g50:#F9FAFB; --g100:#F3F4F6; --g200:#E5E7EB;
    --g400:#9CA3AF; --g600:#4B5563; --g800:#1F2937;
    --muted:#6B7280;
    --r:10px;
    --sh-sm:0 1px 3px rgba(0,0,0,.07),0 1px 2px rgba(0,0,0,.04);
    --sh-md:0 4px 6px -1px rgba(0,0,0,.08),0 2px 4px -1px rgba(0,0,0,.04);
}
.ul *{box-sizing:border-box}

/* PAGE HEADER */
.ul-header{display:flex;justify-content:space-between;align-items:flex-start;
    margin-bottom:24px;flex-wrap:wrap;gap:12px}
.ul-header h1{font-family:'Montserrat',sans-serif;font-size:26px;font-weight:800;
    color:#4D1E0A;margin:0 0 4px;letter-spacing:-0.4px}
.ul-breadcrumb{display:flex;align-items:center;gap:6px;font-size:13px;color:#7B4F2C}

/* NOTIF */
.ul-notif{display:flex;align-items:center;gap:10px;padding:12px 16px;
    border-radius:var(--r);font-size:13.5px;font-weight:500;
    margin-bottom:18px;box-shadow:var(--sh-sm)}
.ul-notif.warning{background:var(--ylw-bg);color:var(--ylw-tx);border-left:4px solid #F59E0B}
.ul-notif.error  {background:var(--red-bg);color:var(--red-tx);border-left:4px solid #EF4444}
.ul-notif-ico{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;
    justify-content:center;flex-shrink:0;background:rgba(0,0,0,.06)}
.ul-notif-cls{margin-left:auto;background:none;border:none;cursor:pointer;
    font-size:17px;color:inherit;opacity:.6;padding:2px;line-height:1}
.ul-notif-cls:hover{opacity:1}

/* STAT CARDS */
.ul-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
.ul-stat{background:#fff;border-radius:var(--r);padding:16px 18px;
    box-shadow:var(--sh-sm);display:flex;align-items:center;gap:14px;
    border-left:4px solid transparent}
.ul-stat.s-total  {border-left-color:var(--br-600)}
.ul-stat.s-flagged{border-left-color:#EF4444}
.ul-stat.s-rating {border-left-color:#F59E0B}
.ul-stat.s-komen  {border-left-color:#10B981}
.ul-stat-ico{width:42px;height:42px;border-radius:10px;display:flex;
    align-items:center;justify-content:center;flex-shrink:0}
.s-total  .ul-stat-ico{background:var(--br-100)}
.s-flagged .ul-stat-ico{background:#FEF2F2}
.s-rating .ul-stat-ico{background:#FFFBEB}
.s-komen  .ul-stat-ico{background:#ECFDF5}
.ul-stat-ico i{font-size:20px}
.s-total  .ul-stat-ico i{color:var(--br-600)}
.s-flagged .ul-stat-ico i{color:#EF4444}
.s-rating .ul-stat-ico i{color:#F59E0B}
.s-komen  .ul-stat-ico i{color:#10B981}
.ul-stat-val{font-family:'Montserrat',sans-serif;font-size:22px;
    font-weight:800;color:var(--g800);line-height:1.1}
.ul-stat-lbl{font-size:12px;color:var(--muted);margin-top:2px}

/* CARD */
.ul-card{background:#fff;border-radius:var(--r);box-shadow:var(--sh-md);
    overflow:hidden;margin-bottom:24px}
.ul-card-head{padding:18px 20px 14px;border-bottom:1px solid var(--g100);
    display:flex;flex-direction:column;gap:14px}
.ul-card-title{display:flex;align-items:center;gap:10px;
    font-size:15px;font-weight:700;color:var(--br-700)}
.ul-title-ico{width:32px;height:32px;background:var(--br-100);border-radius:8px;
    display:flex;align-items:center;justify-content:center}
.ul-title-ico i{color:var(--br-700);font-size:17px}
.ul-badge-count{background:var(--br-100);color:var(--br-600);font-size:11.5px;
    font-weight:600;padding:3px 10px;border-radius:20px}

/* TOOLBAR */
.ul-toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.ul-search-wrap{position:relative;flex:1;min-width:200px}
.ul-search-wrap i{position:absolute;left:10px;top:50%;transform:translateY(-50%);
    color:var(--g400);font-size:16px;pointer-events:none}
.ul-search{width:100%;padding:8px 12px 8px 34px;border:1.5px solid var(--g200);
    border-radius:8px;font-size:13px;color:var(--g800);outline:none;
    transition:border-color .2s;background:var(--g50)}
.ul-search:focus{border-color:var(--br-600);background:#fff}
.ul-select{padding:8px 12px;border:1.5px solid var(--g200);border-radius:8px;
    font-size:13px;color:var(--g600);outline:none;cursor:pointer;
    background:var(--g50);transition:border-color .2s}
.ul-select:focus{border-color:var(--br-600)}
.ul-btn-refresh{display:flex;align-items:center;gap:6px;padding:8px 16px;
    background:var(--br-700);color:#fff;border:none;border-radius:8px;
    font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;white-space:nowrap}
.ul-btn-refresh:hover{background:var(--br-600)}
@keyframes spin{to{transform:rotate(360deg)}}
.spinning{animation:spin .6s linear infinite}

/* INFO BAR */
.ul-infobar{padding:9px 20px;background:var(--g50);border-bottom:1px solid var(--g100);
    font-size:12.5px;color:var(--muted);display:flex;align-items:center;
    gap:5px;flex-wrap:wrap}
.ul-infobar strong{color:var(--br-700)}

/* TABLE */
.ul-table-wrap{overflow-x:auto}
.ul-table{width:100%;border-collapse:collapse;font-size:13px;min-width:720px}
.ul-table thead tr{background:var(--g50);border-bottom:2px solid var(--g200)}
.ul-table thead th{padding:11px 14px;text-align:left;font-size:11.5px;
    font-weight:700;text-transform:uppercase;letter-spacing:.05em;
    color:var(--muted);white-space:nowrap}
.ul-table thead th a{color:inherit;text-decoration:none;display:inline-flex;
    align-items:center;gap:4px;transition:color .15s}
.ul-table thead th a:hover{color:var(--br-700)}
.ul-table tbody tr{border-bottom:1px solid var(--g100);transition:background .15s}
.ul-table tbody tr:hover{background:var(--br-100)}
.ul-table tbody tr.flagged-row{background:#FFF8F8}
.ul-table tbody tr.flagged-row:hover{background:#FEF2F2}
.ul-table tbody tr:last-child{border-bottom:none}
.ul-table td{padding:11px 14px;vertical-align:middle;color:var(--g800)}

/* AVATAR */
.ul-avatar{width:34px;height:34px;border-radius:50%;background:var(--br-700);
    color:#fff;display:flex;align-items:center;justify-content:center;
    font-size:13px;font-weight:700;flex-shrink:0;text-transform:uppercase}
.ul-name-cell{display:flex;align-items:center;gap:9px}
.ul-name{font-weight:600;color:var(--g800);max-width:130px;
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ul-uname{font-size:11.5px;color:var(--muted);margin-top:1px}

/* ARTIKEL */
.ul-artikel{max-width:200px;overflow:hidden;text-overflow:ellipsis;
    white-space:nowrap;color:var(--br-700);font-weight:500;font-size:12.5px}

/* KOMENTAR */
.ul-komen-wrap{max-width:260px}
.ul-komen{font-size:12.5px;color:var(--g600);line-height:1.5;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;
    overflow:hidden}
.ul-no-komen{font-size:12px;color:var(--g400);font-style:italic}
.ul-flag-note{font-size:11px;color:#EF4444;margin-top:4px;
    display:flex;align-items:center;gap:3px}

/* BADGE */
.ul-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;
    border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap}
.b-flagged{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}
.b-ok     {background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0}
.ul-date{color:var(--muted);font-size:12px;white-space:nowrap}
.ul-rownum{color:var(--g400);font-size:12px}

/* RATING */
.ul-rating{display:flex;align-items:center;gap:4px;flex-wrap:nowrap}
.ul-rating-num{font-weight:700;font-size:13px;color:var(--g800);min-width:14px}

/* ===== AKSI — dua tombol saja ===== */
.ul-aksi {
    display: flex;
    gap: 5px;
    flex-wrap: nowrap;
    justify-content: flex-end;
    align-items: center;
}
.ul-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 7px;
    font-size: 15px;
    text-decoration: none;
    transition: opacity .15s, transform .1s, box-shadow .15s;
    border: none;
    cursor: pointer;
    flex-shrink: 0;
}
.ul-btn:hover {
    opacity: .85;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0,0,0,.12);
}
.ul-btn-warn   { background: var(--ylw-bg);  color: var(--ylw-tx); }
.ul-btn-detail { background: var(--br-100);  color: var(--br-700); }

/* NO DATA */
.ul-nodata{text-align:center;padding:50px 20px;color:var(--g400)}
.ul-nodata i{font-size:48px;display:block;margin-bottom:12px}
.ul-nodata p{font-size:14px;margin-bottom:4px}
.ul-nodata small{font-size:12px}

/* PAGINATION */
.ul-pagination{display:flex;align-items:center;gap:6px;padding:14px 20px;
    border-top:1px solid var(--g100);flex-wrap:wrap}
.pg2-btn{display:flex;align-items:center;justify-content:center;min-width:34px;
    height:34px;padding:0 8px;border-radius:7px;font-size:13px;font-weight:600;
    text-decoration:none;color:var(--g600);background:var(--g100);
    transition:background .15s,color .15s;border:none;cursor:pointer}
.pg2-btn:hover{background:var(--br-100);color:var(--br-700)}
.pg2-btn.aktif{background:var(--br-700);color:#fff}
.pg2-btn.off{opacity:.35;cursor:not-allowed;pointer-events:none}
.pg2-dots{color:var(--g400);font-size:13px;padding:0 2px}
.pg2-info{margin-left:auto;font-size:12px;color:var(--muted)}

/* MODAL */
.ul-modal-ov{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;
    display:flex;align-items:center;justify-content:center;padding:20px;
    opacity:0;pointer-events:none;transition:opacity .2s}
.ul-modal-ov.show{opacity:1;pointer-events:all}
.ul-modal-box{background:#fff;border-radius:14px;padding:28px 28px 24px;
    width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.2);
    transform:translateY(20px);transition:transform .2s}
.ul-modal-ov.show .ul-modal-box{transform:translateY(0)}
.ul-modal-title{font-size:16px;font-weight:700;color:var(--g800);margin-bottom:4px}
.ul-modal-sub{font-size:12.5px;color:var(--muted);margin-bottom:6px}

/* PREVIEW BOX di modal */
.ul-preview-box{background:var(--g50);border:1px solid var(--g200);border-radius:8px;
    padding:12px 14px;margin-bottom:14px;font-size:12.5px;color:var(--g600);
    line-height:1.6;max-height:100px;overflow-y:auto}
.ul-preview-meta{display:flex;gap:8px;align-items:center;margin-bottom:6px;flex-wrap:wrap}
.ul-preview-meta span{font-size:11.5px;color:var(--muted)}
.ul-preview-meta strong{color:var(--br-700)}

.ul-modal-ta{width:100%;min-height:90px;padding:10px 12px;
    border:1.5px solid var(--g200);border-radius:8px;font-size:13px;
    resize:vertical;outline:none;transition:border-color .2s;font-family:inherit}
.ul-modal-ta:focus{border-color:var(--br-600)}
.ul-modal-hint{font-size:11px;color:var(--muted);margin-top:4px}
.ul-modal-foot{display:flex;gap:8px;justify-content:flex-end;margin-top:14px}
.ul-modal-ok{padding:8px 18px;background:var(--br-700);color:#fff;border:none;
    border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s}
.ul-modal-ok:hover{background:var(--br-600)}
.ul-modal-cancel{padding:8px 16px;background:var(--g100);color:var(--g600);
    border:none;border-radius:8px;font-size:13px;font-weight:600;
    cursor:pointer;transition:background .2s}
.ul-modal-cancel:hover{background:var(--g200)}

/* MODAL DETAIL */
.ul-detail-box{background:#fff;border-radius:14px;padding:28px;
    width:100%;max-width:540px;box-shadow:0 20px 60px rgba(0,0,0,.2);
    transform:translateY(20px);transition:transform .2s;max-height:90vh;overflow-y:auto}
.ul-modal-ov.show .ul-detail-box{transform:translateY(0)}
.ul-detail-section{margin-bottom:16px}
.ul-detail-section-title{font-size:11px;font-weight:700;text-transform:uppercase;
    letter-spacing:.06em;color:var(--muted);margin-bottom:8px}
.ul-detail-komentar{background:var(--g50);border:1px solid var(--g200);
    border-radius:8px;padding:14px;font-size:13.5px;color:var(--g800);
    line-height:1.7;white-space:pre-wrap;word-break:break-word}
.ul-detail-row{display:flex;gap:8px;align-items:flex-start;margin-bottom:8px;
    font-size:13px;color:var(--g600)}
.ul-detail-row strong{color:var(--g800);min-width:110px;flex-shrink:0}
.ul-warn-note{background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;
    padding:12px 14px;font-size:12.5px;color:var(--ylw-tx);margin-top:4px;
    display:flex;gap:8px;align-items:flex-start}
.ul-warn-note i{font-size:16px;flex-shrink:0;margin-top:1px}

/* RESPONSIVE */
@media(max-width:900px){.ul-stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){
    .ul-stats{grid-template-columns:1fr 1fr}
    .ul-header{flex-direction:column}
    .ul-toolbar{flex-direction:column}
    .ul-search-wrap{min-width:100%}
    .pg2-info{margin-left:0}
}
</style>

<div class="ul">

<!-- NOTIFIKASI -->
<?php if ($pesan): ?>
<div class="ul-notif <?= $tipe ?>" id="ul-notif">
    <div class="ul-notif-ico">
        <i class='bx <?= $tipe==="error"?"bx-x":"bx-error" ?>'
           style="font-size:17px"></i>
    </div>
    <span><?= $pesan ?></span>
    <button class="ul-notif-cls" onclick="this.closest('.ul-notif').remove()">
        <i class='bx bx-x'></i>
    </button>
</div>
<?php endif ?>

<!-- PAGE HEADER -->
<div class="ul-header">
    <div>
        <h1>Kelola Ulasan</h1>
        <div class="ul-breadcrumb">
            <i class='bx bx-home-alt' style="font-size:13px"></i>
            <span>Dashboard</span>
            <i class='bx bx-chevron-right' style="font-size:14px"></i>
            <span style="color:var(--br-600);font-weight:600">Kelola Ulasan</span>
        </div>
    </div>
</div>

<!-- STAT CARDS -->
<div class="ul-stats">
    <div class="ul-stat s-total">
        <div class="ul-stat-ico"><i class='bx bx-message-square-detail'></i></div>
        <div>
            <div class="ul-stat-val"><?= number_format($stat['total'] ?? 0) ?></div>
            <div class="ul-stat-lbl">Total Ulasan</div>
        </div>
    </div>
    <div class="ul-stat s-flagged">
        <div class="ul-stat-ico"><i class='bx bx-flag'></i></div>
        <div>
            <div class="ul-stat-val"><?= number_format($stat['flagged'] ?? 0) ?></div>
            <div class="ul-stat-lbl">Diberi Peringatan</div>
        </div>
    </div>
    <div class="ul-stat s-rating">
        <div class="ul-stat-ico"><i class='bx bxs-star'></i></div>
        <div>
            <div class="ul-stat-val"><?= $stat['avg_rating'] ?? '—' ?></div>
            <div class="ul-stat-lbl">Rata-rata Rating</div>
        </div>
    </div>
    <div class="ul-stat s-komen">
        <div class="ul-stat-ico"><i class='bx bx-comment'></i></div>
        <div>
            <div class="ul-stat-val"><?= number_format($stat['dengan_komentar'] ?? 0) ?></div>
            <div class="ul-stat-lbl">Dengan Komentar</div>
        </div>
    </div>
</div>

<!-- TABEL CARD -->
<div class="ul-card">

    <!-- Header card -->
    <div class="ul-card-head">
        <div class="ul-card-title">
            <div class="ul-title-ico"><i class='bx bx-message-square-detail'></i></div>
            Daftar Ulasan User
            <span class="ul-badge-count"><?= number_format($total_data) ?> ulasan</span>
        </div>

        <form method="get" action="dashboardAdmin.php" id="ul-filter-form">
            <input type="hidden" name="page" value="ulasan">
            <div class="ul-toolbar">

                <div class="ul-search-wrap">
                    <i class='bx bx-search'></i>
                    <input type="text" name="cari" id="ul-search"
                           class="ul-search"
                           placeholder="Cari nama, komentar, judul artikel..."
                           value="<?= htmlspecialchars($cari) ?>"
                           autocomplete="off">
                </div>

                <!-- Filter Rating -->
                <select name="rating" class="ul-select"
                        onchange="document.getElementById('ul-filter-form').submit()">
                    <option value="0">Semua Rating</option>
                    <?php for ($r = 5; $r >= 1; $r--): ?>
                    <option value="<?= $r ?>" <?= $filter_rating===$r?'selected':'' ?>>
                        <?= $r ?> Bintang
                    </option>
                    <?php endfor ?>
                </select>

                <!-- Filter Status -->
                <select name="flagged" class="ul-select"
                        onchange="document.getElementById('ul-filter-form').submit()">
                    <option value="">Semua Status</option>
                    <option value="1" <?= $filter_flagged==='1'?'selected':'' ?>>⚠️ Diperingatkan</option>
                    <option value="0" <?= $filter_flagged==='0'?'selected':'' ?>>✅ Normal</option>
                </select>

                <!-- Sort -->
                <select name="sort" class="ul-select"
                        onchange="document.getElementById('ul-filter-form').submit()">
                    <option value="u.created_at"     <?= $sort_by==='u.created_at'    ?'selected':'' ?>>Terbaru</option>
                    <option value="u.rating"         <?= $sort_by==='u.rating'        ?'selected':'' ?>>Rating</option>
                    <option value="usr.nama_lengkap" <?= $sort_by==='usr.nama_lengkap'?'selected':'' ?>>Nama User</option>
                    <option value="a.judul"          <?= $sort_by==='a.judul'         ?'selected':'' ?>>Judul Artikel</option>
                </select>

                <button type="button" class="ul-btn-refresh" onclick="ulRefresh()">
                    <i class='bx bx-refresh' id="ul-ref-ico"></i>
                    Refresh
                </button>

                <button type="submit" style="display:none"></button>
            </div>
        </form>
    </div>

    <!-- Info bar -->
    <div class="ul-infobar">
        <i class='bx bx-info-circle' style="font-size:14px"></i>
        Menampilkan <strong><?= count($ulasan_list) ?></strong>
        dari <strong><?= number_format($total_data) ?></strong> ulasan
        <?php if ($cari):          ?>&nbsp;·&nbsp; Pencarian: <strong>"<?= htmlspecialchars($cari) ?>"</strong><?php endif ?>
        <?php if ($filter_rating): ?>&nbsp;·&nbsp; Rating: <strong><?= $filter_rating ?> bintang</strong><?php endif ?>
        <?php if ($filter_flagged !== ''): ?>
            &nbsp;·&nbsp; Status: <strong><?= $filter_flagged==='1'?'Diperingatkan':'Normal' ?></strong>
        <?php endif ?>
    </div>

    <!-- TABEL -->
    <div class="ul-table-wrap">
        <table class="ul-table">
            <thead>
                <tr>
                    <th style="width:40px">No</th>
                    <th>
                        <a href="<?= ulasanUrl(['sort'=>'usr.nama_lengkap','dir'=>$sort_by==='usr.nama_lengkap'?$sort_next:'ASC','halaman'=>1]) ?>">
                            User <i class='bx <?= $sort_by==="usr.nama_lengkap"?($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt"):"bx-sort-alt-2" ?>'></i>
                        </a>
                    </th>
                    <th>
                        <a href="<?= ulasanUrl(['sort'=>'a.judul','dir'=>$sort_by==='a.judul'?$sort_next:'ASC','halaman'=>1]) ?>">
                            Artikel <i class='bx <?= $sort_by==="a.judul"?($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt"):"bx-sort-alt-2" ?>'></i>
                        </a>
                    </th>
                    <th>
                        <a href="<?= ulasanUrl(['sort'=>'u.rating','dir'=>$sort_by==='u.rating'?$sort_next:'DESC','halaman'=>1]) ?>">
                            Rating <i class='bx <?= $sort_by==="u.rating"?($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt"):"bx-sort-alt-2" ?>'></i>
                        </a>
                    </th>
                    <th>Komentar</th>
                    <th>Status</th>
                    <th>
                        <a href="<?= ulasanUrl(['sort'=>'u.created_at','dir'=>$sort_by==='u.created_at'?$sort_next:'DESC','halaman'=>1]) ?>">
                            Tanggal <i class='bx <?= $sort_by==="u.created_at"?($sort_dir==="ASC"?"bx-up-arrow-alt":"bx-down-arrow-alt"):"bx-sort-alt-2" ?>'></i>
                        </a>
                    </th>
                    <th style="text-align:right;padding-right:16px;width:80px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ulasan_list)): ?>
                <tr>
                    <td colspan="8">
                        <div class="ul-nodata">
                            <i class='bx bx-message-x'></i>
                            <p>Tidak ada ulasan ditemukan</p>
                            <small><?= $cari ? 'Coba ubah kata kunci pencarian.' : 'Belum ada ulasan yang masuk.' ?></small>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($ulasan_list as $i => $u):
                    $initial = mb_strtoupper(mb_substr($u['nama_lengkap'] ?? 'U', 0, 1));
                    $no      = ($halaman - 1) * $per_halaman + $i + 1;
                    $is_flag = (bool)$u['flagged'];
                ?>
                <tr class="<?= $is_flag ? 'flagged-row' : '' ?>">
                    <td><span class="ul-rownum"><?= $no ?></span></td>

                    <!-- User -->
                    <td>
                        <div class="ul-name-cell">
                            <div class="ul-avatar"><?= htmlspecialchars($initial) ?></div>
                            <div>
                                <div class="ul-name" title="<?= htmlspecialchars($u['nama_lengkap']) ?>">
                                    <?= htmlspecialchars($u['nama_lengkap']) ?>
                                </div>
                                <div class="ul-uname">@<?= htmlspecialchars($u['username']) ?></div>
                            </div>
                        </div>
                    </td>

                    <!-- Artikel -->
                    <td>
                        <span class="ul-artikel" title="<?= htmlspecialchars($u['judul_artikel']) ?>">
                            <?= htmlspecialchars($u['judul_artikel']) ?>
                        </span>
                    </td>

                    <!-- Rating -->
                    <td>
                        <div class="ul-rating">
                            <span class="ul-rating-num"><?= $u['rating'] ?></span>
                            <?= renderBintang($u['rating']) ?>
                        </div>
                    </td>

                    <!-- Komentar -->
                    <td>
                        <div class="ul-komen-wrap">
                            <?php if ($u['komentar']): ?>
                                <div class="ul-komen"><?= htmlspecialchars($u['komentar']) ?></div>
                            <?php else: ?>
                                <span class="ul-no-komen">— tidak ada komentar —</span>
                            <?php endif ?>
                            <?php if ($is_flag && $u['admin_note']): ?>
                                <div class="ul-flag-note">
                                    <i class='bx bx-flag'></i>
                                    <?= htmlspecialchars(mb_substr($u['admin_note'], 0, 60)) ?><?= mb_strlen($u['admin_note']) > 60 ? '…' : '' ?>
                                </div>
                            <?php endif ?>
                        </div>
                    </td>

                    <!-- Status -->
                    <td>
                        <?php if ($is_flag): ?>
                            <span class="ul-badge b-flagged"><i class='bx bx-flag'></i> Diperingatkan</span>
                        <?php else: ?>
                            <span class="ul-badge b-ok"><i class='bx bx-check'></i> Normal</span>
                        <?php endif ?>
                    </td>

                    <!-- Tanggal -->
                    <td>
                        <span class="ul-date"><?= date('d M Y', strtotime($u['created_at'])) ?></span>
                    </td>

                    <!-- Aksi: hanya Detail & Peringatan -->
                    <td>
                        <div class="ul-aksi">
                            <!-- Tombol Detail -->
                            <button type="button"
                                    class="ul-btn ul-btn-detail"
                                    title="Lihat Detail"
                                    onclick="ulOpenDetail(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)">
                                <i class='bx bx-show'></i>
                            </button>

                            <!-- Tombol Peringatan -->
                            <button type="button"
                                    class="ul-btn ul-btn-warn"
                                    title="Kirim Peringatan"
                                    onclick="ulOpenWarn(
                                        <?= $u['id'] ?>,
                                        '<?= addslashes(htmlspecialchars($u['nama_lengkap'])) ?>',
                                        '<?= addslashes(htmlspecialchars(mb_substr($u['komentar'] ?? '', 0, 80))) ?>'
                                    )">
                                <i class='bx bx-bell'></i>
                            </button>
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
    <div class="ul-pagination">
        <?php if ($halaman > 1): ?>
            <a href="<?= ulasanUrl(['halaman'=>$halaman-1]) ?>" class="pg2-btn"><i class='bx bx-chevron-left'></i></a>
        <?php else: ?>
            <span class="pg2-btn off"><i class='bx bx-chevron-left'></i></span>
        <?php endif ?>

        <?php for ($p = 1; $p <= $total_page; $p++):
            if ($p === $halaman): ?>
                <span class="pg2-btn aktif"><?= $p ?></span>
            <?php elseif ($p === 1 || $p === $total_page || abs($p - $halaman) <= 2): ?>
                <a href="<?= ulasanUrl(['halaman'=>$p]) ?>" class="pg2-btn"><?= $p ?></a>
            <?php elseif (abs($p - $halaman) === 3): ?>
                <span class="pg2-dots">…</span>
            <?php endif ?>
        <?php endfor ?>

        <?php if ($halaman < $total_page): ?>
            <a href="<?= ulasanUrl(['halaman'=>$halaman+1]) ?>" class="pg2-btn"><i class='bx bx-chevron-right'></i></a>
        <?php else: ?>
            <span class="pg2-btn off"><i class='bx bx-chevron-right'></i></span>
        <?php endif ?>

        <span class="pg2-info">
            Halaman <strong><?= $halaman ?></strong> dari <strong><?= $total_page ?></strong>
        </span>
    </div>
    <?php endif ?>

</div><!-- /ul-card -->


<!-- ===== MODAL PERINGATAN ===== -->
<div class="ul-modal-ov" id="ul-modal-warn">
    <div class="ul-modal-box">
        <div class="ul-modal-title">
            <i class='bx bx-bell' style="color:var(--ylw-tx);margin-right:6px"></i>
            Kirim Peringatan
        </div>
        <div class="ul-modal-sub" id="ul-warn-sub">Peringatan untuk user</div>

        <div class="ul-preview-box" id="ul-warn-preview" style="display:none">
            <div class="ul-preview-meta">
                <i class='bx bx-message-square-detail' style="font-size:13px;color:var(--g400)"></i>
                <span>Komentar yang ditandai:</span>
            </div>
            <span id="ul-warn-komen-text" style="color:var(--g800)">—</span>
        </div>

        <form method="post" action="dashboardAdmin.php?page=ulasan">
            <input type="hidden" name="aksi"      value="peringatan_ulasan">
            <input type="hidden" name="ulasan_id" id="ul-warn-id" value="">
            <textarea name="warn_msg" id="ul-warn-msg" class="ul-modal-ta"
                      placeholder="Tulis pesan peringatan untuk user ini..."
                      maxlength="500"></textarea>
            <div class="ul-modal-hint">
                Peringatan dicatat di sistem, ditandai pada ulasan, dan dikirim sebagai notifikasi ke user (maks. 500 karakter).
                Jika dikosongkan, pesan default akan digunakan.
            </div>
            <div class="ul-modal-foot">
                <button type="button" class="ul-modal-cancel" onclick="ulCloseWarn()">Batal</button>
                <button type="submit" class="ul-modal-ok">
                    <i class='bx bx-send'></i> Kirim Peringatan
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ===== MODAL DETAIL ULASAN ===== -->
<div class="ul-modal-ov" id="ul-modal-detail">
    <div class="ul-detail-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <div>
                <div class="ul-modal-title">
                    <i class='bx bx-message-square-detail' style="color:var(--br-600);margin-right:6px"></i>
                    Detail Ulasan
                </div>
                <div class="ul-modal-sub" style="margin-bottom:0" id="dt-tanggal">—</div>
            </div>
            <button type="button" class="ul-modal-cancel"
                    onclick="ulCloseDetail()"
                    style="padding:6px 10px;font-size:20px;line-height:1;min-width:0">
                <i class='bx bx-x'></i>
            </button>
        </div>

        <div class="ul-detail-section">
            <div class="ul-detail-section-title">Informasi User</div>
            <div class="ul-detail-row"><strong>Nama</strong><span id="dt-nama">—</span></div>
            <div class="ul-detail-row"><strong>Username</strong><span id="dt-username">—</span></div>
        </div>

        <div class="ul-detail-section">
            <div class="ul-detail-section-title">Artikel yang Diulas</div>
            <div class="ul-detail-row"><strong>Judul</strong><span id="dt-artikel">—</span></div>
        </div>

        <div class="ul-detail-section">
            <div class="ul-detail-section-title">Ulasan</div>
            <div class="ul-detail-row">
                <strong>Rating</strong>
                <span id="dt-rating-stars">—</span>
            </div>
            <div class="ul-detail-row" style="flex-direction:column;gap:6px">
                <strong>Komentar</strong>
                <div class="ul-detail-komentar" id="dt-komentar">—</div>
            </div>
        </div>

        <div class="ul-detail-section" id="dt-warn-section" style="display:none">
            <div class="ul-detail-section-title">Catatan Peringatan Admin</div>
            <div class="ul-warn-note">
                <i class='bx bx-error'></i>
                <span id="dt-warn-note">—</span>
            </div>
        </div>

        <div class="ul-modal-foot" style="margin-top:0">
            <button type="button" class="ul-modal-cancel" onclick="ulCloseDetail()">Tutup</button>
        </div>
    </div>
</div>


</div><!-- /ul -->

<script>
/* ===== REFRESH ===== */
function ulRefresh() {
    const ico = document.getElementById('ul-ref-ico');
    ico.classList.add('spinning');
    setTimeout(() => location.reload(), 300);
}

/* ===== MODAL PERINGATAN ===== */
function ulOpenWarn(id, nama, komen) {
    document.getElementById('ul-warn-id').value  = id;
    document.getElementById('ul-warn-sub').textContent = 'Peringatan untuk: ' + nama;
    document.getElementById('ul-warn-msg').value = '';

    const prev = document.getElementById('ul-warn-preview');
    if (komen && komen.trim() !== '') {
        document.getElementById('ul-warn-komen-text').textContent = komen + (komen.length >= 80 ? '…' : '');
        prev.style.display = 'block';
    } else {
        prev.style.display = 'none';
    }
    document.getElementById('ul-modal-warn').classList.add('show');
}
function ulCloseWarn() {
    document.getElementById('ul-modal-warn').classList.remove('show');
}
document.getElementById('ul-modal-warn').addEventListener('click', function(e) {
    if (e.target === this) ulCloseWarn();
});

/* ===== MODAL DETAIL ===== */
function ulOpenDetail(data) {
    document.getElementById('dt-nama').textContent     = data.nama_lengkap || '—';
    document.getElementById('dt-username').textContent = '@' + (data.username || '—');
    document.getElementById('dt-artikel').textContent  = data.judul_artikel || '—';
    document.getElementById('dt-tanggal').textContent  = 'Ditulis: ' + formatTanggal(data.created_at);

    // Rating bintang
    const ratingEl = document.getElementById('dt-rating-stars');
    ratingEl.innerHTML = '';
    const numSpan = document.createElement('span');
    numSpan.style.fontWeight = '700';
    numSpan.style.marginRight = '6px';
    numSpan.textContent = data.rating;
    ratingEl.appendChild(numSpan);
    for (let i = 1; i <= 5; i++) {
        const ico = document.createElement('i');
        ico.className = 'bx ' + (i <= data.rating ? 'bxs-star' : 'bx-star');
        ico.style.color = i <= data.rating ? '#F59E0B' : '#D1D5DB';
        ico.style.fontSize = '15px';
        ratingEl.appendChild(ico);
    }

    // Komentar
    const komentarEl = document.getElementById('dt-komentar');
    if (data.komentar && data.komentar.trim() !== '') {
        komentarEl.textContent = data.komentar;
        komentarEl.style.fontStyle = 'normal';
        komentarEl.style.color = '';
    } else {
        komentarEl.textContent = '— tidak ada komentar —';
        komentarEl.style.fontStyle = 'italic';
        komentarEl.style.color = 'var(--g400)';
    }

    // Catatan peringatan
    const warnSection = document.getElementById('dt-warn-section');
    if (data.flagged == 1 && data.admin_note) {
        document.getElementById('dt-warn-note').textContent = data.admin_note;
        warnSection.style.display = 'block';
    } else {
        warnSection.style.display = 'none';
    }

    document.getElementById('ul-modal-detail').classList.add('show');
}
function ulCloseDetail() {
    document.getElementById('ul-modal-detail').classList.remove('show');
}
document.getElementById('ul-modal-detail').addEventListener('click', function(e) {
    if (e.target === this) ulCloseDetail();
});

function formatTanggal(ts) {
    if (!ts) return '—';
    const d = new Date(ts.replace(' ', 'T'));
    const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    return d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
}

/* ===== AUTO-DISMISS NOTIF ===== */
(function() {
    const n = document.getElementById('ul-notif');
    if (!n) return;
    setTimeout(() => {
        n.style.transition = 'opacity .4s';
        n.style.opacity = '0';
        setTimeout(() => n.remove(), 400);
    }, 5000);
})();

/* ===== LIVE SEARCH ===== */
(function() {
    let t;
    const s = document.getElementById('ul-search');
    if (!s) return;
    s.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => document.getElementById('ul-filter-form').submit(), 400);
    });
    s.addEventListener('keydown', e => {
        if (e.key === 'Enter')  { e.preventDefault(); clearTimeout(t); document.getElementById('ul-filter-form').submit(); }
        if (e.key === 'Escape') { s.value = ''; clearTimeout(t); document.getElementById('ul-filter-form').submit(); }
    });
})();
</script>