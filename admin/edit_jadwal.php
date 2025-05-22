<?php
session_start();
require_once('../config/koneksi.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$id_jadwal = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_jadwal) die("ID jadwal tidak valid.");

$stmt = $conn->prepare("
    SELECT j.*, f.judul_film, s.nama_studio, b.nama_bioskop 
    FROM jadwal j 
    JOIN film f ON j.id_film = f.id_film 
    JOIN studio s ON j.id_studio = s.id_studio 
    JOIN bioskop b ON s.id_bioskop = b.id_bioskop
    WHERE j.id_jadwal = ?
");
$stmt->bind_param("i", $id_jadwal);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) die("Data jadwal tidak ditemukan.");

$film = $conn->query("SELECT * FROM film ORDER BY judul_film");
$studio = $conn->query("SELECT s.*, b.nama_bioskop FROM studio s JOIN bioskop b ON s.id_bioskop = b.id_bioskop ORDER BY b.nama_bioskop, s.nama_studio");

$success = '';
$error = '';
$errors = [];

$tanggal = substr($data['waktu_tayang'], 0, 10);
$jam = substr($data['waktu_tayang'], 11, 5);
$id_film = $data['id_film'];
$id_studio = $data['id_studio'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id_film_new = (int) $_POST['id_film'];
    $id_studio_new = (int) $_POST['id_studio'];
    $tanggal_new = $_POST['tanggal'];
    $jam_new = $_POST['jam_mulai'];

    if (!$id_film_new || !$id_studio_new || !$tanggal_new || !$jam_new) {
        $error = "Semua field wajib diisi.";
    } else {
        $waktu_new = "$tanggal_new $jam_new:00";

        if (
            $id_film_new === $data['id_film'] &&
            $id_studio_new === $data['id_studio'] &&
            $waktu_new === $data['waktu_tayang']
        ) {
            $error = "Tidak ada perubahan yang dilakukan.";
        } else {
            $stmt = $conn->prepare("UPDATE jadwal SET id_film=?, id_studio=?, waktu_tayang=? WHERE id_jadwal=?");
            $stmt->bind_param("iisi", $id_film_new, $id_studio_new, $waktu_new, $id_jadwal);
            if ($stmt->execute()) {
                $desc = "Mengedit jadwal ID $id_jadwal";
                $log = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'UPDATE', ?)");
                $log->bind_param("is", $_SESSION['id_user'], $desc);
                $log->execute();

                $_SESSION['success'] = "Jadwal berhasil diperbarui.";
                header("Location: jadwal.php?update=success");
                exit;
            } else {
                $error = "Gagal memperbarui jadwal.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Jadwal Tayang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen p-6">

<div class="max-w-xl mx-auto bg-white p-8 rounded shadow">
    <h1 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2"><i data-lucide="edit-3"></i> Edit Jadwal Tayang</h1>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4 flex items-center gap-2">
            <i data-lucide="alert-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4" onsubmit="return confirm('Simpan perubahan jadwal ini?')">
        <div>
            <label class="block mb-1 font-semibold">Film</label>
            <select name="id_film" class="w-full p-2 border rounded" required>
                <option value="">-- Pilih Film --</option>
                <?php foreach ($film as $f): ?>
                    <option value="<?= $f['id_film'] ?>" <?= $id_film == $f['id_film'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['judul_film']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block mb-1 font-semibold">Studio</label>
            <select name="id_studio" class="w-full p-2 border rounded" required>
                <option value="">-- Pilih Studio --</option>
                <?php foreach ($studio as $s): ?>
                    <option value="<?= $s['id_studio'] ?>" <?= $id_studio == $s['id_studio'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['nama_bioskop']) ?> - <?= htmlspecialchars($s['nama_studio']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block mb-1 font-semibold">Tanggal Tayang</label>
            <input type="date" name="tanggal" class="w-full p-2 border rounded" value="<?= $tanggal ?>" required>
        </div>
        <div>
            <label class="block mb-1 font-semibold">Jam Mulai</label>
            <input type="time" name="jam_mulai" class="w-full p-2 border rounded" value="<?= $jam ?>" required>
        </div>

        <div class="flex justify-end gap-3">
            <a href="jadwal.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400 flex items-center gap-2">
                <i data-lucide="x-circle"></i> Batal
            </a>
            <button type="submit" name="update" class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700 flex items-center gap-2">
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
