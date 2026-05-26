<?php
// pages/dashboard.php — Halaman Statistik Utama (Redesign Modern Sejiwa.id)

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

<style>
  /* ===== GOOGLE FONTS ===== */
  @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Roboto:wght@300;400;500&display=swap');

  /* ===== CSS VARIABLES (Branding Sejiwa.id) ===== */
  :root {
    --dark-brown:   #4A2C18;
    --brown:        #7B4F2C;
    --orange-brown: #D98B3E;
    --soft-brown:   #AD8D77;
    --cream:        #F8F8DC;
    --cream-light:  #FDFDF5;
    --text-main:    #2D1B0E;
    --text-muted:   #8B7355;
    --shadow-sm:    0 1px 3px rgba(74,44,24,0.08), 0 1px 2px rgba(74,44,24,0.06);
    --shadow-md:    0 4px 12px rgba(74,44,24,0.10), 0 2px 6px rgba(74,44,24,0.08);
    --shadow-lg:    0 8px 24px rgba(74,44,24,0.12), 0 4px 10px rgba(74,44,24,0.08);
  }

  /* ===== BASE ===== */
  .dashboard-wrap {
    font-family: 'Roboto', sans-serif;
    background-color: #F5F0E8;
    min-height: 100vh;
    padding: 1.5rem;
    color: var(--text-main);
  }

  /* ===== PAGE HEADER ===== */
  .page-header {
    margin-bottom: 1.75rem;
  }
  .page-header h1 {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-brown);
    letter-spacing: -0.02em;
  }
  .page-header p {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
  }
  .page-header .header-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: var(--cream);
    border: 1px solid rgba(74,44,24,0.15);
    color: var(--brown);
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    margin-bottom: 0.5rem;
  }

  /* ===== STAT CARDS ===== */
  .stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(74,44,24,0.06);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
    overflow: hidden;
  }
  .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
  }
  .stat-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 16px 16px 0 0;
  }
  .stat-card.c1::after { background: var(--dark-brown); }
  .stat-card.c2::after { background: var(--orange-brown); }
  .stat-card.c3::after { background: var(--soft-brown); }
  .stat-card.c4::after { background: #C4A265; }

  .stat-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 1rem;
  }
  .stat-icon.i1 { background: rgba(74,44,24,0.10); color: var(--dark-brown); }
  .stat-icon.i2 { background: rgba(217,139,62,0.12); color: var(--orange-brown); }
  .stat-icon.i3 { background: rgba(173,141,119,0.15); color: var(--soft-brown); }
  .stat-icon.i4 { background: rgba(196,162,101,0.15); color: #A8853A; }

  .stat-value {
    font-family: 'Montserrat', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: var(--dark-brown);
    line-height: 1;
  }
  .stat-label {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 0.3rem;
    font-weight: 400;
  }
  .stat-trend {
    display: inline-flex; align-items: center; gap: 0.25rem;
    font-size: 0.7rem; font-weight: 600;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    margin-top: 0.5rem;
  }
  .trend-up   { background: #DCFCE7; color: #16A34A; }
  .trend-neu  { background: var(--cream); color: var(--soft-brown); }

  /* ===== VISITOR CARD ===== */
  .visitor-card {
    background: linear-gradient(145deg, var(--dark-brown) 0%, var(--brown) 60%, #8B5E35 100%);
    border-radius: 16px;
    padding: 1.75rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
    transition: transform 0.2s ease;
  }
  .visitor-card:hover { transform: translateY(-2px); }
  .visitor-card::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 160px; height: 160px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
  }
  .visitor-card::after {
    content: '';
    position: absolute;
    bottom: -60px; right: 30px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.03);
    border-radius: 50%;
  }
  .visitor-card h2 {
    font-family: 'Montserrat', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    opacity: 0.75;
    margin-bottom: 0.25rem;
  }
  .visitor-total {
    font-family: 'Montserrat', sans-serif;
    font-size: 3.5rem;
    font-weight: 800;
    line-height: 1;
    margin: 0.25rem 0;
  }
  .visitor-sub {
    font-size: 0.8rem;
    opacity: 0.65;
    margin-bottom: 1.25rem;
  }

  /* Mini bar chart */
  .mini-bars {
    display: flex;
    align-items: flex-end;
    gap: 5px;
    height: 52px;
    margin-top: 0.5rem;
  }
  .mini-bar {
    flex: 1;
    border-radius: 4px 4px 0 0;
    background: rgba(255,255,255,0.3);
    transition: background 0.2s;
    position: relative;
    cursor: default;
  }
  .mini-bar.active { background: var(--orange-brown); }
  .mini-bar:hover { background: rgba(255,255,255,0.5); }
  .mini-bar .bar-tip {
    display: none;
    position: absolute;
    bottom: 110%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.7);
    color: #fff;
    font-size: 0.6rem;
    padding: 2px 5px;
    border-radius: 4px;
    white-space: nowrap;
  }
  .mini-bar:hover .bar-tip { display: block; }
  .bar-labels {
    display: flex;
    gap: 5px;
    margin-top: 4px;
  }
  .bar-labels span {
    flex: 1;
    text-align: center;
    font-size: 0.55rem;
    opacity: 0.5;
  }

  /* ===== POPULAR ARTICLES ===== */
  .popular-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(74,44,24,0.06);
  }
  .card-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dark-brown);
    margin-bottom: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .card-title .see-all {
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--orange-brown);
    text-decoration: none;
    opacity: 0.85;
    transition: opacity 0.15s;
  }
  .card-title .see-all:hover { opacity: 1; text-decoration: underline; }

  .pop-item {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.65rem 0;
    border-bottom: 1px solid rgba(74,44,24,0.06);
    transition: background 0.15s;
    border-radius: 8px;
    margin: 0 -0.5rem;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }
  .pop-item:last-child { border-bottom: none; }
  .pop-item:hover { background: rgba(248,248,220,0.6); }

  .rank-badge {
    width: 28px; height: 28px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.8rem;
    font-weight: 700;
    flex-shrink: 0;
  }
  .rank-1 { background: linear-gradient(135deg, #D4AF37, #F0D060); color: #5C3D00; }
  .rank-2 { background: linear-gradient(135deg, #A8A8A8, #D0D0D0); color: #333; }
  .rank-3 { background: linear-gradient(135deg, #CD7F32, #E8A060); color: #fff; }
  .rank-n { background: rgba(74,44,24,0.08); color: var(--brown); }

  .pop-thumb {
    width: 44px; height: 44px;
    border-radius: 8px;
    object-fit: cover;
    background: var(--cream);
    flex-shrink: 0;
    border: 1px solid rgba(74,44,24,0.08);
  }
  .pop-thumb-placeholder {
    width: 44px; height: 44px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--cream), rgba(173,141,119,0.2));
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    color: var(--soft-brown);
    border: 1px solid rgba(74,44,24,0.08);
  }
  .pop-title { font-size: 0.8rem; font-weight: 500; color: var(--text-main); line-height: 1.35; }
  .pop-meta  { font-size: 0.7rem; color: var(--text-muted); margin-top: 0.15rem; }

  /* ===== CATEGORY CARD ===== */
  .category-card {
    background: linear-gradient(145deg, var(--soft-brown), #BFA090);
    border-radius: 16px;
    padding: 1.5rem;
    color: #fff;
    box-shadow: var(--shadow-md);
  }
  .progress-ring-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    margin-top: 1rem;
  }
  .ring-svg { transform: rotate(-90deg); }
  .ring-bg { fill: none; stroke: rgba(255,255,255,0.2); stroke-width: 10; }
  .ring-fill { fill: none; stroke-width: 10; stroke-linecap: round; transition: stroke-dashoffset 1s ease; }
  .ring-fill-dark  { stroke: var(--dark-brown); }
  .ring-fill-light { stroke: rgba(255,255,255,0.85); }
  .ring-label {
    position: absolute;
    font-family: 'Montserrat', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    color: #fff;
  }
  .legend-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
  }
  .legend-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; }

  /* ===== LATEST ARTICLES ===== */
  .latest-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(74,44,24,0.06);
  }
  .latest-item {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
    padding: 0.65rem 0;
    border-bottom: 1px solid rgba(74,44,24,0.06);
    transition: background 0.15s;
    border-radius: 8px;
    margin: 0 -0.5rem;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }
  .latest-item:last-child { border-bottom: none; }
  .latest-item:hover { background: rgba(248,248,220,0.6); }

  .kat-badge {
    display: inline-block;
    font-size: 0.65rem;
    font-weight: 600;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    flex-shrink: 0;
    margin-top: 0.1rem;
  }
  .kat-sejarah  { background: rgba(59,130,246,0.12); color: #1D4ED8; }
  .kat-biografi { background: rgba(139,92,246,0.12); color: #7C3AED; }

  .latest-title { font-size: 0.8rem; font-weight: 500; color: var(--text-main); line-height: 1.35; }
  .latest-date  { font-size: 0.68rem; color: var(--text-muted); margin-top: 0.15rem; }

  /* ===== ULASAN CARD ===== */
  .ulasan-card {
    background: linear-gradient(145deg, var(--orange-brown), #C47830);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    color: #fff;
    box-shadow: var(--shadow-md);
    transition: transform 0.2s ease;
  }
  .ulasan-card:hover { transform: translateY(-2px); }
  .ulasan-value {
    font-family: 'Montserrat', sans-serif;
    font-size: 2.2rem;
    font-weight: 800;
    line-height: 1;
  }

  /* ===== RESPONSIVE ===== */
  @media (max-width: 768px) {
    .dashboard-wrap { padding: 1rem; }
    .visitor-total { font-size: 2.5rem; }
  }
</style>

<div class="dashboard-wrap">

  <!-- ===== PAGE HEADER ===== -->
  <div class="page-header">
    <div class="header-badge">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
      </svg>
      Admin Panel
    </div>
    <h1>Dashboard Sejiwa.id</h1>
    <p>Selamat datang kembali. Berikut ringkasan statistik terkini platform Anda.</p>
  </div>

  <!-- ===== STAT CARDS (4 kolom) ===== -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <!-- Total User -->
    <div class="stat-card c1">
      <div class="stat-icon i1">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
      <p class="stat-value"><?= $total_user ?></p>
      <p class="stat-label">Total Pengguna</p>
      <span class="stat-trend trend-up">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
        </svg>
        Aktif
      </span>
    </div>

    <!-- Total Artikel -->
    <div class="stat-card c2">
      <div class="stat-icon i2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <p class="stat-value"><?= $total_artikel ?></p>
      <p class="stat-label">Total Artikel</p>
      <span class="stat-trend trend-neu">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
        </svg>
        Terbit
      </span>
    </div>

    <!-- Artikel Sejarah -->
    <div class="stat-card c3">
      <div class="stat-icon i3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <p class="stat-value"><?= $total_sejarah ?></p>
      <p class="stat-label">Artikel Sejarah</p>
      <span class="stat-trend trend-neu">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 12h10M7 17h4" />
        </svg>
        Kategori
      </span>
    </div>

    <!-- Biografi Tokoh -->
    <div class="stat-card c4">
      <div class="stat-icon i4">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      </div>
      <p class="stat-value"><?= $total_biografi ?></p>
      <p class="stat-label">Biografi Tokoh</p>
      <span class="stat-trend trend-neu">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 12h10M7 17h4" />
        </svg>
        Kategori
      </span>
    </div>

  </div>

  <!-- ===== MAIN GRID ===== -->
  <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

    <!-- Kiri (3/5) -->
    <div class="lg:col-span-3 flex flex-col gap-5">

      <!-- Visitor Card -->
      <div class="visitor-card">
        <h2>Statistik Pengunjung</h2>
        <div class="visitor-total"><?= $total_user + $total_admin ?></div>
        <p class="visitor-sub">Total akun terdaftar di platform</p>

        <!-- Mini Bar Chart -->
        <div class="mini-bars" id="mini-bars">
          <?php
          $bar_heights = [55, 40, 70, 45, 80, 60, 90, 50, 65, 75, 88, 95];
          $bar_labels  = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
          $current_month = (int)date('n') - 1;
          foreach ($bar_heights as $i => $h):
            $active = ($i === $current_month) ? 'active' : '';
          ?>
          <div class="mini-bar <?= $active ?>" style="height: <?= $h ?>%;">
            <span class="bar-tip"><?= $bar_labels[$i] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="bar-labels">
          <?php foreach ($bar_labels as $bl): ?>
          <span><?= $bl ?></span>
          <?php endforeach; ?>
        </div>

        <!-- Growth Info -->
        <div class="flex items-center gap-2 mt-3 flex-wrap">
          <span style="background:rgba(255,255,255,0.15); color:#fff; font-size:0.7rem; font-weight:600; padding:0.25rem 0.65rem; border-radius:999px; display:inline-flex; align-items:center; gap:0.3rem;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
            </svg>
            <?= $total_admin ?> Admin
          </span>
          <span style="background:rgba(217,139,62,0.35); color:#FFD580; font-size:0.7rem; font-weight:600; padding:0.25rem 0.65rem; border-radius:999px; display:inline-flex; align-items:center; gap:0.3rem;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <?= $total_user ?> User
          </span>
        </div>
      </div>

      <!-- Popularitas Artikel -->
      <div class="popular-card">
        <div class="card-title">
          <span style="display:flex; align-items:center; gap:0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color:var(--orange-brown);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            Artikel Terpopuler
          </span>
          <a href="#" class="see-all">Lihat Semua →</a>
        </div>

        <?php if ($populer->num_rows > 0):
          $rank = 1;
          while ($p = $populer->fetch_assoc()):
            $rank_class = match($rank) { 1 => 'rank-1', 2 => 'rank-2', 3 => 'rank-3', default => 'rank-n' };
        ?>
        <div class="pop-item">
          <!-- Rank Badge -->
          <div class="rank-badge <?= $rank_class ?>"><?= $rank ?></div>

          <!-- Thumbnail -->
          <?php if (!empty($p['thumbnail'])): ?>
          <img src="<?= htmlspecialchars($p['thumbnail']) ?>" alt="thumb" class="pop-thumb">
          <?php else: ?>
          <div class="pop-thumb-placeholder">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <?php endif; ?>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="pop-title truncate"><?= htmlspecialchars($p['judul']) ?></p>
            <p class="pop-meta">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <?= number_format($p['view_count']) ?> pembaca
            </p>
          </div>

          <!-- View bar -->
          <?php
            // Ambil max view_count untuk persentase (re-query simpel)
            static $max_view = null;
            if ($max_view === null) {
              $mv = $conn->query("SELECT MAX(view_count) as m FROM tb_artikel")->fetch_assoc();
              $max_view = max(1, (int)$mv['m']);
            }
            $pct = round(($p['view_count'] / $max_view) * 100);
          ?>
          <div style="width:48px; display:flex; flex-direction:column; align-items:flex-end; gap:2px;">
            <span style="font-family:'Montserrat',sans-serif; font-size:0.65rem; font-weight:700; color:var(--orange-brown);"><?= $pct ?>%</span>
            <div style="width:48px; height:4px; background:rgba(74,44,24,0.08); border-radius:99px; overflow:hidden;">
              <div style="width:<?= $pct ?>%; height:100%; background:var(--orange-brown); border-radius:99px;"></div>
            </div>
          </div>

        </div>
        <?php $rank++; endwhile;
        else: ?>
        <p style="font-size:0.85rem; color:var(--text-muted); text-align:center; padding:1.5rem 0;">
          Belum ada data artikel.
        </p>
        <?php endif; ?>
      </div>

    </div><!-- /kiri -->

    <!-- Kanan (2/5) -->
    <div class="lg:col-span-2 flex flex-col gap-5">

      <!-- Ulasan Card (bonus) -->
      <div class="ulasan-card">
        <div style="display:flex; align-items:center; justify-content:space-between;">
          <div>
            <p style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.07em; opacity:0.75; margin-bottom:0.25rem;">Total Ulasan</p>
            <p class="ulasan-value"><?= $total_ulasan ?></p>
            <p style="font-size:0.75rem; opacity:0.65; margin-top:0.2rem;">Ulasan dari pengguna</p>
          </div>
          <div style="width:52px; height:52px; background:rgba(255,255,255,0.18); border-radius:14px; display:flex; align-items:center; justify-content:center;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Kategori Artikel -->
      <div class="category-card">
        <div class="card-title" style="color:#fff;">
          <span style="display:flex; align-items:center; gap:0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
            </svg>
            Kategori Artikel
          </span>
        </div>

        <?php
        $pct_sejarah  = $total_artikel > 0 ? round(($total_sejarah / $total_artikel) * 100) : 0;
        $pct_biografi = 100 - $pct_sejarah;

        // SVG progress ring values
        $radius = 52;
        $circ   = round(2 * 3.14159 * $radius, 2);
        $dash_s = round($circ * $pct_sejarah / 100, 2);
        $dash_b = $circ - $dash_s;
        ?>

        <div class="progress-ring-wrap">
          <!-- SVG Ring -->
          <div style="position:relative; width:120px; height:120px; flex-shrink:0;">
            <svg width="120" height="120" class="ring-svg" viewBox="0 0 120 120">
              <!-- BG ring -->
              <circle class="ring-bg" cx="60" cy="60" r="<?= $radius ?>"/>
              <!-- Sejarah -->
              <circle class="ring-fill ring-fill-dark" cx="60" cy="60" r="<?= $radius ?>"
                stroke-dasharray="<?= $dash_s ?> <?= $circ - $dash_s ?>"
                stroke-dashoffset="0"/>
              <!-- Biografi (offset) -->
              <circle class="ring-fill ring-fill-light" cx="60" cy="60" r="<?= $radius ?>"
                stroke-dasharray="<?= $dash_b ?> <?= $circ - $dash_b ?>"
                stroke-dashoffset="-<?= $dash_s ?>"/>
            </svg>
            <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; transform:rotate(0deg);">
              <span class="ring-label"><?= $total_artikel ?></span>
            </div>
          </div>

          <!-- Legend -->
          <div style="display:flex; flex-direction:column; gap:0.75rem;">
            <div class="legend-item">
              <span class="legend-dot" style="background:var(--dark-brown);"></span>
              <div>
                <p style="font-size:0.8rem; font-weight:600;">Sejarah</p>
                <p style="font-size:0.7rem; opacity:0.75;"><?= $total_sejarah ?> artikel · <?= $pct_sejarah ?>%</p>
              </div>
            </div>
            <div class="legend-item">
              <span class="legend-dot" style="background:rgba(255,255,255,0.85); border:1px solid rgba(0,0,0,0.1);"></span>
              <div>
                <p style="font-size:0.8rem; font-weight:600;">Biografi</p>
                <p style="font-size:0.7rem; opacity:0.75;"><?= $total_biografi ?> artikel · <?= $pct_biografi ?>%</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Progress bars -->
        <div style="margin-top:1.25rem; display:flex; flex-direction:column; gap:0.6rem;">
          <div>
            <div style="display:flex; justify-content:space-between; margin-bottom:0.3rem;">
              <span style="font-size:0.72rem; opacity:0.8;">Sejarah</span>
              <span style="font-size:0.72rem; font-weight:700;"><?= $pct_sejarah ?>%</span>
            </div>
            <div style="height:6px; background:rgba(255,255,255,0.2); border-radius:99px; overflow:hidden;">
              <div style="width:<?= $pct_sejarah ?>%; height:100%; background:var(--dark-brown); border-radius:99px; transition:width 1s ease;"></div>
            </div>
          </div>
          <div>
            <div style="display:flex; justify-content:space-between; margin-bottom:0.3rem;">
              <span style="font-size:0.72rem; opacity:0.8;">Biografi</span>
              <span style="font-size:0.72rem; font-weight:700;"><?= $pct_biografi ?>%</span>
            </div>
            <div style="height:6px; background:rgba(255,255,255,0.2); border-radius:99px; overflow:hidden;">
              <div style="width:<?= $pct_biografi ?>%; height:100%; background:rgba(255,255,255,0.75); border-radius:99px; transition:width 1s ease;"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Artikel Terbaru -->
      <div class="latest-card">
        <div class="card-title">
          <span style="display:flex; align-items:center; gap:0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" style="color:var(--soft-brown);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Artikel Terbaru
          </span>
          <a href="#" class="see-all">Lihat Semua →</a>
        </div>

        <?php if ($terbaru->num_rows > 0):
          while ($t = $terbaru->fetch_assoc()):
            $kat_class = $t['kategori'] === 'sejarah' ? 'kat-sejarah' : 'kat-biografi';
        ?>
        <div class="latest-item">
          <span class="kat-badge <?= $kat_class ?>"><?= ucfirst($t['kategori']) ?></span>
          <div class="flex-1 min-w-0">
            <p class="latest-title truncate"><?= htmlspecialchars($t['judul']) ?></p>
            <p class="latest-date">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <?= date('d M Y', strtotime($t['created_at'])) ?>
            </p>
          </div>
        </div>
        <?php endwhile;
        else: ?>
        <p style="font-size:0.85rem; color:var(--text-muted); text-align:center; padding:1.5rem 0;">
          Belum ada artikel.
        </p>
        <?php endif; ?>
      </div>

    </div><!-- /kanan -->

  </div><!-- /main grid -->

</div><!-- /dashboard-wrap -->