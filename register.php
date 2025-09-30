<?php
require_once 'auth.php';
include 'koneksi.php';
require_once 'account_service.php';

if (is_logged_in()) {
    header('Location: profile.php');
    exit;
}

$accountService = null;
if (!$conn->connect_error) {
    $accountService = new AccountService($conn);
}

function password_is_strong(string $password): bool
{
    if (strlen($password) < 8) {
        return false;
    }

    $hasUpper = preg_match('/[A-Z]/', $password);
    $hasLower = preg_match('/[a-z]/', $password);
    $hasDigit = preg_match('/[0-9]/', $password);

    return $hasUpper && $hasLower && $hasDigit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm'] ?? '';
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$message = '';
$messageType = '';
$registerCsrf = csrf_token('register_form');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token('register_form', $_POST['csrf_token'] ?? '')) {
        $message = 'Sesi formulir kedaluwarsa. Muat ulang halaman dan coba lagi.';
        $messageType = 'error';
    } elseif ($name === '' || $email === '' || $password === '') {
        $message = 'Nama, email, dan password wajib diisi.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Format email tidak valid.';
        $messageType = 'error';
    } elseif ($password !== $confirm) {
        $message = 'Konfirmasi password tidak cocok.';
        $messageType = 'error';
    } elseif (!password_is_strong($password)) {
        $message = 'Password minimal 8 karakter dengan kombinasi huruf besar, huruf kecil, dan angka.';
        $messageType = 'error';
    } else {
        // Check if database is available
        if ($conn->connect_error) {
            $message = 'Demo mode: Registrasi tidak tersedia tanpa database. Silakan setup database terlebih dahulu.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $message = 'Email sudah terdaftar.';
                    $messageType = 'error';
                }
                $stmt->close();
            }

            if ($message === '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('sssss', $name, $email, $hash, $phone, $address);
                    if ($stmt->execute()) {
                        $userId = $stmt->insert_id;
                        $user = [
                            'id' => $userId,
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'address' => $address,
                        ];
                        $stmt->close();

                        login_user($user);
                        if ($accountService) {
                            $accountService->registerSession($userId, session_id(), current_session_token(), client_ip_address(), client_user_agent());
                            $accountService->logActivity($userId, 'auth', 'Membuat akun baru', client_ip_address(), client_user_agent());
                        }
                        header('Location: profile.php?status=welcome');
                        exit;
                    }

                    $message = 'Terjadi kesalahan: ' . $stmt->error;
                    $messageType = 'error';
                    $stmt->close();
                } else {
                    $message = 'Terjadi kesalahan: ' . $conn->error;
                    $messageType = 'error';
                }
            }
        }
    }

    $registerCsrf = csrf_token('register_form');
}

$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun</title>
    <style>
        :root {
            color-scheme: light;
            font-family: 'Segoe UI', 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.5;
            --accent: #03ac0e;
            --accent-dark: #027a09;
            --surface: #ffffff;
            --shell: #f6f8fb;
            --text-strong: #0f172a;
            --text-muted: #64748b;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--shell); color: var(--text-strong); }
        .app-shell { display: flex; flex-direction: column; min-height: 100vh; }
        header { background: linear-gradient(135deg, rgba(3,172,14,0.12), rgba(3,172,14,0.05)); border-bottom: 1px solid rgba(2,122,9,0.08); }
        .topbar { max-width: 960px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 20px clamp(16px, 4vw, 32px); }
        .brand { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 22px; letter-spacing: -0.01em; color: var(--accent-dark); }
        nav { display: flex; gap: 12px; }
        nav a { font-weight: 600; font-size: 14px; color: var(--accent-dark); text-decoration: none; }
        main { flex: 1; display: flex; align-items: center; justify-content: center; padding: clamp(32px, 6vw, 56px) 16px; }
        .card { background: var(--surface); border-radius: 24px; padding: clamp(32px, 5vw, 48px); box-shadow: 0 24px 64px rgba(15,23,42,0.12); border: 1px solid rgba(148,163,184,0.14); width: min(480px, 100%); display: grid; gap: 24px; }
        h1 { margin: 0; font-size: clamp(26px, 3vw, 34px); letter-spacing: -0.02em; }
        .message { border-radius: 16px; padding: 14px 18px; font-size: 15px; }
        .message.error { background: rgba(220,38,38,0.12); color: #991b1b; }
        label { font-size: 13px; font-weight: 600; color: var(--accent-dark); text-transform: uppercase; letter-spacing: 0.08em; }
        input, textarea { width: 100%; border: 1px solid rgba(148,163,184,0.35); border-radius: 16px; padding: 14px 18px; background: #f9fafb; font-size: 15px; transition: border 0.2s ease, box-shadow 0.2s ease; }
        textarea { resize: vertical; min-height: 96px; }
        input:focus, textarea:focus { outline: none; border-color: rgba(3,172,14,0.6); box-shadow: 0 0 0 4px rgba(3,172,14,0.16); background: #ffffff; }
        .primary-btn { border: none; border-radius: 18px; padding: 15px 28px; font-size: 15px; font-weight: 600; cursor: pointer; color: #ffffff; background: linear-gradient(135deg, #03ac0e, #02a30d); box-shadow: 0 14px 30px rgba(3,172,14,0.24); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .primary-btn:hover { transform: translateY(-1px); box-shadow: 0 16px 36px rgba(3,172,14,0.28); }
        .form-group { display: grid; gap: 8px; }
        .switch-link { text-align: center; color: var(--text-muted); font-size: 14px; }
        .switch-link a { color: var(--accent-dark); font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
<div class="app-shell">
    <header>
        <div class="topbar">
            <span class="brand">AhRawr Shop</span>
            <nav>
                <a href="view_product.php">Katalog</a>
                <a href="cart.php">Keranjang<?= $cartCount ? ' (' . $cartCount . ')' : '' ?></a>
                <a href="login.php">Masuk</a>
            </nav>
        </div>
    </header>
    <main>
        <div class="card">
            <div>
                <h1>Buat Akun Baru</h1>
                <p style="margin:4px 0 0;color:var(--text-muted);">Nikmati pengalaman belanja personal dengan akun AhRawr Shop.</p>
            </div>
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($registerCsrf) ?>">
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm">Konfirmasi Password</label>
                    <input type="password" id="confirm" name="confirm" required>
                </div>
                <div class="form-group">
                    <label for="phone">No. Telepon</label>
                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>">
                </div>
                <div class="form-group">
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address"><?= htmlspecialchars($address) ?></textarea>
                </div>
                <button type="submit" class="primary-btn">Daftar Sekarang</button>
            </form>
            <div class="switch-link">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
        </div>
    </main>
</div>
</body>
</html>

