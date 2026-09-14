<?php
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_produk = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $jenis_bahan = mysqli_real_escape_string($conn, $_POST['jenis_bahan']);
    $warna       = $_POST['warna'];
    $nama_warna  = mysqli_real_escape_string($conn, $_POST['nama_warna']);

    // 1. Tangkap array ukuran yang dicentang dari modal
    $size_pilihan = isset($_POST['size_pilihan']) ? $_POST['size_pilihan'] : [];

    // 2. Logika: Jika centang ada, set 0 (tersedia), jika tidak ada set -1 (tidak dijual)
    // Karena ini produk BARU, biasanya stok awal dimulai dari 0 atau input manual
    $s   = in_array('s', $size_pilihan)   ? 0 : -1;
    $m   = in_array('m', $size_pilihan)   ? 0 : -1;
    $l   = in_array('l', $size_pilihan)   ? 0 : -1;
    $xl  = in_array('xl', $size_pilihan)  ? 0 : -1;
    $xxl = in_array('xxl', $size_pilihan) ? 0 : -1;

    // Proses Upload Foto
    $foto_name = $_FILES['foto']['name'];
    $tmp_name  = $_FILES['foto']['tmp_name'];
    
    if ($foto_name) {
        $ekstensi  = pathinfo($foto_name, PATHINFO_EXTENSION);
        $foto_baru = "prod_" . date('dmYHis') . "_" . uniqid() . "." . $ekstensi;
        move_uploaded_file($tmp_name, "../uploads/" . $foto_baru);
    } else {
        $foto_baru = "default.jpg";
    }

    // 3. Simpan ke tabel produk
    $query_prod = "INSERT INTO produk (nama_produk, jenis_bahan) VALUES ('$nama_produk', '$jenis_bahan')";
    
    if (mysqli_query($conn, $query_prod)) {
        $id_baru = mysqli_insert_id($conn);
        
        // 4. Simpan varian pertama ke tabel varian_warna
        $query_var = "INSERT INTO varian_warna (id_produk, warna, nama_warna, foto_produk, stok_s, stok_m, stok_l, stok_xl, stok_xxl) 
                      VALUES ('$id_baru', '$warna', '$nama_warna', '$foto_baru', '$s', '$m', '$l', '$xl', '$xxl')";
        
        if (mysqli_query($conn, $query_var)) {
            header("Location: ../index.php?page=katalog");
        } else {
            echo "Gagal simpan varian: " . mysqli_error($conn);
        }
    } else {
        echo "Gagal simpan produk: " . mysqli_error($conn);
    }
}
?>