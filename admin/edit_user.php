<?php
session_start();
require_once('../config/koneksi.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$id_user = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_user) die("ID user tidak valid.");

$user = $conn->query("SELECT * FROM users WHERE id_user = $id_user")->fetch_assoc();
if (!$user) die("Pengguna tidak ditemukan.");

$errors = [];
$nama = $user['nama_lengkap'];
$username = $user['username'];
$email = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $new_nama = trim($_POST['nama_lengkap']);
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);

    // Validasi
    if ($new_nama === '') $errors['nama'] = 'Nama lengkap wajib diisi.';
    if ($new_username === '') $errors['username'] = 'Username wajib diisi.';
    if ($new_email === '' || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email tidak valid.';

    // Cek perubahan
    if (
        $new_nama === $user['nama_lengkap'] &&
        $new_username === $user['username'] &&
        $new_email === $user['email']
    ) {
        $errors['no_change'] = "Tidak ada perubahan data yang dilakukan.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, username = ?, email = ? WHERE id_user = ?");
        $stmt->bind_param("sssi", $new_nama, $new_username, $new_email, $id_user);
        $stmt->execute();

        $log = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'UPDATE', ?)");
        $desc = "Mengedit data user: $new_username";
        $log->bind_param("is", $_SESSION['id_user'], $desc);
        $log->execute();

        $_SESSION['success'] = "Data pengguna berhasil diperbarui.";
        header("Location: data_user.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit User | JATIX Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen p-8">

<div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-6 text-purple-700 flex items-center gap-2">
        <i data-lucide="edit-3"></i> Edit Data Pengguna
    </h1>

    <?php if (isset($errors['no_change'])): ?>
        <div class="bg-yellow-100 text-yellow-700 p-3 mb-4 rounded flex items-center gap-2">
            <i data-lucide="alert-triangle"></i> <?= $errors['no_change'] ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6" onsubmit="return confirm('Yakin simpan perubahan data pengguna?')">
        <div>
            <label class="block mb-1 font-semibold">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($nama) ?>"
                   class="w-full p-2 border rounded <?= isset($errors['nama']) ? 'border-red-500' : '' ?>">
            <?php if (isset($errors['nama'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= $errors['nama'] ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block mb-1 font-semibold">Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>"
                   class="w-full p-2 border rounded <?= isset($errors['username']) ? 'border-red-500' : '' ?>">
            <?php if (isset($errors['username'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= $errors['username'] ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block mb-1 font-semibold">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>"
                   class="w-full p-2 border rounded <?= isset($errors['email']) ? 'border-red-500' : '' ?>">
            <?php if (isset($errors['email'])): ?>
                <p class="text-red-500 text-sm mt-1"><?= $errors['email'] ?></p>
            <?php endif; ?>
        </div>

        <div class="flex justify-end gap-3">
            <a href="data_user.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400 flex items-center gap-2">
                <i data-lucide="x-circle"></i> Batal
            </a>
            <button type="submit" name="update"
                    class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700 flex items-center gap-2">
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
