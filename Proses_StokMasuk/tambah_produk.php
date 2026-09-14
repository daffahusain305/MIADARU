<?php
session_start();
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data user dari Session
    $id_user  = $_SESSION['id_user'] ?? 0;
    $username = $_SESSION['username'] ?? 'System';
    $role     = $_SESSION['role'] ?? 'User';

    $nama_produk = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $jenis_bahan = mysqli_real_escape_string($conn, $_POST['jenis_bahan']);
    $kode_warna  = mysqli_real_escape_string($conn, $_POST['kode_warna']); // Kode Hex (#ffffff)
    $nama_warna_input = mysqli_real_escape_string($conn, $_POST['nama_warna']);
    
    // Ambil array ukuran yang dicentang dari form (jika ada)
    $ukuran_dipilih = isset($_POST['ukuran']) ? $_POST['ukuran'] : array();

    // Logika Stok Baru:
    // Jika ukuran dicentang/tersedia beri 0, jika tidak beri -1 (tidak diproduksi)
    $stok_s   = in_array('S', $ukuran_dipilih)   || in_array('s', $ukuran_dipilih)   ? 0 : -1;
    $stok_m   = in_array('M', $ukuran_dipilih)   || in_array('m', $ukuran_dipilih)   ? 0 : -1;
    $stok_l   = in_array('L', $ukuran_dipilih)   || in_array('l', $ukuran_dipilih)   ? 0 : -1;
    $stok_xl  = in_array('XL', $ukuran_dipilih)  || in_array('xl', $ukuran_dipilih)  ? 0 : -1;
    $stok_xxl = in_array('XXL', $ukuran_dipilih) || in_array('xxl', $ukuran_dipilih) ? 0 : -1;

    // Penanganan Upload Foto
    $nama_foto = "";
    if (isset($_FILES['foto_produk']) && $_FILES['foto_produk']['error'] === UPLOAD_ERR_OK) {
        $tmp_name  = $_FILES['foto_produk']['tmp_name'];
        $ext       = pathinfo($_FILES['foto_produk']['name'], PATHINFO_EXTENSION);
        $nama_foto = 'prod_' . date('dmYHis') . '_' . uniqid() . '.' . $ext;
        
        $folder_tujuan = "../uploads/";
        if (!is_dir($folder_tujuan)) {
            mkdir($folder_tujuan, 0777, true);
        }
        
        move_uploaded_file($tmp_name, $folder_tujuan . $nama_foto);
    }

    // 1. Simpan Produk Utama
    $query_produk = "INSERT INTO produk (nama_produk, jenis_bahan) VALUES ('$nama_produk', '$jenis_bahan')";

    if (mysqli_query($conn, $query_produk)) {
        $id_produk_baru = mysqli_insert_id($conn);

        // 2. Pecah string warna berdasarkan koma ( , ) jika user memasukkan lebih dari 1 warna
        $daftar_warna = array_map('trim', explode(',', $nama_warna_input));

        // 3. Simpan setiap varian warna ke tabel `varian_warna`
        foreach ($daftar_warna as $warna_tunggal) {
            if (!empty($warna_tunggal)) {
                $warna_clean = mysqli_real_escape_string($conn, $warna_tunggal);
                
                $query_varian = "INSERT INTO varian_warna 
                                 (id_produk, warna, nama_warna, foto_produk, stok_s, stok_m, stok_l, stok_xl, stok_xxl) 
                                 VALUES 
                                 ('$id_produk_baru', '$kode_warna', '$warna_clean', '$nama_foto', '$stok_s', '$stok_m', '$stok_l', '$stok_xl', '$stok_xxl')";
                
                mysqli_query($conn, $query_varian);
            }
        }

        // 4. CATAT LOG AKTIVITAS (TAMBAH PRODUK)
        $aksi       = "TAMBAH PRODUK";
        $keterangan = "Menambahkan produk baru: " . $nama_produk . " (Bahan: " . $jenis_bahan . ") dengan varian warna (" . $nama_warna_input . ")";

        $q_log = "INSERT INTO log_aktivitas (id_user, username, role, aksi, keterangan) 
                  VALUES ('$id_user', '$username', '$role', '$aksi', '$keterangan')";
        mysqli_query($conn, $q_log);

        // Alert dan Redirect
        echo "<script>
            alert('Produk $nama_produk beserta varian warna berhasil ditambahkan!'); 
            window.location='../index.php?page=StokMasuk';
        </script>";
        exit();
    } else {
        $error_msg = mysqli_error($conn);
        echo "<script>
            alert('Gagal menambahkan produk: $error_msg'); 
            window.location='../index.php?page=StokMasuk';
        </script>";
    }
}
?>