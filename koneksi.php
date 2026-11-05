<?php
// Mengambil variabel database dari Railway, atau fallback ke localhost jika di XAMPP
$host     = getenv('MYSQL_HOST') ?: getenv('DB_HOST') ?: 'localhost';
$user     = getenv('MYSQL_USER') ?: getenv('DB_USER') ?: 'root';
$pass     = getenv('MYSQL_PASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$db       = getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: 'marketiva';
$port     = getenv('MYSQL_PORT') ?: getenv('DB_PORT') ?: '3306';

$koneksi = mysqli_connect($host, $user, $pass, $db, (int)$port);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>