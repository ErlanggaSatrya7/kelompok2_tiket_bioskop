<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');

$id_user = $_SESSION['id_user'];

// Ambil wishlist
$wishlist = $conn->query("
  SELECT w.*, f.judul_film, f.genre, f.durasi, f.poster 
  FROM wishlist w 
  JOIN film f ON w.id_film = f.id_film 
  WHERE w.id_user = $id_user 
  ORDER BY w.created_at DESC
");

// Foto profil user
$user = $conn->query("SELECT foto_profil FROM users WHERE id_user = $id_user")->fetch_assoc();
$foto = !empty($user['foto_profil']) && file_exists("../assets/img/profil/" . $user['foto_profil'])
  ? "../assets/img/profil/" . $user['foto_profil']
  : "../assets/foto/default.png";
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Wishlist | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<!-- ✅ Navbar -->
<header class="bg-white shadow sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
    <h1 class="text-xl font-bold text-purple-700">🎟 JATIX</h1>
    <nav class="flex items-center gap-4 text-sm">
      <?php $current = basename($_SERVER['PHP_SELF']); ?>
      <a href="beranda.php" class="<?= $current == 'beranda.php' ? 'text-purple-700 font-semibold' : 'hover:text-purple-600' ?>">Beranda</a>
      <a href="film.php" class="<?= $current == 'film.php' ? 'text-purple-700 font-semibold' : 'hover:text-purple-600' ?>">Film</a>
      <a href="tiket_saya.php" class="<?= $current == 'tiket_saya.php' ? 'text-purple-700 font-semibold' : 'hover:text-purple-600' ?>">Tiket Saya</a>
      <a href="wishlist.php" class="<?= $current == 'wishlist.php' ? 'text-purple-700 font-semibold' : 'hover:text-purple-600' ?>">Wishlist</a>
      <a href="profile.php" class="<?= $current == 'profile.php' ? 'text-purple-700 font-semibold' : 'hover:text-purple-600' ?>">Profil</a>
      <a href="logout.php" class="text-red-500 hover:underline">Logout</a>
      <a href="profile.php" title="Lihat Profil">
        <img src="<?= $foto ?>" class="w-8 h-8 rounded-full object-cover border ml-2 hover:ring hover:ring-purple-300">
      </a>
    </nav>
  </div>
</header>

<!-- ✅ Konten Wishlist -->
<main class="max-w-6xl mx-auto pt-24 px-6 pb-10">
  <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="heart"></i> Wishlist Saya</h1>

  <?php if ($wishlist->num_rows === 0): ?>
    <p class="text-center text-gray-500">Belum ada film di wishlist.</p>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
      <?php while ($f = $wishlist->fetch_assoc()): ?>
        <div class="bg-white shadow rounded overflow-hidden">
          <img src="../assets/img/<?= $f['poster'] ?>" class="w-full h-[300px] object-cover">
          <div class="p-4 space-y-1">
            <h3 class="text-lg font-semibold text-purple-700 truncate"><?= htmlspecialchars($f['judul_film']) ?></h3>
            <p class="text-sm text-gray-500"><?= $f['genre'] ?> • <?= $f['durasi'] ?> menit</p>
            <div class="flex justify-between pt-3">
              <a href="detail_film.php?id=<?= $f['id_film'] ?>" class="text-sm text-purple-600 hover:underline">Lihat Detail</a>
              <a href="tambah_wishlist.php?id=<?= $f['id_film'] ?>&act=hapus" class="text-sm text-red-600 hover:underline">Hapus</a>
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
