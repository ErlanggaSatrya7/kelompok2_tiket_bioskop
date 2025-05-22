<?php
session_start();
require_once('../config/koneksi.php');

if (!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$search = trim($_GET['search'] ?? '');

$where = $search ? "WHERE u.nama_lengkap LIKE '%$search%' OR f.judul_film LIKE '%$search%'" : "";

$query = "
    SELECT 
        c.id_checkin,
        t.kode_qr,
        t.jadwal_tayang,
        u.nama_lengkap,
        f.judul_film,
        c.waktu_checkin
    FROM checkin_log c
    JOIN tiket t ON c.id_tiket = t.id_tiket
    JOIN users u ON t.id_user = u.id_user
    JOIN film f ON t.id_film = f.id_film
    $where
    ORDER BY c.waktu_checkin DESC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Audit Tiket | JATIX Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-100 text-gray-800 flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
        <h2 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2">
            <i data-lucide="scan-line"></i> JATIX Admin
        </h2>
        <nav class="space-y-2 text-sm">
            <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="bioskop.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200">
                <i data-lucide="building"></i> Bioskop
            </a>
            <a href="film.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200">
                <i data-lucide="video"></i> Film
            </a>
            <a href="data_user.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200">
                <i data-lucide="users"></i> Pengguna
            </a>
            <a href="data_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200">
                <i data-lucide="ticket"></i> Tiket
            </a>
            <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200">
                <i data-lucide="file-text"></i> Laporan
            </a>
            <a href="log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200">
                <i data-lucide="history"></i> Log Aktivitas
            </a>
            <a href="audit_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold">
                <i data-lucide="scan-line"></i> Audit Tiket
            </a>
            <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200">
                <i data-lucide="log-out"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 p-8 w-full">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">
            <i data-lucide="scan-line"></i> Audit Tiket (Check-In Log)
        </h1>

        <!-- Filter -->
        <form method="GET" class="max-w-md mb-4 flex gap-2">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari user atau film..." class="px-4 py-2 border rounded w-full">
            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                Cari
            </button>
        </form>


        <!-- Tombol Export PDF & Excel -->
        <div class="mb-4 flex justify-end gap-2">
            <form method="POST" action="export/pdf/export_audit_tiket_pdf.php" target="_blank">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 flex items-center gap-1">
                    <i data-lucide="file-text"></i> PDF
                </button>
            </form>
            <form method="POST" action="export/excel/export_audit_tiket_excel.php" target="_blank">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-1">
                    <i data-lucide="file-spreadsheet"></i> Excel
                </button>
            </form>
        </div>


        <!-- Table -->
        <div class="bg-white p-6 rounded shadow overflow-x-auto">
            <table class="min-w-full text-sm border">
                <thead>
                    <tr class="bg-purple-100 text-purple-800">
                        <th class="p-3 border">ID</th>
                        <th class="p-3 border">QR Code</th>
                        <th class="p-3 border">User</th>
                        <th class="p-3 border">Film</th>
                        <th class="p-3 border">Jadwal Tayang</th>
                        <th class="p-3 border">Waktu Check-in</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 border text-gray-500">#<?= $row['id_checkin'] ?></td>
                                <td class="p-3 border"><?= htmlspecialchars($row['kode_qr']) ?></td>
                                <td class="p-3 border"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                <td class="p-3 border"><?= htmlspecialchars($row['judul_film']) ?></td>
                                <td class="p-3 border"><?= date('d M Y H:i', strtotime($row['jadwal_tayang'])) ?></td>
                                <td class="p-3 border text-green-600"><?= date('d M Y H:i', strtotime($row['waktu_checkin'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center p-4 text-gray-500">Tidak ada data check-in ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>