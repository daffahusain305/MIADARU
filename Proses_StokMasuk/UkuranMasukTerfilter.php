<?php
include __DIR__ . '/../koneksi.php';

$id_produk = isset($_GET['id_produk']) ? intval($_GET['id_produk']) : 0;

if ($id_produk > 0) {
    // Ambil target_size dari pembelian_roll yang belum resolved
    $query = "
        SELECT DISTINCT pb.target_size 
        FROM pembelian_roll pb
        LEFT JOIN varian_warna v ON pb.id_varian = v.id_varian
        LEFT JOIN produk p1 ON v.id_produk = p1.id_produk
        LEFT JOIN produk p2 ON LOWER(pb.nama_produk_manual) = LOWER(p2.nama_produk)
        WHERE (p1.id_produk = $id_produk OR p2.id_produk = $id_produk) AND pb.is_resolved = 0
    ";
              
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        echo '<option value="">-- Pilih Ukuran --</option>';
        $ukuran_map = [
            's' => 'stok_s',
            'm' => 'stok_m',
            'l' => 'stok_l',
            'xl' => 'stok_xl',
            'xxl' => 'stok_xxl'
        ];

        $added = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $sizes = preg_split('/[\s,dan&]+/', strtolower($row['target_size']));
            foreach ($sizes as $sz) {
                $sz = trim($sz);
                if (array_key_exists($sz, $ukuran_map) && !in_array($sz, $added)) {
                    echo '<option value="' . $ukuran_map[$sz] . '">Ukuran ' . strtoupper($sz) . '</option>';
                    $added[] = $sz;
                }
            }
        }
    } else {
        echo '<option value="">Ukuran Tidak Ditemukan</option>';
    }
}
?>