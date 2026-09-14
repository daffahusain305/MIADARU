<?php
session_start();
include __DIR__ . '/../koneksi.php';

if (isset($_GET['id'])) {
    $id_rekap = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Proses hapus dari database
    $delete = mysqli_query($conn, "DELETE FROM rekap_penjualan WHERE id_rekap = '$id_rekap'");
}

header("Location: ../index.php?page=rekap");
exit();