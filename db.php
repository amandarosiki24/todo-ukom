<?php
// db.php
$host = 'localhost';
$dbname = 'tdl_ukom';
$username = 'root';  // sesuaikan
$password = '';      // sesuaikan

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>