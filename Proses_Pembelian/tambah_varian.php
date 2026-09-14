<?php
session_start();
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produk        = mysqli_real_escape_string($conn, $_POST['id_produk']);
    $kode_warna       = mysqli_real_escape_string($conn, $_POST['kode_warna']);
    $nama_warna_input = mysqli_real_escape_string($conn, $_POST['nama_warna']);
    
    // Ambil array ukuran yang dicentang
    $ukuran_dipilih = isset($_POST['ukuran']) ? $_POST['ukuran'] : array();

    // Logika Stok: dicentang = 0, tidak dicentang = -1
    $stok_s   = in_array('S', $ukuran_dipilih)   || in_array('s', $ukuran_dipilih)   ? 0 : -1;
    $stok_m   = in_array('M', $ukuran_dipilih)   || in_array('m', $ukuran_dipilih)   ? 0 : -1;
    $stok_l   = in_array('L', $ukuran_dipilih)   || in_array('l', $ukuran_dipilih)   ? 0 : -1;
    $stok_xl  = in_array('XL', $ukuran_dipilih)  || in_array('xl', $ukuran_dipilih)  ? 0 : -1;
    $stok_xxl = in_array('XXL', $ukuran_dipilih) || in_array('xxl', $ukuran_dipilih) ? 0 : -1;

    // Upload Foto
    $nama_foto = "";
    if (isset($_FILES['foto_produk']) && $_FILES['foto_produk']['error'] === UPLOAD_ERR_OK) {
        $ext       = pathinfo($_FILES['foto_produk']['name'], PATHINFO_EXTENSION);
        $nama_foto = 'var_' . date('dmYHis') . '_' . uniqid() . '.' . $ext;
        
        $folder_tujuan = "../uploads/";
        if (!is_dir($folder_tujuan)) {
            mkdir($folder_tujuan, 0777, true);
        }
        move_uploaded_file($_FILES['foto_produk']['tmp_name'], $folder_tujuan . $nama_foto);
    }

    // Pecah input warna berdasarkan koma (,)
    $daftar_warna = array_map('trim', explode(',', $nama_warna_input));

    $berhasil = true;
    foreach ($daftar_warna as $warna_tunggal) {
        if (!empty($warna_tunggal)) {
            $warna_clean = mysqli_real_escape_string($conn, $warna_tunggal);
            
            $query_varian = "INSERT INTO varian_warna 
                             (id_produk, warna, nama_warna, foto_produk, stok_s, stok_m, stok_l, stok_xl, stok_xxl) 
                             VALUES 
                             ('$id_produk', '$kode_warna', '$warna_clean', '$nama_foto', '$stok_s', '$stok_m', '$stok_l', '$stok_xl', '$stok_xxl')";
            
            if (!mysqli_query($conn, $query_varian)) {
                $berhasil = false;
            }
        }
    }

    if ($berhasil) {
        echo "<script>
            alert('Varian warna berhasil ditambahkan!'); 
            window.location='../index.php?page=pembelian';
        </script>";
    } else {
        $error_msg = mysqli_error($conn);
        echo "<script>
            alert('Terjadi kesalahan saat menambah varian: $error_msg'); 
            window.location='../index.php?page=pembelian';
        </script>";
    }
}
?>