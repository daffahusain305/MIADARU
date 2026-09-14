<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'koneksi.php';

// --- 1. LOGIKA PENGAMBILAN DATA WIDGET ---
// Total Jenis Produk
$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM produk"))['total'] ?? 0;

// Stok Menipis (Menggunakan threshold < 30 agar konsisten dengan infostok, mengabaikan -1)
$q_kritis = mysqli_query($conn, "SELECT COUNT(*) as total FROM varian_warna 
            WHERE (stok_s >= 0 AND stok_s < 30) OR (stok_m >= 0 AND stok_m < 30) 
            OR (stok_l >= 0 AND stok_l < 30) OR (stok_xl >= 0 AND stok_xl < 30) 
            OR (stok_xxl >= 0 AND stok_xxl < 30)");
$total_kritis = mysqli_fetch_assoc($q_kritis)['total'] ?? 0;

// Pending Retur
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM log_retur WHERE status = 'Pending'"))['total'] ?? 0;

// Masuk Hari Ini
$total_masuk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah_masuk) as total FROM log_stok_masuk WHERE DATE(tgl_masuk) = CURDATE()"))['total'] ?? 0;


// --- 2. LOGIKA GRAFIK TREN (OPTIMASI 2 QUERY DENGAN GROUP BY) ---
$label_tanggal = [];
$data_masuk_map = [];
$data_retur_map = [];

// Inisialisasi peta tanggal 30 hari terakhir
for ($i = 29; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $label_tanggal[] = date('d M', strtotime($tgl));
    $data_masuk_map[$tgl] = 0;
    $data_retur_map[$tgl] = 0;
}

$tgl_mulai = date('Y-m-d', strtotime("-29 days"));

// 1 Query untuk seluruh data masuk 30 hari
$q_masuk_all = mysqli_query($conn, "SELECT DATE(tgl_masuk) as tgl, SUM(jumlah_masuk) as total 
                                    FROM log_stok_masuk 
                                    WHERE DATE(tgl_masuk) >= '$tgl_mulai' 
                                    GROUP BY DATE(tgl_masuk)");
while ($r = mysqli_fetch_assoc($q_masuk_all)) {
    if (isset($data_masuk_map[$r['tgl']])) {
        $data_masuk_map[$r['tgl']] = (int)$r['total'];
    }
}

// 1 Query untuk seluruh data retur 30 hari
$q_retur_all = mysqli_query($conn, "SELECT DATE(tgl_retur) as tgl, SUM(jumlah_retur) as total 
                                   FROM log_retur 
                                   WHERE DATE(tgl_retur) >= '$tgl_mulai' 
                                   GROUP BY DATE(tgl_retur)");
while ($r = mysqli_fetch_assoc($q_retur_all)) {
    if (isset($data_retur_map[$r['tgl']])) {
        $data_retur_map[$r['tgl']] = (int)$r['total'];
    }
}

$json_labels = json_encode($label_tanggal);
$json_masuk = json_encode(array_values($data_masuk_map));
$json_retur = json_encode(array_values($data_retur_map));
?>

<style>
    .card-dashboard { border-radius: 20px; transition: all 0.3s ease; border: none !important; }
    .card-dashboard:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }
    .icon-box { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 14px; margin-bottom: 15px; }
    .stat-value { font-size: 1.8rem; font-weight: 700; color: #2d3436; }
    .stat-label { font-size: 0.85rem; color: #636e72; font-weight: 500; }
    .list-kritis { max-height: 380px; overflow-y: auto; }
    .list-kritis::-webkit-scrollbar { width: 4px; }
    .list-kritis::-webkit-scrollbar-thumb { background: #eee; border-radius: 10px; }
</style>

<div class="container-fluid py-4">
    <!-- WIDGET RINGKASAN -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-dashboard shadow-sm p-4 bg-white">
                <div class="icon-box bg-primary-subtle text-primary">
                    <i class="bi bi-box-seam fs-4"></i>
                </div>
                <div class="stat-label">Total Jenis Produk</div>
                <div class="stat-value"><?= $total_produk ?></div>
                <div class="mt-2"><span class="badge bg-success-subtle text-success" style="font-size: 10px;">AKTIF</span></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-dashboard shadow-sm p-4 bg-white">
                <div class="icon-box bg-danger-subtle text-danger">
                    <i class="bi bi-graph-down-arrow fs-4"></i>
                </div>
                <div class="stat-label">Stok Menipis / Kritis</div>
                <div class="stat-value"><?= $total_kritis ?></div>
                <div class="mt-2"><span class="text-danger small fw-bold" style="font-size: 11px;">Butuh Restok Segera</span></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-dashboard shadow-sm p-4 bg-white">
                <div class="icon-box bg-warning-subtle text-warning">
                    <i class="bi bi-clock-history fs-4"></i>
                </div>
                <div class="stat-label">Pending Retur</div>
                <div class="stat-value"><?= $total_pending ?></div>
                <div class="mt-2"><span class="text-muted small" style="font-size: 11px;">Menunggu Supplier</span></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-dashboard shadow-sm p-4 bg-white">
                <div class="icon-box bg-success-subtle text-success">
                    <i class="bi bi-lightning-charge fs-4"></i>
                </div>
                <div class="stat-label">Masuk Hari Ini</div>
                <div class="stat-value"><?= $total_masuk ?></div>
                <div class="mt-2"><span class="text-muted small" style="font-size: 11px;">Update: <?= date('d M Y') ?></span></div>
            </div>
        </div>
        
    </div>

    <!-- TAMPILAN GRAFIK & LIST ALERT -->
    <div class="row g-4">
        <!-- List Alert Stok Kritis -->
        <div class="col-md-4">
            <div class="card card-dashboard shadow-sm p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0">Alert Stok Habis & Kritis</h6>
                    <i class="bi bi-bell text-muted"></i>
                </div>
                <div class="list-kritis">
                    <?php
                    $sql = "SELECT p.nama_produk, v.nama_warna, v.stok_s, v.stok_m, v.stok_l, v.stok_xl, v.stok_xxl 
                            FROM varian_warna v JOIN produk p ON v.id_produk = p.id_produk 
                            WHERE (v.stok_s >= 0 AND v.stok_s < 30) OR (v.stok_m >= 0 AND v.stok_m < 30) 
                            OR (v.stok_l >= 0 AND v.stok_l < 30) OR (v.stok_xl >= 0 AND v.stok_xl < 30) 
                            OR (v.stok_xxl >= 0 AND v.stok_xxl < 30)";
                    $res = mysqli_query($conn, $sql);
                    
                    $ada_item = false;
                    if(mysqli_num_rows($res) > 0):
                        while($k = mysqli_fetch_assoc($res)):
                            $sizes = ['S' => $k['stok_s'], 'M' => $k['stok_m'], 'L' => $k['stok_l'], 'XL' => $k['stok_xl'], 'XXL' => $k['stok_xxl']];
                            foreach($sizes as $sz => $val):
                                if($val >= 0 && $val < 30): 
                                    $ada_item = true;
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-light">
                            <div>
                                <div class="fw-bold small text-dark"><?= htmlspecialchars($k['nama_produk']) ?></div>
                                <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($k['nama_warna']) ?> — Size <?= $sz ?></div>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= $val == 0 ? 'bg-danger' : 'bg-warning-subtle text-warning' ?> rounded-pill" style="font-size: 10px;">
                                    <?= $val == 0 ? 'HABIS' : $val . ' Pcs' ?>
                                </span>
                            </div>
                        </div>
                    <?php 
                                endif;
                            endforeach;
                        endwhile;
                    endif;

                    if(!$ada_item): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check2-circle fs-1 text-success"></i>
                            <p class="text-muted small mt-2">Semua stok aman!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-dashboard shadow-sm p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0">Tren Aktivitas Barang (30 Hari)</h6>
                    <div class="small text-muted">Bulan: <?= date('F Y') ?></div>
                </div>
                <div style="height: 320px;">
                    <canvas id="chartTrenStok"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('chartTrenStok').getContext('2d');

const gradientIn = ctx.createLinearGradient(0, 0, 0, 300);
gradientIn.addColorStop(0, 'rgba(13, 110, 253, 0.2)');
gradientIn.addColorStop(1, 'rgba(13, 110, 253, 0)');

const gradientOut = ctx.createLinearGradient(0, 0, 0, 300);
gradientOut.addColorStop(0, 'rgba(220, 53, 69, 0.2)');
gradientOut.addColorStop(1, 'rgba(220, 53, 69, 0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= $json_labels ?>,
        datasets: [
            {
                label: 'Barang Masuk',
                data: <?= $json_masuk ?>,
                borderColor: '#0d6efd',
                backgroundColor: gradientIn,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                borderWidth: 3
            },
            {
                label: 'Barang Retur',
                data: <?= $json_retur ?>,
                borderColor: '#dc3545',
                backgroundColor: gradientOut,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                borderWidth: 3
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                display: true,
                position: 'top',
                align: 'end',
                labels: { boxWidth: 10, usePointStyle: true, font: { size: 11 } }
            },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            y: { 
                beginAtZero: true, 
                grid: { color: '#f8f9fa' },
                ticks: { font: { size: 10 } }
            },
            x: { 
                grid: { display: false },
                ticks: { 
                    font: { size: 10 },
                    maxRotation: 0, 
                    autoSkip: true, 
                    maxTicksLimit: 7 
                }
            }
        }
    }
});
</script>