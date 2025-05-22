<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('user');

$id_user = $_SESSION['id_user'];
$success = '';
$error = '';
$errors = [];

$user = $conn->query("SELECT * FROM users WHERE id_user = $id_user")->fetch_assoc();

$nama = $user['nama_lengkap'];
$username = $user['username'];
$email = $user['email'];
$foto = $user['foto_profil'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_baru = trim($_POST['nama_lengkap']);
    $username_baru = trim($_POST['username']);
    $email_baru = trim($_POST['email']);

    if ($nama_baru === '') $errors['nama_lengkap'] = "Nama tidak boleh kosong.";
    if ($username_baru === '') $errors['username'] = "Username tidak boleh kosong.";
    if (!filter_var($email_baru, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Email tidak valid.";

    $foto_baru = $foto;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $errors['foto'] = "Format file tidak valid (jpg/jpeg/png).";
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $errors['foto'] = "Ukuran maksimal 2MB.";
        } else {
            $nama_file = time() . '_' . basename($_FILES['foto']['name']);
            $target_path = "../assets/img/profil/" . $nama_file;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_path)) {
                if ($foto && file_exists("../assets/img/profil/" . $foto)) unlink("../assets/img/profil/" . $foto);
                $foto_baru = $nama_file;
            } else {
                $errors['foto'] = "Gagal upload foto.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET nama_lengkap=?, username=?, email=?, foto_profil=? WHERE id_user=?");
        $stmt->bind_param("ssssi", $nama_baru, $username_baru, $email_baru, $foto_baru, $id_user);
        $stmt->execute();

        $_SESSION['nama_lengkap'] = $nama_baru;
        $_SESSION['success'] = 'Profil berhasil diperbarui.';
        header("Location: profile.php");
        exit;
    }
}
?>
