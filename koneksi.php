<?php
// Mengambil variabel lingkungan internal Railway (atau fallback ke localhost jika di XAMPP)
$host     = getenv('MYSQL_HOST') ?: getenv('MYSQL_HOST') ?: 'localhost';
$user     = getenv('MYSQL_USER') ?: getenv('MYSQL_USER') ?: 'root';
$pass     = getenv('MYSQL_PASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';
$db       = getenv('MYSQL_DATABASE') ?: getenv('MYSQL_DATABASE') ?: 'railway';
$port     = getenv('MYSQL_PORT') ?: getenv('MYSQL_PORT') ?: 3306;

$koneksi = mysqli_connect($host, $user, $pass, $db, (int)$port);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>