<?php
session_start();
require_once('../config/koneksi.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$error = '';
$success = '';
$search = trim($_GET['search'] ?? '');
$where = $search ? "WHERE f.judul_film LIKE '%$search%' OR s.nama_studio LIKE '%$search%' OR b.nama_bioskop LIKE '%$search%'" : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $id_film = (int) $_POST['id_film'];
    $id_studio = (int) $_POST['id_studio'];
    $tanggal = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];

    if (!$id_film || !$id_studio || !$tanggal || !$jam_mulai) {
        $error = 'Semua field wajib diisi.';
    } else {
        $waktu_tayang = "$tanggal $jam_mulai:00";
        $stmt = $conn->prepare("INSERT INTO jadwal (id_film, id_studio, waktu_tayang) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id_film, $id_studio, $waktu_tayang);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Jadwal berhasil ditambahkan.';
            $desc = "Menambahkan jadwal film ID $id_film di studio ID $id_studio pada $waktu_tayang";
            $log = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'INSERT', ?)");
            $log->bind_param("is", $_SESSION['id_user'], $desc);
            $log->execute();
            header("Location: jadwal.php");
            exit;
        } else {
            $error = 'Gagal menambahkan jadwal.';
        }
    }
}

// Hapus jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id = (int) $_POST['id_jadwal'];
    $jadwal = $conn->query("SELECT * FROM jadwal WHERE id_jadwal = $id")->fetch_assoc();
    if ($jadwal) {
        $conn->query("DELETE FROM jadwal WHERE id_jadwal = $id");
        $desc = "Menghapus jadwal ID $id";
        $log = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'DELETE', ?)");
        $log->bind_param("is", $_SESSION['id_user'], $desc);
        $log->execute();
        $_SESSION['success'] = "Jadwal berhasil dihapus.";
        header("Location: jadwal.php");
        exit;
    }
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

$film = $conn->query("SELECT * FROM film ORDER BY judul_film");
$studio = $conn->query("SELECT s.*, b.nama_bioskop FROM studio s JOIN bioskop b ON s.id_bioskop = b.id_bioskop ORDER BY b.nama_bioskop, s.nama_studio");

$jadwal = $conn->query("
    SELECT j.*, f.judul_film, s.nama_studio, b.nama_bioskop
    FROM jadwal j
    JOIN film f ON j.id_film = f.id_film
    JOIN studio s ON j.id_studio = s.id_studio
    JOIN bioskop b ON s.id_bioskop = b.id_bioskop
    $where
    ORDER BY j.waktu_tayang DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Jadwal | JATIX Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex min-h-screen">

<!-- Sidebar -->
<aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
    <h2 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2"><i data-lucide="calendar-clock"></i> JATIX Admin</h2>
    <nav class="space-y-2 text-sm">
        <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="layout-dashboard"></i> Dashboard</a>
        <a href="bioskop.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="building"></i> Kelola Bioskop</a>
        <a href="studio.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="columns"></i> Kelola Studio</a>
        <a href="film.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="video"></i> Kelola Film</a>
        <a href="jadwal.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="calendar-clock"></i> Jadwal Tayang</a>
        <a href="data_user.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="users"></i> Data Pengguna</a>
        <a href="data_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="ticket"></i> Data Tiket</a>
        <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="file-text"></i> Laporan</a>
        <a href="log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="history"></i> Log Aktivitas</a>
        <a href="scan_camera.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="scan-line"></i> Scan QR</a>
        <a href="checkin_log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="check"></i> Log Check-in</a>
        <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
    </nav>
</aside>

<!-- Main -->
<main class="ml-64 p-8 w-full">
    <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="calendar-clock"></i> Kelola Jadwal</h1>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-3 mb-4 rounded flex items-center gap-2"><i data-lucide="check-circle"></i> <?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 mb-4 rounded flex items-center gap-2"><i data-lucide="alert-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <!-- Form Tambah -->
    <form method="POST" class="bg-white p-6 rounded shadow max-w-xl mb-6 space-y-4" onsubmit="return confirm('Yakin ingin menambahkan jadwal ini?')">
        <input type="hidden" name="tambah" value="1">
        <div>
            <label class="block font-semibold mb-1">Film</label>
            <select name="id_film" class="w-full p-2 border rounded" required>
                <option value="">-- Pilih Film --</option>
                <?php while ($f = $film->fetch_assoc()): ?>
                    <option value="<?= $f['id_film'] ?>"><?= htmlspecialchars($f['judul_film']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label class="block font-semibold mb-1">Studio</label>
            <select name="id_studio" class="w-full p-2 border rounded" required>
                <option value="">-- Pilih Studio --</option>
                <?php while ($s = $studio->fetch_assoc()): ?>
                    <option value="<?= $s['id_studio'] ?>"><?= htmlspecialchars($s['nama_bioskop']) ?> - <?= htmlspecialchars($s['nama_studio']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label class="block font-semibold mb-1">Tanggal Tayang</label>
            <input type="date" name="tanggal" class="w-full p-2 border rounded" required>
        </div>
        <div>
            <label class="block font-semibold mb-1">Jam Mulai</label>
            <input type="time" name="jam_mulai" class="w-full p-2 border rounded" required>
        </div>
        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Tambah Jadwal</button>
    </form>

    <!-- Filter -->
    <form method="GET" class="mb-6 max-w-md flex gap-2">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari film, studio, bioskop..." class="w-full px-4 py-2 border rounded shadow-sm focus:ring-2 focus:ring-purple-500" />
        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 flex items-center gap-1"><i data-lucide="search"></i></button>
    </form>

    <!-- Daftar Jadwal -->
    <h2 class="text-xl font-semibold mb-4 flex items-center gap-2"><i data-lucide="list"></i> Daftar Jadwal Tayang</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php while ($row = $jadwal->fetch_assoc()): ?>
            <?php $waktu = explode(' ', $row['waktu_tayang']); ?>
            <div class="bg-white p-4 rounded shadow">
                <h3 class="text-lg font-bold mb-1">🎬 <?= htmlspecialchars($row['judul_film']) ?></h3>
                <p class="text-sm text-gray-600">🏢 <?= htmlspecialchars($row['nama_bioskop']) ?> - <?= htmlspecialchars($row['nama_studio']) ?></p>
                <p class="text-sm text-gray-600">📅 <?= $waktu[0] ?> | 🕒 <?= substr($waktu[1], 0, 5) ?></p>

                <div class="mt-4 flex gap-2 justify-end">
                    <a href="edit_jadwal.php?id=<?= $row['id_jadwal'] ?>" onclick="return confirm('Edit jadwal ini?')" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 flex items-center justify-center">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                    </a>
                    <form method="post" onsubmit="return confirm('Yakin hapus jadwal ini?')">
                        <input type="hidden" name="id_jadwal" value="<?= $row['id_jadwal'] ?>">
                        <button type="submit" name="hapus" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 flex items-center justify-center">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</main>

<script>
    lucide.createIcons();
</script>
</body>
</html>
