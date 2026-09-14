<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'koneksi.php';

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Query memecah ukuran menjadi baris tersendiri (S, M, L, XL, XXL)
$query_sc = mysqli_query($conn, "
    SELECT 
        p.id_produk,
        p.nama_produk,
        v.id_varian,
        v.nama_warna,
        v.warna,
        u.ukuran,
        -- Mengambil stok gudang spesifik per ukuran
        CASE u.ukuran
            WHEN 'S' THEN GREATEST(v.stok_s, 0)
            WHEN 'M' THEN GREATEST(v.stok_m, 0)
            WHEN 'L' THEN GREATEST(v.stok_l, 0)
            WHEN 'XL' THEN GREATEST(v.stok_xl, 0)
            WHEN 'XXL' THEN GREATEST(v.stok_xxl, 0)
        END AS stok_gudang,
        COALESCE(mutasi.total_kirim, 0) AS total_kirim,
        COALESCE(rekap.total_terjual, 0) AS total_terjual
    FROM varian_warna v
    JOIN produk p ON v.id_produk = p.id_produk
    CROSS JOIN (
        SELECT 'S' AS ukuran UNION SELECT 'M' UNION SELECT 'L' UNION SELECT 'XL' UNION SELECT 'XXL'
    ) u
    -- LEFT JOIN Total Kirim per Varian & Ukuran
    LEFT JOIN (
        SELECT id_varian, UPPER(ukuran) AS ukuran, SUM(jumlah) AS total_kirim 
        FROM log_distribusi 
        GROUP BY id_varian, UPPER(ukuran)
    ) mutasi ON v.id_varian = mutasi.id_varian AND u.ukuran = mutasi.ukuran
    -- LEFT JOIN Total Terjual per Varian & Ukuran
    LEFT JOIN (
        SELECT id_varian, UPPER(ukuran) AS ukuran, SUM(jumlah_terjual) AS total_terjual 
        FROM rekap_penjualan 
        GROUP BY id_varian, UPPER(ukuran)
    ) rekap ON v.id_varian = rekap.id_varian AND u.ukuran = rekap.ukuran
    -- Filter hanya menampilkan ukuran yang diproduksi (stok != -1)
    WHERE CASE u.ukuran
            WHEN 'S' THEN v.stok_s
            WHEN 'M' THEN v.stok_m
            WHEN 'L' THEN v.stok_l
            WHEN 'XL' THEN v.stok_xl
            WHEN 'XXL' THEN v.stok_xxl
          END != -1
    ORDER BY p.nama_produk ASC, v.nama_warna ASC, FIELD(u.ukuran, 'S', 'M', 'L', 'XL', 'XXL')
");
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .table-monitoring th {
        font-size: 0.72rem;
        letter-spacing: 0.5px;
    }
    .th-total-highlight {
        background-color: #5b6b7c !important;
        color: #ffffff !important;
        width: 100px;
    }
    .td-total-highlight {
        background-color: #f4f7f9;
        font-weight: 700;
        color: #1e40af;
        font-size: 1rem;
    }
    .th-grandtotal-highlight {
        background-color: #0f172a !important;
        color: #38bdf8 !important;
        width: 120px;
    }
    .td-grandtotal-highlight {
        background-color: #f0f9ff;
        font-weight: 800;
        color: #0369a1;
        font-size: 1.05rem;
    }
    .badge-status {
        font-size: 11px;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 600;
    }
    .badge-size {
        background-color: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 6px;
    }
</style>

<div class="container-fluid py-4 px-4">
    <!-- Banner Header & Filter Pencarian -->
    <div class="card border-0 shadow-sm rounded-4 text-white mb-4" style="background: #1e3a4b;">
        <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center">
                <div class="bg-white text-primary rounded-3 p-3 me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                    <i class="bi bi-box-seam-fill fs-3 text-primary"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-1">Monitoring Stok Real Sales Center</h4>
                    <p class="mb-0 text-white-50 small">Sinkronisasi Keseluruhan Stok (Gudang Utama + Sales Center)</p>
                </div>
            </div>
            
            <!-- Fitur Pencarian Realtime -->
            <div class="search-box-wrapper" style="min-width: 300px;">
                <div class="input-group">
                    <span class="input-group-text bg-white border-0 ps-3 rounded-start-pill text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="searchInputSC" class="form-control border-0 pe-4 py-2 rounded-end-pill" placeholder="Cari Produk, Warna, atau Size..." onkeyup="filterTableSC()">
                </div>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-monitoring" id="tableSalesCenter">
                    <thead>
                        <tr class="text-uppercase fw-bold align-middle">
                            <th class="ps-4 py-3 text-dark" style="width: 25%;">PRODUK & VARIAN WARNA</th>
                            <th class="text-center py-3 th-total-highlight">TOTAL (GUDANG)</th>
                            <th class="text-center py-3 text-primary" style="width: 14%;">TOTAL MUTASI</th>
                            <th class="text-center py-3 text-danger" style="width: 14%;">TOTAL TERJUAL</th>
                            <th class="text-center py-3 text-success" style="width: 14%;">STOK SALES CENTER</th>
                            <th class="text-center py-3 text-dark" style="width: 14%;">
                                TOTAL STOK KESELURUHAN
                            </th>
                            <th class="text-center pe-4 py-3" style="width: 12%;">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query_sc && mysqli_num_rows($query_sc) > 0): 
                            while($row = mysqli_fetch_assoc($query_sc)): 
                                $stok_gudang = $row['stok_gudang'];
                                $kirim = $row['total_kirim'];
                                $jual = $row['total_terjual'];
                                $sisa_sc = $kirim - $jual;
                                
                                // TOTAL STOK SINKRON = TOTAL GUDANG + SISA STOK REAL SC
                                $total_stok_keseluruhan = $stok_gudang + $sisa_sc;

                                if ($total_stok_keseluruhan <= 0) {
                                    $badge = '<span class="badge-status bg-danger-subtle text-danger border border-danger">Stok Habis</span>';
                                } elseif ($total_stok_keseluruhan <= 5) {
                                    $badge = '<span class="badge-status bg-warning-subtle text-warning-emphasis border border-warning">Stok Menipis</span>';
                                } else {
                                    $badge = '<span class="badge-status bg-success-subtle text-success border border-success">Tersedia</span>';
                                }
                        ?>
                        <tr>
                            <!-- PRODUK & VARIAN WARNA -->
                            <td class="ps-4 py-3">
                                <span class="fw-bold text-dark d-block mb-1" style="font-size: 0.95rem;"><?= htmlspecialchars($row['nama_produk']) ?></span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block border" style="width: 10px; height: 10px; background-color: <?= htmlspecialchars($row['warna']) ?>;"></span>
                                    <span class="text-muted small fw-medium me-2"><?= htmlspecialchars($row['nama_warna']) ?></span>
                                    <span class="text-muted small" style="font-size: 11px;">SIZE</span>
                                    <span class="badge-size"><?= $row['ukuran'] ?></span>
                                </div>
                            </td>

                            <!-- TOTAL (GUDANG) -->
                            <td class="text-center td-total-highlight">
                                <?= number_format($stok_gudang) ?>
                            </td>

                            <!-- TOTAL KIRIM (VELOCITY) -->
                            <td class="text-center fw-semibold text-primary" style="font-size: 0.92rem;">
                                <?= number_format($kirim) ?> Pcs
                            </td>

                            <!-- TOTAL TERJUAL (REKAP) -->
                            <td class="text-center fw-semibold text-danger" style="font-size: 0.92rem;">
                                <?= number_format($jual) ?> Pcs
                            </td>

                            <!-- SISA STOK REAL SC -->
                            <td class="text-center fw-semibold text-success" style="font-size: 0.95rem;">
                                <?= number_format($sisa_sc) ?> Pcs
                            </td>

                            <!-- TOTAL STOK (SINKRONISASI: TOTAL GUDANG + SISA STOK REAL SC) -->
                            <td class="text-center fw-semibold td-grandtotal-highlight">
                                <?= number_format($total_stok_keseluruhan) ?> Pcs
                            </td>

                            <!-- STATUS -->
                            <td class="text-center pe-4">
                                <?= $badge ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block opacity-50 mb-2"></i>
                                Belum ada data produk di database.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterTableSC() {
    let input = document.getElementById("searchInputSC");
    let filter = input.value.toUpperCase();
    let rows = document.querySelectorAll("#tableSalesCenter tbody tr");
    
    rows.forEach(row => {
        let text = row.innerText.toUpperCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
}
</script>