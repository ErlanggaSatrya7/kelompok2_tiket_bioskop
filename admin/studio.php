<?php
session_start();
require_once('../config/koneksi.php');

if (!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$error = '';
$success = '';
$search = trim($_GET['search'] ?? '');
$where = $search ? "WHERE s.nama_studio LIKE '%$search%' OR b.nama_bioskop LIKE '%$search%'" : '';

// Tambah studio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $id_bioskop = $_POST['id_bioskop'];
    $nama_studio = trim($_POST['nama_studio']);
    $kapasitas = intval($_POST['kapasitas']);

    if (!$id_bioskop || $nama_studio === '' || $kapasitas <= 0) {
        $error = 'Semua field wajib diisi dengan benar.';
    } else {
        $stmt = $conn->prepare("INSERT INTO studio (id_bioskop, nama_studio, kapasitas) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $id_bioskop, $nama_studio, $kapasitas);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Studio berhasil ditambahkan.';
            $deskripsi = "Menambahkan studio '$nama_studio' (kapasitas $kapasitas) di bioskop ID $id_bioskop";
            $log = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'INSERT', ?)");
            $log->bind_param("is", $_SESSION['id_user'], $deskripsi);
            $log->execute();
            header("Location: studio.php");
            exit;
        } else {
            $error = 'Gagal menambahkan studio.';
        }
    }
}

// Hapus studio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id_studio = (int) $_POST['id_studio'];
    $s = $conn->query("SELECT * FROM studio WHERE id_studio = $id_studio")->fetch_assoc();
    if ($s) {
        $conn->query("DELETE FROM studio WHERE id_studio = $id_studio");
        $desc = "Menghapus studio: {$s['nama_studio']}";
        $log_stmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'DELETE', ?)");
        $log_stmt->bind_param("is", $_SESSION['id_user'], $desc);
        $log_stmt->execute();
        $_SESSION['success'] = "Studio berhasil dihapus.";
        header("Location: studio.php");
        exit;
    }
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

$bioskop = $conn->query("SELECT * FROM bioskop ORDER BY nama_bioskop");

$studio = $conn->query("
    SELECT s.*, b.nama_bioskop, b.lokasi
    FROM studio s
    JOIN bioskop b ON s.id_bioskop = b.id_bioskop
    $where
    ORDER BY s.id_studio DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Studio | JATIX Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-100 text-gray-800 flex min-h-screen">

    <aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
        <h2 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2"><i data-lucide="columns"></i> JATIX Admin</h2>
        <nav class="space-y-2 text-sm">
            <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="layout-dashboard"></i> Dashboard</a>
            <a href="bioskop.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="building"></i> Kelola Bioskop</a>
            <a href="studio.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="columns"></i> Kelola Studio</a>
            <a href="film.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="video"></i> Kelola Film</a>
            <a href="jadwal.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="calendar-clock"></i> Jadwal Tayang</a>
            <a href="data_user.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="users"></i> Data Pengguna</a>
            <a href="data_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="ticket"></i> Data Tiket</a>
            <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="file-text"></i> Laporan</a>
            <a href="log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="history"></i> Log Aktivitas</a>
            <a href="scan_camera.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="scan-line"></i> Scan QR</a>
            <a href="checkin_log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="check"></i> Log Check-in</a>
            <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
        </nav>
    </aside>

    <main class="ml-64 p-8 w-full">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="columns"></i> Kelola Studio</h1>

        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 p-3 mb-4 rounded flex items-center gap-2"><i data-lucide="check-circle"></i> <?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 mb-4 rounded flex items-center gap-2"><i data-lucide="alert-circle"></i> <?= $error ?></div>
        <?php endif; ?>

        <!-- Form Tambah Studio -->
        <form method="POST" class="bg-white p-6 rounded shadow max-w-xl mb-6 space-y-4" onsubmit="return confirm('Yakin ingin menambahkan studio ini?')">
            <input type="hidden" name="tambah" value="1">
            <div>
                <label class="block font-semibold mb-1">Pilih Bioskop</label>
                <select name="id_bioskop" class="w-full p-2 border rounded" required>
                    <option value="">-- Pilih Bioskop --</option>
                    <?php while ($b = $bioskop->fetch_assoc()): ?>
                        <option value="<?= $b['id_bioskop'] ?>"><?= htmlspecialchars($b['nama_bioskop']) ?> (<?= htmlspecialchars($b['lokasi']) ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block font-semibold mb-1">Nama Studio</label>
                <input type="text" name="nama_studio" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label class="block font-semibold mb-1">Kapasitas</label>
                <input type="number" name="kapasitas" min="1" class="w-full p-2 border rounded" required>
            </div>
            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                Tambah Studio
            </button>
        </form>

        <!-- Filter Pencarian -->
        <form method="GET" class="mb-6 max-w-md flex gap-2">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama studio atau bioskop..." class="w-full px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 flex items-center gap-1"><i data-lucide="search"></i></button>
        </form>

        <!-- Daftar Studio -->
        <h2 class="text-xl font-semibold mb-4 flex items-center gap-2"><i data-lucide="list"></i> Daftar Studio</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($row = $studio->fetch_assoc()): ?>
                <div class="bg-white p-4 rounded shadow">
                    <h3 class="text-lg font-bold"><?= htmlspecialchars($row['nama_studio']) ?></h3>
                    <p class="text-sm text-gray-600">🎥 Kapasitas: <?= $row['kapasitas'] ?></p>
                    <p class="text-sm text-gray-600">🏢 <?= htmlspecialchars($row['nama_bioskop']) ?> - <?= htmlspecialchars($row['lokasi']) ?></p>

                    <div class="mt-4 flex gap-2 justify-end">
                        <a href="edit_studio.php?id=<?= $row['id_studio'] ?>" onclick="return confirm('Edit studio ini?')"
                            class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 flex items-center justify-center">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </a>
                        <form method="post" onsubmit="return confirm('Yakin hapus studio ini?')">
                            <input type="hidden" name="id_studio" value="<?= $row['id_studio'] ?>">
                            <button type="submit" name="hapus"
                                class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 flex items-center justify-center">
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