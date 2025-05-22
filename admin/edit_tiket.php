<?php
session_start();
require_once('../config/koneksi.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$id_tiket = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_tiket) die("ID tiket tidak valid.");

$tiket = $conn->query("
SELECT t.*, u.nama_lengkap, f.judul_film, j.waktu_tayang, s.nama_studio, b.nama_bioskop 
FROM tiket t
JOIN users u ON t.id_user = u.id_user
JOIN film f ON t.id_film = f.id_film
JOIN jadwal j ON t.jadwal_tayang = j.id_jadwal
JOIN studio s ON j.id_studio = s.id_studio
JOIN bioskop b ON s.id_bioskop = b.id_bioskop
WHERE t.id_tiket = $id_tiket
")->fetch_assoc();

if (!$tiket) die("Tiket tidak ditemukan.");

$errors = [];
$status = $tiket['status'];
$kursi = $tiket['nomor_kursi'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $new_status = $_POST['status'] ?? '';
    $new_kursi = trim($_POST['nomor_kursi'] ?? '');

    if (!in_array($new_status, ['menunggu', 'dibayar', 'digunakan', 'kedaluwarsa', 'dibatalkan'])) {
        $errors['status'] = "Status tidak valid.";
    }
    if ($new_kursi === '') {
        $errors['kursi'] = "Nomor kursi tidak boleh kosong.";
    }

    $no_change = ($new_status === $tiket['status'] && $new_kursi === $tiket['nomor_kursi']);
    if ($no_change) {
        $errors['no_change'] = "Tidak ada perubahan data yang dilakukan.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE tiket SET status = ?, nomor_kursi = ? WHERE id_tiket = ?");
        $stmt->bind_param("ssi", $new_status, $new_kursi, $id_tiket);
        $stmt->execute();

        $desc = "Edit tiket #$id_tiket: status $new_status, kursi $new_kursi";
        $log = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'UPDATE', ?)");
        $log->bind_param("is", $_SESSION['id_user'], $desc);
        $log->execute();

        $_SESSION['success'] = "Tiket berhasil diperbarui.";
        header("Location: data_tiket.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Tiket | JATIX Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen p-8">

<div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-6 text-purple-700 flex items-center gap-2">
        <i data-lucide="edit-3"></i> Edit Tiket #<?= $tiket['id_tiket'] ?>
    </h1>

    <?php if (isset($errors['no_change'])): ?>
        <div class="bg-yellow-100 text-yellow-800 p-3 mb-4 rounded">
            ⚠️ <?= $errors['no_change'] ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4" onsubmit="return confirm('Simpan perubahan tiket ini?')">
        <div>
            <label class="block font-semibold">User</label>
            <input type="text" value="<?= htmlspecialchars($tiket['nama_lengkap']) ?>" disabled class="w-full p-2 bg-gray-100 rounded">
        </div>

        <div>
            <label class="block font-semibold">Film</label>
            <input type="text" value="<?= htmlspecialchars($tiket['judul_film']) ?>" disabled class="w-full p-2 bg-gray-100 rounded">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold">Bioskop</label>
                <input type="text" value="<?= htmlspecialchars($tiket['nama_bioskop']) ?>" disabled class="w-full p-2 bg-gray-100 rounded">
            </div>
            <div>
                <label class="block font-semibold">Studio</label>
                <input type="text" value="<?= htmlspecialchars($tiket['nama_studio']) ?>" disabled class="w-full p-2 bg-gray-100 rounded">
            </div>
        </div>

        <div>
            <label class="block font-semibold">Waktu Tayang</label>
            <input type="text" value="<?= date('d M Y H:i', strtotime($tiket['waktu_tayang'])) ?>" disabled class="w-full p-2 bg-gray-100 rounded">
        </div>

        <div>
            <label class="block font-semibold">Nomor Kursi</label>
            <input type="text" name="nomor_kursi" value="<?= htmlspecialchars($kursi) ?>" class="w-full p-2 border rounded <?= isset($errors['kursi']) ? 'border-red-500' : '' ?>">
            <?php if (isset($errors['kursi'])): ?>
                <p class="text-red-500 text-sm"><?= $errors['kursi'] ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block font-semibold">Status</label>
            <select name="status" class="w-full p-2 border rounded <?= isset($errors['status']) ? 'border-red-500' : '' ?>">
                <?php
                $status_list = ['menunggu', 'dibayar', 'digunakan', 'kedaluwarsa', 'dibatalkan'];
                foreach ($status_list as $s):
                    $selected = $status === $s ? 'selected' : '';
                    $color = match($s) {
                        'dibayar' => 'bg-green-100 text-green-700',
                        'menunggu' => 'bg-yellow-100 text-yellow-700',
                        'digunakan' => 'bg-blue-100 text-blue-700',
                        'kedaluwarsa' => 'bg-gray-200 text-gray-700',
                        'dibatalkan' => 'bg-red-100 text-red-700',
                        default => ''
                    };
                ?>
                    <option value="<?= $s ?>" class="<?= $color ?>" <?= $selected ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['status'])): ?>
                <p class="text-red-500 text-sm"><?= $errors['status'] ?></p>
            <?php endif; ?>
        </div>

        <?php
        $qr_path = '../assets/qr/' . $tiket['kode_qr'] . '.png';
        if (file_exists($qr_path)):
        ?>
        <div class="text-center mt-4">
            <img src="<?= $qr_path ?>" alt="QR Code" class="w-40 h-40 mx-auto border p-1 bg-white">
            <p class="text-sm text-gray-600 mt-1"><?= $tiket['kode_qr'] ?></p>
        </div>
        <?php endif; ?>

        <div class="flex justify-end gap-3">
            <a href="data_tiket.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400 flex items-center gap-2">
                <i data-lucide="x-circle"></i> Batal
            </a>
            <button type="submit" name="update" class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700 flex items-center gap-2">
                <i data-lucide="save"></i> Simpan
            </button>
        </div>
    </form>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>
