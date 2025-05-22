<?php
require_once('../../../config/koneksi.php');
require_once '../../../vendor/autoload.php';

$search = trim($_POST['search'] ?? '');
$where = $search ? "WHERE u.nama_lengkap LIKE '%$search%' OR f.judul_film LIKE '%$search%'" : "";

$query = "
    SELECT 
        c.id_checkin, t.kode_qr, t.jadwal_tayang, u.nama_lengkap, f.judul_film, c.waktu_checkin
    FROM checkin_log c
    JOIN tiket t ON c.id_tiket = t.id_tiket
    JOIN users u ON t.id_user = u.id_user
    JOIN film f ON t.id_film = f.id_film
    $where
    ORDER BY c.waktu_checkin DESC
";

$result = $conn->query($query);

$html = '
<h2 style="text-align:center;">Laporan Audit Tiket - JATIX</h2>
<table border="1" cellspacing="0" cellpadding="5" width="100%">
<tr>
    <th>ID</th>
    <th>QR Code</th>
    <th>User</th>
    <th>Film</th>
    <th>Jadwal Tayang</th>
    <th>Waktu Check-in</th>
</tr>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . $row['id_checkin'] . '</td>
        <td>' . $row['kode_qr'] . '</td>
        <td>' . $row['nama_lengkap'] . '</td>
        <td>' . $row['judul_film'] . '</td>
        <td>' . date('d M Y H:i', strtotime($row['jadwal_tayang'])) . '</td>
        <td>' . date('d M Y H:i', strtotime($row['waktu_checkin'])) . '</td>
    </tr>';
}
$html .= '</table>';

$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML($html);
$mpdf->Output('audit_tiket.pdf', 'I');
?>
