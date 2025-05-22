<?php
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('admin');

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$status_query = '';

if ($status_filter && in_array($status_filter, ['menunggu', 'dibayar', 'digunakan', 'kedaluwarsa', 'dibatalkan'])) {
    $status_query = " AND t.status = '$status_filter'";
}

$where = $search ?
    "WHERE (u.nama_lengkap LIKE '%$search%' OR f.judul_film LIKE '%$search%' OR b.nama_bioskop LIKE '%$search%' OR s.nama_studio LIKE '%$search%') $status_query" :
    ($status_filter ? "WHERE t.status = '$status_filter'" : '');

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Ambil data tiket
$query = "
SELECT 
    t.id_tiket,
    u.nama_lengkap AS nama_user,
    f.judul_film,
    s.nama_studio,
    b.nama_bioskop,
    j.waktu_tayang,
    t.nomor_kursi,
    t.status,
    t.kode_qr,
    t.created_at
FROM tiket t
JOIN users u ON t.id_user = u.id_user
JOIN film f ON t.id_film = f.id_film
JOIN jadwal j ON t.jadwal_tayang = j.id_jadwal
JOIN studio s ON j.id_studio = s.id_studio
JOIN bioskop b ON s.id_bioskop = b.id_bioskop
$where
ORDER BY t.created_at DESC
";

$tiket = $conn->query($query);
$status_list = ['menunggu', 'dibayar', 'digunakan', 'kedaluwarsa', 'dibatalkan'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Tiket | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex min-h-screen">

<!-- Sidebar -->
<aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
  <h2 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2">
    <i data-lucide="ticket"></i>JATIX Admin
  </h2>
  <nav class="space-y-2 text-sm">
    <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="layout-dashboard"></i> Dashboard</a>
    <a href="bioskop.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="building"></i> Bioskop</a>
    <a href="film.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="video"></i> Film</a>
    <a href="data_user.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="users"></i> Pengguna</a>
    <a href="data_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="ticket"></i> Tiket</a>
    <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="file-text"></i> Laporan</a>
    <a href="log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="history"></i> Log Aktivitas</a>
    <a href="audit_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="scan-line"></i> Audit Tiket</a>
    <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
  </nav>
</aside>

<main class="ml-64 p-8 w-full">
  <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="ticket"></i> Data Tiket</h1>

  <?php if ($success): ?>
    <div class="bg-green-100 text-green-700 p-3 mb-4 rounded flex items-center gap-2">
      <i data-lucide="check-circle"></i> <?= $success ?>
    </div>
  <?php endif; ?>

  <!-- Filter -->
  <form method="GET" class="mb-6 flex flex-wrap gap-4 items-center">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari user/film/bioskop..." class="px-4 py-2 border rounded shadow-sm focus:ring-purple-500" />
    <select name="status" onchange="this.form.submit()" class="px-4 py-2 border rounded shadow-sm focus:ring-purple-500">
      <option value="">Semua Status</option>
      <?php foreach ($status_list as $s): ?>
        <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <!-- Export -->
  <div class="flex justify-end gap-2 mb-4">
    <a href="export/excel/export_tiket_excel.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-2"><i data-lucide="file-spreadsheet"></i> Excel</a>
    <a href="export/pdf/export_tiket_pdf.php" target="_blank" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 flex items-center gap-2"><i data-lucide="file-text"></i> PDF</a>
  </div>

  <!-- Tabel Tiket -->
  <div class="bg-white p-6 rounded shadow overflow-x-auto">
    <table class="min-w-full text-sm border">
      <thead>
        <tr class="bg-purple-100 text-purple-800 text-left">
          <th class="p-3 border">#</th>
          <th class="p-3 border">User</th>
          <th class="p-3 border">Film</th>
          <th class="p-3 border">Bioskop</th>
          <th class="p-3 border">Studio</th>
          <th class="p-3 border">Waktu</th>
          <th class="p-3 border">Kursi</th>
          <th class="p-3 border">Status</th>
          <th class="p-3 border">QR</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($r = $tiket->fetch_assoc()): ?>
          <tr class="hover:bg-gray-50">
            <td class="p-3 border"><?= $r['id_tiket'] ?></td>
            <td class="p-3 border"><?= htmlspecialchars($r['nama_user']) ?></td>
            <td class="p-3 border"><?= htmlspecialchars($r['judul_film']) ?></td>
            <td class="p-3 border"><?= htmlspecialchars($r['nama_bioskop']) ?></td>
            <td class="p-3 border"><?= htmlspecialchars($r['nama_studio']) ?></td>
            <td class="p-3 border"><?= date('d M Y H:i', strtotime($r['waktu_tayang'])) ?></td>
            <td class="p-3 border"><?= htmlspecialchars($r['nomor_kursi']) ?></td>
            <td class="p-3 border"><?= ucfirst($r['status']) ?></td>
            <td class="p-3 border text-center">
              <?php
              $qr = "../assets/qr/{$r['kode_qr']}.png";
              if (file_exists($qr)):
              ?>
                <img src="<?= $qr ?>" class="w-10 h-10 object-contain mx-auto">
              <?php else: ?>
                <span class="text-xs italic text-gray-400">QR tidak ada</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
