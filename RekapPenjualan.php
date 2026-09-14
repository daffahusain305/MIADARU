<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'koneksi.php';

// Ambil data varian untuk opsi dropdown
$options_varian = "";
$q_varian = mysqli_query($conn, "SELECT v.id_varian, p.nama_produk, v.nama_warna FROM varian_warna v JOIN produk p ON v.id_produk = p.id_produk ORDER BY p.nama_produk ASC");
while($v = mysqli_fetch_assoc($q_varian)) {
    $options_varian .= "<option value='".$v['id_varian']."'>".$v['nama_produk']." - ".$v['nama_warna']."</option>";
}

// Tanggal hari ini
$hari_ini = date('Y-m-d');

// 1. Query Ringkasan Penjualan Hari Ini
$q_summary = mysqli_query($conn, "
    SELECT 
        COALESCE(SUM(jumlah_terjual), 0) AS total_pcs_hari_ini,
        COUNT(id_rekap) AS total_transaksi_hari_ini
    FROM rekap_penjualan 
    WHERE tgl_rekap = '$hari_ini'
");
$summary = mysqli_fetch_assoc($q_summary);

// 2. Query Riwayat Rekap Penjualan Hari Ini
$q_rekap_hari_ini = mysqli_query($conn, "
    SELECT r.*, p.nama_produk, v.nama_warna 
    FROM rekap_penjualan r
    JOIN varian_warna v ON r.id_varian = v.id_varian
    JOIN produk p ON v.id_produk = p.id_produk
    WHERE r.tgl_rekap = '$hari_ini'
    ORDER BY r.created_at DESC
");

// 3. Query Logika Filter Tanggal Tunggal (Riwayat Sebelumnya)
$filter_tgl = isset($_GET['tgl_cari']) ? mysqli_real_escape_string($conn, $_GET['tgl_cari']) : '';

$where_clause = "WHERE 1=1";
if (!empty($filter_tgl)) {
    $where_clause .= " AND r.tgl_rekap = '$filter_tgl'";
}

$q_riwayat_all = mysqli_query($conn, "
    SELECT r.*, p.nama_produk, v.nama_warna 
    FROM rekap_penjualan r
    JOIN varian_warna v ON r.id_varian = v.id_varian
    JOIN produk p ON v.id_produk = p.id_produk
    $where_clause
    ORDER BY r.tgl_rekap DESC, r.created_at DESC
");
?>

<div class="container-fluid py-4">
    <!-- Header Dashboard & Tombol Card "Riwayat Sebelumnya" -->
    <div class="row mb-4 align-items-stretch">
        <!-- Banner Utama Sales Center -->
        <div class="col-lg-9 col-md-8 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm rounded-4 text-white h-100" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-white text-success rounded-3 p-3 me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-cart-check-fill fs-3 text-success"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1">Rekap Penjualan Sales Center</h4>
                            <p class="mb-0 text-white-50 small">Pencatatan manual penjualan harian toko untuk sinkronisasi sisa stok</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 bg-white text-dark p-3 rounded-4 shadow-sm" style="min-width: 220px;">
                        <div class="text-center flex-fill">
                            <span class="d-block small text-muted fw-semibold text-uppercase" style="font-size: 10px;">Terjual Hari Ini</span>
                            <span class="fs-4 fw-bold text-success"><?= number_format($summary['total_pcs_hari_ini']) ?> <small class="fs-6 fw-normal text-secondary">Pcs</small></span>
                        </div>
                        <div class="vr bg-secondary opacity-25" style="height: 35px;"></div>
                        <div class="text-center flex-fill">
                            <span class="d-block small text-muted fw-semibold text-uppercase" style="font-size: 10px;">Total Input</span>
                            <span class="fs-4 fw-bold text-dark"><?= number_format($summary['total_transaksi_hari_ini']) ?> <small class="fs-6 fw-normal text-secondary">Data</small></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Riwayat Sebelumnya -->
        <div class="col-lg-3 col-md-4">
            <div class="card border border-2 border-dashed shadow-sm rounded-4 h-100 text-center p-3 d-flex flex-column align-items-center justify-content-center bg-white" 
                 style="cursor: pointer; transition: all 0.2s;" 
                 data-bs-toggle="modal" 
                 data-bs-target="#modalRiwayatSebelumnya"
                 onmouseover="this.style.transform='scale(1.02)'" 
                 onmouseout="this.style.transform='scale(1)'">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 mb-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-calendar-week fs-4"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Riwayat Sebelumnya</h6>
                <small class="text-muted" style="font-size: 12px;">Cari data kalender lama</small>
            </div>
        </div>
    </div>

    <!-- Form Input Rekap Penjualan -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center mb-4">
                    <i class="bi bi-plus-circle-fill text-success fs-4 me-2"></i>
                    <h5 class="fw-bold mb-0 text-dark">Input Penjualan Terjual (Manual)</h5>
                </div>
                <form action="Proses_RekapPenjualan/proses_tambah_rekap.php" method="POST">
                    <div id="containerInputPenjualan">
                        <div class="row align-items-end mb-3 baris-input">
                            <div class="col-md-4">
                                <label class="small text-secondary fw-semibold mb-1">Pilih Produk & Warna</label>
                                <select name="id_varian[]" class="form-select border border-secondary-subtle bg-white select-varian" required onchange="getUkuranRekap(this)" style="border-radius: 8px; padding: 10px;">
                                    <option value="">-- Pilih Produk & Warna --</option>
                                    <?= $options_varian ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-secondary fw-semibold mb-1">Ukuran</label>
                                <select name="ukuran[]" class="form-select border border-secondary-subtle bg-white select-ukuran" required disabled style="border-radius: 8px; padding: 10px;">
                                    <option value="">Pilih Produk Terlebih Dahulu</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="small text-secondary fw-semibold mb-1">Jumlah Terjual (PCS)</label>
                                <input type="number" name="jumlah_terjual[]" class="form-control border border-secondary-subtle bg-white" placeholder="0" min="1" required style="border-radius: 8px; padding: 10px;">
                            </div>
                            <div class="col-md-2">
                                <label class="small text-secondary fw-semibold mb-1">Tanggal</label>
                                <input type="date" name="tgl_rekap[]" class="form-control border border-secondary-subtle bg-white" value="<?= date('Y-m-d') ?>" required style="border-radius: 8px; padding: 10px;">
                            </div>
                            <div class="col-md-1 d-flex gap-1">
                                <button type="button" class="btn btn-success rounded-3 w-100 py-2" onclick="tambahBarisInput()" title="Tambah Produk">
                                    <i class="bi bi-plus-lg fs-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-success rounded-pill px-5 py-2.5 fw-bold shadow-sm" style="background: #11998e; border: none;">
                            <i class="bi bi-check-lg me-1"></i> Simpan Rekap Penjualan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabel Rekap Penjualan Hari Ini -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark text-uppercase small mb-0 d-flex align-items-center">
                        <span class="p-1.5 bg-success rounded-2 me-2 d-inline-block" style="width: 8px; height: 18px;"></span>
                        Rekap Penjualan Hari Ini (<?= date('d-m-Y') ?>)
                    </h6>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="small text-secondary bg-light">
                            <tr>
                                <th class="border-0 ps-3 py-3 text-uppercase fw-bold">Waktu</th>
                                <th class="border-0 py-3 text-uppercase fw-bold">Produk & Varian</th>
                                <th class="border-0 text-center py-3 text-uppercase fw-bold">Ukuran</th>
                                <th class="border-0 text-center py-3 text-uppercase fw-bold">Terjual</th>
                                <th class="border-0 text-end pe-3 py-3 text-uppercase fw-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($q_rekap_hari_ini && mysqli_num_rows($q_rekap_hari_ini) > 0): 
                                while($row = mysqli_fetch_assoc($q_rekap_hari_ini)): ?>
                            <tr>
                                <td class="small ps-3 text-secondary fw-semibold"><?= date('H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <span class="fw-bold text-dark d-block mb-0"><?= htmlspecialchars($row['nama_produk']) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($row['nama_warna']) ?></small>
                                </td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= strtoupper($row['ukuran']) ?></span></td>
                                <td class="text-center fw-bold text-success">+<?= $row['jumlah_terjual'] ?> PCS</td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-link text-warning p-1 me-1" 
                                            onclick="bukaModalEdit('<?= $row['id_rekap'] ?>', '<?= htmlspecialchars($row['nama_produk'] . ' - ' . $row['nama_warna']) ?>', '<?= $row['ukuran'] ?>', '<?= $row['jumlah_terjual'] ?>', '<?= $row['tgl_rekap'] ?>')" 
                                            title="Edit">
                                        <i class="bi bi-pencil-square fs-5"></i>
                                    </button>
                                    <a href="Proses_RekapPenjualan/hapus_rekap.php?id=<?= $row['id_rekap'] ?>" class="btn btn-sm btn-link text-danger p-1" onclick="return confirm('Yakin ingin menghapus rekap penjualan ini?')" title="Hapus">
                                        <i class="bi bi-trash3 fs-5"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">
                                    <i class="bi bi-inbox text-muted opacity-50 d-block fs-3 mb-2"></i>
                                    Belum ada pencatatan rekap penjualan untuk hari ini.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CARD BARU: Riwayat Seluruh Rekap Penjualan (Tersimpan) -->
<div class="modal fade" id="modalRiwayatSebelumnya" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-3">
                    <h6 class="fw-bold text-dark text-uppercase small mb-0 d-flex align-items-center">
                        <span class="p-1.5 bg-primary rounded-2 me-2 d-inline-block" style="width: 8px; height: 18px;"></span>
                        RIWAYAT SELURUH REKAP PENJUALAN (TERSIMPAN)
                    </h6>

                    <!-- Form Filter Tanggal yang Sudah Diperbaiki (Tanpa Tombol Reset Ganda) -->
                    <div class="d-flex align-items-center gap-2">
                        <form method="GET" action="index.php" class="d-flex align-items-center gap-2 m-0">
                            <input type="hidden" name="page" value="rekap">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0 text-muted" style="border-top-left-radius: 6px; border-bottom-left-radius: 6px;">
                                    <i class="bi bi-calendar"></i>
                                </span>
                                <input type="date" name="tgl_cari" class="form-control border-start-0" value="<?= htmlspecialchars($filter_tgl) ?>" style="border-top-right-radius: 6px; border-bottom-right-radius: 6px;">
                            </div>
                            <button type="submit" class="btn btn-sm text-white px-3 d-flex align-items-center gap-1 fw-medium" style="background-color: #2b5797; border-radius: 6px; border: none; height: 31px;">
                                <i class="bi bi-search"></i> Cari 
                            </button>
                            <?php if(!empty($filter_tgl)): ?>
                                <a href="index.php?page=rekap" class="btn-close text-reset ms-1" style="font-size: 12px;" title="Reset Filter Tanggal"></a>
                            <?php endif; ?>
                        </form>
                        
                        <?php if(empty($filter_tgl)): ?>
                            <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="small text-secondary bg-light">
                            <tr>
                                <th class="border-0 ps-3 py-3 text-uppercase fw-bold">Tanggal & Waktu</th>
                                <th class="border-0 py-3 text-uppercase fw-bold">Produk & Varian</th>
                                <th class="border-0 text-center py-3 text-uppercase fw-bold">Ukuran</th>
                                <th class="border-0 text-center py-3 text-uppercase fw-bold">Jumlah Terjual</th>
                                <th class="border-0 text-end pe-3 py-3 text-uppercase fw-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($q_riwayat_all && mysqli_num_rows($q_riwayat_all) > 0): 
                                while($row_all = mysqli_fetch_assoc($q_riwayat_all)): ?>
                            <tr>
                                <td class="small ps-3 text-secondary">
                                    <span class="fw-semibold text-dark d-block"><?= date('d-m-Y', strtotime($row_all['tgl_rekap'])) ?></span>
                                    <small class="text-muted"><?= date('H:i', strtotime($row_all['created_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block mb-0"><?= htmlspecialchars($row_all['nama_produk']) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($row_all['nama_warna']) ?></small>
                                </td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= strtoupper($row_all['ukuran']) ?></span></td>
                                <td class="text-center fw-bold text-success">+<?= $row_all['jumlah_terjual'] ?> PCS</td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-link text-warning p-1 me-1" 
                                            onclick="bukaModalEdit('<?= $row_all['id_rekap'] ?>', '<?= htmlspecialchars($row_all['nama_produk'] . ' - ' . $row_all['nama_warna']) ?>', '<?= $row_all['ukuran'] ?>', '<?= $row_all['jumlah_terjual'] ?>', '<?= $row_all['tgl_rekap'] ?>')" 
                                            title="Edit">
                                        <i class="bi bi-pencil-square fs-5"></i>
                                    </button>
                                    <a href="Proses_RekapPenjualan/hapus_rekap.php?id=<?= $row_all['id_rekap'] ?>" class="btn btn-sm btn-link text-danger p-1" onclick="return confirm('Yakin ingin menghapus rekap penjualan ini?')" title="Hapus">
                                        <i class="bi bi-trash3 fs-5"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted small">
                                    <i class="bi bi-folder-x text-muted opacity-50 d-block fs-2 mb-2"></i>
                                    Tidak ada riwayat rekap penjualan yang ditemukan.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Rekap Penjualan -->
<div class="modal fade" id="modalEditRekap" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Rekap Penjualan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="Proses_RekapPenjualan/proses_edit_rekap.php" method="POST">
                <div class="modal-body py-3">
                    <input type="hidden" name="id_rekap" id="edit_id_rekap">
                    
                    <div class="mb-3">
                        <label class="small text-secondary fw-semibold mb-1">Produk & Varian</label>
                        <input type="text" id="edit_nama_produk" class="form-control bg-light" readonly>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small text-secondary fw-semibold mb-1">Ukuran</label>
                            <input type="text" id="edit_ukuran" class="form-control bg-light text-uppercase fw-bold" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-secondary fw-semibold mb-1">Jumlah Terjual (PCS)</label>
                            <input type="number" name="jumlah_terjual" id="edit_jumlah_terjual" class="form-control" min="1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-secondary fw-semibold mb-1">Tanggal Rekap</label>
                        <input type="date" name="tgl_rekap" id="edit_tgl_rekap" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function getUkuranRekap(selectElement) {
    const idVarian = selectElement.value;
    const row = selectElement.closest('.baris-input');
    const selectUkuran = row.querySelector('.select-ukuran');

    if (!idVarian) {
        selectUkuran.disabled = true;
        selectUkuran.innerHTML = '<option value="">Pilih Produk Terlebih Dahulu</option>';
        return;
    }

    selectUkuran.innerHTML = '<option value="">Mengambil ukuran...</option>';
    selectUkuran.disabled = true;

    fetch('Proses_PindahStok/DetailVarian.php?id_varian=' + idVarian)
        .then(response => response.json())
        .then(data => {
            let options = '<option value="">-- Pilih Size --</option>';
            data.forEach(item => {
                options += `<option value="${item.size}">${item.size.toUpperCase()}</option>`;
            });
            selectUkuran.disabled = false;
            selectUkuran.innerHTML = options;
        })
        .catch(err => {
            selectUkuran.innerHTML = '<option value="">Gagal memuat ukuran</option>';
        });
}

function tambahBarisInput() {
    const container = document.getElementById('containerInputPenjualan');
    const today = new Date().toISOString().split('T')[0];
    
    const newRow = document.createElement('div');
    newRow.className = 'row align-items-end mb-3 baris-input';
    newRow.innerHTML = `
        <div class="col-md-4">
            <select name="id_varian[]" class="form-select border border-secondary-subtle bg-white select-varian" required onchange="getUkuranRekap(this)" style="border-radius: 8px; padding: 10px;">
                <option value="">-- Pilih Produk & Warna --</option>
                <?= $options_varian ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="ukuran[]" class="form-select border border-secondary-subtle bg-white select-ukuran" required disabled style="border-radius: 8px; padding: 10px;">
                <option value="">Pilih Produk Terlebih Dahulu</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="jumlah_terjual[]" class="form-control border border-secondary-subtle bg-white" placeholder="0" min="1" required style="border-radius: 8px; padding: 10px;">
        </div>
        <div class="col-md-2">
            <input type="date" name="tgl_rekap[]" class="form-control border border-secondary-subtle bg-white" value="${today}" required style="border-radius: 8px; padding: 10px;">
        </div>
        <div class="col-md-1 d-flex gap-1">
            <button type="button" class="btn btn-outline-danger rounded-3 w-100 py-2" onclick="hapusBarisInput(this)" title="Hapus Baris">
                <i class="bi bi-trash3 fs-5"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
}

function hapusBarisInput(buttonElement) {
    buttonElement.closest('.baris-input').remove();
}

function bukaModalEdit(id, nama, ukuran, jumlah, tgl) {
    document.getElementById('edit_id_rekap').value = id;
    document.getElementById('edit_nama_produk').value = nama;
    document.getElementById('edit_ukuran').value = ukuran;
    document.getElementById('edit_jumlah_terjual').value = jumlah;
    document.getElementById('edit_tgl_rekap').value = tgl;
    
    var modalEdit = new bootstrap.Modal(document.getElementById('modalEditRekap'));
    modalEdit.show();
}

// Otomatis buka Modal jika user melakukan pencarian tanggal
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('tgl_cari')) {
        var modalRiwayat = new bootstrap.Modal(document.getElementById('modalRiwayatSebelumnya'));
        modalRiwayat.show();
    }
});
</script>