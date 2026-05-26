<?php
// ============================================================
// pages/kategori.php — Manajemen Kategori (Admin Dashboard)
// ============================================================

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../loginpage.php");
    exit();
}

// ─────────────────────────────────────────────────────────────
// HELPER: buat slug otomatis dari nama kategori
// ─────────────────────────────────────────────────────────────
function buatSlugKategori($nama) {
    $slug = strtolower(trim($nama));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return $slug;
}

$pesan = '';
$tipe  = '';

// ─────────────────────────────────────────────────────────────
// PROSES TAMBAH KATEGORI
// ─────────────────────────────────────────────────────────────
if (isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    $nama = mb_substr(strip_tags(trim($_POST['nama_kategori'])), 0, 100);
    $slug = buatSlugKategori($nama);

    if ($nama === '') {
        $pesan = 'Nama kategori tidak boleh kosong.';
        $tipe  = 'error';
    } else {
        // Cek duplikat
        $cek = $conn->prepare("SELECT id_kategori FROM tb_kategori WHERE nama_kategori = ? OR slug_kategori = ?");
        $cek->bind_param("ss", $nama, $slug);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $pesan = 'Kategori dengan nama tersebut sudah ada.';
            $tipe  = 'error';
        } else {
            $ins = $conn->prepare("INSERT INTO tb_kategori (nama_kategori, slug_kategori) VALUES (?, ?)");
            $ins->bind_param("ss", $nama, $slug);
            if ($ins->execute()) {
                $pesan = '✅ Kategori berhasil ditambahkan.';
                $tipe  = 'sukses';
            } else {
                $pesan = 'Gagal menambahkan kategori.';
                $tipe  = 'error';
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────
// PROSES EDIT KATEGORI
// ─────────────────────────────────────────────────────────────
if (isset($_POST['aksi']) && $_POST['aksi'] === 'edit') {
    $id   = (int)$_POST['id_kategori'];
    $nama = mb_substr(strip_tags(trim($_POST['nama_kategori'])), 0, 100);
    $slug = buatSlugKategori($nama);

    if ($nama === '') {
        $pesan = 'Nama kategori tidak boleh kosong.';
        $tipe  = 'error';
    } else {
        // Cek duplikat (exclude id sendiri)
        $cek = $conn->prepare("SELECT id_kategori FROM tb_kategori WHERE (nama_kategori = ? OR slug_kategori = ?) AND id_kategori != ?");
        $cek->bind_param("ssi", $nama, $slug, $id);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $pesan = 'Nama kategori sudah digunakan oleh kategori lain.';
            $tipe  = 'error';
        } else {
            // Update juga kolom kategori di tb_artikel supaya sinkron
            $upd = $conn->prepare("UPDATE tb_kategori SET nama_kategori = ?, slug_kategori = ? WHERE id_kategori = ?");
            $upd->bind_param("ssi", $nama, $slug, $id);
            if ($upd->execute()) {
                // Sinkron kolom kategori lama di tb_artikel
                $sync = $conn->prepare("UPDATE tb_artikel SET kategori = ? WHERE id_kategori = ?");
                $sync->bind_param("si", $slug, $id);
                $sync->execute();

                $pesan = '✅ Kategori berhasil diperbarui.';
                $tipe  = 'sukses';
            } else {
                $pesan = 'Gagal memperbarui kategori.';
                $tipe  = 'error';
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────
// PROSES HAPUS KATEGORI
// ─────────────────────────────────────────────────────────────
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    // Cek apakah kategori masih dipakai oleh artikel
    $cek_pakai = $conn->prepare("SELECT COUNT(*) as total FROM tb_artikel WHERE id_kategori = ?");
    $cek_pakai->bind_param("i", $id);
    $cek_pakai->execute();
    $jumlah = $cek_pakai->get_result()->fetch_assoc()['total'];

    if ($jumlah > 0) {
        $pesan = "⛔ Tidak bisa dihapus! Kategori ini masih digunakan oleh {$jumlah} artikel.";
        $tipe  = 'error';
    } else {
        $del = $conn->prepare("DELETE FROM tb_kategori WHERE id_kategori = ?");
        $del->bind_param("i", $id);
        if ($del->execute()) {
            $pesan = '✅ Kategori berhasil dihapus.';
            $tipe  = 'sukses';
        } else {
            $pesan = 'Gagal menghapus kategori.';
            $tipe  = 'error';
        }
    }
}

// ─────────────────────────────────────────────────────────────
// DATA UNTUK FORM EDIT
// ─────────────────────────────────────────────────────────────
$mode_edit  = false;
$data_edit  = null;
if (isset($_GET['edit'])) {
    $id_edit   = (int)$_GET['edit'];
    $q_edit    = $conn->prepare("SELECT * FROM tb_kategori WHERE id_kategori = ?");
    $q_edit->bind_param("i", $id_edit);
    $q_edit->execute();
    $data_edit = $q_edit->get_result()->fetch_assoc();
    if ($data_edit) $mode_edit = true;
}

// ─────────────────────────────────────────────────────────────
// AMBIL SEMUA KATEGORI + jumlah artikel per kategori
// ─────────────────────────────────────────────────────────────
$list_kategori = $conn->query("
    SELECT k.*, COUNT(a.id) AS jumlah_artikel
    FROM tb_kategori k
    LEFT JOIN tb_artikel a ON a.id_kategori = k.id_kategori
    GROUP BY k.id_kategori
    ORDER BY k.created_at ASC
")->fetch_all(MYSQLI_ASSOC);
?>

<!-- ══════════════════════════════════════════════════════════
     CSS
══════════════════════════════════════════════════════════ -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<style>
:root {
    --br-900:#3B1F0E; --br-700:#4A2C18; --br-600:#6B3E23;
    --br-400:#A3826F; --br-100:#F5EDE6; --br-50:#faf5ec;
    --green-bg:#ECFDF5; --green-tx:#065F46; --green-bd:#A7F3D0;
    --red-bg:#FEF2F2;   --red-tx:#991B1B; --red-bd:#FCA5A5;
    --ylw-bg:#FFFBEB;   --ylw-tx:#92400E; --ylw-bd:#FCD34D;
    --g50:#F9FAFB; --g100:#F3F4F6; --g200:#E5E7EB;
    --g400:#9CA3AF; --g600:#4B5563; --g800:#1F2937;
    --r:10px;
    --sh-sm:0 1px 3px rgba(0,0,0,.07);
    --sh-md:0 4px 16px rgba(74,44,24,.10);
    --font-head:'Montserrat',sans-serif;
    --font-body:'Roboto',sans-serif;
}
.kat * { box-sizing: border-box; }
.kat { font-family: var(--font-body); color: var(--g800); }

/* PAGE HEADER */
.kat-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.kat-header h1 { font-family:var(--font-head); font-size:26px; font-weight:800; color:#4D1E0A; margin:0 0 4px; letter-spacing:-0.4px; }
.kat-breadcrumb { display:flex; align-items:center; gap:6px; font-size:13px; color:#7B4F2C; }

/* NOTIF */
.kat-notif { display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:var(--r); font-size:13.5px; font-weight:500; margin-bottom:18px; box-shadow:var(--sh-sm); }
.kat-notif.sukses { background:var(--green-bg); color:var(--green-tx); border-left:4px solid #10B981; }
.kat-notif.error  { background:var(--red-bg);   color:var(--red-tx);   border-left:4px solid #EF4444; }
.kat-notif-cls { margin-left:auto; background:none; border:none; cursor:pointer; font-size:17px; color:inherit; opacity:.6; }
.kat-notif-cls:hover { opacity:1; }

/* LAYOUT DUA KOLOM */
.kat-layout { display:grid; grid-template-columns:340px 1fr; gap:24px; align-items:start; }
@media(max-width:900px) { .kat-layout { grid-template-columns:1fr; } }

/* CARD */
.kat-card { background:#fff; border-radius:var(--r); box-shadow:var(--sh-md); overflow:hidden; }
.kat-card-head { padding:16px 20px; border-bottom:1px solid var(--g100); display:flex; align-items:center; gap:10px; }
.kat-card-head .ico { width:32px; height:32px; background:var(--br-100); border-radius:8px; display:flex; align-items:center; justify-content:center; }
.kat-card-head .ico i { color:var(--br-700); font-size:17px; }
.kat-card-head h2 { font-family:var(--font-head); font-size:15px; font-weight:700; color:var(--br-700); margin:0; }
.kat-card-body { padding:20px; }

/* FORM */
.kat-label { display:block; font-size:12.5px; font-weight:600; color:var(--g600); margin-bottom:6px; }
.kat-label span { color:#EF4444; margin-left:2px; }
.kat-input { width:100%; padding:10px 13px; border:1.5px solid var(--g200); border-radius:8px; font-size:14px; font-family:var(--font-body); color:var(--g800); background:var(--g50); outline:none; transition:border-color .2s; }
.kat-input:focus { border-color:var(--br-600); background:#fff; box-shadow:0 0 0 3px rgba(107,62,35,.1); }
.kat-hint { font-size:11.5px; color:var(--g400); margin-top:5px; }
.kat-form-group { margin-bottom:16px; }
.kat-form-foot { display:flex; gap:8px; margin-top:4px; }
.kat-btn-simpan { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; background:var(--br-700); color:#fff; border:none; border-radius:8px; font-size:13.5px; font-weight:700; font-family:var(--font-head); cursor:pointer; transition:background .2s; }
.kat-btn-simpan:hover { background:var(--br-900); }
.kat-btn-batal { display:inline-flex; align-items:center; gap:6px; padding:10px 16px; background:var(--g100); color:var(--g600); border:none; border-radius:8px; font-size:13.5px; font-weight:600; cursor:pointer; text-decoration:none; transition:background .2s; }
.kat-btn-batal:hover { background:var(--g200); color:var(--g800); text-decoration:none; }

/* MODE EDIT HIGHLIGHT */
.edit-mode-banner { background:var(--ylw-bg); color:var(--ylw-tx); border:1px solid var(--ylw-bd); border-radius:8px; padding:10px 14px; font-size:12.5px; font-weight:600; margin-bottom:16px; display:flex; align-items:center; gap:8px; }

/* TABEL */
.kat-badge-count { background:var(--br-100); color:var(--br-600); font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:20px; }
.kat-table { width:100%; border-collapse:collapse; font-size:13.5px; }
.kat-table thead tr { background:var(--g50); border-bottom:2px solid var(--g200); }
.kat-table th { padding:11px 16px; text-align:left; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--g400); white-space:nowrap; }
.kat-table td { padding:12px 16px; border-bottom:1px solid var(--g100); vertical-align:middle; }
.kat-table tbody tr:last-child td { border-bottom:none; }
.kat-table tbody tr:hover td { background:var(--br-100); }

.kat-nama { font-weight:600; color:var(--g800); }
.kat-slug-text { font-size:12px; color:var(--g400); font-family:monospace; background:var(--g100); padding:2px 7px; border-radius:4px; }
.kat-count-badge { display:inline-flex; align-items:center; gap:4px; background:var(--blue-bg,#EFF6FF); color:var(--blue-tx,#1D4ED8); padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
.kat-count-zero { background:var(--g100); color:var(--g400); }

/* AKSI */
.kat-aksi { display:flex; gap:6px; justify-content:flex-end; }
.kat-btn-aksi { display:inline-flex; align-items:center; gap:4px; padding:5px 11px; border-radius:6px; font-size:12px; font-weight:600; border:none; cursor:pointer; text-decoration:none; transition:opacity .15s, transform .1s; }
.kat-btn-aksi:hover { opacity:.85; transform:translateY(-1px); text-decoration:none; }
.btn-edit-kat  { background:var(--ylw-bg); color:var(--ylw-tx); }
.btn-hapus-kat { background:var(--red-bg);  color:var(--red-tx); }

/* NO DATA */
.kat-nodata { text-align:center; padding:40px 20px; color:var(--g400); }
.kat-nodata i { font-size:42px; display:block; margin-bottom:10px; }
.kat-nodata p { font-size:14px; }
</style>

<div class="kat">

<!-- NOTIFIKASI -->
<?php if ($pesan): ?>
<div class="kat-notif <?= $tipe ?>" id="kat-notif">
    <i class='bx <?= $tipe === "sukses" ? "bx-check-circle" : "bx-error-circle" ?>' style="font-size:20px;"></i>
    <span><?= htmlspecialchars($pesan) ?></span>
    <button class="kat-notif-cls" onclick="this.closest('.kat-notif').remove()"><i class='bx bx-x'></i></button>
</div>
<?php endif; ?>

<!-- PAGE HEADER -->
<div class="kat-header">
    <div>
        <h1>Manajemen Kategori</h1>
        <div class="kat-breadcrumb">
            <i class='bx bx-home-alt' style="font-size:13px;"></i>
            <span>Dashboard</span>
            <i class='bx bx-chevron-right' style="font-size:14px;"></i>
            <span style="color:var(--br-600);font-weight:600;">Kategori</span>
        </div>
    </div>
</div>

<!-- LAYOUT -->
<div class="kat-layout">

    <!-- ═══ KIRI: FORM TAMBAH / EDIT ═══ -->
    <div class="kat-card">
        <div class="kat-card-head">
            <div class="ico"><i class='bx <?= $mode_edit ? "bx-edit" : "bx-folder-plus" ?>'></i></div>
            <h2><?= $mode_edit ? 'Edit Kategori' : 'Tambah Kategori Baru' ?></h2>
        </div>
        <div class="kat-card-body">

            <?php if ($mode_edit): ?>
            <div class="edit-mode-banner">
                <i class='bx bx-pencil'></i>
                Sedang mengedit: <strong><?= htmlspecialchars($data_edit['nama_kategori']) ?></strong>
            </div>
            <?php endif; ?>

            <form method="post" action="dashboardAdmin.php?page=kategori">
                <input type="hidden" name="aksi" value="<?= $mode_edit ? 'edit' : 'tambah' ?>">
                <?php if ($mode_edit): ?>
                <input type="hidden" name="id_kategori" value="<?= $data_edit['id_kategori'] ?>">
                <?php endif; ?>

                <div class="kat-form-group">
                    <label class="kat-label">Nama Kategori <span>*</span></label>
                    <input type="text" name="nama_kategori" class="kat-input"
                           placeholder="cth: Sejarah, Biografi Tokoh..."
                           value="<?= htmlspecialchars($data_edit['nama_kategori'] ?? '') ?>"
                           maxlength="100" required autofocus>
                    <div class="kat-hint">Slug akan dibuat otomatis dari nama kategori.</div>
                </div>

                <div class="kat-form-foot">
                    <button type="submit" class="kat-btn-simpan">
                        <i class='bx <?= $mode_edit ? "bx-save" : "bx-plus" ?>'></i>
                        <?= $mode_edit ? 'Simpan Perubahan' : 'Tambah Kategori' ?>
                    </button>
                    <?php if ($mode_edit): ?>
                    <a href="dashboardAdmin.php?page=kategori" class="kat-btn-batal">
                        <i class='bx bx-x'></i> Batal
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══ KANAN: TABEL DAFTAR KATEGORI ═══ -->
    <div class="kat-card">
        <div class="kat-card-head">
            <div class="ico"><i class='bx bx-category'></i></div>
            <h2>Daftar Kategori</h2>
            <span class="kat-badge-count" style="margin-left:4px;"><?= count($list_kategori) ?> kategori</span>
        </div>

        <table class="kat-table">
            <thead>
                <tr>
                    <th style="width:44px;">No</th>
                    <th>Nama Kategori</th>
                    <th>Slug</th>
                    <th style="text-align:center;">Artikel</th>
                    <th style="text-align:right; padding-right:20px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list_kategori)): ?>
                <tr>
                    <td colspan="5">
                        <div class="kat-nodata">
                            <i class='bx bx-category'></i>
                            <p>Belum ada kategori. Tambahkan di sini!</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($list_kategori as $i => $kat): ?>
                <tr>
                    <td style="color:var(--g400);font-size:12px;"><?= $i + 1 ?></td>
                    <td>
                        <span class="kat-nama"><?= htmlspecialchars($kat['nama_kategori']) ?></span>
                    </td>
                    <td>
                        <span class="kat-slug-text"><?= htmlspecialchars($kat['slug_kategori']) ?></span>
                    </td>
                    <td style="text-align:center;">
                        <span class="kat-count-badge <?= $kat['jumlah_artikel'] == 0 ? 'kat-count-zero' : '' ?>">
                            <i class='bx bx-file'></i>
                            <?= $kat['jumlah_artikel'] ?> artikel
                        </span>
                    </td>
                    <td style="padding-right:20px;">
                        <div class="kat-aksi">
                            <!-- Edit -->
                            <a href="dashboardAdmin.php?page=kategori&edit=<?= $kat['id_kategori'] ?>"
                               class="kat-btn-aksi btn-edit-kat">
                                <i class='bx bx-edit'></i> Edit
                            </a>
                            <!-- Hapus (tidak bisa hapus jika ada artikel) -->
                            <?php if ($kat['jumlah_artikel'] == 0): ?>
                            <a href="dashboardAdmin.php?page=kategori&hapus=<?= $kat['id_kategori'] ?>"
                               class="kat-btn-aksi btn-hapus-kat"
                               onclick="return confirm('Yakin hapus kategori \"<?= addslashes(htmlspecialchars($kat['nama_kategori'])) ?>\"?')">
                                <i class='bx bx-trash'></i> Hapus
                            </a>
                            <?php else: ?>
                            <span class="kat-btn-aksi" style="background:var(--g100);color:var(--g400);cursor:not-allowed;"
                                  title="Tidak bisa dihapus, masih ada <?= $kat['jumlah_artikel'] ?> artikel">
                                <i class='bx bx-lock'></i> Terkunci
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div><!-- /kat-layout -->

</div><!-- /kat -->

<script>
// Auto-hide notif setelah 5 detik
(function() {
    const n = document.getElementById('kat-notif');
    if (!n) return;
    setTimeout(() => {
        n.style.transition = 'opacity .4s';
        n.style.opacity = '0';
        setTimeout(() => n.remove(), 400);
    }, 5000);
})();
</script>