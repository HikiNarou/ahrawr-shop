<?php
$host = 'localhost'; // Host database
$username = 'root'; // Username database
$password = ''; // Password database
$dbname = 'ecommerce'; // Nama database

// Koneksi ke database dengan error handling
try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // Cek koneksi
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // For demo purposes, create a mock connection object
    error_log("Database connection failed: " . $e->getMessage());
    
    $conn = new class {
        public $connect_error = 'Database not available';
        public $error = 'Demo mode';
        
        public function query($sql) {
            return false;
        }
        
        public function prepare($sql) {
            return false;
        }
        
        public function set_charset($charset) {
            return true;
        }
    };
}
?>
