<?php
session_start();
require_once('../config/koneksi.php');


if (!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$laporan = [];
$total_pendapatan = 0;
$total_tiket = 0;

if ($tanggal_awal && $tanggal_akhir) {
    $tanggal_awal = date('Y-m-d', strtotime($tanggal_awal));
    $tanggal_akhir = date('Y-m-d', strtotime($tanggal_akhir));

    $query = "
SELECT 
    f.judul_film,
    b.nama_bioskop,
    s.nama_studio,
    j.waktu_tayang,
    p.total_bayar,
    u.nama_lengkap,
    p.waktu_pesan AS created_at
FROM pemesanan p
JOIN users u ON p.id_user = u.id_user
JOIN jadwal j ON p.id_jadwal = j.id_jadwal
JOIN film f ON j.id_film = f.id_film
LEFT JOIN studio s ON j.id_studio = s.id_studio
LEFT JOIN bioskop b ON s.id_bioskop = b.id_bioskop
WHERE p.status = 'LUNAS'
ORDER BY p.waktu_pesan DESC
    ";

    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $laporan[] = $row;
        $total_pendapatan += (int)$row['total_bayar'];
        $total_tiket++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan | JATIX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-100 text-gray-800 flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
        <h2 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2">
            <i data-lucide="file-text"></i> JATIX Admin
        </h2>
        <nav class="space-y-2 text-sm">
            <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="layout-dashboard"></i> Dashboard</a>
            <a href="bioskop.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="building"></i>Bioskop</a>
            <a href="film.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="video"></i>Film</a>
            <a href="data_user.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="users"></i>Pengguna</a>
            <!-- <a href="data_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="ticket"></i>Tiket</a> -->
            <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="file-text"></i> Laporan</a>
            <a href="log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="history"></i> Log Aktivitas</a>
            <!-- <a href="audit_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="scan-line"></i> Audit Tiket</a> -->
            <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 p-8 w-full">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="file-text"></i> Laporan Penjualan Tiket</h1>

        <!-- Filter -->
        <form method="GET" class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium mb-1">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>" class="border border-gray-300 rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>" class="border border-gray-300 rounded px-3 py-2">
            </div>
            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Tampilkan</button>
        </form>

        <?php if (!empty($laporan)): ?>
            <div class="mb-4 flex justify-between items-center">
                <p class="text-sm text-gray-600">
                    Menampilkan <strong><?= $total_tiket ?></strong> transaksi | Total Pendapatan: <strong class="text-green-600">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></strong>
                </p>
                <div class="flex gap-2">
                    <!-- Export PDF -->
                    <!-- Export PDF -->
                    <form method="POST" action="export/pdf/export_data_tiket_pdf.php" target="_blank">
                        <input type="hidden" name="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>">
                        <input type="hidden" name="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>">
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 flex items-center gap-1">
                            <i data-lucide="file-text"></i> PDF
                        </button>
                    </form>

                    <!-- Export Excel -->
                    <form method="POST" action="export/excel/export_data_tiket_excel.php" target="_blank">
                        <input type="hidden" name="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>">
                        <input type="hidden" name="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>">
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-1">
                            <i data-lucide="file-spreadsheet"></i> Excel
                        </button>
                    </form>

                </div>
            </div>

            <!-- Tabel -->
            <div class="bg-white rounded shadow p-4 overflow-x-auto">
                <table class="min-w-full text-sm border">
                    <thead>
                        <tr class="bg-purple-100 text-purple-800">
                            <th class="p-3 border">User</th>
                            <th class="p-3 border">Film</th>
                            <th class="p-3 border">Bioskop</th>
                            <th class="p-3 border">Studio</th>
                            <th class="p-3 border">Waktu Tayang</th>
                            <th class="p-3 border">Bayar</th>
                            <th class="p-3 border">Waktu Pesan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laporan as $row): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 border"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                <td class="p-3 border"><?= htmlspecialchars($row['judul_film']) ?></td>
                                <td class="p-3 border"><?= htmlspecialchars($row['nama_bioskop']) ?></td>
                                <td class="p-3 border"><?= htmlspecialchars($row['nama_studio']) ?></td>
                                <td class="p-3 border"><?= date('d M Y H:i', strtotime($row['waktu_tayang'])) ?></td>
                                <td class="p-3 border">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></td>
                                <td class="p-3 border"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($tanggal_awal && $tanggal_akhir): ?>
            <p class="text-gray-500">Tidak ada data pemesanan untuk periode tersebut.</p>
        <?php endif; ?>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>