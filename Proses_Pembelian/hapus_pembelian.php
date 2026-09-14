<?php
session_start();
include __DIR__ . '/../koneksi.php';

if (isset($_GET['id'])) {
    $id_pembelian = (int)$_GET['id'];

    $sql = "DELETE FROM pembelian_roll WHERE id_pembelian = $id_pembelian";

    if (mysqli_query($conn, $sql)) {
        header("Location: ../index.php?page=pembelian&status=deleted");
        exit();
    } else {
        echo "Error Database: " . mysqli_error($conn);
    }
} else {
    header("Location: ../index.php?page=pembelian");
    exit();
}
?>