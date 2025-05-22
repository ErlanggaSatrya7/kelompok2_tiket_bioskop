<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('admin');

$error = '';
$success = '';
$search = trim($_GET['search'] ?? '');
$where = $search ? "WHERE nama_bioskop LIKE '%$search%' OR lokasi LIKE '%$search%'" : '';

// Tambah bioskop
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_bioskop']);
    $lokasi = trim($_POST['lokasi']);

    if ($nama === '' || $lokasi === '') {
        $error = "Nama bioskop dan lokasi wajib diisi.";
    } else {
        $stmt = $conn->prepare("CALL sp_tambah_bioskop(?, ?)");
        $stmt->bind_param("ss", $nama, $lokasi);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Bioskop berhasil ditambahkan.";
            header("Location: bioskop.php");
            exit;
        } else {
            $error = "Gagal menambahkan bioskop.";
        }
    }
}

// Hapus bioskop
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id = (int) $_POST['id_bioskop'];
    $conn->query("CALL sp_hapus_bioskop($id)");
    $_SESSION['success'] = "Bioskop berhasil dihapus.";
    header("Location: bioskop.php");
    exit;
}

// Ambil data dari VIEW
$bioskop = $conn->query("SELECT * FROM view_bioskop_dengan_jumlah_studio $where ORDER BY id_bioskop DESC");

// Notifikasi
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kelola Bioskop | JATIX Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex min-h-screen">

<!-- Sidebar -->
<aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
  <h2 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2">
    <i data-lucide="building"></i>JATIX Admin
  </h2>
  <nav class="space-y-2 text-sm">
    <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="layout-dashboard"></i> Dashboard</a>
    <a href="bioskop.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="building"></i> Bioskop</a>
    <a href="film.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="video"></i> Film</a>
    <a href="data_user.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="users"></i> Pengguna</a>
    <!-- <a href="data_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="ticket"></i> Tiket</a> -->
    <!-- <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="file-text"></i> Laporan</a> -->
    <a href="log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="history"></i> Log Aktivitas</a>
    <!-- <a href="audit_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="scan-line"></i> Audit Tiket</a> -->
    <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
  </nav>
</aside>

<!-- Konten -->
<main class="ml-64 p-8 w-full">
  <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="building"></i> Kelola Bioskop</h1>

  <?php if ($success): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4 flex items-center gap-2">
      <i data-lucide="check-circle"></i> <?= $success ?>
    </div>
  <?php elseif ($error): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4 flex items-center gap-2">
      <i data-lucide="alert-circle"></i> <?= $error ?>
    </div>
  <?php endif; ?>

  <!-- Tambah Form -->
  <form method="POST" class="bg-white rounded shadow p-6 max-w-lg mb-6 space-y-4" onsubmit="return confirm('Tambah bioskop baru?')">
    <input type="text" name="nama_bioskop" placeholder="Nama Bioskop" class="w-full border rounded px-3 py-2" required>
    <input type="text" name="lokasi" placeholder="Lokasi" class="w-full border rounded px-3 py-2" required>
    <button type="submit" name="tambah" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tambah</button>
  </form>

  <!-- Filter -->
  <form method="GET" class="mb-6 max-w-md flex gap-2">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari bioskop..." class="w-full px-4 py-2 border rounded">
    <button class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700"><i data-lucide="search"></i></button>
  </form>

  <!-- List Bioskop -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php while ($row = $bioskop->fetch_assoc()): ?>
      <div class="bg-white p-4 rounded shadow">
        <h3 class="text-lg font-bold"><?= htmlspecialchars($row['nama_bioskop']) ?></h3>
        <p class="text-sm text-gray-600">📍 <?= htmlspecialchars($row['lokasi']) ?></p>
        <p class="text-sm text-gray-500">🎬 Studio: <?= $row['total_studio'] ?></p>
        <div class="flex justify-end gap-2 mt-4">
          <a href="edit_bioskop.php?id=<?= $row['id_bioskop'] ?>" onclick="return confirm('Edit bioskop ini?')" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600"><i data-lucide="edit-3"></i></a>
          <form method="POST" onsubmit="return confirm('Yakin hapus bioskop ini?')">
            <input type="hidden" name="id_bioskop" value="<?= $row['id_bioskop'] ?>">
            <button type="submit" name="hapus" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700"><i data-lucide="trash-2"></i></button>
          </form>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
