<?php
session_start();
include __DIR__ . '/../koneksi.php';

if (isset($_GET['id_varian'])) {
    $id_varian = mysqli_real_escape_string($conn, $_GET['id_varian']);

    // 1. Cari id_produk dari varian yang akan dihapus (perbaikan typo id_id_varian menjadi id_varian)
    $query_get_produk = mysqli_query($conn, "SELECT id_produk FROM varian_warna WHERE id_varian = '$id_varian'");
    $data_varian = mysqli_fetch_assoc($query_get_produk);

    if ($data_varian) {
        $id_produk = $data_varian['id_produk'];

        // 2. Hapus riwayat di log_stok_masuk terlebih dahulu
        mysqli_query($conn, "DELETE FROM log_stok_masuk WHERE id_varian = '$id_varian'");

        // 3. Hapus varian warna dari tabel varian_warna
        $hapus_varian = mysqli_query($conn, "DELETE FROM varian_warna WHERE id_varian = '$id_varian'");

        if ($hapus_varian) {
            // 4. Cek apakah masih ada varian lain yang tersisa untuk produk ini
            $cek_varian_lain = mysqli_query($conn, "SELECT COUNT(*) as sisa FROM varian_warna WHERE id_produk = '$id_produk'");
            $row_sisa = mysqli_fetch_assoc($cek_varian_lain);

            // Jika tidak ada varian lain (sisa = 0), hapus juga master datanya di tabel produk
            if ($row_sisa['sisa'] == 0) {
                mysqli_query($conn, "DELETE FROM produk WHERE id_produk = '$id_produk'");
            }

            echo "<script>
                alert('Produk/Varian berhasil dihapus!');
                window.location='../index.php?page=katalog';
            </script>";
            exit();
        } else {
            $error = mysqli_error($conn);
            echo "<script>
                alert('Gagal menghapus varian: $error');
                window.location='../index.php?page=katalog';
            </script>";
            exit();
        }
    } else {
        echo "<script>
            alert('Data varian tidak ditemukan!');
            window.location='../index.php?page=katalog';
        </script>";
        exit();
    }
} else {
    header("Location: ../index.php?page=katalog");
    exit();
}
?>