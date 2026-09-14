<?php
session_start();
include __DIR__ . '/../koneksi.php';

// Cek parameter yang dikirim dari tombol
if (isset($_GET['id_pembelian']) && isset($_GET['aksi'])) {
    $id_pembelian = intval($_GET['id_pembelian']);
    $aksi         = intval($_GET['aksi']); // 1 = Resolve, 0 = Batal Resolve (Pending)

    if ($id_pembelian > 0) {
        // Query update status is_resolved di tabel pembelian_roll
        $sql = "UPDATE pembelian_roll SET is_resolved = '$aksi' WHERE id_pembelian = '$id_pembelian'";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Status berhasil diperbarui!";
        } else {
            $_SESSION['error'] = "Gagal memperbarui status: " . mysqli_error($conn);
        }
    }
}

// Redirect kembali ke halaman StokMasuk
header("Location: ../index.php?page=StokMasuk");
exit;