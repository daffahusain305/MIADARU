<?php
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_varian = mysqli_real_escape_string($conn, $_POST['id_varian']);
    $nama_warna = mysqli_real_escape_string($conn, $_POST['nama_warna']);
    $kode_warna = mysqli_real_escape_string($conn, $_POST['kode_warna']);

    $query = "UPDATE varian_warna SET 
              nama_warna = '$nama_warna', 
              warna = '$kode_warna' 
              WHERE id_varian = '$id_varian'";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Warna berhasil diperbarui!'); window.location='../index.php?page=katalog';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui warna!'); window.location='../index.php?page=katalog';</script>";
    }
}
?>