<?php
include 'koneksi.php';

// Ambil data produk untuk dropdown input retur
$q_produk = mysqli_query($conn, "SELECT v.id_varian, p.nama_produk, v.nama_warna FROM varian_warna v JOIN produk p ON v.id_produk = p.id_produk ORDER BY p.nama_produk ASC");

// 1. QUERY UNTUK CARD TOTAL (Semua data yang masih 'Pending' dari hari-hari lalu sampai sekarang)
$total_kain = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah_retur) as total FROM log_retur WHERE keterangan = 'kain' AND status = 'Pending'"))['total'] ?? 0;
$total_noda = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah_retur) as total FROM log_retur WHERE keterangan = 'noda' AND status = 'Pending'"))['total'] ?? 0;

// ALL DATA PENDING (Untuk isi di dalam Pop-up Modal)
$q_modal_kain = mysqli_query($conn, "SELECT l.*, v.id_varian, v.nama_warna, p.nama_produk FROM log_retur l JOIN varian_warna v ON l.id_varian = v.id_varian JOIN produk p ON v.id_produk = p.id_produk WHERE l.keterangan = 'kain' AND l.status = 'Pending' ORDER BY l.tgl_retur DESC");
$q_modal_noda = mysqli_query($conn, "SELECT l.*, v.id_varian, v.nama_warna, p.nama_produk FROM log_retur l JOIN varian_warna v ON l.id_varian = v.id_varian JOIN produk p ON v.id_produk = p.id_produk WHERE l.keterangan = 'noda' AND l.status = 'Pending' ORDER BY l.tgl_retur DESC");

// 2. QUERY UNTUK TABEL UTAMA (Hanya menampilkan data yang diinput HARI INI saja)
$hari_ini = date('Y-m-d');
$q_tabel_kain = mysqli_query($conn, "SELECT l.*, v.id_varian, v.nama_warna, p.nama_produk FROM log_retur l JOIN varian_warna v ON l.id_varian = v.id_varian JOIN produk p ON v.id_produk = p.id_produk WHERE l.keterangan = 'kain' AND l.status = 'Pending' AND DATE(l.tgl_retur) = '$hari_ini' ORDER BY l.tgl_retur DESC");
$q_tabel_noda = mysqli_query($conn, "SELECT l.*, v.id_varian, v.nama_warna, p.nama_produk FROM log_retur l JOIN varian_warna v ON l.id_varian = v.id_varian JOIN produk p ON v.id_produk = p.id_produk WHERE l.keterangan = 'noda' AND l.status = 'Pending' AND DATE(l.tgl_retur) = '$hari_ini' ORDER BY l.tgl_retur DESC");
?>

<style>
    .card-hover-effect {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .card-hover-effect:hover {
        transform: scale(1.02);
        box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.15) !important;
    }
</style>

<div class="container-fluid px-4 py-3">
    <!-- CARD RINGKASAN DI ATAS -->
    <div class="row g-3 mb-4">
        <!-- CARD TOTAL CACAT NODA -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-warning-subtle text-warning-emphasis p-3 card-hover-effect">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div role="button" data-bs-toggle="modal" data-bs-target="#modalAllCacatNoda" style="cursor: pointer;" class="flex-grow-1">
                        <h6 class="fw-bold mb-1">TOTAL CACAT NODA (ALL PENDING)</h6>
                        <h2 class="fw-extrabold mb-0"><?= $total_noda ?> <span class="fs-5">PCS</span></h2>
                        <small class="text-muted" style="font-size: 11px;"><i class="bi bi-info-circle"></i> Klik card untuk lihat daftar detail</small>
                    </div>
                    <i class="bi bi-droplet-half fs-1 opacity-50 ms-3"></i>
                </div>
                <hr class="my-2 border-warning opacity-25">
                <div class="text-end">
                    <a href="Proses_ReturBarang/kirim_wa_retur.php?jenis=noda&scope=all" target="_blank" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold shadow-sm">
                        <i class="bi bi-whatsapp me-1"></i> Kirim Laporan 
                    </a>
                </div>
            </div>
        </div>

        <!-- CARD TOTAL CACAT KAIN -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-danger-subtle text-danger p-3 card-hover-effect">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div role="button" data-bs-toggle="modal" data-bs-target="#modalAllCacatKain" style="cursor: pointer;" class="flex-grow-1">
                        <h6 class="fw-bold mb-1">TOTAL CACAT KAIN (ALL PENDING)</h6>
                        <h2 class="fw-extrabold mb-0"><?= $total_kain ?> <span class="fs-5">PCS</span></h2>
                        <small class="text-muted" style="font-size: 11px;"><i class="bi bi-info-circle"></i> Klik card untuk lihat daftar detail</small>
                    </div>
                    <i class="bi bi-scissors fs-1 opacity-50 ms-3"></i>
                </div>
                <hr class="my-2 border-danger opacity-25">
                <div class="text-end">
                    <a href="Proses_ReturBarang/kirim_wa_retur.php?jenis=kain&scope=all" target="_blank" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold shadow-sm text-white">
                        <i class="bi bi-whatsapp me-1"></i> Kirim Laporan 
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- TOMBOL INPUT BARU -->
    <div class="mb-4">
        <button class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="collapse" data-bs-target="#formRetur">
            <i class="bi bi-plus-lg me-2"></i> Catat Barang Cacat / Retur
        </button>
    </div>

    <!-- COLLAPSE FORM INPUT -->
    <div class="collapse mb-4" id="formRetur">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3">Formulir Pencatatan Produk Rusak</h6>
            <form action="Proses_ReturBarang/aksi_retur.php?aksi=tambah" method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Pilih Produk & Varian</label>
                        <select name="id_varian" id="selectProdukRetur" class="form-select border-1 select-produk-retur" required onchange="muatUkuranDinamis(this.value)">
                            <option value="">-- Pilih Produk --</option>
                            <?php 
                            mysqli_data_seek($q_produk, 0); 
                            while($p = mysqli_fetch_assoc($q_produk)): 
                            ?>
                                <option value="<?= $p['id_varian'] ?>"><?= $p['nama_produk'] ?> (<?= $p['nama_warna'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Ukuran</label>
                        <select name="ukuran" id="selectUkuranRetur" class="form-select border-1 select-ukuran-retur" required disabled>
                            <option value="">Pilih Produk Dulu</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Jumlah (Qty)</label>
                        <input type="number" name="jumlah_retur" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Jenis Cacat</label>
                        <select name="keterangan" class="form-select border-1 text-danger fw-bold" required>
                            <option value="">--Pilih Jenis Cacat--</option>
                            <option value="kain">✂️ Cacat Kain (Slowing/Sobek/Bolong)</option>
                            <option value="noda">💧 Cacat Noda (Tinta/Kotor Segel/Minyak)</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-success rounded-pill px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL CACAT NODA HARI INI -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-warning-emphasis"><i class="bi bi-droplet-half me-2"></i> DATA CACAT NODA HARI INI</h6>
               
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>JAM INPUT</th>
                            <th>PRODUK</th>
                            <th class="text-center">SIZE</th>
                            <th class="text-center">QTY</th>
                            <th class="text-center">STATUS</th>
                            <th class="text-end">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($q_tabel_noda) > 0): while($rn = mysqli_fetch_assoc($q_tabel_noda)): ?>
                        <tr>
                            <td class="small"><?= date('H:i', strtotime($rn['tgl_retur'])) ?> WIB</td>
                            <td class="fw-bold"><?= $rn['nama_produk'] ?> <br><small class="text-muted"><?= $rn['nama_warna'] ?></small></td>
                            <td class="text-center"><span class="badge bg-light text-dark border"><?= strtoupper($rn['ukuran']) ?></span></td>
                            <td class="text-center fw-bold"><?= $rn['jumlah_retur'] ?> Pcs</td>
                            <td class="text-center"><span class="badge bg-warning-subtle text-warning-emphasis">Pending</span></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalEditRetur" 
                                        data-id="<?= $rn['id_retur'] ?>" 
                                        data-varian="<?= $rn['id_varian'] ?>"
                                        data-produk="<?= $rn['nama_produk'] ?> - <?= $rn['nama_warna'] ?>" 
                                        data-size="<?= strtoupper($rn['ukuran']) ?>" 
                                        data-qty="<?= $rn['jumlah_retur'] ?>" 
                                        data-cacat="<?= $rn['keterangan'] ?>" 
                                        onclick="isiModalEditRetur(this)"><i class="bi bi-pencil-square"></i> Process/Edit</button>
                                    <a href="Proses_ReturBarang/aksi_retur.php?aksi=hapus&id=<?= $rn['id_retur'] ?>" class="btn btn-outline-secondary" onclick="return confirm('Hapus data?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted small">Belum ada data masuk hari ini. Klik card diatas untuk mengelola data lama.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABEL CACAT KAIN HARI INI -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-scissors me-2"></i> DATA CACAT KAIN HARI INI</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>JAM INPUT</th>
                            <th>PRODUK</th>
                            <th class="text-center">SIZE</th>
                            <th class="text-center">QTY</th>
                            <th class="text-center">STATUS</th>
                            <th class="text-end">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($q_tabel_kain) > 0): while($rk = mysqli_fetch_assoc($q_tabel_kain)): ?>
                        <tr>
                            <td class="small"><?= date('H:i', strtotime($rk['tgl_retur'])) ?> WIB</td>
                            <td class="fw-bold"><?= $rk['nama_produk'] ?> <br><small class="text-muted"><?= $rk['nama_warna'] ?></small></td>
                            <td class="text-center"><span class="badge bg-light text-dark border"><?= strtoupper($rk['ukuran']) ?></span></td>
                            <td class="text-center fw-bold"><?= $rk['jumlah_retur'] ?> Pcs</td>
                            <td class="text-center"><span class="badge bg-danger-subtle text-danger">Pending</span></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalEditRetur" 
                                        data-id="<?= $rk['id_retur'] ?>" 
                                        data-varian="<?= $rk['id_varian'] ?>"
                                        data-produk="<?= $rk['nama_produk'] ?> - <?= $rk['nama_warna'] ?>" 
                                        data-size="<?= strtoupper($rk['ukuran']) ?>" 
                                        data-qty="<?= $rk['jumlah_retur'] ?>" 
                                        data-cacat="<?= $rk['keterangan'] ?>" 
                                        onclick="isiModalEditRetur(this)"><i class="bi bi-pencil-square"></i> Process/Edit</button>
                                    <a href="Proses_ReturBarang/aksi_retur.php?aksi=hapus&id=<?= $rk['id_retur'] ?>" class="btn btn-outline-secondary" onclick="return confirm('Hapus data?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted small">Belum ada data masuk hari ini. Klik card diatas untuk mengelola data lama.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- POP UP MODAL 1: SEMUA DATA CACAT KAIN PENDING -->
<div class="modal fade" id="modalAllCacatKain" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-scissors me-2"></i> Semua Riwayat Pending - Cacat Kain</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th>TANGGAL MASUK</th>
                                <th>PRODUK</th>
                                <th class="text-center">SIZE</th>
                                <th class="text-center">QTY</th>
                                <th class="text-end">AKSI KELOLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($q_modal_kain) > 0): while($mk = mysqli_fetch_assoc($q_modal_kain)): ?>
                            <tr>
                                <td class="small"><?= date('d-m-Y H:i', strtotime($mk['tgl_retur'])) ?></td>
                                <td class="fw-bold"><?= $mk['nama_produk'] ?> <br><small class="text-muted"><?= $mk['nama_warna'] ?></small></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= strtoupper($mk['ukuran']) ?></span></td>
                                <td class="text-center fw-bold text-danger"><?= $mk['jumlah_retur'] ?> Pcs</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-primary" 
                                            data-bs-dismiss="modal" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditRetur" 
                                            data-id="<?= $mk['id_retur'] ?>" 
                                            data-varian="<?= $mk['id_varian'] ?>"
                                            data-produk="<?= $mk['nama_produk'] ?> - <?= $mk['nama_warna'] ?>" 
                                            data-size="<?= strtoupper($mk['ukuran']) ?>" 
                                            data-qty="<?= $mk['jumlah_retur'] ?>" 
                                            data-cacat="<?= $mk['keterangan'] ?>" 
                                            onclick="isiModalEditRetur(this)"><i class="bi bi-pencil-square"></i> Kelola</button>
                                        <a href="Proses_ReturBarang/aksi_retur.php?aksi=hapus&id=<?= $mk['id_retur'] ?>" class="btn btn-secondary" onclick="return confirm('Hapus permanen?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada tumpukan data pending cacat kain.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- POP UP MODAL 2: SEMUA DATA CACAT NODA PENDING -->
<div class="modal fade" id="modalAllCacatNoda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning text-dark p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-droplet-half me-2"></i> Semua Riwayat Pending - Cacat Noda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th>TANGGAL MASUK</th>
                                <th>PRODUK</th>
                                <th class="text-center">SIZE</th>
                                <th class="text-center">QTY</th>
                                <th class="text-end">AKSI KELOLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($q_modal_noda) > 0): while($mn = mysqli_fetch_assoc($q_modal_noda)): ?>
                            <tr>
                                <td class="small"><?= date('d-m-Y H:i', strtotime($mn['tgl_retur'])) ?></td>
                                <td class="fw-bold"><?= $mn['nama_produk'] ?> <br><small class="text-muted"><?= $mn['nama_warna'] ?></small></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= strtoupper($mn['ukuran']) ?></span></td>
                                <td class="text-center fw-bold text-warning-emphasis"><?= $mn['jumlah_retur'] ?> Pcs</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-primary" 
                                            data-bs-dismiss="modal" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditRetur" 
                                            data-id="<?= $mn['id_retur'] ?>" 
                                            data-varian="<?= $mn['id_varian'] ?>"
                                            data-produk="<?= $mn['nama_produk'] ?> - <?= $mn['nama_warna'] ?>" 
                                            data-size="<?= strtoupper($mn['ukuran']) ?>" 
                                            data-qty="<?= $mn['jumlah_retur'] ?>" 
                                            data-cacat="<?= $mn['keterangan'] ?>" 
                                            onclick="isiModalEditRetur(this)"><i class="bi bi-pencil-square"></i> Kelola</button>
                                        <a href="Proses_ReturBarang/aksi_retur.php?aksi=hapus&id=<?= $mn['id_retur'] ?>" class="btn btn-secondary" onclick="return confirm('Hapus permanen?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada tumpukan data pending cacat noda.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL MASTER EDIT & SELESAIKAN (3 TOMBOL) -->
<div class="modal fade" id="modalEditRetur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="Proses_ReturBarang/aksi_retur.php?aksi=edit" method="POST" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">✏️ Edit & Proses Retur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="edit_id_retur" name="id_retur">
                <input type="hidden" id="edit_id_varian" name="id_varian">

                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Produk</label>
                    <input type="text" id="edit_nama_produk" class="form-control bg-light" readonly>
                </div>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Ukuran</label>
                        <input type="text" id="edit_size" name="ukuran" class="form-control bg-light" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Jumlah Cacat (Qty)</label>
                        <input type="number" id="edit_qty" name="jumlah_retur_baru" class="form-control" min="1" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Kategori Kerusakan</label>
                    <select id="edit_cacat" name="keterangan_baru" class="form-select" onchange="toggleFormNodaBersih()" required>
                        <option value="kain">Cacat Kain</option>
                        <option value="noda">Cacat Noda</option>
                    </select>
                </div>

                <!-- FORM DINAMIS UNTUK CACAT NODA BERSIH -->
                <div id="wrapper_noda_bersih" class="p-3 bg-light rounded-3 border mb-2 d-none">
                    <label class="form-label small fw-bold text-success mb-1">
                        ✨ Cacat Noda Bersih (Restore Ke Stok)
                    </label>
                    <input type="number" id="edit_qty_bersih" name="cacat_noda_bersih" class="form-control border-success" placeholder="Masukkan Qty yang bersih" min="0">
                    <small class="text-muted d-block mt-1" style="font-size: 11px;">
                        * Hanya berlaku saat menekan tombol "Selesaikan". Barang bersih kembali ke stok, sisanya dianggap hangus.
                    </small>
                </div>
            </div>

            <!-- 3 TOMBOL UTAMA -->
            <div class="modal-footer border-0 p-4 pt-0 justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <div class="d-flex gap-2">
                    <button type="submit" name="submit_type" value="simpan" class="btn btn-primary rounded-pill px-3">
                        Simpan Perubahan
                    </button>
                    <button type="submit" name="submit_type" value="selesaikan" class="btn btn-success rounded-pill px-3">
                        <i class="bi bi-check-circle me-1"></i> Selesaikan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function isiModalEditRetur(btn) {
    let idRetur = btn.getAttribute('data-id');
    let idVarian= btn.getAttribute('data-varian');
    let produk  = btn.getAttribute('data-produk');
    let size    = btn.getAttribute('data-size');
    let qty     = btn.getAttribute('data-qty');
    let cacat   = btn.getAttribute('data-cacat');

    document.getElementById('edit_id_retur').value   = idRetur;
    document.getElementById('edit_id_varian').value  = idVarian;
    document.getElementById('edit_nama_produk').value = produk;
    document.getElementById('edit_size').value        = size;
    document.getElementById('edit_qty').value         = qty;
    document.getElementById('edit_cacat').value       = cacat;

    document.getElementById('edit_qty_bersih').value = qty;

    toggleFormNodaBersih();
}

function toggleFormNodaBersih() {
    let jenisCacat  = document.getElementById('edit_cacat').value;
    let wrapper     = document.getElementById('wrapper_noda_bersih');
    let inputBersih = document.getElementById('edit_qty_bersih');
    let totalQty    = document.getElementById('edit_qty').value;

    if (jenisCacat === 'noda') {
        wrapper.classList.remove('d-none');
        inputBersih.setAttribute('max', totalQty);
    } else {
        wrapper.classList.add('d-none');
        inputBersih.value = 0;
    }
}

document.getElementById('edit_qty').addEventListener('input', function() {
    document.getElementById('edit_qty_bersih').setAttribute('max', this.value);
});

function muatUkuranDinamis(idVarian) {
    const selectUkuran = document.getElementById('selectUkuranRetur');

    if (!idVarian) {
        selectUkuran.disabled = true;
        selectUkuran.innerHTML = '<option value="">Pilih Produk Dulu</option>';
        return;
    }

    selectUkuran.disabled = true;
    selectUkuran.innerHTML = '<option value="">Memuat Ukuran...</option>';

    fetch(`Proses_PindahStok/DetailVarian.php?id_varian=${idVarian}`)
        .then(res => res.json())
        .then(data => {
            let options = '<option value="">-- Pilih Ukuran --</option>';
            if (data.length > 0) {
                data.forEach(item => {
                    let sizeCode = item.value.replace('stok_', '').toLowerCase().trim();
                    let sizeLabel = item.size.toUpperCase();
                    options += `<option value="${sizeCode}">${sizeLabel}</option>`;
                });
                selectUkuran.disabled = false;
            } else {
                options = '<option value="">Tidak ada ukuran tersedia</option>';
            }
            selectUkuran.innerHTML = options;
        })
        .catch(err => {
            console.error("Gagal memuat ukuran:", err);
            selectUkuran.innerHTML = '<option value="">Gagal memuat ukuran</option>';
        });
}
</script>