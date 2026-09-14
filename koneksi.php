<?php
// Atur zona waktu ke Asia/Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');

$conn = mysqli_connect("localhost", "root", "", "toko_hijab");

if (!$conn) { 
    die("Koneksi Gagal: " . mysqli_connect_error()); 
}

// Tambahkan juga ini agar database MySQL sinkron dengan jam PHP
mysqli_query($conn, "SET time_zone = '+07:00'");
?>

