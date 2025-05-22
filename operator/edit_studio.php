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

// Ambil data studio berdasarkan id & id_bioskop
$id_studio = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT nama_studio, kapasitas FROM studio WHERE id_studio = ? AND id_bioskop = ?");
$stmt->bind_param("ii", $id_studio, $id_bioskop);
$stmt->execute();
$stmt->bind_result($nama_studio, $kapasitas);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "Studio tidak ditemukan.";
    header("Location: studio.php");
    exit;
}
$stmt->close();

$error = '';
$success = '';

// Update jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_studio'] ?? '');
    $kapasitas = intval($_POST['kapasitas'] ?? 0);

    if (!$nama || $kapasitas <= 0) {
        $error = "Nama dan kapasitas wajib diisi dengan benar.";
    } else {
        $stmt = $conn->prepare("UPDATE studio SET nama_studio = ?, kapasitas = ? WHERE id_studio = ? AND id_bioskop = ?");
        $stmt->bind_param("siii", $nama, $kapasitas, $id_studio, $id_bioskop);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Studio berhasil diperbarui.";
            header("Location: studio.php");
            exit;
        } else {
            $error = "Gagal mengupdate studio.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Studio | Operator</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8 min-h-screen text-gray-800">
  <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">✏️ Edit Studio</h1>

    <?php if ($error): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Nama Studio</label>
        <input type="text" name="nama_studio" value="<?= htmlspecialchars($nama_studio) ?>" class="w-full p-2 border rounded" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Kapasitas</label>
        <input type="number" name="kapasitas" value="<?= $kapasitas ?>" class="w-full p-2 border rounded" required min="1">
      </div>
      <div class="flex gap-4">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Simpan Perubahan</button>
        <a href="studio.php" class="text-gray-600 hover:underline">Batal</a>
      </div>
    </form>
  </div>
</body>
</html>
