<?php
// File: /config/database_setup.php
// Jalankan ini sekali untuk setup prosedur, view, dan trigger

require_once('koneksi.php');

// Stored Procedure 1: Tambah Film
$conn->query("DROP PROCEDURE IF EXISTS sp_tambah_film");
$conn->query(<<<SQL
CREATE PROCEDURE sp_tambah_film (
    IN p_judul VARCHAR(100),
    IN p_genre VARCHAR(50),
    IN p_durasi INT,
    IN p_deskripsi TEXT,
    IN p_poster VARCHAR(100)
)
BEGIN
    INSERT INTO film (judul_film, genre, durasi, deskripsi, poster)
    VALUES (p_judul, p_genre, p_durasi, p_deskripsi, p_poster);
END
SQL);

// Stored Procedure 2: Tambah Studio & Kursi (Loop)
$conn->query("DROP PROCEDURE IF EXISTS sp_tambah_studio_dan_kursi");
$conn->query(<<<SQL
CREATE PROCEDURE sp_tambah_studio_dan_kursi (
    IN p_id_bioskop INT,
    IN p_nama_studio VARCHAR(50),
    IN p_jumlah_baris INT,
    IN p_kursi_per_baris INT
)
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE j INT;
    DECLARE huruf CHAR(1);
    DECLARE new_id_studio INT;

    INSERT INTO studio (id_bioskop, nama_studio) 
    VALUES (p_id_bioskop, p_nama_studio);

    SET new_id_studio = LAST_INSERT_ID();

    WHILE i <= p_jumlah_baris DO
        SET j = 1;
        SET huruf = CHAR(64 + i);

        WHILE j <= p_kursi_per_baris DO
            INSERT INTO kursi (id_studio, no_kursi)
            VALUES (new_id_studio, CONCAT(huruf, j));
            SET j = j + 1;
        END WHILE;

        SET i = i + 1;
    END WHILE;
END
SQL);

// Stored Procedure 3: Buat Jadwal
$conn->query("DROP PROCEDURE IF EXISTS sp_buat_jadwal");
$conn->query(<<<SQL
CREATE PROCEDURE sp_buat_jadwal (
    IN p_id_film INT,
    IN p_id_studio INT,
    IN p_waktu DATETIME,
    IN p_harga INT
)
BEGIN
    INSERT INTO jadwal (id_film, id_studio, waktu_tayang, harga)
    VALUES (p_id_film, p_id_studio, p_waktu, p_harga);
END
SQL);

// Stored Procedure 4: Buat Tiket (loop string split manual diganti di PHP saat eksekusi)
$conn->query("DROP PROCEDURE IF EXISTS sp_update_status_tiket");
$conn->query(<<<SQL
CREATE PROCEDURE sp_update_status_tiket (
    IN p_id_tiket INT,
    IN p_status VARCHAR(20)
)
BEGIN
    UPDATE tiket
    SET status = p_status
    WHERE id_tiket = p_id_tiket;
END
SQL);

// View: Ringkasan Dashboard
$conn->query("DROP VIEW IF EXISTS view_dashboard_summary");
$conn->query(<<<SQL
CREATE VIEW view_dashboard_summary AS
SELECT 
    (SELECT COUNT(*) FROM film) AS total_film,
    (SELECT COUNT(*) FROM studio) AS total_studio,
    (SELECT COUNT(*) FROM bioskop) AS total_bioskop,
    (SELECT COUNT(*) FROM jadwal) AS total_jadwal,
    (SELECT COUNT(*) FROM users WHERE role = 'user') AS total_pengguna,
    (SELECT COUNT(*) FROM tiket WHERE status = 'dibayar') AS tiket_terjual,
    (SELECT SUM(j.harga) FROM tiket t JOIN jadwal j ON t.id_jadwal = j.id_jadwal WHERE t.status = 'dibayar') AS total_pendapatan;
SQL);

// Trigger log aktivitas insert tiket
$conn->query("DROP TRIGGER IF EXISTS tr_after_insert_tiket");
$conn->query(<<<SQL
CREATE TRIGGER tr_after_insert_tiket
AFTER INSERT ON tiket
FOR EACH ROW
BEGIN
    INSERT INTO log_aktivitas (id_user, aksi, deskripsi, waktu)
    VALUES (NEW.id_user, 'INSERT', CONCAT('Memesan tiket kursi ', NEW.nomor_kursi), NOW());
END
SQL);

echo "✅ Semua stored procedure, view, dan trigger berhasil dibuat.";
?>
