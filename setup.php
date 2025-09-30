<?php
/**
 * Setup script for AhRawr Shop E-commerce Application
 * Run this file once to check system requirements and setup
 */

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Setup - AhRawr Shop</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; line-height: 1.6; }
        .success { color: #027a09; background: #f0fdf4; padding: 10px; border-radius: 8px; margin: 10px 0; }
        .error { color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 8px; margin: 10px 0; }
        .info { color: #1d4ed8; background: #eff6ff; padding: 10px; border-radius: 8px; margin: 10px 0; }
        h1 { color: #027a09; }
        h2 { color: #374151; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>";

echo "<h1>🛒 AhRawr Shop - Setup</h1>";

// Check PHP version
echo "<h2>1. PHP Requirements</h2>";
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "<div class='success'>✅ PHP $phpVersion (OK)</div>";
} else {
    echo "<div class='error'>❌ PHP $phpVersion (Minimum required: 7.4.0)</div>";
}

// Check required extensions
echo "<h2>2. PHP Extensions</h2>";
$required_extensions = ['mysqli', 'session', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='success'>✅ $ext extension loaded</div>";
    } else {
        echo "<div class='error'>❌ $ext extension not found</div>";
    }
}

// Check database connection
echo "<h2>3. Database Connection</h2>";
try {
    include 'koneksi.php';
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    echo "<div class='success'>✅ Database connection successful</div>";
    
    // Check if tables exist
    $tables = ['users', 'products', 'user_sessions', 'user_activity_log'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<div class='success'>✅ Table '$table' exists</div>";
        } else {
            echo "<div class='error'>❌ Table '$table' not found</div>";
        }
    }
    
    // Check products count
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    if ($result) {
        $row = $result->fetch_assoc();
        $productCount = $row['count'];
        if ($productCount > 0) {
            echo "<div class='success'>✅ Found $productCount products in database</div>";
        } else {
            echo "<div class='info'>ℹ️ No products found. You can add products from the admin panel.</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    echo "<div class='info'>ℹ️ Please run <code>database.sql</code> to create the database and tables.</div>";
}

// Check uploads directory
echo "<h2>4. File Permissions</h2>";
$uploadsDir = __DIR__ . '/uploads';
if (is_dir($uploadsDir)) {
    if (is_writable($uploadsDir)) {
        echo "<div class='success'>✅ Uploads directory is writable</div>";
    } else {
        echo "<div class='error'>❌ Uploads directory is not writable</div>";
        echo "<div class='info'>ℹ️ Run: <code>chmod 755 uploads</code></div>";
    }
} else {
    echo "<div class='error'>❌ Uploads directory not found</div>";
    echo "<div class='info'>ℹ️ Run: <code>mkdir uploads && chmod 755 uploads</code></div>";
}

// Security check
echo "<h2>5. Security</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<div class='success'>✅ PHP sessions working</div>";

if (function_exists('password_hash')) {
    echo "<div class='success'>✅ Password hashing available</div>";
} else {
    echo "<div class='error'>❌ Password hashing not available</div>";
}

if (function_exists('random_bytes')) {
    echo "<div class='success'>✅ Secure random generation available</div>";
} else {
    echo "<div class='error'>❌ Secure random generation not available</div>";
}

echo "<h2>6. Getting Started</h2>";
echo "<div class='info'>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If you see any errors above, fix them first</li>";
echo "<li>Delete this <code>setup.php</code> file for security</li>";
echo "<li>Visit <a href='index.php'>index.php</a> to start using the application</li>";
echo "<li>Register a new account or login if you have one</li>";
echo "<li>Start adding products and managing your e-commerce store!</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>