<?php
session_start();
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_rekap = mysqli_real_escape_string($conn, $_POST['id_rekap']);
    $jumlah_terjual = intval($_POST['jumlah_terjual']);
    $tgl_rekap = mysqli_real_escape_string($conn, $_POST['tgl_rekap']);

    if (!empty($id_rekap) && $jumlah_terjual > 0) {
        mysqli_query($conn, "UPDATE rekap_penjualan 
                             SET jumlah_terjual = '$jumlah_terjual', tgl_rekap = '$tgl_rekap' 
                             WHERE id_rekap = '$id_rekap'");
    }

    header("Location: ../index.php?page=rekap");
    exit();
}