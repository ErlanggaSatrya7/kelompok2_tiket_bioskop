<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('../partials/auth_user.php');
require_once('../config/koneksi.php');

$id_user = $_SESSION['id_user'];
$nama = $_POST['nama'] ?? '';
$email = $_POST['email'] ?? '';

if ($nama && $email) {
    $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, email = ? WHERE id_user = ?");
    $stmt->bind_param("ssi", $nama, $email, $id_user);
    $stmt->execute();
}

header("Location: profil.php");
exit;
