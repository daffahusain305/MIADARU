<?php
session_start();
include __DIR__ . '/../koneksi.php';

// Validasi autentikasi
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Ambil parameter dari URL
$id_pembelian = isset($_GET['id']) ? intval($_GET['id']) : 0;
$status       = isset($_GET['status']) ? intval($_GET['status']) : 0;
$tgl_riwayat  = isset($_GET['tgl']) ? $_GET['tgl'] : date('Y-m-d');

if ($id_pembelian > 0) {
    // Update status is_resolved di tabel pembelian_roll
    $query = "UPDATE pembelian_roll SET is_resolved = '$status' WHERE id_pembelian = '$id_pembelian'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Status resolved berhasil diperbarui.";
    } else {
        $_SESSION['error'] = "Gagal memperbarui status: " . mysqli_error($conn);
    }
}

// Redirect kembali ke halaman pembelian dengan membawa filter tanggal sebelumnya
header("Location: ../index.php?page=pembelian&tgl_riwayat=" . urlencode($tgl_riwayat));
exit();