<?php
// Mengambil variabel database dari Railway, atau fallback ke localhost jika di XAMPP
$host     = getenv('gondola.proxy.rlwy.net') ?: getenv('DB_HOST') ?: 'localhost';
$user     = getenv('root') ?: getenv('DB_USER') ?: 'root';
$pass     = getenv('GkPfROdZaWNwkjZaOMBRMPDIpqslrOaz') ?: getenv('DB_PASSWORD') ?: '';
$db       = getenv('railway') ?: getenv('DB_NAME') ?: 'marketiva';
$port     = getenv('10955') ?: getenv('DB_PORT') ?: '3306';

$koneksi = mysqli_connect($host, $user, $pass, $db, (int)$port);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>