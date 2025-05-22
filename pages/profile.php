<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');

$id_user = $_SESSION['id_user'];
$user = $conn->query("SELECT * FROM users WHERE id_user = $id_user")->fetch_assoc();
$foto = (!empty($user['foto_profil']) && file_exists("../assets/img/profil/" . $user['foto_profil']))
    ? "../assets/img/profil/" . $user['foto_profil']
    : "../assets/foto/default.png";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Profil Saya | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-white shadow sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
    <h1 class="text-xl font-bold text-purple-700">🎟 JATIX</h1>
    <nav class="flex items-center gap-4 text-sm">
      <a href="beranda.php" class="hover:text-purple-600">Beranda</a>
      <a href="film.php" class="hover:text-purple-600">Film</a>
      <a href="tiket_saya.php" class="hover:text-purple-600">Tiket Saya</a>
      <a href="wishlist.php" class="hover:text-purple-600">Wishlist</a>
      <a href="profile.php" class="text-purple-700 font-semibold">Profil</a>
      <a href="logout.php" class="text-red-500 hover:underline">Logout</a>
    </nav>
  </div>
</header>

<main class="max-w-4xl mx-auto mt-16 px-6 pb-10">
  <div class="bg-white rounded-xl shadow-lg p-8 flex flex-col md:flex-row gap-8">
    <div class="md:w-1/3 text-center">
      <img src="<?= $foto ?>" alt="Foto Profil" class="w-32 h-32 mx-auto rounded-full object-cover border shadow">
      <h2 class="mt-4 text-xl font-bold text-purple-700"><?= htmlspecialchars($user['nama_lengkap']) ?></h2>
    </div>
    <div class="md:w-2/3 space-y-4">
      <div>
        <label class="block text-sm font-semibold text-gray-600">Username</label>
        <div class="text-base text-gray-800"><?= htmlspecialchars($user['username']) ?></div>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-600">Nama Lengkap</label>
        <div class="text-base text-gray-800"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-600">Email</label>
        <div class="text-base text-gray-800"><?= htmlspecialchars($user['email']) ?></div>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-600">No. HP</label>
        <div class="text-base text-gray-800"><?= htmlspecialchars($user['no_hp']) ?></div>
      </div>
      <div class="pt-6 text-right">
        <a href="edit_profile.php" class="inline-block bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700 transition">
          ✏ Edit Profil
        </a>
      </div>
    </div>
  </div>
</main>

</body>
</html>
