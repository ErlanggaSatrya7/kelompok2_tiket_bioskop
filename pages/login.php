<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('../config/koneksi.php');
require_once('../config/auth.php');

$error = '';
$username = '';
$remembered = false;

// Ambil cookie jika tersedia
if (isset($_COOKIE['ingat_username']) && isset($_COOKIE['ingat_password'])) {
    $username = $_COOKIE['ingat_username'];
    $remembered = true;
}

// Generate CAPTCHA hanya saat GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $angka1 = rand(1, 9);
    $angka2 = rand(1, 9);
    $_SESSION['captcha'] = (string)($angka1 + $angka2);
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_input = trim($_POST['username'] ?? '');
    $password_input = $_POST['password'] ?? '';
    $captcha_input  = $_POST['captcha'] ?? '';
    $captcha_session = $_SESSION['captcha'] ?? '';

    if ($captcha_input !== $captcha_session) {
        $error = 'Captcha salah!';
    } else {
        $stmt = $conn->prepare("SELECT id_user, nama_lengkap, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ($password_input === $user['password']) {
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];

                if (isset($_POST['ingat'])) {
                    setcookie('ingat_username', $username_input, time() + (7 * 24 * 60 * 60), '/');
                    setcookie('ingat_password', $password_input, time() + (7 * 24 * 60 * 60), '/');
                } else {
                    setcookie('ingat_username', '', time() - 3600, '/');
                    setcookie('ingat_password', '', time() - 3600, '/');
                }

                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } elseif ($user['role'] === 'operator') {
                    header("Location: ../operator/dashboard.php");
                } else {
                    header("Location: beranda.php");
                }
                exit;
            }
        }
        $error = 'Username atau password salah!';
    }

    $username = '';
    $_POST = [];
}

if (!isset($angka1)) $angka1 = rand(1, 9);
if (!isset($angka2)) $angka2 = $_SESSION['captcha'] - $angka1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login | JATIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    setTimeout(() => {
      const alert = document.getElementById('error-alert');
      if (alert) alert.style.display = 'none';
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
  <div class="w-full max-w-md bg-white p-8 rounded shadow">
    <h1 class="text-2xl font-bold text-purple-700 mb-4 text-center">🎟 Login JATIX</h1>

    <?php if ($error): ?>
      <div id="error-alert" class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm mb-1 font-medium text-gray-700">Username</label>
        <input type="text" name="username" required
               class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
               value="">
      </div>
      <div>
        <label class="block text-sm mb-1 font-medium text-gray-700">Password</label>
        <div class="relative">
          <input type="password" name="password" id="password" required
                 class="w-full p-2 pr-10 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
                 value="">
          <button type="button" onclick="togglePassword()" class="absolute right-2 top-2 text-lg" id="toggle-icon">👁</button>
        </div>
      </div>

      <div>
        <label class="block text-sm mb-1 font-medium text-gray-700">Berapa hasil <?= $angka1 ?> + <?= $angka2 ?> ?</label>
        <input type="text" name="captcha" required
               class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
      </div>

      <div class="flex items-center gap-2">
        <input type="checkbox" name="ingat" id="ingat" <?= $remembered ? 'checked' : '' ?>>
        <label for="ingat" class="text-sm text-gray-700">Ingat Saya</label>
      </div>

      <button type="submit"
              class="w-full bg-purple-600 text-white py-2 rounded hover:bg-purple-700">
        Masuk
      </button>
    </form>

    <p class="text-sm text-center mt-4 text-gray-600">
      Belum punya akun?
      <a href="register.php" class="text-purple-600 hover:underline">Daftar sekarang</a>
    </p>
  </div>
</body>
</html>
