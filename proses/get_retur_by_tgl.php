<?php
include __DIR__ . '/../koneksi.php';
$tgl = $_GET['tanggal'];

$query = mysqli_query($conn, "SELECT * FROM log_retur WHERE DATE(tgl_retur) = '$tgl' ORDER BY tgl_retur DESC");

if(mysqli_num_rows($query) > 0) {
    echo '<table class="table table-sm table-borderless align-middle">
            <thead class="small text-muted border-bottom">
                <tr><th>JAM</th><th>PRODUK</th><th class="text-center">QTY</th><th>ALASAN</th></tr>
            </thead><tbody>';
    while($row = mysqli_fetch_assoc($query)) {
        echo '<tr>
                <td class="small">'.date('H:i', strtotime($row['tgl_retur'])).'</td>
                <td><strong>'.$row['nama_produk'].'</strong><br><small>'.$row['nama_warna'].'</small></td>
                <td class="text-center text-danger fw-bold">-'.$row['jumlah_retur'].'</td>
                <td class="small">'.$row['keterangan'].'</td>
              </tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p class="text-center py-5 text-muted">Tidak ada data retur pada tanggal ini.</p>';
}
?>