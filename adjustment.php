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

// Menghitung statistik ringkas
$query_stats = mysqli_query($conn, "SELECT 
    COUNT(id_varian) as total_varian,
    SUM(CASE WHEN (stok_s >= 0 OR stok_m >= 0 OR stok_l >= 0 OR stok_xl >= 0 OR stok_xxl >= 0) THEN 1 ELSE 0 END) as varian_aktif
    FROM varian_warna");
$stat_data = mysqli_fetch_assoc($query_stats);
?>

<!-- Font & Icon Bootstrap -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<link rel="stylesheet" href="./CSS/penyesuaian.css?v=2">

<div class="container-fluid py-4 px-4">
    
    <!-- Header Banner -->
    <div class="page-header-card mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-bold rounded-pill px-3 py-1" style="font-size: 11px;">Gudang & Stok</span>
            </div>
            <h3 class="fw-bold text-white mb-1">Penyesuaian Stok </h3>
            <p class="text-white-50 small mb-0">Lakukan pembaruan jumlah stok fisik barang secara akurat.</p>
        </div>

        <!-- Quick Summary Badges -->
        <div class="d-flex gap-3">
            <div class="stat-badge">
                <i class="bi bi-boxes fs-4 text-info"></i>
                <div>
                    <div class="text-white-50" style="font-size: 11px; font-weight: 600;">TOTAL VARIAN</div>
                    <div class="fw-bold fs-6 text-white"><?= number_format($stat_data['total_varian'] ?? 0) ?></div>
                </div>
            </div>
            <div class="stat-badge">
                <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                <div>
                    <div class="text-white-50" style="font-size: 11px; font-weight: 600;">VARIAN AKTIF</div>
                    <div class="fw-bold fs-6 text-white"><?= number_format($stat_data['varian_aktif'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions & Filter Bar -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
            <h6 class="fw-bold mb-0 text-slate-800">Daftar Stok Varian</h6>
            <span class="text-muted small">Ubah nilai angka pada kolom ukuran untuk memperbarui stok.</span>
        </div>
        
        <div class="search-box-wrapper" style="min-width: 280px;">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="searchInput" class="form-control" placeholder="Cari Produk atau Warna..." onkeyup="filterTable()">
        </div>
    </div>

    <!-- Table Container -->
    <div class="custom-table-container overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="adjustmentTable">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 32%;">Produk & Varian</th>
                        <th class="text-center">Ukuran S</th>
                        <th class="text-center">Ukuran M</th>
                        <th class="text-center">Ukuran L</th>
                        <th class="text-center">Ukuran XL</th>
                        <th class="text-center">Ukuran XXL</th>
                        <th class="text-center" style="width: 10%;">Aksi Hapus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $query = mysqli_query($conn, "SELECT p.nama_produk, v.* FROM varian_warna v JOIN produk p ON v.id_produk = p.id_produk ORDER BY p.nama_produk ASC, v.nama_warna ASC");
                    if (mysqli_num_rows($query) > 0):
                        while($row = mysqli_fetch_assoc($query)):
                    ?>
                    <tr>
                        <td class="ps-4 py-3">
                            <span class="product-title"><?= htmlspecialchars($row['nama_produk']) ?></span>
                            <div class="product-meta">
                                <span class="color-dot" style="background-color: <?= htmlspecialchars($row['warna']) ?>;"></span>
                                <span><?= htmlspecialchars($row['nama_warna']) ?></span>
                                <span class="text-muted">• ID: <?= $row['id_varian'] ?></span>
                            </div>
                        </td>
                        
                        <?php foreach(['s','m','l','xl','xxl'] as $sz): 
                            $stok = $row['stok_'.$sz];
                        ?>
                        <td class="text-center">
                            <?php if($stok == -1): ?>
                                <span class="stok-disabled" title="Ukuran tidak diproduksi">—</span>
                            <?php else: 
                                $is_zero = ($stok == 0) ? 'zero-val' : '';
                            ?>
                                <div class="stok-input-wrapper">
                                    <input type="number" 
                                           class="stok-input <?= $is_zero ?>" 
                                           value="<?= $stok ?>" 
                                           min="0"
                                           onchange="updateStokLangsung(<?= $row['id_varian'] ?>, '<?= $sz ?>', this.value, <?= $stok ?>)">
                                    <!-- Tombol Silang Hapus Ukuran Ini -->
                                    <button type="button" class="btn-hapus-size" title="Hapus Ukuran <?= strtoupper($sz) ?>" onclick="hapusUkuran(<?= $row['id_varian'] ?>, '<?= $sz ?>')">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>

                        <!-- KELOMPOK TOMBOL HAPUS WARNA & PRODUK -->
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border rounded-pill px-3 fw-bold" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-trash text-danger me-1"></i> Hapus
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                    <li>
                                        <button class="dropdown-item text-danger small py-2 fw-semibold" onclick="hapusVarian(<?= $row['id_varian'] ?>, '<?= htmlspecialchars($row['nama_produk']) ?> - <?= htmlspecialchars($row['nama_warna']) ?>')">
                                            <i class="bi bi-palette me-2"></i> Hapus Warna Ini
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item text-danger small py-2 fw-bold" onclick="hapusProduk(<?= $row['id_produk'] ?>, '<?= htmlspecialchars($row['nama_produk']) ?>')">
                                            <i class="bi bi-box-seam me-2"></i> Hapus Seluruh Produk
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block text-slate-300 mb-2"></i>
                            Belum ada data varian produk yang tersedia.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// 1. HAPUS SPESIFIK UKURAN
function hapusUkuran(idVarian, size) {
    if (confirm(`Apakah kamu yakin ingin menghapus/menonaktifkan Ukuran ${size.toUpperCase()} pada varian ini?`)) {
        fetch('Proses_Penyesuaian/hapus_fitur.php?aksi=hapus_ukuran', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id_varian=${idVarian}&size=${size}`
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.status === 'success') location.reload();
        })
        .catch(err => alert("Gagal memproses request."));
    }
}

// 2. HAPUS HANYA WARNA/VARIAN INI
function hapusVarian(idVarian, namaLengkap) {
    if (confirm(`Yakin ingin menghapus varian warna '${namaLengkap}'?`)) {
        fetch('Proses_Penyesuaian/hapus_fitur.php?aksi=hapus_varian', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id_varian=${idVarian}`
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.status === 'success') location.reload();
        })
        .catch(err => alert("Gagal memproses request."));
    }
}

// 3. HAPUS KESELURUHAN PRODUK
function hapusProduk(idProduk, namaProduk) {
    if (confirm(`PERINGATAN!\nApakah Anda yakin ingin menghapus SELURUH PRODUK '${namaProduk}' beserta SEMUA VARIAN WARNA & UKURANNYA?`)) {
        fetch('Proses_Penyesuaian/hapus_fitur.php?aksi=hapus_produk', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id_produk=${idProduk}`
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.status === 'success') location.reload();
        })
        .catch(err => alert("Gagal memproses request."));
    }
}

function updateStokLangsung(idVarian, size, nilaiBaru, nilaiLama) {
    if (nilaiBaru === nilaiLama || nilaiBaru === '') return;

    let alasan = prompt("Masukkan alasan koreksi stok:", "Penyesuaian Stok Fisik / Barang Rusak");
    if (alasan != null && alasan.trim() !== "") {
        fetch('proses/proses_adjustment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id_varian=${idVarian}&size=${size}&baru=${nilaiBaru}&lama=${nilaiLama}&ket=${encodeURIComponent(alasan)}`
        })
        .then(res => res.text())
        .then(data => {
            console.log("Stok Berhasil Diperbarui");
        })
        .catch(err => {
            alert("Gagal memperbarui stok. Silakan coba lagi.");
            location.reload();
        });
    } else {
        location.reload();
    }
}

function filterTable() {
    let input = document.getElementById("searchInput");
    let filter = input.value.toUpperCase();
    let rows = document.querySelectorAll("#adjustmentTable tbody tr");
    
    rows.forEach(row => {
        let text = row.innerText.toUpperCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
}
</script>