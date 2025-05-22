<!-- partials/navbar.php -->
<?php
// partials/navbar.php
// session_start();
?>

<nav class="fixed top-0 left-0 right-0 bg-white shadow px-6 py-4 flex justify-between items-center z-50">
  <div class="text-2xl font-bold text-purple-700">JATIX</div>
  <div class="flex gap-4 items-center text-sm">
    <?php if (isset($_SESSION['id_user'])): ?>
      <a href="index.php" class="hover:text-purple-600">Beranda</a>
      <a href="tiket_saya.php" class="hover:text-purple-600">Tiket Saya</a>
      <a href="profil.php" class="hover:text-purple-600">Profil</a>
      <a href="logout.php" class="hover:text-purple-600">Keluar</a>
    <?php else: ?>
      <a href="login.php" class="px-4 py-2 border border-purple-600 text-purple-600 rounded hover:bg-purple-100">Masuk</a>
      <a href="register.php" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Daftar</a>
    <?php endif; ?>
  </div>
</nav>
<!-- spacer setinggi navbar -->
<div class="h-16"></div>
