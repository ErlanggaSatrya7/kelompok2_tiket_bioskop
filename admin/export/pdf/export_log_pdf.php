<?php
require_once('../../../config/koneksi.php');
require_once('../../../vendor/autoload.php');

use Dompdf\Dompdf;

$query = "
SELECT l.*, u.nama_lengkap, u.role
FROM log_aktivitas l
JOIN users u ON l.id_user = u.id_user
ORDER BY l.waktu DESC
";

$result = $conn->query($query);

$html = "<h2 style='text-align:center;'>Log Aktivitas Pengguna</h2><br>";
$html .= "<table border='1' cellpadding='5' cellspacing='0' width='100%'>
<thead>
<tr>
  <th>Waktu</th>
  <th>User</th>
  <th>Role</th>
  <th>Aksi</th>
  <th>Deskripsi</th>
</tr>
</thead><tbody>";

while ($row = $result->fetch_assoc()) {
  $html .= "<tr>
    <td>".date('d M Y H:i', strtotime($row['waktu']))."</td>
    <td>".htmlspecialchars($row['nama_lengkap'])."</td>
    <td>".ucfirst(htmlspecialchars($row['role']))."</td>
    <td><strong>".strtoupper(htmlspecialchars($row['aksi']))."</strong></td>
    <td>".htmlspecialchars($row['deskripsi'])."</td>
  </tr>";
}
$html .= "</tbody></table>";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("log_aktivitas.pdf", array("Attachment" => 0));
