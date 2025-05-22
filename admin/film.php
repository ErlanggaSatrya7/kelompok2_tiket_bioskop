<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('../config/koneksi.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$error = '';
$success = '';
$judul = $genre = $durasi = $deskripsi = '';
$errors = [];

$search = trim($_GET['search'] ?? '');
$where = $search ? "WHERE judul_film LIKE '%$search%' OR genre LIKE '%$search%'" : '';

if (isset($_GET['update']) && $_GET['update'] === 'success') {
    $_SESSION['success'] = 'Film berhasil diperbarui.';
    header("Location: film.php");
    exit;
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Tambah film
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tambah'])) {
        $judul = trim($_POST['judul'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $durasi = (int) ($_POST['durasi'] ?? 0);
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        // Validasi
        if ($judul === '' || is_numeric($judul)) $errors['judul'] = "Judul harus diisi dan tidak boleh angka.";
        if ($genre === '' || is_numeric($genre)) $errors['genre'] = "Genre harus diisi dan tidak boleh angka.";
        if ($durasi < 120) $errors['durasi'] = "Durasi minimal 120 menit.";
        elseif ($durasi > 240) $errors['durasi'] = "Durasi maksimal 240 menit.";
        if ($deskripsi === '') $errors['deskripsi'] = "Deskripsi wajib diisi.";
        elseif (strlen($deskripsi) < 150 || strlen($deskripsi) > 250) $errors['deskripsi'] = "Deskripsi minimal 150 dan maksimal 250 karakter.";

        if (!isset($_FILES['poster']) || $_FILES['poster']['error'] !== 0) {
            $errors['poster'] = "Poster wajib diunggah.";
        } else {
            $poster = $_FILES['poster'];
            $ext = strtolower(pathinfo($poster['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];
            if (!in_array($ext, $allowed)) $errors['poster'] = "Format file tidak valid (jpg/jpeg/png).";
            elseif ($poster['size'] > 2 * 1024 * 1024) $errors['poster'] = "Ukuran file maksimal 2MB.";
        }

        if (empty($errors)) {
            $filename = time() . '_' . preg_replace("/[^a-zA-Z0-9._-]/", "", basename($poster['name']));
            $upload_path = "../assets/img/" . $filename;

            if (move_uploaded_file($poster['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("CALL sp_tambah_film(?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiss", $judul, $genre, $durasi, $deskripsi, $filename);
                if ($stmt->execute()) {
                    $success = "Film berhasil ditambahkan.";

                    $log_desc = "Menambahkan film: $judul";
                    $log_stmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'INSERT', ?)");
                    $log_stmt->bind_param("is", $_SESSION['id_user'], $log_desc);
                    $log_stmt->execute();

                    $_POST = [];
                } else {
                    $error = "Gagal menyimpan film ke database.";
                }
            } else {
                $errors['poster'] = "Gagal mengunggah file poster.";
            }
        } else {
            $error = "Gagal menambahkan film. Periksa kembali input Anda.";
        }
    }

    // Hapus film
   // Hapus film
if (isset($_POST['hapus']) && isset($_POST['id_film'])) {
    $id = (int) $_POST['id_film'];
    $film = $conn->query("SELECT * FROM film WHERE id_film = $id")->fetch_assoc();
    if ($film) {
        $poster_path = "../assets/img/" . $film['poster'];
        if (file_exists($poster_path)) unlink($poster_path);

        // Gunakan query biasa, bukan stored procedure
        $conn->query("DELETE FROM film WHERE id_film = $id");

        // Catat log
        $log_desc = "Menghapus film: " . $film['judul_film'];
        $log_stmt = $conn->prepare("INSERT INTO log_aktivitas (id_user, aksi, deskripsi) VALUES (?, 'DELETE', ?)");
        $log_stmt->bind_param("is", $_SESSION['id_user'], $log_desc);
        $log_stmt->execute();
        

        $_SESSION['success'] = "Film berhasil dihapus.";
        header("Location: film.php");
        exit;
    }
}

}

$film = $conn->query("SELECT * FROM film $where ORDER BY id_film DESC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Film | JATIX Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-100 text-gray-800 flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
        <h2 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2">
            <i data-lucide="video"></i> JATIX Admin
        </h2>
        <nav class="space-y-2 text-sm">
            <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="layout-dashboard"></i> Dashboard</a>
            <a href="bioskop.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="building"></i> Bioskop</a>
            <a href="film.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="video"></i> Film</a>
            <!-- <a href="jadwal.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="calendar-clock"></i> Jadwal</a> -->
            <a href="data_user.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="users"></i> Pengguna</a>
            <!-- <a href="data_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="ticket"></i> Tiket</a> -->
            <!-- <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="file-text"></i> Laporan</a> -->
            <a href="log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="history"></i> Log Aktivitas</a>
            <!-- <a href="audit_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="scan-line"></i> Audit Tiket</a> -->
            <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 p-8 w-full">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="video"></i> Tambah Film Baru</h1>

        <?php if ($success): ?>
            <div id="notif-success" class="bg-green-100 text-green-700 p-3 mb-4 rounded flex items-center gap-2"><i data-lucide="check-circle"></i> <?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 mb-4 rounded flex items-center gap-2"><i data-lucide="alert-circle"></i> <?= $error ?></div>
        <?php endif; ?>

        <!-- Form Tambah Film -->
        <form method="post" enctype="multipart/form-data" class="space-y-4 bg-white p-6 rounded shadow" onsubmit="return confirm('Yakin ingin menambahkan film ini?')">
            <input type="text" name="judul" placeholder="Judul Film" value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>" class="w-full p-2 border rounded <?= isset($errors['judul']) ? 'border-red-500' : '' ?>" required />
            <input type="text" name="genre" placeholder="Genre" value="<?= htmlspecialchars($_POST['genre'] ?? '') ?>" class="w-full p-2 border rounded <?= isset($errors['genre']) ? 'border-red-500' : '' ?>" required />
            <input type="number" name="durasi" placeholder="Durasi (menit)" value="<?= htmlspecialchars($_POST['durasi'] ?? '') ?>" min="1" max="240" class="w-full p-2 border rounded <?= isset($errors['durasi']) ? 'border-red-500' : '' ?>" required />
            <textarea name="deskripsi" placeholder="Deskripsi Film" class="w-full p-2 border rounded h-24 <?= isset($errors['deskripsi']) ? 'border-red-500' : '' ?>" required><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
            <input type="file" name="poster" id="posterInput" accept="image/*" class="w-full p-2 border rounded <?= isset($errors['poster']) ? 'border-red-500' : '' ?>" required />
            <img id="posterPreview" style="display:none;" class="w-full max-w-xs rounded border aspect-[2/3] object-cover">
            <button type="submit" name="tambah" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tambah Film</button>
        </form>

        <!-- Filter Pencarian -->
        <h2 class="text-xl font-semibold mt-10 mb-4 flex items-center gap-2"><i data-lucide="list-video"></i> Daftar Film</h2>
        <form method="GET" class="mb-6 max-w-md flex gap-2">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul atau genre..." class="w-full px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 flex items-center gap-1"><i data-lucide="search"></i></button>
        </form>

        <!-- List Film -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($row = $film->fetch_assoc()): ?>
                <div class="bg-white p-4 rounded shadow flex flex-col">
                    <?php $posterPath = "../assets/img/" . $row['poster']; ?>
                    <?php if (!empty($row['poster']) && file_exists($posterPath)): ?>
                        <img src="<?= $posterPath ?>" class="w-full aspect-[2/3] object-cover mb-2 rounded" alt="Poster">
                    <?php else: ?>
                        <div class="w-full aspect-[2/3] bg-gray-300 flex items-center justify-center rounded mb-2 text-gray-600">Tidak ada poster</div>
                    <?php endif; ?>

                    <div class="flex-grow">
                        <h3 class="text-lg font-bold"><?= htmlspecialchars($row['judul_film']) ?></h3>
                        <p class="text-sm text-gray-600">🎭 Genre: <?= htmlspecialchars($row['genre']) ?></p>
                        <p class="text-sm text-gray-600">⏱ Durasi: <?= htmlspecialchars($row['durasi']) ?> menit</p>
                        <p class="text-sm mt-2 text-gray-700 break-words"><?= nl2br(htmlspecialchars($row['deskripsi'])) ?></p>
                    </div>

                    <div class="mt-4 flex gap-2 justify-end">
                        <a href="edit_film.php?id=<?= $row['id_film'] ?>" onclick="return confirm('Yakin ingin mengedit film ini?')" class="flex items-center justify-center bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600" title="Edit Film">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </a>
                        <form method="post" onsubmit="return confirm('Yakin hapus film ini?')">
                            <input type="hidden" name="id_film" value="<?= $row['id_film'] ?>">
                            <button type="submit" name="hapus" class="flex items-center justify-center bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700" title="Hapus Film">
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

        setTimeout(() => {
            const notif = document.getElementById('notif-success');
            if (notif) notif.remove();
        }, 3000);

        const posterInput = document.getElementById('posterInput');
        const posterPreview = document.getElementById('posterPreview');

        posterInput?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    posterPreview.src = e.target.result;
                    posterPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                posterPreview.style.display = 'none';
                posterPreview.src = '';
            }
        });
    </script>
</body>

</html>