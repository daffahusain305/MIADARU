<?php
include __DIR__ . '/../koneksi.php';

if (isset($_GET['id_varian'])) {
    $id = $_GET['id_varian'];
    $query = mysqli_query($conn, "SELECT stok_s, stok_m, stok_l, stok_xl, stok_xxl FROM varian_warna WHERE id_varian = '$id'");
    $data = mysqli_fetch_assoc($query);

    $available = [];
    foreach ($data as $key => $val) {
        // Kita hanya ambil yang stoknya di atas 0 dan bukan -1
        if ($val > 0) {
            $size = str_replace('stok_', '', $key);
            $available[] = ['size' => strtoupper($size), 'stok' => $val, 'value' => $size];
        }
    }
    echo json_encode($available);
}
?>