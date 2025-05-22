<?php
require_once('../config/koneksi.php');
require_once('../config/auth.php');
require_role('operator');

// Terima input kode_tiket
$kode = trim($_POST['kode_tiket'] ?? '');

$response = [
    'status' => 'error',
    'message' => 'Kode tiket tidak valid.'
];

if ($kode !== '') {
    $stmt = $conn->prepare("
        SELECT t.*, f.judul_film, j.waktu_tayang, u.nama_lengkap
        FROM tiket t
        JOIN film f ON t.id_film = f.id_film
        JOIN jadwal j ON t.id_jadwal = j.id_jadwal
        JOIN users u ON t.id_user = u.id_user
        WHERE t.kode_tiket = ?
    ");
    $stmt->bind_param("s", $kode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($data = $result->fetch_assoc()) {
        // Cek status dan waktu tayang
        $status = $data['status'];
        $waktu_tayang = strtotime($data['waktu_tayang']);
        $now = time();

        if ($status === 'digunakan') {
            $response['message'] = 'Tiket sudah digunakan.';
        } elseif ($status === 'kedaluwarsa') {
            $response['message'] = 'Tiket sudah kedaluwarsa.';
        } elseif ($status === 'dibatalkan') {
            $response['message'] = 'Tiket dibatalkan.';
        } elseif ($status === 'menunggu') {
            $response['message'] = 'Tiket belum dibayar.';
        } elseif ($status === 'dibayar') {
            if ($waktu_tayang < $now - 7200) { // 2 jam lewat
                $conn->query("UPDATE tiket SET status='kedaluwarsa' WHERE id_tiket = {$data['id_tiket']}");
                $response['message'] = 'Tiket kadaluarsa (melebihi waktu tayang).';
            } else {
                // Simpan ke log checkin
                $log = $conn->prepare("INSERT INTO checkin_log (id_tiket, checkin_by, waktu) VALUES (?, ?, NOW())");
                $log->bind_param("ii", $data['id_tiket'], $_SESSION['id_user']);
                $log->execute();

                // Update status tiket
                $conn->query("UPDATE tiket SET status='digunakan' WHERE id_tiket = {$data['id_tiket']}");

                $response = [
                    'status' => 'success',
                    'message' => 'Check-in berhasil.',
                    'data' => [
                        'judul_film' => $data['judul_film'],
                        'nama' => $data['nama_lengkap'],
                        'kode_tiket' => $data['kode_tiket'],
                        'kursi' => $data['nomor_kursi'],
                        'waktu' => date('d M Y H:i', strtotime($data['waktu_tayang']))
                    ]
                ];
            }
        }
    } else {
        $response['message'] = 'Tiket tidak ditemukan.';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
