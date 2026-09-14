<?php
include __DIR__ . '/../koneksi.php';

if (isset($_GET['id']) && isset($_GET['size']) && isset($_GET['val'])) {
    $id    = mysqli_real_escape_string($conn, $_GET['id']);
    $size  = "stok_" . mysqli_real_escape_string($conn, $_GET['size']);
    $val   = (int)$_GET['val'];

    $query = "UPDATE varian_warna SET $size = '$val' WHERE id_varian = '$id'";
    mysqli_query($conn, $query);
}
?>