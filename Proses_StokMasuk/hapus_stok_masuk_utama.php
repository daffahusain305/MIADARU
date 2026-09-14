<?php
session_start(); 
include __DIR__ . '/../koneksi.php';
include __DIR__ . '/../proses/fungsi.php'; 

if (isset($_GET['id_log'])) {
    $id_log_masuk = mysqli_real_escape_string($conn, $_GET['id_log']);

    $query_log = "SELECT l.*, p.nama_produk, v.nama_warna FROM log_stok_masuk l
                  JOIN varian_warna v ON l.id_varian = v.id_varian
                  JOIN produk p ON v.id_produk = p.id_produk
                  WHERE l.id_log_masuk = '$id_log_masuk'";
    
    $res_log = mysqli_query($conn, $query_log);

    if ($res_log && mysqli_num_rows($res_log) > 0) {
        $data_log = mysqli_fetch_assoc($res_log);
        
        $id_varian     = $data_log['id_varian'];
        $jumlah_masuk  = (int)$data_log['jumlah_masuk']; // Nilai nota yang akan dikurangkan kembali dari katalog
        $ukuran        = strtolower($data_log['ukuran']); 
        $kolom_ukuran  = "stok_" . $ukuran;

        // 1. Rollback saldo di tabel katalog utama
        $sql_rollback = "UPDATE varian_warna SET $kolom_ukuran = $kolom_ukuran - $jumlah_masuk WHERE id_varian = '$id_varian'";

        // 2. Hapus total baris riwayat data ini
        $sql_delete = "DELETE FROM log_stok_masuk WHERE id_log_masuk = '$id_log_masuk'";

        if (mysqli_query($conn, $sql_rollback) && mysqli_query($conn, $sql_delete)) {
            
            $id_user    = $_SESSION['id_user'];
            $username   = $_SESSION['username'];
            $role       = $_SESSION['role'];
            $aksi       = "HAPUS TOTAL INPUT STOK";
            $keterangan = "Menghapus total riwayat transaksi masuk produk " . $data_log['nama_produk'] . ". Saldo katalog dipotong kembali (-$jumlah_masuk pcs).";

            catatLog($conn, $id_user, $username, $role, $aksi, $keterangan);

            echo "<script>alert('Transaksi berhasil dihapus total dan stok katalog dikembalikan semula!'); window.location='../index.php?page=StokMasuk';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        echo "<script>alert('Data tidak ditemukan!'); window.location='../index.php?page=StokMasuk';</script>";
    }
} else {
    header("Location: ../index.php?page=StokMasuk");
    exit();
}
?>