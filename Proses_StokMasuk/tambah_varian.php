<?php
session_start();
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produk  = mysqli_real_escape_string($conn, $_POST['id_produk']);
    $kode_warna = mysqli_real_escape_string($conn, $_POST['kode_warna']); // Hex Code (#ffffff)
    $nama_warna = mysqli_real_escape_string($conn, $_POST['nama_warna']);

    // Ambil array ukuran yang dicentang dari form modal/page Tambah Varian
    $ukuran_dipilih = isset($_POST['ukuran']) ? $_POST['ukuran'] : array();

    // Logika Stok:
    // Jika ukuran DICENTANG (tersedia/diproduksi), beri stok awal 0.
    // Jika TIDAK DICENTANG (tidak diproduksi), beri nilai -1 (TIDAK DIPAKAI).
    $stok_s   = in_array('S', $ukuran_dipilih)   || in_array('s', $ukuran_dipilih)   ? 0 : -1;
    $stok_m   = in_array('M', $ukuran_dipilih)   || in_array('m', $ukuran_dipilih)   ? 0 : -1;
    $stok_l   = in_array('L', $ukuran_dipilih)   || in_array('l', $ukuran_dipilih)   ? 0 : -1;
    $stok_xl  = in_array('XL', $ukuran_dipilih)  || in_array('xl', $ukuran_dipilih)  ? 0 : -1;
    $stok_xxl = in_array('XXL', $ukuran_dipilih) || in_array('xxl', $ukuran_dipilih) ? 0 : -1;

    // Penanganan Upload Foto Varian
    $nama_foto = "";
    if (isset($_FILES['foto_produk']) && $_FILES['foto_produk']['error'] === UPLOAD_ERR_OK) {
        $tmp_name  = $_FILES['foto_produk']['tmp_name'];
        $ext       = pathinfo($_FILES['foto_produk']['name'], PATHINFO_EXTENSION);
        $nama_foto = 'varian_' . date('dmYHis') . '_' . uniqid() . '.' . $ext;
        
        $folder_tujuan = "../uploads/";
        if (!is_dir($folder_tujuan)) {
            mkdir($folder_tujuan, 0777, true);
        }
        
        move_uploaded_file($tmp_name, $folder_tujuan . $nama_foto);
    }

    // Insert Varian Warna Baru ke tabel `varian_warna` dengan nilai stok dinamis
    $query = "INSERT INTO varian_warna 
              (id_produk, warna, nama_warna, foto_produk, stok_s, stok_m, stok_l, stok_xl, stok_xxl) 
              VALUES 
              ('$id_produk', '$kode_warna', '$nama_warna', '$nama_foto', '$stok_s', '$stok_m', '$stok_l', '$stok_xl', '$stok_xxl')";

    if (mysqli_query($conn, $query)) {
        echo "<script>
            alert('Varian warna $nama_warna berhasil ditambahkan!'); 
            window.location='../index.php?page=StokMasuk';
        </script>";
        exit();
    } else {
        $error_msg = mysqli_error($conn);
        echo "<script>
            alert('Gagal menambahkan varian: $error_msg'); 
            window.location='../index.php?page=StokMasuk';
        </script>";
    }
}
?>