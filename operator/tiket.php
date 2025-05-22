<?php
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

// Jika ada validasi check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_tiket'])) {
  $id_tiket = (int) $_POST['id_tiket'];
  $stmt = $conn->prepare("UPDATE tiket SET status = 'digunakan', updated_at = NOW() WHERE id_tiket = ? AND status = 'LUNAS'");
  $stmt->bind_param("i", $id_tiket);
  $stmt->execute();
  $stmt->close();
}

// Ambil data tiket hanya dari studio bioskop ini
$tiket = $conn->query("
  SELECT t.id_tiket, u.username AS nama_user, f.judul_film, s.nama_studio, j.waktu_tayang, 
         t.status, t.created_at 
  FROM tiket t
  JOIN users u ON t.id_user = u.id_user
  JOIN jadwal j ON t.id_film = j.id_film
  JOIN film f ON j.id_film = f.id_film
  JOIN studio s ON j.id_studio = s.id_studio
  WHERE s.id_bioskop = $id_bioskop
  ORDER BY t.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Tiket | Operator</title>
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
      <a href="studio.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="building"></i> Studio</a>
      <a href="jadwal.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="calendar-clock"></i> Jadwal</a>
      <a href="tiket.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="ticket"></i> Tiket</a>
      <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="file-text"></i> Laporan</a>
      <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="ml-64 p-8 w-full">
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="ticket"></i> Daftar Tiket</h1>

    <div class="bg-white rounded shadow p-4 overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="py-2">Pemesan</th>
            <th>Film</th>
            <th>Studio</th>
            <th>Waktu Tayang</th>
            <th>Status</th>
            <th>Dipesan Pada</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $tiket->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="py-2"><?= htmlspecialchars($row['nama_user']) ?></td>
              <td><?= htmlspecialchars($row['judul_film']) ?></td>
              <td><?= $row['nama_studio'] ?></td>
              <td><?= date('d M Y H:i', strtotime($row['waktu_tayang'])) ?></td>
              <td>
                <?php if ($row['status'] == 'LUNAS'): ?>
                  <span class="text-green-600 font-semibold">LUNAS</span>
                <?php elseif ($row['status'] == 'digunakan'): ?>
                  <span class="text-blue-600 font-semibold">DIGUNAKAN</span>
                <?php else: ?>
                  <span class="text-red-600 font-semibold">BELUM</span>
                <?php endif; ?>
              </td>
              <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
              <td>
                <?php if ($row['status'] == 'LUNAS'): ?>
                  <form method="POST" onsubmit="return confirm('Validasi tiket ini?')">
                    <input type="hidden" name="id_tiket" value="<?= $row['id_tiket'] ?>">
                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Check-in</button>
                  </form>
                <?php elseif ($row['status'] == 'digunakan'): ?>
                  <span class="text-sm text-gray-500">✅ Sudah digunakan</span>
                <?php else: ?>
                  <span class="text-sm text-gray-400 italic">Belum lunas</span>
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