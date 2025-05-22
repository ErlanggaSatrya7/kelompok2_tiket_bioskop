<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('admin');

$success = '';
$error = '';
$search = trim($_GET['search'] ?? '');
$where = $search ? "WHERE (nama_lengkap LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%') AND role != 'superadmin'" : "WHERE role != 'superadmin'";

// Hapus user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id_user = (int) $_POST['id_user'];
    $user = $conn->query("SELECT * FROM users WHERE id_user = $id_user")->fetch_assoc();
    if ($user) {
        $conn->query("DELETE FROM users WHERE id_user = $id_user");
        $log = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'DELETE', ?)");
        $desc = "Menghapus user: {$user['username']}";
        $log->bind_param("is", $_SESSION['id_user'], $desc);
        $log->execute();
        $_SESSION['success'] = "User berhasil dihapus.";
        header("Location: data_user.php");
        exit;
    }
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

$result = $conn->query("SELECT id_user, nama_lengkap, username, email, role FROM users $where ORDER BY id_user DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pengguna | JATIX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex min-h-screen">

<!-- Sidebar -->
<aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
    <h2 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2"><i data-lucide="users"></i> JATIX Admin</h2>
    <nav class="space-y-2 text-sm">
        <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="layout-dashboard"></i> Dashboard</a>
        <a href="bioskop.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="building"></i> Bioskop</a>
        <a href="film.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="video"></i> Film</a>
        <!-- <a href="data_user.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="users"></i> Pengguna</a> -->
        <!-- <a href="data_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="ticket"></i> Tiket</a> -->
        <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="file-text"></i> Laporan</a>
        <a href="log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="history"></i> Log Aktivitas</a>
        <!-- <a href="audit_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="scan-line"></i> Audit Tiket</a> -->
        <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
    </nav>
</aside>

<!-- Konten -->
<main class="ml-64 p-8 w-full">
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="users"></i> Data Pengguna</h1>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-3 mb-4 rounded flex items-center gap-2">
            <i data-lucide="check-circle"></i> <?= $success ?>
        </div>
    <?php elseif ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 mb-4 rounded flex items-center gap-2">
            <i data-lucide="alert-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="flex justify-between items-center mb-4">
        <form method="GET" class="max-w-md flex gap-2">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, username, email..." class="w-full px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 flex items-center gap-1"><i data-lucide="search"></i></button>
        </form>
        <div class="flex gap-2">
            <a href="tambah_user.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center gap-2"><i data-lucide="user-plus"></i> Tambah</a>
            <a href="../admin/export/excel/export_user_excel.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-2"><i data-lucide="file-spreadsheet"></i> Excel</a>
            <a href="../admin/export/pdf/export_user_pdf.php" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 flex items-center gap-2" target="_blank"><i data-lucide="file-text"></i> PDF</a>
        </div>
    </div>

    <!-- Tabel Pengguna -->
    <div class="bg-white rounded shadow p-6 overflow-x-auto">
        <table class="min-w-full text-sm border">
            <thead>
                <tr class="bg-purple-100 text-purple-800">
                    <th class="p-3 border">ID</th>
                    <th class="p-3 border">Nama Lengkap</th>
                    <th class="p-3 border">Username</th>
                    <th class="p-3 border">Email</th>
                    <th class="p-3 border">Role</th>
                    <th class="p-3 border">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                    <td class="p-3 border"><?= $row['id_user'] ?></td>
                    <td class="p-3 border"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                    <td class="p-3 border"><?= htmlspecialchars($row['username']) ?></td>
                    <td class="p-3 border"><?= htmlspecialchars($row['email']) ?></td>
                    <td class="p-3 border capitalize"><?= htmlspecialchars($row['role']) ?></td>
                    <td class="p-3 border flex gap-2 justify-center">
                        <a href="edit_user.php?id=<?= $row['id_user'] ?>" onclick="return confirm('Edit user ini?')" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 flex items-center justify-center">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </a>
                        <form method="post" onsubmit="return confirm('Yakin hapus user ini?')">
                            <input type="hidden" name="id_user" value="<?= $row['id_user'] ?>">
                            <button type="submit" name="hapus" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 flex items-center justify-center">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
