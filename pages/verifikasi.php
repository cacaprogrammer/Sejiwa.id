<?php
// pages/verifikasi.php

$r_menunggu = $conn->query("SELECT COUNT(*) AS total FROM tb_artikel WHERE status = 'pending'")->fetch_assoc()['total'];
$r_verified = $conn->query("SELECT COUNT(*) AS total FROM tb_artikel WHERE status = 'published'")->fetch_assoc()['total'];

$stmt = $conn->query("
    SELECT a.id, a.judul, a.kategori, a.created_at, a.penulis,
           u.nama_lengkap AS nama_pengirim
    FROM tb_artikel a
    LEFT JOIN tb_user u ON u.id = a.created_by
    WHERE a.status = 'pending'
    ORDER BY a.created_at DESC
");
$list_pending = $stmt ? $stmt->fetch_all(MYSQLI_ASSOC) : [];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
.vf-wrap  { max-width: 860px; }
.vf-title { font-size: 22px; font-weight: bold; color: #2d1a0e; margin-bottom: 4px; }
.vf-sub   { font-size: 13px; color: #888; margin-bottom: 1.5rem; }

.vf-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 1.75rem;
    max-width: 380px;
}
.vf-stat-card  { background: #f5f0eb; border-radius: 12px; padding: 14px 18px; }
.vf-stat-label { font-size: 12px; color: #888; margin-bottom: 5px; }
.vf-stat-val   { font-size: 26px; font-weight: bold; color: #2d1a0e; }
.vf-stat-val.amber { color: #8a5800; }
.vf-stat-val.green { color: #1a6b3a; }

.vf-list { display: flex; flex-direction: column; gap: 12px; }

.vf-card {
    background: white;
    border: 1px solid #e8ddd5;
    border-left: 4px solid #4a2c18;
    border-radius: 12px;
    padding: 16px 18px;
}
.vf-card-header {
    display: flex; align-items: center; gap: 8px;
    font-size: 13.5px; font-weight: bold; color: #4a2c18;
    margin-bottom: 12px;
}
.vf-rows {
    display: grid; grid-template-columns: 90px 1fr;
    gap: 5px 8px; margin-bottom: 12px;
}
.vf-key { font-size: 13px; color: #999; }
.vf-val { font-size: 13px; color: #2d1a0e; font-weight: 600; }
.vf-btn-row {
    display: flex; gap: 8px; flex-wrap: wrap;
    padding-top: 12px; border-top: 1px solid #f0e8e0;
}
.vf-btn {
    font-size: 12.5px; padding: 7px 15px; border-radius: 8px;
    border: 1px solid #d5c9c0; background: white; color: #333;
    cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
    transition: background 0.15s;
}
.vf-btn:hover         { background: #f7f3f0; }
.vf-btn-success       { background: #d6f5e3; color: #1a6b3a; border-color: #a8e6c5; }
.vf-btn-success:hover { background: #b8ecce; }
.vf-btn-danger        { background: #fde8e8; color: #8b1a1a; border-color: #f5b8b8; }
.vf-btn-danger:hover  { background: #fbd0d0; }

.vf-empty {
    text-align: center; padding: 3rem 1rem; color: #aaa;
    background: white; border: 1px dashed #d5c9c0; border-radius: 12px;
}
.vf-empty i { font-size: 3rem; color: #d5c9c0; margin-bottom: 1rem; display: block; }
.vf-empty p { font-size: 14px; }

.vf-alert {
    padding: 10px 16px; border-radius: 8px; font-size: 13px;
    margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;
}
.vf-alert.success { background: #d6f5e3; color: #1a6b3a; }
.vf-alert.danger  { background: #fde8e8; color: #8b1a1a; }

/* ===== MODAL SHARED BASE ===== */
.vf-modal-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.48);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.vf-modal-overlay.show { display: flex; }

.vf-modal-box {
    background: #fff;
    border-radius: 16px;
    padding: 28px 28px 24px;
    width: 100%; max-width: 440px;
    box-shadow: 0 24px 64px rgba(0,0,0,.22);
    animation: vfModalIn .2s ease;
}
@keyframes vfModalIn {
    from { opacity: 0; transform: translateY(18px) scale(.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.vf-modal-icon {
    width: 52px; height: 52px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 16px;
}
.vf-modal-icon.green { background: #d6f5e3; }
.vf-modal-icon.green i { color: #1a6b3a; font-size: 24px; }
.vf-modal-icon.red   { background: #fde8e8; }
.vf-modal-icon.red i { color: #c0392b; font-size: 24px; }

.vf-modal-title {
    font-size: 17px; font-weight: 700;
    color: #1a1a1a; margin-bottom: 6px;
}
.vf-modal-sub {
    font-size: 13px; color: #777; margin-bottom: 16px; line-height: 1.5;
}

.vf-modal-chip {
    background: #f5f0eb; border-radius: 8px;
    padding: 9px 13px; font-size: 13px;
    color: #4a2c18; font-weight: 600;
    margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
}
.vf-modal-chip i { color: #a3826f; flex-shrink: 0; }

/* textarea (hanya untuk modal tolak) */
.vf-modal-label {
    font-size: 12.5px; font-weight: 600;
    color: #555; margin-bottom: 6px; display: block;
}
.vf-modal-label span { color: #e74c3c; margin-left: 2px; }
.vf-modal-textarea {
    width: 100%; min-height: 96px;
    padding: 10px 12px; border: 1.5px solid #e0d5cc;
    border-radius: 10px; font-size: 13px; color: #333;
    font-family: inherit; resize: vertical; outline: none;
    background: #fafafa; box-sizing: border-box;
    transition: border-color .2s;
}
.vf-modal-textarea:focus { border-color: #4a2c18; background: #fff; }
.vf-modal-err {
    font-size: 12px; color: #c0392b;
    margin-top: 5px; display: none;
}

.vf-modal-footer {
    display: flex; gap: 8px; justify-content: flex-end;
    margin-top: 20px; padding-top: 16px;
    border-top: 1px solid #f0e8e0;
}
.vf-modal-btn-batal {
    padding: 9px 18px; background: #f3f4f6; color: #555;
    border: none; border-radius: 8px; font-size: 13px;
    font-weight: 600; cursor: pointer; transition: background .2s;
}
.vf-modal-btn-batal:hover { background: #e5e7eb; }
.vf-modal-btn-ok {
    padding: 9px 20px; color: #fff;
    border: none; border-radius: 8px; font-size: 13px;
    font-weight: 600; cursor: pointer;
    display: flex; align-items: center; gap: 7px;
    transition: background .2s;
}
.vf-modal-btn-ok.green { background: #1a6b3a; }
.vf-modal-btn-ok.green:hover { background: #155a30; }
.vf-modal-btn-ok.red   { background: #c0392b; }
.vf-modal-btn-ok.red:hover   { background: #a93226; }
</style>

<div class="vf-wrap">
    <div class="vf-title">Verifikasi Artikel</div>
    <div class="vf-sub">Tinjau dan verifikasi artikel yang dikirimkan oleh pengguna</div>

    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'approved'): ?>
            <div class="vf-alert success"><i class="fas fa-check-circle"></i> Artikel berhasil diverifikasi dan diterbitkan.</div>
        <?php elseif ($_GET['status'] === 'rejected'): ?>
            <div class="vf-alert danger"><i class="fas fa-times-circle"></i> Artikel berhasil ditolak dan user telah diberitahu.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="vf-stats">
        <div class="vf-stat-card">
            <div class="vf-stat-label">Menunggu Verifikasi</div>
            <div class="vf-stat-val amber"><?= $r_menunggu ?></div>
        </div>
        <div class="vf-stat-card">
            <div class="vf-stat-label">Sudah Diverifikasi</div>
            <div class="vf-stat-val green"><?= $r_verified ?></div>
        </div>
    </div>

    <div class="vf-list">
        <?php if (empty($list_pending)): ?>
            <div class="vf-empty">
                <i class="fas fa-check-circle"></i>
                <p>Tidak ada artikel yang menunggu verifikasi saat ini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($list_pending as $art):
                $tgl      = date('d M Y', strtotime($art['created_at']));
                $kat      = ucfirst($art['kategori'] ?? '-');
                $pengirim = $art['nama_pengirim'] ?? ($art['penulis'] ?? 'Tidak diketahui');
            ?>
            <div class="vf-card">
                <div class="vf-card-header">
                    <i class="fas fa-bell"></i>
                    Permintaan verifikasi artikel baru
                </div>
                <div class="vf-rows">
                    <span class="vf-key">Judul</span>
                    <span class="vf-val"><?= htmlspecialchars($art['judul']) ?></span>
                    <span class="vf-key">Pengirim</span>
                    <span class="vf-val"><?= htmlspecialchars($pengirim) ?></span>
                    <span class="vf-key">Kategori</span>
                    <span class="vf-val"><?= $kat ?></span>
                    <span class="vf-key">Tanggal</span>
                    <span class="vf-val"><?= $tgl ?></span>
                </div>
                <div class="vf-btn-row">
                    <a href="detail_verifikasi.php?id=<?= $art['id'] ?>" class="vf-btn">
                        <i class="fas fa-eye"></i> Lihat Detail
                    </a>
                    <button type="button" class="vf-btn vf-btn-success"
                            onclick="bukaModalVerifikasi(<?= $art['id'] ?>, '<?= addslashes(htmlspecialchars($art['judul'])) ?>')">
                        <i class="fas fa-check"></i> Verifikasi
                    </button>
                    <button type="button" class="vf-btn vf-btn-danger"
                            onclick="bukaModalTolak(<?= $art['id'] ?>, '<?= addslashes(htmlspecialchars($art['judul'])) ?>')">
                        <i class="fas fa-times"></i> Tolak
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ===== MODAL VERIFIKASI ===== -->
<div class="vf-modal-overlay" id="modal-verifikasi" onclick="tutupModal('modal-verifikasi', event)">
    <div class="vf-modal-box">
        <div class="vf-modal-icon green">
            <i class="fas fa-check"></i>
        </div>
        <div class="vf-modal-title">Verifikasi Artikel</div>
        <div class="vf-modal-sub">Artikel berikut akan diterbitkan dan dapat dibaca oleh publik.</div>
        <div class="vf-modal-chip">
            <i class="fas fa-file-alt"></i>
            <span id="verif-judul-text">—</span>
        </div>
        <div class="vf-modal-footer">
            <button type="button" class="vf-modal-btn-batal" onclick="tutupModal('modal-verifikasi')">Batal</button>
            <button type="button" class="vf-modal-btn-ok green" onclick="kirimVerifikasi()">
                <i class="fas fa-check"></i> Ya, Terbitkan
            </button>
        </div>
    </div>
</div>

<!-- ===== MODAL TOLAK ===== -->
<div class="vf-modal-overlay" id="modal-tolak" onclick="tutupModal('modal-tolak', event)">
    <div class="vf-modal-box">
        <div class="vf-modal-icon red">
            <i class="fas fa-times"></i>
        </div>
        <div class="vf-modal-title">Tolak Artikel</div>
        <div class="vf-modal-sub">Berikan alasan penolakan yang jelas untuk dikirimkan ke penulis.</div>
        <div class="vf-modal-chip">
            <i class="fas fa-file-alt"></i>
            <span id="tolak-judul-text">—</span>
        </div>
        <label class="vf-modal-label">Catatan Penolakan <span>*</span></label>
        <textarea class="vf-modal-textarea" id="tolak-catatan"
                  placeholder="cth: Konten tidak sesuai pedoman, mohon revisi bagian..."></textarea>
        <div class="vf-modal-err" id="tolak-err">
            <i class="fas fa-exclamation-circle"></i> Catatan tidak boleh kosong.
        </div>
        <div class="vf-modal-footer">
            <button type="button" class="vf-modal-btn-batal" onclick="tutupModal('modal-tolak')">Batal</button>
            <button type="button" class="vf-modal-btn-ok red" onclick="kirimTolak()">
                <i class="fas fa-paper-plane"></i> Kirim Penolakan
            </button>
        </div>
    </div>
</div>

<script>
var _verifId = null;
var _tolakId = null;

/* ---- Verifikasi ---- */
function bukaModalVerifikasi(id, judul) {
    _verifId = id;
    document.getElementById('verif-judul-text').textContent = judul;
    document.getElementById('modal-verifikasi').classList.add('show');
}
function kirimVerifikasi() {
    if (!_verifId) return;
    window.location.href = 'aksi_verifikasi.php?id=' + _verifId + '&aksi=approve';
}

/* ---- Tolak ---- */
function bukaModalTolak(id, judul) {
    _tolakId = id;
    document.getElementById('tolak-judul-text').textContent = judul;
    document.getElementById('tolak-catatan').value = '';
    document.getElementById('tolak-err').style.display = 'none';
    document.getElementById('modal-tolak').classList.add('show');
    setTimeout(() => document.getElementById('tolak-catatan').focus(), 200);
}
function kirimTolak() {
    var catatan = document.getElementById('tolak-catatan').value.trim();
    var errEl   = document.getElementById('tolak-err');
    if (catatan === '') {
        errEl.style.display = 'block';
        document.getElementById('tolak-catatan').focus();
        return;
    }
    errEl.style.display = 'none';
    window.location.href = 'aksi_verifikasi.php?id=' + _tolakId
        + '&aksi=reject&catatan=' + encodeURIComponent(catatan);
}

/* ---- Tutup modal ---- */
function tutupModal(modalId, e) {
    if (e && e.target !== document.getElementById(modalId)) return;
    document.getElementById(modalId).classList.remove('show');
}

document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.vf-modal-overlay.show').forEach(function(m) {
        m.classList.remove('show');
    });
});
</script>