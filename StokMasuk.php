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

// Cek apakah ada Produk atau Warna Baru dari pembelian_roll (yang diisi manual) yang belum terdaftar di Katalog
$q_cek_baru = mysqli_query($conn, "
    SELECT COUNT(*) as jml_baru 
    FROM pembelian_roll pb
    LEFT JOIN produk pr ON LOWER(pb.nama_produk_manual) = LOWER(pr.nama_produk)
    LEFT JOIN varian_warna v ON (pr.id_produk = v.id_produk AND LOWER(pb.warna_manual) = LOWER(v.nama_warna))
    WHERE pb.is_resolved = 0 
      AND pb.id_varian IS NULL 
      AND (pr.id_produk IS NULL OR v.id_varian IS NULL)
");
$data_cek_baru = mysqli_fetch_assoc($q_cek_baru);
$ada_produk_baru = ($data_cek_baru['jml_baru'] > 0);
// Query mengelompokkan SEMUA warna dari produk baru di pembelian_roll
$list_produk_baru = [];
if ($ada_produk_baru) {
    $q_detail_baru = mysqli_query($conn, "
        SELECT 
            pb.nama_produk_manual, 
            pb.jenis_bahan_manual, 
            GROUP_CONCAT(DISTINCT pb.warna_manual SEPARATOR ', ') as semua_warna
        FROM pembelian_roll pb
        LEFT JOIN produk pr ON LOWER(pb.nama_produk_manual) = LOWER(pr.nama_produk)
        LEFT JOIN varian_warna v ON (pr.id_produk = v.id_produk AND LOWER(pb.warna_manual) = LOWER(v.nama_warna))
        WHERE pb.is_resolved = 0 
          AND pb.id_varian IS NULL 
          AND (pr.id_produk IS NULL OR v.id_varian IS NULL)
        GROUP BY LOWER(pb.nama_produk_manual)
    ");
    while ($row_pb = mysqli_fetch_assoc($q_detail_baru)) {
        $list_produk_baru[] = $row_pb;
    }
}
?>

<div class="container-fluid py-4">
    <!-- Header Dashboard -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 text-white" style="background: linear-gradient(135deg, #1e3c72, #2a5298);">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-white text-primary rounded-3 p-3 me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-box-seam fs-3 text-dark"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1">Pencatatan Stok Masuk</h4>
                            <p class="mb-0 text-white-50 small">Manajemen inventaris barang masuk dari konveksi/pemasok secara real-time</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shortcut Menu -->
    <div class="row mb-4">
        <!-- 1. CARD PALING KIRI: Riwayat Pembelian Roll Pemilik -->
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-start border-success border-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-success small fw-bold text-uppercase mb-1">Riwayat Pembelian</h6>
                        <h5 class="fw-bold mb-2 text-dark">Data Pembelian Roll</h5>
                        <p class="small text-muted mb-3">Tabel riwayat pembelian bahan roll kain dari supplier</p>
                        <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalRiwayatPemilik" onclick="jalankanCariRiwayatPemilik()">
                            <i class="bi bi-folder2-open me-2"></i> Buka Riwayat
                        </button>
                    </div>
                    <div class="display-5 text-success opacity-25">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. CARD TENGAH: Barang Lebih/Kurang -->
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-start border-danger border-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-danger small fw-bold text-uppercase mb-1">Kontrol Selisih</h6>
                        <h5 class="fw-bold mb-2 text-dark">Barang Lebih / Kurang</h5>
                        <p class="small text-muted mb-3">Pantau pengiriman konveksi yang tidak sesuai nota</p>
                        <button class="btn btn-outline-danger btn-sm rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalSelisih">
                            <i class="bi bi-exclamation-triangle me-2"></i> Lihat Riwayat Selisih
                        </button>
                    </div>
                    <div class="display-5 text-danger opacity-25">
                        <i class="bi bi-patch-minus-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 3. CARD PALING KANAN: Kalender -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-start border-primary border-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-primary small fw-bold text-uppercase mb-1">Arsip Data</h6>
                        <h5 class="fw-bold mb-2 text-dark">Riwayat Sebelumnya</h5>
                        <p class="small text-muted mb-3">Cari data masuk berdasarkan tanggal kalender</p>
                        <button class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalKalender">
                            <i class="bi bi-calendar3 me-2"></i> Buka Kalender
                        </button>
                    </div>
                    <div class="display-5 text-primary opacity-25">
                        <i class="bi bi-archive-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modul Penambahan Produk / Varian Warna Baru -->
    <div class="card border-0 shadow-sm rounded-4 text-white mb-4" style="background: linear-gradient(135deg, #1e3c72, #2a5298);">
        <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">Penambahan Produk</h4>
                    <p class="mb-0 text-white-50 small">
                        <?php if ($ada_produk_baru): ?>
                            <span class="text-warning fw-bold"><i class="bi bi-exclamation-circle-fill me-1"></i> Terdeteksi produk baru pada Pembelian Roll yang belum terdaftar!</span>
                        <?php else: ?>
                            Kelola penambahan katalog produk kapan saja.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <!-- Tombol ini HANYA MUNCUL jika ada produk baru yang terdeteksi dari pembelian roll -->
                <?php if ($ada_produk_baru): ?>
                <button class="btn btn-light text-primary fw-bold rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahProduk">
                    <i class="bi bi-plus-circle-fill me-1 text-primary"></i> Tambah Produk Baru
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Form Input Stok Masuk Utama -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center mb-4">
                    <i class="bi bi-plus-circle text-primary fs-4 me-2"></i>
                    <h5 class="fw-bold mb-0 text-dark">Input Stok Masuk</h5>
                </div>
                <form action="Proses_StokMasuk/Proses_stok_masuk.php" method="POST">
                    <div class="row">
                        <!-- Dropdown Produk Disesuaikan dengan pembelian_roll yang Pending (is_resolved = 0) -->
                        <div class="col-md-4 mb-3">
                            <label class="small text-secondary fw-semibold mb-1">Pilih Produk</label>
                            <select name="id_produk" id="pilihProduk" class="form-select border border-secondary-subtle bg-white" required onchange="getVarianDanUkuran()" style="border-radius: 8px; padding: 10px;">
                                <option value="">-- Pilih Produk  --</option>
                                <?php 
                                // Query Produk yang ada di pembelian_roll & is_resolved = 0
                                $q_produk_pemilik = "
                                    SELECT DISTINCT p.id_produk, p.nama_produk 
                                    FROM pembelian_roll pb
                                    JOIN varian_warna v ON pb.id_varian = v.id_varian
                                    JOIN produk p ON v.id_produk = p.id_produk
                                    WHERE pb.is_resolved = 0
                                    UNION
                                    SELECT DISTINCT p.id_produk, p.nama_produk 
                                    FROM pembelian_roll pb
                                    JOIN produk p ON LOWER(pb.nama_produk_manual) = LOWER(p.nama_produk)
                                    WHERE pb.is_resolved = 0 AND pb.id_varian IS NULL
                                    ORDER BY nama_produk ASC";
                                
                                $res_prod = mysqli_query($conn, $q_produk_pemilik);
                                while($p = mysqli_fetch_assoc($res_prod)) {
                                    echo "<option value='".$p['id_produk']."'>".$p['nama_produk']."</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Dropdown Warna Dinamis -->
                        <div class="col-md-4 mb-3">
                            <label class="small text-secondary fw-semibold mb-1">Pilih Warna</label>
                            <select name="id_varian" id="pilihVarian" class="form-select border border-secondary-subtle bg-white" required disabled style="border-radius: 8px; padding: 10px;">
                                <option value="">Pilih Produk Terlebih Dahulu</option>
                            </select>
                        </div>

                        <!-- Dropdown Ukuran Dinamis -->
                        <div class="col-md-4 mb-3">
                            <label class="small text-secondary fw-semibold mb-1">Pilih Ukuran</label>
                            <select name="ukuran_kolom" id="pilihUkuran" class="form-select border border-secondary-subtle bg-white" required disabled style="border-radius: 8px; padding: 10px;">
                                <option value="">Pilih Produk Terlebih Dahulu</option>
                            </select>
                        </div>
                    </div>

                    <div class="row align-items-end mb-4">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="small text-secondary fw-semibold mb-1">Jumlah Seharusnya (Sesuai Nota)</label>
                            <input type="number" name="jumlah" class="form-control border border-secondary-subtle bg-white" placeholder="Masukkan kuantitas nota" min="1" required style="border-radius: 8px; padding: 10px;">
                        </div>
                        <div class="col-md-4 col-6">
                            <label class="small text-success mb-1 fw-bold"><i class="bi bi-plus-circle-fill me-1"></i> Barang Lebih</label>
                            <input type="number" name="barang_lebih" class="form-control border border-success-subtle bg-success-subtle text-success fw-bold" placeholder="0" min="0" value="0" style="border-radius: 8px; padding: 10px;">
                        </div>
                        <div class="col-md-4 col-6">
                            <label class="small text-danger mb-1 fw-bold"><i class="bi bi-dash-circle-fill me-1"></i> Barang Kurang</label>
                            <input type="number" name="barang_kurang" class="form-control border border-danger-subtle bg-danger-subtle text-danger fw-bold" placeholder="0" min="0" value="0" style="border-radius: 8px; padding: 10px;">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-5 py-2.5 fw-bold shadow-sm" style="background: #2a5298; border: none;">
                            <i class="bi bi-check2-circle me-2"></i> Konfirmasi Barang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 1: RIWAYAT PEMBELIAN ROLL -->
    <div class="modal fade" id="modalRiwayatPemilik" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow-lg p-3">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-folder2-open text-primary"></i> Data Pembelian Roll (Pemilik)
                        </h5>
                        <p class="small text-muted mb-0">Daftar riwayat lengkap transaksi pembelian roll kain</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div id="hasilCariRiwayatPemilik">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted small">Memuat riwayat pembelian...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill btn-sm fw-bold" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat Hari Ini -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark text-uppercase small mb-0 d-flex align-items-center">
                        <span class="p-1.5 bg-secondary-subtle rounded-2 me-2 d-inline-block" style="width: 8px; height: 18px;"></span>
                        Kelola Input Hari Ini
                    </h6>
                    <span class="badge bg-light text-secondary border border-secondary-subtle px-2.5 py-1.5 rounded-pill">Hari Ini</span>
                </div>
                
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="small text-secondary bg-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th class="border-0 ps-3 py-3 text-uppercase fw-bold" style="font-size: 0.75rem;">Jam</th>
                                <th class="border-0 py-3 text-uppercase fw-bold" style="font-size: 0.75rem;">Produk</th>
                                <th class="border-0 text-center py-3 text-uppercase fw-bold" style="font-size: 0.75rem;">Qty</th>
                                <th class="border-0 text-center py-3 text-uppercase fw-bold" style="font-size: 0.75rem;">Selisih</th>
                                <th class="border-0 text-end pe-3 py-3 text-uppercase fw-bold" style="font-size: 0.75rem; width: 110px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $query_log = "SELECT l.*, p.nama_produk, v.nama_warna 
                                          FROM log_stok_masuk l
                                          JOIN varian_warna v ON l.id_varian = v.id_varian
                                          JOIN produk p ON v.id_produk = p.id_produk
                                          WHERE DATE(l.tgl_masuk) = CURDATE() 
                                          ORDER BY l.tgl_masuk DESC";
                            $log = mysqli_query($conn, $query_log);

                            if ($log && mysqli_num_rows($log) > 0):
                                while($row = mysqli_fetch_assoc($log)):
                            ?>
                            <tr>
                                <td class="small ps-3 text-secondary fw-semibold"><?= date('H:i', strtotime($row['tgl_masuk'])) ?></td>
                                <td>
                                    <span class="fw-bold text-dark d-block mb-0" style="font-size: 0.95rem;"><?= $row['nama_produk'] ?></span>
                                    <small class="text-muted fw-medium"><?= $row['nama_warna'] ?> &bull; Size: <?= strtoupper($row['ukuran']) ?></small>
                                </td>
                                <td class="text-center fw-bold text-primary" style="font-size: 1rem;"><?= $row['jumlah_masuk'] ?></td>
                                <td class="text-center small">
                                    <?php if($row['barang_lebih'] > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-2">+<?= $row['barang_lebih'] ?> Lebih</span>
                                    <?php endif; ?>
                                    <?php if($row['barang_kurang'] > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-2">-<?= $row['barang_kurang'] ?> Kurang</span>
                                    <?php endif; ?>
                                    <?php if($row['barang_lebih'] == 0 && $row['barang_kurang'] == 0): ?>
                                        <span class="text-muted fw-semibold">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-sm btn-link text-warning p-1 me-1 hover-scale" onclick="modalEditNotaUtama(<?= $row['id_log_masuk'] ?>)" title="Edit">
                                        <i class="bi bi-pencil-square fs-5"></i>
                                    </button>
                                    <button class="btn btn-sm btn-link text-danger p-1 hover-scale" onclick="konfirmasiHapusNotaUtama(<?= $row['id_log_masuk'] ?>)" title="Hapus">
                                        <i class="bi bi-trash3 fs-5"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted small">
                                    <i class="bi bi-inbox text-muted opacity-50 d-block fs-2 mb-2"></i>
                                    Belum ada riwayat stok masuk hari ini.
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

<!-- MODAL TAMBAH PRODUK BARU -->
<div class="modal fade" id="modalTambahProduk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark">Tambah Produk Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form action="Proses_StokMasuk/tambah_produk.php" method="POST" enctype="multipart/form-data">
                    
                    <!-- DROPDOWN PILIH DARI PEMBELIAN ROLL -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-primary mb-1">
                            <i class="bi bi-magic me-1"></i> Pilih Produk Baru dari Pembelian Roll
                        </label>
                        <select id="selectProdukBaruRoll" class="form-select form-select-lg border-primary-subtle fs-6 rounded-3 bg-light" onchange="autoFillFormProdukBaru(this)">
                            <option value="">-- Pilih Produk yang Ingin Ditambahkan --</option>
                            <?php foreach ($list_produk_baru as $pb_item): ?>
                                <option value='<?= htmlspecialchars(json_encode($pb_item), ENT_QUOTES, 'UTF-8') ?>'>
                                    <?= htmlspecialchars($pb_item['nama_produk_manual']) ?> (<?= htmlspecialchars($pb_item['semua_warna'] ?? '-') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" style="font-size: 0.75rem;">Memilih item di atas akan mengisi form secara otomatis.</small>
                    </div>

                    <hr class="my-3 opacity-25">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Nama Produk</label>
                        <input type="text" name="nama_produk" id="inputNamaProduk" class="form-control form-control-lg border-secondary-subtle fs-6 rounded-3" placeholder="Contoh: Kemeja Flanel" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Jenis Bahan</label>
                        <input type="text" name="jenis_bahan" id="inputJenisBahan" class="form-control form-control-lg border-secondary-subtle fs-6 rounded-3" placeholder="Contoh: Katun Rayon" required>
                    </div>

                    <!-- UBAH LABEL MENJADI 'WARNA' & DUKUNG MULTI WARNA -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Warna (Pisahkan dengan Koma jika Lebih dari 1)</label>
                        <div class="input-group">
                            <input type="color" name="kode_warna" class="form-control form-control-color border-secondary-subtle rounded-start-3" value="#2a5298" style="width: 50px; height: 45px;" title="Pilih Kode Warna Default">
                            <input type="text" name="nama_warna" id="inputNamaWarna" class="form-control form-control-lg border-secondary-subtle fs-6 rounded-end-3" placeholder="Contoh: Hitam, Merah, Ungu" required>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Semua warna di atas akan dibuatkan variannya secara otomatis.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark mb-1">Foto Produk</label>
                        <input type="file" name="foto_produk" class="form-control form-control-lg border-secondary-subtle fs-6 rounded-3" accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-dark w-100 py-2.5 fw-bold rounded-3" style="background-color: #212529;">
                        Simpan Produk & Semua Warna
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL SELISIH BARANG -->
<div class="modal fade" id="modalSelisih" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold text-danger d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Riwayat Selisih Barang
                    </h5>
                    <p class="text-muted small mb-0">Daftar semua log pengiriman konveksi yang mengalami selisih hitung</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <?php 
                $query_selisih = "SELECT l.*, p.nama_produk, v.nama_warna 
                                  FROM log_stok_masuk l
                                  JOIN varian_warna v ON l.id_varian = v.id_varian
                                  JOIN produk p ON v.id_produk = p.id_produk
                                  WHERE l.barang_lebih > 0 OR l.barang_kurang > 0
                                  ORDER BY l.tgl_masuk DESC";
                $res_selisih = mysqli_query($conn, $query_selisih);
                $jumlah_selisih = mysqli_num_rows($res_selisih);

                $wa_text_kolektif = "⚠️ *LAPORAN REKAPITULASI SELISIH PENGIRIMAN KONVEKSI* ⚠️\n";
                $wa_text_kolektif .= "Ditemukan *" . $jumlah_selisih . "* transaksi selisih masuk gudang:\n\n";
                
                $no = 1;
                $data_selisih_array = [];
                
                if ($jumlah_selisih > 0) {
                    while($s = mysqli_fetch_assoc($res_selisih)) {
                        $data_selisih_array[] = $s;
                        
                        $hari_tgl = date('d-m-Y', strtotime($s['tgl_masuk']));
                        $nama_barang = $s['nama_produk'] . ' (' . $s['nama_warna'] . ' - ' . strtoupper($s['ukuran']) . ')';
                        $qty_nota = $s['jumlah_masuk'];
                        $info_selisih = ($s['barang_lebih'] > 0) ? "Kelebihan +" . $s['barang_lebih'] . " pcs" : "Kekurangan -" . $s['barang_kurang'] . " pcs";
                        
                        $wa_text_kolektif .= $no . ". [" . $hari_tgl . "]\n"
                                           . "    📦 *Produk*: " . $nama_barang . "\n"
                                           . "    📄 *Nota*: " . $qty_nota . " pcs\n"
                                           . "    🚨 *Selisih*: *" . $info_selisih . "*\n\n";
                        $no++;
                    }
                    $wa_text_kolektif .= "Mohon diperiksa kembali dengan tim produksi konveksi. Terima kasih.";
                    $wa_url_kolektif = "https://api.whatsapp.com/send?text=" . urlencode($wa_text_kolektif);
                }
                ?>

                <?php if ($jumlah_selisih > 0): ?>
                    <div class="d-flex justify-content-end mb-3">
                        <a href="<?= $wa_url_kolektif ?>" target="_blank" class="btn btn-success fw-bold rounded-pill px-4 shadow-sm text-white border-0" style="background-color: #2ca759;">
                            <i class="bi bi-whatsapp me-2"></i> Kirim Semua Laporan Selisih (<?= $jumlah_selisih ?> Data)
                        </a>
                    </div>
                <?php endif; ?>

                <div class="table-responsive rounded-3 bg-light p-2" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="small text-secondary sticky-top bg-light" style="z-index: 1;">
                            <tr>
                                <th class="border-0 ps-3">Hari/Tgl</th>
                                <th class="border-0">Produk</th>
                                <th class="border-0 text-center">Qty Nota</th>
                                <th class="border-0 text-center">Selisih</th>
                                <th class="border-0 text-end pe-3" style="width: 110px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($jumlah_selisih > 0):
                                foreach($data_selisih_array as $row_s):
                            ?>
                            <tr>
                                <td class="small ps-3">
                                    <span class="d-block fw-bold text-dark"><?= date('d M Y', strtotime($row_s['tgl_masuk'])) ?></span>
                                    <small class="text-muted fw-medium"><?= date('H:i', strtotime($row_s['tgl_masuk'])) ?> WIB</small>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark d-block mb-0"><?= $row_s['nama_produk'] ?></span>
                                    <small class="text-muted fw-medium"><?= $row_s['nama_warna'] ?> (Size: <?= strtoupper($row_s['ukuran']) ?>)</small>
                                </td>
                                <td class="text-center fw-bold text-dark"><?= $row_s['jumlah_masuk'] ?></td>
                                <td class="text-center">
                                    <?php if($row_s['barang_lebih'] > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1.5 rounded-2">+<?= $row_s['barang_lebih'] ?> Lebih</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1.5 rounded-2">-<?= $row_s['barang_kurang'] ?> Kurang</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-sm btn-link text-warning p-1 me-1" onclick="modalEditSpesifik(<?= $row_s['id_log_masuk'] ?>)" title="Edit">
                                        <i class="bi bi-pencil-square fs-5"></i>
                                    </button>
                                    <button class="btn btn-sm btn-link text-danger p-1" onclick="konfirmasiHapus(<?= $row_s['id_log_masuk'] ?>)" title="Hapus">
                                        <i class="bi bi-trash3 fs-5"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted small">
                                    <i class="bi bi-check-circle text-success fs-2 mb-2 d-block opacity-75"></i>
                                    Alhamdulillah, tidak ada selisih barang dalam pengiriman konveksi.
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

<!-- MODAL KALENDER ARSIP -->
<div class="modal fade" id="modalKalender" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-archive me-2 text-primary"></i> Arsip Stok Masuk</h5>
                    <p class="text-muted small mb-0">Cari data lama berdasarkan tanggal kalender</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-2 mb-4">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-light border border-secondary-subtle"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" id="filterTanggal" class="form-control bg-light border border-secondary-subtle" value="<?= date('Y-m-d') ?>" style="height: 45px; border-radius: 0 8px 8px 0;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100 fw-bold border-0" onclick="jalankanCariRiwayat()" style="height: 45px; border-radius: 8px; background: #2a5298;">
                            <i class="bi bi-search me-2"></i> Cari Data
                        </button>
                    </div>
                </div>
                <div id="hasilCariRiwayat" class="bg-light rounded-3 p-3" style="min-height: 200px; max-height: 350px; overflow-y: auto;">
                    <div class="text-center py-5">
                        <i class="bi bi-calendar2-check text-muted opacity-50" style="font-size: 2.5rem;"></i>
                        <p class="text-muted small mt-2">Silakan pilih tanggal dan klik Cari</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL FORM EDIT DATA -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Input Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="formEditLoading" class="text-center py-4">
                    <div class="spinner-border text-primary shadow-sm" role="status"></div>
                    <p class="small text-muted mt-2">Mengambil data...</p>
                </div>
                <div id="kontenEdit"></div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-scale { transition: transform 0.2s ease; }
    .hover-scale:hover { transform: scale(1.15); }
    .table-responsive::-webkit-scrollbar, #hasilCariRiwayat::-webkit-scrollbar { width: 6px; }
    .table-responsive::-webkit-scrollbar-track, #hasilCariRiwayat::-webkit-scrollbar-track { background: transparent; }
    .table-responsive::-webkit-scrollbar-thumb, #hasilCariRiwayat::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<script>
function getVarianDanUkuran() {
    const idProduk = document.getElementById('pilihProduk').value;
    const selectVarian = document.getElementById('pilihVarian');
    const selectUkuran = document.getElementById('pilihUkuran');

    if (idProduk === "") {
        selectVarian.disabled = true;
        selectVarian.innerHTML = '<option value="">Pilih Produk Terlebih Dahulu</option>';
        selectUkuran.disabled = true;
        selectUkuran.innerHTML = '<option value="">Pilih Produk Terlebih Dahulu</option>';
        return;
    }

    // Ambil Varian Warna
    selectVarian.innerHTML = '<option value="">Mengambil warna...</option>';
    selectVarian.disabled = true;
    fetch('Proses_StokMasuk/VarianMasukTerfilter.php?id_produk=' + idProduk)
        .then(response => response.text())
        .then(data => {
            selectVarian.disabled = false;
            selectVarian.innerHTML = data;
        });

    // Ambil Target Size
    selectUkuran.innerHTML = '<option value="">Mengambil ukuran...</option>';
    selectUkuran.disabled = true;
    fetch('Proses_StokMasuk/UkuranMasukTerfilter.php?id_produk=' + idProduk)
        .then(response => response.text())
        .then(data => {
            selectUkuran.disabled = false;
            selectUkuran.innerHTML = data;
        });
}

function modalEditSpesifik(idLog) {
    const modalSelisihEl = document.getElementById('modalSelisih');
    const modalSelisihInstance = bootstrap.Modal.getInstance(modalSelisihEl);
    if(modalSelisihInstance) modalSelisihInstance.hide();

    var myModal = new bootstrap.Modal(document.getElementById('modalEdit'));
    myModal.show();
    
    document.getElementById('formEditLoading').style.display = 'block';
    document.getElementById('kontenEdit').innerHTML = '';

    fetch('Proses_StokMasuk/DataTerakhir.php?id_log=' + idLog)
        .then(response => response.text())
        .then(data => {
            document.getElementById('formEditLoading').style.display = 'none';
            document.getElementById('kontenEdit').innerHTML = data;
        });
}

function konfirmasiHapus(idLog) {
    if (confirm("Apakah Anda yakin ingin menghapus riwayat stok masuk ini? Tindakan ini akan mengembalikan jumlah saldo stok barang ke kondisi semula.")) {
        window.location.href = 'Proses_StokMasuk/hapus_stok_masuk.php?id_log=' + idLog;
    }
}

function jalankanCariRiwayat() {
    const tglValue = document.getElementById('filterTanggal').value;
    const wadahHasil = document.getElementById('hasilCariRiwayat');
    if (!tglValue) {
        alert("Silakan pilih tanggal terlebih dahulu.");
        return;
    }
    wadahHasil.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Mencari data...</p></div>';
    fetch('Proses_StokMasuk/AmbilRiwayatTGL.php?tanggal=' + tglValue)
        .then(res => res.text())
        .then(data => {
            wadahHasil.innerHTML = data;
        })
        .catch(err => {
            wadahHasil.innerHTML = '<div class="alert alert-danger">Gagal memuat data.</div>';
        });
}

function jalankanCariRiwayatPemilik() {
    const wadahHasil = document.getElementById('hasilCariRiwayatPemilik');
    if (!wadahHasil) return;

    wadahHasil.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Memuat riwayat pembelian roll...</p></div>';
    
    fetch('Proses_StokMasuk/AmbilRiwayatPemilik.php')
        .then(res => {
            if(!res.ok) throw new Error("HTTP error " + res.status);
            return res.text();
        })
        .then(data => {
            wadahHasil.innerHTML = data;
        })
        .catch(err => {
            wadahHasil.innerHTML = '<div class="alert alert-danger">Gagal memuat data riwayat pembelian roll.</div>';
        });
}

function modalEditNotaUtama(idLog) {
    var myModal = new bootstrap.Modal(document.getElementById('modalEdit'));
    myModal.show();
    
    document.getElementById('formEditLoading').style.display = 'block';
    document.getElementById('kontenEdit').innerHTML = '';

    fetch('Proses_StokMasuk/DataNotaUtama.php?id_log=' + idLog)
        .then(response => response.text())
        .then(data => {
            document.getElementById('formEditLoading').style.display = 'none';
            document.getElementById('kontenEdit').innerHTML = data;
        });
}

function konfirmasiHapusNotaUtama(idLog) {
    if (confirm("Apakah Anda yakin ingin menghapus seluruh riwayat stok masuk ini? Saldo katalog produk akan dikurangi kembali.")) {
        window.location.href = 'Proses_StokMasuk/hapus_stok_masuk_utama.php?id_log=' + idLog;
    }
}

function autoFillFormProdukBaru(selectEl) {
    const val = selectEl.value;
    const inputNamaProduk = document.getElementById('inputNamaProduk');
    const inputJenisBahan = document.getElementById('inputJenisBahan');
    const inputNamaWarna = document.getElementById('inputNamaWarna');

    if (!val) {
        inputNamaProduk.value = '';
        inputJenisBahan.value = '';
        inputNamaWarna.value = '';
        return;
    }

    try {
        const data = JSON.parse(val);
        inputNamaProduk.value = data.nama_produk_manual || '';
        inputJenisBahan.value = data.jenis_bahan_manual || '';
        // Otomatis mengisi semua warna sekaligus dipisah koma (misal: Hitam, Merah, Ungu)
        inputNamaWarna.value = data.semua_warna || '';
    } catch (e) {
        console.error("Gagal mengurai data JSON produk:", e);
    }
}

</script>