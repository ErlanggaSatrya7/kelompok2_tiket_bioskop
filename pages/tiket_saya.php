<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');

$id_user = $_SESSION['id_user'];

// Ambil tiket lengkap
$tiket = $conn->query("
  SELECT t.*, f.judul_film, f.poster, j.waktu_tayang, j.harga, 
         s.nama_studio, b.nama_bioskop 
  FROM tiket t
  JOIN film f ON t.id_film = f.id_film
  JOIN jadwal j ON t.id_jadwal = j.id_jadwal
  JOIN studio s ON j.id_studio = s.id_studio
  JOIN bioskop b ON s.id_bioskop = b.id_bioskop
  WHERE t.id_user = $id_user
  ORDER BY j.waktu_tayang DESC
");

$user = $conn->query("SELECT foto_profil FROM users WHERE id_user = $id_user")->fetch_assoc();
$foto = !empty($user['foto_profil']) && file_exists("../assets/img/profil/" . $user['foto_profil']) 
  ? "../assets/img/profil/" . $user['foto_profil'] 
  : "../assets/foto/default.png";
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tiket Saya | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<!-- Navbar -->
<header class="bg-white shadow sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
    <h1 class="text-xl font-bold text-purple-700">🎟 JATIX</h1>
    <nav class="flex items-center gap-4 text-sm">
      <a href="beranda.php">Beranda</a>
      <a href="film.php">Film</a>
      <a href="tiket_saya.php" class="text-purple-700 font-semibold">Tiket Saya</a>
      <a href="wishlist.php">Wishlist</a>
      <a href="profile.php">Profil</a>
      <a href="logout.php" class="text-red-500 hover:underline">Logout</a>
      <a href="profile.php"><img src="<?= $foto ?>" class="w-8 h-8 rounded-full border" alt="Profil"></a>
    </nav>
  </div>
</header>

<!-- Konten Tiket -->
<main class="pt-24 px-6 pb-10 max-w-5xl mx-auto">
  <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">
    <i data-lucide="ticket-check"></i> Tiket Saya
  </h1>

  <?php if ($tiket->num_rows === 0): ?>
    <p class="text-center text-gray-500">Belum ada tiket dipesan.</p>
  <?php else: ?>
    <div class="grid grid-cols-1 gap-4">
      <?php while ($t = $tiket->fetch_assoc()): ?>
        <?php
          $status_class = match($t['status']) {
            'digunakan'     => 'bg-green-100 text-green-700',
            'kedaluwarsa'   => 'bg-red-100 text-red-700',
            'dibatalkan'    => 'bg-gray-100 text-gray-600',
            'dibayar', 
            'menunggu'      => 'bg-yellow-100 text-yellow-700',
            default         => 'bg-gray-100 text-gray-600',
          };

          $cek_rating = $conn->query("SELECT 1 FROM rating WHERE id_user = $id_user AND id_film = {$t['id_film']}")->num_rows;
        ?>
        <div class="bg-white rounded shadow p-4 flex gap-4 items-start">
          <img src="../assets/img/<?= $t['poster'] ?>" class="w-20 rounded" alt="Poster">
          <div class="flex-1">
            <h2 class="font-semibold text-lg text-purple-700"><?= htmlspecialchars($t['judul_film']) ?></h2>
            <p class="text-sm text-gray-600"><?= $t['nama_bioskop'] ?> - Studio <?= $t['nama_studio'] ?></p>
            <p class="text-sm">Waktu: <?= date('d M Y H:i', strtotime($t['waktu_tayang'])) ?></p>
            <p class="text-sm">Kursi: <strong><?= $t['nomor_kursi'] ?></strong> | Harga: Rp<?= number_format($t['harga'], 0, ',', '.') ?></p>
            <p class="text-sm mt-1">Status:
              <span class="px-2 py-1 rounded text-xs font-medium <?= $status_class ?>">
                <?= strtoupper($t['status']) ?>
              </span>
            </p>

            <!-- Aksi -->
            <div class="mt-3 flex gap-2 flex-wrap text-sm">
              <?php if ($t['status'] === 'digunakan' && !$cek_rating): ?>
                <a href="rating.php?id=<?= $t['id_film'] ?>" class="text-yellow-600 hover:underline">⭐ Beri Rating</a>
              <?php elseif ($cek_rating): ?>
                <span class="text-purple-600">✅ Sudah Dinilai</span>
              <?php endif; ?>

              <?php if (in_array($t['status'], ['menunggu', 'dibayar'])): ?>
                <a href="batalkan_tiket.php?id=<?= $t['id_tiket'] ?>" class="text-red-600 hover:underline">Batalkan</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
