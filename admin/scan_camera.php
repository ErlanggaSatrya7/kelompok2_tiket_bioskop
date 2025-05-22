<?php
session_start();
if (!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Scan Tiket | JATIX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.7"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }


        #reader {
            width: 100%;
            height: 400px !important;
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-800 flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg p-6 fixed h-full">
        <h2 class="text-2xl font-bold text-purple-700 mb-6 flex items-center gap-2">
            <i data-lucide="scan-line"></i> JATIX Admin
        </h2>
        <nav class="space-y-2 text-sm">
            <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="layout-dashboard"></i> Dashboard</a>
            <a href="bioskop.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="building"></i> Kelola Bioskop</a>
            <a href="studio.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="columns"></i> Kelola Studio</a>
            <a href="film.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="video"></i> Kelola Film</a>
            <a href="jadwal.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="calendar-clock"></i> Jadwal Tayang</a>
            <a href="data_user.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="users"></i> Data Pengguna</a>
            <a href="data_tiket.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="ticket"></i> Data Tiket</a>
            <a href="laporan.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="file-text"></i> Laporan</a>
            <a href="log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="history"></i> Log Aktivitas</a>
            <a href="scan_camera.php" class="flex items-center gap-2 px-3 py-2 rounded bg-purple-100 text-purple-700 font-semibold"><i data-lucide="scan-line"></i> Scan QR</a>
            <a href="checkin_log.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-200"><i data-lucide="check"></i> Log Check-in</a>
            <a href="../pages/logout.php" class="flex items-center gap-2 px-3 py-2 mt-4 rounded bg-red-100 text-red-700 hover:bg-red-200"><i data-lucide="log-out"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 p-8 w-full">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="scan"></i> Scan Tiket QR</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded shadow p-4">
                <div id="reader" class="rounded border" style="width: 100%; height: 300px;"></div>
            </div>
            <div class="bg-white rounded shadow p-6" id="result">
                <p class="text-gray-500 italic">Arahkan kamera ke QR code tiket untuk melihat detail.</p>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        const resultContainer = document.getElementById('result');

        function onScanSuccess(decodedText) {
            if (!decodedText) return;
            html5QrScanner.clear();

            fetch(`check_qr.php?kode_qr=${encodeURIComponent(decodedText)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const t = data.data;
                        resultContainer.innerHTML = `
                        <div class="text-green-700">
                            <h2 class="text-xl font-bold mb-2 flex items-center gap-2"><i data-lucide="check-circle"></i> Tiket Valid</h2>
                            <div class="grid grid-cols-1 gap-1 text-sm">
                                <p><strong>Nama:</strong> ${t.nama_lengkap}</p>
                                <p><strong>Film:</strong> ${t.judul_film}</p>
                                <p><strong>Bioskop:</strong> ${t.nama_bioskop}</p>
                                <p><strong>Studio:</strong> ${t.nama_studio}</p>
                                <p><strong>Kursi:</strong> ${t.nomor_kursi}</p>
                                <p><strong>Jadwal:</strong> ${t.waktu_tayang}</p>
                                <p><strong>Status:</strong> <span class="font-semibold">${t.status}</span></p>
                                <p><strong>Check-in:</strong> ${t.updated_at || '-'}</p>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <button onclick="window.location.reload()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">
                                🔄 Scan Tiket Lain
                            </button>
                        </div>
                    `;
                        lucide.createIcons();
                    } else {
                        resultContainer.innerHTML = `
                        <div class="text-red-700">
                            <h2 class="text-xl font-bold mb-2 flex items-center gap-2"><i data-lucide="x-circle"></i> Tiket Tidak Valid</h2>
                            <p>${data.message}</p>
                        </div>
                        <div class="mt-4 text-center">
                            <button onclick="window.location.reload()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">
                                🔄 Scan Ulang
                            </button>
                        </div>
                    `;
                        lucide.createIcons();
                    }
                });
        }

        const html5QrScanner = new Html5Qrcode("reader");
        html5QrScanner.start({
                facingMode: "environment"
            }, {
                fps: 10,
                qrbox: 400
            },
            onScanSuccess
        );
    </script>
</body>

</html>