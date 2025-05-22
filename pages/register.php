<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('../config/koneksi.php');

$success = '';
$error = '';
$nama_lengkap = '';
$username = '';
$email = '';

// Generate CAPTCHA hanya saat GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $angka1 = rand(1, 9);
  $angka2 = rand(1, 9);
  $_SESSION['captcha'] = (string)($angka1 + $angka2);
}

// Validasi form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $captcha_user = $_POST['captcha'] ?? '';
    $captcha_sess = $_SESSION['captcha'] ?? '';

    if ($captcha_user !== $captcha_sess) {
        $error = 'Captcha salah!';
        $nama_lengkap = $username = $email = '';
    } else {
        $cek = $conn->prepare("SELECT id_user FROM users WHERE username = ? OR email = ?");
        $cek->bind_param("ss", $username, $email);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = 'Username atau email sudah digunakan.';
            $nama_lengkap = $username = $email = '';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, username, email, password, role, created_at) VALUES (?, ?, ?, ?, 'user', NOW())");
            $stmt->bind_param("ssss", $nama_lengkap, $username, $email, $password);
            if ($stmt->execute()) {
                $success = 'Berhasil mendaftar. <a href=\"login.php\" class=\"text-purple-600 underline\">Login sekarang</a>.';
            } else {
                $error = 'Terjadi kesalahan saat menyimpan data.';
            }
            $nama_lengkap = $username = $email = '';
        }
    }
}

if (!isset($angka1)) $angka1 = rand(1, 9);
if (!isset($angka2)) $angka2 = $_SESSION['captcha'] - $angka1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    setTimeout(() => {
      const msg = document.getElementById('alert-message');
      if (msg) msg.style.display = 'none';
    }, 5000);

    function togglePassword() {
      const field = document.getElementById('password');
      const icon = document.getElementById('toggle-icon');
      if (field.type === 'password') {
        field.type = 'text';
        icon.textContent = '🙈';
      } else {
        field.type = 'password';
        icon.textContent = '👁';
      }
    }
  </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
    <h1 class="text-2xl font-bold text-purple-700 mb-4 text-center">📝 Daftar Akun</h1>

    <?php if ($success): ?>
      <div id="alert-message" class="bg-green-100 text-green-700 px-4 py-2 mb-4 rounded">
        <?= $success ?>
      </div>
    <?php elseif ($error): ?>
      <div id="alert-message" class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
        <input type="text" name="nama_lengkap" required
               class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
               value="">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Username</label>
        <input type="text" name="username" required
               class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
               value="">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" required
               class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
               value="">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Password</label>
        <div class="relative">
          <input type="password" name="password" id="password" required
                 class="w-full p-2 pr-10 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
          <button type="button" onclick="togglePassword()" class="absolute right-2 top-2 text-lg" id="toggle-icon">👁</button>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Berapa hasil <?= $angka1 ?> + <?= $angka2 ?> ?</label>
        <input type="text" name="captcha" required
               class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
      </div>
      <button type="submit"
              class="w-full bg-purple-600 text-white py-2 rounded hover:bg-purple-700">
        Daftar
      </button>
    </form>

    <p class="text-sm text-center mt-4 text-gray-600">
      Sudah punya akun?
      <a href="login.php" class="text-purple-600 hover:underline">Login di sini</a>
    </p>
  </div>
</body>
</html>
