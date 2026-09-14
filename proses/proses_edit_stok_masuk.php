<?php
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Ambil data ID Log yang mau dikoreksi
    $id_log = mysqli_real_escape_string($conn, $_POST['id_log_masuk']);
    
    // 2. DATA LAMA (Yang salah input) untuk dikembalikan (Rollback)
    $id_varian_lama = mysqli_real_escape_string($conn, $_POST['id_varian_lama']);
    $kolom_lama = mysqli_real_escape_string($conn, $_POST['kolom_ukuran_lama']);
    $qty_lama = (int)$_POST['qty_lama'];

    // 3. DATA BARU (Hasil Koreksi)
    $id_varian_baru = mysqli_real_escape_string($conn, $_POST['id_varian']);
    $kolom_baru = mysqli_real_escape_string($conn, $_POST['ukuran_kolom']);
    $qty_baru = (int)$_POST['jumlah'];

    // Mulai Transaksi Database sederhana
    // A. KEMBALIKAN STOK LAMA (Dikurangi karena sebelumnya salah tambah)
    $sql_rollback = "UPDATE varian_warna SET $kolom_lama = $kolom_lama - $qty_lama WHERE id_varian = '$id_varian_lama'";
    
    // B. TAMBAH STOK BARU (Data yang benar)
    $sql_update_baru = "UPDATE varian_warna SET $kolom_baru = $kolom_baru + $qty_baru WHERE id_varian = '$id_varian_baru'";

    // C. UPDATE CATATAN DI LOG (Riwayat)
    $nama_ukuran_baru = strtoupper(str_replace('stok_', '', $kolom_baru));
    $sql_log = "UPDATE log_stok_masuk SET 
                id_varian = '$id_varian_baru', 
                jumlah_masuk = '$qty_baru', 
                ukuran = '$nama_ukuran_baru' 
                WHERE id_log_masuk = '$id_log'";

    // Eksekusi semua perintah
    if (mysqli_query($conn, $sql_rollback)) {
        if (mysqli_query($conn, $sql_update_baru)) {
            if (mysqli_query($conn, $sql_log)) {
                echo "<script>alert('Koreksi Berhasil! Stok lama telah ditarik kembali dan stok baru telah diperbarui.'); window.location='../index.php?page=StokMasuk';</script>";
            } else {
                echo "Gagal update log: " . mysqli_error($conn);
            }
        } else {
            echo "Gagal update stok baru: " . mysqli_error($conn);
        }
    } else {
        echo "Gagal rollback stok lama: " . mysqli_error($conn);
    }
}
?>