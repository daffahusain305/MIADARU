<?php
session_start();
include __DIR__ . '/../koneksi.php';
include __DIR__ . '/../proses/fungsi.php'; // Sesuaikan path fungsi jika dipakai

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produk    = $_POST['id_produk'];
    $id_varian    = $_POST['id_varian'];
    $ukuran_kolom = $_POST['ukuran_kolom']; // berisi stok_s, stok_m, dll
    $jumlah       = intval($_POST['jumlah']);
    $keterangan   = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    // Ambil info nama produk dan nama warna untuk dicatat ke log_retur
    $query_info = mysqli_query($conn, "SELECT p.nama_produk, v.nama_warna FROM varian_warna v 
                                       JOIN produk p ON v.id_produk = p.id_produk 
                                       WHERE v.id_varian = '$id_varian'");
    $info = mysqli_fetch_assoc($query_info);
    $nama_produk = $info['nama_produk'];
    $nama_warna  = $info['nama_warna'];

    // Simpan catatan retur dengan status awal 'Pending' (TANPA KURANGI STOK)
    $query_insert = "INSERT INTO log_retur (tgl_retur, id_varian, nama_produk, nama_warna, ukuran, jumlah_retur, keterangan, status) 
                     VALUES (NOW(), '$id_varian', '$nama_produk', '$nama_warna', '$ukuran_kolom', '$jumlah', '$keterangan', 'Pending')";
    
    if (mysqli_query($conn, $query_insert)) {
        // Jika kamu punya fungsi catatLog aktivitas user, panggil di sini
        if (function_exists('catatLog')) {
            // Ambil data session user login
            $id_user  = $_SESSION['id_user'];
            $username = $_SESSION['username'];
            $role     = $_SESSION['role'];
            
            // Panggil fungsi dengan 6 argumen sesuai fungsi.php
            catatLog($conn, $id_user, $username, $role, 'Retur Pending', "Mencatat data retur baru (Pending) untuk produk $nama_produk ($nama_warna)");
        }
        echo "<script>alert('Catatan retur berhasil disimpan sebagai Pending!'); window.location='../index.php?page=ReturBarang';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data!'); window.location='../index.php?page=ReturBarang';</script>";
    }
}
?>