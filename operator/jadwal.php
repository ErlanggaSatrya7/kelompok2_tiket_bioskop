<?php
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('operator');

$id_user = $_SESSION['id_user'];

// Ambil bioskop operator
$stmt = $conn->prepare("SELECT id_bioskop FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$stmt->bind_result($id_bioskop);
$stmt->fetch();
$stmt->close();

$error = '';
$success = '';

// Tambah jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $id_film = intval($_POST['id_film'] ?? 0);
    $id_studio = intval($_POST['id_studio'] ?? 0);
    $waktu = $_POST['waktu_tayang'] ?? '';

    if ($id_film && $id_studio && $waktu) {
        $stmt = $conn->prepare("INSERT INTO jadwal (id_film, id_studio, waktu_tayang) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id_film, $id_studio, $waktu);
        if ($stmt->execute()) {
            $success = "Jadwal berhasil ditambahkan.";
        } else {
            $error = "Gagal menambahkan jadwal.";
        }
    } else {
        $error = "Semua field wajib diisi.";
    }
}

// Hapus jadwal
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM jadwal WHERE id_jadwal = ? AND id_studio IN (SELECT id_studio FROM studio WHERE id_bioskop = ?)");
    $stmt->bind_param("ii", $id, $id_bioskop);
    if ($stmt->execute()) {
        $success = "Jadwal berhasil dihapus.";
    } else {
        $error = "Gagal menghapus jadwal.";
    }
}

// Ambil daftar film
$film_result = $conn->query("SELECT id_film, judul_film FROM film ORDER BY judul_film ASC");

// Ambil daftar studio milik operator
$studio_result = $conn->query("SELECT id_studio, nama_studio FROM studio WHERE id_bioskop = $id_bioskop ORDER BY nama_studio ASC");

// Ambil daftar jadwal
$jadwal = $conn->query("
  SELECT j.id_jadwal, f.judul_film, s.nama_studio, j.waktu_tayang 
  FROM jadwal j 
  JOIN film f ON j.id_film = f.id_film 
  JOIN studio s ON j.id_studio = s.id_studio 
  WHERE s.id_bioskop = $id_bioskop
  ORDER BY j.waktu_tayang DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Jadwal | Operator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-100 text-gray-800 flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
        <h2 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2">
            <i data-lucide="video"></i> JATIX Operator
        </h2>
        <nav class="space-y-2 text-sm">
            <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="layout-dashboard"></i> Dashboard</a>
            <a href="studio.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="building"></i> Studio</a>
            <a href="jadwal.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="calendar-clock"></i> Jadwal</a>
            <!-- <a href="scan_qr.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="scan"></i> Scan Tiket</a> -->
            <a href="tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="ticket"></i> Tiket</a>
            <!-- <a href="validasi_checkin.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="file-text"></i> Validasi Checkin</a> -->
            <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="file-text"></i> Laporan</a>
            <!-- <a href="audit_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="scan-line"></i> Audit Tiket</a> -->
            <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 p-8 w-full">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="calendar-clock"></i> Jadwal Tayang</h1>

        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 px-4 py-2 mb-4 rounded"><?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded"><?= $error ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Tabel Jadwal -->
            <div class="lg:col-span-2 bg-white rounded shadow p-4">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i data-lucide="list"></i> Daftar Jadwal</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2">Film</th>
                                <th>Studio</th>
                                <th>Waktu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $jadwal->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2"><?= htmlspecialchars($row['judul_film']) ?></td>
                                    <td><?= $row['nama_studio'] ?></td>
                                    <td><?= date('d M Y H:i', strtotime($row['waktu_tayang'])) ?></td>
                                    <td class="flex gap-2">
                                        <a href="edit_jadwal.php?id=<?= $row['id_jadwal'] ?>" class="text-blue-600 hover:underline">Edit</a>
                                        <a href="?hapus=<?= $row['id_jadwal'] ?>" onclick="return confirm('Hapus jadwal ini?')" class="text-red-600 hover:underline">Hapus</a>
                                    </td>

                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Tambah -->
            <div class="bg-white rounded shadow p-4">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i data-lucide="plus-circle"></i> Tambah Jadwal</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm mb-1">Film</label>
                        <select name="id_film" required class="w-full p-2 border rounded">
                            <option value="">Pilih Film</option>
                            <?php while ($film = $film_result->fetch_assoc()): ?>
                                <option value="<?= $film['id_film'] ?>"><?= htmlspecialchars($film['judul_film']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Studio</label>
                        <select name="id_studio" required class="w-full p-2 border rounded">
                            <option value="">Pilih Studio</option>
                            <?php while ($s = $studio_result->fetch_assoc()): ?>
                                <option value="<?= $s['id_studio'] ?>"><?= htmlspecialchars($s['nama_studio']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Waktu Tayang</label>
                        <input type="datetime-local" name="waktu_tayang" required class="w-full p-2 border rounded">
                    </div>
                    <button name="tambah" class="w-full bg-purple-600 text-white py-2 rounded hover:bg-purple-700">Tambah Jadwal</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>