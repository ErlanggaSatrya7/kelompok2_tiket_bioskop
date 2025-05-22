<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('operator');

$id_user = $_SESSION['id_user'];

// Ambil id bioskop operator
$stmt = $conn->prepare("SELECT id_bioskop FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$stmt->bind_result($id_bioskop);
$stmt->fetch();
$stmt->close();

// Hitung tiket hari ini
$today = date('Y-m-d');
$data = [];
$label = [];

$tiket_hari = $conn->query("SELECT COUNT(*) FROM tiket t JOIN jadwal j ON t.id_film = j.id_film JOIN studio s ON j.id_studio = s.id_studio WHERE s.id_bioskop = $id_bioskop AND DATE(t.created_at) = '$today'")->fetch_row()[0];

// Data grafik 7 hari terakhir
for ($i = 6; $i >= 0; $i--) {
  $tgl = date('Y-m-d', strtotime("-$i days"));
  $label[] = date('d M', strtotime($tgl));
  $jumlah = $conn->query("SELECT COUNT(*) FROM tiket t JOIN jadwal j ON t.id_film = j.id_film JOIN studio s ON j.id_studio = s.id_studio WHERE s.id_bioskop = $id_bioskop AND DATE(t.created_at) = '$tgl'")->fetch_row()[0];
  $data[] = $jumlah;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan | Operator</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
      <a href="studio.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="building"></i> Studio</a>
      <a href="jadwal.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="calendar-clock"></i> Jadwal</a>
      <a href="tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="ticket"></i> Tiket</a>
      <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700"><i data-lucide="file-text"></i> Laporan</a>
      <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
    </nav>
  </aside>

  <!-- Main -->
  <main class="ml-64 p-8 w-full">
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="file-text"></i> Laporan Tiket</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <div class="bg-white p-4 rounded shadow">
        <p class="text-sm text-gray-500">Tiket Hari Ini</p>
        <p class="text-3xl font-bold text-purple-700"><?= $tiket_hari ?></p>
      </div>
    </div>

    <div class="bg-white p-6 rounded shadow">
      <h2 class="text-lg font-semibold mb-4">📊 Tiket Terjual 7 Hari Terakhir</h2>
      <canvas id="chartTiket" height="120"></canvas>
    </div>
  </main>

  <script>
    lucide.createIcons();

    const ctx = document.getElementById('chartTiket').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($label) ?>,
        datasets: [{
          label: 'Tiket Terjual',
          data: <?= json_encode($data) ?>,
          borderColor: '#8b5cf6',
          backgroundColor: 'rgba(139, 92, 246, 0.2)',
          tension: 0.4,
          fill: true,
          pointRadius: 5,
          pointHoverRadius: 7
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  </script>
</body>
</html>
