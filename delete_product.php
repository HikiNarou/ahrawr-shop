<?php
require_once 'auth.php';
require_login();
include 'koneksi.php';

if (!isset($_GET['id'])) {
    echo "Produk tidak ditemukan.";
    exit;
}

$id = (int)$_GET['id'];
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

$imageName = '';
$stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
if ($stmt) {
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $stmt->bind_result($imageName);
        $stmt->fetch();
    }
    $stmt->close();
}

if ($imageName) {
    $filePath = $uploadDir . DIRECTORY_SEPARATOR . $imageName;
    if (is_file($filePath)) {
        unlink($filePath);
    }
}

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
if ($stmt) {
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo "Produk berhasil dihapus";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Error: " . $conn->error;
}
?>
