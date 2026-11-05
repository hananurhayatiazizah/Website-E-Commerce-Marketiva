<?php
// Mengambil variabel database dari Railway, atau fallback ke localhost jika di XAMPP
$host     = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$user     = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$pass     = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$db       = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'marketiva';
$port     = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';

$koneksi = mysqli_connect($host, $user, $pass, $db, (int)$port);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>