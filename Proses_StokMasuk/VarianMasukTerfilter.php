<?php
include __DIR__ . '/../koneksi.php';

$id_produk = isset($_GET['id_produk']) ? intval($_GET['id_produk']) : 0;

if ($id_produk > 0) {
    // Query HANYA mengambil varian warna dari produk yang dibeli di pembelian_roll (is_resolved = 0)
    $query = "
        SELECT DISTINCT v.id_varian, v.nama_warna 
        FROM pembelian_roll pb
        JOIN varian_warna v ON pb.id_varian = v.id_varian
        WHERE v.id_produk = $id_produk 
          AND pb.is_resolved = 0

        UNION

        SELECT DISTINCT v.id_varian, v.nama_warna 
        FROM pembelian_roll pb
        JOIN produk p ON LOWER(pb.nama_produk_manual) = LOWER(p.nama_produk)
        JOIN varian_warna v ON (p.id_produk = v.id_produk AND LOWER(pb.warna_manual) = LOWER(v.nama_warna))
        WHERE p.id_produk = $id_produk 
          AND pb.is_resolved = 0

        ORDER BY nama_warna ASC
    ";
              
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        echo '<option value="">-- Pilih Warna --</option>';
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<option value="' . $row['id_varian'] . '">' . htmlspecialchars($row['nama_warna']) . '</option>';
        }
    } else {
        echo '<option value="">Tidak ada warna aktif di Pembelian Roll</option>';
    }
} else {
    echo '<option value="">Pilih Produk Terlebih Dahulu</option>';
}
?>