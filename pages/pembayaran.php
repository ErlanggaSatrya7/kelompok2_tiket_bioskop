<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');

$id_user = $_SESSION['id_user'] ?? null;
$order = $_SESSION['order'] ?? null;

if (!$order || !$id_user) {
  header("Location: beranda.php");
  exit;
}

// Ambil info film & jadwal
$id_film = intval($order['id_film']);
$id_jadwal = intval($order['id_jadwal']);
$nomor_kursi = $order['nomor_kursi']; // array
$harga_total = $order['harga_total'];

$film = $conn->query("SELECT judul_film FROM film WHERE id_film = $id_film")->fetch_assoc();
$jadwal = $conn->query("
  SELECT j.waktu_tayang, j.harga, s.nama_studio, b.nama_bioskop 
  FROM jadwal j 
  JOIN studio s ON j.id_studio = s.id_studio 
  JOIN bioskop b ON s.id_bioskop = b.id_bioskop 
  WHERE j.id_jadwal = $id_jadwal
")->fetch_assoc();

// Proses bayar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($nomor_kursi as $kursi) {
    $conn->query("
      INSERT INTO tiket (id_user, id_film, id_jadwal, nomor_kursi, status)
      VALUES ($id_user, $id_film, $id_jadwal, '$kursi', 'terpesan')
    ");
  }

  // Hapus session order
  unset($_SESSION['order']);

  // Redirect
  header("Location: tiket_saya.php?msg=success");
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pembayaran | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-2xl font-bold mb-4">💳 Pembayaran Tiket</h1>

  <div class="bg-white rounded shadow p-4 space-y-4">
    <div>
      <h2 class="text-lg font-semibold mb-1">Film:</h2>
      <p><?= htmlspecialchars($film['judul_film']) ?></p>
    </div>

    <div>
      <h2 class="text-lg font-semibold mb-1">Jadwal Tayang:</h2>
      <p><?= date('d M Y H:i', strtotime($jadwal['waktu_tayang'])) ?> — Studio <?= $jadwal['nama_studio'] ?> — <?= $jadwal['nama_bioskop'] ?></p>
    </div>

    <div>
      <h2 class="text-lg font-semibold mb-1">Kursi:</h2>
      <p><?= implode(', ', $nomor_kursi) ?></p>
    </div>

    <div>
      <h2 class="text-lg font-semibold mb-1">Total Bayar:</h2>
      <p class="text-purple-700 font-bold text-lg">Rp<?= number_format($harga_total, 0, ',', '.') ?></p>
    </div>
  </div>

  <form method="POST" class="mt-6 text-right">
    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded shadow">Bayar Sekarang</button>
  </form>
</div>

</body>
</html>
