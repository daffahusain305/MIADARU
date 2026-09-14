<?php
include __DIR__ . '/../koneksi.php';

// Cek apakah parameter tanggal ada, jika tidak ada buat kosong
$tgl = isset($_GET['tanggal']) ? mysqli_real_escape_string($conn, $_GET['tanggal']) : '';

if ($tgl != '') {
    // Query mengambil data log_distribusi berdasarkan tanggal
    $query = mysqli_query($conn, "SELECT * FROM log_distribusi WHERE DATE(tgl_pindah) = '$tgl' ORDER BY tgl_pindah DESC");

    if(mysqli_num_rows($query) > 0) {
        echo '<div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="small text-muted bg-light">
                        <tr>
                            <th class="ps-3">JAM</th>
                            <th>PRODUK</th>
                            <th class="text-center">UKURAN</th>
                            <th class="text-center">QTY</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        while($row = mysqli_fetch_assoc($query)) {
            echo '<tr>
                    <td class="small ps-3">'.date('H:i', strtotime($row['tgl_pindah'])).'</td>
                    <td>
                        <span class="fw-bold text-dark">'.$row['nama_produk'].'</span><br>
                        <small class="text-muted text-uppercase">'.$row['nama_warna'].'</small>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-white text-dark border fw-normal">'.strtoupper($row['ukuran']).'</span>
                    </td>
                    <td class="text-center fw-bold text-primary">'.$row['jumlah'].'</td>
                  </tr>';
        }
        echo '</tbody></table></div>';
    } else {
        echo '<div class="text-center py-5">
                <i class="bi bi-search text-muted opacity-25" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2 small">Tidak ada data perpindahan pada tanggal '.date('d/m/Y', strtotime($tgl)).'</p>
              </div>';
    }
} else {
    echo '<div class="alert alert-warning m-3">Silakan pilih tanggal yang valid.</div>';
}
?>