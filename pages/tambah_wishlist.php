<?php
session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');

// Cek login
require_role('user');

$id_user = $_SESSION['id_user'] ?? null;
$id_film = $_GET['id'] ?? null;
$act     = $_GET['act'] ?? null;

if (!$id_film || !in_array($act, ['tambah', 'hapus'])) {
  header("Location: film.php");
  exit;
}

if ($act === 'tambah') {
  // Cek apakah sudah ada
  $cek = $conn->prepare("SELECT 1 FROM wishlist WHERE id_user = ? AND id_film = ?");
  $cek->bind_param("ii", $id_user, $id_film);
  $cek->execute();
  $cek_result = $cek->get_result();

  if ($cek_result->num_rows === 0) {
    $insert = $conn->prepare("INSERT INTO wishlist (id_user, id_film, created_at) VALUES (?, ?, NOW())");
    $insert->bind_param("ii", $id_user, $id_film);
    $insert->execute();
  }

  header("Location: detail_film.php?id=$id_film&msg=WishlistDitambahkan");
  exit;

} elseif ($act === 'hapus') {
  $hapus = $conn->prepare("DELETE FROM wishlist WHERE id_user = ? AND id_film = ?");
  $hapus->bind_param("ii", $id_user, $id_film);
  $hapus->execute();

  // Redirect kembali ke halaman sebelumnya
  $referer = $_SERVER['HTTP_REFERER'] ?? "wishlist.php";
  $redirect = strpos($referer, 'wishlist.php') !== false ? 'wishlist.php' : "detail_film.php?id=$id_film";
  header("Location: $redirect&msg=WishlistDihapus");
  exit;
}
