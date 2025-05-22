<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');

$id_user = $_SESSION['id_user'] ?? null;
$nama_user = $_SESSION['nama_lengkap'] ?? 'Guest';

// Ambil data film + rating + wishlist
$film = $conn->query("
  SELECT f.*, 
    (SELECT ROUND(AVG(r.rating),1) FROM rating r WHERE r.id_film = f.id_film) AS avg_rating,
    EXISTS(SELECT 1 FROM wishlist w WHERE w.id_user = $id_user AND w.id_film = f.id_film) AS wishlisted
  FROM film f 
  ORDER BY tanggal_tayang DESC 
  LIMIT 10
");

// Foto profil user
$user = $conn->query("SELECT foto_profil FROM users WHERE id_user = $id_user")->fetch_assoc();
$foto_user = !empty($user['foto_profil']) ? "../assets/img/profil/" . $user['foto_profil'] : "../assets/foto/default.png";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Beranda | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    .carousel { display: flex; overflow-x: auto; scroll-behavior: smooth; gap: 1rem; }
    .carousel::-webkit-scrollbar { display: none; }
    .film-card:hover { transform: translateY(-4px); transition: transform 0.2s ease-in-out; }
  </style>
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
        <img src="<?= $foto_user ?>" class="w-8 h-8 rounded-full object-cover border ml-2 hover:ring-2 hover:ring-purple-400 transition" alt="Foto Profil">
      </a>
    </nav>
  </div>
</header>

<!-- ✅ Hero Section -->
<section class="bg-gradient-to-r from-purple-700 to-purple-900 text-white py-16 text-center">
  <div class="max-w-3xl mx-auto px-4">
    <h2 class="text-4xl font-extrabold mb-3 drop-shadow">Selamat Datang, <?= htmlspecialchars($nama_user) ?>!</h2>
    <p class="text-lg text-purple-100">Temukan film favoritmu dan pesan tiket secara online hanya di JATIX.</p>
  </div>
</section>

<!-- ✅ Slider Film -->
<section class="max-w-7xl mx-auto px-4 mt-12 relative">
  <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
    <i data-lucide="clapperboard"></i> Film Terbaru
  </h3>
  <div class="relative">
    <button onclick="scrollCarousel(-1)" class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-white p-2 rounded-full shadow z-10 hover:bg-purple-100">
      <i data-lucide="chevron-left"></i>
    </button>
    <div id="carousel" class="carousel pb-2 px-4">
      <?php while($f = $film->fetch_assoc()): ?>
        <div class="bg-white rounded-lg shadow film-card min-w-[180px] max-w-[180px] overflow-hidden">
          <a href="detail_film.php?id=<?= $f['id_film'] ?>">
            <img src="../assets/img/<?= $f['poster'] ?>" alt="Poster" class="w-full aspect-[2/3] object-cover">
            <div class="p-2">
              <h4 class="text-sm font-semibold text-purple-700 truncate"><?= htmlspecialchars($f['judul_film']) ?></h4>
              <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($f['genre']) ?></p>
              <div class="flex items-center justify-between mt-1">
                <span class="text-yellow-500 text-xs"><?= $f['avg_rating'] ? "⭐ {$f['avg_rating']}/5" : "Belum ada rating" ?></span>
                <i data-lucide="heart" class="w-4 h-4 <?= $f['wishlisted'] ? 'text-pink-500 fill-pink-500' : 'text-gray-300' ?>"></i>
              </div>
            </div>
          </a>
        </div>
      <?php endwhile; ?>
    </div>
    <button onclick="scrollCarousel(1)" class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-white p-2 rounded-full shadow z-10 hover:bg-purple-100">
      <i data-lucide="chevron-right"></i>
    </button>
  </div>
</section>

<!-- ✅ CTA -->
<section class="text-center mt-16 py-12 bg-white border-t border-b">
  <h4 class="text-2xl font-bold text-purple-700 mb-2">Siap Nonton?</h4>
  <p class="text-gray-600 mb-4">Pilih film favoritmu dan pesan tiket dengan mudah hanya di JATIX.</p>
  <a href="film.php" class="inline-block bg-purple-600 text-white px-6 py-2 rounded shadow hover:bg-purple-700">🎬 Lihat Semua Film</a>
</section>

<!-- ✅ Footer -->
<footer class="text-center text-xs text-gray-500 py-6">
  &copy; <?= date('Y') ?> JATIX. All rights reserved.
</footer>

<script>
  lucide.createIcons();
  function scrollCarousel(direction) {
    const container = document.getElementById('carousel');
    container.scrollBy({ left: direction * 220, behavior: 'smooth' });
  }
</script>
</body>
</html>
