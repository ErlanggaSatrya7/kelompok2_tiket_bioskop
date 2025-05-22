<?php
require_once('../config/koneksi.php');

$kode_qr = $_GET['kode_qr'] ?? '';

if (!$kode_qr) {
    echo json_encode(['status' => 'error', 'message' => 'QR tidak ditemukan.']);
    exit;
}

// Ambil data tiket berdasarkan QR
$query = "
SELECT t.*, u.nama_lengkap, f.judul_film, s.nama_studio, b.nama_bioskop, j.waktu_tayang
FROM tiket t
JOIN users u ON t.id_user = u.id_user
JOIN film f ON t.id_film = f.id_film
JOIN jadwal j ON t.jadwal_tayang = j.waktu_tayang AND j.id_film = f.id_film
JOIN studio s ON j.id_studio = s.id_studio
JOIN bioskop b ON s.id_bioskop = b.id_bioskop
WHERE t.kode_qr = ?
LIMIT 1
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $kode_qr);
$stmt->execute();
$result = $stmt->get_result();

if ($data = $result->fetch_assoc()) {
    // Jika tiket masih aktif, ubah status menjadi digunakan dan isi updated_at
    if ($data['status'] === 'aktif') {
        $update = $conn->prepare("UPDATE tiket SET status = 'digunakan', updated_at = NOW() WHERE id_tiket = ?");
        $update->bind_param("i", $data['id_tiket']);
        $update->execute();

        // Update nilai pada variabel $data untuk ditampilkan ke frontend
        $data['status'] = 'digunakan';
        $data['updated_at'] = date('Y-m-d H:i:s'); // nilai lokal untuk ditampilkan
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Tiket tidak ditemukan atau tidak valid.']);
}
