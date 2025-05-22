<?php
require_once('../../../config/koneksi.php');

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=data_tiket.xls");

$tanggal_awal = $_POST['tanggal_awal'] ?? '';
$tanggal_akhir = $_POST['tanggal_akhir'] ?? '';

$where = '';
if ($tanggal_awal && $tanggal_akhir) {
    $tanggal_awal = date('Y-m-d', strtotime($tanggal_awal));
    $tanggal_akhir = date('Y-m-d', strtotime($tanggal_akhir));
    $where = "WHERE DATE(t.created_at) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
}

$query = "
SELECT 
    t.id_tiket,
    u.nama_lengkap,
    f.judul_film,
    b.nama_bioskop,
    s.nama_studio,
    j.waktu_tayang,
    t.nomor_kursi,
    t.status
FROM tiket t
JOIN users u ON t.id_user = u.id_user
JOIN film f ON t.id_film = f.id_film
JOIN jadwal j ON t.jadwal_tayang = j.id_jadwal
JOIN studio s ON j.id_studio = s.id_studio
JOIN bioskop b ON s.id_bioskop = b.id_bioskop
$where
ORDER BY t.created_at DESC
";

$tiket = $conn->query($query);

echo "<table border='1'>";
echo "<tr>
    <th>ID Tiket</th>
    <th>Nama User</th>
    <th>Film</th>
    <th>Bioskop</th>
    <th>Studio</th>
    <th>Waktu Tayang</th>
    <th>No Kursi</th>
    <th>Status</th>
</tr>";

while ($row = $tiket->fetch_assoc()) {
    echo "<tr>
        <td>#{$row['id_tiket']}</td>
        <td>" . htmlspecialchars($row['nama_lengkap']) . "</td>
        <td>" . htmlspecialchars($row['judul_film']) . "</td>
        <td>" . htmlspecialchars($row['nama_bioskop']) . "</td>
        <td>" . htmlspecialchars($row['nama_studio']) . "</td>
        <td>" . date('d-m-Y H:i', strtotime($row['waktu_tayang'])) . "</td>
        <td>" . htmlspecialchars($row['nomor_kursi']) . "</td>
        <td>" . ucfirst($row['status']) . "</td>
    </tr>";
}
echo "</table>";
