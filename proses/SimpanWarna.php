<?php
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_produk   = $_POST['id_produk'];
    $warna       = $_POST['warna']; 
    $nama_warna  = mysqli_real_escape_string($conn, $_POST['nama_warna']); 
    
    // Tangkap array ukuran yang dicentang
    $size_pilihan = isset($_POST['size_pilihan']) ? $_POST['size_pilihan'] : [];

    // Logika: Jika ukuran dicentang, ambil nilai inputnya. Jika tidak, set ke -1 (Hidden)
    $s   = in_array('s', $size_pilihan)   ? ($_POST['s']   ?: 0) : -1;
    $m   = in_array('m', $size_pilihan)   ? ($_POST['m']   ?: 0) : -1;
    $l   = in_array('l', $size_pilihan)   ? ($_POST['l']   ?: 0) : -1;
    $xl  = in_array('xl', $size_pilihan)  ? ($_POST['xl']  ?: 0) : -1;
    $xxl = in_array('xxl', $size_pilihan) ? ($_POST['xxl'] ?: 0) : -1;

    // Proses Upload Foto
    $foto_name = $_FILES['foto']['name'];
    $tmp_name  = $_FILES['foto']['tmp_name'];
    
    if ($foto_name) {
        $ekstensi  = pathinfo($foto_name, PATHINFO_EXTENSION);
        $foto_baru = "varian_" . date('dmYHis') . "_" . uniqid() . "." . $ekstensi;
        move_uploaded_file($tmp_name, "../uploads/" . $foto_baru);
    } else {
        $foto_baru = "default.jpg";
    }

    $query = "INSERT INTO varian_warna (id_produk, warna, nama_warna, foto_produk, stok_s, stok_m, stok_l, stok_xl, stok_xxl) 
              VALUES ('$id_produk', '$warna', '$nama_warna', '$foto_baru', '$s', '$m', '$l', '$xl', '$xxl')";

    if (mysqli_query($conn, $query)) {
        header("Location: ../index.php?page=katalog");
    } else {
        echo "Gagal: " . mysqli_error($conn);
    }
}
?>