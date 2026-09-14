<?php
include __DIR__ . '/../koneksi.php';

$aksi = $_GET['aksi'] ?? '';
// Disesuaikan dengan key dari data menu Anda ('Velocity')
$url_kembali = "../index.php?page=Velocity"; 

if ($aksi === 'hapus') {
    $id_log = mysqli_real_escape_string($conn, $_GET['id']);

    // 1. Ambil data log lama sebelum dihapus
    $q_log = mysqli_query($conn, "SELECT id_varian, ukuran, jumlah FROM log_distribusi WHERE id_log = '$id_log'");
    if (mysqli_num_rows($q_log) > 0) {
        $log = mysqli_fetch_assoc($q_log);
        $id_varian = $log['id_varian'];
        $ukuran = strtolower($log['ukuran']); 
        $jumlah_lama = intval($log['jumlah']);
        $nama_kolom_stok = "stok_" . $ukuran; 

        // 2. KEMBALIKAN STOK ke gudang utama
        $update_stok = mysqli_query($conn, "UPDATE varian_warna 
                                            SET $nama_kolom_stok = $nama_kolom_stok + $jumlah_lama 
                                            WHERE id_varian = '$id_varian'");

        // 3. Hapus log riwayatnya
        $hapus_log = mysqli_query($conn, "DELETE FROM log_distribusi WHERE id_log = '$id_log'");

        if ($update_stok && $hapus_log) {
            echo "<script>alert('Perpindahan stok berhasil dibatalkan! Stok telah dikembalikan.'); window.location='$url_kembali';</script>";
        } else {
            echo "<script>alert('Gagal membatalkan perpindahan stok!'); window.location='$url_kembali';</script>";
        }
    } else {
        echo "<script>alert('Data mutasi tidak ditemukan!'); window.location='$url_kembali';</script>";
    }
    exit();

} elseif ($aksi === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_log = mysqli_real_escape_string($conn, $_POST['id_log']);
    $jumlah_baru = intval($_POST['jumlah_baru']);

    // 1. Ambil data log lama sebelum diupdate
    $q_log = mysqli_query($conn, "SELECT id_varian, ukuran, jumlah FROM log_distribusi WHERE id_log = '$id_log'");
    if (mysqli_num_rows($q_log) > 0) {
        $log = mysqli_fetch_assoc($q_log);
        $id_varian = $log['id_varian'];
        $ukuran = strtolower($log['ukuran']); 
        $jumlah_lama = intval($log['jumlah']);
        $nama_kolom_stok = "stok_" . $ukuran;

        // 2. Hitung selisih perubahan
        $selisih = $jumlah_baru - $jumlah_lama;

        // 3. Update stok gudang utama di tabel varian_warna berdasarkan selisihnya
        $update_stok = mysqli_query($conn, "UPDATE varian_warna 
                                            SET $nama_kolom_stok = $nama_kolom_stok - ($selisih) 
                                            WHERE id_varian = '$id_varian'");

        // 4. Update data jumlah di tabel log_distribusi
        $update_log = mysqli_query($conn, "UPDATE log_distribusi SET jumlah = '$jumlah_baru' WHERE id_log = '$id_log'");

        if ($update_stok && $update_log) {
            echo "<script>alert('Jumlah perpindahan stok berhasil diubah!'); window.location='$url_kembali';</script>";
        } else {
            echo "<script>alert('Gagal mengubah data perpindahan!'); window.location='$url_kembali';</script>";
        }
    } else {
        echo "<script>alert('Data mutasi tidak ditemukan!'); window.location='$url_kembali';</script>";
    }
    exit();
}
?>