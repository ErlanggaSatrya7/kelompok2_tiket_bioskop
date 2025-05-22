<?php
require_once('../../../config/koneksi.php');
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=data_pengguna.xls");

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Nama Lengkap</th><th>Username</th><th>Email</th><th>Role</th></tr>";

$query = $conn->query("SELECT id_user, nama_lengkap, username, email, role FROM users WHERE role != 'admin'");
while ($row = $query->fetch_assoc()) {
    echo "<tr>
        <td>{$row['id_user']}</td>
        <td>{$row['nama_lengkap']}</td>
        <td>{$row['username']}</td>
        <td>{$row['email']}</td>
        <td>{$row['role']}</td>
    </tr>";
}
echo "</table>";
