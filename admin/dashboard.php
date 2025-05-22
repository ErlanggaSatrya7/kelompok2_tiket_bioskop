<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('admin');

// Ambil data summary dari view
$summary = $conn->query("SELECT * FROM view_dashboard_summary")->fetch_assoc();
$stats = [
    ['label'=>'Film','value'=>$summary['total_film'],'icon'=>'video'],
    ['label'=>'Bioskop','value'=>$summary['total_bioskop'],'icon'=>'building'],
    ['label'=>'Pengguna','value'=>$summary['total_pengguna'],'icon'=>'users'],
];

// Log aktivitas terbaru
$log = $conn->query("
  SELECT la.aksi, la.deskripsi, la.waktu, u.username 
  FROM log_aktivitas la 
  JOIN users u ON la.id_user = u.id_user 
  ORDER BY la.waktu DESC 
  LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex min-h-screen">

<!-- Sidebar -->
<aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
  <h2 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2">
    <i data-lucide="layout-dashboard"></i> JATIX Admin
  </h2>
  <nav class="space-y-2 text-sm">
    <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="layout-dashboard"></i> Dashboard</a>
    <a href="bioskop.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="building"></i> Bioskop</a>
    <a href="film.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="video"></i> Film</a>
    <a href="data_user.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="users"></i> Pengguna</a>
    <a href="log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="history"></i> Log Aktivitas</a>
    <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
  </nav>
</aside>

<!-- Konten Utama -->
<main class="ml-64 p-8 w-full space-y-10">
  <h1 class="text-2xl font-bold flex items-center gap-2"><i data-lucide="layout-dashboard"></i> Dashboard Admin</h1>

  <!-- Stat Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
    <?php foreach($stats as $s): ?>
    <div class="bg-white shadow rounded p-4 text-center hover:shadow-lg transition">
      <i data-lucide="<?= $s['icon'] ?>" class="w-6 h-6 mx-auto text-purple-600 mb-2"></i>
      <p class="text-gray-500"><?= $s['label'] ?></p>
      <h2 class="text-2xl font-bold"><?= $s['value'] ?></h2>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Log Aktivitas -->
  <section class="space-y-4">
    <h2 class="text-xl font-semibold flex items-center gap-2"><i data-lucide="history"></i> Log Aktivitas Terbaru</h2>
    <div class="bg-white shadow rounded p-4">
      <?php if($log->num_rows): ?>
        <ul class="space-y-2">
          <?php while($l = $log->fetch_assoc()): ?>
          <li class="border-b pb-2">
            <strong><?= strtoupper($l['aksi']) ?>:</strong> <?= htmlspecialchars($l['deskripsi']) ?>
            <span class="text-sm text-gray-500">— <?= $l['username'] ?>, <?= date('d M Y H:i', strtotime($l['waktu'])) ?></span>
          </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <p class="text-gray-500">Belum ada log aktivitas.</p>
      <?php endif; ?>
    </div>
  </section>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
