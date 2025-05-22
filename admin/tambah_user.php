<?php
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('admin');

$error = '';
$success = '';
$nama = $username = $email = $password = $role = '';
$id_bioskop = null;

$bioskop_list = $conn->query("SELECT id_bioskop, nama_bioskop FROM bioskop ORDER BY nama_bioskop ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? '';
    $id_bioskop = $_POST['id_bioskop'] ?? null;

    if (!$nama || !$username || !$email || !$password || !$role) {
        $error = 'Semua field wajib diisi.';
    } elseif (!in_array($role, ['admin', 'operator'])) {
        $error = 'Role tidak valid.';
    } elseif ($role === 'operator' && !$id_bioskop) {
        $error = 'Operator wajib memilih bioskop.';
    } else {
        $password_plain = $password; // tanpa hash

        $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, username, email, password, role, id_bioskop) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $nama, $username, $email, $password_plain, $role, $id_bioskop);

        if ($stmt->execute()) {
            $log = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'INSERT', ?)");
            $desc = "Menambahkan user $username dengan role $role";
            $log->bind_param("is", $_SESSION['id_user'], $desc);
            $log->execute();
            $_SESSION['success'] = "User berhasil ditambahkan.";
            header("Location: data_user.php");
            exit;
        } else {
            $error = 'Gagal menambahkan user.';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah User | JATIX Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-6">Tambah User Baru</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= $error ?></div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="text" name="nama" placeholder="Nama Lengkap" value="<?= htmlspecialchars($nama) ?>" class="w-full p-2 border rounded" required>
            <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($username) ?>" class="w-full p-2 border rounded" required>
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($email) ?>" class="w-full p-2 border rounded" required>
            <input type="password" name="password" placeholder="Password" class="w-full p-2 border rounded" required>

            <select name="role" onchange="toggleBioskop()" class="w-full p-2 border rounded" required>
                <option value="">Pilih Role</option>
                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="operator" <?= $role === 'operator' ? 'selected' : '' ?>>Operator</option>
            </select>

            <div id="bioskop-select" style="display: <?= $role === 'operator' ? 'block' : 'none' ?>;">
                <select name="id_bioskop" class="w-full p-2 border rounded mt-2">
                    <option value="">-- Pilih Bioskop --</option>
                    <?php while ($b = $bioskop_list->fetch_assoc()): ?>
                        <option value="<?= $b['id_bioskop'] ?>" <?= $id_bioskop == $b['id_bioskop'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['nama_bioskop']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tambah User</button>
            <a href="data_user.php" class="ml-4 text-sm text-gray-600 hover:underline">Kembali</a>
        </form>
    </div>

    <script>
        function toggleBioskop() {
            const role = document.querySelector('select[name="role"]').value;
            document.getElementById('bioskop-select').style.display = role === 'operator' ? 'block' : 'none';
        }
    </script>
</body>
</html>
