<?php
// Cek apakah sedang running di Railway atau di Laptop (XAMPP)
if (getenv('RAILWAY_ENVIRONMENT') || getenv('MYSQLHOST')) {
    // Kalau di Railway, pakai data publik Railway
    $host = 'gondola.proxy.rlwy.net';
    $user = 'root';
    $pass = 'GkPfROdZaWNwkJZa';
    $db   = 'railway'; // atau 'marketiva'
    $port = 10955;
} else {
    // Kalau lagi di laptop (XAMPP), pakai localhost seperti biasa
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'marketiva';
    $port = 3306;
}

$koneksi = mysqli_connect($host, $user, $pass, $db, $port);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>