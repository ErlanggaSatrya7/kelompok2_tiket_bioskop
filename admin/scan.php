<?php
session_start();
require_once('../config/koneksi.php');

// Cek login & role admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

// Proses validasi QR jika ada input
$validasi_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qr_code = $_POST['qr_code'];

    $stmt = $conn->prepare("SELECT t.*, f.judul_film, u.nama_user 
                            FROM tiket t 
                            JOIN film f ON t.id_film = f.id_film 
                            JOIN user u ON t.id_user = u.id_user 
                            WHERE t.kode_qr = ?");
    $stmt->bind_param("s", $qr_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $validasi_result = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Scan Tiket | JATIX Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">
<?php include 'partials_admin/navbar_admin.php'; ?>

<div class="max-w-xl mx-auto mt-10 bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold text-purple-700 mb-4">🔍 Validasi Tiket</h1>

    <form method="POST" class="mb-6">
        <label class="block mb-2 text-sm font-medium text-gray-600">Masukkan Kode QR:</label>
        <input type="text" name="qr_code" class="w-full p-2 border rounded" required>
        <button type="submit" class="mt-4 bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
            Validasi Tiket
        </button>
    </form>

    <?php if ($validasi_result): ?>
        <div class="bg-green-100 p-4 rounded">
            <h2 class="text-lg font-semibold text-green-700">✅ Tiket Ditemukan</h2>
            <p><strong>Nama:</strong> <?= htmlspecialchars($validasi_result['nama_user']) ?></p>
            <p><strong>Film:</strong> <?= htmlspecialchars($validasi_result['judul_film']) ?></p>
            <p><strong>Kursi:</strong> <?= htmlspecialchars($validasi_result['nomor_kursi']) ?></p>
            <p><strong>Jadwal:</strong> <?= htmlspecialchars($validasi_result['jadwal_tayang']) ?></p>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="bg-red-100 p-4 rounded text-red-700">
            ❌ Tiket tidak ditemukan atau kode QR salah.
        </div>
    <?php endif; ?>
</div>

</body>
</html>
