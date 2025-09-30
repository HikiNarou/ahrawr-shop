<?php
require_once 'auth.php';
include 'koneksi.php';

if (!isset($_GET['id'])) {
    echo 'Produk tidak ditemukan.';
    exit;
}

$id = (int)$_GET['id'];
$product = null;
$message = '';
$messageType = '';

$stmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
if ($stmt) {
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$product) {
    echo 'Produk tidak ditemukan.';
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart = &$_SESSION['cart'];
$cartCount = array_sum($cart);
$user = current_user();
$activePage = 'catalog';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $cart[$id] = ($cart[$id] ?? 0) + $quantity;
    $cartCount = array_sum($cart);
    $message = 'Produk ditambahkan ke keranjang.';
    $messageType = 'success';
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$imagePath = '';
if (!empty($product['image'])) {
    $filePath = $uploadDir . DIRECTORY_SEPARATOR . $product['image'];
    if (is_file($filePath)) {
        $imagePath = 'uploads/' . $product['image'];
    }
}

$relatedProducts = [];
$relatedStmt = $conn->prepare('SELECT id, name, price, image FROM products WHERE id <> ? ORDER BY id DESC LIMIT 6');
if ($relatedStmt) {
    $relatedStmt->bind_param('i', $id);
    if ($relatedStmt->execute()) {
        $relatedResult = $relatedStmt->get_result();
        while ($row = $relatedResult->fetch_assoc()) {
            $relatedProducts[] = $row;
        }
    }
    $relatedStmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?> - Detail Produk</title>
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
        body { margin: 0; min-height: 100vh; background: var(--shell); color: var(--text-strong); display: flex; flex-direction: column; }
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
        main { flex: 1; padding: clamp(32px, 6vw, 60px) clamp(16px, 4vw, 32px) clamp(48px, 8vw, 96px); }
        .content { max-width: 1120px; margin: 0 auto; display: grid; gap: 28px; }
        .breadcrumbs { display: inline-flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; }
        .breadcrumbs a { text-decoration: none; color: var(--accent-dark); font-weight: 600; }
        .breadcrumbs span { opacity: 0.4; }
        .message { border-radius: 18px; padding: 14px 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; }
        .message.success { background: rgba(3, 172, 14, 0.12); color: var(--accent-dark); border: 1px solid rgba(3, 172, 14, 0.18); }
        .message.error { background: rgba(220, 38, 38, 0.12); color: #991b1b; border: 1px solid rgba(220, 38, 38, 0.18); }
        .product-hero { background: linear-gradient(135deg, rgba(3, 172, 14, 0.14), rgba(3, 172, 14, 0.04)); border-radius: 32px; padding: clamp(28px, 4vw, 48px); display: grid; grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr); gap: clamp(28px, 4vw, 44px); align-items: center; border: 1px solid rgba(3, 172, 14, 0.18); box-shadow: 0 28px 60px rgba(15, 23, 42, 0.08); }
        .media-pane { background: rgba(255, 255, 255, 0.92); border-radius: 28px; padding: clamp(16px, 2vw, 24px); box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.04); }
        .media-pane figure { margin: 0; display: grid; place-items: center; border-radius: 20px; overflow: hidden; background: rgba(148, 163, 184, 0.12); min-height: clamp(260px, 32vw, 420px); }
        .media-pane img { width: 100%; height: 100%; object-fit: cover; }
        .media-pane .placeholder { padding: 32px; font-weight: 600; color: var(--text-muted); text-align: center; }
        .info-pane { display: grid; gap: 24px; }
        .product-meta { display: inline-flex; flex-wrap: wrap; gap: 12px; font-size: 12px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }
        .info-pane h1 { margin: 0; font-size: clamp(30px, 4vw, 44px); letter-spacing: -0.015em; color: var(--accent-dark); }
        .price-block { display: grid; gap: 6px; }
        .price { font-size: clamp(28px, 4vw, 40px); font-weight: 700; color: var(--accent-dark); }
        .description { color: var(--text-muted); font-size: 15px; line-height: 1.7; }
        .purchase-box { background: rgba(255, 255, 255, 0.94); border-radius: 24px; padding: 22px; display: grid; gap: 18px; border: 1px solid rgba(148, 163, 184, 0.16); box-shadow: 0 18px 46px rgba(15, 23, 42, 0.1); }
        .purchase-box form { display: grid; gap: 18px; }
        .qty-control { display: grid; gap: 10px; }
        .qty-control label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--accent-dark); }
        .qty-stepper { display: inline-flex; align-items: center; gap: 6px; padding: 4px; border-radius: 999px; border: 1px solid rgba(148, 163, 184, 0.3); background: rgba(248, 250, 252, 0.9); }
        .qty-stepper input { width: 68px; border: none; background: transparent; text-align: center; font-size: 16px; font-weight: 600; color: var(--text-strong); padding: 6px 8px; }
        .qty-stepper input[type="number"] { -moz-appearance: textfield; }
        .qty-stepper input::-webkit-outer-spin-button, .qty-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .qty-stepper button { width: 34px; height: 34px; border-radius: 50%; border: none; background: linear-gradient(135deg, rgba(3, 172, 14, 0.12), rgba(3, 172, 14, 0.22)); color: var(--accent-dark); font-weight: 700; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .qty-stepper button:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(3, 172, 14, 0.18); }
        .qty-stepper button:active { transform: translateY(0); }
        .cta-row { display: flex; flex-wrap: wrap; gap: 12px; }
        .primary-btn { display: inline-flex; align-items: center; justify-content: center; border: none; border-radius: 18px; padding: 14px 26px; font-size: 15px; font-weight: 600; cursor: pointer; color: #ffffff; background: linear-gradient(135deg, #03ac0e, #02a30d); box-shadow: 0 16px 36px rgba(3, 172, 14, 0.24); transition: transform 0.2s ease, box-shadow 0.2s ease; text-decoration: none; }
        .primary-btn:hover { transform: translateY(-1px); box-shadow: 0 18px 42px rgba(3, 172, 14, 0.28); }
        .primary-btn:active { transform: translateY(0); }
        .ghost-btn { display: inline-flex; align-items: center; justify-content: center; padding: 12px 22px; border-radius: 16px; font-size: 15px; font-weight: 600; border: 1px solid rgba(3, 172, 14, 0.22); color: var(--accent-dark); text-decoration: none; background: rgba(255, 255, 255, 0.9); transition: background 0.2s ease, box-shadow 0.2s ease; }
        .ghost-btn:hover { background: rgba(3, 172, 14, 0.08); box-shadow: 0 10px 24px rgba(3, 172, 14, 0.18); }
        .related-section { display: grid; gap: 18px; margin-top: 16px; }
        .related-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .related-header h2 { margin: 0; font-size: 22px; letter-spacing: -0.01em; }
        .pill-link { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 999px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid rgba(3, 172, 14, 0.22); color: var(--accent-dark); background: rgba(255, 255, 255, 0.92); transition: background 0.2s ease, box-shadow 0.2s ease; }
        .pill-link:hover { background: rgba(3, 172, 14, 0.08); box-shadow: 0 10px 22px rgba(3, 172, 14, 0.16); }
        .related-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: clamp(16px, 2vw, 24px); }
        .related-card { background: var(--surface); border-radius: 22px; border: 1px solid rgba(148, 163, 184, 0.14); box-shadow: 0 18px 40px rgba(15, 23, 42, 0.07); display: grid; gap: 14px; padding: 16px; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .related-card:hover { transform: translateY(-3px); box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12); }
        .related-card .thumb { display: block; border-radius: 16px; overflow: hidden; background: rgba(148, 163, 184, 0.12); aspect-ratio: 4 / 3; position: relative; }
        .related-card .thumb img { width: 100%; height: 100%; object-fit: cover; }
        .related-card .thumb span { display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-muted); font-weight: 600; font-size: 13px; }
        .related-body { display: grid; gap: 6px; }
        .related-body h3 { margin: 0; font-size: 16px; letter-spacing: -0.01em; color: var(--text-strong); }
        .related-body .price { font-size: 15px; font-weight: 700; color: var(--accent-dark); }
        .related-actions { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .text-link { font-size: 13px; font-weight: 600; color: var(--accent-dark); text-decoration: none; }
        .text-link:hover { text-decoration: underline; }
        .mini-btn { display: inline-flex; align-items: center; justify-content: center; border: none; border-radius: 14px; padding: 8px 14px; font-size: 13px; font-weight: 600; cursor: pointer; color: #ffffff; background: linear-gradient(135deg, #03ac0e, #02a30d); box-shadow: 0 10px 20px rgba(3, 172, 14, 0.22); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .mini-btn:hover { transform: translateY(-1px); box-shadow: 0 12px 26px rgba(3, 172, 14, 0.26); }
        form, input, button, textarea { font-family: inherit; }
        @media (max-width: 900px) {
            .product-hero { grid-template-columns: 1fr; }
            .media-pane { order: 2; }
            .info-pane { order: 1; }
        }
        @media (max-width: 720px) {
            .topbar { flex-wrap: wrap; }
            .nav-bar { order: 3; width: 100%; }
            .account-area { order: 2; width: 100%; justify-content: flex-end; }
            .brand { order: 1; }
            .cta-row { flex-direction: column; align-items: stretch; }
            .primary-btn, .ghost-btn { width: 100%; justify-content: center; }
        }
        @media (max-width: 640px) {
            .nav-bar { justify-content: flex-start; }
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
            <div class="breadcrumbs">
                <a href="view_product.php">Katalog</a><span>/</span><strong><?= htmlspecialchars($product['name']) ?></strong>
            </div>
            <?php if ($message): ?>
                <div class="message <?= $messageType ? htmlspecialchars($messageType) : '' ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <section class="product-hero">
                <div class="media-pane">
                    <figure>
                        <?php if ($imagePath): ?>
                            <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <?php else: ?>
                            <span class="placeholder">Belum ada gambar</span>
                        <?php endif; ?>
                    </figure>
                </div>
                <div class="info-pane">
                    <div class="product-meta">
                        <span>Kode Produk #<?= htmlspecialchars((string)$product['id']) ?></span>
                        <span>AhRawr Shop Collection</span>
                    </div>
                    <h1><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="price-block">
                        <div class="price">Rp <?= number_format((float)$product['price'], 0, ',', '.') ?></div>
                    </div>
                    <div class="description"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
                    <div class="purchase-box">
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="qty-control">
                                <label for="quantity">Jumlah</label>
                                <div class="qty-stepper" data-stepper>
                                    <button type="button" class="step-btn" data-step="-1" aria-label="Kurangi jumlah">-</button>
                                    <input type="number" id="quantity" name="quantity" value="1" min="1">
                                    <button type="button" class="step-btn" data-step="1" aria-label="Tambah jumlah">+</button>
                                </div>
                            </div>
                            <div class="cta-row">
                                <button type="submit" class="primary-btn">Tambah ke Keranjang</button>
                                <a class="ghost-btn" href="cart.php">Lihat Keranjang</a>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
            <?php if (!empty($relatedProducts)): ?>
                <section class="related-section">
                    <div class="related-header">
                        <h2>Produk Lainnya Pilihan Kami</h2>
                        <a class="pill-link" href="view_product.php">Lihat Semua</a>
                    </div>
                    <div class="related-grid">
                        <?php foreach ($relatedProducts as $item): ?>
                            <?php
                            $relatedImage = '';
                            if (!empty($item['image'])) {
                                $candidate = $uploadDir . DIRECTORY_SEPARATOR . $item['image'];
                                if (is_file($candidate)) {
                                    $relatedImage = 'uploads/' . $item['image'];
                                }
                            }
                            ?>
                            <article class="related-card">
                                <a class="thumb" href="product_detail.php?id=<?= htmlspecialchars((string)$item['id']) ?>">
                                    <?php if ($relatedImage): ?>
                                        <img src="<?= htmlspecialchars($relatedImage) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                    <?php else: ?>
                                        <span>Belum ada gambar</span>
                                    <?php endif; ?>
                                </a>
                                <div class="related-body">
                                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                                    <div class="price">Rp <?= number_format((float)$item['price'], 0, ',', '.') ?></div>
                                </div>
                                <div class="related-actions">
                                    <a class="text-link" href="product_detail.php?id=<?= htmlspecialchars((string)$item['id']) ?>">Lihat Detail</a>
                                    <form method="POST" action="cart.php">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?= htmlspecialchars((string)$item['id']) ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="mini-btn">Tambah</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>
</div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-stepper]').forEach(function (stepper) {
                var input = stepper.querySelector('input[type="number"]');
                if (!input) {
                    return;
                }
                stepper.addEventListener('click', function (event) {
                    var button = event.target.closest('button[data-step]');
                    if (!button) {
                        return;
                    }
                    event.preventDefault();
                    var step = parseInt(button.getAttribute('data-step'), 10) || 0;
                    var min = parseInt(input.getAttribute('min'), 10) || 1;
                    var value = parseInt(input.value, 10);
                    if (isNaN(value)) {
                        value = min;
                    }
                    value = Math.max(min, value + step);
                    input.value = value;
                    input.dispatchEvent(new Event('change'));
                });
            });
        });
    </script>
</body>
</html>
