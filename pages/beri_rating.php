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
$film = $conn->query("SELECT judul_film FROM film WHERE id_film = $id_film")->fetch_assoc();
if (!$film) {
    echo "Film tidak ditemukan.";
    exit;
}

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int) $_POST['rating'];
    $komentar = trim($_POST['komentar']);

    // Validasi nilai rating
    if ($rating < 1 || $rating > 5) {
        $error = "Nilai rating harus antara 1 hingga 5.";
    } else {
        // Cek apakah user sudah pernah beri rating
        $cek = $conn->query("SELECT * FROM rating WHERE id_user = $id_user AND id_film = $id_film");
        if ($cek->num_rows > 0) {
            $conn->query("UPDATE rating SET rating = $rating, ulasan = '$komentar', updated_at = NOW() WHERE id_user = $id_user AND id_film = $id_film");
        } else {
            $conn->query("INSERT INTO rating (id_user, id_film, rating, ulasan, created_at) VALUES ($id_user, $id_film, $rating, '$komentar', NOW())");
        }
        header("Location: detail_film.php?id=$id_film&msg=RatingBerhasil");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Beri Rating | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex items-center justify-center px-4">

  <div class="bg-white p-8 rounded shadow max-w-md w-full">
    <h1 class="text-2xl font-bold text-purple-700 mb-4">⭐ Beri Rating</h1>
    <p class="text-sm mb-3 text-gray-600">Film: <strong><?= htmlspecialchars($film['judul_film']) ?></strong></p>

    <?php if (isset($error)): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 mb-3 rounded"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1">Rating (1-5)</label>
        <input type="number" name="rating" min="1" max="5" required class="w-full border rounded p-2 focus:ring-2 focus:ring-purple-500">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Komentar (opsional)</label>
        <textarea name="komentar" rows="3" class="w-full border rounded p-2 focus:ring-2 focus:ring-purple-500"></textarea>
      </div>
      <div class="flex justify-end gap-3">
        <a href="detail_film.php?id=<?= $id_film ?>" class="text-sm text-gray-600 hover:underline">Batal</a>
        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Simpan</button>
      </div>
    </form>
  </div>

</body>
</html>
