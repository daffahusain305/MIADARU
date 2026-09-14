<?php
session_start();
include __DIR__ . '/../koneksi.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir, silakan login ulang']);
    exit();
}

$aksi = $_GET['aksi'] ?? '';

// 1. HAPUS SPESIFIK UKURAN (Set ke -1)
if ($aksi === 'hapus_ukuran') {
    $id_varian = mysqli_real_escape_string($conn, $_POST['id_varian']);
    $size      = mysqli_real_escape_string($conn, $_POST['size']); // s, m, l, xl, xxl
    $kolom     = "stok_" . strtolower($size);

    $query = mysqli_query($conn, "UPDATE varian_warna SET $kolom = -1 WHERE id_varian = '$id_varian'");
    
    if ($query) {
        echo json_encode(['status' => 'success', 'message' => 'Ukuran berhasil dinonaktifkan/dihapus!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus ukuran']);
    }
    exit();
}

// 2. HAPUS WARNA / VARIAN
if ($aksi === 'hapus_varian') {
    $id_varian = mysqli_real_escape_string($conn, $_POST['id_varian']);

    $query = mysqli_query($conn, "DELETE FROM varian_warna WHERE id_varian = '$id_varian'");
    
    if ($query) {
        echo json_encode(['status' => 'success', 'message' => 'Varian warna berhasil dihapus!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus varian warna']);
    }
    exit();
}

// 3. HAPUS KESELURUHAN PRODUK (Hapus Master Produk & Seluruh Varian Warnanya)
if ($aksi === 'hapus_produk') {
    $id_produk = mysqli_real_escape_string($conn, $_POST['id_produk']);

    // Hapus varian dulu
    mysqli_query($conn, "DELETE FROM varian_warna WHERE id_produk = '$id_produk'");
    // Hapus induk produk
    $query = mysqli_query($conn, "DELETE FROM produk WHERE id_produk = '$id_produk'");
    
    if ($query) {
        echo json_encode(['status' => 'success', 'message' => 'Seluruh produk beserta semua variannya berhasil dihapus!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus produk']);
    }
    exit();
}
?>