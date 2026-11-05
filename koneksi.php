<?php
// Mengambil variabel lingkungan internal Railway (atau fallback ke localhost jika di XAMPP)
$host     = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: 'localhost';
$user     = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: 'root';
$pass     = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';
$db       = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'railway';
$port     = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: 3306;

// menggunakan $conn
$conn = mysqli_connect($host, $user, $pass, $db, (int)$port);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>