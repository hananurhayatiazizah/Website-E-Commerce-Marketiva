<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Otomatis membaca konfigurasi dari Railway atau fallback ke localhost jika dibuka di laptop
$host = getenv('altaria.proxy.rlwy.net') ?: 'localhost';
$user = getenv('root') ?: 'root';
$pass = getenv('rRhPdgvKyaFgaZDbdyFpdXAqrfzMKIAF') ?: '';
$db   = getenv('railway') ?: 'marketiva';
$port = getenv('38054') ?: '3306';

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>