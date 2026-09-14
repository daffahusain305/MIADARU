<?php
include __DIR__ . '/../koneksi.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Query untuk mengambil TOTAL AKUMULASI produk yang sudah dipindahkan dari awal
$query_str = "SELECT p.nama_produk, v.nama_warna, l.ukuran, SUM(l.jumlah) as total_terpindah
              FROM log_distribusi l
              JOIN varian_warna v ON l.id_varian = v.id_varian
              JOIN produk p ON v.id_produk = p.id_produk";

// Fitur Pencarian Produk
if (!empty($search)) {
    $query_str .= " WHERE p.nama_produk LIKE '%$search%'";
}

// Dikelompokkan per produk, warna, dan ukuran agar mutasi terpantau jelas
$query_str .= " GROUP BY p.nama_produk, v.nama_warna, l.ukuran
                ORDER BY total_terpindah DESC"; // Diurutkan dari yang paling banyak dipindahkan (Fast Moving)

$result = mysqli_query($conn, $query_str);

if (mysqli_num_rows($result) > 0) {
    echo '<table class="table table-hover align-middle border-0">';
    echo '<thead class="table-light text-muted small">
            <tr>
                <th>NAMA PRODUK / VARIAN</th>
                <th class="text-center">UKURAN</th>
                <th class="text-center">TOTAL TERPINDAH (AKUMULASI)</th>
                <th class="text-end">VELOCITY STATUS</th>
            </tr>
          </thead>
          <tbody>';
    
    // Ambil angka tertinggi untuk indikator acuan velocity status (opsional sebagai pemanis visual)
    $max_total = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Logika sederhana penentu status perputaran barang
        // Jika produk dipindahkan > 50 pcs dianggap sangat cepat (bisa disesuaikan dengan skala bisnis Anda)
        if ($row['total_terpindah'] >= 50) {
            $status = '<span class="badge bg-danger-subtle text-danger"><i class="bi bi-fire me-1"></i> Fast Moving</span>';
        } elseif ($row['total_terpindah'] >= 15) {
            $status = '<span class="badge bg-primary-subtle text-primary"><i class="bi bi-arrow-right-short"></i> Steady</span>';
        } else {
            $status = '<span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-snow"></i> Slow Moving</span>';
        }

        echo "<tr>
                <td>
                    <span class='fw-bold text-dark'>{$row['nama_produk']}</span><br>
                    <small class='text-muted text-uppercase' style='font-size: 11px;'>{$row['nama_warna']}</small>
                </td>
                <td class='text-center'><span class='badge bg-light text-dark border'>".strtoupper($row['ukuran'])."</span></td>
                <td class='text-center fw-bold text-dark fs-6'>{$row['total_terpindah']} PCS</td>
                <td class='text-end'>{$status}</td>
              </tr>";
    }
    echo '</tbody></table>';
} else {
    echo '<div class="text-center py-5 text-muted">
            <i class="bi bi-box-seam fs-2 opacity-50"></i>
            <p class="small mt-2">Produk tidak ditemukan atau belum pernah ada mutasi stok.</p>
          </div>';
}
?>