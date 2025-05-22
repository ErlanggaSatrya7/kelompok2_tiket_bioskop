<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');

$id_user = $_SESSION['id_user'];
$id_film = $_GET['id'] ?? null;

if (!$id_film) {
  echo "ID film tidak ditemukan.";
  exit;
}

// Ambil data film
$film = $conn->query("SELECT * FROM film WHERE id_film = $id_film")->fetch_assoc();
if (!$film) {
  echo "Film tidak ditemukan.";
  exit;
}

// Ambil semua jadwal film
$jadwal_result = $conn->query("
  SELECT j.*, s.nama_studio 
  FROM jadwal j 
  JOIN studio s ON j.id_studio = s.id_studio 
  WHERE j.id_film = $id_film
");

$selected_jadwal = $_GET['jadwal'] ?? null;
$kursi_terisi = [];

if ($selected_jadwal) {
  // Ambil kursi yang sudah dipesan
  $kursi_result = $conn->query("SELECT nomor_kursi FROM tiket WHERE id_jadwal = $selected_jadwal");
  while ($k = $kursi_result->fetch_assoc()) {
    $kursi_terisi[] = $k['nomor_kursi'];
  }
}

// Proses submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_jadwal = $_POST['id_jadwal'];
  $kursi = $_POST['kursi'] ?? [];

  if (!$id_jadwal || count($kursi) === 0) {
    $error = "Pilih jadwal dan kursi terlebih dahulu.";
  } else {
    // Ambil harga per kursi
    $harga = $conn->query("SELECT harga FROM jadwal WHERE id_jadwal = $id_jadwal")->fetch_assoc()['harga'];
    $_SESSION['order'] = [
      'id_film' => $id_film,
      'id_jadwal' => $id_jadwal,
      'nomor_kursi' => $kursi,
      'harga_total' => $harga * count($kursi)
    ];
    header("Location: pembayaran.php");
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pilih Kursi | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="max-w-5xl mx-auto p-6">
  <h1 class="text-2xl font-bold mb-2">🎟 Pilih Jadwal & Kursi</h1>
  <p class="mb-4 text-gray-600"><?= htmlspecialchars($film['judul_film']) ?> (<?= $film['durasi'] ?> menit)</p>

  <!-- Jadwal -->
  <form method="GET" class="mb-6">
    <input type="hidden" name="id" value="<?= $id_film ?>">
    <label class="block text-sm font-semibold mb-1">Pilih Jadwal Tayang:</label>
    <select name="jadwal" onchange="this.form.submit()" class="w-full border rounded p-2">
      <option value="">-- Pilih Jadwal --</option>
      <?php while($j = $jadwal_result->fetch_assoc()): ?>
        <option value="<?= $j['id_jadwal'] ?>" <?= $selected_jadwal == $j['id_jadwal'] ? 'selected' : '' ?>>
          <?= date('d M Y H:i', strtotime($j['waktu_tayang'])) ?> - Studio <?= $j['nama_studio'] ?> - Rp<?= number_format($j['harga'], 0, ',', '.') ?>
        </option>
      <?php endwhile; ?>
    </select>
  </form>

  <?php if ($selected_jadwal): ?>
  <!-- Grid Kursi -->
  <form method="POST">
    <input type="hidden" name="id_jadwal" value="<?= $selected_jadwal ?>">
    <label class="block text-sm font-semibold mb-2">Pilih Kursi:</label>
    <div class="grid grid-cols-8 gap-2 max-w-xl mb-4">
      <?php
        $rows = ['A','B','C','D','E'];
        for ($i = 0; $i < count($rows); $i++) {
          for ($j = 1; $j <= 8; $j++) {
            $no_kursi = $rows[$i] . $j;
            $disabled = in_array($no_kursi, $kursi_terisi);
            echo '<label class="cursor-pointer relative group">';
            echo '<input type="checkbox" name="kursi[]" value="'. $no_kursi .'" '. ($disabled ? 'disabled' : '') .' class="hidden peer">';
            echo '<div class="text-center border rounded py-2 transition-all duration-200 text-sm
                  '. ($disabled 
                      ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                      : 'bg-white hover:bg-purple-100 peer-checked:bg-purple-600 peer-checked:text-white peer-checked:font-semibold peer-checked:shadow-lg') .'">'
                  . $no_kursi .'</div>';
            echo '</label>';
          }
        }
      ?>
    </div>

    <?php if (isset($error)): ?>
      <p class="text-red-600 text-sm mb-3"><?= $error ?></p>
    <?php endif; ?>

    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700">Lanjut Pembayaran</button>
  </form>
  <?php endif; ?>
</div>

</body>
</html>