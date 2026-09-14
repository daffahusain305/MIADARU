<?php
session_start();
include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_varian = $_POST['id_varian'];
    $ukuran = $_POST['ukuran'];
    $jumlah_terjual = $_POST['jumlah_terjual'];
    $tgl_rekap = $_POST['tgl_rekap'];

    if (is_array($id_varian)) {
        for ($i = 0; $i < count($id_varian); $i++) {
            $v_id = mysqli_real_escape_string($conn, $id_varian[$i]);
            $v_uk = mysqli_real_escape_string($conn, $ukuran[$i]);
            $v_jml = intval($jumlah_terjual[$i]);
            $v_tgl = mysqli_real_escape_string($conn, $tgl_rekap[$i]);

            if (!empty($v_id) && !empty($v_uk) && $v_jml > 0) {
                mysqli_query($conn, "INSERT INTO rekap_penjualan (id_varian, ukuran, jumlah_terjual, tgl_rekap, created_at) 
                                     VALUES ('$v_id', '$v_uk', '$v_jml', '$v_tgl', NOW())");
            }
        }
    }

    header("Location: ../index.php?page=rekap");
    exit();
}