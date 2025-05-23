<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('operator');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Penting untuk menangkap SIGNAL 45000

$id_user = $_SESSION['id_user'];

// Ambil id bioskop operator
$stmt = $conn->prepare("SELECT id_bioskop FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$stmt->bind_result($id_bioskop);
$stmt->fetch();
$stmt->close();

$error = '';
$success = '';

// Tambah studio dengan prosedur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_studio'] ?? '');
    $kapasitas = intval($_POST['kapasitas'] ?? 0);

    try {
        $stmt = $conn->prepare("CALL sp_tambah_studio_validasi(?, ?, ?)");
        $stmt->bind_param("sii", $nama, $kapasitas, $id_bioskop);
        $stmt->execute();
        $success = "Studio berhasil ditambahkan.";
    } catch (mysqli_sql_exception $e) {
        $error = "Gagal menambahkan studio: " . $e->getMessage();
    }
}

// Hapus studio
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM studio WHERE id_studio = ? AND id_bioskop = ?");
    $stmt->bind_param("ii", $id, $id_bioskop);
    if ($stmt->execute()) {
        $success = "Studio berhasil dihapus.";
    } else {
        $error = "Gagal menghapus studio.";
    }
}

$studio = $conn->query("SELECT id_studio, nama_studio, kapasitas FROM studio WHERE id_bioskop = $id_bioskop ORDER BY nama_studio ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Studio | Operator</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex min-h-screen">

  <!-- Sidebar -->
  <aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
    <h2 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2">
      <i data-lucide="video"></i> JATIX Operator
    </h2>
    <nav class="space-y-2 text-sm">
      <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="layout-dashboard"></i> Dashboard</a>
      <a href="studio.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="building"></i> Studio</a>
      <a href="jadwal.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="calendar-clock"></i> Jadwal</a>
      <a href="tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="ticket"></i> Tiket</a>
      <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="file-text"></i> Laporan</a>
      <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="ml-64 p-8 w-full">
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="building"></i> Manajemen Studio</h1>

    <?php if ($success): ?>
      <div class="bg-green-100 text-green-700 px-4 py-2 mb-4 rounded"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded"><?= $error ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Tabel Studio -->
      <div class="lg:col-span-2 bg-white rounded shadow p-4">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i data-lucide="list"></i> Daftar Studio</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b">
                <th class="py-2">Nama</th>
                <th>Kapasitas</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $studio->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="py-2"><?= htmlspecialchars($row['nama_studio']) ?></td>
                  <td><?= $row['kapasitas'] ?></td>
                  <td class="flex gap-2">
                    <a href="edit_studio.php?id=<?= $row['id_studio'] ?>" class="text-blue-600 hover:underline">Edit</a>
                    <a href="?hapus=<?= $row['id_studio'] ?>" onclick="return confirm('Yakin hapus?')" class="text-red-600 hover:underline">Hapus</a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Form Tambah -->
      <div class="bg-white rounded shadow p-4">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i data-lucide="plus-circle"></i> Tambah Studio</h2>
        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Nama Studio</label>
            <input type="text" name="nama_studio" required class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Kapasitas</label>
            <input type="number" name="kapasitas" required min="1" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
          </div>
          <button name="tambah" class="w-full bg-purple-600 text-white py-2 rounded hover:bg-purple-700">Tambah</button>
        </form>
      </div>
    </div>
  </main>

  <script>lucide.createIcons();</script>
</body>
</html>
