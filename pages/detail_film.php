<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');

$id_user = $_SESSION['id_user'];
$id_film = $_GET['id'] ?? null;

if (!$id_film) {
  echo "ID film tidak ditemukan.";
  exit;
}

// Ambil data film
$film = $conn->query("SELECT * FROM film WHERE id_film = $id_film")->fetch_assoc();
if (!$film) {
  echo "Film tidak tersedia.";
  exit;
}

// Ambil jadwal
// $jadwal = $conn->query("SELECT * FROM jadwal WHERE id_film = $id_film ORDER BY waktu_tayang ASC");
$jadwal = $conn->query("CALL sp_jadwal_film($id_film)");
$conn->next_result();

// Wishlist user
$wishlist = $conn->query("SELECT * FROM wishlist WHERE id_user = $id_user AND id_film = $id_film")->num_rows > 0;

// Rating
$rating_result = $conn->query("CALL sp_rating_film($id_film)");
$conn->next_result();
// $rating_result = $conn->query("
//   SELECT r.rating, r.ulasan, r.created_at, u.nama_lengkap 
//   FROM rating r 
//   JOIN users u ON r.id_user = u.id_user 
//   WHERE r.id_film = $id_film 
//   ORDER BY r.created_at DESC
// ");

// Foto profil user
$user = $conn->query("SELECT foto_profil FROM users WHERE id_user = $id_user")->fetch_assoc();
$foto_user = !empty($user['foto_profil']) && file_exists("../assets/img/profil/" . $user['foto_profil']) 
  ? "../assets/img/profil/" . $user['foto_profil']
  : "../assets/foto/default.png";
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($film['judul_film']) ?> | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    @keyframes fadeIn {
      0% { opacity: 0; transform: translateY(-10px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
      animation: fadeIn 0.4s ease-out;
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<!-- ✅ Navbar -->
<!-- <header class="bg-white shadow sticky top-0 z-50">
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
        <img src="<?= $foto_user ?>" class="w-8 h-8 rounded-full object-cover border ml-2 hover:ring hover:ring-purple-300">
      </a>
    </nav>
  </div>
</header> -->

<!-- ✅ Notifikasi -->
<?php if (isset($_GET['msg'])): ?>
  <div id="alert-box" class="fixed top-6 left-1/2 transform -translate-x-1/2 bg-green-100 border border-green-300 text-green-700 px-4 py-2 rounded shadow flex items-center gap-2 animate-fadeIn">
    <i data-lucide="check-circle" class="w-5 h-5"></i>
    <span class="text-sm">
      <?= $_GET['msg'] === 'WishlistDitambahkan' ? 'Ditambahkan ke wishlist.' :
         ($_GET['msg'] === 'WishlistDihapus' ? 'Dihapus dari wishlist.' :
         ($_GET['msg'] === 'RatingBerhasil' ? 'Rating berhasil disimpan.' : '')) ?>
    </span>
  </div>
  <script>
    setTimeout(() => document.getElementById('alert-box')?.remove(), 3000);
  </script>
<?php endif; ?>

<!-- ✅ Tombol Kembali -->
<div class="flex justify-between items-center px-6 py-4">
  <a href="film.php" class="text-sm text-purple-600 hover:underline flex items-center gap-1">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke daftar film
  </a>
</div>

<!-- ✅ Konten Film -->
<main class="px-6 pb-10 max-w-6xl mx-auto">
  <div class="bg-white p-6 rounded-xl shadow-md flex flex-col md:flex-row gap-8">
    <img src="../assets/img/<?= $film['poster'] ?>" alt="Poster" class="w-full md:w-1/3 aspect-[2/3] object-cover rounded">
    <div class="flex-1">
      <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($film['judul_film']) ?></h1>
      <p class="text-sm text-gray-500 mb-1"><?= $film['genre'] ?> • <?= $film['durasi'] ?> menit</p>
      <p class="text-gray-700 mb-4 text-justify"><?= nl2br(htmlspecialchars($film['deskripsi'])) ?></p>

      <div class="flex gap-3 flex-wrap mb-6">
        <a href="beli_tiket.php?id=<?= $film['id_film'] ?>" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 text-sm">🎟 Pesan Tiket</a>
        <?php if ($wishlist): ?>
          <a href="tambah_wishlist.php?id=<?= $film['id_film'] ?>&act=hapus" class="bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-400">💔 Hapus Wishlist</a>
        <?php else: ?>
          <a href="tambah_wishlist.php?id=<?= $film['id_film'] ?>&act=tambah" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">❤️ Tambah Wishlist</a>
        <?php endif; ?>
      </div>

      <!-- ✅ Jadwal Tayang -->
      <h2 class="text-xl font-semibold mb-2">Jadwal Tayang</h2>
      <ul class="space-y-2">
        <?php if ($jadwal->num_rows === 0): ?>
          <li class="text-sm text-gray-500">Belum ada jadwal tayang.</li>
        <?php else: ?>
          <?php while ($j = $jadwal->fetch_assoc()): ?>
            <li class="bg-gray-50 p-3 rounded border text-sm text-gray-700">
              <?= date('d M Y H:i', strtotime($j['waktu_tayang'])) ?> — Rp<?= number_format($j['harga'], 0, ',', '.') ?>
            </li>
          <?php endwhile; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- ✅ Review -->
  <section class="mt-10">
    <h2 class="text-xl font-semibold mb-4">Ulasan & Rating</h2>
    <?php if ($rating_result->num_rows === 0): ?>
      <p class="text-sm text-gray-500">Belum ada ulasan.</p>
    <?php else: ?>
      <div class="space-y-4">
        <?php while ($r = $rating_result->fetch_assoc()): ?>
          <div class="bg-white p-4 rounded-lg shadow-sm border">
            <div class="flex justify-between items-center mb-1">
              <strong class="text-purple-700"><?= htmlspecialchars($r['nama_lengkap']) ?></strong>
              <span class="text-yellow-500 text-sm"><?= str_repeat('⭐', $r['rating']) ?> (<?= $r['rating'] ?>/5)</span>
            </div>
            <?php if (!empty(trim($r['ulasan']))): ?>
              <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($r['ulasan'])) ?></p>
            <?php endif; ?>
            <p class="text-xs text-gray-400 mt-1"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></p>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
