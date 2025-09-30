<?php
require_once 'auth.php';
include 'koneksi.php';

$sql = 'SELECT * FROM products ORDER BY id DESC';
$result = $conn->query($sql);

$products = [];
$error = '';

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    $error = 'Tidak dapat memuat produk: ' . $conn->error;
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$totalValue = $products ? array_sum(array_map('floatval', array_column($products, 'price'))) : 0;
$user = current_user();
$activePage = 'catalog';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Produk</title>
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
        .content { max-width: 1120px; margin: 0 auto; display: grid; gap: 28px; }
        .hero { background: linear-gradient(135deg, rgba(3, 172, 14, 0.14), rgba(3, 172, 14, 0.05)); border-radius: 24px; padding: clamp(28px, 4vw, 40px); border: 1px solid rgba(3, 172, 14, 0.12); box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08); display: grid; gap: 16px; }
        .hero h1 { margin: 0; font-size: clamp(28px, 3.2vw, 38px); letter-spacing: -0.02em; color: var(--accent-dark); }
        .hero p { margin: 0; color: var(--text-muted); font-size: 16px; max-width: 620px; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .pill-btn { display: inline-flex; align-items: center; justify-content: center; padding: 11px 18px; border-radius: 14px; font-weight: 600; text-decoration: none; font-size: 13px; transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease; }
        .pill-btn.primary { background: linear-gradient(135deg, #03ac0e, #02a30d); color: #ffffff; box-shadow: 0 12px 28px rgba(3, 172, 14, 0.24); }
        .pill-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 14px 32px rgba(3, 172, 14, 0.26); }
        .pill-btn.ghost { background: rgba(255, 255, 255, 0.7); color: var(--accent-dark); border: 1px solid rgba(3, 172, 14, 0.2); }
        .pill-btn.ghost:hover { background: rgba(255, 255, 255, 0.9); }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        .summary-card { background: var(--surface); border-radius: 20px; padding: 20px; border: 1px solid rgba(148, 163, 184, 0.14); box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06); display: grid; gap: 6px; }
        .summary-card span { color: var(--text-muted); font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; }
        .summary-card strong { font-size: 24px; letter-spacing: -0.02em; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: clamp(20px, 2.8vw, 32px); }
        .product-card { background: var(--surface); border-radius: 24px; border: 1px solid rgba(148, 163, 184, 0.12); box-shadow: 0 20px 48px rgba(15, 23, 42, 0.08); overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 28px 64px rgba(15, 23, 42, 0.12); }
        .product-thumb { position: relative; padding-top: 70%; background: linear-gradient(135deg, rgba(3, 172, 14, 0.12), rgba(3, 172, 14, 0.04)); }
        .product-thumb img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .product-thumb span { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: var(--accent-dark); font-weight: 600; }
        .product-body { padding: 20px 24px; display: grid; gap: 12px; flex: 1; }
        .product-body h2 { margin: 0; font-size: 18px; letter-spacing: -0.01em; }
        .product-description { margin: 0; color: var(--text-muted); font-size: 14px; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }
        .product-footer { padding: 0 24px 20px; display: grid; gap: 14px; }
        .product-meta { display: flex; justify-content: space-between; align-items: center; font-weight: 600; color: var(--text-strong); }
        .product-meta span { font-size: 14px; }
        .product-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .product-actions form { display: contents; }
        .btn { flex: 1 1 120px; display: inline-flex; justify-content: center; align-items: center; padding: 11px 16px; border-radius: 14px; font-weight: 600; text-decoration: none; font-size: 13px; transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease; }
        .btn-primary { background: linear-gradient(135deg, #03ac0e, #02a30d); color: #ffffff; box-shadow: 0 12px 26px rgba(3, 172, 14, 0.24); }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 16px 30px rgba(3, 172, 14, 0.26); }
        .btn-ghost { background: rgba(3, 172, 14, 0.12); color: var(--accent-dark); border: none; cursor: pointer; }
        .btn-ghost:hover { background: rgba(3, 172, 14, 0.18); }
        .btn-outline { background: rgba(255, 255, 255, 0.7); color: var(--accent-dark); border: 1px solid rgba(3, 172, 14, 0.2); }
        .btn-outline:hover { background: rgba(255, 255, 255, 0.9); }
        .message { padding: 14px 20px; border-radius: 16px; font-size: 15px; display: flex; align-items: center; gap: 10px; }
        .message.error { background: rgba(220, 38, 38, 0.12); color: #991b1b; }
        .empty-state { background: var(--surface); border-radius: 24px; padding: clamp(32px, 4vw, 48px); text-align: center; box-shadow: 0 20px 48px rgba(15, 23, 42, 0.08); border: 1px solid rgba(148, 163, 184, 0.14); display: grid; gap: 12px; }
        .empty-state h2 { margin: 0; font-size: 22px; }
        .empty-state p { margin: 0; color: var(--text-muted); }
        @media (max-width: 720px) { .hero-actions { width: 100%; } .hero-actions .pill-btn { flex: 1 1 160px; } }
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
            <section class="hero">
                <div>
                    <h1>Katalog Produk</h1>
                    <p>Kelola koleksi barang Anda dengan tampilan modern ala marketplace terkemuka. Update stok, harga, dan visual produk secara cepat.</p>
                </div>
                <div class="hero-actions">
                    <a class="pill-btn primary" href="add_product.php">+ Tambah Produk</a>
                    <a class="pill-btn ghost" href="cart.php">Lihat Keranjang</a>
                </div>
            </section>

            <section class="summary">
                <div class="summary-card">
                    <span>Total Produk</span>
                    <strong><?= count($products) ?></strong>
                </div>
                <div class="summary-card">
                    <span>Produk Terbaru</span>
                    <strong><?= !empty($products) ? htmlspecialchars($products[0]['name']) : 'Belum Ada' ?></strong>
                </div>
                <div class="summary-card">
                    <span>Total Nilai</span>
                    <strong>Rp <?= number_format($totalValue, 0, ',', '.') ?></strong>
                </div>
            </section>

            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!$error && empty($products)): ?>
                <section class="empty-state">
                    <h2>Belum Ada Produk</h2>
                    <p>Mulai tambahkan produk pertama Anda agar katalog terlihat profesional.</p>
                    <a class="pill-btn primary" href="add_product.php">Tambah Produk Sekarang</a>
                </section>
            <?php else: ?>
                <section class="grid">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $imagePath = '';
                        if (!empty($product['image'])) {
                            $filePath = $uploadDir . DIRECTORY_SEPARATOR . $product['image'];
                            if (is_file($filePath)) {
                                $imagePath = 'uploads/' . $product['image'];
                            }
                        }
                        ?>
                        <article class="product-card">
                            <div class="product-thumb">
                                <?php if ($imagePath): ?>
                                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <span>Tidak ada gambar</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-body">
                                <h2><?= htmlspecialchars($product['name']) ?></h2>
                                <p class="product-description"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                            </div>
                            <div class="product-footer">
                                <div class="product-meta">
                                    <span>ID #<?= htmlspecialchars((string)$product['id']) ?></span>
                                    <span>Rp <?= number_format((float)$product['price'], 0, ',', '.') ?></span>
                                </div>
                                <div class="product-actions">
                                    <a class="btn btn-outline" href="product_detail.php?id=<?= htmlspecialchars((string)$product['id']) ?>">Detail</a>
                                    <form method="POST" action="cart.php">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?= htmlspecialchars((string)$product['id']) ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-ghost">Tambah Keranjang</button>
                                    </form>
                                    <?php if ($user): ?>
                                        <a class="btn btn-primary" href="edit_product.php?id=<?= htmlspecialchars((string)$product['id']) ?>">Edit</a>
                                        <a class="btn btn-ghost" href="delete_product.php?id=<?= htmlspecialchars((string)$product['id']) ?>" onclick="return confirm('Hapus produk ini?');">Hapus</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
