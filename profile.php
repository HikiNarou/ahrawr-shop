<?php
require_once 'auth.php';
require_login();
include 'koneksi.php';
require_once 'account_service.php';

$accountService = new AccountService($conn);
$user = current_user();
$userId = (int)$user['id'];

$accountService->touchSession(session_id(), client_ip_address(), client_user_agent());
$freshUser = $accountService->getUserProfile($userId);
if ($freshUser) {
    $user = array_merge($user, $freshUser);
    refresh_session_user($freshUser);
}

$name = $user['name'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';
$address = $user['address'] ?? '';

$message = '';
$messageType = '';
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$activePage = '';

$profileToken = csrf_token('profile_update');
$passwordToken = csrf_token('password_change');
$securityToken = csrf_token('security_action');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        if (!verify_csrf_token('profile_update', $_POST['csrf_token'] ?? '')) {
            $message = 'Sesi formulir kedaluwarsa. Muat ulang halaman dan coba lagi.';
            $messageType = 'error';
        } else {
            $name = $_POST['name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $address = $_POST['address'] ?? '';

            $result = $accountService->updateProfile(
                $userId,
                $name,
                $phone,
                $address,
                client_ip_address(),
                client_user_agent()
            );

            if ($result['success']) {
                $message = $result['message'];
                $messageType = 'success';
                $data = $result['data'];
                $name = $data['name'];
                $phone = $data['phone'];
                $address = $data['address'];
                refresh_session_user([
                    'name' => $name,
                    'phone' => $phone,
                    'address' => $address,
                ]);
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
        $profileToken = csrf_token('profile_update');
    } elseif ($action === 'change_password') {
        if (!verify_csrf_token('password_change', $_POST['csrf_token'] ?? '')) {
            $message = 'Sesi formulir kedaluwarsa. Muat ulang halaman dan coba lagi.';
            $messageType = 'error';
        } else {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $result = $accountService->changePassword(
                $userId,
                $currentPassword,
                $newPassword,
                $confirmPassword,
                client_ip_address(),
                client_user_agent()
            );

            if ($result['success']) {
                $accountService->revokeOtherSessions($userId, session_id(), client_ip_address(), client_user_agent());
                $message = $result['message'] . ' Semua sesi lain telah dikeluarkan.';
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
        $passwordToken = csrf_token('password_change');
    } elseif ($action === 'revoke_session') {
        if (!verify_csrf_token('security_action', $_POST['csrf_token'] ?? '')) {
            $message = 'Sesi keamanan kedaluwarsa. Muat ulang halaman dan coba lagi.';
            $messageType = 'error';
        } else {
            $targetSession = $_POST['session_id'] ?? '';
            if ($targetSession === session_id()) {
                $accountService->revokeSession($userId, $targetSession, client_ip_address(), client_user_agent());
                logout_user();
                header('Location: login.php');
                exit;
            }

            if ($accountService->revokeSession($userId, $targetSession, client_ip_address(), client_user_agent())) {
                $message = 'Sesi perangkat berhasil dikeluarkan.';
                $messageType = 'success';
            } else {
                $message = 'Tidak dapat menghapus sesi yang dipilih.';
                $messageType = 'error';
            }
        }
        $securityToken = csrf_token('security_action');
    } elseif ($action === 'revoke_others') {
        if (!verify_csrf_token('security_action', $_POST['csrf_token'] ?? '')) {
            $message = 'Sesi keamanan kedaluwarsa. Muat ulang halaman dan coba lagi.';
            $messageType = 'error';
        } else {
            $count = $accountService->revokeOtherSessions($userId, session_id(), client_ip_address(), client_user_agent());
            if ($count > 0) {
                $message = 'Berhasil mengeluarkan ' . $count . ' sesi lainnya.';
                $messageType = 'success';
            } else {
                $message = 'Tidak ada sesi lain yang ditemukan.';
                $messageType = 'error';
            }
        }
        $securityToken = csrf_token('security_action');
    }
}

$activeSessions = $accountService->getActiveSessions($userId);
$recentActivities = $accountService->getRecentActivities($userId, 8);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Akun</title>
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
        header { background: linear-gradient(120deg, #f0fdf4 0%, #dcfce7 100%); border-bottom: 1px solid rgba(2, 122, 9, 0.08); }
        .topbar { max-width: 960px; margin: 0 auto; padding: clamp(16px, 3vw, 24px) clamp(16px, 4vw, 32px); display: flex; align-items: center; gap: clamp(16px, 4vw, 48px); }
        .brand { display: inline-flex; align-items: center; gap: 12px; text-decoration: none; }
        .brand-logo { width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #03ac0e, #04be15); color: #ffffff; font-weight: 700; font-size: 15px; display: flex; align-items: center; justify-content: center; letter-spacing: 0.04em; }
        .brand-text { display: flex; flex-direction: column; line-height: 1.1; }
        .brand-text strong { font-size: 16px; color: var(--accent-dark); }
        .brand-text span { font-size: 11px; color: var(--text-muted); letter-spacing: 0.16em; text-transform: uppercase; }
        .nav-bar { flex: 1; display: flex; gap: 6px; background: rgba(255, 255, 255, 0.92); border-radius: 999px; padding: 5px; border: 1px solid rgba(3, 172, 14, 0.12); box-shadow: 0 8px 18px rgba(3, 172, 14, 0.06); }
        .nav-bar .nav-link { position: relative; display: inline-flex; align-items: center; gap: 4px; padding: 6px 14px; border-radius: 999px; font-weight: 600; color: var(--text-muted); text-decoration: none; font-size: 13px; transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease; }
        .nav-bar .nav-link:hover { color: var(--accent-dark); background: rgba(3, 172, 14, 0.08); }
        .nav-bar .nav-link.active { background: linear-gradient(135deg, #03ac0e, #02a30d); color: #ffffff; box-shadow: 0 6px 14px rgba(3, 172, 14, 0.18); }
        .nav-count { min-width: 18px; height: 18px; border-radius: 9px; background: rgba(255, 255, 255, 0.9); color: var(--accent-dark); font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; padding: 0 5px; }
        .account-area { display: flex; align-items: center; gap: 8px; margin-left: auto; }
        .account-mini { display: inline-flex; align-items: center; gap: 6px; padding: 5px 7px; border-radius: 999px; border: 1px solid rgba(148, 163, 184, 0.18); background: rgba(255, 255, 255, 0.96); box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08); }
        .account-avatar { width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #03ac0e, #06c715); color: #ffffff; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        .icon-btn { width: 28px; height: 28px; border-radius: 50%; border: 1px solid rgba(3, 172, 14, 0.22); background: #ffffff; color: var(--accent-dark); display: inline-flex; align-items: center; justify-content: center; font-size: 14px; text-decoration: none; transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease; }
        .icon-btn:hover { background: rgba(3, 172, 14, 0.08); box-shadow: 0 4px 10px rgba(3, 172, 14, 0.12); }
        .icon-btn.danger { border-color: rgba(220, 38, 38, 0.25); color: #dc2626; }
        .icon-btn.danger:hover { background: rgba(220, 38, 38, 0.1); box-shadow: 0 4px 10px rgba(220, 38, 38, 0.12); }
        main { display: grid; gap: 24px; max-width: 960px; margin: clamp(24px, 6vw, 48px) auto; padding: 0 clamp(16px, 4vw, 32px); }
        .card { background: var(--surface); border-radius: 24px; padding: clamp(28px, 4vw, 40px); box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08); border: 1px solid rgba(148, 163, 184, 0.14); display: grid; gap: 24px; }
        h1 { margin: 0; font-size: clamp(26px, 3vw, 34px); letter-spacing: -0.02em; }
        h2 { margin: 0; font-size: 20px; letter-spacing: -0.01em; }
        .message { border-radius: 16px; padding: 14px 18px; font-size: 15px; }
        .message.success { background: rgba(3, 172, 14, 0.12); color: var(--accent-dark); }
        .message.error { background: rgba(220, 38, 38, 0.12); color: #991b1b; }
        label { font-size: 13px; font-weight: 600; color: var(--accent-dark); text-transform: uppercase; letter-spacing: 0.08em; }
        input, textarea { width: 100%; border: 1px solid rgba(148, 163, 184, 0.35); border-radius: 16px; padding: 14px 18px; background: #f9fafb; font-size: 15px; transition: border 0.2s ease, box-shadow 0.2s ease; }
        textarea { resize: vertical; min-height: 96px; }
        input:focus, textarea:focus { outline: none; border-color: rgba(3, 172, 14, 0.6); box-shadow: 0 0 0 4px rgba(3, 172, 14, 0.16); background: #ffffff; }
        .primary-btn { border: none; border-radius: 18px; padding: 15px 28px; font-size: 15px; font-weight: 600; cursor: pointer; color: #ffffff; background: linear-gradient(135deg, #03ac0e, #02a30d); box-shadow: 0 14px 30px rgba(3, 172, 14, 0.24); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .primary-btn:hover { transform: translateY(-1px); box-shadow: 0 16px 36px rgba(3, 172, 14, 0.28); }
        .ghost-btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; border-radius: 14px; border: 1px solid rgba(3, 172, 14, 0.22); background: rgba(255, 255, 255, 0.95); font-weight: 600; font-size: 13px; color: var(--accent-dark); cursor: pointer; transition: background 0.2s ease, box-shadow 0.2s ease; }
        .ghost-btn:hover { background: rgba(3, 172, 14, 0.08); box-shadow: 0 12px 24px rgba(3, 172, 14, 0.18); }
        .form-grid { display: grid; gap: 18px; }
        .split-grid { display: grid; gap: 24px; }
        .split-grid.two { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        .security-list { display: grid; gap: 12px; }
        .session-card { padding: 16px; border-radius: 18px; border: 1px solid rgba(148, 163, 184, 0.18); background: rgba(255, 255, 255, 0.96); display: grid; gap: 6px; box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08); }
        .session-meta { display: flex; flex-wrap: wrap; gap: 10px; font-size: 13px; color: var(--text-muted); }
        .session-actions { display: flex; align-items: center; gap: 10px; justify-content: flex-end; }
        .activity-list { display: grid; gap: 12px; }
        .activity-item { padding: 12px 16px; border-radius: 16px; background: rgba(255, 255, 255, 0.94); border: 1px solid rgba(148, 163, 184, 0.16); box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.04); }
        .activity-item span { display: block; font-size: 13px; color: var(--text-muted); }
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: rgba(3, 172, 14, 0.12); color: var(--accent-dark); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
        @media (max-width: 720px) {
            .topbar { flex-wrap: wrap; }
            .nav-bar { order: 3; width: 100%; }
            .account-area { order: 2; width: 100%; justify-content: flex-end; }
            .brand { order: 1; }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <header>
        <div class="topbar">
            <a class="brand" href="view_product.php">
                <span class="brand-logo">AR</span>
                <span class="brand-text">
                    <strong>AhRawr Shop</strong>
                    <span>Marketplace</span>
                </span>
            </a>
            <nav class="nav-bar">
                <a class="nav-link <?= $activePage === 'catalog' ? 'active' : '' ?>" href="view_product.php">Katalog</a>
                <a class="nav-link <?= $activePage === 'add' ? 'active' : '' ?>" href="add_product.php">Tambah Produk</a>
                <a class="nav-link <?= $activePage === 'cart' ? 'active' : '' ?>" href="cart.php">Keranjang<?php if ($cartCount) : ?><span class="nav-count"><?= $cartCount ?></span><?php endif; ?></a>
            </nav>
            <div class="account-area">
                <?php $initial = strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
                <div class="account-mini">
                    <span class="account-avatar" title="Akun">
                        <?= $initial ?>
                    </span>
                    <a class="icon-btn" href="profile.php" title="Profil">?</a>
                    <a class="icon-btn danger" href="logout.php" onclick="return confirm('Keluar dari sesi?');" title="Keluar">?</a>
                </div>
            </div>
        </div>
    </header>
    <main>
        <div class="card">
            <div class="split-grid">
                <div>
                    <h1>Profil Akun</h1>
                    <p style="margin:4px 0 0;color:var(--text-muted);">Perbarui informasi pribadi dan kelola kredensial akun Anda.</p>
                </div>
                <span class="badge">Akun Terverifikasi</span>
            </div>
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($profileToken) ?>">
                <div>
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" value="<?= htmlspecialchars($email) ?>" disabled>
                </div>
                <div>
                    <label for="phone">No. Telepon</label>
                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>">
                </div>
                <div>
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address"><?= htmlspecialchars($address) ?></textarea>
                </div>
                <button type="submit" class="primary-btn">Simpan Perubahan</button>
            </form>
        </div>
        <div class="card">
            <div class="split-grid">
                <div>
                    <h2>Keamanan Password</h2>
                    <p style="margin:4px 0 0;color:var(--text-muted);">Gunakan password kuat dan unik untuk melindungi akun.</p>
                </div>
            </div>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($passwordToken) ?>">
                <div>
                    <label for="current_password">Password Saat Ini</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div>
                    <label for="new_password">Password Baru</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div>
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="primary-btn">Perbarui Password</button>
            </form>
        </div>
        <div class="card">
            <div class="split-grid">
                <div>
                    <h2>Sesi Aktif</h2>
                    <p style="margin:4px 0 0;color:var(--text-muted);">Kelola perangkat yang sedang masuk menggunakan akun Anda.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="revoke_others">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($securityToken) ?>">
                    <button type="submit" class="ghost-btn">Keluarkan Semua Sesi Lain</button>
                </form>
            </div>
            <div class="security-list">
                <?php foreach ($activeSessions as $session): ?>
                    <?php $isCurrent = $session['session_id'] === session_id(); ?>
                    <div class="session-card">
                        <strong><?= $isCurrent ? 'Sesi ini' : 'Perangkat lain' ?></strong>
                        <div class="session-meta">
                            <span>IP: <?= htmlspecialchars($session['ip_address']) ?></span>
                            <span>Terakhir aktif: <?= htmlspecialchars(date('d M Y H:i', strtotime($session['last_seen_at']))) ?></span>
                            <span>Masuk: <?= htmlspecialchars(date('d M Y H:i', strtotime($session['created_at']))) ?></span>
                        </div>
                        <span style="font-size:13px;color:var(--text-muted);"><?= htmlspecialchars($session['user_agent']) ?></span>
                        <?php if (!$isCurrent): ?>
                            <div class="session-actions">
                                <form method="POST">
                                    <input type="hidden" name="action" value="revoke_session">
                                    <input type="hidden" name="session_id" value="<?= htmlspecialchars($session['session_id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($securityToken) ?>">
                                    <button type="submit" class="ghost-btn">Keluarkan Perangkat</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span class="badge" style="background:rgba(59,130,246,0.12);color:#1d4ed8;">Aktif</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($activeSessions)): ?>
                    <p style="margin:0;color:var(--text-muted);">Belum ada sesi yang tercatat.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="split-grid">
                <div>
                    <h2>Aktivitas Terbaru</h2>
                    <p style="margin:4px 0 0;color:var(--text-muted);">Pantau perubahan penting pada akun Anda.</p>
                </div>
            </div>
            <div class="activity-list">
                <?php foreach ($recentActivities as $activity): ?>
                    <div class="activity-item">
                        <strong><?= htmlspecialchars(ucfirst($activity['category'])) ?></strong>
                        <span><?= htmlspecialchars($activity['message']) ?></span>
                        <span>IP <?= htmlspecialchars($activity['ip_address']) ?> • <?= htmlspecialchars(date('d M Y H:i', strtotime($activity['created_at']))) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($recentActivities)): ?>
                    <p style="margin:0;color:var(--text-muted);">Belum ada aktivitas yang tercatat.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
