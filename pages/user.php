<?php
// pages/user.php — Halaman Data User
// Dipanggil oleh dashboardAdmin.php via include

// ===== AMBIL DATA DARI DATABASE =====
// Searching
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Filtering by role
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';

// Sorting
$sort_col = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_dir = isset($_GET['dir'])  ? $_GET['dir']  : 'asc';

// Whitelist kolom yang boleh di-sort (keamanan)
$allowed_sort = ['id', 'nama_lengkap', 'username', 'email', 'role', 'created_at'];
if (!in_array($sort_col, $allowed_sort)) $sort_col = 'id';
$sort_dir = $sort_dir === 'desc' ? 'desc' : 'asc';
$next_dir = $sort_dir === 'asc' ? 'desc' : 'asc';

// Pagination
$per_page    = 10;
$current_page = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $per_page;

// Bangun query dengan kondisi search & filter
$where = "WHERE 1=1";
$params = [];
$types  = "";

if ($search !== '') {
    $where   .= " AND (nama_lengkap LIKE ? OR username LIKE ? OR email LIKE ?)";
    $keyword  = "%$search%";
    $params   = array_merge($params, [$keyword, $keyword, $keyword]);
    $types   .= "sss";
}
if ($filter_role !== '') {
    $where  .= " AND role = ?";
    $params  = array_merge($params, [$filter_role]);
    $types  .= "s";
}

// Hitung total data (untuk pagination)
$count_sql  = "SELECT COUNT(*) as total FROM tb_user $where";
$count_stmt = $conn->prepare($count_sql);
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_data = $count_stmt->get_result()->fetch_assoc()['total'];
$total_page = ceil($total_data / $per_page);

// Ambil data sesuai halaman
$sql  = "SELECT * FROM tb_user $where ORDER BY $sort_col $sort_dir LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$all_types  = $types . "ii";
$all_params = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$users = $stmt->get_result();

// Helper: buat URL dengan parameter
function buildUrl($params) {
    $base = array_merge($_GET, $params);
    return '?' . http_build_query($base);
}

// ===== HAPUS USER =====
if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    $del = $conn->prepare("DELETE FROM tb_user WHERE id = ? AND role != 'admin'");
    $del->bind_param("i", $hapus_id);
    $del->execute();
    header("Location: dashboardAdmin.php?page=user&msg=hapus");
    exit();
}
?>

<div class="bg-white p-6 rounded-xl shadow">

    <!-- Judul & Tombol Tambah -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-sejiwa-dark">Data User</h1>
        <a href="dashboardAdmin.php?page=user&aksi=tambah" 
           class="bg-sejiwa-dark text-white px-5 py-2 rounded-lg font-semibold hover:bg-sejiwa-medium transition text-sm">
            + Tambah User
        </a>
    </div>

    <!-- Pesan sukses/hapus -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="mb-4 p-3 rounded-lg text-sm
            <?= $_GET['msg'] === 'hapus' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
            <?= $_GET['msg'] === 'hapus' ? '✅ User berhasil dihapus.' : '✅ Data berhasil disimpan.' ?>
        </div>
    <?php endif; ?>

    <!-- ===== SEARCH & FILTER ===== -->
    <form method="GET" action="dashboardAdmin.php" class="flex flex-wrap gap-3 mb-5">
        <input type="hidden" name="page" value="user">

        <!-- Search -->
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
               placeholder="🔍 Cari nama, username, email..."
               class="flex-grow p-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-sejiwa-dark">

        <!-- Filter Role -->
        <select name="role" class="p-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-sejiwa-dark">
            <option value="">Semua Role</option>
            <option value="admin"  <?= $filter_role === 'admin'  ? 'selected' : '' ?>>Admin</option>
            <option value="user"   <?= $filter_role === 'user'   ? 'selected' : '' ?>>User</option>
        </select>

        <button type="submit" class="bg-sejiwa-dark text-white px-4 py-2 rounded-lg text-sm hover:bg-sejiwa-medium transition">
            Cari
        </button>
        <a href="dashboardAdmin.php?page=user" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">
            Reset
        </a>
    </form>

    <!-- Info jumlah data -->
    <p class="text-sm text-gray-500 mb-3">
        Menampilkan <strong><?= $total_data ?></strong> data
        <?= $search ? "untuk pencarian \"<strong>$search</strong>\"" : '' ?>
    </p>

    <!-- ===== TABEL ===== -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm" style="min-width:700px;">
            <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-xs">

                    <!-- Numbering -->
                    <th class="p-3 text-center w-10">No</th>

                    <!-- Sorting: klik judul kolom untuk sort -->
                    <th class="p-3 text-left">
                        <a href="<?= buildUrl(['page'=>'user','sort'=>'nama_lengkap','dir'=>$sort_col==='nama_lengkap'?$next_dir:'asc']) ?>"
                           class="hover:text-sejiwa-dark flex items-center gap-1">
                            Nama
                            <?= $sort_col === 'nama_lengkap' ? ($sort_dir==='asc'?'↑':'↓') : '↕' ?>
                        </a>
                    </th>
                    <th class="p-3 text-left">
                        <a href="<?= buildUrl(['page'=>'user','sort'=>'username','dir'=>$sort_col==='username'?$next_dir:'asc']) ?>"
                           class="hover:text-sejiwa-dark flex items-center gap-1">
                            Username
                            <?= $sort_col === 'username' ? ($sort_dir==='asc'?'↑':'↓') : '↕' ?>
                        </a>
                    </th>
                    <th class="p-3 text-left">Email</th>
                    <th class="p-3 text-center">
                        <a href="<?= buildUrl(['page'=>'user','sort'=>'role','dir'=>$sort_col==='role'?$next_dir:'asc']) ?>"
                           class="hover:text-sejiwa-dark flex items-center justify-center gap-1">
                            Role
                            <?= $sort_col === 'role' ? ($sort_dir==='asc'?'↑':'↓') : '↕' ?>
                        </a>
                    </th>
                    <th class="p-3 text-left">
                        <a href="<?= buildUrl(['page'=>'user','sort'=>'created_at','dir'=>$sort_col==='created_at'?$next_dir:'asc']) ?>"
                           class="hover:text-sejiwa-dark flex items-center gap-1">
                            Terdaftar
                            <?= $sort_col === 'created_at' ? ($sort_dir==='asc'?'↑':'↓') : '↕' ?>
                        </a>
                    </th>
                    <th class="p-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users->num_rows > 0):
                    $no = $offset + 1;
                    while ($row = $users->fetch_assoc()): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <!-- Numbering otomatis -->
                    <td class="p-3 text-center text-gray-500"><?= $no++ ?></td>
                    <td class="p-3 font-medium text-gray-800"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                    <td class="p-3 text-gray-600">@<?= htmlspecialchars($row['username']) ?></td>
                    <td class="p-3 text-gray-600"><?= htmlspecialchars($row['email']) ?></td>
                    <td class="p-3 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            <?= $row['role'] === 'admin' ? 'bg-sejiwa-dark text-white' : 'bg-gray-200 text-gray-700' ?>">
                            <?= ucfirst($row['role']) ?>
                        </span>
                    </td>
                    <td class="p-3 text-gray-500 text-xs"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    <td class="p-3 text-center">
                        <?php if ($row['role'] !== 'admin'): ?>
                        <a href="<?= buildUrl(['page'=>'user','hapus'=>$row['id']]) ?>"
                           onclick="return confirm('Yakin hapus user <?= htmlspecialchars($row['username']) ?>?')"
                           class="text-red-600 hover:underline text-xs font-semibold">Hapus</a>
                        <?php else: ?>
                        <span class="text-gray-300 text-xs">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                    <?php endwhile;
                else: ?>
                <tr>
                    <td colspan="7" class="p-8 text-center text-gray-400">
                        Tidak ada data user yang ditemukan.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ===== PAGINATION ===== -->
    <?php if ($total_page > 1): ?>
    <div class="flex justify-between items-center mt-5">
        <p class="text-sm text-gray-500">
            Halaman <strong><?= $current_page ?></strong> dari <strong><?= $total_page ?></strong>
        </p>
        <div class="flex gap-2">
            <?php if ($current_page > 1): ?>
                <a href="<?= buildUrl(['page'=>'user','halaman'=>$current_page-1]) ?>"
                   class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm">← Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_page; $i++): ?>
                <a href="<?= buildUrl(['page'=>'user','halaman'=>$i]) ?>"
                   class="px-3 py-1 rounded text-sm <?= $i === $current_page ? 'bg-sejiwa-dark text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($current_page < $total_page): ?>
                <a href="<?= buildUrl(['page'=>'user','halaman'=>$current_page+1]) ?>"
                   class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm">Next →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>