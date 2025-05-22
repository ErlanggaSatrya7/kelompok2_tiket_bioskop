<?php
require_once('../../../config/koneksi.php');

require_once('../../../vendor/autoload.php'); // Pastikan Dompdf sudah terinstal

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Ambil data tiket
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

$data = $conn->query($query);

$html = '<h2 style="text-align:center;">Laporan Data Tiket</h2>';
$html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">
<thead>
<tr style="background:#f2f2f2;">
    <th>ID</th>
    <th>User</th>
    <th>Film</th>
    <th>Bioskop</th>
    <th>Studio</th>
    <th>Waktu</th>
    <th>Kursi</th>
    <th>Status</th>
</tr>
</thead>
<tbody>';

while ($r = $data->fetch_assoc()) {
    $html .= '<tr>
        <td>#' . $r['id_tiket'] . '</td>
        <td>' . htmlspecialchars($r['nama_user']) . '</td>
        <td>' . htmlspecialchars($r['judul_film']) . '</td>
        <td>' . htmlspecialchars($r['nama_bioskop']) . '</td>
        <td>' . htmlspecialchars($r['nama_studio']) . '</td>
        <td>' . date('d-m-Y H:i', strtotime($r['waktu_tayang'])) . '</td>
        <td>' . $r['nomor_kursi'] . '</td>
        <td>' . ucfirst($r['status']) . '</td>
    </tr>';
}
$html .= '</tbody></table>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('laporan_tiket.pdf', ['Attachment' => false]);
