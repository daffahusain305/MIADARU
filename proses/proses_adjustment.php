<?php
include __DIR__ . '/../koneksi.php';

$id_varian = $_POST['id_varian'];
$size      = $_POST['size']; // Misal: s, m, l
$baru      = $_POST['baru'];
$lama      = $_POST['lama'];
$ket       = $_POST['ket'];

$kolom_stok = "stok_" . $size;

// 1. Update stok di tabel varian_warna
$update = mysqli_query($conn, "UPDATE varian_warna SET $kolom_stok = '$baru' WHERE id_varian = '$id_varian'");

// 2. Catat ke log_adjustment
if ($update) {
    mysqli_query($conn, "INSERT INTO log_adjustment (id_varian, stok_sebelum, stok_sesudah, keterangan) 
                         VALUES ('$id_varian', '$lama', '$baru', '$ket')");
    echo "Sukses";
} else {
    echo "Gagal";
}
?>