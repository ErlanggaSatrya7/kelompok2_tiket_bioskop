<?php
require_once('../../../config/koneksi.php');

require_once('../../../vendor/autoload.php'); // Pastikan Dompdf sudah terinstal

use Dompdf\Dompdf;

$dompdf = new Dompdf();
$html = '<h3 style="text-align:center;">Laporan Data Pengguna</h3>';
$html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">
<thead><tr style="background:#eee;">
<th>ID</th><th>Nama Lengkap</th><th>Username</th><th>Email</th><th>Role</th>
</tr></thead><tbody>';

$query = $conn->query("SELECT id_user, nama_lengkap, username, email, role FROM users WHERE role != 'admin'");
while ($row = $query->fetch_assoc()) {
    $html .= "<tr>
        <td>{$row['id_user']}</td>
        <td>{$row['nama_lengkap']}</td>
        <td>{$row['username']}</td>
        <td>{$row['email']}</td>
        <td>{$row['role']}</td>
    </tr>";
}
$html .= '</tbody></table>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('data_pengguna.pdf', ['Attachment' => false]);
exit;
