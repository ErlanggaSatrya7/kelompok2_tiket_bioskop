<?php
require_once('../config/koneksi.php');
require '../vendor/autoload.php'; // Dompdf via Composer

use Dompdf\Dompdf;

// Validasi input
$tanggal_awal = $_POST['tanggal_awal'] ?? '';
$tanggal_akhir = $_POST['tanggal_akhir'] ?? '';

if (!$tanggal_awal || !$tanggal_akhir) {
    die("Tanggal tidak valid.");
}

// Ambil data dari database
$query = "SELECT tiket.id_tiket, film.judul, studio.nama_studio, bioskop.nama_bioskop, 
                 jadwal.waktu_tayang, tiket.waktu_pesan, tiket.harga 
          FROM tiket 
          JOIN jadwal ON tiket.id_jadwal = jadwal.id_jadwal 
          JOIN film ON jadwal.id_film = film.id_film 
          JOIN studio ON jadwal.id_studio = studio.id_studio 
          JOIN bioskop ON studio.id_bioskop = bioskop.id_bioskop 
          WHERE tiket.waktu_pesan BETWEEN ? AND ? 
          ORDER BY tiket.waktu_pesan ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $tanggal_awal, $tanggal_akhir);
$stmt->execute();
$result = $stmt->get_result();

$html = '
<h2 style="text-align:center;">Laporan Penjualan Tiket</h2>
<p style="text-align:center;">Periode: ' . htmlspecialchars($tanggal_awal) . ' s/d ' . htmlspecialchars($tanggal_akhir) . '</p>
<table border="1" cellspacing="0" cellpadding="5" width="100%">
    <thead>
        <tr>
            <th>ID Tiket</th>
            <th>Judul Film</th>
            <th>Studio</th>
            <th>Bioskop</th>
            <th>Waktu Tayang</th>
            <th>Waktu Pesan</th>
            <th>Harga</th>
        </tr>
    </thead>
    <tbody>';

$total = 0;
while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . $row['id_tiket'] . '</td>
                <td>' . htmlspecialchars($row['judul']) . '</td>
                <td>' . htmlspecialchars($row['nama_studio']) . '</td>
                <td>' . htmlspecialchars($row['nama_bioskop']) . '</td>
                <td>' . $row['waktu_tayang'] . '</td>
                <td>' . $row['waktu_pesan'] . '</td>
                <td>Rp' . number_format($row['harga'], 0, ',', '.') . '</td>
            </tr>';
    $total += $row['harga'];
}

$html .= '<tr>
            <td colspan="6" style="text-align:right;"><strong>Total</strong></td>
            <td><strong>Rp' . number_format($total, 0, ',', '.') . '</strong></td>
          </tr>';

$html .= '</tbody></table>';

// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('laporan_tiket_' . $tanggal_awal . '_sd_' . $tanggal_akhir . '.pdf', ['Attachment' => false]);
