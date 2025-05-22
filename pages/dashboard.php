<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');

$id_user = $_SESSION['id_user'];

$tiket = $conn->query("SELECT COUNT(*) FROM tiket WHERE id_user = $id_user AND status = 'LUNAS'")->fetch_row()[0];
$wishlist = $conn->query("SELECT COUNT(*) FROM wishlist WHERE id_user = $id_user")->fetch_row()[0];
$rating = $conn->query("SELECT COUNT(*) FROM rating WHERE id_user = $id_user")->fetch_row()[0];

$user = $conn->query("SELECT nama_lengkap, username, email, foto_profil FROM users WHERE id_user = $id_user")->fetch_assoc();
$foto = (!empty($user['foto_profil']) && file_exists("../uploads/foto_user/" . $user['foto_profil']))
        ? "../uploads/foto_user/" . $user['foto_profil']
        : "../assets/img/default.png";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Profil Saya | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <!-- Navbar sama seperti film.php -->
  <nav class="bg-white shadow-md fixed top-0 left-0 right-0 z-10 px-6 py-3">
    <div class="flex flex-wrap items-center justify-between max-w-7xl mx-auto">
      <div class="flex items-center gap-2 text-purple-700 font-bold text-xl">
        <i data-lucide="ticket" class="w-6 h-6"></i><span>JATIX</span>
      </div>
      <div class="flex items-center gap-6 text-sm">
        <a href="film.php" class="text-gray-600 hover:text-purple-600">Film</a>
        <a href="tiket_saya.php" class="text-gray-600 hover:text-purple-600">Tiket Saya</a>
        <a href="wishlist.php" class="text-gray-600 hover:text-purple-600">Wishlist</a>
        <a href="dashboard.php" class="text-purple-700 font-semibold">Profil</a>
        <a href="logout.php" title="Logout" class="text-red-500 hover:text-red-700"><i data-lucide="log-out" class="w-5 h-5"></i></a>
        <a href="profil.php"><img src="<?= $foto ?>" class="w-8 h-8 rounded-full object-cover border" alt="Profil"></a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="pt-24 px-6 pb-10 max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="layout-dashboard"></i> Profil Saya</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
      <div class="bg-white p-5 rounded-lg shadow-md">
        <p class="text-sm text-gray-500 mb-1">Tiket Aktif</p>
        <p class="text-3xl font-bold text-purple-700"><?= $tiket ?></p>
      </div>
      <div class="bg-white p-5 rounded-lg shadow-md">
        <p class="text-sm text-gray-500 mb-1">Wishlist</p>
        <p class="text-3xl font-bold text-pink-600"><?= $wishlist ?></p>
      </div>
      <div class="bg-white p-5 rounded-lg shadow-md">
        <p class="text-sm text-gray-500 mb-1">Rating Diberikan</p>
        <p class="text-3xl font-bold text-yellow-500"><?= $rating ?></p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 flex items-center gap-6">
      <img src="<?= $foto ?>" class="w-24 h-24 object-cover rounded-full border">
      <div>
        <h2 class="text-lg font-semibold"><?= htmlspecialchars($user['nama_lengkap']) ?></h2>
        <p class="text-sm text-gray-600">@<?= htmlspecialchars($user['username']) ?></p>
        <p class="text-sm text-gray-600"><?= htmlspecialchars($user['email']) ?></p>
        <a href="edit_profil.php" class="inline-block mt-3 px-4 py-2 bg-purple-600 text-white text-sm rounded hover:bg-purple-700">Edit Profil</a>
      </div>
    </div>
  </main>

  <script>lucide.createIcons();</script>
</body>
</html>
