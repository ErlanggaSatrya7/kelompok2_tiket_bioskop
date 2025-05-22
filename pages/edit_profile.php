<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');

$id_user = $_SESSION['id_user'];
$user = $conn->query("SELECT * FROM users WHERE id_user = $id_user")->fetch_assoc();

$foto = (!empty($user['foto_profil']) && file_exists("../assets/img/profil/" . $user['foto_profil']))
    ? "../assets/img/profil/" . $user['foto_profil']
    : "../assets/foto/default.png";

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = htmlspecialchars(trim($_POST['nama_lengkap']));
    $no_hp = htmlspecialchars(trim($_POST['no_hp']));

    // Cek dan proses upload foto baru jika ada
    if (!empty($_FILES['foto']['name'])) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nama_file = 'profil_' . time() . '.' . $ext;
        $lokasi = "../assets/img/profil/" . $nama_file;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $lokasi)) {
            // Hapus foto lama
            if (!empty($user['foto_profil']) && file_exists("../assets/img/profil/" . $user['foto_profil'])) {
                unlink("../assets/img/profil/" . $user['foto_profil']);
            }
            // Simpan dengan foto baru
            $conn->query("UPDATE users SET nama_lengkap = '$nama', no_hp = '$no_hp', foto_profil = '$nama_file' WHERE id_user = $id_user");
        }
    } else {
        // Simpan tanpa ganti foto
        $conn->query("UPDATE users SET nama_lengkap = '$nama', no_hp = '$no_hp' WHERE id_user = $id_user");
    }

    $_SESSION['nama_lengkap'] = $nama;
    header("Location: profile.php?msg=success");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Profil | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<!-- ✅ Navbar -->
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

<!-- ✅ Form Edit -->
<main class="max-w-2xl mx-auto mt-16 px-6 pb-10">
  <h1 class="text-2xl font-bold text-purple-700 mb-6 text-center">Edit Profil</h1>

  <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-xl shadow-lg space-y-6">
    <div class="text-center">
      <img src="<?= $foto ?>" class="w-28 h-28 mx-auto rounded-full object-cover border shadow mb-2" alt="Foto Profil">
      <label class="block text-sm font-medium text-gray-700">Ubah Foto Profil</label>
      <input type="file" name="foto" accept="image/*" class="mt-1 block w-full text-sm text-gray-600">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
      <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required
             class="mt-1 w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-purple-600">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">No. HP</label>
      <input type="text" name="no_hp" value="<?= htmlspecialchars($user['no_hp']) ?>" required
             class="mt-1 w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-purple-600">
    </div>

    <div class="flex justify-end gap-3">
      <a href="profile.php" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-sm">← Kembali</a>
      <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded font-semibold">
        💾 Simpan Perubahan
      </button>
    </div>
  </form>
</main>

</body>
</html>
