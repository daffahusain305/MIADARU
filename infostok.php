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


// -------------------------------------------------------------------
// 1. DATA UNTUK BAGIAN ATAS: LAPORAN STOK KRITIS
// -------------------------------------------------------------------
$q_habis = mysqli_query($conn, "SELECT 
    (SELECT COUNT(*) FROM varian_warna WHERE stok_s = 0) +
    (SELECT COUNT(*) FROM varian_warna WHERE stok_m = 0) +
    (SELECT COUNT(*) FROM varian_warna WHERE stok_l = 0) +
    (SELECT COUNT(*) FROM varian_warna WHERE stok_xl = 0) +
    (SELECT COUNT(*) FROM varian_warna WHERE stok_xxl = 0) AS total");
$total_habis = mysqli_fetch_assoc($q_habis)['total'] ?? 0;

$q_restock = mysqli_query($conn, "SELECT 
    (SELECT COUNT(*) FROM varian_warna WHERE stok_s > 0 AND stok_s < 30) +
    (SELECT COUNT(*) FROM varian_warna WHERE stok_m > 0 AND stok_m < 30) +
    (SELECT COUNT(*) FROM varian_warna WHERE stok_l > 0 AND stok_l < 30) +
    (SELECT COUNT(*) FROM varian_warna WHERE stok_xl > 0 AND stok_xl < 30) +
    (SELECT COUNT(*) FROM varian_warna WHERE stok_xxl > 0 AND stok_xxl < 30) AS total");
$total_restock = mysqli_fetch_assoc($q_restock)['total'] ?? 0;

// Query stok kritis (stok < 30 dan stok >= 0)
$query_kritis = mysqli_query($conn, "SELECT v.*, p.nama_produk 
                                      FROM varian_warna v 
                                      JOIN produk p ON v.id_produk = p.id_produk 
                                      WHERE v.stok_s < 30 OR v.stok_m < 30 OR v.stok_l < 30 OR v.stok_xl < 30 OR v.stok_xxl < 30");

// Format Pesan WhatsApp Laporan Kritis
date_default_timezone_set('Asia/Jakarta');
$tanggal = date('d/m/Y H:i');
$pesan_wa_kritis = "*📢 LAPORAN STOK KRITIS MIADARU*\n";

$pesan_wa_kritis .= "------------------------------------------\n\n";

$text_habis = "*❌ STOK HABIS (SEGERA ISI):*\n";
$text_restock = "\n*⚠️ PERLU RESTOCK (STOK < 30):*\n";
$ada_habis = false;
$ada_restock = false;

if ($query_kritis && mysqli_num_rows($query_kritis) > 0) {
    while($row_wa = mysqli_fetch_assoc($query_kritis)) {
        $sizes = ['s', 'm', 'l', 'xl', 'xxl'];
        $item_habis = [];
        $item_restock = [];

        foreach($sizes as $sz) {
            $qty = $row_wa['stok_'.$sz];
            if($qty == -1) continue; 

            if($qty == 0) {
                $item_habis[] = strtoupper($sz);
            } elseif($qty > 0 && $qty < 30) {
                $item_restock[] = strtoupper($sz) . " (Sisa $qty)";
            }
        }

        if(!empty($item_habis)) {
            $text_habis .= "• " . $row_wa['nama_produk'] . " [" . $row_wa['nama_warna'] . "]\n  Size: " . implode(", ", $item_habis) . "\n";
            $ada_habis = true;
        }
        if(!empty($item_restock)) {
            $text_restock .= "• " . $row_wa['nama_produk'] . " [" . $row_wa['nama_warna'] . "]\n  Detail: " . implode(", ", $item_restock) . "\n";
            $ada_restock = true;
        }
    }
}

$pesan_final_kritis = $pesan_wa_kritis . ($ada_habis ? $text_habis : "") . ($ada_restock ? $text_restock : "");
$pesan_final_kritis .= "\n------------------------------------------\n_Mohon diproses untuk pengisian stok._";
$nomor_tujuan = "62895365668157"; 
$wa_link_kritis = "https://api.whatsapp.com/send?phone=$nomor_tujuan&text=" . urlencode($pesan_final_kritis);


// -------------------------------------------------------------------
// 2. DATA UNTUK BAGIAN BAWAH: INFO STOK SELURUH PRODUK
// -------------------------------------------------------------------
$search = "";
if (isset($_GET['cari_stok'])) {
    $search = mysqli_real_escape_string($conn, $_GET['cari_stok']);
}

$query_str = "SELECT p.nama_produk, p.jenis_bahan, v.* FROM produk p 
              JOIN varian_warna v ON p.id_produk = v.id_produk 
              WHERE p.nama_produk LIKE '%$search%' 
              OR v.nama_warna LIKE '%$search%'
              ORDER BY p.id_produk DESC";

$query_infostok = mysqli_query($conn, $query_str);

// Format Pesan Rekap Seluruh Stok untuk WA
$pesan_wa_rekap = "*REKAP STOK MIADARU*\n";
$pesan_wa_rekap .= "Tanggal: " . date('d-m-Y H:i') . "\n";
if ($search != "") {
    $pesan_wa_rekap .= "Pencarian: _" . strtoupper($search) . "_\n";
}
$pesan_wa_rekap .= "----------------------------------\n\n";

if ($query_infostok && mysqli_num_rows($query_infostok) > 0) {
    while($row_wa = mysqli_fetch_assoc($query_infostok)) {
        $detail_stok = [];
        foreach(['s','m','l','xl','xxl'] as $sz) {
            $stk = isset($row_wa['stok_'.$sz]) ? (int)$row_wa['stok_'.$sz] : -1;
            if($stk >= 0) {
                $status = ($stk == 0) ? "KOSONG" : $stk;
                $detail_stok[] = strtoupper($sz) . "($status)";
            }
        }
        if (!empty($detail_stok)) {
            $pesan_wa_rekap .= "*" . strtoupper($row_wa['nama_produk']) . "*\n";
            $pesan_wa_rekap .= "Varian: " . $row_wa['nama_warna'] . "\n";
            $pesan_wa_rekap .= implode(" | ", $detail_stok) . "\n\n";
        }
    }
}
$wa_link_rekap = "https://api.whatsapp.com/send?text=" . urlencode($pesan_wa_rekap);
?>

<!-- Font & Stylesheet -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="./CSS/InfoStok.css">



<div class="container-fluid py-3 px-3">

    <!-- ========================================== -->
    <!-- BAGIAN 1: LAPORAN STOK KRITIS              -->
    <!-- ========================================== -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Stok Habis -->
        <div class="col-md-4">
            <div class="card card-alert-habis border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-exclamation-circle-fill fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-bold" style="font-size: 10px; letter-spacing: 1px;">SINYAL PERINGATAN</small>
                        <h3 class="fw-extrabold text-dark mb-0"><?= $total_habis ?> SKU</h3>
                    </div>
                </div>
                <div class="mt-2 d-flex align-items-center">
                    <span class="rounded-circle bg-danger me-2" style="width: 8px; height: 8px;"></span>
                    <small class="text-danger fw-bold" style="font-size: 11px;">STOK HABIS: Segera isi Produk dari TikTok!</small>
                </div>
            </div>
        </div>

        <!-- Card 2: Perlu Restock -->
        <div class="col-md-4">
            <div class="card card-alert-restock border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-box-seam-fill fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-bold" style="font-size: 10px; letter-spacing: 1px;">PRODUK PERLU DIISI</small>
                        <h3 class="fw-extrabold text-dark mb-0"><?= $total_restock ?> SKU</h3>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted" style="font-size: 11px;">Beberapa Produk Akan Segera Habis.</small>
                </div>
            </div>
        </div>

        <!-- Card 3: Kirim Laporan WA -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 text-white d-flex flex-column justify-content-between" style="background-color: #111827;">
                <div class="d-flex align-items-center mb-2">
                    <div class="text-success me-2"><i class="bi bi-whatsapp fs-4"></i></div>
                    <div>
                        <h6 class="fw-bold mb-0 text-white">Distribusi Formal</h6>
                        <small class="text-white-50" style="font-size: 10px;">Formalize Organizational Knowledge</small>
                    </div>
                </div>
                <a href="<?= $wa_link_kritis ?>" target="_blank" class="btn btn-success w-100 rounded-pill py-2 fw-bold text-white shadow-sm mt-2">
                    <i class="bi bi-send-fill me-2"></i> KIRIM LAPORAN WA
                </a>
            </div>
        </div>
    </div>

    <!-- Tabel 1: Status Pengetahuan Produk (Stok Kritis) -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-5">
        <h5 class="fw-bold text-dark mb-3">Status Produk</h5>
        <div class="table-scrollable">
            <table class="table align-middle border-0 mb-0">
                <thead class="bg-light text-muted" style="font-size: 11px;">
                    <tr>
                        <th class="border-0 ps-3">IDENTITAS PRODUK</th>
                        <th class="border-0 text-center">VARIAN/SIZE</th>
                        <th class="border-0 text-center">QTY</th>
                        <th class="border-0 text-end pe-3">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($query_kritis && mysqli_num_rows($query_kritis) > 0):
                        mysqli_data_seek($query_kritis, 0); 
                        $ada_kritis_item = false;
                        while($row = mysqli_fetch_assoc($query_kritis)): 
                            foreach(['s', 'm', 'l', 'xl', 'xxl'] as $sz):
                                $qty = $row['stok_' . $sz];
                                if($qty >= 0 && $qty < 30): 
                                    $ada_kritis_item = true;
                    ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td class="ps-3 py-3">
                            <a href="index.php?page=katalog&highlight_varian=<?= $row['id_varian'] ?>" class="text-decoration-none text-dark">
                                <h6 class="mb-0 fw-bold" style="font-size: 14px;"><?= htmlspecialchars($row['nama_produk']) ?></h6>
                                <small class="text-muted text-uppercase" style="font-size: 10px;">PRODUK MIADARU</small>
                            </a>
                        </td>
                        <td class="text-center">
                            <span class="text-muted me-2 small"><?= htmlspecialchars($row['nama_warna']) ?></span>
                            <span class="badge bg-light text-dark border fw-bold"><?= strtoupper($sz) ?></span>
                        </td>
                        <td class="text-center">
                            <h5 class="fw-bold mb-0 <?= $qty == 0 ? 'text-danger' : 'text-warning' ?>"><?= $qty ?></h5>
                        </td>
                        <td class="text-end pe-3">
                            <?php if($qty == 0): ?>
                                <span class="badge bg-danger rounded-pill px-3 py-2 animate-blink" style="font-size: 11px;">STOK HABIS</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fw-bold" style="font-size: 11px; background-color: #fef08a !important; color: #854d0e !important;">RESTOCK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                                endif;
                            endforeach;
                        endwhile; 

                        if(!$ada_kritis_item):
                    ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted small">Semua stok varian dalam kondisi aman (≥ 30).</td>
                    </tr>
                    <?php
                        endif;
                    else: 
                    ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted small">Tidak ada data stok kritis saat ini.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- ========================================== -->
    <!-- BAGIAN 2: INFO STOK SELURUH PRODUK         -->
    <!-- ========================================== -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-0">📦 Info Stok Seluruh Produk Di Gudang</h4>
            <p class="text-muted small mb-0">Total: <?= mysqli_num_rows($query_infostok) ?> Varian ditemukan</p>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <a href="<?= $wa_link_rekap ?>" target="_blank" class="btn btn-success rounded-pill px-3 shadow-sm font-weight-bold" style="background-color: #045f41; border: none;">
                <i class="bi bi-whatsapp me-2"></i> Kirim Rekap
            </a>

            <form method="GET" action="" class="d-flex gap-2 mb-0">
                <input type="hidden" name="page" value="infostok">
                <div class="position-relative">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" name="cari_stok" 
                           class="form-control ps-5 rounded-pill border-0 shadow-sm" 
                           placeholder="Cari Andaru Anara..." 
                           value="<?= htmlspecialchars($search) ?>"
                           style="min-width: 220px; height: 38px; font-size: 13px;">
                </div>
                <button type="submit" class="btn btn-dark rounded-pill px-4" style="background-color: #111827; border: none; font-size: 13px;">Cari</button>
                <?php if($search != ""): ?>
                    <a href="index.php?page=infostok" class="btn btn-light rounded-pill border shadow-sm" style="font-size: 13px;">Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Tabel 2: Info Seluruh Stok produk -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-scrollable">
            <table class="table table-hover align-middle mb-0">
                <thead class="text-center" style="background-color: #1f2937; color: #ffffff;">
                    <tr>
                        <th class="ps-4 py-3 text-start bg-dark text-white" style="width: 30%;">Produk dan Varian</th>
                        <th class="bg-dark text-white" style="width: 10%;">S</th>
                        <th class="bg-dark text-white" style="width: 10%;">M</th>
                        <th class="bg-dark text-white" style="width: 10%;">L</th>
                        <th class="bg-dark text-white" style="width: 10%;">XL</th>
                        <th class="bg-dark text-white" style="width: 10%;">XXL</th>
                        <th class="text-white" style="width: 20%; background-color: #64748b;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($query_infostok && mysqli_num_rows($query_infostok) > 0): ?>
                        <?php 
                        mysqli_data_seek($query_infostok, 0);
                        while($row = mysqli_fetch_assoc($query_infostok)): 
                            $total_per_warna = 0;
                        ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="fw-bold text-dark" style="font-size: 14px;"><?= htmlspecialchars($row['nama_produk']) ?></div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="color-dot" style="background-color: <?= htmlspecialchars($row['warna']) ?>;"></span>
                                    <small class="text-muted text-uppercase" style="font-size: 10px; font-weight: 600;">
                                        <?= htmlspecialchars($row['nama_warna']) ?> (<?= htmlspecialchars($row['jenis_bahan']) ?>)
                                    </small>
                                </div>
                            </td>
                            
                            <?php foreach(['s','m','l','xl','xxl'] as $sz): 
                                $stok = isset($row['stok_'.$sz]) ? (int)$row['stok_'.$sz] : -1;
                            ?>
                            <td class="text-center">
                                <?php if($stok < 0): ?>
                                    <span class="text-muted opacity-25" title="Ukuran tidak diproduksi">—</span>
                                <?php else: 
                                    $total_per_warna += $stok;
                                    $color_class = ($stok == 0) ? 'text-danger fw-bold' : (($stok < 30) ? 'text-warning fw-bold' : 'text-dark fw-semibold');
                                ?>
                                    <span class="<?= $color_class ?>"><?= $stok ?></span>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>

                            <td class="text-center bg-light fw-bold text-primary" style="font-size: 15px;">
                                <?= $total_per_warna ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                                Produk "<strong><?= htmlspecialchars($search) ?></strong>" tidak ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>