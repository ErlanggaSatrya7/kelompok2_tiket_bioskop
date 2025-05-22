<?php
require_once('../../../config/koneksi.php');


header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=laporan_tiket.xls");

$query = "
SELECT 
    t.id_tiket,
    u.nama_lengkap AS nama_user,
    f.judul_film,
    s.nama_studio,
    b.nama_bioskop,
    j.waktu_tayang,
    t.nomor_kursi,
    t.status
FROM tiket t
JOIN users u ON t.id_user = u.id_user
JOIN film f ON t.id_film = f.id_film
JOIN jadwal j ON t.jadwal_tayang = j.id_jadwal
JOIN studio s ON j.id_studio = s.id_studio
JOIN bioskop b ON s.id_bioskop = b.id_bioskop
ORDER BY t.created_at DESC
";

$result = $conn->query($query);
?>

<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color:#f2f2f2;">
            <th>ID Tiket</th>
            <th>Nama User</th>
            <th>Judul Film</th>
            <th>Bioskop</th>
            <th>Studio</th>
            <th>Waktu Tayang</th>
            <th>Nomor Kursi</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($r = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $r['id_tiket'] ?></td>
            <td><?= htmlspecialchars($r['nama_user']) ?></td>
            <td><?= htmlspecialchars($r['judul_film']) ?></td>
            <td><?= htmlspecialchars($r['nama_bioskop']) ?></td>
            <td><?= htmlspecialchars($r['nama_studio']) ?></td>
            <td><?= date('d-m-Y H:i', strtotime($r['waktu_tayang'])) ?></td>
            <td><?= $r['nomor_kursi'] ?></td>
            <td><?= ucfirst($r['status']) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
