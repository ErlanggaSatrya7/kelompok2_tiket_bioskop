

<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');

$id_user = $_SESSION['id_user'] ?? null;
$user = $conn->query("SELECT foto_profil FROM users WHERE id_user = $id_user")->fetch_assoc();
$foto_user = !empty($user['foto_profil']) ? "../assets/img/profil/" . $user['foto_profil'] : "../assets/foto/default.png";

$film = $conn->query("SELECT *, (SELECT ROUND(AVG(r.rating),1) FROM rating r WHERE r.id_film = f.id_film) AS avg_rating FROM film f ORDER BY tanggal_tayang DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Film | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<!-- Navbar -->
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
      <a href="profile.php">
        <img src="<?= $foto_user ?>" class="w-8 h-8 rounded-full object-cover border ml-2 hover:ring-2 hover:ring-purple-400 transition" alt="Foto Profil">
      </a>
    </nav>
  </div>
</header>

<!-- Konten Film -->
<main class="max-w-6xl mx-auto px-4 pt-10 pb-20">
  <h1 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2">
    <i data-lucide="film"></i> Daftar Film
  </h1>

  <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
    <?php while($f = $film->fetch_assoc()): ?>
    <div class="bg-white rounded-lg shadow hover:shadow-md overflow-hidden">
      <a href="detail_film.php?id=<?= $f['id_film'] ?>">
        <img src="../assets/img/<?= $f['poster'] ?>" class="w-full aspect-[2/3] object-cover">
        <div class="p-3">
          <h3 class="text-sm font-semibold text-purple-700 truncate"><?= htmlspecialchars($f['judul_film']) ?></h3>
          <p class="text-xs text-gray-500 truncate"><?= $f['genre'] ?> • <?= $f['durasi'] ?> menit</p>
          <p class="text-xs text-yellow-500 mt-1">
            <?= $f['avg_rating'] ? "⭐ {$f['avg_rating']}/5" : "Belum ada rating" ?>
          </p>
        </div>
      </a>
    </div>
    <?php endwhile; ?>
  </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
