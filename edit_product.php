<?php
require_once 'auth.php';
require_login();
include 'koneksi.php';

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = '';
$messageType = '';
$name = '';
$description = '';
$priceInput = '';
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$user = current_user();
$activePage = 'add';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priceInput = trim($_POST['price'] ?? '');

    $priceValue = filter_var($priceInput, FILTER_VALIDATE_FLOAT);
    if ($priceValue === false) {
        $message = 'Harga tidak valid. Gunakan angka saja.';
        $messageType = 'error';
    }

    $imageName = '';
    $targetPath = '';

    if ($message === '') {
        if (!empty($_FILES['image']['name'])) {
            $originalName = basename($_FILES['image']['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if ($extension && !in_array($extension, $allowed, true)) {
                $message = 'Format gambar harus JPG, JPEG, PNG, atau WEBP.';
                $messageType = 'error';
            } else {
                $imageName = uniqid('product_', true) . ($extension ? '.' . $extension : '');
                $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $imageName;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $message = 'Gagal mengunggah gambar. Coba lagi.';
                    $messageType = 'error';
                }
            }
        } else {
            $message = 'Gambar produk wajib diunggah.';
            $messageType = 'error';
        }
    }

    if ($message === '') {
        $stmt = $conn->prepare('INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('ssds', $name, $description, $priceValue, $imageName);
            if ($stmt->execute()) {
                $message = 'Produk berhasil ditambahkan.';
                $messageType = 'success';
                $name = '';
                $description = '';
                $priceInput = '';
            } else {
                $message = 'Terjadi kesalahan: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Terjadi kesalahan: ' . $conn->error;
            $messageType = 'error';
        }

        if ($messageType !== 'success' && $imageName && $targetPath && file_exists($targetPath)) {
            unlink($targetPath);
        }
    } elseif ($imageName && $targetPath && file_exists($targetPath)) {
        unlink($targetPath);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Produk</title>
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
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--shell);
            color: var(--text-strong);
        }
        header {
            background: linear-gradient(120deg, #f0fdf4 0%, #dcfce7 100%);
            border-bottom: 1px solid rgba(2, 122, 9, 0.08);
        }
        .topbar {
            max-width: 1120px;
            margin: 0 auto;
            padding: clamp(16px, 3vw, 24px) clamp(16px, 4vw, 32px);
            display: flex;
            align-items: center;
            gap: clamp(16px, 4vw, 48px);
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .brand-logo {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #03ac0e, #04be15);
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.04em;
        }
        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }
        .brand-text strong {
            font-size: 16px;
            color: var(--accent-dark);
        }
        .brand-text span {
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }
        .nav-bar {
            flex: 1;
            display: flex;
            gap: 6px;
            background: rgba(255, 255, 255, 0.92);
            border-radius: 999px;
            padding: 5px;
            border: 1px solid rgba(3, 172, 14, 0.12);
            box-shadow: 0 8px 18px rgba(3, 172, 14, 0.06);
        }
        .nav-bar .nav-link {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 14px;
            border-radius: 999px;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        }
        .nav-bar .nav-link:hover {
            color: var(--accent-dark);
            background: rgba(3, 172, 14, 0.08);
        }
        .nav-bar .nav-link.active {
            background: linear-gradient(135deg, #03ac0e, #02a30d);
            color: #ffffff;
            box-shadow: 0 6px 14px rgba(3, 172, 14, 0.18);
        }
        .nav-count {
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--accent-dark);
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
        }
        .account-area {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }
        .account-mini {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 7px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
        }
        .account-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #03ac0e, #06c715);
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        .icon-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 1px solid rgba(3, 172, 14, 0.22);
            background: #ffffff;
            color: var(--accent-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            text-decoration: none;
            transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        }
        .icon-btn:hover {
            background: rgba(3, 172, 14, 0.08);
            box-shadow: 0 4px 10px rgba(3, 172, 14, 0.12);
        }
        .icon-btn.danger {
            border-color: rgba(220, 38, 38, 0.25);
            color: #dc2626;
        }
        .icon-btn.danger:hover {
            background: rgba(220, 38, 38, 0.1);
            box-shadow: 0 4px 10px rgba(220, 38, 38, 0.12);
        }
        .auth-mini {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .auth-mini .icon-btn.primary {
            border: none;
            background: linear-gradient(135deg, #03ac0e, #02a30d);
            color: #ffffff;
            box-shadow: 0 8px 16px rgba(3, 172, 14, 0.18);
        }
        .auth-mini .icon-btn.primary:hover {
            box-shadow: 0 10px 20px rgba(3, 172, 14, 0.24);
        }
        @media (max-width: 900px) {
            .nav-bar { order: 3; width: 100%; }
            .account-area { order: 2; width: 100%; justify-content: flex-end; }
            .brand { order: 1; }
        }
        @media (max-width: 640px) {
            .topbar { flex-wrap: wrap; }
            .nav-bar { justify-content: flex-start; }
        }
        main {
            flex: 1;
            padding: clamp(32px, 6vw, 56px) clamp(16px, 4vw, 32px) 64px;
        }
        .content {
            max-width: 960px;
            margin: 0 auto;
            display: grid;
            gap: 24px;
        }
        .page-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .page-title h1 {
            margin: 0;
            font-size: clamp(26px, 3vw, 34px);
            letter-spacing: -0.02em;
            color: var(--text-strong);
        }
        .page-title p {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: 15px;
        }
        .card {
            background: var(--surface);
            border-radius: 20px;
            padding: clamp(28px, 4vw, 40px);
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(148, 163, 184, 0.12);
        }
        .form-grid {
            display: grid;
            gap: 24px;
        }
        .field {
            display: grid;
            grid-template-columns: minmax(160px, 200px) minmax(0, 1fr);
            align-items: start;
            gap: 20px;
        }
        label {
            font-size: 13px;
            font-weight: 600;
            color: var(--accent-dark);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .control { display: grid; gap: 10px; }
        textarea,
        input[type="text"],
        input[type="number"],
        input[type="file"] {
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 16px;
            padding: 14px 18px;
            background: #f9fafb;
            transition: border 0.2s ease, box-shadow 0.2s ease;
            font-size: 15px;
            color: var(--text-strong);
        }
        textarea { resize: vertical; min-height: 140px; }
        input:focus,
        textarea:focus {
            outline: none;
            border-color: rgba(3, 172, 14, 0.6);
            box-shadow: 0 0 0 4px rgba(3, 172, 14, 0.16);
            background: #ffffff;
        }
        input[type="file"] { padding: 10px 14px; }
        .message {
            border-radius: 16px;
            padding: 14px 20px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .message.success {
            background: rgba(3, 172, 14, 0.12);
            color: var(--accent-dark);
        }
        .message.error {
            background: rgba(220, 38, 38, 0.12);
            color: #991b1b;
        }
        .actions {
            display: flex;
            justify-content: flex-end;
        }
        .primary-btn {
            border: none;
            border-radius: 18px;
            padding: 15px 28px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            color: #ffffff;
            background: linear-gradient(135deg, #03ac0e, #02a30d);
            box-shadow: 0 14px 30px rgba(3, 172, 14, 0.24);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .primary-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 36px rgba(3, 172, 14, 0.28);
        }
        .primary-btn:active { transform: translateY(0); }
        @media (max-width: 720px) {
            .page-title { flex-direction: column; align-items: flex-start; }
            .field { grid-template-columns: 1fr; gap: 12px; }
            .actions { justify-content: stretch; }
            .primary-btn { width: 100%; }
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
                <?php $initial = strtoupper(substr($user['name'], 0, 1)); ?>
                <div class="account-mini">
                    <a class="account-avatar" href="profile.php" title="Profil">
                        <?= $initial ?>
                    </a>
                    <a class="icon-btn" href="profile.php" title="Profil">
                        ⚙
                    </a>
                    <a class="icon-btn danger" href="logout.php" onclick="return confirm('Keluar dari sesi?');" title="Keluar">
                        ⎋
                    </a>
                </div>
            </div>
        </div>
    </header>
    <main>
        <div class="content">
            <div class="page-title">
                <div>
                    <h1>Edit Produk</h1>
                    <p>Perbarui detail produk Anda dan jaga tampilan toko tetap menarik.</p>
                </div>
            </div>
            <div class="workspace">
                <div class="card">
                    <?php if ($message): ?>
                        <div class="message <?= $messageType ? htmlspecialchars($messageType) : '' ?>"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <form class="form-grid" method="POST" enctype="multipart/form-data">
                        <div class="field">
                            <label for="name">Nama Produk</label>
                            <div class="control">
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                            </div>
                        </div>
                        <div class="field">
                            <label for="description">Deskripsi</label>
                            <div class="control">
                                <textarea id="description" name="description" required><?= htmlspecialchars($product['description']) ?></textarea>
                                <small style="color: var(--text-muted); font-size: 13px;">Gunakan cerita produk yang ringkas dan jelas.</small>
                            </div>
                        </div>
                        <div class="field">
                            <label for="price">Harga</label>
                            <div class="control">
                                <input type="number" step="0.01" id="price" name="price" value="<?= htmlspecialchars((string)$product['price']) ?>" required>
                            </div>
                        </div>
                        <div class="field">
                            <label for="image">Perbarui Gambar</label>
                            <div class="control">
                                <input type="file" id="image" name="image" accept="image/*">
                                <small style="color: var(--text-muted); font-size: 13px;">Unggah gambar baru jika ingin mengganti tampilan produk.</small>
                            </div>
                        </div>
                        <div class="actions">
                            <button type="submit" name="submit" class="primary-btn">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
                <aside class="preview-card">
                    <span class="preview-title">Pratinjau Produk</span>
                    <div class="preview-figure">
                        <?php if ($currentImage): ?>
                            <img src="<?= htmlspecialchars($currentImage) ?>" alt="Pratinjau Gambar Produk">
                        <?php else: ?>
                            <span>Belum ada gambar</span>
                        <?php endif; ?>
                    </div>
                    <div class="meta-list">
                        <span><strong>Nama:</strong> <?= htmlspecialchars($product['name']) ?></span>
                        <span><strong>Harga:</strong> Rp <?= number_format((float)$product['price'], 0, ',', '.') ?></span>
                        <span><strong>ID:</strong> #<?= htmlspecialchars((string)$product['id']) ?></span>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</div>
</body>
</html>
