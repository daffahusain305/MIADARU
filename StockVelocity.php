<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'koneksi.php';

// --- 1. Fastest Moving ---
$q_fast = mysqli_query($conn, "SELECT nama_produk, nama_warna, SUM(jumlah) as total 
                               FROM log_distribusi 
                               WHERE tgl_pindah >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                               GROUP BY nama_produk, nama_warna 
                               ORDER BY total DESC LIMIT 1");
$fast = mysqli_fetch_assoc($q_fast);

// --- 2. Cold Storage ---
$q_cold = mysqli_query($conn, "SELECT p.nama_produk, v.nama_warna FROM varian_warna v 
                               JOIN produk p ON p.id_produk = v.id_produk 
                               LEFT JOIN log_distribusi l ON v.id_varian = l.id_varian 
                               WHERE l.id_varian IS NULL LIMIT 1");
$cold = mysqli_fetch_assoc($q_cold);

// --- 3. Riwayat Perpindahan Hari Ini (Perbaikan JOIN agar nama_produk & nama_warna muncul) ---
$hari_ini = date('Y-m-d');
$q_riwayat = mysqli_query($conn, "SELECT l.*, p.nama_produk, v.nama_warna 
                                  FROM log_distribusi l
                                  JOIN varian_warna v ON l.id_varian = v.id_varian
                                  JOIN produk p ON v.id_produk = p.id_produk
                                  WHERE DATE(l.tgl_pindah) = '$hari_ini' 
                                  ORDER BY l.tgl_pindah DESC");
?>

<link rel="stylesheet" href="CSS/Velocity.css">

<!-- GANTI STRUKTUR CARD ATAS JADI 4 KOLOM (col-md-3) -->
<div class="row mb-4">
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card card-gradient-hover border-0 shadow-sm p-4 d-flex flex-column justify-content-between h-100" 
             style="border-radius: 20px; background: linear-gradient(45deg, #f03e3e, #ff6b6b); min-height: 160px; color: white;">
            <div class="d-flex justify-content-between align-items-start">
                <i class="bi bi-fire fs-4 text-warning"></i> <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="mt-3">
                <small class="text-white-50 d-block mb-1">Fastest Moving</small>
                <h4 class="fw-bold mb-0 text-truncate"><?= $fast['nama_produk'] ?? 'Belum Ada' ?></h4>
                <small class="text-white-50 text-truncate d-block"><?= $fast['nama_warna'] ?? 'Top demand this week' ?></small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card card-dark-modern-hover border-0 shadow-sm p-4 d-flex flex-column justify-content-between h-100" 
             style="border-radius: 20px; background: #1a1d23; min-height: 160px; color: white;">
            <div class="d-flex justify-content-between align-items-start">
                <i class="bi bi-snow fs-4 text-info"></i>
                <i class="bi bi-graph-down-arrow"></i>
            </div>
            <div class="mt-3">
                <small class="text-secondary d-block mb-1">Cold Storage</small>
                <h4 class="fw-bold mb-0 text-truncate"><?= $cold['nama_produk'] ?? 'Stok Aman' ?></h4>
                <small class="text-secondary text-truncate d-block"><?= $cold['nama_warna'] ?? 'Overstocked variants' ?></small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3 mb-md-0">
        <div class="card card-dashed-hover border-0 shadow-sm h-100 p-4 d-flex flex-column justify-content-center align-items-center text-center" 
             style="border-radius: 20px; background: #ffffff; min-height: 160px; border: 2px dashed #e9ecef !important; cursor: pointer;"
             data-bs-toggle="modal" data-bs-target="#modalRiwayat">
            <div class="rounded-circle bg-light p-3 mb-2">
                <i class="bi bi-calendar3 fs-3 text-primary"></i>
            </div>
            <h6 class="fw-bold mb-1">Riwayat Sebelumnya</h6>
            <small class="text-muted">Cari data kalender lama</small>
        </div>
    </div>

    <!-- CARD KE-4 BARU: RIWAYAT MUTASI -->
    <div class="col-md-3">
        <div class="card card-dashed-hover border-0 shadow-sm h-100 p-4 d-flex flex-column justify-content-center align-items-center text-center" 
             style="border-radius: 20px; background: #ffffff; min-height: 160px; border: 2px dashed #b5e2fa !important; cursor: pointer;"
             data-bs-toggle="modal" data-bs-target="#modalMutasi" onclick="bukaMutasi()">
            <div class="rounded-circle bg-primary-subtle p-3 mb-2">
                <i class="bi bi-arrow-left-right fs-3 text-primary"></i>
            </div>
            <h6 class="fw-bold mb-1">Riwayat Mutasi</h6>
            <small class="text-muted">Semua informasi & Cari produk</small>
        </div>
    </div>
</div>

<!-- --- FORM DISTRIBUSI --- -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">🚀 Distribusi Stok ke Sales Center</h5>
        <form id="formPindah" method="POST" action="Proses_PindahStok/proses_pindah.php">
            <div id="wrapperPindah">
                <div class="row g-2 mb-3 item-pindah align-items-center">
                    <div class="col-md-5">
                        <select name="id_varian[]" class="form-select border-0 bg-light rounded-3 select-produk" required style="height: 50px;">
                            <option value="">Pilih Produk - Warna...</option>
                            <?php 
                            $v_query = mysqli_query($conn, "SELECT v.id_varian, p.nama_produk, v.nama_warna FROM varian_warna v JOIN produk p ON p.id_produk = v.id_produk");
                            while($v = mysqli_fetch_assoc($v_query)) echo "<option value='{$v['id_varian']}'>{$v['nama_produk']} - {$v['nama_warna']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="ukuran[]" class="form-select border-0 bg-light rounded-3 select-ukuran" required style="height: 50px;">
                            <option value="">Size</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <input type="number" name="jumlah[]" class="form-control border-0 bg-light rounded-3 input-qty" placeholder="Qty" required style="height: 50px;">
                            <span class="input-group-text border-0 bg-light text-muted small sisa-stok">/ 0</span>
                        </div>
                    </div>
                    <div class="col-md-1 text-center">
                        <button type="button" class="btn btn-danger rounded-circle p-1 btn-tambah" style="width: 30px; height: 30px; line-height: 15px;">+</button>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn rounded-pill px-5 py-2 mt-3 fw-bold text-white" style="background-color: #2a5298; border-color: #2a5298;">Konfirmasi Pemindahan</button>
        </form>
    </div>
</div>

<!-- --- TABEL HARI INI --- -->
<div class="card border-0 shadow-sm rounded-4 bg-light">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-3 text-muted">RIWAYAT PERPINDAHAN KE SALES CENTER TERBARU</h6>
        <div class="table-responsive">
            <table class="table table-borderless align-middle">
                <thead class="text-muted small">
                    <tr>
                        <th>JAM</th> 
                        <th>PRODUK</th>
                        <th class="text-center">UKURAN</th>
                        <th class="text-center">JUMLAH</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-end">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($q_riwayat) > 0): 
                        while($l = mysqli_fetch_assoc($q_riwayat)): ?>
                    <tr>
                        <td class="small text-muted"><?= date('H:i', strtotime($l['tgl_pindah'])) ?></td>
                        <td>
                            <span class="fw-bold"><?= $l['nama_produk'] ?></span> <br>
                            <small class="text-muted text-uppercase" style="font-size: 10px;"><?= $l['nama_warna'] ?></small>
                        </td>
                        <td class="text-center"><span class="badge bg-white text-dark border"><?= strtoupper($l['ukuran']) ?></span></td>
                        <td class="text-center fw-bold"><?= $l['jumlah'] ?> PCS</td>
                        <td class="text-center text-success small"><i class="bi bi-check-circle-fill"></i> Moved</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <!-- Tombol Edit -->
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-start-pill px-3" 
                                        data-bs-toggle="modal" data-bs-target="#modalEditLog"
                                        data-id="<?= $l['id_log'] ?>" 
                                        data-produk="<?= $l['nama_produk'] ?> - <?= $l['nama_warna'] ?>"
                                        data-ukuran="<?= $l['ukuran'] ?>"
                                        data-jumlah="<?= $l['jumlah'] ?>"
                                        onclick="isiModalEdit(this)">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <!-- Tombol Hapus -->
                                <a href="Proses_PindahStok/aksi_log.php?aksi=hapus&id=<?= $l['id_log'] ?>" 
                                   class="btn btn-outline-danger btn-sm rounded-end-pill px-3"
                                   onclick="return confirm('Apakah Anda yakin ingin membatalkan perpindahan ini? Stok akan dikembalikan ke gudang utama.')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <img src="assets/img/empty-box.png" style="width: 50px; opacity: 0.5;" class="mb-2">
                            <p class="text-muted small">Belum ada perpindahan stok untuk hari ini.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL 1: ARSIP RIWAYAT KALENDER (Lama) -->
<div class="modal fade" id="modalRiwayat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <div class="modal-header border-0 p-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Arsip Perpindahan</h5>
                    <p class="text-muted small mb-0">Cari data lama berdasarkan tanggal kalender</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-2 mb-4">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" id="filterTanggal" class="form-control bg-light border-0" value="<?= date('Y-m-d') ?>" style="height: 50px;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100 fw-bold border-0" onclick="cariRiwayat()" style="height: 50px; border-radius: 12px; background: #4361ee;">
                            <i class="bi bi-search me-2"></i> Cari Data
                        </button>
                    </div>
                </div>
                <div id="hasilCariRiwayat" class="bg-light rounded-4 p-3" style="min-height: 200px;">
                    <div class="text-center py-5">
                        <i class="bi bi-calendar2-check text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p class="text-muted mt-2">Silakan pilih tanggal dan klik Cari</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2: REKAPITULASI AKUMULASI MUTASI PRODUK -->
<div class="modal fade" id="modalMutasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <div class="modal-header border-0 p-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">📊 Akumulasi Mutasi & Perputaran Produk</h5>
                    <p class="text-muted small mb-0">Total kuantitas barang yang telah didistribusikan dari awal hingga sekarang</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Input Pencarian Produk -->
                <div class="input-group mb-4 shadow-sm rounded-3 overflow-hidden">
                    <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="cariProdukMutasi" class="form-control border-0 p-3" placeholder="Ketik nama produk untuk memantau kecepatan stok (Contoh: Andaru)..." onkeyup="bukaMutasi()">
                </div>

                <!-- Wadah Hasil Tabel Mutasi -->
                <div id="kontenMutasi" class="table-responsive" style="max-height: 450px;">
                    <!-- Data rekap akan dirender otomatis via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL POP-UP EDIT QTY LOG -->
<div class="modal fade" id="modalEditLog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">✏️ Edit Jumlah Perpindahan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="Proses_PindahStok/aksi_log.php?aksi=edit" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" id="edit_id_log" name="id_log">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Produk & Varian</label>
                        <input type="text" id="edit_nama_produk" class="form-control bg-light border-0" readonly style="height: 45px;">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">Ukuran</label>
                            <input type="text" id="edit_ukuran" class="form-control bg-light border-0" readonly style="height: 45px;">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">Jumlah Baru (PCS)</label>
                            <input type="number" id="edit_jumlah" name="jumlah_baru" class="form-control border-1" required style="height: 45px;" min="1">
                        </div>
                    </div>
                    <small class="text-secondary d-block mt-2"><i class="bi bi-info-circle"></i> Stok gudang utama akan otomatis menyesuaikan selisih dari perubahan ini.</small>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- --- SCRIPT JAVASCRIPT --- -->
<script>
// Fungsi Menampilkan & Mencari Data Mutasi secara Live (AJAX)
function bukaMutasi() {
    const keyword = document.getElementById('cariProdukMutasi').value;
    const wadah = document.getElementById('kontenMutasi');
    
    wadah.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted small">Memuat seluruh data mutasi...</p>
        </div>`;

    fetch(`Proses_PindahStok/SemuaMutasi.php?search=${encodeURIComponent(keyword)}`)
        .then(res => res.text())
        .then(data => {
            wadah.innerHTML = data;
        })
        .catch(err => {
            console.error(err);
            wadah.innerHTML = '<div class="alert alert-danger">Gagal memuat data mutasi.</div>';
        });
}

// Handler Dropdown & Duplikasi Baris Form Input (Bawaan Kode Anda yang sudah dirapikan)
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('select-produk')) {
        const row = e.target.closest('.item-pindah');
        const idVarian = e.target.value;
        const selectUkuran = row.querySelector('.select-ukuran');
        const labelStok = row.querySelector('.sisa-stok');
        const inputQty = row.querySelector('.input-qty');

        if (!idVarian) {
            selectUkuran.innerHTML = '<option value="">Size</option>';
            labelStok.innerText = '/ 0';
            return;
        }

        fetch(`Proses_PindahStok/DetailVarian.php?id_varian=${idVarian}`)
            .then(res => res.json())
            .then(data => {
                let options = '<option value="">Size</option>';
                data.forEach(item => {
                    options += `<option value="${item.value}" data-max="${item.stok}">${item.size}</option>`;
                });
                selectUkuran.innerHTML = options;
                labelStok.innerText = '/ 0';
                inputQty.max = 0;
            });
    }

    if (e.target.classList.contains('select-ukuran')) {
        const row = e.target.closest('.item-pindah');
        const selectedOption = e.target.options[e.target.selectedIndex];
        const maxStok = selectedOption.getAttribute('data-max') || 0;
        
        row.querySelector('.sisa-stok').innerText = `/ ${maxStok}`;
        row.querySelector('.input-qty').max = maxStok;
    }
});

document.querySelector('.btn-tambah').addEventListener('click', function() {
    const wrapper = document.getElementById('wrapperPindah');
    const firstRow = document.querySelector('.item-pindah');
    const newRow = firstRow.cloneNode(true);
    
    newRow.querySelector('.select-produk').value = "";
    newRow.querySelector('.select-ukuran').innerHTML = '<option value="">Size</option>';
    newRow.querySelector('.input-qty').value = "";
    newRow.querySelector('.sisa-stok').innerText = "/ 0";
    
    const btn = newRow.querySelector('.btn-tambah');
    btn.classList.replace('btn-danger', 'btn-outline-secondary');
    btn.classList.replace('btn-tambah', 'btn-hapus');
    btn.innerText = "-";
    
    wrapper.appendChild(newRow);
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-hapus')) {
        e.target.closest('.item-pindah').remove();
    }
});

function cariRiwayat() {
    const tgl = document.getElementById('filterTanggal').value;
    const wadah = document.getElementById('hasilCariRiwayat');
    if (!tgl) { alert("Silakan pilih tanggal!"); return; }

    wadah.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted small">Mencari data distribusi...</p>
        </div>`;
    
    fetch(`Proses_PindahStok/Riwayat.php?tanggal=${tgl}`)
        .then(res => res.text())
        .then(data => { wadah.innerHTML = data; })
        .catch(err => { wadah.innerHTML = '<div class="alert alert-danger">Gagal memuat data.</div>'; });
}

function isiModalEdit(btn) {
    document.getElementById('edit_id_log').value = btn.getAttribute('data-id');
    document.getElementById('edit_nama_produk').value = btn.getAttribute('data-produk');
    document.getElementById('edit_ukuran').value = btn.getAttribute('data-ukuran').toUpperCase();
    document.getElementById('edit_jumlah').value = btn.getAttribute('data-jumlah');
}
</script>