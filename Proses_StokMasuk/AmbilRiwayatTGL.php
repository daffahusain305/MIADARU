<?php
// 1. Hubungkan ke database (pastikan path '../koneksi.php' sudah benar)
include __DIR__ . '/../koneksi.php';

// 2. Tangkap parameter 'tanggal' dari fetch JavaScript
// Menggunakan 'tanggal' karena di JS kamu menulis: ?tanggal=${tglValue}
$tgl_dipilih = isset($_GET['tanggal']) ? mysqli_real_escape_string($conn, $_GET['tanggal']) : '';

if ($tgl_dipilih != '') {
    // 3. Query SQL (Disesuaikan dengan kolom: tgl_masuk dan jumlah_masuk)
    $sql = "SELECT l.*, p.nama_produk, v.nama_warna 
            FROM log_stok_masuk l
            JOIN varian_warna v ON l.id_varian = v.id_varian
            JOIN produk p ON v.id_produk = p.id_produk
            WHERE DATE(l.tgl_masuk) = '$tgl_dipilih' 
            ORDER BY l.tgl_masuk DESC";

    $query = mysqli_query($conn, $sql);

    // 4. Cek apakah ada data
    if (mysqli_num_rows($query) > 0) {
        echo '<div class="table-responsive">
                <table class="table table-sm table-borderless align-middle">';
        echo '<thead class="small text-muted border-bottom">
                <tr>
                    <th>JAM</th>
                    <th>PRODUK</th>
                    <th class="text-end">QTY</th>
                </tr>
              </thead>
              <tbody>';
        
        while ($row = mysqli_fetch_assoc($query)) {
            // Format Jam dari tgl_masuk
            $jam = date('H:i', strtotime($row['tgl_masuk']));
            
            echo '<tr>';
            echo '<td class="small text-muted">' . $jam . '</td>';
            echo '<td>
                    <strong class="d-block">' . $row['nama_produk'] . '</strong>
                    <small class="text-muted">' . $row['nama_warna'] . ' (' . strtoupper($row['ukuran']) . ')</small>
                  </td>';
            // Gunakan kolom jumlah_masuk sesuai gambar database kamu
            echo '<td class="text-end fw-bold text-primary">+' . number_format($row['jumlah_masuk']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table></div>';
    } else {
        // Jika data kosong pada tanggal tersebut
        echo '<div class="text-center py-5">
                <i class="bi bi-info-circle text-muted" style="font-size: 2rem; opacity: 0.5;"></i>
                <p class="text-muted mt-2 small">Tidak ada riwayat masuk pada tanggal ' . date('d/m/Y', strtotime($tgl_dipilih)) . '</p>
              </div>';
    }
} else {
    echo '<div class="alert alert-danger">Parameter tanggal tidak ditemukan.</div>';
}
?>