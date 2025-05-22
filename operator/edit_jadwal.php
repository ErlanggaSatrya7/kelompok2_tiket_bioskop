<?php
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('operator');

$id_user = $_SESSION['id_user'];

// Ambil id bioskop operator
$stmt = $conn->prepare("SELECT id_bioskop FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$stmt->bind_result($id_bioskop);
$stmt->fetch();
$stmt->close();

// Ambil ID jadwal dari URL
$id_jadwal = intval($_GET['id'] ?? 0);

// Ambil data jadwal jika milik bioskop ini
$stmt = $conn->prepare("SELECT id_film, id_studio, waktu_tayang FROM jadwal 
                        WHERE id_jadwal = ? AND id_studio IN 
                        (SELECT id_studio FROM studio WHERE id_bioskop = ?)");
$stmt->bind_param("ii", $id_jadwal, $id_bioskop);
$stmt->execute();
$stmt->bind_result($id_film, $id_studio, $waktu_tayang);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "Jadwal tidak ditemukan.";
    header("Location: jadwal.php");
    exit;
}
$stmt->close();

$error = '';
$success = '';

// Jika form dikirim (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_film = intval($_POST['id_film'] ?? 0);
    $id_studio = intval($_POST['id_studio'] ?? 0);
    $waktu = $_POST['waktu_tayang'] ?? '';

    if ($id_film && $id_studio && $waktu) {
        $stmt = $conn->prepare("UPDATE jadwal SET id_film = ?, id_studio = ?, waktu_tayang = ? 
                                WHERE id_jadwal = ? AND id_studio IN 
                                (SELECT id_studio FROM studio WHERE id_bioskop = ?)");
        $stmt->bind_param("iisii", $id_film, $id_studio, $waktu, $id_jadwal, $id_bioskop);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Jadwal berhasil diperbarui.";
            header("Location: jadwal.php");
            exit;
        } else {
            $error = "Gagal memperbarui jadwal.";
        }
    } else {
        $error = "Semua field wajib diisi.";
    }
}

// Ambil daftar film dan studio
$film_result = $conn->query("SELECT id_film, judul_film FROM film ORDER BY judul_film ASC");
$studio_result = $conn->query("SELECT id_studio, nama_studio FROM studio WHERE id_bioskop = $id_bioskop");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Jadwal</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8 min-h-screen text-gray-800">
  <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">✏️ Edit Jadwal</h1>

    <?php if ($error): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <div>
        <label class="block text-sm mb-1">Film</label>
        <select name="id_film" required class="w-full p-2 border rounded">
          <?php while ($film = $film_result->fetch_assoc()): ?>
            <option value="<?= $film['id_film'] ?>" <?= $film['id_film'] == $id_film ? 'selected' : '' ?>>
              <?= htmlspecialchars($film['judul_film']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1">Studio</label>
        <select name="id_studio" required class="w-full p-2 border rounded">
          <?php while ($s = $studio_result->fetch_assoc()): ?>
            <option value="<?= $s['id_studio'] ?>" <?= $s['id_studio'] == $id_studio ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['nama_studio']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1">Waktu Tayang</label>
        <input type="datetime-local" name="waktu_tayang" value="<?= date('Y-m-d\TH:i', strtotime($waktu_tayang)) ?>" required class="w-full p-2 border rounded">
      </div>
      <div class="flex gap-4">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Simpan</button>
        <a href="jadwal.php" class="text-gray-600 hover:underline">Batal</a>
      </div>
    </form>
  </div>
</body>
</html>
