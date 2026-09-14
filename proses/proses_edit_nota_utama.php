<?php
session_start();
include __DIR__ . '/../koneksi.php';
include 'fungsi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_log_masuk = mysqli_real_escape_string($conn, $_POST['id_log_masuk']);
    $jumlah_baru  = (int)$_POST['jumlah_masuk'];

    // 1. Ambil data lama untuk hitung selisih koreksi stok
    $query_info = mysqli_query($conn, "SELECT l.*, p.nama_produk, v.nama_warna FROM log_stok_masuk l
                                       JOIN varian_warna v ON l.id_varian = v.id_varian
                                       JOIN produk p ON v.id_produk = p.id_produk
                                       WHERE l.id_log_masuk = '$id_log_masuk'");
    $data = mysqli_fetch_assoc($query_info);
    
    $id_varian   = $data['id_varian'];
    $jumlah_lama = (int)$data['jumlah_masuk'];
    $kolom_ukuran= "stok_" . strtolower($data['ukuran']);

    // 2. Hitung selisih untuk dimasukkan ke katalog utama
    // Jika jumlah baru 30 dan lama 25, maka katalog ditambah 5 (+5)
    // Jika jumlah baru 20 dan lama 25, maka katalog dikurang 5 (-5)
    $selisih_koreksi = $jumlah_baru - $jumlah_lama;

    // 3. Update stok utama di katalog produk
    $sql_update_katalog = "UPDATE varian_warna SET $kolom_ukuran = $kolom_ukuran + $selisih_koreksi WHERE id_varian = '$id_varian'";
    
    // 4. Update log transaksi stok masuk
    $sql_update_log = "UPDATE log_stok_masuk SET jumlah_masuk = '$jumlah_baru' WHERE id_log_masuk = '$id_log_masuk'";

    if (mysqli_query($conn, $sql_update_katalog) && mysqli_query($conn, $sql_update_log)) {
        
        $id_user    = $_SESSION['id_user'];
        $username   = $_SESSION['username'];
        $role       = $_SESSION['role'];
        $aksi       = "EDIT NOTA STOK MASUK";
        $keterangan = "Mengubah QTY Nota produk " . $data['nama_produk'] . " (" . $data['nama_warna'] . ") dari $jumlah_lama pcs menjadi $jumlah_baru pcs.";

        catatLog($conn, $id_user, $username, $role, $aksi, $keterangan);

        echo "<script>alert('Data QTY Nota berhasil diperbarui dan stok katalog dikoreksi!'); window.location='../index.php?page=StokMasuk';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>