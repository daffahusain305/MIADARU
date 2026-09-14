<?php
session_start(); 
include __DIR__ . '/../koneksi.php';
include __DIR__ . '/../proses/fungsi.php';


if (isset($_GET['id_log'])) {
    $id_log_masuk = mysqli_real_escape_string($conn, $_GET['id_log']);

    // --- LANGKAH 1: AMBIL DATA SEBELUM DIUPDATE ---
    $query_log = "SELECT l.*, p.nama_produk, v.nama_warna FROM log_stok_masuk l
                  JOIN varian_warna v ON l.id_varian = v.id_varian
                  JOIN produk p ON v.id_produk = p.id_produk
                  WHERE l.id_log_masuk = '$id_log_masuk'";
    
    $res_log = mysqli_query($conn, $query_log);

    if ($res_log && mysqli_num_rows($res_log) > 0) {
        $data_log = mysqli_fetch_assoc($res_log);
        
        $nama_p  = $data_log['nama_produk'];
        $warna   = $data_log['nama_warna'];
        $ukuran  = strtoupper($data_log['ukuran']);

        // --- LANGKAH 2: UPDATE NILAI SELISIH MENJADI 0 ---
        // Kita tidak mendelete baris data karena QTY NOTA tetap harus ada di riwayat utama.
        // Kita hanya membersihkan nilai selisihnya saja.
        $sql_clear_selisih = "UPDATE log_stok_masuk 
                              SET barang_lebih = 0, barang_kurang = 0 
                              WHERE id_log_masuk = '$id_log_masuk'";

        if (mysqli_query($conn, $sql_clear_selisih)) {
            
            // --- LANGKAH 3: LOG AKTIVITAS (AUDIT TRAIL) ---
            $id_user    = $_SESSION['id_user'];
            $username   = $_SESSION['username'];
            $role       = $_SESSION['role'];
            $aksi       = "HAPUS SELISIH STOK";
            $keterangan = "Menghapus/membersihkan catatan selisih pada pengiriman produk $nama_p ($warna) Size $ukuran. Transaksi utama (Qty Nota) tetap dipertahankan.";

            catatLog($conn, $id_user, $username, $role, $aksi, $keterangan);

            echo "<script>alert('Catatan selisih berhasil dihapus/dibersihkan!'); window.location='../index.php?page=StokMasuk';</script>";
        } else {
            echo "Gagal memperbarui data: " . mysqli_error($conn);
        }
    } else {
        echo "<script>alert('Data riwayat tidak ditemukan!'); window.location='../index.php?page=StokMasuk';</script>";
    }
} else {
    header("Location: ../index.php?page=StokMasuk");
    exit();
}
?>