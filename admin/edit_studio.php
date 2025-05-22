<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('../config/koneksi.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) die("ID studio tidak valid.");

$stmt = $conn->prepare("SELECT * FROM studio WHERE id_studio = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$studio = $stmt->get_result()->fetch_assoc();
if (!$studio) die("Studio tidak ditemukan.");

$nama_studio = $studio['nama_studio'];
$kapasitas = $studio['kapasitas'];
$id_bioskop = $studio['id_bioskop'];
$errors = [];

$bioskop = $conn->query("SELECT * FROM bioskop ORDER BY nama_bioskop");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $nama_baru = trim($_POST['nama_studio']);
    $kapasitas_baru = (int) $_POST['kapasitas'];
    $bioskop_baru = (int) $_POST['id_bioskop'];

    if ($nama_baru === '') $errors['nama_studio'] = 'Nama studio wajib diisi.';
    if ($kapasitas_baru <= 0) $errors['kapasitas'] = 'Kapasitas harus lebih dari 0.';
    if (!$bioskop_baru) $errors['id_bioskop'] = 'Pilih bioskop terlebih dahulu.';

    $tidak_berubah = (
        $nama_baru === $studio['nama_studio'] &&
        $kapasitas_baru === (int) $studio['kapasitas'] &&
        $bioskop_baru === (int) $studio['id_bioskop']
    );

    if ($tidak_berubah) {
        $errors['umum'] = 'Tidak ada perubahan yang dilakukan.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE studio SET nama_studio = ?, kapasitas = ?, id_bioskop = ? WHERE id_studio = ?");
        $stmt->bind_param("siii", $nama_baru, $kapasitas_baru, $bioskop_baru, $id);
        $stmt->execute();

        $desc = "Mengedit studio ID $id menjadi '$nama_baru', kapasitas $kapasitas_baru, bioskop ID $bioskop_baru";
        $log = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'UPDATE', ?)");
        $log->bind_param("is", $_SESSION['id_user'], $desc);
        $log->execute();

        $_SESSION['success'] = 'Studio berhasil diperbarui.';
        header("Location: studio.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen p-8">

<div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-6 text-purple-700 flex items-center gap-2">
        <i data-lucide="edit-3"></i> Edit Studio
    </h1>

    <form method="POST" class="space-y-6" onsubmit="return confirm('Yakin ingin menyimpan perubahan studio?')">
        <?php if (isset($errors['umum'])): ?>
            <div class="bg-yellow-100 text-yellow-800 p-3 rounded">
                ⚠️ <?= $errors['umum'] ?>
            </div>
        <?php endif; ?>

        <div>
            <label class="block font-semibold mb-1">Nama Studio</label>
            <input type="text" name="nama_studio" value="<?= htmlspecialchars($nama_studio) ?>"
                   class="w-full p-2 border rounded <?= isset($errors['nama_studio']) ? 'border-red-500' : '' ?>">
            <?php if (isset($errors['nama_studio'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= $errors['nama_studio'] ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block font-semibold mb-1">Kapasitas</label>
            <input type="number" name="kapasitas" value="<?= htmlspecialchars($kapasitas) ?>" min="1"
                   class="w-full p-2 border rounded <?= isset($errors['kapasitas']) ? 'border-red-500' : '' ?>">
            <?php if (isset($errors['kapasitas'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= $errors['kapasitas'] ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block font-semibold mb-1">Pilih Bioskop</label>
            <select name="id_bioskop" class="w-full p-2 border rounded <?= isset($errors['id_bioskop']) ? 'border-red-500' : '' ?>" required>
                <option value="">-- Pilih Bioskop --</option>
                <?php while ($b = $bioskop->fetch_assoc()): ?>
                    <option value="<?= $b['id_bioskop'] ?>" <?= $b['id_bioskop'] == $id_bioskop ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['nama_bioskop']) ?> (<?= htmlspecialchars($b['lokasi']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            <?php if (isset($errors['id_bioskop'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= $errors['id_bioskop'] ?></p>
            <?php endif; ?>
        </div>

        <div class="flex justify-end gap-3">
            <a href="studio.php" class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                <i data-lucide="x-circle"></i> Batal
            </a>
            <button type="submit" name="update" class="flex items-center gap-2 bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700">
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
