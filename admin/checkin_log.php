<?php
require_once('../config/koneksi.php');
header('Content-Type: application/json');

$kode_qr = $_GET['kode_qr'] ?? '';

if (!$kode_qr) {
    echo json_encode(['status' => 'error', 'message' => 'Kode QR tidak valid.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        t.id_tiket, t.status, t.updated_at, t.jadwal_tayang, t.nomor_kursi,
        u.nama_lengkap,
        f.judul_film,
        b.nama_bioskop,
        s.nama_studio,
        j.waktu_tayang
    FROM tiket t
    JOIN users u ON t.id_user = u.id_user
    JOIN film f ON t.id_film = f.id_film
    JOIN jadwal j ON t.jadwal_tayang = j.id_jadwal
    JOIN studio s ON j.id_studio = s.id_studio
    JOIN bioskop b ON s.id_bioskop = b.id_bioskop
    WHERE t.kode_qr = ?
    LIMIT 1
");
$stmt->bind_param("s", $kode_qr);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $id_tiket = $row['id_tiket'];
    $status = $row['status'];
    $now = date('Y-m-d H:i:s');

    // Jika belum digunakan dan status dibayar, maka update jadi digunakan
    if ($status === 'dibayar') {
        $conn->query("UPDATE tiket SET status = 'digunakan', updated_at = '$now' WHERE id_tiket = $id_tiket");

        $desc = "Check-in otomatis tiket ID $id_tiket melalui QR scan";
        $log_stmt = $conn->prepare("INSERT INTO checkin_log (id_tiket, waktu_checkin, deskripsi) VALUES (?, ?, ?)");
        $log_stmt->bind_param("iss", $id_tiket, $now, $desc);
        $log_stmt->execute();

        $row['status'] = 'digunakan';
        $row['updated_at'] = $now;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $row
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Tiket tidak ditemukan atau kode QR tidak valid.'
    ]);
}
