<?php
require_once('../../../config/koneksi.php');

header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=log_aktivitas.xls");

$query = "
SELECT l.*, u.nama_lengkap, u.role
FROM log_aktivitas l
JOIN users u ON l.id_user = u.id_user
ORDER BY l.waktu DESC
";

$result = $conn->query($query);
?>

<table border="1" cellpadding="5" cellspacing="0">
  <thead>
    <tr>
      <th>Waktu</th>
      <th>User</th>
      <th>Role</th>
      <th>Aksi</th>
      <th>Deskripsi</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= date('d M Y H:i', strtotime($row['waktu'])) ?></td>
        <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
        <td><?= ucfirst(htmlspecialchars($row['role'])) ?></td>
        <td><strong><?= strtoupper(htmlspecialchars($row['aksi'])) ?></strong></td>
        <td><?= htmlspecialchars($row['deskripsi']) ?></td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>
