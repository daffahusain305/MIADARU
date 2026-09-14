<?php
session_start();
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pembelian  = (int)$_POST['id_pembelian'];
    $tgl_pembelian = mysqli_real_escape_string($conn, $_POST['tgl_pembelian']);
    $supplier      = mysqli_real_escape_string($conn, $_POST['supplier']);
    
    $no_nota_raw   = trim($_POST['no_nota']);
    $no_nota       = !empty($no_nota_raw) ? "'" . mysqli_real_escape_string($conn, $no_nota_raw) . "'" : "NULL";
    
    $target_size_arr = isset($_POST['target_size']) ? $_POST['target_size'] : [];
    $target_size     = mysqli_real_escape_string($conn, implode(', ', $target_size_arr));
    $jumlah_roll     = (int)$_POST['jumlah_roll'];

    $id_varian_input         = trim($_POST['id_varian']);
    $nama_produk_manual_raw  = trim($_POST['nama_produk_manual']);
    $jenis_bahan_manual_raw  = trim($_POST['jenis_bahan_manual']);
    $warna_manual_raw        = trim($_POST['warna_manual']);

    if (!empty($id_varian_input)) {
        $id_varian          = (int)$id_varian_input;
        $nama_produk_manual = "NULL";
        $jenis_bahan_manual = "NULL";
        $warna_manual       = "NULL";
    } elseif (!empty($nama_produk_manual_raw)) {
        $id_varian          = "NULL";
        $nama_produk_manual = "'" . mysqli_real_escape_string($conn, $nama_produk_manual_raw) . "'";
        $jenis_bahan_manual = !empty($jenis_bahan_manual_raw) ? "'" . mysqli_real_escape_string($conn, $jenis_bahan_manual_raw) . "'" : "NULL";
        $warna_manual       = !empty($warna_manual_raw) ? "'" . mysqli_real_escape_string($conn, $warna_manual_raw) . "'" : "NULL";
    } else {
        echo "<script>alert('Pilih produk terdaftar ATAU isi produk manual!'); window.history.back();</script>";
        exit();
    }

    $sql = "UPDATE pembelian_roll SET 
            tgl_pembelian = '$tgl_pembelian',
            supplier = '$supplier',
            no_nota = $no_nota,
            id_varian = $id_varian,
            nama_produk_manual = $nama_produk_manual,
            jenis_bahan_manual = $jenis_bahan_manual,
            warna_manual = $warna_manual,
            jumlah_roll = $jumlah_roll,
            target_size = '$target_size'
            WHERE id_pembelian = $id_pembelian";

    if (mysqli_query($conn, $sql)) {
        header("Location: ../index.php?page=pembelian&status=updated");
        exit();
    } else {
        echo "Error Database: " . mysqli_error($conn);
    }
}
?>