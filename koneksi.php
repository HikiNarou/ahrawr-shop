<?php
$host = 'localhost'; // Host database
$username = 'root'; // Username database
$password = ''; // Password database
$dbname = 'ecommerce'; // Nama database
// Koneksi ke database
$conn = new mysqli($host, $username, $password, $dbname);
// Cek koneksi
if ($conn->connect_error) {
 die("Connection failed: " . $conn->connect_error);
}
?>
