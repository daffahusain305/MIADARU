<?php
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_varian = $_POST['id_varian'];
    $foto = $_FILES['foto']['name'];
    $tmp = $_FILES['foto']['tmp_name'];
    
    // Beri nama unik agar tidak bentrok
    $fotobaru = date('dmYHis').$foto;
    $path = "../uploads/".$fotobaru;

    // Ambil nama foto lama untuk dihapus
    $query = mysqli_query($conn, "SELECT foto_produk FROM varian_warna WHERE id_varian = '$id_varian'");
    $data = mysqli_fetch_assoc($query);
    
    if (move_uploaded_file($tmp, $path)) {
        // Hapus foto lama dari folder uploads (jika bukan default)
        if (file_exists("../uploads/".$data['foto_produk'])) {
            unlink("../uploads/".$data['foto_produk']);
        }
        
        // Update database
        mysqli_query($conn, "UPDATE varian_warna SET foto_produk = '$fotobaru' WHERE id_varian = '$id_varian'");
        
        header("Location: ../index.php?page=katalog");
    }
}
?>