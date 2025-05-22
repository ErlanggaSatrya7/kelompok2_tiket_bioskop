<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('koneksi.php');

function require_role($expected_role) {
    if (!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) !== strtolower($expected_role)) {
        header("Location: ../pages/login.php");
        exit;
    }
}
