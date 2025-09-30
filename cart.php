<?php
require_once 'auth.php';
include 'koneksi.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart = &$_SESSION['cart'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'add':
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = max(1, (int)($_POST['quantity'] ?? 1));
            if ($productId > 0) {
                $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
            }
            header('Location: cart.php?status=added');
            exit;
        case 'update':
            if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
                foreach ($_POST['quantities'] as $id => $qty) {
                    $pid = (int)$id;
                    $quantity = max(0, (int)$qty);
                    if ($quantity === 0) {
                        unset($cart[$pid]);
                    } else {
                        $cart[$pid] = $quantity;
                    }
                }
            }
            header('Location: cart.php?status=updated');
            exit;
        case 'remove':
            $productId = (int)($_POST['product_id'] ?? 0);
            if ($productId > 0 && isset($cart[$productId])) {
                unset($cart[$productId]);
            }
            header('Location: cart.php?status=removed');
            exit;
        case 'clear':
            $cart = [];
            header('Location: cart.php?status=cleared');
            exit;
    }
}

$cartProductIds = array_keys($cart);
$products = [];
$total = 0.0;
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

if ($cartProductIds) {
    $placeholders = implode(',', array_fill(0, count($cartProductIds), '?'));
    $types = str_repeat('i', count($cartProductIds));
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$cartProductIds);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $pid = (int)$row['id'];
                $quantity = $cart[$pid] ?? 0;
                $row['quantity'] = $quantity;
                $row['subtotal'] = $quantity * (float)$row['price'];
                $total += $row['subtotal'];
                $products[$pid] = $row;
            }
        }
        $stmt->close();
    }
}

$cartCount = array_sum($cart);
$status = $_GET['status'] ?? '';
$statusMessage = '';
if ($status) {
    $messages = [
        'added' => 'Produk ditambahkan ke keranjang.',
        'updated' => 'Keranjang diperbarui.',
        'removed' => 'Produk dihapus dari keranjang.',
        'cleared' => 'Keranjang dikosongkan.',
    ];
    $statusMessage = $messages[$status] ?? '';
}

$user = current_user();
$activePage = 'cart';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keranjang Belanja</title>
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
        .topbar { max-width: 1120px; margin: 0 auto; padding: clamp(16px, 3vw, 24px) clamp(16px, 4vw, 32px); display: flex; align-items: center; gap: clamp(16px, 4vw, 48px); }
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
        .auth-mini { display: inline-flex; align-items: center; gap: 6px; }
        .auth-mini .icon-btn.primary { border: none; background: linear-gradient(135deg, #03ac0e, #02a30d); color: #ffffff; box-shadow: 0 8px 16px rgba(3, 172, 14, 0.18); }
        .auth-mini .icon-btn.primary:hover { box-shadow: 0 10px 20px rgba(3, 172, 14, 0.24); }
        @media (max-width: 900px) { .topbar { flex-wrap: wrap; } .nav-bar { order: 3; width: 100%; } .account-area { order: 2; width: 100%; justify-content: flex-end; } .brand { order: 1; } }
        @media (max-width: 640px) { .nav-bar { justify-content: flex-start; } }
        main { flex: 1; padding: clamp(32px, 6vw, 56px) clamp(16px, 4vw, 32px) 64px; }
        .content { max-width: 1120px; margin: 0 auto; display: grid; gap: 24px; }
        .page-title h1 { margin: 0; font-size: clamp(28px, 3vw, 36px); letter-spacing: -0.02em; }
        .page-title p { margin: 4px 0 0; color: var(--text-muted); font-size: 15px; }
        .message { border-radius: 16px; padding: 14px 20px; font-size: 15px; display: flex; align-items: center; gap: 10px; background: rgba(3, 172, 14, 0.12); color: var(--accent-dark); }
        .cart-layout { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 24px; align-items: start; }
        .cart-card, .summary-card { background: var(--surface); border-radius: 24px; padding: clamp(24px, 3vw, 36px); box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08); border: 1px solid rgba(148, 163, 184, 0.12); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 16px 0; border-bottom: 1px solid rgba(148, 163, 184, 0.2); text-align: left; }
        th { color: var(--text-muted); font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; }
        td img { width: 72px; height: 72px; object-fit: cover; border-radius: 12px; margin-right: 16px; }
        .product-cell { display: flex; align-items: center; gap: 16px; }
        .qty-input { width: 72px; border: 1px solid rgba(148, 163, 184, 0.35); border-radius: 12px; padding: 10px; font-size: 15px; text-align: center; }
        .subtotal { font-weight: 600; }
        .empty-state { text-align: center; padding: clamp(24px, 3vw, 36px); color: var(--text-muted); display: grid; gap: 12px; }
        .primary-btn { border: none; border-radius: 18px; padding: 15px 28px; font-size: 15px; font-weight: 600; cursor: pointer; color: #ffffff; background: linear-gradient(135deg, #03ac0e, #02a30d); box-shadow: 0 14px 30px rgba(3, 172, 14, 0.24); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .primary-btn:hover { transform: translateY(-1px); box-shadow: 0 16px 36px rgba(3, 172, 14, 0.28); }
        .secondary-btn { display: inline-flex; justify-content: center; align-items: center; padding: 14px 24px; border-radius: 16px; border: 1px solid rgba(3, 172, 14, 0.2); background: rgba(255, 255, 255, 0.7); color: var(--accent-dark); font-weight: 600; text-decoration: none; transition: background 0.2s ease; }
        .secondary-btn:hover { background: rgba(255, 255, 255, 0.9); }
        .danger-btn { background: none; border: none; color: #dc2626; font-weight: 600; cursor: pointer; text-decoration: underline; }
        .summary-card { display: grid; gap: 16px; }
        .summary-card h2 { margin: 0; font-size: 20px; }
        .summary-card .total { font-size: 24px; font-weight: 700; color: var(--accent-dark); }
        .summary-actions { display: grid; gap: 12px; }
        @media (max-width: 980px) { .cart-layout { grid-template-columns: 1fr; } }
        @media (max-width: 720px) { table, thead { display: none; } .cart-card { padding: 20px; } .cart-list { display: grid; gap: 16px; } .cart-item { display: grid; gap: 12px; border-bottom: 1px solid rgba(148, 163, 184, 0.2); padding-bottom: 16px; } .cart-item:last-child { border-bottom: none; } .cart-item img { width: 100%; max-width: 160px; border-radius: 16px; } .item-actions { display: flex; gap: 12px; } }
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
                <?php if ($user): ?>
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
                <?php else: ?>
                    <div class="auth-mini">
                        <a class="icon-btn" href="login.php" title="Masuk">⇢</a>
                        <a class="icon-btn primary" href="register.php" title="Daftar">＋</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main>
        <div class="content">
            <div class="page-title">
                <div>
                    <h1>Keranjang Belanja</h1>
                    <p>Kelola barang yang ingin dibeli dan lanjutkan ke proses checkout.</p>
                </div>
            </div>
            <?php if ($statusMessage): ?>
                <div class="message"><?= htmlspecialchars($statusMessage) ?></div>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="cart-card empty-state">
                    <h2>Keranjang masih kosong</h2>
                    <p>Temukan produk menarik di katalog dan tambahkan ke keranjang Anda.</p>
                    <a class="secondary-btn" href="view_product.php">Kembali ke Katalog</a>
                </div>
            <?php else: ?>
                <div class="cart-layout">
                    <div class="cart-card">
                        <form id="update-form" method="POST">
                            <input type="hidden" name="action" value="update">
                        </form>
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Kuantitas</th>
                                    <th>Harga</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="product-cell">
                                                <?php
                                                $imagePath = '';
                                                if (!empty($item['image'])) {
                                                    $filePath = $uploadDir . DIRECTORY_SEPARATOR . $item['image'];
                                                    if (is_file($filePath)) {
                                                        $imagePath = 'uploads/' . $item['image'];
                                                    }
                                                }
                                                ?>
                                                <?php if ($imagePath): ?>
                                                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                                                    <span style="color:var(--text-muted);">ID #<?= htmlspecialchars((string)$item['id']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <input class="qty-input" type="number" name="quantities[<?= htmlspecialchars((string)$item['id']) ?>]" min="0" value="<?= htmlspecialchars((string)$item['quantity']) ?>" form="update-form">
                                        </td>
                                        <td>Rp <?= number_format((float)$item['price'], 0, ',', '.') ?></td>
                                        <td class="subtotal">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?= htmlspecialchars((string)$item['id']) ?>">
                                                <button type="submit" class="danger-btn" onclick="return confirm('Hapus produk ini dari keranjang?');">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div style="display:flex; justify-content: space-between; align-items:center; margin-top:20px; gap:12px; flex-wrap:wrap;">
                            <button type="submit" class="primary-btn" form="update-form">Perbarui Keranjang</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="danger-btn" onclick="return confirm('Kosongkan semua isi keranjang?');">Kosongkan Keranjang</button>
                            </form>
                        </div>
                    </div>
                    <aside class="summary-card">
                        <h2>Ringkasan Belanja</h2>
                        <div>Jumlah Produk: <strong><?= $cartCount ?></strong></div>
                        <div class="total">Total: Rp <?= number_format($total, 0, ',', '.') ?></div>
                        <div class="summary-actions">
                            <button type="button" class="primary-btn" onclick="alert('Integrasi checkout dapat ditambahkan di sini.');">Checkout</button>
                            <a class="secondary-btn" href="view_product.php">Lanjut Belanja</a>
                        </div>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
