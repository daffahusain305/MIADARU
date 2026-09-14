<?php
session_start();
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tgl_pembelian = mysqli_real_escape_string($conn, $_POST['tgl_pembelian']);
    $supplier      = mysqli_real_escape_string($conn, $_POST['supplier']);
    $no_nota       = !empty($_POST['no_nota']) ? "'" . mysqli_real_escape_string($conn, $_POST['no_nota']) . "'" : "NULL";
    
    $id_varian     = (int)$_POST['id_varian'];
    $jumlah_roll   = (int)$_POST['jumlah_roll'];
    
    $stok_s        = (int)$_POST['stok_s'];
    $stok_m        = (int)$_POST['stok_m'];
    $stok_l        = (int)$_POST['stok_l'];
    $stok_xl       = (int)$_POST['stok_xl'];
    $stok_xxl      = (int)$_POST['stok_xxl'];

    $total_pcs = $stok_s + $stok_m + $stok_l + $stok_xl + $stok_xxl;

    if ($id_varian <= 0 || $jumlah_roll <= 0 || $total_pcs <= 0) {
        echo "<script>alert('Mohon isi varian, jumlah roll, dan minimal 1 item size!'); window.history.back();</script>";
        exit();
    }

    // 1. Simpan Header Pembelian
    $sql_header = "INSERT INTO pembelian_barang (tgl_pembelian, supplier, no_nota) 
                   VALUES ('$tgl_pembelian', '$supplier', $no_nota)";
    
    if (mysqli_query($conn, $sql_header)) {
        $id_pembelian = mysqli_insert_id($conn);

        // 2. Simpan Detail Rincian Pembelian
        $sql_detail = "INSERT INTO detail_pembelian (id_pembelian, id_varian, jumlah_roll, stok_s, stok_m, stok_l, stok_xl, stok_xxl) 
                       VALUES ('$id_pembelian', '$id_varian', '$jumlah_roll', '$stok_s', '$stok_m', '$stok_l', '$stok_xl', '$stok_xxl')";
        mysqli_query($conn, $sql_detail);

        // 3. Otomatis Tambahkan Stok ke Tabel Varian Utama
        $sql_update_stok = "UPDATE varian_warna SET 
                            stok_s = stok_s + $stok_s,
                            stok_m = stok_m + $stok_m,
                            stok_l = stok_l + $stok_l,
                            stok_xl = stok_xl + $stok_xl,
                            stok_xxl = stok_xxl + $stok_xxl
                            WHERE id_varian = '$id_varian'";
        mysqli_query($conn, $sql_update_stok);

        // 4. Catat ke Log Stok Masuk (Agar grafik Dashboard BI ter-update)
        $keterangan = "Pembelian ($jumlah_roll Roll) - Supplier: $supplier";
        $sql_log = "INSERT INTO log_stok_masuk (id_varian, jumlah_masuk, tgl_masuk, keterangan) 
                    VALUES ('$id_varian', '$total_pcs', '$tgl_pembelian', '$keterangan')";
        mysqli_query($conn, $sql_log);

        header("Location: ../index.php?page=pembelian&status=success");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>