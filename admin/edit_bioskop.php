<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) die("ID bioskop tidak valid.");

$stmt = $conn->prepare("SELECT * FROM bioskop WHERE id_bioskop = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data) die("Data bioskop tidak ditemukan.");

$nama_bioskop = $data['nama_bioskop'];
$lokasi = $data['lokasi'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $nama_baru = trim($_POST['nama_bioskop']);
    $lokasi_baru = trim($_POST['lokasi']);

    if ($nama_baru === '') $errors['nama_bioskop'] = 'Nama bioskop wajib diisi.';
    if ($lokasi_baru === '') $errors['lokasi'] = 'Lokasi wajib diisi.';

    $tidak_berubah = (
        $nama_baru === $data['nama_bioskop'] &&
        $lokasi_baru === $data['lokasi']
    );

    if ($tidak_berubah) {
        $errors['umum'] = 'Tidak ada perubahan yang dilakukan.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("CALL sp_update_bioskop(?, ?, ?)");
        $stmt->bind_param("iss", $id, $nama_baru, $lokasi_baru);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Bioskop berhasil diperbarui.';
            header("Location: bioskop.php");
            exit;
        } else {
            $errors['umum'] = 'Gagal menyimpan perubahan ke database.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Bioskop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen p-8">

<div class="max-w-xl mx-auto bg-white p-6 rounded shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-purple-700 flex items-center gap-2">
        <i data-lucide="edit-3"></i> Edit Bioskop
    </h1>

    <form method="POST" class="space-y-6" onsubmit="return confirm('Yakin ingin menyimpan perubahan bioskop?')">
        <?php if (isset($errors['umum'])): ?>
            <div class="bg-yellow-100 text-yellow-800 p-3 rounded">
                ⚠️ <?= $errors['umum'] ?>
            </div>
        <?php endif; ?>

        <div>
            <label class="block mb-1 font-semibold">Nama Bioskop</label>
            <input type="text" name="nama_bioskop" value="<?= htmlspecialchars($nama_bioskop) ?>"
                   class="w-full p-2 border rounded <?= isset($errors['nama_bioskop']) ? 'border-red-500' : '' ?>">
            <?php if (isset($errors['nama_bioskop'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= $errors['nama_bioskop'] ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block mb-1 font-semibold">Lokasi</label>
            <input type="text" name="lokasi" value="<?= htmlspecialchars($lokasi) ?>"
                   class="w-full p-2 border rounded <?= isset($errors['lokasi']) ? 'border-red-500' : '' ?>">
            <?php if (isset($errors['lokasi'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= $errors['lokasi'] ?></p>
            <?php endif; ?>
        </div>

        <div class="flex justify-end gap-3">
            <a href="bioskop.php" class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                <i data-lucide="x-circle"></i> Batal
            </a>
            <button type="submit" name="update"
                    class="flex items-center gap-2 bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700">
                <i data-lucide="save"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
