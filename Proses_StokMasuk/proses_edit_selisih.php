<?php
session_start();
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_log_masuk = mysqli_real_escape_string($conn, $_POST['id_log_masuk']);
    $barang_lebih = (int)$_POST['barang_lebih'];
    $barang_kurang = (int)$_POST['barang_kurang'];
    $tombol_aksi  = isset($_POST['aksi']) ? $_POST['aksi'] : 'simpan';

    // Ambil info produk untuk proses update stok
    $query_info = mysqli_query($conn, "SELECT l.*, p.nama_produk, v.nama_warna FROM log_stok_masuk l
                                       JOIN varian_warna v ON l.id_varian = v.id_varian
                                       JOIN produk p ON v.id_produk = p.id_produk
                                       WHERE l.id_log_masuk = '$id_log_masuk'");
    $data = mysqli_fetch_assoc($query_info);

    if ($data) {
        $id_varian  = $data['id_varian'];
        $ukuran_raw = strtolower(trim($data['ukuran'])); 
        $kolom_stok = "stok_" . $ukuran_raw; 

        if ($tombol_aksi === 'selesaikan') {
            // ==========================================
            // AKSI 1: SELESAIKAN SELISIH
            // ==========================================

            // 1. Tambah stok jika BARANG LEBIH > 0
            if ($barang_lebih > 0) {
                $sql_stok = "UPDATE varian_warna SET `$kolom_stok` = `$kolom_stok` + $barang_lebih WHERE id_varian = '$id_varian'";
                mysqli_query($conn, $sql_stok);
            }

            // 2. Reset nilai selisih menjadi 0 agar tidak muncul lagi di daftar selisih
            $sql_update = "UPDATE log_stok_masuk 
                           SET barang_lebih = 0, 
                               barang_kurang = 0 
                           WHERE id_log_masuk = '$id_log_masuk'";

            if (mysqli_query($conn, $sql_update)) {
                echo "<script>alert('Selisih berhasil diselesaikan dan stok telah diperbarui!'); window.location='../index.php?page=StokMasuk';</script>";
            } else {
                echo "Error: " . mysqli_error($conn);
            }

        } else {
            // ==========================================
            // AKSI 2: HANYA SIMPAN PERUBAHAN
            // ==========================================
            $sql_update = "UPDATE log_stok_masuk 
                           SET barang_lebih = '$barang_lebih', 
                               barang_kurang = '$barang_kurang' 
                           WHERE id_log_masuk = '$id_log_masuk'";

            if (mysqli_query($conn, $sql_update)) {
                echo "<script>alert('Data koreksi selisih berhasil diperbarui!'); window.location='../index.php?page=StokMasuk';</script>";
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        }
    } else {
        echo "<script>alert('Data log tidak ditemukan!'); window.location='../index.php?page=StokMasuk';</script>";
    }
}
?>