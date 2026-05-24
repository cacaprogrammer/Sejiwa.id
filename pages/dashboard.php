<?php
// pages/dashboard.php — Halaman Statistik Utama

// Ambil statistik dari database
$total_user    = $conn->query("SELECT COUNT(*) as t FROM tb_user WHERE role='user'")->fetch_assoc()['t'];
$total_admin   = $conn->query("SELECT COUNT(*) as t FROM tb_user WHERE role='admin'")->fetch_assoc()['t'];
$total_artikel = $conn->query("SELECT COUNT(*) as t FROM tb_artikel")->fetch_assoc()['t'];
$total_sejarah = $conn->query("SELECT COUNT(*) as t FROM tb_artikel WHERE kategori='sejarah'")->fetch_assoc()['t'];
$total_biografi= $conn->query("SELECT COUNT(*) as t FROM tb_artikel WHERE kategori='biografi'")->fetch_assoc()['t'];
$total_ulasan  = $conn->query("SELECT COUNT(*) as t FROM tb_ulasan")->fetch_assoc()['t'];

// Artikel terpopuler (by view_count)
$populer = $conn->query("SELECT judul, view_count, thumbnail FROM tb_artikel ORDER BY view_count DESC LIMIT 5");

// Artikel terbaru
$terbaru = $conn->query("SELECT judul, kategori, created_at FROM tb_artikel ORDER BY created_at DESC LIMIT 5");
?>

<!-- ===== KARTU STATISTIK ===== -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-xl shadow text-center border-t-4 border-sejiwa-dark">
        <p class="text-3xl font-bold text-sejiwa-dark"><?= $total_user ?></p>
        <p class="text-sm text-gray-500 mt-1">Total User</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow text-center border-t-4 border-sejiwa-medium">
        <p class="text-3xl font-bold text-sejiwa-medium"><?= $total_artikel ?></p>
        <p class="text-sm text-gray-500 mt-1">Total Artikel</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow text-center border-t-4 border-sejiwa-light">
        <p class="text-3xl font-bold text-sejiwa-light"><?= $total_sejarah ?></p>
        <p class="text-sm text-gray-500 mt-1">Artikel Sejarah</p>
    </div>
    <div class="bg-white p-5 rounded-xl shadow text-center border-t-4 border-yellow-500">
        <p class="text-3xl font-bold text-yellow-600"><?= $total_biografi ?></p>
        <p class="text-sm text-gray-500 mt-1">Biografi Tokoh</p>
    </div>
</div>

<!-- ===== GRID KONTEN ===== -->
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

    <!-- Kiri: Statistik Visual (dari tampilan asli kamu) -->
    <div class="lg:col-span-3 space-y-6">

        <!-- Card Statistik Pengunjung -->
        <div class="rounded-xl p-6 text-white overflow-hidden relative min-h-48"
             style="background: linear-gradient(160deg, #A3826F 0%, #6B3E23 40%, #4A2C18 100%);">
            <h2 class="text-xl font-semibold mb-4">Statistik Pengunjung</h2>
            <div class="flex items-end gap-6">
                <span class="text-7xl font-bold leading-none"><?= $total_user + $total_admin ?></span>
                <div class="flex items-end gap-3 h-24 pb-2">
                    <div style="height:80%;width:12px;background:#FFD400;border-radius:4px"></div>
                    <div style="height:60%;width:12px;background:#FFD400;border-radius:4px"></div>
                    <div style="height:50%;width:12px;background:#FFD400;border-radius:4px"></div>
                    <div style="height:70%;width:12px;background:#FFD400;border-radius:4px"></div>
                    <div style="height:90%;width:12px;background:#63B754;border-radius:4px"></div>
                    <div style="height:40%;width:12px;background:#FFD400;border-radius:4px"></div>
                    <div style="height:30%;width:12px;background:#FFD400;border-radius:4px"></div>
                </div>
            </div>
            <img src="d1.png" alt="ilustrasi" class="absolute right-0 bottom-0 h-full opacity-80 object-contain pointer-events-none"
                 style="max-width:45%;">
        </div>

        <!-- Card Popularitas Artikel -->
        <div class="bg-white rounded-xl p-6 shadow" style="background-color:#DAC6BB;">
            <h2 class="text-lg font-semibold mb-4">Popularitas Artikel</h2>
            <?php if ($populer->num_rows > 0):
                $rank = 1;
                while ($p = $populer->fetch_assoc()): ?>
            <div class="flex items-center gap-3 py-2 border-b border-black/10 last:border-0">
                <span class="text-lg font-bold text-sejiwa-dark w-6"><?= $rank++ ?></span>
                <div class="flex-1">
                    <p class="font-semibold text-sm"><?= htmlspecialchars($p['judul']) ?></p>
                    <p class="text-xs text-gray-600"><?= $p['view_count'] ?> Pembaca</p>
                </div>
            </div>
            <?php endwhile;
            else: ?>
            <p class="text-sm text-gray-500">Belum ada data artikel.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kanan -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Card Kategori -->
        <div class="rounded-xl p-6 text-white" style="background-color:#A3826F;">
            <h2 class="text-lg font-semibold mb-4">Kategori Artikel</h2>
            <?php
            $pct_sejarah  = $total_artikel > 0 ? round(($total_sejarah / $total_artikel) * 100) : 0;
            $pct_biografi = 100 - $pct_sejarah;
            ?>
            <div class="flex items-center gap-4 justify-center">
                <div class="w-32 h-32 rounded-full flex-shrink-0"
                     style="background: conic-gradient(#4A2C18 0% <?= $pct_sejarah ?>%, #fff <?= $pct_sejarah ?>% 100%);
                            box-shadow: 0 0 0 8px rgba(0,0,0,0.05);">
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-sejiwa-dark inline-block"></span>
                        <span class="text-sm">Sejarah (<?= $pct_sejarah ?>%)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-white inline-block border"></span>
                        <span class="text-sm">Biografi (<?= $pct_biografi ?>%)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Artikel Terbaru -->
        <div class="bg-white rounded-xl p-6 shadow" style="background-color:#D9D9D9;">
            <h2 class="text-lg font-semibold mb-4">Artikel Terbaru</h2>
            <?php if ($terbaru->num_rows > 0):
                while ($t = $terbaru->fetch_assoc()): ?>
            <div class="flex items-start gap-3 py-2 border-b border-black/10 last:border-0">
                <span class="text-xs px-2 py-1 rounded mt-0.5 flex-shrink-0
                    <?= $t['kategori'] === 'sejarah' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' ?>">
                    <?= ucfirst($t['kategori']) ?>
                </span>
                <div>
                    <p class="font-medium text-sm text-gray-800"><?= htmlspecialchars($t['judul']) ?></p>
                    <p class="text-xs text-gray-500"><?= date('d M Y', strtotime($t['created_at'])) ?></p>
                </div>
            </div>
            <?php endwhile;
            else: ?>
            <p class="text-sm text-gray-500">Belum ada artikel.</p>
            <?php endif; ?>
        </div>
    </div>
</div>