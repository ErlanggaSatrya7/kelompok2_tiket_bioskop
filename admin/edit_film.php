<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('../config/koneksi.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$id_film = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_film) die("ID film tidak valid.");

$stmt = $conn->prepare("SELECT * FROM film WHERE id_film = ?");
$stmt->bind_param("i", $id_film);
$stmt->execute();
$film = $stmt->get_result()->fetch_assoc();
if (!$film) die("Film tidak ditemukan.");

$errors = [];
$judul = $film['judul_film'];
$genre = $film['genre'];
$durasi = $film['durasi'];
$deskripsi = $film['deskripsi'];
$poster = $film['poster'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $judul = trim($_POST['judul']);
    $genre = trim($_POST['genre']);
    $durasi = (int) $_POST['durasi'];
    $deskripsi = trim($_POST['deskripsi']);

    // Validasi
    if ($judul === '' || is_numeric($judul)) $errors['judul'] = 'Judul harus berupa teks dan tidak boleh angka.';
    if ($genre === '' || is_numeric($genre)) $errors['genre'] = 'Genre harus berupa teks dan tidak boleh angka.';
    if ($durasi < 120) $errors['durasi'] = 'Durasi minimal 120 menit.';
    elseif ($durasi > 240) $errors['durasi'] = 'Durasi maksimal 240 menit.';
    if ($deskripsi === '') $errors['deskripsi'] = 'Deskripsi wajib diisi.';
    elseif (strlen($deskripsi) < 150 || strlen($deskripsi) > 250) $errors['deskripsi'] = 'Deskripsi minimal 150 dan maksimal 250 karakter.';

    $poster_diubah = isset($_FILES['poster']) && $_FILES['poster']['error'] === 0;
    $tidak_berubah = (
        $judul === $film['judul_film'] &&
        $genre === $film['genre'] &&
        $durasi === (int)$film['durasi'] &&
        $deskripsi === $film['deskripsi'] &&
        !$poster_diubah
    );

    if ($tidak_berubah) {
        $errors['umum'] = 'Tidak ada perubahan yang dilakukan.';
    }

    if ($poster_diubah) {
        $poster_file = $_FILES['poster'];
        $ext = strtolower(pathinfo($poster_file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed)) {
            $errors['poster'] = 'Format file harus jpg/jpeg/png.';
        } elseif ($poster_file['size'] > 2 * 1024 * 1024) {
            $errors['poster'] = 'Ukuran maksimal 2MB.';
        } else {
            $new_name = time() . '_' . preg_replace("/[^a-zA-Z0-9._-]/", "", basename($poster_file['name']));
            $upload_path = "../assets/img/" . $new_name;

            if (move_uploaded_file($poster_file['tmp_name'], $upload_path)) {
                $lama = "../assets/img/" . $poster;
                if (file_exists($lama)) unlink($lama);
                $poster = $new_name;
            } else {
                $errors['poster'] = 'Gagal mengunggah file.';
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE film SET judul_film = ?, genre = ?, durasi = ?, deskripsi = ?, poster = ? WHERE id_film = ?");
        $stmt->bind_param("ssissi", $judul, $genre, $durasi, $deskripsi, $poster, $id_film);
        
        $stmt->execute();

        $log_desc = "Mengedit film: $judul";
        $log_stmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'UPDATE', ?)");
        $log_stmt->bind_param("is", $_SESSION['id_user'], $log_desc);
        $log_stmt->execute();

        $_SESSION['success'] = 'Film berhasil diperbarui.';
        header("Location: film.php?update=success");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Film</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen p-8">

<div class="max-w-4xl mx-auto bg-white p-8 rounded shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-purple-700 flex items-center gap-2">
        <i data-lucide="edit-3"></i> Edit Film
    </h1>

    <form method="POST" enctype="multipart/form-data" class="space-y-6" id="editForm">
        <?php if (isset($errors['umum'])): ?>
            <div class="bg-yellow-100 text-yellow-800 p-3 rounded shadow">
                ⚠️ <?= $errors['umum'] ?>
            </div>
        <?php endif; ?>

        <div>
            <label class="block mb-1 font-semibold">Judul Film</label>
            <input type="text" name="judul" value="<?= htmlspecialchars($judul) ?>"
                   class="w-full p-2 border rounded <?= isset($errors['judul']) ? 'border-red-500' : '' ?>">
            <?php if (isset($errors['judul'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= $errors['judul'] ?></p>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block mb-1 font-semibold">Genre</label>
                <input type="text" name="genre" value="<?= htmlspecialchars($genre) ?>"
                       class="w-full p-2 border rounded <?= isset($errors['genre']) ? 'border-red-500' : '' ?>">
                <?php if (isset($errors['genre'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?= $errors['genre'] ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label class="block mb-1 font-semibold">Durasi (menit)</label>
                <input type="number" name="durasi" value="<?= htmlspecialchars($durasi) ?>" min="1" max="240"
                       class="w-full p-2 border rounded <?= isset($errors['durasi']) ? 'border-red-500' : '' ?>">
                <?php if (isset($errors['durasi'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?= $errors['durasi'] ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <label class="block mb-1 font-semibold">Deskripsi</label>
            <textarea name="deskripsi" class="w-full p-2 border rounded h-32 <?= isset($errors['deskripsi']) ? 'border-red-500' : '' ?>"><?= htmlspecialchars($deskripsi) ?></textarea>
            <?php if (isset($errors['deskripsi'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= $errors['deskripsi'] ?></p>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <div>
                <label class="block mb-1 font-semibold">Poster Saat Ini</label>
                <img id="posterPreview" src="../assets/img/<?= htmlspecialchars($poster) ?>" alt="Poster" class="rounded w-full max-w-xs">
            </div>
            <div>
                <label class="block mb-1 font-semibold">Ganti Poster (opsional)</label>
                <input type="file" name="poster" id="posterInput" accept="image/*"
                       class="w-full p-2 border rounded <?= isset($errors['poster']) ? 'border-red-500' : '' ?>">
                <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengganti poster.</p>
                <?php if (isset($errors['poster'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?= $errors['poster'] ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="film.php" class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
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

    const posterInput = document.getElementById('posterInput');
    const posterPreview = document.getElementById('posterPreview');

    posterInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                posterPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            posterPreview.src = posterPreview.src; // tetap pakai yang lama jika bukan gambar
        }
    });

    document.getElementById('editForm').addEventListener('submit', function (e) {
        const judul = document.querySelector('input[name="judul"]').value.trim();
        const genre = document.querySelector('input[name="genre"]').value.trim();
        const durasi = parseInt(document.querySelector('input[name="durasi"]').value.trim());
        const deskripsi = document.querySelector('textarea[name="deskripsi"]').value.trim();

        let error = '';

        if (!judul || !isNaN(judul)) error += 'Judul harus berupa teks dan tidak boleh angka.\n';
        if (!genre || !isNaN(genre)) error += 'Genre harus berupa teks dan tidak boleh angka.\n';
        if (isNaN(durasi) || durasi < 120 || durasi > 240) error += 'Durasi harus antara 120–240 menit.\n';
        if (deskripsi.length < 150 || deskripsi.length > 250) error += 'Deskripsi minimal 150 dan maksimal 250 karakter.\n';

        if (error) {
            alert('Validasi Gagal:\n' + error);
            e.preventDefault();
        } else {
            if (!confirm('Yakin ingin menyimpan perubahan?')) {
                e.preventDefault();
            }
        }
    });
</script>
</body>
</html>