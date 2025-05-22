<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('operator');

$id_user = $_SESSION['id_user'];

// Ambil data operator & bioskop
$user_stmt = $conn->prepare("SELECT u.nama_lengkap, b.nama_bioskop FROM users u JOIN bioskop b ON u.id_bioskop = b.id_bioskop WHERE u.id_user = ?");
$user_stmt->bind_param("i", $id_user);
$user_stmt->execute();
$user_stmt->bind_result($nama_operator, $nama_bioskop);
$user_stmt->fetch();
$user_stmt->close();

// Hitung total studio
$studio_result = $conn->query("SELECT COUNT(*) AS total FROM studio WHERE id_bioskop = (SELECT id_bioskop FROM users WHERE id_user = $id_user)");
$total_studio = $studio_result->fetch_assoc()['total'];

// Hitung total jadwal tayang
$jadwal_result = $conn->query("SELECT COUNT(*) AS total FROM jadwal WHERE id_studio IN (SELECT id_studio FROM studio WHERE id_bioskop = (SELECT id_bioskop FROM users WHERE id_user = $id_user))");
$total_jadwal = $jadwal_result->fetch_assoc()['total'];

// Hitung total tiket terjual
$tiket_result = $conn->query("SELECT COUNT(*) AS total FROM tiket WHERE status IN ('dibayar','digunakan') AND id_jadwal IN (SELECT id_jadwal FROM jadwal WHERE id_studio IN (SELECT id_studio FROM studio WHERE id_bioskop = (SELECT id_bioskop FROM users WHERE id_user = $id_user)))");
$total_tiket = $tiket_result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Operator | JATIX</title>
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
      <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="layout-dashboard"></i> Dashboard</a>
      <a href="studio.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="building"></i> Studio</a>
      <a href="jadwal.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="calendar-clock"></i> Jadwal</a>
      <a href="tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="ticket"></i> Tiket</a>
      <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="file-text"></i> Laporan</a>
      <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="ml-64 p-8 w-full">
    <h1 class="text-2xl font-bold text-purple-700 mb-2">📊 Dashboard Operator</h1>
    <p class="text-sm text-gray-500 mb-6">Operator: <strong><?= htmlspecialchars($nama_operator) ?></strong> — <strong><?= htmlspecialchars($nama_bioskop) ?></strong></p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white rounded-lg shadow p-5">
        <h2 class="text-sm text-gray-500">Total Studio</h2>
        <p class="text-3xl font-bold text-purple-600 mt-2">🏢 <?= $total_studio ?></p>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <h2 class="text-sm text-gray-500">Jadwal Tayang</h2>
        <p class="text-3xl font-bold text-blue-600 mt-2">🗓 <?= $total_jadwal ?></p>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <h2 class="text-sm text-gray-500">Tiket Terjual</h2>
        <p class="text-3xl font-bold text-green-600 mt-2">🎟 <?= $total_tiket ?></p>
      </div>
    </div>
  </main>

  <script>lucide.createIcons();</script>
</body>
</html>
