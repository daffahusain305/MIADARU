<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (!isset($conn)) {
    include 'koneksi.php';
}

// AMBIL ID UNTUK HIGHLIGHT
$highlight_id = isset($_GET['highlight_varian']) ? $_GET['highlight_varian'] : '';

$search = "";
$where_clause = "";
if (isset($_GET['cari']) && trim($_GET['cari']) !== '') {
    $search = mysqli_real_escape_string($conn, trim($_GET['cari']));
    $where_clause = "WHERE p.nama_produk LIKE '%$search%' OR v.nama_warna LIKE '%$search%' OR v.warna LIKE '%$search%'";
}

// Query eksplisit mengambil v.id_varian unik untuk menghindari ambigu
$query = "SELECT 
            p.id_produk, p.nama_produk, p.jenis_bahan, 
            v.id_varian, v.nama_warna, v.warna, v.foto_produk,
            v.stok_s, v.stok_m, v.stok_l, v.stok_xl, v.stok_xxl
          FROM varian_warna v 
          JOIN produk p ON v.id_produk = p.id_produk 
          $where_clause
          ORDER BY v.id_varian DESC";

$result = mysqli_query($conn, $query);
?>

<link rel="stylesheet" href="CSS/Produk.css">

<div class="row align-items-center g-3 mb-4">
    <div class="col-12 d-md-flex justify-content-end gap-2">
        <form method="GET" action="" class="position-relative flex-grow-1 flex-md-grow-0 mb-2 mb-md-0">
    <input type="hidden" name="page" value="katalog">
    
    <div class="position-relative d-flex align-items-center">
        <!-- Icon Search -->
        <i class="bi bi-search position-absolute start-0 ms-3 text-secondary" style="z-index: 5;"></i>
        
                <!-- Input Text -->
                <input type="search" name="cari" 
                    class="form-control border-0 ps-5 pe-4 rounded-pill shadow-sm" 
                    placeholder="Cari kain atau model..." 
                    value="<?= htmlspecialchars($search) ?>"
                    style="background-color: #f1f3f5; color: #212529; height: 46px; min-width: 280px; font-size: 0.95rem; transition: all 0.2s ease-in-out;"
                    onfocus="this.style.backgroundColor='#ffffff'; this.style.boxShadow='0 0.5rem 1rem rgba(0, 0, 0, 0.08)';"
                    onblur="this.style.backgroundColor='#f1f3f5'; this.style.boxShadow='none';">
            </div>
        </form>
    </div>
</div>

<div class="row">
    <?php while ($row = mysqli_fetch_assoc($result)): 
        $is_highlighted = ($highlight_id == $row['id_varian']) ? 'highlight-card' : '';
        
        // Hitung total stok nyata (mengabaikan -1 sebagai indikator ukuran tidak tersedia)
        $total_tampilan = 0;
        foreach(['s','m','l','xl','xxl'] as $s) {
            $val = (int)$row['stok_'.$s];
            if($val > 0) {
                $total_tampilan += $val;
            }
        }
    ?>
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm rounded-5 p-4 bg-white h-100 <?= $is_highlighted ?>" id="varian-<?= $row['id_varian'] ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($row['nama_produk']) ?></h6>
                    <span class="badge rounded-pill bg-danger py-2 px-3" style="font-size: 10px;"><?= strtoupper(htmlspecialchars($row['jenis_bahan'])) ?></span>
                </div>
            </div>

            <div class="d-flex align-items-center my-4">
                <div class="img-container" onclick="openEditFoto(<?= $row['id_varian'] ?>, 'uploads/<?= htmlspecialchars($row['foto_produk']) ?>')" style="cursor: pointer;" title="Klik untuk ubah foto">
                    <img src="uploads/<?= htmlspecialchars($row['foto_produk']) ?>" onerror="this.src='https://placehold.co/85x85?text=No+Image'"
                         class="rounded-4 shadow-sm" width="85" height="85" style="object-fit: cover; border: 4px solid #f8f9fa;">
                </div>
                <div class="ms-3 flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <span class="rounded-circle me-2" style="width: 15px; height: 15px; background-color: <?= htmlspecialchars($row['warna']) ?>; border: 1px solid #ddd;"></span>
                            <span class="fw-bold">
                                <?= !empty($row['nama_warna']) ? ucfirst(htmlspecialchars($row['nama_warna'])) : htmlspecialchars($row['warna']) ?>
                            </span>
                        </div>
                        <div>
                            <!-- Edit Nama & Warna Varian -->
                            <i class="bi bi-pencil-square text-muted me-2 cursor-pointer" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalEditWarna<?= $row['id_varian'] ?>" title="Edit Warna"></i>

                            <!-- Hapus Varian -->
                            
                        </div>
                    </div>
                    <div class="row g-0" style="font-size: 10px;">
                        <div class="col border-end pe-2">
                            <small class="d-block text-muted text-uppercase">SKU</small>
                            <span class="fw-bold text-success">ID: <?= $row['id_varian'] ?></span>
                        </div>
                        <div class="col ps-2">
                            <small class="d-block text-muted text-uppercase">Total Stok</small>
                            <span class="fw-bold <?= $total_tampilan == 0 ? 'text-muted' : 'text-danger' ?>" id="total-<?= $row['id_varian'] ?>">
                                <?= $total_tampilan ?> PCS
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN PER UKURAN -->
            <div class="row text-center g-2">
                <?php 
                $ukuran_ada = false;
                foreach (['s', 'm', 'l', 'xl', 'xxl'] as $size): 
                    $stok_sekarang = (int)$row['stok_'.$size];
                    // Tampilkan jika ukuran aktif/tersedia (stok >= 0)
                    if ($stok_sekarang >= 0): 
                        $ukuran_ada = true;
                ?>
                <div class="col">
                    <div class="p-2 border rounded-4 <?= $stok_sekarang == 0 ? 'bg-danger-subtle text-danger' : 'bg-light text-dark' ?>">
                        <small class="d-block text-uppercase fw-bold" style="font-size: 9px; opacity: 0.7;"><?= $size ?></small>
                        <span class="fw-bold d-block" style="font-size: 13px; min-height: 20px; line-height: 20px;">
                            <?= $stok_sekarang ?>
                        </span>
                    </div>
                </div>
                <?php 
                    endif; 
                endforeach; 

                if (!$ukuran_ada): 
                ?>
                <div class="col-12">
                    <span class="badge bg-secondary w-100 py-2">Ukuran Tidak Dikonfigurasi</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Edit Varian Warna -->
    <div class="modal fade" id="modalEditWarna<?= $row['id_varian'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h6 class="fw-bold mb-0">Edit Varian Warna</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="proses/edit_warna.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_varian" value="<?= $row['id_varian'] ?>">
                        
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Nama Warna</label>
                            <input type="text" name="nama_warna" class="form-control rounded-3" 
                                   value="<?= htmlspecialchars($row['nama_warna']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="small text-muted mb-1">Pilih Warna Visual</label>
                            <input type="color" name="kode_warna" class="form-control form-control-color w-100 rounded-3" 
                                   value="<?= htmlspecialchars($row['warna']) ?>" title="Pilih warna">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="submit" class="btn btn-dark w-100 rounded-pill">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Modal Ubah Foto -->
<div class="modal fade" id="modalEditFoto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form action="proses/UpdateFoto.php" method="POST" enctype="multipart/form-data" class="modal-content rounded-5 border-0">
            <div class="modal-header border-0 px-4 pt-4">
                <h6 class="fw-bold mb-0">Ubah Foto</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" name="id_varian" id="edit_id_varian">
                <img id="imgPreviewEdit" src="" class="rounded-4 mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <input type="file" name="foto" class="form-control form-control-sm" accept="image/*" required>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-dark w-100">Simpan Foto</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    <?php if($highlight_id != ''): ?>
    const element = document.getElementById('varian-<?= $highlight_id ?>');
    if (element) {
        setTimeout(() => {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 500);
    }
    <?php endif; ?>
});

function openEditFoto(id, src) {
    document.getElementById('edit_id_varian').value = id;
    document.getElementById('imgPreviewEdit').src = src;
    var myModal = new bootstrap.Modal(document.getElementById('modalEditFoto'));
    myModal.show();
}
</script>