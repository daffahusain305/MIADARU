<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'koneksi.php';

// Ambil data varian warna & produk untuk dropdown form & modal edit
$q_varian = mysqli_query($conn, "SELECT v.id_varian, p.nama_produk, v.nama_warna 
                                FROM varian_warna v 
                                JOIN produk p ON v.id_produk = p.id_produk 
                                ORDER BY p.nama_produk ASC, v.nama_warna ASC");

$list_varian = [];
while ($v = mysqli_fetch_assoc($q_varian)) {
    $list_varian[] = $v;
}

// Tanggal filter riwayat (Default hari ini)
$tgl_riwayat = isset($_GET['tgl_riwayat']) ? $_GET['tgl_riwayat'] : date('Y-m-d');
?>

 <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 text-white" style="background: linear-gradient(135deg, #1e3c72, #2a5298);">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-white text-primary rounded-3 p-3 me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-box-seam fs-3 text-dark"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1">Riwayat Pembelian Kain</h4>
                            <p class="mb-0 text-white-50 small">Pencatatan Riwayat Pembelian Kain.</p>
                        </div>
                    </div>
            <div class="d-flex align-items-center gap-3 bg-white p-2 px-3 rounded-4 shadow-sm border">
            <div class="text-end">
                <span class="fw-bold d-block text-dark small" style="font-size: 13px;">Riwayat Sebelumnya</span>
                <span class="text-muted min-small">Cari data kalender lama</span>
            </div>
            <div>
                <form action="" method="GET" class="d-flex align-items-center gap-1" id="formRiwayat">
                    <input type="hidden" name="page" value="pembelian">
                    <input type="date" name="tgl_riwayat" value="<?= $tgl_riwayat ?>" class="form-control form-control-sm rounded-pill px-3 border-light-subtle bg-light fw-semibold text-primary" onchange="document.getElementById('formRiwayat').submit();">
                </form>
            </div>
            <?php if ($tgl_riwayat != date('Y-m-d')): ?>
                <a href="index.php?page=pembelian" class="btn btn-sm btn-outline-secondary rounded-pill" title="Kembali ke Hari Ini">
                    <i class="bi bi-arrow-counterclockwise"></i> Hari Ini
                </a>
            <?php endif; ?>
        </div>
                </div>
            </div>
        </div>
    </div>

<div class="container-fluid py-4">
    

    <div class="row g-4">
        <!-- FORM INPUT PEMBELIAN ROLL -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-dark text-white p-3 px-4 border-0">
                    <h6 class="fw-bold mb-0 d-flex align-items-center">
                        <i class="bi bi-plus-circle me-2 fs-5"></i> Input Pembelian Roll Kain
                    </h6>
                </div>
                
                <div class="card-body p-4">
                    <form action="Proses_Pembelian/simpan_pembelian_roll.php" method="POST" id="formPembelian">
                        
                        <!-- BARIS 1: TANGGAL & NOTA -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Tanggal Masuk</label>
                                <input type="date" name="tgl_pembelian" class="form-control rounded-3 border-light-subtle" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">No. Nota / Surat</label>
                                <input type="text" name="no_nota" class="form-control rounded-3 border-light-subtle" placeholder="Opsional">
                            </div>
                        </div>

                        <!-- SUPPLIER -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Supplier / Toko Kain</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-light-subtle"><i class="bi bi-shop text-muted"></i></span>
                                <input type="text" name="supplier" class="form-control rounded-end-3 border-light-subtle" placeholder="Contoh: Bu Haji" required>
                            </div>
                        </div>

                        <!-- TAB SELECTION UNTUK METODE INPUT PRODUK -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary d-block">Pilih Metode Input Produk</label>
                            <ul class="nav nav-pills nav-fill bg-light p-1 rounded-3 border" id="productSourceTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active rounded-2 small py-1 fw-medium" id="existing-tab" data-bs-toggle="pill" data-bs-target="#tab-existing" type="button" role="tab">Produk Terdaftar</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link rounded-2 small py-1 fw-medium" id="manual-tab" data-bs-toggle="pill" data-bs-target="#tab-manual" type="button" role="tab">+ Produk Baru</button>
                                </li>
                            </ul>

                            <div class="tab-content mt-3" id="productSourceTabContent">
                                <!-- TAB A: PRODUK LAMA -->
                                    <div class="tab-pane fade show active" id="tab-existing" role="tabpanel">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label min-small text-muted mb-0">Pilih Produk & Warna</label>
                                            <!-- TOMBOL TAMBAH VARIAN -->
                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill shadow-sm" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#modalTambahVarian">
                                                <i class="bi bi-plus-lg"></i> Tambah Varian
                                            </button>
                                        </div>
                                        <select name="id_varian" id="id_varian" class="form-select rounded-3 border-light-subtle">
                                            <option value="">-- Pilih Produk & Warna --</option>
                                            <?php foreach ($list_varian as $v): ?>
                                                <option value="<?= $v['id_varian'] ?>">
                                                    <?= htmlspecialchars($v['nama_produk']) ?> — <?= htmlspecialchars($v['nama_warna']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted mt-1 d-block" style="font-size: 10px;">Jika varian warna untuk produk yang sudah ada belum tersedia, klik "Tambah Varian".</small>
                                    </div>

                                <!-- TAB B: PRODUK BARU / MANUAL -->
                                <div class="tab-pane fade" id="tab-manual" role="tabpanel">
                                    <div class="p-3 bg-light rounded-3 border border-dashed">
                                        <div class="mb-2">
                                            <label class="form-label min-small text-muted mb-1">Nama Kerudung Baru</label>
                                            <input type="text" name="nama_produk_manual" id="nama_produk_manual" class="form-control form-control-sm rounded-3" placeholder="Misal: Kerudung Bella Square">
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-5">
                                                <label class="form-label min-small text-muted mb-1">Bahan</label>
                                                <input type="text" name="jenis_bahan_manual" id="jenis_bahan_manual" class="form-control form-control-sm rounded-3" placeholder="Super Jersey Premium">
                                            </div>
                                            <div class="col-7">
                                                <label class="form-label min-small text-muted mb-1">Warna (Pisahkan dengan Koma)</label>
                                                <!-- TAMBAHKAN HELPER TEXT AGAR USER TAHU BISA INPUT BANYAK WARNA -->
                                                <input type="text" name="warna_manual" id="warna_manual" class="form-control form-control-sm rounded-3" placeholder="Contoh: Hitam, Merah, Ungu">
                                            </div>
                                        </div>
                                        <small class="text-primary mt-2 d-block" style="font-size: 11px;">
                                            <i class="bi bi-info-circle me-1"></i> Jika mengisi beberapa warna dipisah koma, sistem akan otomatis mencatat masing-masing warna secara terpisah.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- JUMLAH ROLL -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Jumlah Kain (Roll)</label>
                            <div class="input-group">
                                <input type="number" name="jumlah_roll" class="form-control border-light-subtle rounded-start-3" min="1" placeholder="0" required>
                                <span class="input-group-text bg-light border-light-subtle text-muted">Roll</span>
                            </div>
                        </div>

                        <!-- TARGET SIZE CHECKBOX -->
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary d-block">Rencana Peruntukan Size</label>
                            <div class="d-flex flex-wrap gap-2 pt-1">
                                <?php foreach (['S', 'M', 'L', 'XL', 'XXL'] as $size): ?>
                                    <input type="checkbox" class="btn-check" name="target_size[]" id="size_<?= $size ?>" value="<?= $size ?>" autocomplete="off">
                                    <label class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 min-w-40" for="size_<?= $size ?>"><?= $size ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 rounded-3 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-box-seam me-1"></i> Simpan Data Roll Datang
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- DAFTAR HISTORI KAIN DATANG HARIAN / SESUAI TANGGAL -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-header bg-white p-4 border-0 pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Daftar Roll Kain Datang</h6>
                        <span class="text-muted small">
                            <?= ($tgl_riwayat == date('Y-m-d')) ? 'Hari Ini ('.date('d M Y').')' : 'Tanggal: '.date('d M Y', strtotime($tgl_riwayat)); ?>
                        </span>
                    </div>
                    <?php if ($tgl_riwayat != date('Y-m-d')): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1">Mode Riwayat Lama</span>
                    <?php endif; ?>
                </div>

                <div class="table-responsive" style="max-height: 570px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <!-- Tambahkan sticky-top & bg-white pada execution thead -->
                            <thead class="table-light text-secondary sticky-top bg-white" style="z-index: 1;">
                                <tr class="text-uppercase fs-11 tracking-wider">
                                    <th class="border-0">Tanggal / Supplier</th>
                                    <th class="border-0">Produk & Warna</th>
                                    <th class="border-0 text-center">Jumlah</th>
                                    <th class="border-0">Rencana Size</th>
                                    <th class="border-0 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0">
                                <?php
                                // Query difilter berdasarkan tanggal yang dipilih ($tgl_riwayat)
                                $q_history = mysqli_query($conn, "
                                    SELECT pr.*, v.nama_warna, p.nama_produk 
                                    FROM pembelian_roll pr
                                    LEFT JOIN varian_warna v ON pr.id_varian = v.id_varian
                                    LEFT JOIN produk p ON v.id_produk = p.id_produk
                                    WHERE pr.tgl_pembelian = '$tgl_riwayat' 
                                    AND (pr.is_resolved = 0 OR pr.is_resolved IS NULL)
                                    ORDER BY pr.id_pembelian DESC
                                ");

                                if ($q_history && mysqli_num_rows($q_history) > 0):
                                    while ($h = mysqli_fetch_assoc($q_history)):
                                        $nota = !empty($h['no_nota']) ? htmlspecialchars($h['no_nota']) : '-';
                                        
                                        if (!empty($h['id_varian'])) {
                                            $nama_produk = htmlspecialchars($h['nama_produk']);
                                            $detail_warna = htmlspecialchars($h['nama_warna']);
                                            $type_badge   = '<span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2" style="font-size: 10px;">TERDAFTAR</span>';
                                        } else {
                                            $nama_produk = htmlspecialchars($h['nama_produk_manual']);
                                            $detail_warna = htmlspecialchars($h['warna_manual']);
                                            if (!empty($h['jenis_bahan_manual'])) {
                                                $detail_warna .= " • " . htmlspecialchars($h['jenis_bahan_manual']);
                                            }
                                            $type_badge   = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2" style="font-size: 10px;">BARU</span>';
                                        }

                                        $is_resolved = isset($h['is_resolved']) ? $h['is_resolved'] : 0;
                                ?>
                                <tr class="<?= $is_resolved ? 'bg-light-subtle' : '' ?>">
                                    <td>
                                        <span class="fw-semibold text-dark d-block"><?= date('d/m/Y', strtotime($h['tgl_pembelian'])) ?></span>
                                        <small class="text-muted d-block"><?= htmlspecialchars($h['supplier']) ?></small>
                                        <small class="text-muted fs-11">Nota: <?= $nota ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="fw-bold text-dark"><?= $nama_produk ?></span>
                                            <?= $type_badge ?>
                                        </div>
                                        <span class="text-secondary small d-block"><?= $detail_warna ?></span>
                                        <?php if ($is_resolved): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill mt-1" style="font-size: 10px;">
                                                <i class="bi bi-check-circle-fill me-1"></i> Resolved
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-bold">
                                            <?= $h['jumlah_roll'] ?> Roll
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            $sizes = explode(',', $h['target_size']);
                                            foreach ($sizes as $s): 
                                        ?>
                                            <span class="badge bg-light text-dark border me-1 my-1" style="font-size: 10px;"><?= trim($s) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <!-- TOMBOL AKSI: RESOLVED, EDIT, HAPUS -->
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <!-- TOMBOL RESOLVED -->
                                            <?php if ($is_resolved): ?>
                                                <a href="Proses_Pembelian/resolved.php?id=<?= $h['id_pembelian'] ?>&status=0&tgl=<?= $tgl_riwayat ?>" 
                                                   class="btn btn-success" 
                                                   title="Batalkan Resolved">
                                                    <i class="bi bi-check-lg"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="Proses_Pembelian/resolved.php?id=<?= $h['id_pembelian'] ?>&status=1&tgl=<?= $tgl_riwayat ?>" 
                                                   class="btn btn-outline-success" 
                                                   title="Mark as Resolved (Terselesaikan)"
                                                   onclick="return confirm('Tandai penerimaan kain ini sebagai Selesai / Resolved?')">
                                                    <i class="bi bi-check2"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- TOMBOL EDIT -->
                                            <button type="button" class="btn btn-outline-warning btn-edit" 
                                                    data-id="<?= $h['id_pembelian'] ?>"
                                                    data-tgl="<?= $h['tgl_pembelian'] ?>"
                                                    data-supplier="<?= htmlspecialchars($h['supplier']) ?>"
                                                    data-nota="<?= htmlspecialchars($h['no_nota'] ?? '') ?>"
                                                    data-varian="<?= $h['id_varian'] ?>"
                                                    data-nama="<?= htmlspecialchars($h['nama_produk_manual'] ?? '') ?>"
                                                    data-bahan="<?= htmlspecialchars($h['jenis_bahan_manual'] ?? '') ?>"
                                                    data-warna="<?= htmlspecialchars($h['warna_manual'] ?? '') ?>"
                                                    data-jumlah="<?= $h['jumlah_roll'] ?>"
                                                    data-size="<?= $h['target_size'] ?>"
                                                    title="Edit Data">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <!-- TOMBOL HAPUS -->
                                            <a href="Proses_Pembelian/hapus_pembelian.php?id=<?= $h['id_pembelian'] ?>&tgl=<?= $tgl_riwayat ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')"
                                               title="Hapus Data">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        Tidak ada data pembelian roll kain pada tanggal ini (<?= date('d/m/Y', strtotime($tgl_riwayat)) ?>).
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
</div>

<!-- MODAL TAMBAH VARIAN WARNA (PRODUK SUDAH ADA) -->
<div class="modal fade" id="modalTambahVarian" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h6 class="modal-title fw-bold"><i class="bi bi-palette me-2"></i>Tambah Varian Warna</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Arahkan action ke file proses baru atau file yang sudah kamu miliki -->
            <form action="Proses_Pembelian/tambah_varian.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Pilih Produk Induk</label>
                        <select name="id_produk" class="form-select rounded-3" required>
                            <option value="">-- Pilih Produk --</option>
                            <?php 
                            // Query untuk mengambil daftar unik nama produk saja
                            $q_produk_induk = mysqli_query($conn, "SELECT id_produk, nama_produk FROM produk ORDER BY nama_produk ASC");
                            while($p = mysqli_fetch_assoc($q_produk_induk)): 
                            ?>
                                <option value="<?= $p['id_produk'] ?>"><?= htmlspecialchars($p['nama_produk']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Warna (Bisa lebih dari 1, pisah koma)</label>
                        <div class="input-group">
                            <input type="color" name="kode_warna" class="form-control form-control-color border-light-subtle rounded-start-3" value="#000000" style="width: 50px; height: 40px;" title="Pilih Kode Warna Dasar">
                            <input type="text" name="nama_warna" class="form-control rounded-end-3" placeholder="Misal: Hitam, Merah, Biru" required>
                        </div>
                        <small class="text-primary mt-1 d-block" style="font-size: 11px;">
                           <i class="bi bi-info-circle me-1"></i> Pisahkan dengan koma jika menambah banyak warna sekaligus untuk produk ini.
                        </small>
                    </div>

                    <!-- Pilihan Size Stok Awal (Opsional) -->
                     <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary d-block">Ukuran Tersedia (Aktifkan untuk stok)</label>
                        <div class="d-flex flex-wrap gap-2 pt-1">
                            <?php foreach (['S', 'M', 'L', 'XL', 'XXL'] as $size): ?>
                                <input type="checkbox" class="btn-check" name="ukuran[]" id="var_size_<?= $size ?>" value="<?= $size ?>" checked>
                                <label class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 min-w-40" for="var_size_<?= $size ?>"><?= $size ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Foto Varian (Opsional)</label>
                        <input type="file" name="foto_produk" class="form-control rounded-3" accept="image/*">
                    </div>

                </div>
                <div class="modal-footer bg-light rounded-bottom-4 border-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Simpan Varian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT DATA PEMBELIAN -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4">
                <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Roll Kain</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="Proses_Pembelian/edit_pembelian.php" method="POST">
                <input type="hidden" name="tgl_riwayat" value="<?= $tgl_riwayat ?>">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_pembelian" id="edit_id">

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Tanggal Masuk</label>
                            <input type="date" name="tgl_pembelian" id="edit_tgl" class="form-control rounded-3" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">No. Nota / Surat</label>
                            <input type="text" name="no_nota" id="edit_nota" class="form-control rounded-3">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Supplier / Toko Kain</label>
                        <input type="text" name="supplier" id="edit_supplier" class="form-control rounded-3" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Pilih Produk (Jika Terdaftar)</label>
                        <select name="id_varian" id="edit_varian" class="form-select rounded-3">
                            <option value="">-- Pilih Produk & Warna --</option>
                            <?php foreach ($list_varian as $v): ?>
                                <option value="<?= $v['id_varian'] ?>">
                                    <?= htmlspecialchars($v['nama_produk']) ?> — <?= htmlspecialchars($v['nama_warna']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="p-3 bg-light rounded-3 border border-dashed mb-3">
                        <div class="mb-2">
                            <label class="form-label min-small text-muted mb-1">Nama Produk Baru (Jika Manual)</label>
                            <input type="text" name="nama_produk_manual" id="edit_nama_manual" class="form-control form-control-sm rounded-3">
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label min-small text-muted mb-1">Bahan</label>
                                <input type="text" name="jenis_bahan_manual" id="edit_bahan_manual" class="form-control form-control-sm rounded-3">
                            </div>
                            <div class="col-6">
                                <label class="form-label min-small text-muted mb-1">Warna</label>
                                <input type="text" name="warna_manual" id="edit_warna_manual" class="form-control form-control-sm rounded-3">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Jumlah Roll</label>
                        <input type="number" name="jumlah_roll" id="edit_jumlah" class="form-control rounded-3" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary d-block">Rencana Peruntukan Size</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach (['S', 'M', 'L', 'XL', 'XXL'] as $size): ?>
                                <input type="checkbox" class="btn-check edit-size-check" name="target_size[]" id="edit_size_<?= $size ?>" value="<?= $size ?>">
                                <label class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 min-w-40" for="edit_size_<?= $size ?>"><?= $size ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4 border-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-semibold"><i class="bi bi-check-lg me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- STYLES & SCRIPT -->
<style>
    .fs-11 { font-size: 11px; }
    .min-small { font-size: 11px; }
    .min-w-40 { min-width: 40px; text-align: center; }
    .border-dashed { border-style: dashed !important; }
    .tracking-wider { letter-spacing: 0.05em; }

    #productSourceTab .nav-link.active {
        background-color: #0d6efd !important;
        color: #ffffff !important;
        font-weight: 600 !important;
    }
    #productSourceTab .nav-link {
        color: #495057 !important;
    }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Handling Tab Input Form
    const existingTab = document.getElementById('existing-tab');
    const manualTab = document.getElementById('manual-tab');
    const selectVarian = document.getElementById('id_varian');
    const inputNama = document.getElementById('nama_produk_manual');
    const inputBahan = document.getElementById('jenis_bahan_manual');
    const inputWarna = document.getElementById('warna_manual');

    if(existingTab) {
        existingTab.addEventListener('click', function() {
            inputNama.value = '';
            inputBahan.value = '';
            inputWarna.value = '';
        });
    }

    if(manualTab) {
        manualTab.addEventListener('click', function() {
            selectVarian.value = '';
        });
    }

    // Handling Modal Edit
    const modalEditElement = document.getElementById('modalEdit');
    const bsModalEdit = new bootstrap.Modal(modalEditElement);

    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_tgl').value = this.dataset.tgl;
            document.getElementById('edit_supplier').value = this.dataset.supplier;
            document.getElementById('edit_nota').value = this.dataset.nota;
            document.getElementById('edit_varian').value = this.dataset.varian || '';
            document.getElementById('edit_nama_manual').value = this.dataset.nama || '';
            document.getElementById('edit_bahan_manual').value = this.dataset.bahan || '';
            document.getElementById('edit_warna_manual').value = this.dataset.warna || '';
            document.getElementById('edit_jumlah').value = this.dataset.jumlah;

            const sizeArray = this.dataset.size ? this.dataset.size.split(',').map(s => s.trim()) : [];
            document.querySelectorAll('.edit-size-check').forEach(chk => {
                chk.checked = sizeArray.includes(chk.value);
            });

            bsModalEdit.show();
        });
    });
});
</script>