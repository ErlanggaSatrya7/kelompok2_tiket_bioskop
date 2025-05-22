<?php
// partials/auth_user.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id_user'])) {
    header("Location: ../pages/login.php");
    exit;
}
